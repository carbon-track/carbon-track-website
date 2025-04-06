<?php
require_once 'global_variables.php';
require_once 'global_error_handler.php';
require 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $token = sanitizeInput($_POST['token'] ?? '');
        $email = opensslDecrypt($token);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            handleApiError(403, 'Token不合法');
            exit;
        }

        $type = sanitizeInput($_POST['type'] ?? 'all');
        $location = sanitizeInput($_POST['location'] ?? '');
        $school = sanitizeInput($_POST['school'] ?? '');

        $sql = "SELECT username, points FROM users";
        $params = [];

        if ($type === 'local' && !empty($location)) {
            $sql .= " WHERE location = :location";
            $params[':location'] = $location;
        } elseif ($type === 'school' && !empty($school)) {
            $sql .= " WHERE school = :school";
            $params[':school'] = $school;
        }

        $sql .= " ORDER BY points DESC LIMIT 10";

        $pdo = new PDO($dsn, $user, $pass, $options);
        $stmt = $pdo->prepare($sql);

        foreach ($params as $key => &$val) {
            $stmt->bindParam($key, $val);
        }
        $stmt->execute();

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'ranking' => $results]);
    } catch (PDOException $e) {
        logException($e);
    } catch (Exception $e) {
        logException($e);
    }
} else {
    handleApiError(405, '不支持的请求方法。');
}
?>
