<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'src/Exception.php';
require 'src/PHPMailer.php';
require 'src/SMTP.php';

function opensslDecrypt($data, $key) {
    $data = base64_decode($data);
    $ivLength = openssl_cipher_iv_length('aes-256-cbc');
    // 分离IV和加密数据
    $iv = substr($data, 0, $ivLength);
    $encrypted = substr($data, $ivLength);
    return openssl_decrypt($encrypted, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
}

$token = $_POST['token'];
$base64Key = "28lVmS8LHIZIQdAmT6jyHal29N8g6aRZrHEA2mv/q/4=";
$key = base64_decode($base64Key);
$email = opensslDecrypt($token, $key);

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Token解密失败，不是有效的邮件地址。']);
    exit; // 停止脚本执行
}

$mail = new PHPMailer(true); // Initialize the PHPMailer object

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
    $mail->setFrom('jeffery.lyu@qq.com', 'Notification');
    $mail->addAddress($email); // 添加收件人

    $mail->isHTML(true);
    $mail->Subject = '信息提交成功通知';
    $mail->Body = "<p>尊敬的用户，</p>
<p>感谢您使用我们的小程序，并提交了您的碳减排相关证据！我们很高兴地通知您，您的信息已成功提交。Thank you for using our platform and submitting your evidence of carbon reduction! We are pleased to inform you that your record has been successfully submitted.</p>
<p>您的贡献对于环境保护和碳减排事业至关重要。我们深知您对环境的关注和努力，您的行动将为创造更可持续的未来做出重要贡献。Your contribution is vital to the cause of environmental protection and carbon reduction. We know that your concern for the environment and your efforts will make an important contribution to creating a more sustainable future.</p>
<p>如果您有任何疑问或需要进一步的帮助，请随时联系我们的团队。我们将竭诚为您提供支持和指导。If you have any questions or need further assistance, please feel free to contact our team. We will be happy to provide you with support and guidance.</p>
<p>感谢您的积极参与和贡献！Thank you for your active participation and contribution!</p>
<p>诚挚的问候，Sincere regards,<br>CarbonTrack</p>";

    // 发送邮件
    $mail->send();
    echo json_encode(['success' => true, 'message' => '邮件已发送。']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => '邮件未能发送。Mailer Error: ' . $mail->ErrorInfo]);
}
?>
