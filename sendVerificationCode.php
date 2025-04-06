<?php
require_once 'global_variables.php';
require_once 'global_error_handler.php';

header('Content-Type: application/json; charset=UTF-8');

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
session_start();
}

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    handleApiError(405, 'Invalid request method.');
}

// Wrap in try block
try {
    // Check Turnstile token
    if (!isset($_POST['cf_token'])) { // Assuming token is sent as 'cf_token'
        handleApiError(400, 'Missing anti-bot verification token.');
    }
    $cftoken = $_POST['cf_token'];
    if (!verifyTurnstileToken($cftoken)) {
        handleApiError(403, 'Anti-bot verification failed.');
    }

    // Sanitize and validate email
    $email = sanitizeInput($_POST['email'] ?? '');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        handleApiError(400, '无效的邮箱地址。');
    }
    
    // Optionally check if email is already registered? Depending on use case.
    /*
    global $pdo;
    $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE email = :email");
    $stmtCheck->bindParam(':email', $email);
    $stmtCheck->execute();
    if ($stmtCheck->fetchColumn()) { 
        handleApiError(400, 'Email address is already registered.');
}
    */

    // Generate and store verification code
    $verificationCode = rand(100000, 999999); 
$_SESSION['verification_code'] = $verificationCode;

    // Send email using global function (handles its own exceptions)
    sendRegistrationEmail($email, $verificationCode); 
    
    // If sendRegistrationEmail didn't exit, it succeeded
    echo json_encode(['success' => true, 'message' => 'Verification code sent.']);

} catch (Exception $e) {
    // Catch any other unexpected exceptions (PDOExceptions should be caught by global handler if db.php fails)
    logException($e); // Log and provide generic 500 response
}

?>
