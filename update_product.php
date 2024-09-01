<?php
require 'db.php'; // 包含数据库连接配置
require 'admin_emails.php';
header('Content-Type: application/json');

function opensslDecrypt($data, $key) {
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
        exit;
    }

    $product_id = $_POST['product_id'];
    $fieldsToUpdate = [];
    $params = [];

    // 检查每个字段是否存在，如果存在，则添加到更新列表
    if (isset($_POST['name'])) {
        $fieldsToUpdate[] = "name = ?";
        $params[] = $_POST['name'];
    }
    if (isset($_POST['description'])) {
        $fieldsToUpdate[] = "description = ?";
        $params[] = $_POST['description'];
    }
    if (isset($_POST['points_required'])) {
        $fieldsToUpdate[] = "points_required = ?";
        $params[] = $_POST['points_required'];
    }
    if (isset($_POST['stock'])) {
        $fieldsToUpdate[] = "stock = ?";
        $params[] = $_POST['stock'];
    }
    if (isset($_POST['image_path'])) {
        $fieldsToUpdate[] = "image_path = ?";
        $params[] = $_POST['image_path'];
    }

    // 如果没有提供要更新的字段，返回错误
    if (empty($fieldsToUpdate)) {
        echo json_encode(['success' => false, 'error' => 'No fields provided for update']);
        exit;
    }

    // 构建SQL语句
    $sql = "UPDATE products SET " . implode(', ', $fieldsToUpdate) . " WHERE product_id = ?";
    $params[] = $product_id; // 添加product_id作为参数

    // 执行更新操作
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    echo json_encode(['success' => $stmt->rowCount() > 0]);
}
?>
