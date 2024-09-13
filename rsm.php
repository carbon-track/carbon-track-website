<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

    require 'src/Exception.php';
    require 'src/PHPMailer.php';
    require 'src/SMTP.php';
 $mail = new PHPMailer(true);

try {
    // 邮件服务器设置
        $mail->CharSet = "UTF-8";
        $mail->SMTPDebug = 0;
        $mail->isSMTP();
        $mail->Host = 'smtp.qq.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'jeffery.lyu@qq.com';
        $mail->Password = 'ripbxgbbtimfdcib';
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;

    // 收件人设置
    $mail->setFrom('jeffery.lyu@qq.com', 'Regsitery');
    $mail->addAddress($_POST['email']); // 添加收件人

    // 邮件内容设置
    $mail->isHTML(true);
$mail->Subject = '账号注册成功通知 Account Registration Success Notification';
$mail->Body = "<p>尊敬的用户，Dear user, </p>
<p>感谢您选择注册我们的小程序！我们很高兴地通知您，您的账号已成功注册。Thank you for choosing our platform! We are thrilled to inform you that your account has been successfully registered.</p>
<p>您现在可以开始享受我们小程序提供的各种功能和服务。我们致力于为您提供优质的体验，并不断改进和更新我们的功能，以满足您的需求。You can now start enjoying the various functions and services offered by our platform. We are committed to providing you with a quality experience and are constantly improving and updating our features to meet your needs.</p>
<p>如果您在使用过程中遇到任何问题或有任何建议，我们的团队将随时为您提供帮助和支持。请随时联系我们，我们期待为您提供最佳的用户体验。If you encounter any problems or have any suggestions during use, our team will be ready to help and support you. Please feel free to contact us and we look forward to providing you with the best user experience.</p>
<p>再次感谢您对我们小程序的支持和信任！祝您使用愉快！Thanks again for your support and trust in our platform! Wish you a happy use!</p>
<p>诚挚的问候，Sincerely, <br>CarbonTrack</p>";


    $mail->send();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $mail->ErrorInfo]);
}
?>
