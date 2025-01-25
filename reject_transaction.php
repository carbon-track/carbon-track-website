<?php
require_once 'global_variables.php';
require_once 'global_error_handler.php';
require 'db.php';

header('Content-Type: application/json');

function updateTransactionHistory($pdo, $transaction_id, $type) {
    try {
        $pdo->beginTransaction();
        $content = "<p>尊敬的用户，Dear user,</p>
    <p>感谢您使用我们的小程序，并提交了碳减排相关证据。我们很遗憾地通知您，由于您提交的碳减排证据存在一些错误，我们不得不对您的碳积分进行了更改。Thank you for using our platform and submitting evidence related to carbon reduction. We regret to inform you that due to some errors in the carbon reduction evidence you submitted, we have had to make changes to your carbon credits.</p>
    <p>我们建议您仔细检查您的证据，并确保准确地记录您的碳减排行为。如果您有任何疑问或需要进一步的帮助，请随时联系我们的团队。我们将竭诚为您提供支持和帮助。We recommend that you review your evidence carefully and ensure that your carbon reduction actions are accurately documented. If you have any questions or need further assistance, please feel free to contact our team. We will be happy to provide you with support and assistance.</p>
    <p>我们感谢您对环境保护事业的关注和努力，并对您的理解和支持表示衷心的感谢。We thank you for your attention and efforts in the cause of environmental protection, and express our heartfelt thanks for your understanding and support.</p>
    <p>诚挚的问候，Sincere regards,<br>CarbonTrack</p>";
        // Prepare the UPDATE statement with a named placeholder for the transaction ID
        $updstmt = $pdo->prepare("UPDATE points_transactions SET auth = 'no' WHERE id = :id");
        $updstmt->bindParam(':id', $transaction_id, PDO::PARAM_INT);
        $updstmt->execute();

        if ($type === 'ord') {
            $ptstmt = $pdo->prepare("SELECT points, uid FROM points_transactions WHERE id = :id");
            $ptstmt->bindParam(':id', $transaction_id, PDO::PARAM_INT);
            $ptstmt->execute();
            $upoints = $ptstmt->fetch(PDO::FETCH_ASSOC);
            $val = $upoints['points'];
            $uid = $upoints['uid'];

            $emstmt = $pdo->prepare("UPDATE users SET points = points - :val WHERE id = :uid");
            $emstmt->bindParam(':uid', $uid, PDO::PARAM_STR);
            $emstmt->bindParam(':val', $val, PDO::PARAM_STR);
            $emstmt->execute();
            if ($emstmt->rowCount() > 0 && $updstmt->rowCount() > 0 && $ptstmt->rowCount() > 0) {
                sendInAppMessage($pdo,10000,$uid,$content);
            } else {
                $pdo->rollBack();
                handleApiError(500, 'No rows updated');
            }
        } else if ($type === 'spec') {
            $emstmt = $pdo->prepare("SELECT uid FROM points_transactions WHERE id = :id");
            $emstmt->bindParam(':id', $transaction_id, PDO::PARAM_INT);
            $emstmt->execute();
            $userIdRow = $emstmt->fetch(PDO::FETCH_ASSOC);
            if (!empty($userIdRow['uid'])) {
                $uid = $userIdRow['uid'];
                sendInAppMessage($pdo,10000,$uid,$content);
            } else {
                $pdo->rollBack();
                handleApiError(500, '邮件记录错误');
            }
        }
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Transaction rejected.']);
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

        $email = opensslDecrypt($token);

        if (!isAdmin($email)) {
            handleApiError(401, 'Unauthorized');
        }
        updateTransactionHistory($pdo, $transaction_id, $type);
    } catch (Exception $e) {
        logException($e);
        handleApiError(500, 'Internal server error');
    }
}
?>
