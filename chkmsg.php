<?php
require_once 'global_variables.php';
require_once 'global_error_handler.php';
require 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $token = sanitizeInput($_POST['token']);
        $email = opensslDecrypt($token);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            handleApiError(401, 'Token不合法。');
        }
        $userId = getUid($pdo, $email);

        $sql = "SELECT COUNT(*) AS unread_count FROM `messages` WHERE `receiver_id` = ? AND `is_read` = 0";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'unreadCount' => $result['unread_count']]);
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
