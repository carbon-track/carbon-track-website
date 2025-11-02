<?php
require_once 'global_variables.php';
require_once 'global_error_handler.php';
require_once 'db.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        handleApiError(405, 'Invalid request method.');
    }

    if (!isset($_POST['token'], $_POST['userId'])) {
        handleApiError(400, 'Missing required fields (token, userId).');
    }

    $token = sanitizeInput($_POST['token']);
    $userId = filter_var(sanitizeInput($_POST['userId']), FILTER_VALIDATE_INT);

    if ($userId === false || $userId <= 0) {
        handleApiError(400, 'Invalid User ID.');
    }
    $email = opensslDecrypt($token);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        handleApiError(401, 'Invalid or expired token.');
    }

    // Verify admin status
    if (!isAdmin($email)) {
        handleApiError(403, 'Access denied.');
    }
    global $pdo; // Ensure $pdo from db.php is accessible
    if (!isset($pdo)) {
        require_once 'db.php';
        if(!isset($pdo)) {
            throw new Exception("Database connection is not available.");
        }
    }

    $adminId = getUid($pdo, $email);
    if ($adminId !== null && $adminId === $userId) {
        handleApiError(400, 'Cannot deactivate your own account.');
    }

    $sql = "UPDATE users SET status = 'deleted' WHERE id = :userId AND status <> 'deleted'";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);

    if ($stmt->execute()) {
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'User deactivated successfully.']);
        } else {
            handleApiError(404, 'User not found or already deactivated.');
        }
    } else {
         throw new Exception("Failed to execute user delete statement.");
    }

} catch (PDOException $e) {
    logException($e);
    handleApiError(500, 'Internal Server Error.');
} catch (Exception $e) {
    logException($e);
    handleApiError(500, 'Internal Server Error.');
}
?> 
