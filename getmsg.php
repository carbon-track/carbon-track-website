<?php
require_once 'global_variables.php';
require_once 'global_error_handler.php';
require 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if (!isset($data['token']) || !isset($data['receiver_id'])) {
            handleApiError(400, 'Token and receiver_id are required');
        }

        $token = sanitizeInput($data['token']);
        $email = opensslDecrypt($token);
        $receiver_id = getUid($pdo, $email);
        $sender_id = $receiver_id;
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            handleApiError(401, 'Token不合法。');
        }
        if (!is_numeric($receiver_id)) {
            handleApiError(400, '接收者ID不合法。');
        }
        $sender = $receiver_id;
        if (!$sender) {
            handleApiError(404, '发送者未找到。');
        }     
        // 开始事务
        $pdo->beginTransaction();

        // 获取未读消息，确保包含created_at字段
        $stmtSelect = $pdo->prepare("
            SELECT id, sender_id, receiver_id, content, is_read, created_at 
            FROM messages 
            WHERE (receiver_id = :receiver_id OR sender_id = :sender_id)
            ORDER BY created_at ASC
        ");
        $stmtSelect->execute([
            'receiver_id' => $receiver_id,
            'sender_id' => $sender_id
        ]);
        $messages = $stmtSelect->fetchAll(PDO::FETCH_ASSOC);

        // 更新消息为已读
        $stmtUpdate = $pdo->prepare("
            UPDATE messages SET is_read = 1 WHERE receiver_id = :receiver_id
        ");
        $stmtUpdate->execute(['receiver_id' => $receiver_id]);

        // 提交事务
        $pdo->commit();

        // 确保每条消息都有时间戳
        foreach ($messages as &$message) {
            if (!isset($message['created_at']) || empty($message['created_at'])) {
                $message['created_at'] = date('Y-m-d H:i:s');
            }
        }

        echo json_encode(['success' => true, 'messages' => $messages]);
    } catch (PDOException $e) {
        // 回滚事务如果有错误
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        logException($e);
        handleApiError(500, '数据库错误');
    } catch (Exception $e) {
        // 回滚事务如果有错误
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        logException($e);
        handleApiError(500, '内部服务器错误');
    }
} else {
    handleApiError(405, '不支持的请求方法。');
}
?>
