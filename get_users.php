<?php
require_once 'global_variables.php';
require_once 'global_error_handler.php';
require_once 'db.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        handleApiError(405, 'Invalid request method.');
    }

    if (!isset($_POST['token'])) {
        handleApiError(400, 'Token is required.');
    }

    $token = sanitizeInput($_POST['token']);
    $email = opensslDecrypt($token);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        handleApiError(401, 'Invalid or expired token.');
    }

    // Verify admin status
    if (!isAdmin($email)) {
        handleApiError(403, 'Access denied. Administrator privileges required.');
    }

    $searchTerm = isset($_POST['search']) ? sanitizeInput($_POST['search']) : '';

    global $pdo; // Ensure $pdo from db.php is accessible
    if (!isset($pdo)) {
        // Attempt to include db.php again if $pdo is somehow not set
        // This is a fallback, ideally db.php is included correctly at the top
        require_once 'db.php'; 
        if(!isset($pdo)) {
             throw new Exception("Database connection is not available.");
        }
    }
    
    // Base query - exclude soft-deleted accounts
    $sql = "SELECT id, username, email, points, school, lastlgn, status FROM users WHERE status <> 'deleted'";
    $params = [];

    // Add search condition if search term exists
    if (!empty($searchTerm)) {
        $sql .= " AND (username LIKE :search OR email LIKE :search)";
        $params[':search'] = '%' . $searchTerm . '%';
    }
    
    // Add ordering
    $sql .= " ORDER BY id ASC"; // Or username, points, etc.

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'users' => $users]);

} catch (PDOException $e) {
    logException($e);
    handleApiError(500, 'Database error while fetching users.');
} catch (Exception $e) {
    logException($e);
    handleApiError(500, 'An unexpected error occurred.');
}
?> 
