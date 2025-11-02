<?php
require_once 'global_variables.php';
require_once 'global_error_handler.php';
require 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    handleApiError(405, 'Invalid request method.');
}

try {
    if (!isset($_POST['token']) || !isset($_POST['id'])) {
        handleApiError(400, 'token and id are required');
    }
    $token = sanitizeInput($_POST['token']);
    $email = opensslDecrypt($token);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        handleApiError(401, 'Invalid token.');
    }
    if (!isAdmin($email)) {
        handleApiError(403, 'Admin required');
    }

    $id = intval($_POST['id']);
    if ($id <= 0) {
        handleApiError(400, 'Invalid id');
    }

    // Deactivate avatar
    $pdo->prepare('UPDATE avatars SET active = 0 WHERE id = :id')->execute([':id' => $id]);

    // Optionally reassign users using this avatar to default
    $default = $pdo->prepare("SELECT id FROM avatars WHERE filename = 'avatar1.svg' LIMIT 1");
    $default->execute();
    $row = $default->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $defId = intval($row['id']);
        $pdo->prepare("UPDATE users SET avatar_id = :def WHERE avatar_id = :old AND status = 'active'")->execute([':def' => $defId, ':old' => $id]);
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    logException($e);
}

// No closing tag
