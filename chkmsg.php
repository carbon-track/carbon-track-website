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
        if (!$userId) {
            handleApiError(404, 'User not found or inactive.');
        }

        $sql = "SELECT COUNT(*) AS unread_count FROM `messages` WHERE `receiver_id` = ? AND `is_read` = 0";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        try {
            $currentTime = date('Y-m-d H:i:s');
            $updateSql = "UPDATE users SET lastlgn = :lastLoginTime WHERE id = :userId AND status = 'active'";
            $updateStmt = $pdo->prepare($updateSql);
            $updateStmt->bindParam(':lastLoginTime', $currentTime);
            $updateStmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            $updateStmt->execute();
        } catch (PDOException $updateE) {
            logException($updateE); 
        }

        echo json_encode(['success' => true, 'unreadCount' => $result['unread_count']]);
    } catch (PDOException $e) {
        logException($e);
    } catch (Exception $e) {
        logException($e);
    }
} else {
    handleApiError(405, '不支持的请求方法。');
}
?>
