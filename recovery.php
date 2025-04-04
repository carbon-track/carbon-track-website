<?php
// 引入数据库连接和相关函数
require 'db.php';
// 开启新会话或继续会话
session_start();

if (!isset($_POST['cf_token'])) {
    http_response_code(400);
    echo json_encode(["message" => "Please Finish the Anti-bot Verification."]);
    exit();
}

// Verify Turnstile token using the global function
$cftoken = $_POST['cf_token'];
if (!verifyTurnstileToken($cftoken)) {
    handleApiError(403, 'Anti-bot verification failed. Please try again.');
}

// 清理和验证输入
$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
$verificationCode = filter_input(INPUT_POST, 'verificationCode', FILTER_SANITIZE_NUMBER_INT);
$newPassword = $_POST['newPassword']; // 新密码将被哈希，不需要过滤

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => '无效的邮箱地址。']);
    exit;
}

if ($verificationCode != $_SESSION['verification_code']) {
    echo json_encode(['success' => false, 'error' => '验证码错误。']);
    exit;
}

// 检查邮箱是否存在
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
$stmt->bindParam(':email', $email);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    // 邮箱不存在
    echo json_encode(['success' => false, 'error' => '邮箱不存在。']);
    exit;
}

// 密码哈希
$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

// 更新用户密码
$stmt = $pdo->prepare("UPDATE users SET password = :password WHERE email = :email");
$stmt->bindParam(':password', $hashedPassword);
$stmt->bindParam(':email', $email);

try {
    $stmt->execute();
    unset($_SESSION['verification_code']); // 清除验证码
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    // 处理错误，例如数据库更新错误
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
