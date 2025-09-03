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
            handleApiError(401, 'Invalid token.');
        }

    // 获取用户积分与头像
    $stmtUser = $pdo->prepare("SELECT points, school, location, avatar, avatar_id FROM users WHERE email = :email");
        $stmtUser->bindParam(':email', $email, PDO::PARAM_STR);
        $stmtUser->execute();
        $userInfo = $stmtUser->fetch(PDO::FETCH_ASSOC); // 使用fetch获取一行数据

        // 获取全站积分排行榜
        $stmtLeaderboard = $pdo->prepare("SELECT username, points FROM users WHERE location = :location ORDER BY points DESC LIMIT 10");
        $stmtLeaderboard->bindParam(':location', $userInfo['location'], PDO::PARAM_STR);
        $stmtLeaderboard->execute();
        $leaderboard = $stmtLeaderboard->fetchAll();

        if ($userInfo) {
            // 解析头像URL（优先使用avatar_id关联的avatars表）
            $avatarUrl = null;
            $avatarId = isset($userInfo['avatar_id']) ? intval($userInfo['avatar_id']) : null;
            if ($avatarId) {
                $stmtA = $pdo->prepare("SELECT filename FROM avatars WHERE id = :id AND active = 1");
                $stmtA->execute([':id' => $avatarId]);
                $rowA = $stmtA->fetch(PDO::FETCH_ASSOC);
                if ($rowA && !empty($rowA['filename'])) {
                    $avatarUrl = 'img/avatars/' . $rowA['filename'];
                }
            }
            // 若未找到，退回到legacy filename
            if (!$avatarUrl && !empty($userInfo['avatar'])) {
                $avatarUrl = 'img/avatars/' . $userInfo['avatar'];
            }
            // 最终兜底
            if (!$avatarUrl) {
                $avatarUrl = 'img/avatars/avatar1.svg';
            }
            echo json_encode([
                'success' => true,
                'userPoints' => $userInfo['points'],
                'leaderboard' => $leaderboard,
                'school' => $userInfo['school'],
                'location' => $userInfo['location'],
                'avatar' => isset($userInfo['avatar']) ? $userInfo['avatar'] : null,
                'avatar_id' => $avatarId,
                'avatar_url' => $avatarUrl
            ]);
        } else {
            handleApiError(404, 'User not found.');
        }
    } catch (PDOException $e) {
        logException($e);
    } catch (Exception $e) {
        logException($e);
    }
} else {
    handleApiError(405, 'Invalid request method.');
}

// No closing PHP tag to avoid accidental output
