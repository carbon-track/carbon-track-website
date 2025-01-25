<?php
require_once 'global_variables.php';
require_once 'global_error_handler.php';
require 'db.php';

header('Content-Type: application/json');

function updateTransactionHistory($pdo, $transaction_id, $type, $uid) {
    try {
        $content = "<p>尊敬的用户，Dear user,</p>
                    <p>我们很高兴地通知您，您提交的碳减排记录已经成功通过我们的审核流程。相应的积分已经添加到您的账户中，您现在可以查看更新后的积分余额。We are pleased to inform you that the carbon reduction record you submitted has successfully passed our review process. The corresponding credits have been added to your account and you can now view the updated points balance.</p>
                    <p>我们真诚地感谢您对碳减排事业的贡献，以及您对我们小程序的使用和支持。您的每一项努力都对促进环境的可持续发展起到了积极作用。请继续与我们一起，为打造更加绿色的未来而努力。We sincerely thank you for your contribution to the cause of carbon reduction, as well as your use and support of our platform. Each of your efforts plays a positive role in promoting environmental sustainability. Please continue to work with us for a greener future.</p>
                    <p>再次感谢，期待您继续参与我们的活动，并享受更多小程序带来的便利和乐趣。Thank you again and look forward to your continuing participation in our activities and enjoying the convenience and fun brought by the greener lifestyle.</p>
                    <p>诚挚的问候，Sincere regards,<br>CarbonTrack</p>";
        $pdo->beginTransaction();
    
        // Prepare the UPDATE statement with a named placeholder for the transaction ID
        $updstmt = $pdo->prepare("UPDATE points_transactions SET auth = 'yes' WHERE id = :id");
        $updstmt->bindParam(':id', $transaction_id, PDO::PARAM_INT);
        $updstmt->execute();

        if ($type === 'ord') {
            $ptstmt = $pdo->prepare("SELECT points, uid FROM points_transactions WHERE id = :id");
            $ptstmt->bindParam(':id', $transaction_id, PDO::PARAM_INT);
            $ptstmt->execute();
            $upoints = $ptstmt->fetch(PDO::FETCH_ASSOC);
            $val = $upoints['points'];
            $uid = $upoints['uid'];
            if ($updstmt->rowCount() > 0 && $ptstmt->rowCount() > 0) {
                 sendInAppMessage($pdo,10000,$uid,$content);
            } else {
                $pdo->rollBack();
                handleApiError(500, 'No rows updated');
            }
        } else if ($type === 'spec') {
             $gptstmt = $pdo->prepare("SELECT points FROM points_transactions WHERE id = :id");
            $gptstmt->bindParam(':id', $transaction_id, PDO::PARAM_INT);
            $gptstmt->execute();
            $pt = $gptstmt->fetch(PDO::FETCH_ASSOC);
            $points = $pt['points']; // 获取积分值
            $uptstmt = $pdo->prepare("UPDATE users SET points = points + :points WHERE id = :uid");
            $uptstmt->bindParam(':uid', $uid, PDO::PARAM_INT);
            $uptstmt->bindParam(':points', $points, PDO::PARAM_INT);
            $uptstmt->execute();
            if (!empty($uid)) {
                sendInAppMessage($pdo,10000,$uid,$content);
            } else {
                $pdo->rollBack();
                handleApiError(500, 'uid参数错误');
            }
        }
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Transaction approved.']);
    } catch (PDOException $e) {
        $pdo->rollBack();
        logException($e);
        handleApiError(500, 'Database error');
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $token = sanitizeInput($_POST['token']);
        $type = sanitizeInput($_POST['type']);
        $transaction_id = sanitizeInput($_POST['transactionId']);
         $uid = sanitizeInput($_POST['uid']);

        $email = opensslDecrypt($token);
        if (!isAdmin($email)) {
            handleApiError(401, 'Unauthorized');
        }
        updateTransactionHistory($pdo, $transaction_id, $type, $uid);
    } catch (Exception $e) {
        logException($e);
        handleApiError(500, 'Internal server error');
    }
}
?>
