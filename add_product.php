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
    $name = $_POST['name'];
    $description = $_POST['description'];
    $points_required = $_POST['points_required'];
    $stock = $_POST['stock'];
    $image_path = 'img'; // 假设图片路径已经处理好了

    // 插入产品信息到数据库
    $stmt = $pdo->prepare("INSERT INTO products (name, description, points_required, stock, image_path) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$name, $description, $points_required, $stock, $image_path]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        // 获取错误信息
        $errorInfo = $stmt->errorInfo();
        echo json_encode(['success' => false, 'error' => $errorInfo[2]]);
    }
}
?>
