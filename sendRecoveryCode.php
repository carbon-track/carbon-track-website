<?php
require_once 'global_variables.php';
require_once 'global_error_handler.php';
require 'db.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    try {
        $email = sanitizeInput($_POST['email']);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            handleApiError(422, 'Invalid email address.');
        }

        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $result = $stmt->fetchAll();

        if (count($result) == 0) {
            handleApiError(404, 'Email address not found.');
        }

        // 生成随机验证码
        $verificationCode = rand(100000, 999999);
        $_SESSION['verification_code'] = $verificationCode;

        try {
            sendRecoveryEmail($email, $verificationCode);
            echo json_encode(['success' => true, 'message' => 'Verification code sent successfully.']);
        } catch (Exception $e) {
            logException($e);
            handleApiError(500, 'Failed to send recovery email.');
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

function sendRecoveryEmail($email, $verificationCode) {
    try {
        // 邮件服务器设置
        $mail = initializeMailer();

        // 收件人设置
        $mail->addAddress($email); // 添加收件人

        // 设置邮件格式和内容
        $mail->isHTML(true);
        $mail->Subject = '[CarbonTrack]密码恢复验证码 Password Recovery Verification Code';
        $mail->Body    = "你的密码恢复验证码是 Your password recovery verification code is：{$verificationCode}";

        // 发送邮件
        $mail->send();
    } catch (Exception $e) {
        logException($e);
        throw $e; // Re-throw the exception to be caught by the global error handler
    }
}
?>
