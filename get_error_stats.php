<?php
// get_error_stats.php

require_once 'db.php';
require_once 'global_variables.php';
require_once 'admin_emails.php';

// Enable error reporting for debugging (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set response headers
header('Content-Type: application/json');

// Allow CORS (adjust as needed)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method Not Allowed.']);
    exit;
}

// Get request data
$input = json_decode(file_get_contents('php://input'), true);
$token = $input['token'] ?? '';
$key = base64_decode($base64Key);

// Authentication
if (empty($token)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Token is required.']);
    exit;
}

$email = opensslDecrypt($token, $key);

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !isAdmin($email)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized.']);
    exit;
}

// Get time range parameters (for hourly stats)
$hourlyStartDate = $input['hourly_start_date'] ?? null;
$hourlyEndDate = $input['hourly_end_date'] ?? null;

// Initialize $hourlyErrorStats
$hourlyErrorStats = [];

try {
    // Error type statistics
    $sql = "SELECT error_type, COUNT(*) as count FROM error_logs GROUP BY error_type";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $errorTypeStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Daily error statistics (last 7 days)
    $sql = "SELECT DATE(error_time) as date, COUNT(*) as count FROM error_logs WHERE error_time >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY DATE(error_time)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $dailyErrorStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Weekly error statistics
    $sql = "SELECT WEEKDAY(error_time) as weekday, COUNT(*) as count FROM error_logs GROUP BY WEEKDAY(error_time)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $weeklyErrorStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Hourly error statistics
    if ($hourlyStartDate && $hourlyEndDate) {
        $sql = "SELECT DATE_FORMAT(error_time, '%Y-%m-%d %H') as hour_label, COUNT(*) as count FROM error_logs WHERE error_time BETWEEN :start_date AND :end_date GROUP BY hour_label ORDER BY hour_label";
        $stmt = $pdo->prepare($sql);
        $startDateTime = $hourlyStartDate . ' 00:00:00';
        $endDateTime = $hourlyEndDate . ' 23:59:59';
        $stmt->bindParam(':start_date', $startDateTime);
        $stmt->bindParam(':end_date', $endDateTime);
        $stmt->execute();
        $hourlyErrorStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Return data
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'errorTypeStats' => $errorTypeStats,
        'dailyErrorStats' => $dailyErrorStats,
        'weeklyErrorStats' => $weeklyErrorStats,
        'hourlyErrorStats' => $hourlyErrorStats
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database query failed: ' . $e->getMessage()]);
}
?>
