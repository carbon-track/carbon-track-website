<?php
require_once 'global_variables.php';
require_once 'global_error_handler.php';
require_once 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    handleApiError(405, 'Invalid request method.');
}

try {
    if (!isset($_POST['token'])) {
        handleApiError(400, 'Token is required.');
    }

    $token = sanitizeInput($_POST['token']);
    $email = opensslDecrypt($token);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        handleApiError(401, 'Invalid token.');
    }

    global $pdo;
    if (!isset($pdo)) {
        require_once 'db.php';
    }
    if (!isset($pdo)) {
        throw new Exception('Database connection is not available.');
    }

    $stmt = $pdo->prepare("UPDATE users SET status = 'deleted' WHERE email = :email AND status = 'active'");
    $stmt->execute([':email' => $email]);

    if ($stmt->rowCount() === 0) {
        handleApiError(404, 'Account not found or already deactivated.');
    }

    echo json_encode(['success' => true, 'message' => 'Account deactivated successfully.']);
} catch (PDOException $e) {
    logException($e);
    handleApiError(500, 'Internal Server Error.');
} catch (Exception $e) {
    logException($e);
    handleApiError(500, 'Internal Server Error.');
}
// No closing tag
