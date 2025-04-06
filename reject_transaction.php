<?php
require_once 'global_variables.php';
require_once 'global_error_handler.php';
// require 'db.php'; // Included via global_variables

header('Content-Type: application/json; charset=UTF-8');

function updateTransactionHistory($pdo, $transaction_id, $type) {
    // Wrap the entire function logic in try/catch
    try {
        $content = "<p>尊敬的用户，Dear user,</p>
    <p>感谢您使用我们的小程序，并提交了碳减排相关证据。我们很遗憾地通知您，由于您提交的碳减排证据存在一些错误，我们不得不对您的碳积分进行了更改。Thank you for using our platform and submitting evidence related to carbon reduction. We regret to inform you that due to some errors in the carbon reduction evidence you submitted, we have had to make changes to your carbon credits.</p>
    <p>我们建议您仔细检查您的证据，并确保准确地记录您的碳减排行为。如果您有任何疑问或需要进一步的帮助，请随时联系我们的团队。我们将竭诚为您提供支持和帮助。We recommend that you review your evidence carefully and ensure that your carbon reduction actions are accurately documented. If you have any questions or need further assistance, please feel free to contact our team. We will be happy to provide you with support and assistance.</p>
    <p>我们感谢您对环境保护事业的关注和努力，并对您的理解和支持表示衷心的感谢。We thank you for your attention and efforts in the cause of environmental protection, and express our heartfelt thanks for your understanding and support.</p>
    <p>诚挚的问候，Sincere regards,<br>CarbonTrack</p>";
        
        $pdo->beginTransaction();
    
        // Update transaction status to 'no'
        $updstmt = $pdo->prepare("UPDATE points_transactions SET auth = 'no' WHERE id = :id AND auth = 'non'"); // Only update if currently 'non'
        $updstmt->bindParam(':id', $transaction_id, PDO::PARAM_INT);
        $updstmt->execute();

        // Check if the update actually happened
        if ($updstmt->rowCount() === 0) {
            $pdo->rollBack();
            // Use handleApiError for a client-understandable issue (e.g., already processed)
            handleApiError(409, 'Transaction not found or already processed.'); 
        }

        // Get transaction details (points, uid)
            $ptstmt = $pdo->prepare("SELECT points, uid FROM points_transactions WHERE id = :id");
            $ptstmt->bindParam(':id', $transaction_id, PDO::PARAM_INT);
            $ptstmt->execute();
        $transactionData = $ptstmt->fetch(PDO::FETCH_ASSOC);

        if (!$transactionData) {
             $pdo->rollBack();
             throw new Exception('Could not retrieve transaction data after update for rejection. ID: ' . $transaction_id);
        }
        
        $points = $transactionData['points'];
        $actualUid = $transactionData['uid'];

        // Handle points adjustment based on type
        if ($type === 'ord') {
            // Rejection of 'ord' type. If points were PREVIOUSLY awarded assuming approval,
            // they might need to be deducted here. This logic depends heavily on the award flow.
            // **Assuming points were awarded before admin approval for 'ord'**: Deduct points.
             if ($points > 0) { // Only deduct if points were positive
                 $emstmt = $pdo->prepare("UPDATE users SET points = points - :points WHERE id = :uid");
                 $emstmt->bindParam(':uid', $actualUid, PDO::PARAM_INT);
                 $emstmt->bindParam(':points', $points, PDO::PARAM_STR);
            $emstmt->execute();
                 error_log("[Reject Transaction] Rejected 'ord' type transaction ID: {$transaction_id}, deducted {$points} points from UID: {$actualUid}");
            } else {
                  error_log("[Reject Transaction] Rejected 'ord' type transaction ID: {$transaction_id} for UID: {$actualUid} (No points deducted as original points were <= 0)");
            }
        } else if ($type === 'spec') {
            // Rejection of 'spec' type. Points were likely NOT added yet, so just set status to 'no'.
             error_log("[Reject Transaction] Rejected 'spec' type transaction ID: {$transaction_id} for UID: {$actualUid} (No points change needed)");
            } else {
                $pdo->rollBack();
             throw new Exception('Invalid transaction type specified for rejection: ' . $type);
        }
        
        // Send notification message
        if (!empty($actualUid)) {
             sendInAppMessage($pdo, 10000, $actualUid, $content);
        } else {
             error_log("[Reject Transaction] Warning: UID was empty for transaction ID: {$transaction_id} when trying to send message.");
            }
        
        $pdo->commit();
        // Success is indicated by returning from the function without error
        
    } catch (Exception $e) { 
        // Ensure rollback if transaction started
        if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
        }
        // Re-throw the exception
        throw $e; 
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Input validation and sanitization
        if (!isset($_POST['token'], $_POST['type'], $_POST['transactionId'])) {
             handleApiError(400, 'Missing required parameters.');
        }
        $token = sanitizeInput($_POST['token']);
        $type = sanitizeInput($_POST['type']);
        $transaction_id = filter_input(INPUT_POST, 'transactionId', FILTER_VALIDATE_INT);

        if ($transaction_id === false || $transaction_id <= 0) {
             handleApiError(400, 'Invalid Transaction ID.');
        }

        // Authentication & Authorization
        $email = opensslDecrypt($token);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !isAdmin($email)) {
            handleApiError(403, 'Unauthorized'); // Use 403
        }

        global $pdo;
        if (!$pdo) {
             handleApiError(500, 'Database connection is not available.');
        }

        // Call the function to handle the update
        updateTransactionHistory($pdo, $transaction_id, $type);

        // If updateTransactionHistory didn't throw an exception, it succeeded
        echo json_encode(['success' => true, 'message' => 'Transaction rejected successfully.']);

    } catch (Exception $e) {
        // Catch exceptions from updateTransactionHistory or initial checks
        logException($e); // Log and exit via global handler
        // handleApiError(500, 'Internal server error'); // Redundant
    }
} else {
     handleApiError(405, 'Method not allowed.');
}
?>
