<?php
require_once 'global_variables.php';
require_once 'global_error_handler.php';
require 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        $token = sanitizeInput($data['token']);
        $receiver_id = sanitizeInput($data['receiver_id']);
        $content = sanitizeInput($data['content']);
        $email = opensslDecrypt($token);
        $sender_id = getUid($pdo, $email);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            handleApiError(401, 'Invalid token.');
        }
        sendInAppMessage($pdo, $sender_id, $receiver_id, $content);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        logException($e);
        handleApiError(500, 'Database error.');
    } catch (Exception $e) {
        logException($e);
        handleApiError(500, 'Internal server error.');
    }
} else {
    handleApiError(405, '114514');
}
?>
