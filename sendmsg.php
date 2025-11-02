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
        $receiver_id = filter_var(sanitizeInput($data['receiver_id']), FILTER_VALIDATE_INT);
        $content = sanitizeInput($data['content']);
        $email = opensslDecrypt($token);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            handleApiError(401, 'Invalid token.');
        }
        if ($receiver_id === false || $receiver_id <= 0) {
            handleApiError(400, 'Invalid receiver.');
        }

        $sender_id = getUid($pdo, $email);
        if (!$sender_id) {
            handleApiError(404, 'User not found or inactive.');
        }

        $stmtReceiver = $pdo->prepare("SELECT id FROM users WHERE id = :id AND status = 'active'");
        $stmtReceiver->bindParam(':id', $receiver_id, PDO::PARAM_INT);
        $stmtReceiver->execute();
        if (!$stmtReceiver->fetch(PDO::FETCH_ASSOC)) {
            handleApiError(404, 'Receiver not found or inactive');
        }

        sendInAppMessage($pdo, $sender_id, $receiver_id, $content);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        logException($e);
    } catch (Exception $e) {
        logException($e);
    }
} else {
    handleApiError(405, '114514');
}
?>
