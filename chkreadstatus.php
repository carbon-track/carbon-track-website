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
        
        // 使用 getUid 函数通过邮箱获取用户ID
        $userId = getUid($pdo, $email);
        
        if (!$userId) {
            handleApiError(404, '用户未找到。');
        }
        
        // 获取聊天伙伴ID
        $partnerId = isset($data['partner_id']) ? sanitizeInput($data['partner_id']) : null;
        if (!$partnerId) {
            handleApiError(400, '聊天伙伴ID是必需的。');
        }
        
        // 记录请求信息
        file_put_contents('chkreadstatus_log.txt', date('Y-m-d H:i:s') . ' - 用户ID: ' . $userId . ', 伙伴ID: ' . $partnerId . "\n", FILE_APPEND);
        
        // 获取当前用户发送给伙伴且已读的消息ID列表
        $stmt = $pdo->prepare("
            SELECT message_id 
            FROM messages 
            WHERE sender_id = :sender_id 
            AND receiver_id = :receiver_id 
            AND is_read = 1
        ");
        $stmt->execute([
            ':sender_id' => $userId,
            ':receiver_id' => $partnerId
        ]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 提取消息ID到数组
        $messageIds = array_map(function($message) {
            return $message['message_id'];
        }, $messages);
        
        // 记录响应数据
        $response = [
            'success' => true, 
            'read_messages' => $messageIds,
            'count' => count($messageIds)
        ];
        file_put_contents('chkreadstatus_log.txt', date('Y-m-d H:i:s') . ' - 响应: ' . json_encode($response) . "\n", FILE_APPEND);
        
        echo json_encode($response);
    } catch (PDOException $e) {
        logException($e);
        file_put_contents('chkreadstatus_log.txt', date('Y-m-d H:i:s') . ' - 数据库错误: ' . $e->getMessage() . "\n", FILE_APPEND);
        handleApiError(500, '数据库错误: ' . $e->getMessage());
    } catch (Exception $e) {
        logException($e);
        file_put_contents('chkreadstatus_log.txt', date('Y-m-d H:i:s') . ' - 服务器错误: ' . $e->getMessage() . "\n", FILE_APPEND);
        handleApiError(500, '内部服务器错误: ' . $e->getMessage());
    }
} else {
    file_put_contents('chkreadstatus_log.txt', date('Y-m-d H:i:s') . ' - 不支持的请求方法: ' . $_SERVER['REQUEST_METHOD'] . "\n", FILE_APPEND);
    handleApiError(405, '不支持的请求方法。');
}
?> 