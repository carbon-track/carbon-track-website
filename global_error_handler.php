<?php
// global_error_handler.php

// 包含数据库连接文件
//require_once 'db.php'; // 确保设置了 $pdo 变量
//包含db.php会循环引用！！！我花半个小时才查出来db.php引用这里的logException() 这个会涉及到db.php里的自定义$pdo 最后$dsn就会为null
require_once 'global_variables.php';

error_reporting(E_ALL);
// 启动输出缓冲
ob_start();

/**
 * 日志记录函数
 *
 * @param array $errorData 错误数据
 */
function logError($errorData)
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            INSERT INTO error_logs (
                error_type, error_message, error_file, error_line, error_time,
                script_name, client_get, client_post, client_files, client_cookie, client_session, client_server
            ) VALUES (
                :error_type, :error_message, :error_file, :error_line, :error_time,
                :script_name, :client_get, :client_post, :client_files, :client_cookie, :client_session, :client_server
            )
        ");

        $stmt->bindParam(':error_type', $errorData['type']);
        $stmt->bindParam(':error_message', $errorData['message']);
        $stmt->bindParam(':error_file', $errorData['file']);
        $stmt->bindParam(':error_line', $errorData['line']);
        $stmt->bindParam(':error_time', $errorData['time']);
        $stmt->bindParam(':script_name', $errorData['script']);

        // 序列化客户端数据
        $clientGetJson = json_encode($errorData['client']['GET'], JSON_UNESCAPED_UNICODE);
        $clientPostJson = json_encode($errorData['client']['POST'], JSON_UNESCAPED_UNICODE);
        $clientFilesJson = json_encode($errorData['client']['FILES'], JSON_UNESCAPED_UNICODE);
        $clientCookieJson = json_encode($errorData['client']['COOKIE'], JSON_UNESCAPED_UNICODE);
        $clientSessionJson = json_encode($errorData['client']['SESSION'], JSON_UNESCAPED_UNICODE);
        $clientServerJson = json_encode($errorData['client']['SERVER'], JSON_UNESCAPED_UNICODE);

        $stmt->bindParam(':client_get', $clientGetJson);
        $stmt->bindParam(':client_post', $clientPostJson);
        $stmt->bindParam(':client_files', $clientFilesJson);
        $stmt->bindParam(':client_cookie', $clientCookieJson);
        $stmt->bindParam(':client_session', $clientSessionJson);
        $stmt->bindParam(':client_server', $clientServerJson);

        $stmt->execute();
    } catch (PDOException $e) {
        // 如果数据库连接失败，可以考虑将错误记录到文件
        error_log('Database error in logError: ' . $e->getMessage());
    }
}

/**
 * 数组枚举函数，过滤敏感信息
 *
 * @param array $array 输入数组
 * @return array 过滤后的数组
 */
function enumerateArray($array)
{
    $result = [];
    $sensitiveFields = ['password', 'passwd', 'pwd', 'credit_card'];

    foreach ($array as $key => $value) {
        if (in_array(strtolower($key), $sensitiveFields)) {
            $result[$key] = '***REDACTED***';
        } else {
            if (is_array($value)) {
                $result[$key] = enumerateArray($value);
            } else {
                $result[$key] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            }
        }
    }
    return $result;
}

/**
 * 处理 API 错误，记录日志并发送 JSON 响应
 *
 * @param int $statusCode HTTP 状态码
 * @param string|Exception $error 错误信息或异常对象
 */
