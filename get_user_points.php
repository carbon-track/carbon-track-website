<?php
require 'db.php';

header('Content-Type: application/json');

function opensslDecrypt($data, $key)
{
    $data = base64_decode($data);
    $ivLength = openssl_cipher_iv_length('aes-256-cbc');
    $iv = substr($data, 0, $ivLength);
    $encrypted = substr($data, $ivLength);
    return openssl_decrypt($encrypted, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $token = $_POST['token'];
    $base64Key = "28lVmS8LHIZIQdAmT6jyHal29N8g6aRZrHEA2mv/q/4=";
    $key = base64_decode($base64Key);
    $email = opensslDecrypt($token, $key);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Token不合法']);
        exit; // 停止脚本执行
    }
    // 开始事务
    $pdo->beginTransaction();
    try {
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
        // 提交事务
        $pdo->commit();

        // 检查用户积分是否获取成功
        if ($userPoints !== false) {
            echo json_encode([
                'success' => true,
                'userPoints' => $userInfo['points'],
                'leaderboard' => $leaderboard,
                'school' => $userInfo['school'],
                'location' => $userInfo['location']
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => '未找到用户',
                'email' => $email,
                'token' => $token
            ]);
        }
    } catch (\PDOException $e) {
        // 如果有错误，回滚事务
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => '无效的请求']);
}
?>
