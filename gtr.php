<?php
// require 'db.php'; // Included via global_variables
require_once 'global_variables.php';
require_once 'global_error_handler.php';

header('Content-Type: application/json; charset=UTF-8');

// Assuming this endpoint doesn't require authentication and can be GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
     handleApiError(405, 'Method Not Allowed.');
}

try {
    global $pdo;
    if (!$pdo) {
         handleApiError(500, 'Database connection is not available.');
    }

    // Get total approved reduction points
    $stmtPoints = $pdo->query("SELECT SUM(points) AS total_reduction FROM points_transactions WHERE auth ='yes'");
    $rowPoints = $stmtPoints->fetch(PDO::FETCH_ASSOC);
    if ($rowPoints === false) {
        throw new Exception('Failed to fetch total reduction points.');
    }

    // Get total number of transactions
    $stmtTimes = $pdo->query("SELECT COUNT(*) AS total_times FROM points_transactions");
    $rowTimes = $stmtTimes->fetch(PDO::FETCH_ASSOC);
     if ($rowTimes === false) {
        throw new Exception('Failed to fetch total transaction times.');
    }
    
    // Format points, handle potential NULL if no approved transactions
    $totalPoints = $rowPoints['total_reduction'] ?? 0;
    $formattedPoints = number_format((float)$totalPoints, 2, '.', '');
    
    echo json_encode([
        'success' => true,
        'points' => $formattedPoints, // Formatted total points
        'times' => $rowTimes['total_times'] ?? 0 // Total transaction count
    ]);

} catch (PDOException $e) {
    logException($e); // Log and exit
} catch (Exception $e) {
    logException($e); // Log and exit
}
?>
