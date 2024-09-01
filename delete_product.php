<?php
require 'db.php'; // 包含数据库连接配置
require 'admin_emails.php';
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
    $token = $_POST['token'];
    $base64Key = "28lVmS8LHIZIQdAmT6jyHal29N8g6aRZrHEA2mv/q/4=";
    $key = base64_decode($base64Key);
    $email = opensslDecrypt($token, $key);
    if (!isAdmin($email)) {
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit; // Stop the script if not an admin
    }

    // 获取POST数据
    $product_id = $_POST['product_id'];

    // 删除产品信息
    $stmt = $pdo->prepare("DELETE FROM products WHERE product_id = ?");
    $stmt->execute([$product_id]);

    echo json_encode(['success' => $stmt->rowCount() > 0]);
}
?>
