<?php
/**
 * OpenAPI Specification Generator for PHP API Endpoints
 * 
 * This script analyzes PHP files in the current directory that appear to be API endpoints
 * and generates an OpenAPI v3 specification file.
 */

// Configuration
$outputFile = 'openapi.json';
$baseUrl = 'https://www.carbontrackapp.com/';
$apiTitle = 'PHP API Endpoints';
$apiVersion = '1.0.0';

// Files to exclude from analysis (not API endpoints)
$excludeFiles = [
    'global_variables.php',
    'global_error_handler.php',
    'db.php',
    'generate_openapi.php',
    'test.php'
];

// Get all PHP files in the current directory
$phpFiles = glob('*.php');
$phpFiles = array_filter($phpFiles, function($file) use ($excludeFiles) {
    return !in_array($file, $excludeFiles) && !is_dir($file);
});

// Initialize the OpenAPI specification
$openapi = [
    'openapi' => '3.0.0',
    'info' => [
        'title' => $apiTitle,
        'version' => $apiVersion
    ],
    'servers' => [
        [
            'url' => $baseUrl,
            'description' => 'Production server'
        ]
    ],
    'paths' => []
];

// Common response schemas
$commonResponses = [
    '500' => [
        'description' => 'Server error',
        'content' => [
            'application/json' => [
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'success' => ['type' => 'boolean', 'example' => false],
                        'error' => ['type' => 'string']
                    ]
                ]
            ]
        ]
    ]
];

