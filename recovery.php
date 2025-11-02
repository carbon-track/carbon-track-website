<?php
// Include global handlers and variables
require_once 'global_variables.php'; // Includes db.php indirectly
require_once 'global_error_handler.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=UTF-8');

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    handleApiError(405, 'Invalid request method.');
}

// Wrap entire logic in try block for generic exceptions
try {
    // Verify Turnstile token first
    if (!isset($_POST['cf_token'])) {
        handleApiError(400, 'Please Finish the Anti-bot Verification.');
    }
    $cftoken = $_POST['cf_token'];
    if (!verifyTurnstileToken($cftoken)) {
        handleApiError(403, 'Anti-bot verification failed. Please try again.');
    }

    // Sanitize and validate input
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    // Use sanitizeInput for consistency, though filter_input might be okay here
    $verificationCode = sanitizeInput($_POST['verificationCode'] ?? ''); 
    $newPassword = $_POST['newPassword'] ?? ''; // Get password, check if empty later

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        handleApiError(400, '无效的邮箱地址。');
    }
    
    if (empty($newPassword)) {
        handleApiError(400, '新密码不能为空。');
    }

    // Check verification code (case-insensitive comparison might be better depending on how it's generated/sent)
    if (empty($verificationCode) || !isset($_SESSION['verification_code']) || strcasecmp($verificationCode, $_SESSION['verification_code']) !== 0) {
        handleApiError(400, '验证码错误。');
    }

    // PDO is included via global_variables -> db.php
    // $pdo = new PDO($dsn, $user, $pass, $options); // Already available as global $pdo
    global $pdo;
    if (!$pdo) {
         // If $pdo is not available, it implies db.php failed, which should have been caught by error handler
         // But as a fallback:
         handleApiError(500, 'Database connection is not available.');
    }

    // Check if email exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email AND status = 'active'");
    $stmt->bindParam(':email', $email);
    $stmt->execute();

    if ($stmt->rowCount() == 0) {
        handleApiError(404, '邮箱不存在。'); // Use 404 Not Found
    }

    // Hash the new password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    // Update user password
    $stmt = $pdo->prepare("UPDATE users SET password = :password WHERE email = :email AND status = 'active'");
    $stmt->bindParam(':password', $hashedPassword);
    $stmt->bindParam(':email', $email);
    
    if ($stmt->execute()) {
        unset($_SESSION['verification_code']); // Clear the verification code on success
        echo json_encode(['success' => true, 'message' => '密码已成功重置。']);
    } else {
        // Throw a generic exception if execute fails for non-PDO reasons (should be rare)
        throw new Exception('未能更新密码。');
    }

} catch (PDOException $e) {
    // Let the global handler manage PDO exceptions
    logException($e);
} catch (Exception $e) {
    // Let the global handler manage other exceptions
    logException($e);
}

?>
