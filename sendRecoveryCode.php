<?php
require_once 'global_variables.php';
require_once 'global_error_handler.php';
// require 'db.php'; // Included via global_variables

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'], $_POST['cf_token'])) {
    try {
        $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
        $token = sanitizeInput($_POST['cf_token']); // Get the token

        // Verify the Cloudflare Turnstile token using the global function
        if (!verifyTurnstileToken($token)) {
            handleApiError(403, 'Anti-bot verification failed. Please try again.');
        }

        // Proceed only if Turnstile verification succeeded
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            handleApiError(422, 'Invalid email address: ' . $email);
        }
        
        global $pdo;
        if (!$pdo) {
             handleApiError(500, 'Database connection is not available.');
        }

        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email AND status = 'active'");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        // Use fetchColumn to just check existence, slightly more efficient
        if (!$stmt->fetchColumn()) { 
            handleApiError(404, 'Email address not found or inactive.');
        }

        // 生成随机验证码
        $verificationCode = rand(100000, 999999);
        $_SESSION['verification_code'] = $verificationCode;

        // Directly call the global function (assuming sendRegistrationEmail is suitable)
        // If not, a dedicated sendRecoveryEmail should be in global_variables.php
        // The global function should handle its own exceptions via logException
        sendVerificationEmail($email, $verificationCode); 
        
        // If sendRegistrationEmail did not exit (meaning success), send success response
        echo json_encode(['success' => true, 'message' => 'Verification code sent successfully.']);

        // Removed inner try-catch block

    } catch (PDOException $e) {
        // logException handles logging, response, and exiting
        logException($e);
        // handleApiError(500, 'Database error.'); // Redundant
    } catch (Exception $e) {
        // logException handles logging, response, and exiting
        logException($e);
        // handleApiError(500, 'Internal server error.'); // Redundant
    }
} else {
    handleApiError(405, '114514');
}

// Removed local sendRecoveryEmail function

?>
