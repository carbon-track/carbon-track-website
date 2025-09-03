<?php
require_once 'global_variables.php';
require_once 'global_error_handler.php';
require 'db.php';

header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT id, filename, mime FROM avatars WHERE active = 1 ORDER BY id ASC");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $avatars = [];
    foreach ($rows as $r) {
        $avatars[] = [
            'id' => (int)$r['id'],
            'url' => 'img/avatars/' . $r['filename'],
            'filename' => $r['filename'],
            'mime' => $r['mime']
        ];
    }
    echo json_encode(['success' => true, 'avatars' => $avatars]);
} catch (Exception $e) {
    logException($e);
}

// No closing tag
