<?php
require_once 'global_variables.php';
require_once 'global_error_handler.php';
require 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $token = sanitizeInput($_POST['token']);
        $email = opensslDecrypt($token);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            handleApiError(401, 'Token不合法。');
        }

        $updateFields = [];
        $params = [];

        if (isset($_POST['school']) && !empty(trim($_POST['school']))) {
            if ($_POST['school'] != '请选择学校Select school') {
                $updateFields[] = "school = :school";
                $params[':school'] = sanitizeInput($_POST['school']);

                if (isset($_POST['new_sch']) && $_POST['new_sch'] === 'true') {
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM schools WHERE name = :school");
                    $stmt->execute([':school' => $_POST['school']]);
                    $count = $stmt->fetchColumn();

                    if ($count == 0) {
                        $stmt = $pdo->prepare("INSERT INTO schools (name) VALUES (:school)");
                        $stmt->execute([':school' => $_POST['school']]);
                    }
                }
            }
        }

        if (isset($_POST['state']) && !empty(trim($_POST['state']))) {
            $updateFields[] = "location = :location";
            $params[':location'] = sanitizeInput($_POST['state']);
        }

        if (empty($updateFields)) {
            echo json_encode(['success' => false, 'message' => '没有提供更新信息']);
            exit;
        }

        $sql = "UPDATE users SET " . join(', ', $updateFields) . " WHERE email = :email";
        $params[':email'] = $email;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        logException($e);
        handleApiError(500, 'Database error');
    } catch (Exception $e) {
        logException($e);
        handleApiError(500, 'Internal server error');
    }
} else {
    handleApiError(400, 'Invalid request method');
}
?>
