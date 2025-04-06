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
             handleApiError(401, 'Token不合法或已过期');
        }

        if (isAdmin($email)) {
            echo json_encode(['success' => true, 'isAdmin' => true]);
        } else {
            echo json_encode(['success' => true, 'isAdmin' => false]);
        }
    } catch (Exception $e) {
        logException($e);
    }
} else {
    handleApiError(405, 'Invalid request method.');
}
?>
