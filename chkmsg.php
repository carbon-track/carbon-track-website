<?php
require 'db.php'; // 引入数据库连接
header('Content-Type: application/json');

function opensslDecrypt($data, $key)
{
    $data = base64_decode($data);
    $ivLength = openssl_cipher_iv_length('aes-256-cbc');
    $iv = substr($data, 0, $ivLength);
    $encrypted = substr($data, $ivLength);
    return openssl_decrypt($encrypted, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    $token = $_POST['token'];
    $base64Key = "28lVmS8LHIZIQdAmT6jyHal29N8g6aRZrHEA2mv/q/4=";
    $key = base64_decode($base64Key);
    $email = opensslDecrypt($token, $key);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Token不合法。']);
        exit; // 停止脚本执行
    }
    $userId = $_POST['uid'];

    try {
        $sql = "SELECT COUNT(*) AS unread_count FROM `messages` WHERE `receiver_id` = ? AND `is_read` = 0";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'unreadCount' => $result['unread_count']]);
    } catch (\PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => '114514']);
}
?>
