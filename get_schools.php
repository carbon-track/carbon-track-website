<?php
require 'db.php'; // 引入数据库连接配置

header('Content-Type: application/json');
function opensslDecrypt($data, $key)
{
    $data = base64_decode($data);
    $ivLength = openssl_cipher_iv_length('aes-256-cbc');
    // 分离IV和加密数据
    $iv = substr($data, 0, $ivLength);
    $encrypted = substr($data, $ivLength);
    return openssl_decrypt($encrypted, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
}

// 检查是否有 POST 请求和 token 是否存在
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['token'])) {
//$inputData = json_decode(file_get_contents('php://input'), true);
    $token = $_POST['token'];
    $base64Key = "28lVmS8LHIZIQdAmT6jyHal29N8g6aRZrHEA2mv/q/4=";
    $key = base64_decode($base64Key);
    $email = opensslDecrypt($token, $key);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Token不合法']);
        exit; // 停止脚本执行
    }
    try {
        // 准备 SQL 语句
        $stmt = $pdo->prepare("SELECT id, name FROM schools"); // 假设您的学校表名为 schools
        $stmt->execute();

        // 获取查询结果
        $schools = $stmt->fetchAll();

        // 返回 JSON 格式的学校列表
        echo json_encode($schools);
    } catch (\PDOException $e) {
        // 出错时返回错误信息
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }

} else {
    // 没有 POST 请求或 token 缺失
    http_response_code(400);
    echo json_encode(['error' => 'Bad Request']);
}
