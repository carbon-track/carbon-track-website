<?php
require 'db.php'; // 包含数据库连接配置

require 'admin_emails.php';

header('Content-Type: application/json');

function opensslDecrypt($data, $key) {
    $data = base64_decode($data);
    $ivLength = openssl_cipher_iv_length('aes-256-cbc');
    $iv = substr($data, 0, $ivLength);
    $encrypted = substr($data, $ivLength);
    return openssl_decrypt($encrypted, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
}
function sendmsg($pdo, $sender_id, $receiver_id){
                        $content = "<p>尊敬的用户，Dear user,</p>
<p>感谢您使用我们的小程序，并提交了碳减排相关证据。我们很遗憾地通知您，由于您提交的碳减排证据存在一些错误，我们不得不对您的碳积分进行了更改。Thank you for using our platform and submitting evidence related to carbon reduction. We regret to inform you that due to some errors in the carbon reduction evidence you submitted, we have had to make changes to your carbon credits.</p>
<p>我们建议您仔细检查您的证据，并确保准确地记录您的碳减排行为。如果您有任何疑问或需要进一步的帮助，请随时联系我们的团队。我们将竭诚为您提供支持和帮助。We recommend that you review your evidence carefully and ensure that your carbon reduction actions are accurately documented. If you have any questions or need further assistance, please feel free to contact our team. We will be happy to provide you with support and assistance.</p>
<p>我们感谢您对环境保护事业的关注和努力，并对您的理解和支持表示衷心的感谢。We thank you for your attention and efforts in the cause of environmental protection, and express our heartfelt thanks for your understanding and support.</p>
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
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

function ord_up_his($pdo, $transaction_id){
    try {
    $pdo->beginTransaction(); // Start transaction

    // Prepare the UPDATE statement with a named placeholder for the transaction ID
    $updstmt = $pdo->prepare("UPDATE points_transactions SET auth = 'no' WHERE id = :id");

    // Bind the transaction ID parameter to the prepared statement
    $updstmt->bindParam(':id', $transaction_id, PDO::PARAM_INT);

    // Execute the prepared statement
    $updstmt->execute();
    
    $ptstmt = $pdo->prepare("SELECT points, uid FROM points_transactions WHERE id = :id");
    // Bind the transaction ID parameter to the prepared statement
    $ptstmt->bindParam(':id', $transaction_id, PDO::PARAM_INT);
    // Execute the prepared statement
    $ptstmt->execute();
    $upoints = $ptstmt->fetch(PDO::FETCH_ASSOC);
    $val=$upoints['points'];
    $uid=$upoints['uid'];
    
    $emstmt = $pdo->prepare("UPDATE users SET points = points - :val WHERE id = :uid");

    // Bind the transaction ID parameter to the prepared statement
    $emstmt->bindParam(':uid', $uid, PDO::PARAM_STR);
    $emstmt->bindParam(':val', $val, PDO::PARAM_STR);

    // Execute the prepared statement
    $emstmt->execute();

    // Check if the update was successful
    if ($emstmt->rowCount() > 0&&$updstmt->rowCount() > 0&&$ptstmt->rowCount() > 0) {
        $pdo->commit(); // Commit the transaction
                sendmsg($pdo,10000,$uid);

    } else {
        $pdo->rollBack(); // Roll back the transaction if no rows were updated
        echo json_encode(['success' => false, 'error' => 'No rows updated']);
    }
} catch (PDOException $e) {
    $pdo->rollBack(); // Roll back the transaction in case of any error
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
}

function spec_up_his($pdo, $transaction_id){
        try {
        $pdo->beginTransaction(); // Start transaction

        // Prepare the UPDATE statement with a named placeholder for the transaction ID
        $stmt = $pdo->prepare("UPDATE points_transactions SET auth = 'no' WHERE id = :id");
        $stmt->bindParam(':id', $transaction_id, PDO::PARAM_INT);
        $stmt->execute();

        // Check if the update was successful
        if ($stmt->rowCount() > 0) {
            $emstmt = $pdo->prepare("SELECT uid FROM points_transactions WHERE id = :id");
            $emstmt->bindParam(':id', $transaction_id, PDO::PARAM_INT);
            $emstmt->execute();
            $userIdRow = $emstmt->fetch(PDO::FETCH_ASSOC);

            if(!empty($userIdRow['uid'])){
                $uid = $userIdRow['uid']; // 获取单个用户的电子邮件地址
                sendmsg($pdo,10000,$uid);
            } else {
                $pdo->rollBack(); // Roll back the transaction if no email was found
                echo json_encode(['success' => false, 'error' => '邮件记录错误']);
            }
        } else {
            $pdo->rollBack(); // Roll back the transaction if no rows were updated
            echo json_encode(['success' => false, 'error' => 'No rows updated']);
        }
    } catch (PDOException $e) {
        $pdo->rollBack(); // Roll back the transaction in case of any error
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
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
$type=$_POST['type'];    
$transaction_id = $_POST['transactionId']; // Ensure this is sanitized or validated appropriately
if($type=="spec"){
    spec_up_his($pdo, $transaction_id);
}else{
    ord_up_his($pdo, $transaction_id);
}
}
?>
