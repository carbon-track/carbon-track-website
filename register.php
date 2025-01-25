<?php
require_once 'global_variables.php';
require_once 'global_error_handler.php';
require 'db.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'], $_POST['regusername'], $_POST['regpassword'], $_POST['verificationCode'])) {
    try {
        $email = sanitizeInput($_POST['email']);
        $username = sanitizeInput($_POST['regusername']);
        $password = $_POST['regpassword']; // Password will be hashed, no need to sanitize
        $verificationCode = sanitizeInput($_POST['verificationCode']);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            handleApiError(400, 'Invalid Email address.');
        }

        if ($verificationCode != $_SESSION['verification_code'] || empty($verificationCode)) {
            handleApiError(400, 'Incorrect Verification Code.');
        }

        $pdo = new PDO($dsn, $user, $pass, $options);

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
            // 使用全局函数获取PHPMailer实例
            $mail = initializeMailer();
            $mail->addAddress($email);
            $mail->Subject = 'Registration Successful';
            $mail->Body = 'Thank you for registering!';
            $mail->send();
            echo json_encode(['success' => true]);
        } else {
            handleApiError(500, 'Failed to register user.');
        }
    } catch (PDOException $e) {
        logException($e);
        handleApiError(500, 'Database error.');
    } catch (Exception $e) {
        logException($e);
        handleApiError(500, 'Internal server error.');
    }
} else {
    handleApiError(405, '114514');
}
?>
