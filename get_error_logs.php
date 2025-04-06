<?php
// get_error_logs.php

// require_once 'db.php'; // Included via global_variables
require_once 'global_variables.php';
require_once 'global_error_handler.php';
// require_once 'admin_emails.php'; // Not needed if using isAdmin() from global_variables

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
    $keyword = $input['keyword'] ?? '';
    $startDate = $input['start_date'] ?? '';
    $endDate = $input['end_date'] ?? '';
    $errorType = $input['error_type'] ?? '';
    
    // $key = base64_decode($base64Key); // $base64Key is from global_variables

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

    // Initialize query conditions and parameters
    $conditions = [];
    $params = [];

    // Keyword search
    if (!empty($keyword)) {
        $keywordParam = '%' . sanitizeInput($keyword) . '%'; // Sanitize keyword
        $conditions[] = "(error_message LIKE ? OR error_file LIKE ? OR error_type LIKE ?)";
        $params = array_merge($params, [$keywordParam, $keywordParam, $keywordParam]);
    }

    // Date range filter (validate date format)
    if (!empty($startDate)) {
        if (($date = DateTime::createFromFormat('Y-m-d', $startDate)) && $date->format('Y-m-d') === $startDate) {
            $conditions[] = "error_time >= ?";
            $params[] = $startDate . ' 00:00:00';
        } else {
            handleApiError(400, 'Invalid start date format. Use YYYY-MM-DD.');
        }
    }

    if (!empty($endDate)) {
         if (($date = DateTime::createFromFormat('Y-m-d', $endDate)) && $date->format('Y-m-d') === $endDate) {
            $conditions[] = "error_time <= ?";
            $params[] = $endDate . ' 23:59:59';
        } else {
            handleApiError(400, 'Invalid end date format. Use YYYY-MM-DD.');
        }
    }

    // Error type filter
    $httpErrorTypes = ['HTTP 400 Error', 'HTTP 401 Error','HTTP 403 Error', 'HTTP 404 Error','HTTP 405 Error', 'HTTP 422 Error', 'HTTP 500 Error']; // Added 405

    if (!empty($errorType)) {
         $errorType = sanitizeInput($errorType); // Sanitize type
         if ($errorType === 'HTTP Errors') {
            $placeholders = implode(',', array_fill(0, count($httpErrorTypes), '?'));
            $conditions[] = "error_type IN ($placeholders)";
            $params = array_merge($params, $httpErrorTypes);
        } else {
            $conditions[] = "error_type = ?";
            $params[] = $errorType;
        }
    }

    // Build SQL query
    $sql = "SELECT * FROM error_logs";
    if (count($conditions) > 0) {
        $sql .= " WHERE " . implode(' AND ', $conditions);
    }
    $sql .= " ORDER BY error_time DESC LIMIT 1000"; // Add a reasonable limit

    // Execute query
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Return data
    echo json_encode(['success' => true, 'data' => $logs]);

} catch (PDOException $e) {
    logException($e); // Log and exit via global handler
} catch (Exception $e) {
    logException($e); // Log and exit via global handler
}
?>