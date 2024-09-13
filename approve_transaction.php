<?php
require 'db.php'; // 包含数据库连接配置
require 'admin_emails.php';

header('Content-Type: application/json');

function opensslDecrypt($data, $key)
{
    $data = base64_decode($data);
    $ivLength = openssl_cipher_iv_length('aes-256-cbc');
    $iv = substr($data, 0, $ivLength);
    $encrypted = substr($data, $ivLength);
    return openssl_decrypt($encrypted, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
}

function sendmsg($pdo, $sender_id, $receiver_id)
{
    $content = "<p>尊敬的用户，Dear user,</p>
                    <p>我们很高兴地通知您，您提交的碳减排记录已经成功通过我们的审核流程。相应的积分已经添加到您的账户中，您现在可以查看更新后的积分余额。We are pleased to inform you that the carbon reduction record you submitted has successfully passed our review process. The corresponding credits have been added to your account and you can now view the updated points balance.</p>
                    <p>我们真诚地感谢您对碳减排事业的贡献，以及您对我们小程序的使用和支持。您的每一项努力都对促进环境的可持续发展起到了积极作用。请继续与我们一起，为打造更加绿色的未来而努力。We sincerely thank you for your contribution to the cause of carbon reduction, as well as your use and support of our platform. Each of your efforts plays a positive role in promoting environmental sustainability. Please continue to work with us for a greener future.</p>
                    <p>再次感谢，期待您继续参与我们的活动，并享受更多小程序带来的便利和乐趣。Thank you again and look forward to your continuing participation in our activities and enjoying the convenience and fun brought by the greener lifestyle.</p>
                    <p>诚挚的问候，Sincere regards,<br>CarbonTrack</p>";
    try {
        $pdo->beginTransaction();
        $sql = "INSERT INTO `messages` (`sender_id`, `receiver_id`, `content`, `send_time`, `is_read`) VALUES (?, ?, ?, NOW(), '0')";
        $stmt = $pdo->prepare($sql);
        if (!$stmt->execute([$sender_id, $receiver_id, $content])) {
            // 如果执行失败，抛出异常
            throw new Exception("站内信未能发送。");
        }
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => '站内信已发送。']);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false/*, 'message' => $e->getMessage()*/]);
    }
    exit;
}

function ord_up_his($pdo, $transaction_id, $uid)
{
    try {
        //$pdo->beginTransaction(); // Start transaction

        // Prepare the UPDATE statement with a named placeholder for the transaction ID
        $stmt = $pdo->prepare("UPDATE points_transactions SET auth = 'yes' WHERE id = :id");
        $stmt->bindParam(':id', $transaction_id, PDO::PARAM_INT);
        $stmt->execute();

        // Check if the update was successful
        if ($stmt->rowCount() > 0) {
            if (!empty($uid)) {
                sendmsg($pdo, 10000, $uid);
            } else {
                ////$pdo->rollBack(); // Roll back the transaction if no email was found
                echo json_encode(['success' => false, 'message' => 'uid参数错误']);
            }
        } else {
            ////$pdo->rollBack(); // Roll back the transaction if no rows were updated
            echo json_encode(['success' => false, 'message' => 'No rows updated']);
        }
    } catch (PDOException $e) {
        //$pdo->rollBack(); // Roll back the transaction in case of any message
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function spec_up_his($pdo, $transaction_id, $uid)
{
    try {
        $stmt = $pdo->prepare("UPDATE points_transactions SET auth = 'yes' WHERE id = :id");
        $stmt->bindParam(':id', $transaction_id, PDO::PARAM_INT);
        $stmt->execute();

        $gptstmt = $pdo->prepare("SELECT points FROM points_transactions WHERE id = :id");
        $gptstmt->bindParam(':id', $transaction_id, PDO::PARAM_INT);
        $gptstmt->execute();
        $pt = $gptstmt->fetch(PDO::FETCH_ASSOC);
        $points = $pt['points']; // 获取积分值
        // Check if the update was successful
        if ($gptstmt->rowCount() > 0) {
            $uptstmt = $pdo->prepare("UPDATE users SET points = points + :points WHERE id = :uid");
            $uptstmt->bindParam(':uid', $uid, PDO::PARAM_INT);
            $uptstmt->bindParam(':points', $points, PDO::PARAM_INT);
            $uptstmt->execute();
            if (!empty($uid)) {
                sendmsg($pdo, 10000, $uid);
            } else {
                ////$pdo->rollBack(); // Roll back the transaction if no email was found
                echo json_encode(['success' => false, 'message' => 'uid参数错误']);
            }
        } else {
            ////$pdo->rollBack(); // Roll back the transaction if no rows were updated
            echo json_encode(['success' => false, 'message' => 'No rows updated']);
        }
    } catch (PDOException $e) {
        //$pdo->rollBack(); // Roll back the transaction in case of any message
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $token = $_POST['token'];
    $base64Key = "28lVmS8LHIZIQdAmT6jyHal29N8g6aRZrHEA2mv/q/4=";
    $key = base64_decode($base64Key);
    $email = opensslDecrypt($token, $key);
    if (!isAdmin($email)) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    $type = $_POST['type'];
    $transaction_id = filter_var($_POST['transactionId'], FILTER_VALIDATE_INT);
    $uid = filter_var($_POST['uid'], FILTER_VALIDATE_INT);
    if ($type == "spec") {
        //$_points=$_POST['upt'];
        spec_up_his($pdo, $transaction_id, $uid);
    } else {
        ord_up_his($pdo, $transaction_id, $uid);
    }

}
?>
