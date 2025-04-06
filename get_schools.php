<?php
require_once 'global_variables.php';
require_once 'global_error_handler.php';
require 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['token'])) {
    try {
        $token = sanitizeInput($_POST['token']);
        $email = opensslDecrypt($token);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            handleApiError(403, 'Token不合法');
        }

        $pdo = new PDO($dsn, $user, $pass, $options);
        $stmt = $pdo->prepare("SELECT id, name FROM schools");
        $stmt->execute();
        $schools = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($schools);
    } catch (PDOException $e) {
        logException($e);
    } catch (Exception $e) {
        logException($e);
    }
} else {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        handleApiError(405, 'Invalid request method.');
    } else {
        handleApiError(400, 'Token is required.');
    }
}
?>
