<?php
require_once 'global_variables.php';
require_once 'global_error_handler.php';
require 'db.php';

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

    if (!isset($_FILES['file'])) {
        handleApiError(400, 'file is required');
    }

    $file = $_FILES['file'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        handleApiError(400, 'Upload error: ' . $file['error']);
    }

    $allowed = ['image/png' => 'png', 'image/svg+xml' => 'svg', 'image/jpeg' => 'jpg'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!isset($allowed[$mime])) {
        handleApiError(400, 'Unsupported file type');
    }
    $ext = $allowed[$mime];

    $targetDir = __DIR__ . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'avatars' . DIRECTORY_SEPARATOR;
    if (!is_dir($targetDir)) {
        if (!mkdir($targetDir, 0755, true)) {
            handleApiError(500, 'Failed to create target directory');
        }
    }

    // Generate unique filename
    $base = 'avatar_' . time() . '_' . bin2hex(random_bytes(4));
    $filename = $base . '.' . $ext;
    $dest = $targetDir . $filename;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        handleApiError(500, 'Failed to save uploaded file');
    }

    // Insert into DB
    $stmt = $pdo->prepare('INSERT INTO avatars (filename, mime, active) VALUES (:filename, :mime, 1)');
    $stmt->execute([':filename' => $filename, ':mime' => $mime]);
    $id = intval($pdo->lastInsertId());

    echo json_encode(['success' => true, 'id' => $id, 'url' => 'img/avatars/' . $filename]);
} catch (Exception $e) {
    logException($e);
}

// No closing tag
