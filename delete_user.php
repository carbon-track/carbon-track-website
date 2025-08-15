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
    
    // Add check to prevent deleting self? (Optional)
    if ($userId == $adminUserId) { handleApiError(400, 'Cannot delete your own account.'); }

    $email = opensslDecrypt($token);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        handleApiError(401, 'Invalid or expired token.');
    }

    // Verify admin status
    if (!isAdmin($email)) {
        handleApiError(403, 'Access denied.');
    }
    handleApiError(403, 'Access denied.');
    global $pdo; // Ensure $pdo from db.php is accessible
    if (!isset($pdo)) {
        require_once 'db.php';
        if(!isset($pdo)) {
            throw new Exception("Database connection is not available.");
        }
    }

    $sql = "DELETE FROM users WHERE id = :userId";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);

    if ($stmt->execute()) {
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'User deleted successfully.']);
            // Optional: Consider deleting related data (transactions, messages) here or setting up cascading deletes in DB.
        } else {
            handleApiError(404, 'User not found.');
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