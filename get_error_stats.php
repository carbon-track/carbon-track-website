<?php
// get_error_stats.php

// require_once 'db.php'; // Included via global_variables
require_once 'global_variables.php';
require_once 'global_error_handler.php';
// require_once 'admin_emails.php'; // Not needed if using isAdmin()

// Remove development-specific error reporting
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

// Set response headers
header('Content-Type: application/json; charset=UTF-8');
// header('Access-Control-Allow-Origin: *'); // Consider making this more specific
// header('Access-Control-Allow-Headers: Content-Type');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    handleApiError(405, 'Method Not Allowed.');
}

// Wrap logic in try block
try {
// Get request data
$input = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        handleApiError(400, 'Invalid JSON input.');
    }
$token = $input['token'] ?? '';
    // $key = base64_decode($base64Key); // From global_variables

    // Authentication and Authorization
if (empty($token)) {
        handleApiError(400, 'Token is required.');
}

    $email = opensslDecrypt($token); // Use key from global_variables

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !isAdmin($email)) {
        handleApiError(403, 'Unauthorized.');
    }
    
    global $pdo;
    if (!$pdo) {
         handleApiError(500, 'Database connection is not available.');
}

// Get time range parameters (for hourly stats)
$hourlyStartDate = $input['hourly_start_date'] ?? null;
$hourlyEndDate = $input['hourly_end_date'] ?? null;

    // Initialize stats arrays
    $errorTypeStats = [];
    $dailyErrorStats = [];
    $weeklyErrorStats = [];
$hourlyErrorStats = [];

    // Error type statistics
    $sqlType = "SELECT error_type, COUNT(*) as count FROM error_logs GROUP BY error_type";
    $stmtType = $pdo->prepare($sqlType);
    $stmtType->execute();
    $errorTypeStats = $stmtType->fetchAll(PDO::FETCH_ASSOC);

    // Daily error statistics (last 7 days)
    $sqlDaily = "SELECT DATE(error_time) as date, COUNT(*) as count FROM error_logs WHERE error_time >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY DATE(error_time)";
    $stmtDaily = $pdo->prepare($sqlDaily);
    $stmtDaily->execute();
    $dailyErrorStats = $stmtDaily->fetchAll(PDO::FETCH_ASSOC);

    // Weekly error statistics (adjusting for MySQL WEEKDAY: 0=Mon, 6=Sun)
    $sqlWeekly = "SELECT WEEKDAY(error_time) as weekday_num, COUNT(*) as count FROM error_logs GROUP BY weekday_num ORDER BY weekday_num";
    $stmtWeekly = $pdo->prepare($sqlWeekly);
    $stmtWeekly->execute();
    $weeklyErrorStatsRaw = $stmtWeekly->fetchAll(PDO::FETCH_ASSOC);
    // Map weekday numbers to names if needed, or adjust frontend
    $weekdays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    $weeklyErrorStats = array_fill(0, 7, ['weekday' => '', 'count' => 0]); // Initialize with 0 counts
    foreach ($weeklyErrorStatsRaw as $row) {
        $dayIndex = (int)$row['weekday_num'];
        if(isset($weekdays[$dayIndex])) {
             $weeklyErrorStats[$dayIndex] = ['weekday' => $weekdays[$dayIndex], 'count' => (int)$row['count']];
        }
    } 
    // Filter out any potential unset days if mapping fails unexpectedly (shouldn't happen with array_fill)
    $weeklyErrorStats = array_values(array_filter($weeklyErrorStats, fn($day) => $day['weekday'] !== ''));

    // Hourly error statistics
    if (!empty($hourlyStartDate) && !empty($hourlyEndDate)) {
         // Validate dates
         $startDateTime = DateTime::createFromFormat('Y-m-d', $hourlyStartDate);
         $endDateTime = DateTime::createFromFormat('Y-m-d', $hourlyEndDate);

         if (!$startDateTime || $startDateTime->format('Y-m-d') !== $hourlyStartDate) {
              handleApiError(400, 'Invalid hourly start date format. Use YYYY-MM-DD.');
         }
          if (!$endDateTime || $endDateTime->format('Y-m-d') !== $hourlyEndDate) {
              handleApiError(400, 'Invalid hourly end date format. Use YYYY-MM-DD.');
         }
         
         $sqlHourly = "SELECT DATE_FORMAT(error_time, '%Y-%m-%d %H:00:00') as hour_label, COUNT(*) as count FROM error_logs WHERE error_time BETWEEN :start_date AND :end_date GROUP BY hour_label ORDER BY hour_label";
         $stmtHourly = $pdo->prepare($sqlHourly);
         $startDateTimeStr = $startDateTime->format('Y-m-d') . ' 00:00:00';
         $endDateTimeStr = $endDateTime->format('Y-m-d') . ' 23:59:59';
         $stmtHourly->bindParam(':start_date', $startDateTimeStr);
         $stmtHourly->bindParam(':end_date', $endDateTimeStr);
         $stmtHourly->execute();
         $hourlyErrorStats = $stmtHourly->fetchAll(PDO::FETCH_ASSOC);
    }

    // Return data
    echo json_encode([
        'success' => true,
        'errorTypeStats' => $errorTypeStats,
        'dailyErrorStats' => $dailyErrorStats,
        'weeklyErrorStats' => $weeklyErrorStats,
        'hourlyErrorStats' => $hourlyErrorStats
    ]);

} catch (PDOException $e) {
    logException($e); // Log and exit via global handler
} catch (Exception $e) {
    logException($e); // Log and exit via global handler
}
?>
