<?php
// get_error_logs.php

require_once 'db.php';
require_once 'global_variables.php';
require_once 'admin_emails.php';

// 设置响应头
header('Content-Type: application/json');

// 允许跨域请求（根据需要调整）
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');

// 仅允许 POST 请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method Not Allowed.']);
    exit;
}

// 获取请求数据
$input = json_decode(file_get_contents('php://input'), true);
$token = $input['token'] ?? '';
$keyword = $input['keyword'] ?? '';
$startDate = $input['start_date'] ?? '';
$endDate = $input['end_date'] ?? '';
$errorType = $input['error_type'] ?? '';
$key = base64_decode($base64Key);

// 鉴权
if (empty($token)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Token is required.']);
    exit;
}

$email = opensslDecrypt($token, $key);

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !isAdmin($email)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized.']);
    exit;
}

// 初始化查询条件和参数
$conditions = [];
$params = [];

// 根据关键词搜索（在多个字段中搜索）
if (!empty($keyword)) {
    $keyword = '%' . $keyword . '%';
    $conditions[] = "(error_message LIKE ? OR error_file LIKE ? OR error_type LIKE ?)";
    $params = array_merge($params, [$keyword, $keyword, $keyword]);
}

// 根据日期范围过滤
if (!empty($startDate)) {
    $conditions[] = "error_time >= ?";
    $params[] = $startDate . ' 00:00:00';
}

if (!empty($endDate)) {
    $conditions[] = "error_time <= ?";
    $params[] = $endDate . ' 23:59:59';
}

// 根据错误类型过滤
$httpErrorTypes = ['HTTP 400 Error', 'HTTP 401 Error','HTTP 403 Error', 'HTTP 404 Error','HTTP 422 Error', 'HTTP 500 Error'];

if (!empty($errorType)) {
    if ($errorType === 'HTTP Errors') {
        // HTTP Errors 包含多个类型
        $placeholders = implode(',', array_fill(0, count($httpErrorTypes), '?'));
        $conditions[] = "error_type IN ($placeholders)";
        $params = array_merge($params, $httpErrorTypes);
    } else {
        $conditions[] = "error_type = ?";
        $params[] = $errorType;
    }
}

// 构建 SQL 查询
$sql = "SELECT * FROM error_logs";
if (count($conditions) > 0) {
    $sql .= " WHERE " . implode(' AND ', $conditions);
}
$sql .= " ORDER BY error_time DESC";

// 获取错误日志
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 返回数据
    http_response_code(200);
    echo json_encode(['success' => true, 'data' => $logs]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database query failed: ' . $e->getMessage()]);
}
?>