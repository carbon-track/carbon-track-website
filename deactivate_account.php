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
        handleApiError(400, 'Token is required.');
    }

    $token = sanitizeInput($_POST['token']);
    $email = opensslDecrypt($token);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        handleApiError(401, 'Invalid token.');
    }

    global $pdo;
    if (!isset($pdo)) {
        require_once 'db.php';
    }
    if (!isset($pdo)) {
        throw new Exception('Database connection is not available.');
    }

    // 统一规范化邮箱（去空格、转小写）并按规范化值匹配
    $emailNorm = strtolower(trim($email));

    // 先取出对应用户的 id（避免因库内邮箱意外空格/大小写导致未匹配）
    $findStmt = $pdo->prepare("SELECT id FROM users WHERE LOWER(TRIM(email)) = :emailNorm LIMIT 1");
    $findStmt->execute([':emailNorm' => $emailNorm]);
    $row = $findStmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        handleApiError(404, 'Account not found or already deleted.');
    }

    $userId = (int)$row['id'];

    $stmt = $pdo->prepare("UPDATE users SET status = 'deleted' WHERE id = :id AND status = 'active'");
    $stmt->execute([':id' => $userId]);

    if ($stmt->rowCount() === 0) {
        handleApiError(404, 'Account not found or already deleted.');
    }

    echo json_encode(['success' => true, 'message' => 'Account deleted successfully.']);
} catch (PDOException $e) {
    logException($e);
    handleApiError(500, 'Internal Server Error.');
} catch (Exception $e) {
    logException($e);
    handleApiError(500, 'Internal Server Error.');
}
// No closing tag
