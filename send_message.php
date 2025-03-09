<?php
require_once 'global_variables.php';
require_once 'global_error_handler.php';
require 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // 验证必要参数
        if (!isset($_POST['token']) || !isset($_POST['receiverId']) || !isset($_POST['message'])) {
            handleApiError(400, 'Missing required parameters');
        }

        // 处理参数
        $token = sanitizeInput($_POST['token']);
        $email = opensslDecrypt($token);
        $receiverId = sanitizeInput($_POST['receiverId']);
        $message = sanitizeInput($_POST['message']);

        // 验证邮箱格式
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            handleApiError(401, 'Invalid token');
        }

        // 连接数据库
        $pdo = new PDO($dsn, $user, $pass, $options);
        
        // 获取发送者ID
        $stmtSender = $pdo->prepare("SELECT id FROM users WHERE email = :email");
        $stmtSender->bindParam(':email', $email, PDO::PARAM_STR);
        $stmtSender->execute();
        $senderData = $stmtSender->fetch(PDO::FETCH_ASSOC);
        
        if (!$senderData) {
            handleApiError(404, 'Sender not found');
        }
        
        $senderId = $senderData['id'];
        
        // 生成当前时间戳
        $currentTime = date('Y-m-d H:i:s');
        
        // 插入消息
        $stmtInsert = $pdo->prepare("
            INSERT INTO messages (sender_id, receiver_id, content, is_read, created_at) 
            VALUES (:sender_id, :receiver_id, :content, 0, :created_at)
        ");
        
        $stmtInsert->bindParam(':sender_id', $senderId, PDO::PARAM_INT);
        $stmtInsert->bindParam(':receiver_id', $receiverId, PDO::PARAM_INT);
        $stmtInsert->bindParam(':content', $message, PDO::PARAM_STR);
        $stmtInsert->bindParam(':created_at', $currentTime, PDO::PARAM_STR);
        $stmtInsert->execute();
        
        // 返回成功响应，包括时间戳
        echo json_encode([
            'success' => true, 
            'message' => 'Message sent successfully',
            'timestamp' => $currentTime,
            'message_data' => [
                'sender_id' => $senderId,
                'receiver_id' => $receiverId,
                'content' => $message,
                'created_at' => $currentTime,
                'is_read' => 0
            ]
        ]);
        
    } catch (PDOException $e) {
        logException($e);
        handleApiError(500, 'Database error: ' . $e->getMessage());
    } catch (Exception $e) {
        logException($e);
        handleApiError(500, 'Server error: ' . $e->getMessage());
    }
} else {
    handleApiError(405, 'Method not allowed');
}
?> 