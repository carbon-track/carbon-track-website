<?php
require_once 'global_variables.php';
require_once 'global_error_handler.php';
// require 'db.php'; // Included via global_variables

header('Content-Type: application/json; charset=UTF-8');

function updateTransactionHistory($pdo, $transaction_id, $type, $uid) {
    // Wrap the entire function logic in try/catch
    try {
        $content = "<p>尊敬的用户，Dear user,</p>
                    <p>我们很高兴地通知您，您提交的碳减排记录已经成功通过我们的审核流程。相应的积分已经添加到您的账户中，您现在可以查看更新后的积分余额。We are pleased to inform you that the carbon reduction record you submitted has successfully passed our review process. The corresponding credits have been added to your account and you can now view the updated points balance.</p>
                    <p>我们真诚地感谢您对碳减排事业的贡献，以及您对我们小程序的使用和支持。您的每一项努力都对促进环境的可持续发展起到了积极作用。请继续与我们一起，为打造更加绿色的未来而努力。We sincerely thank you for your contribution to the cause of carbon reduction, as well as your use and support of our platform. Each of your efforts plays a positive role in promoting environmental sustainability. Please continue to work with us for a greener future.</p>
                    <p>再次感谢，期待您继续参与我们的活动，并享受更多小程序带来的便利和乐趣。Thank you again and look forward to your continuing participation in our activities and enjoying the convenience and fun brought by the greener lifestyle.</p>
                    <p>诚挚的问候，Sincere regards,<br>CarbonTrack</p>";
        
        $pdo->beginTransaction();
    
        // Update transaction status
        $updstmt = $pdo->prepare("UPDATE points_transactions SET auth = 'yes' WHERE id = :id AND auth = 'non'"); // Only update if currently 'non'
        $updstmt->bindParam(':id', $transaction_id, PDO::PARAM_INT);
        $updstmt->execute();

        // Check if the update actually happened
        if ($updstmt->rowCount() === 0) {
             // No rows updated - either ID doesn't exist or was already approved/rejected
             $pdo->rollBack();
             // Use handleApiError for a client-understandable issue (e.g., already processed)
             handleApiError(409, 'Transaction not found or already processed.'); 
             // Or throw Exception if it indicates a bigger problem
             // throw new Exception('Transaction could not be updated. ID: ' . $transaction_id);
        }

        // Get points and UID (might be redundant if passed in correctly)
        $ptstmt = $pdo->prepare("SELECT points, uid FROM points_transactions WHERE id = :id");
        $ptstmt->bindParam(':id', $transaction_id, PDO::PARAM_INT);
        $ptstmt->execute();
        $transactionData = $ptstmt->fetch(PDO::FETCH_ASSOC);

        if (!$transactionData) {
             $pdo->rollBack();
             throw new Exception('Could not retrieve transaction data after update. ID: ' . $transaction_id);
        }
        
        $points = $transactionData['points'];
        $actualUid = $transactionData['uid']; // Use UID from the transaction record itself for security
        
        // Update user points based on type
        if ($type === 'ord') {
            // For 'ord', points are usually deducted elsewhere (like exchange), 
            // approval might just be a status change. If points need adding here, adjust logic.
            // Assuming no points change needed here for 'ord' type on approval.
             error_log("[Approve Transaction] Approved 'ord' type transaction ID: {$transaction_id} for UID: {$actualUid}");
        } else if ($type === 'spec') {
            // For 'spec', add the points to the user account
             $uptstmt = $pdo->prepare("UPDATE users SET points = points + :points WHERE id = :uid");
             $uptstmt->bindParam(':points', $points, PDO::PARAM_STR); // Points can be float
             $uptstmt->bindParam(':uid', $actualUid, PDO::PARAM_INT);
             $uptstmt->execute();
             error_log("[Approve Transaction] Approved 'spec' type transaction ID: {$transaction_id}, added {$points} points to UID: {$actualUid}");
        } else {
             $pdo->rollBack();
             throw new Exception('Invalid transaction type specified: ' . $type);
        }
        
        // Send notification message
        if (!empty($actualUid)) {
             sendInAppMessage($pdo, 10000, $actualUid, $content); // Use actualUid from DB
        } else {
             // This case should ideally not happen if transactionData was fetched
             error_log("[Approve Transaction] Warning: UID was empty for transaction ID: {$transaction_id} when trying to send message.");
        }
        
        $pdo->commit();
        // Return success from the main block, not here
        
    } catch (Exception $e) { // Catch PDOException or general Exception
        // Ensure rollback if transaction started
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        // Re-throw the exception to be caught by the main try-catch which calls logException
        throw $e; 
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Input validation and sanitization
        if (!isset($_POST['token'], $_POST['type'], $_POST['transactionId'])) { // UID might not be needed if fetched from DB
             handleApiError(400, 'Missing required parameters.');
        }
        $token = sanitizeInput($_POST['token']);
        $type = sanitizeInput($_POST['type']);
        $transaction_id = filter_input(INPUT_POST, 'transactionId', FILTER_VALIDATE_INT);
        // $uid = isset($_POST['uid']) ? filter_input(INPUT_POST, 'uid', FILTER_VALIDATE_INT) : null; // Maybe remove if using UID from DB

        if ($transaction_id === false || $transaction_id <= 0) {
             handleApiError(400, 'Invalid Transaction ID.');
        }

        // Authentication & Authorization
        $email = opensslDecrypt($token);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !isAdmin($email)) {
            handleApiError(403, 'Unauthorized'); // Use 403 for authorization failure
        }
        
        global $pdo;
        if (!$pdo) {
             handleApiError(500, 'Database connection is not available.');
        }

        // Call the function to handle the update
        // Pass null for uid if we rely on fetching it inside the function
        updateTransactionHistory($pdo, $transaction_id, $type, null); 

        // If updateTransactionHistory didn't throw an exception, it succeeded
        echo json_encode(['success' => true, 'message' => 'Transaction approved successfully.']);

    } catch (Exception $e) {
        // Catch exceptions from updateTransactionHistory or initial checks
        logException($e); // Log and exit via global handler
        // handleApiError(500, 'Internal server error'); // Redundant
    }
} else {
     handleApiError(405, 'Method not allowed.');
}
?>
