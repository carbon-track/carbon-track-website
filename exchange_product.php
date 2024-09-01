<?php
require 'db.php';
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
    // 从JSON请求体中获取数据
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    $productId = $data['productId'];
    $token = $data['token'];
    $base64Key = "28lVmS8LHIZIQdAmT6jyHal29N8g6aRZrHEA2mv/q/4=";
    $key = base64_decode($base64Key);
    $email = opensslDecrypt($token, $key);

    // 开始事务
    $pdo->beginTransaction();
    try {
        // 获取用户积分
        $stmtUser = $pdo->prepare("SELECT points FROM users WHERE email = :email");
        $stmtUser->bindParam(':email', $email, PDO::PARAM_STR);
        $stmtUser->execute();
        $userPoints = $stmtUser->fetchColumn();

        // 获取商品所需积分
        $stmtProduct = $pdo->prepare("SELECT points_required FROM products WHERE product_id = :productId");
        $stmtProduct->bindParam(':productId', $productId, PDO::PARAM_INT);
        $stmtProduct->execute();
        $productPoints = $stmtProduct->fetchColumn();

        if ($userPoints !== false && $productPoints !== false && $userPoints >= $productPoints) {
            // 扣除用户积分
            $stmtUpdatePoints = $pdo->prepare("UPDATE users SET points = points - :productPoints WHERE email = :email");
            $stmtUpdatePoints->bindParam(':productPoints', $productPoints, PDO::PARAM_INT);
            $stmtUpdatePoints->bindParam(':email', $email, PDO::PARAM_STR);
            $stmtUpdatePoints->execute();

            $stmtUpdatePdt = $pdo->prepare("UPDATE products SET stocks = stocks - 1 WHERE product_id = :productId");
            $stmtUpdatePdt->bindParam(':productId', $productId, PDO::PARAM_INT);
            $stmtUpdatePdt->execute();

            // 添加交易记录，包括交易时间
            $stmtInsertTransaction = $pdo->prepare("INSERT INTO transactions (user_email, product_id, points_spent, transaction_time) VALUES (:email, :productId, :productPoints, NOW())");
            $stmtInsertTransaction->bindParam(':email', $email, PDO::PARAM_STR);
            $stmtInsertTransaction->bindParam(':productId', $productId, PDO::PARAM_INT);
            $stmtInsertTransaction->bindParam(':productPoints', $productPoints, PDO::PARAM_INT);
            $stmtInsertTransaction->execute();

            // 提交事务
            $pdo->commit();

            echo json_encode(['success' => true]);
        } else {
            // 如果积分不足或商品不存在
            $pdo->rollBack();
            echo json_encode([
                'success' => false,
                'error' => '积分不足或商品不存在',
                'userPoints' => $userPoints,
                'productPoints' => $productPoints
            ]);
        }
    } catch (\PDOException $e) {
        // 如果有错误，回滚事务
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => '非POST请求']);
}
?>