// Process each PHP file
foreach ($phpFiles as $file) {
    echo "Processing $file...\n";
    $content = file_get_contents($file);
    
    // Determine HTTP method
    $method = 'post'; // Default to POST
    if (preg_match('/\$_SERVER\[\'REQUEST_METHOD\'\]\s*!==?\s*[\'"]POST[\'"]/i', $content)) {
        if (preg_match('/\$_SERVER\[\'REQUEST_METHOD\'\]\s*===?\s*[\'"]GET[\'"]/i', $content)) {
            $method = 'get';
        }
    }
    
    // Determine parameters
    $parameters = [];
    $requestBody = null;
    
    // Check for POST parameters
    if (preg_match_all('/\$_POST\[[\'"]([^\'"]+)[\'"]\]/', $content, $matches)) {
        $postParams = array_unique($matches[1]);
        
        $properties = [];
        $required = [];
        
        foreach ($postParams as $param) {
            $properties[$param] = ['type' => 'string'];
            
            // Check if parameter is required
            if (preg_match('/isset\(\$_POST\[[\'"]' . preg_quote($param, '/') . '[\'"]\]\)/', $content) || 
                preg_match('/empty\(\$_POST\[[\'"]' . preg_quote($param, '/') . '[\'"]\]\)/', $content)) {
                $required[] = $param;
            }
        }
        
        if (!empty($properties)) {
            $requestBody = [
                'required' => true,
                'content' => [
                    'application/x-www-form-urlencoded' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => $properties,
                            'required' => $required
                        ]
                    ]
                ]
            ];
        }
    }
    
    // Check for JSON input parameters (file_get_contents('php://input') + json_decode)
    if (preg_match('/file_get_contents\([\'"]php:\/\/input[\'"]\)/', $content) && 
        preg_match('/json_decode\(/', $content)) {
        
        // Look for parameters accessed via $data['param']
        if (preg_match_all('/\$data\[[\'"]([^\'"]+)[\'"]\]/', $content, $matches)) {
            $jsonParams = array_unique($matches[1]);
            
            $properties = [];
            $required = [];
            
            foreach ($jsonParams as $param) {
                $properties[$param] = ['type' => 'string'];
                
                // Check if parameter is required - look for patterns like:
                // if (!isset($data['token']))
                // if (!$data['token'])
                if (preg_match('/!\s*isset\(\s*\$data\[[\'"]' . preg_quote($param, '/') . '[\'"]\]\s*\)/', $content) || 
                    preg_match('/if\s*\(\s*!\s*\$data\[[\'"]' . preg_quote($param, '/') . '[\'"]\]/', $content)) {
                    $required[] = $param;
                }
            }
            
            if (!empty($properties)) {
                // Only set JSON requestBody if no form-encoded requestBody was already set
                if (!$requestBody) {
                    $requestBody = [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => $properties,
                                    'required' => $required
                                ]
                            ]
                        ]
                    ];
                }
            }
        }
    }
    
    // Check for GET parameters
    if (preg_match_all('/\$_GET\[[\'"]([^\'"]+)[\'"]\]/', $content, $matches)) {
        $getParams = array_unique($matches[1]);
        
        foreach ($getParams as $param) {
            $isRequired = preg_match('/isset\(\$_GET\[[\'"]' . preg_quote($param, '/') . '[\'"]\]\)/', $content) || 
                         preg_match('/empty\(\$_GET\[[\'"]' . preg_quote($param, '/') . '[\'"]\]\)/', $content);
            
            $parameters[] = [
                'name' => $param,
                'in' => 'query',
                'required' => $isRequired,
                'schema' => ['type' => 'string']
            ];
        }
    }
    
    // Determine response structure
    $successResponse = [
        'description' => 'Successful response',
        'content' => [
            'application/json' => [
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'success' => ['type' => 'boolean', 'example' => true]
                    ]
                ]
            ]
        ]
    ];
    
    // Check for JSON responses - improved detection
    $responseMatches = [];
    
    // Look for json_encode with array literals: json_encode(['key' => value])
    if (preg_match('/json_encode\(\s*\[([^\]]+)\]\s*\)/', $content, $matches)) {
        $responseProps = $matches[1];
        preg_match_all('/[\'"]([^\'"]+)[\'"]\s*=>\s*/', $responseProps, $propMatches);
        $responseMatches = array_merge($responseMatches, $propMatches[1]);
    }
    
    // Look for json_encode with variables: json_encode($response)
    if (preg_match_all('/json_encode\(\s*\$([a-zA-Z_][a-zA-Z0-9_]*)\s*\)/', $content, $matches)) {
        foreach ($matches[1] as $varName) {
            // Look for variable assignment patterns like $response = ['key' => ...]
            if (preg_match('/\$' . preg_quote($varName, '/') . '\s*=\s*\[([^\]]+)\]/', $content, $varMatches)) {
                preg_match_all('/[\'"]([^\'"]+)[\'"]\s*=>\s*/', $varMatches[1], $propMatches);
                $responseMatches = array_merge($responseMatches, $propMatches[1]);
            }
        }
    }
    
    if (!empty($responseMatches)) {
        $responseProperties = ['success' => ['type' => 'boolean']];
        
        foreach (array_unique($responseMatches) as $prop) {
            if ($prop !== 'success') {
                // Try to determine property type
                $type = 'string';
                
                if (strpos($prop, 'count') !== false || 
                    strpos($prop, 'id') !== false || 
                    strpos($prop, 'points') !== false) {
                    $type = 'integer';
                } else if (strpos($prop, 'is_') === 0 || 
                          $prop === 'success') {
                    $type = 'boolean';
                } else if ($prop === 'messages' || $prop === 'data' || $prop === 'users' || 
                          $prop === 'leaderboard' || strpos($prop, 'list') !== false || 
                          strpos($prop, 'array') !== false ||
                          (strpos($prop, 's') === strlen($prop) - 1 && strlen($prop) > 3)) {
                    $type = 'array';
                    $responseProperties[$prop] = [
                        'type' => 'array',
                        'items' => ['type' => 'object']
                    ];
                    continue;
                } else if ($prop === 'debug' || strpos($prop, 'info') !== false) {
                    $type = 'object';
                    $responseProperties[$prop] = ['type' => 'object'];
                    continue;
                }
                
                $responseProperties[$prop] = ['type' => $type];
            }
        }
        
        $successResponse['content']['application/json']['schema']['properties'] = $responseProperties;
    }
    
    // Determine error responses
    $errorResponses = $commonResponses;
    
    if (preg_match('/400/', $content)) {
        $errorResponses['400'] = [
            'description' => 'Bad request',
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'success' => ['type' => 'boolean', 'example' => false],
                            'message' => ['type' => 'string']
                        ]
                    ]
                ]
            ]
        ];
    }
    
    if (preg_match('/401/', $content)) {
        $errorResponses['401'] = [
            'description' => 'Unauthorized',
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'success' => ['type' => 'boolean', 'example' => false],
                            'message' => ['type' => 'string']
                        ]
                    ]
                ]
            ]
        ];
    }
    
    if (preg_match('/403/', $content)) {
        $errorResponses['403'] = [
            'description' => 'Forbidden',
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'success' => ['type' => 'boolean', 'example' => false],
                            'message' => ['type' => 'string']
                        ]
                    ]
                ]
            ]
        ];
    }
    
    // Merge responses while preserving status-code keys
    $responses = array_replace(
        ['200' => $successResponse],
        $commonResponses,
        $errorResponses
    );

    // Create the path item
    $pathItem = [
        $method => [
            'summary' => ucfirst(str_replace('_', ' ', pathinfo($file, PATHINFO_FILENAME))),
            'responses' => $responses
        ]
    ];
    
    if (!empty($parameters)) {
        $pathItem[$method]['parameters'] = $parameters;
    }
    
    if ($requestBody) {
        $pathItem[$method]['requestBody'] = $requestBody;
    }
    
    // Add the path to the OpenAPI specification
    $openapi['paths']['/' . $file] = $pathItem;
}

// Save the OpenAPI specification to a file
file_put_contents($outputFile, json_encode($openapi, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

echo "OpenAPI specification has been generated and saved to $outputFile\n"; 
