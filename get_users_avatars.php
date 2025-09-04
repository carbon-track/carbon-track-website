<?php
require_once 'global_variables.php';
require_once 'global_error_handler.php';
require_once 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    handleApiError(405, 'Invalid request method.');
}

try {
    if (!isset($_POST['token']) || !isset($_POST['userIds'])) {
        handleApiError(400, 'token and userIds are required');
    }

    $token = sanitizeInput($_POST['token']);
    $email = opensslDecrypt($token);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        handleApiError(401, 'Invalid token.');
    }

    $userIds = json_decode($_POST['userIds'], true);
    if (!is_array($userIds) || empty($userIds)) {
        handleApiError(400, 'Invalid userIds format');
    }

    // 构建占位符
    $placeholders = str_repeat('?,', count($userIds) - 1) . '?';
    
    // 查询用户头像信息
    $sql = "SELECT u.username, u.avatar_id, a.filename 
            FROM users u 
            LEFT JOIN avatars a ON u.avatar_id = a.id AND a.active = 1
            WHERE u.username IN ($placeholders)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($userIds);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $avatars = [];
    foreach ($users as $user) {
        $avatarUrl = 'img/avatars/avatar1.svg'; // 默认头像
        if ($user['filename']) {
            $avatarUrl = 'img/avatars/' . $user['filename'];
        }
        
        $avatars[] = [
            'username' => $user['username'],
            'avatar_id' => (int)$user['avatar_id'],
            'avatar_url' => $avatarUrl
        ];
    }

    echo json_encode(['success' => true, 'avatars' => $avatars]);
} catch (Exception $e) {
    logException($e);
}

