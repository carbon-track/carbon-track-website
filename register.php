<?php
require_once 'global_variables.php'; // Includes global_error_handler.php and db.php

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'], $_POST['regusername'], $_POST['regpassword'], $_POST['verificationCode'], $_POST['cf-turnstile-response'])) {
    try {
        // Verify Turnstile token first
        $turnstileToken = $_POST['cf-turnstile-response'];
        if (!verifyTurnstileToken($turnstileToken)) {
            handleApiError(403, 'Anti-bot verification failed. Please try again.');
        }

        $email = trim($_POST['email'] ?? '');
        $username = sanitizeInput($_POST['regusername']);
        $password = $_POST['regpassword']; // Password will be hashed, no need to sanitize
        $verificationCode = sanitizeInput($_POST['verificationCode']);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            handleApiError(400, 'Invalid Email address: ' . $email);
        }

        // Check verification code: ensure submitted code is not empty, session code exists, and they match
        if (empty($verificationCode) || !isset($_SESSION['verification_code']) || $verificationCode != $_SESSION['verification_code']) {
            handleApiError(400, 'Incorrect Verification Code.');
        }

        // $pdo is already created in db.php which is included via global_variables.php
        global $pdo; 
        if (!isset($pdo)) {
             $pdo = new PDO($dsn, $user, $pass, $options);
        }

        // Check if username exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            handleApiError(400, 'Username has been registered.');
        }

        // Check if email exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            handleApiError(400, 'Email has been registered.');
        }

        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Insert new user
        $stmt = $pdo->prepare("INSERT INTO users (username, password, email) VALUES (:username, :password, :email)");
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password', $hashedPassword);
        $stmt->bindParam(':email', $email);
        if ($stmt->execute()) {
            unset($_SESSION['verification_code']); // Clear the verification code
            
            // Send registration success email using global function
            sendRegistrationEmail($email);
            
            echo json_encode(['success' => true]);
        } else {
            handleApiError(500, 'Failed to register user.');
        }
    } catch (PDOException $e) {
        logException($e);
    } catch (Exception $e) {
        logException($e);
    }
} else {
    handleApiError(405, '114514');
}
?>
