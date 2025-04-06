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
            handleApiError(400, '没有提供更新信息');
        }

        $sql = "UPDATE users SET " . join(', ', $updateFields) . " WHERE email = :email";
        $params[':email'] = $email;

        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute($params)) {
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => '用户信息已更新。']);
            } else {
                echo json_encode(['success' => true, 'message' => '未进行任何更改（可能数据未变动）。']);
            }
        } else {
            throw new Exception('未能更新用户信息。');
        }

    } catch (PDOException $e) {
        logException($e);
    } catch (Exception $e) {
        logException($e);
    }
} else {
    handleApiError(405, 'Invalid request method');
}
?>
