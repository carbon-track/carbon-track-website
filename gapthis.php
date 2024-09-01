<?php
// db.php - 包含数据库连接
require 'db.php';
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

    if (isset($_POST['token'])) {
        $token = $_POST['token'];
        $base64Key = "28lVmS8LHIZIQdAmT6jyHal29N8g6aRZrHEA2mv/q/4=";
        $key = base64_decode($base64Key);
        $email = opensslDecrypt($token, $key);

        if ($email === false) {
            echo json_encode(['success' => false, 'message' => 'token不合法！']);
            exit;
        }
        if (!isAdmin($email)) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit; // Stop the script if not an admin
        }
        $stmt = $pdo->prepare("SELECT * FROM points_transactions");
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // 将结果编码为JSON格式并返回
        echo json_encode(['success' => true, 'data' => $results]);
    } else {
        echo json_encode(['success' => false, 'message' => '请提供token！']);
    }
} else {
    echo json_encode(['success' => false, 'message' => '114514']);
}
?>
