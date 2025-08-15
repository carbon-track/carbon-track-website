<?php
/**
 * OpenAPI Specification Merger
 * 
 * This script merges the two OpenAPI specification files into a complete specification.
 */

// Load the two specification files
$part1 = json_decode(file_get_contents('openapi_enhanced.json'), true);
$part2 = json_decode(file_get_contents('openapi_enhanced_part2.json'), true);

// Merge the paths from part2 into part1
foreach ($part2['paths'] as $path => $methods) {
    $part1['paths'][$path] = $methods;
}

// Save the merged specification to a file
file_put_contents('openapi_complete.json', json_encode($part1, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

echo "OpenAPI specifications have been merged and saved to openapi_complete.json\n";