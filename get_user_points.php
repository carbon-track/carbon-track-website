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

        // 获取用户积分
        $stmtUser = $pdo->prepare("SELECT points, school, location FROM users WHERE email = :email");
        $stmtUser->bindParam(':email', $email, PDO::PARAM_STR);
        $stmtUser->execute();
        $userInfo = $stmtUser->fetch(PDO::FETCH_ASSOC); // 使用fetch获取一行数据

        // 获取全站积分排行榜
        $stmtLeaderboard = $pdo->prepare("SELECT username, points FROM users WHERE location = :location ORDER BY points DESC LIMIT 10");
        $stmtLeaderboard->bindParam(':location', $userInfo['location'], PDO::PARAM_STR);
        $stmtLeaderboard->execute();
        $leaderboard = $stmtLeaderboard->fetchAll();

        if ($userInfo) {
            echo json_encode([
                'success' => true,
                'userPoints' => $userInfo['points'],
                'leaderboard' => $leaderboard,
                'school' => $userInfo['school'],
                'location' => $userInfo['location']
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

?>
