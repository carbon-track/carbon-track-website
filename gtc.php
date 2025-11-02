<?php
// require 'db.php'; // Included via global_variables
require_once 'global_variables.php';
require_once 'global_error_handler.php';

header('Content-Type: application/json; charset=UTF-8');

// Assuming this endpoint doesn't require authentication and can be GET
// If authentication or POST is needed, add checks.
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
     handleApiError(405, 'Method Not Allowed.');
}

try {
    global $pdo;
    if (!$pdo) {
         handleApiError(500, 'Database connection is not available.');
    }
    
    $stmt = $pdo->query("SELECT COUNT(*) AS user_count FROM users WHERE status = 'active'");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Check if fetch was successful
    if ($row === false) {
        throw new Exception('Failed to fetch user count.');
    }
    
    echo json_encode(['success' => true, 'count' => $row['user_count'] ?? 0]); // Use null coalescing

} catch (PDOException $e) {
    logException($e); // Log and exit
} catch (Exception $e) {
    logException($e); // Log and exit
}
?>
