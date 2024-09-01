<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

    require 'src/Exception.php';
    require 'src/PHPMailer.php';
    require 'src/SMTP.php';
require 'db.php'; // 引入数据库连接文件
session_start();

// 获取并清洗输入的邮箱地址
$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

// 验证邮箱地址是否有效
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => '无效的邮箱地址。']);
    exit;
}

// 检查邮箱地址是否存在于数据库中
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->bindParam(1, $email); // Corrected method for PDO
$stmt->execute();
$result = $stmt->fetchAll(); // Fetch all results since PDO doesn't have get_result

if (count($result) == 0) {
    echo json_encode(['success' => false, 'error' => '邮箱地址不存在。']);
    exit;
}

// 生成随机验证码
$verificationCode = rand(100000, 999999);

// 在会话或数据库中存储验证码
$_SESSION['verification_code'] = $verificationCode;

// 实例化PHPMailer
$mail = new PHPMailer(true);

try {
    // 设置SMTP服务器
$mail->CharSet = "UTF-8";
        $mail->SMTPDebug = 0;
        $mail->isSMTP();
        $mail->Host = 'smtp.qq.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'jeffery.lyu@qq.com';
        $mail->Password = 'ripbxgbbtimfdcib';
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;

    // 设置发件人和收件人信息
    $mail->setFrom('jeffery.lyu@qq.com', 'Recovery');
    $mail->addAddress($email); // 添加收件人

    // 设置邮件格式和内容
    $mail->isHTML(true);
    $mail->Subject = '密码恢复验证码';
    $mail->Body    = "你的密码恢复验证码是：{$verificationCode}";

    // 发送邮件
    $mail->send();
    echo json_encode(['success' => true, 'message' => '验证码已发送。']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => '邮件未能发送。Mailer Error: ' . $mail->ErrorInfo]);
}
?>
