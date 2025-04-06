<?php
require_once 'global_variables.php';
require_once 'global_error_handler.php';
// require 'db.php'; // Not needed if only sending email via global func

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!isset($_POST['token'])) {
            handleApiError(400, 'Token is required.');
        }
        $token = sanitizeInput($_POST['token']);
        $email = opensslDecrypt($token);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            handleApiError(400, 'Token解密失败，不是有效的邮件地址。');
        }
        
        // Call the global function to send the submission email
        // This function already uses logException internally if PHPMailer fails
        sendSubmissionEmail($email); 

        // If sendSubmissionEmail didn't exit, it succeeded
        echo json_encode(['success' => true, 'message' => '邮件已发送。']);
        
    // Remove specific PDOException catch if no direct DB interaction here
    // } catch (PDOException $e) { 
    //    logException($e);
    } catch (Exception $e) {
        // Catch any other potential exceptions (e.g., from opensslDecrypt if key is bad)
        logException($e);
        // handleApiError(500, 'Internal server error'); // Redundant
    }
} else {
    handleApiError(405, 'Invalid request method.');
}
?>
