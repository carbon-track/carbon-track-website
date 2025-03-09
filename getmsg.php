<?php
require_once 'global_variables.php';
require_once 'global_error_handler.php';
require 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if (!isset($data['token'])) {
            handleApiError(400, 'Token is required');
        }

        $token = sanitizeInput($data['token']);
        $email = opensslDecrypt($token);
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            handleApiError(401, 'Token不合法。');
        }
        
        // 使用token中的email获取用户ID
        $stmtUser = $pdo->prepare("SELECT id FROM users WHERE email = :email");
        $stmtUser->execute(['email' => $email]);
        $userData = $stmtUser->fetch(PDO::FETCH_ASSOC);
        
        if (!$userData) {
            handleApiError(404, '用户未找到。');
        }
        
        $userId = $userData['id'];
        
        // 开始事务
        $pdo->beginTransaction();

        // 获取用户相关的所有消息（作为发送方或接收方）
        $stmtSelect = $pdo->prepare("
            SELECT message_id, sender_id, receiver_id, content, is_read, send_time 
            FROM messages 
            WHERE sender_id = :user_id OR receiver_id = :user_id
            ORDER BY send_time ASC
        ");
        $stmtSelect->execute(['user_id' => $userId]);
        $messages = $stmtSelect->fetchAll(PDO::FETCH_ASSOC);

        // 更新当前用户接收的未读消息为已读
        $stmtUpdate = $pdo->prepare("
            UPDATE messages SET is_read = 1 WHERE receiver_id = :user_id AND is_read = 0
        ");
        $stmtUpdate->execute(['user_id' => $userId]);

        // 提交事务
        $pdo->commit();

        // 确保每条消息都有时间戳
        foreach ($messages as &$message) {
            if (!isset($message['send_time']) || empty($message['send_time'])) {
                $message['send_time'] = date('Y-m-d H:i:s');
            }
            // 为了兼容前端代码，添加created_at字段作为send_time的别名
            $message['created_at'] = $message['send_time'];
        }
        
        // 添加调试信息
        $debugInfo = [
            'user_id' => $userId,
            'email' => $email,
            'message_count' => count($messages)
        ];

        echo json_encode(['success' => true, 'messages' => $messages, 'debug' => $debugInfo]);
    } catch (PDOException $e) {
        // 回滚事务如果有错误
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        logException($e);
        handleApiError(500, '数据库错误: ' . $e->getMessage());
    } catch (Exception $e) {
        // 回滚事务如果有错误
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        logException($e);
        handleApiError(500, '内部服务器错误: ' . $e->getMessage());
    }
} else {
    handleApiError(405, '不支持的请求方法。');
}
?>
