<?php
require_once 'global_variables.php';
require_once 'global_error_handler.php';
require_once 'db.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        handleApiError(405, 'Invalid request method.');
    }

    // Validate inputs
    if (!isset($_POST['token'], $_POST['userId'], $_POST['points'])) {
         handleApiError(400, 'Missing required fields (token, userId, points).');
    }

    $token = sanitizeInput($_POST['token']);
    $userId = filter_var(sanitizeInput($_POST['userId']), FILTER_VALIDATE_INT);
    $points = filter_var(sanitizeInput($_POST['points']), FILTER_VALIDATE_FLOAT); // Allow decimals for points
    $school = isset($_POST['school']) ? sanitizeInput($_POST['school']) : null; // School is optional

    if ($userId === false || $userId <= 0) {
         handleApiError(400, 'Invalid User ID.');
    }
     if ($points === false) { // Points can be 0, check specifically for false
         handleApiError(400, 'Invalid points value.');
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

    $sql = "UPDATE users SET points = :points, school = :school WHERE id = :userId AND status = 'active'";
    $stmt = $pdo->prepare($sql);

    $stmt->bindParam(':points', $points);
    $stmt->bindParam(':school', $school, PDO::PARAM_STR); // Bind school, allowing NULL
    $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);

    if ($stmt->execute()) {
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'User updated successfully.']);
        } else {
            // User ID might not exist, or data was the same
             handleApiError(404, 'User not found or no changes made.');
        }
    } else {
         throw new Exception("Failed to execute user update statement.");
    }

} catch (PDOException $e) {
    logException($e);
    handleApiError(500, 'Internal Server Error.');
} catch (Exception $e) {
    logException($e);
    handleApiError(500, 'Internal Server Error.');
}
?> 