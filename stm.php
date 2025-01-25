<?php
require_once 'global_variables.php';
require_once 'global_error_handler.php';
require 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['token'])) {
    try {
        $token = sanitizeInput($_POST['token']);
        $email = opensslDecrypt($token);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            handleApiError(400, 'Token解密失败，不是有效的邮件地址。');
        }
        // 使用全局函数获取PHPMailer实例
        $mail = initializeMailer();
        $mail->addAddress($email);
        $mail->Subject = 'Submission Successful';
        $mail->Body = 'Thank you for your submission!';
        $mail->send();
        echo json_encode(['success' => true, 'message' => '邮件已发送。']);
    } catch (PDOException $e) {
        logException($e);
        handleApiError(500, 'Database error');
    } catch (Exception $e) {
        logException($e);
        handleApiError(500, 'Internal server error');
    }
} else {
    handleApiError(400, 'Invalid request');
}
?>