function handleApiError($statusCode, $error)
{
    if ($error instanceof Exception) {
        $message = $error->getMessage() . "\n" . $error->getTraceAsString();
        $file = $error->getFile();
        $line = $error->getLine();
        $type = 'Exception';
    } else {
        $message = $error;
        $file = '';
        $line = 0;
        $type = 'API Error';
    }

    if ($type === 'API Error' || $type === 'Exception') {
        $errorData = [
            'type' => $type,
            'message' => $message,
            'file' => $file,
            'line' => $line,
            'time' => date('Y-m-d H:i:s'),
            'script' => $_SERVER['SCRIPT_NAME'],
            'client' => [
                'GET' => $_GET,
                'POST' => $_POST,
                'FILES' => $_FILES,
                'COOKIE' => $_COOKIE,
                'SESSION' => isset($_SESSION) ? $_SESSION : [],
                'SERVER' => $_SERVER,
            ],
        ];

        // 记录错误信息
        logError($errorData);
    }

    // 返回 JSON 错误消息
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=UTF-8');
    $responseMessage = $message . ' If the error persists, please contact carbontrack666@gmail.com and attach the timestamp.';
    if ($statusCode === 405) {
        $responseMessage = '114514';
    }
    echo json_encode([
        'success' => false,
        'message' => $responseMessage,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * 错误处理函数
 */
function customErrorHandler($errno, $errstr, $errfile, $errline)
{
    // 忽略被 @ 符号抑制的错误
    if (!(error_reporting() & $errno)) {
        return false;
    }

    // 防止重复调用错误处理程序
    static $hasHandledError = false;
    if ($hasHandledError) {
        return false;
    }
    $hasHandledError = true;

    $errorData = [
        'type' => 'Error',
        'message' => "$errstr in $errfile on line $errline",
        'file' => $errfile,
        'line' => $errline,
        'time' => date('Y-m-d H:i:s'),
        'script' => $_SERVER['SCRIPT_NAME'],
        'client' => [
            'GET' => $_GET,
            'POST' => $_POST,
            'FILES' => $_FILES,
            'COOKIE' => $_COOKIE,
            'SESSION' => isset($_SESSION) ? $_SESSION : [],
            'SERVER' => $_SERVER,
        ],
    ];

    // 记录错误信息
    logError($errorData);
    
    // 返回 JSON 错误消息
    http_response_code(500);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'message' => 'Internal Server Error.'.' If the error persists, please contact carbontrack666@gmail.com and attach the timestamp.',
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    
    // 结束脚本执行
    exit;
}

/**
 * 异常处理函数
 */
function customExceptionHandler($exception)
{
    $errorData = [
        'type' => 'Exception',
        'message' => $exception->getMessage() . "\n" . $exception->getTraceAsString(),
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'time' => date('Y-m-d H:i:s'),
        'script' => $_SERVER['SCRIPT_NAME'],
        'client' => [
            'GET' => $_GET,
            'POST' => $_POST,
            'FILES' => $_FILES,
            'COOKIE' => $_COOKIE,
            'SESSION' => isset($_SESSION) ? $_SESSION : [],
            'SERVER' => $_SERVER,
        ],
    ];

    // 记录异常信息
    logError($errorData);

    // 返回 JSON 错误消息
    http_response_code(500);
    //header('Location: /err/500.html');
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'message' => 'Internal Server Error.'.' If the error persists, please contact carbontrack666@gmail.com and attach the timestamp.',
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);

    // 结束脚本执行
    exit;
}

/**
 * 关闭函数，处理致命错误和自动记录 4xx 错误
 */
function shutdownFunction()
{
    $lastError = error_get_last();

    // 检查是否有致命错误
    if ($lastError && ($lastError['type'] & (E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR))) {
        $errorData = [
            'type' => 'Fatal Error',
            'message' => "{$lastError['message']} in {$lastError['file']} on line {$lastError['line']}",
            'file' => $lastError['file'],
            'line' => $lastError['line'],
            'time' => date('Y-m-d H:i:s'),
            'script' => $_SERVER['SCRIPT_NAME'],
            'client' => [
                'GET' => $_GET,
                'POST' => $_POST,
                'FILES' => $_FILES,
                'COOKIE' => $_COOKIE,
                'SESSION' => isset($_SESSION) ? $_SESSION : [],
                'SERVER' => $_SERVER,
            ],
        ];

        // 记录致命错误
        logError($errorData);
        
        // 返回 JSON 错误消息
        http_response_code(500);
        //header('Location: /err/500.html');
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'message' => 'Internal Server Error.'.' If the error persists, please contact carbontrack666@gmail.com and attach the timestamp.',
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    // 检查是否有 4xx 响应码且未被记录
    global $hasLogged4xxError;
    if (!isset($hasLogged4xxError)) {
        $currentCode = http_response_code();
        if ($currentCode >= 400 && $currentCode < 500) {
            // 获取输出缓冲内容
            $output = ob_get_clean();

            // 尝试解析 JSON 错误消息
            $errorMessage = 'Client Error';
            $decoded = json_decode($output, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                if (isset($decoded['error'])) {
                    $errorMessage = $decoded['error'];
                } elseif (isset($decoded['message'])) {
                    $errorMessage = $decoded['message'];
                }
            } else {
                // 如果无法解析，使用输出内容作为错误信息
                $errorMessage = $output;
            }

            $errorData = [
                'type' => 'HTTP '.$currentCode.' Error',
                'message' => $errorMessage,
                'file' => '',
                'line' => 0,
                'time' => date('Y-m-d H:i:s'),
                'script' => $_SERVER['SCRIPT_NAME'],
                'client' => [
                    'GET' => $_GET,
                    'POST' => $_POST,
                    'FILES' => $_FILES,
                    'COOKIE' => $_COOKIE,
                    'SESSION' => isset($_SESSION) ? $_SESSION : [],
                    'SERVER' => $_SERVER,
                ],
            ];

            // 记录 4xx 错误
            logError($errorData);

            // 返回 JSON 错误消息
            http_response_code($currentCode);
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode([
                'message' => $errorMessage.' If the error persists, please contact carbontrack666@gmail.com and attach the current time.',
                'timestamp' => date('Y-m-d H:i:s')
            ], JSON_UNESCAPED_UNICODE);
        } else {
            // 对于非 4xx 响应码，发送输出缓冲内容
            ob_end_flush();
        }
    } else {
        // 如果已经记录了 4xx 错误，发送输出缓冲内容
        ob_end_flush();
    }
}

/**
 * 用来处理PDOException的函数
 * 
 * 
 * @param Exception $exception 异常对象
 */
function logException($exception)
{
    customExceptionHandler($exception);
}

// 注册错误、异常、关闭处理器
set_error_handler('customErrorHandler');
set_exception_handler('customExceptionHandler');
register_shutdown_function('shutdownFunction');
?>
