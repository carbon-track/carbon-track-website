<?php
require_once 'global_variables.php';
require_once 'global_error_handler.php';
require 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    handleApiError(405, 'Invalid request method.');
}

try {
    if (!isset($_POST['token']) || !isset($_POST['avatar_id'])) {
        handleApiError(400, 'token and avatar_id are required');
    }

    $token = sanitizeInput($_POST['token']);
    $email = opensslDecrypt($token);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        handleApiError(401, 'Invalid token.');
    }

    $avatarId = intval($_POST['avatar_id']);
    if ($avatarId <= 0) {
        handleApiError(400, 'Invalid avatar_id');
    }

    // Check avatar exists and active
    $stmtA = $pdo->prepare('SELECT id FROM avatars WHERE id = :id AND active = 1');
    $stmtA->execute([':id' => $avatarId]);
    $rowA = $stmtA->fetch(PDO::FETCH_ASSOC);
    if (!$rowA) {
        handleApiError(404, 'Avatar not found or inactive');
    }

    // Update user's avatar_id
    $stmtU = $pdo->prepare("UPDATE users SET avatar_id = :aid WHERE email = :email AND status = 'active'");
    $stmtU->execute([':aid' => $avatarId, ':email' => $email]);
    if ($stmtU->rowCount() === 0) {
        handleApiError(404, 'User not found or inactive.');
    }

    echo json_encode(['success' => true, 'avatar_id' => $avatarId]);
} catch (Exception $e) {
    logException($e);
}

// No closing tag
