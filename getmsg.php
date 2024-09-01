<?php
require 'db.php'; // 确保这个文件中定义了与数据库的连接 $pdo
header('Content-Type: application/json');

function opensslDecrypt($data, $key)
{
    $data = base64_decode($data);
    $ivLength = openssl_cipher_iv_length('aes-256-cbc');
    $iv = substr($data, 0, $ivLength);
    $encrypted = substr($data, $ivLength);
    return openssl_decrypt($encrypted, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
}

// 新增函数，用于查询未读消息
function getUnreadMessages($pdo, $receiver_id)
{
    // 使用参数化查询预防SQL注入
    $stmt = $pdo->prepare("SELECT * FROM messages WHERE receiver_id = :receiver_id OR sender_id = :sender_id");
    $stmt->execute(['receiver_id' => $receiver_id, 'sender_id' => $receiver_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function updateMsgStus($pdo, $receiver_id)
{
    // 使用参数化查询预防SQL注入
    $stmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE receiver_id = :receiver_id");
    $stmt->execute(['receiver_id' => $receiver_id]);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    $token = $data['token'];
    $base64Key = "28lVmS8LHIZIQdAmT6jyHal29N8g6aRZrHEA2mv/q/4=";
    $key = base64_decode($base64Key);
    $email = opensslDecrypt($token, $key);

    // 增强输入验证
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Token不合法。']);
        exit;
    }
    // 验证receiver_id是否为预期格式，例如数字
    $receiver_id = $data['receiver_id'];
    if (!is_numeric($receiver_id)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => '接收者ID不合法。']);
        exit;
    }

    try {
        $messages = getUnreadMessages($pdo, $receiver_id);
        updateMsgStus($pdo, $receiver_id);
        echo json_encode(['success' => true, 'messages' => $messages]);
    } catch (\PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => '不支持的请求方法。']);
}
?>
