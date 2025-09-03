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
    if ($id <= 0) handleApiError(400, 'Invalid id');

    $fields = [];
    $params = [':id' => $id];
    if (isset($_POST['active'])) {
        $active = intval($_POST['active']) ? 1 : 0;
        $fields[] = 'active = :active';
        $params[':active'] = $active;
    }
    if (isset($_POST['filename'])) {
        $filename = basename(sanitizeInput($_POST['filename']));
        // allow only certain extensions
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (!in_array($ext, ['svg','png','jpg','jpeg'])) {
            handleApiError(400, 'Unsupported filename extension');
        }
        $fields[] = 'filename = :filename';
        $params[':filename'] = $filename;
    }
    if (empty($fields)) {
        handleApiError(400, 'No changes provided');
    }
    $sql = 'UPDATE avatars SET ' . implode(', ', $fields) . ' WHERE id = :id';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    logException($e);
}

// No closing tag
