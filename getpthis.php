<?php
require_once 'global_variables.php';
require_once 'global_error_handler.php';
require 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        if (!isset($_POST['token'])) {
            handleApiError(400, 'Token is required');
        }

        $token = sanitizeInput($_POST['token']);
        $email = opensslDecrypt($token);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            handleApiError(401, 'Token不合法！');
        }

        $pdo = new PDO($dsn, $user, $pass, $options);
        $stmt = $pdo->prepare("
            SELECT p.id, p.email, p.time, p.img, p.points, p.auth, p.act, p.raw, p.notes, p.activity_date, 
                   u.username 
            FROM points_transactions p
            LEFT JOIN users u ON p.email = u.email
            WHERE p.email = :email
        ");
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'data' => $results]);
    } catch (PDOException $e) {
        logException($e);
        handleApiError(500, 'Database error');
    } catch (Exception $e) {
        logException($e);
        handleApiError(500, 'Internal server error');
    }
} else {
    handleApiError(405, '不支持的请求方法。');
}
?>
