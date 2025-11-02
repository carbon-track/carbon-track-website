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

        // 允许更新头像（仅限预设文件名）
        if (isset($_POST['avatar']) && !empty(trim($_POST['avatar']))) {
            $avatar = basename(sanitizeInput($_POST['avatar'])); // 防止路径穿越
            // 仅允许以 avatar 开头且为 svg/png/jpg 的文件
            if (preg_match('/^avatar\d+\.(svg|png|jpg|jpeg)$/i', $avatar)) {
                $updateFields[] = "avatar = :avatar";
                $params[':avatar'] = $avatar;
            } else {
                handleApiError(400, '非法的头像选项');
            }
        }

        if (empty($updateFields)) {
            handleApiError(400, '没有提供更新信息');
        }

    $sql = "UPDATE users SET " . join(', ', $updateFields) . " WHERE email = :email AND status = 'active'";
        $params[':email'] = $email;

        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute($params)) {
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => '用户信息已更新。']);
            } else {
                echo json_encode(['success' => true, 'message' => '未进行任何更改（可能数据未变动）。']);
            }
        } else {
            handleApiError(500, '未能更新用户信息。');
        }

    } catch (PDOException $e) {
        logException($e);
    } catch (Exception $e) {
        logException($e);
    }
} else {
    handleApiError(405, 'Invalid request method');
}
// No closing PHP tag
