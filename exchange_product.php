<?php
require_once 'global_variables.php';
require_once 'global_error_handler.php';
require 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if (!isset($data['productId']) || !isset($data['token'])) {
            handleApiError(400, 'Missing required fields');
        }

        $productId = sanitizeInput($data['productId']);
        $token = sanitizeInput($data['token']);
        $email = opensslDecrypt($token);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            handleApiError(401, 'Token不合法。');
        }

        $pdo = new PDO($dsn, $user, $pass, $options);
        $pdo->beginTransaction();

        $stmtUser = $pdo->prepare("SELECT points FROM users WHERE email = :email");
        $stmtUser->bindParam(':email', $email, PDO::PARAM_STR);
        $stmtUser->execute();
        $userPoints = $stmtUser->fetchColumn();

        $stmtProduct = $pdo->prepare("SELECT points_required FROM products WHERE product_id = :productId");
        $stmtProduct->bindParam(':productId', $productId, PDO::PARAM_INT);
        $stmtProduct->execute();
        $productPoints = $stmtProduct->fetchColumn();

        if ($userPoints !== false && $productPoints !== false && $userPoints >= $productPoints) {
            $stmtUpdatePoints = $pdo->prepare("UPDATE users SET points = points - :productPoints WHERE email = :email");
            $stmtUpdatePoints->bindParam(':productPoints', $productPoints, PDO::PARAM_INT);
            $stmtUpdatePoints->bindParam(':email', $email, PDO::PARAM_STR);
            $stmtUpdatePoints->execute();

            $stmtUpdatePdt = $pdo->prepare("UPDATE products SET stock = stock - 1 WHERE product_id = :productId");
            $stmtUpdatePdt->bindParam(':productId', $productId, PDO::PARAM_INT);
            $stmtUpdatePdt->execute();

            $stmtInsertTransaction = $pdo->prepare("INSERT INTO transactions (user_email, product_id, points_spent, transaction_time) VALUES (:email, :productId, :productPoints, NOW())");
            $stmtInsertTransaction->bindParam(':email', $email, PDO::PARAM_STR);
            $stmtInsertTransaction->bindParam(':productId', $productId, PDO::PARAM_INT);
            $stmtInsertTransaction->bindParam(':productPoints', $productPoints, PDO::PARAM_INT);
            $stmtInsertTransaction->execute();

            $pdo->commit();

            echo json_encode(['success' => true]);
        } else {
            $pdo->rollBack();
            echo json_encode([
                'success' => false,
                'error' => '积分不足或商品不存在',
                'userPoints' => $userPoints,
                'productPoints' => $productPoints
            ]);
        }
    } catch (PDOException $e) {
        if (isset($pdo)) {
            $pdo->rollBack();
        }
        logException($e);
        handleApiError(500, 'Database error');
        exit;
    } catch (Exception $e) {
        logException($e);
        handleApiError(500, 'Internal server error');
        exit;
    }
} else {
    handleApiError(405, '不支持的请求方法。');
    exit;
}
?>
