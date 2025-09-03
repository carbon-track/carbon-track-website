<?php
require_once 'global_variables.php';
require_once 'global_error_handler.php';
require_once 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    handleApiError(405, 'Invalid request method.');
}

try {
    if (!isset($_POST['token'])) {
        handleApiError(400, 'Token is required');
    }
    $token = sanitizeInput($_POST['token']);
    $email = opensslDecrypt($token);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        handleApiError(401, 'Invalid token.');
    }
    if (!isAdmin($email)) {
        handleApiError(403, 'Admin required');
    }

    $stmt = $pdo->query("SELECT id, filename, mime, active FROM avatars ORDER BY id ASC");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $avatars = [];
    foreach ($rows as $r) {
        $avatars[] = [
            'id' => (int)$r['id'],
            'url' => 'img/avatars/' . $r['filename'],
            'filename' => $r['filename'],
            'mime' => $r['mime'],
            'active' => (int)$r['active']
        ];
    }
    echo json_encode(['success' => true, 'avatars' => $avatars]);
} catch (Exception $e) {
    logException($e);
}

// No closing tag
