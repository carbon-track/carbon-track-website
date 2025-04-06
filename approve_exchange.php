<?php
require_once 'global_variables.php';
require_once 'global_error_handler.php';
require 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    handleApiError(405, 'Method Not Allowed');
}

$pdo = null; // Initialize PDO to use in catch block if needed

try {
    $token = sanitizeInput($_POST['token'] ?? '');
    $requestId = filter_input(INPUT_POST, 'requestId', FILTER_VALIDATE_INT);

    if (!$requestId) {
        handleApiError(400, 'Invalid Request ID.');
    }

    $adminEmail = opensslDecrypt($token);
    if (!isAdmin($adminEmail)) { // Ensure isAdmin function exists and works
        handleApiError(403, 'Unauthorized access.');
    }

    $pdo = new PDO($dsn, $user, $pass, $options);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->beginTransaction();

    // 1. Get Admin ID
    $stmtAdmin = $pdo->prepare("SELECT id FROM users WHERE email = :email");
    $stmtAdmin->bindParam(':email', $adminEmail, PDO::PARAM_STR);
    $stmtAdmin->execute();
    $adminId = $stmtAdmin->fetchColumn();
    if (!$adminId) {
        throw new Exception("Admin user not found in database for email: " . $adminEmail);
    }

    // 2. Lock and Fetch Request (ensure it's pending)
    $stmtFetch = $pdo->prepare("SELECT * FROM exchange_requests WHERE request_id = :requestId AND status = 'pending' FOR UPDATE");
    $stmtFetch->bindParam(':requestId', $requestId, PDO::PARAM_INT);
    $stmtFetch->execute();
    $request = $stmtFetch->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        $pdo->rollBack();
        handleApiError(404, 'Request not found or already processed.');
    }

    $userId = $request['user_id'];
    $userEmail = $request['user_email'];
    $productId = $request['product_id'];
    $pointsCost = $request['points_cost'];

    // 3. Verify User Points and Product Stock AGAIN
    // Lock user and product rows for consistency
    $stmtUserCheck = $pdo->prepare("SELECT points FROM users WHERE id = :userId FOR UPDATE");
    $stmtUserCheck->bindParam(':userId', $userId, PDO::PARAM_INT);
    $stmtUserCheck->execute();
    $currentUserPoints = $stmtUserCheck->fetchColumn();

    $stmtStockCheck = $pdo->prepare("SELECT name, stock FROM products WHERE product_id = :productId FOR UPDATE");
    $stmtStockCheck->bindParam(':productId', $productId, PDO::PARAM_INT);
    $stmtStockCheck->execute();
    $productDetails = $stmtStockCheck->fetch(PDO::FETCH_ASSOC);

    if ($currentUserPoints === false || $productDetails === false) {
        $pdo->rollBack();
        handleApiError(404, 'User or Product not found during final check.');
    }
    
    $productName = $productDetails['name']; // Get product name for notification
    $currentStock = $productDetails['stock'];

    if ($currentUserPoints < $pointsCost) {
        // Not enough points now, reject instead of erroring out?
        // For now, treat as failure to approve.
        $pdo->rollBack();
        handleApiError(400, 'User no longer has sufficient points (' . $currentUserPoints . ' < ' . $pointsCost . ').');
    }
    if ($currentStock <= 0) {
        $pdo->rollBack();
        handleApiError(400, 'Product is now out of stock.');
    }

    // 4. Perform Updates
    // Deduct points
    $stmtUpdatePoints = $pdo->prepare("UPDATE users SET points = points - :pointsCost WHERE id = :userId");
    $stmtUpdatePoints->bindParam(':pointsCost', $pointsCost, PDO::PARAM_INT);
    $stmtUpdatePoints->bindParam(':userId', $userId, PDO::PARAM_INT);
    $stmtUpdatePoints->execute();

    // Decrement stock
    $stmtUpdateStock = $pdo->prepare("UPDATE products SET stock = stock - 1 WHERE product_id = :productId");
    $stmtUpdateStock->bindParam(':productId', $productId, PDO::PARAM_INT);
    $stmtUpdateStock->execute();

    // Update request status
    $stmtUpdateRequest = $pdo->prepare("UPDATE exchange_requests SET status = 'approved', admin_id = :adminId, review_time = NOW() WHERE request_id = :requestId");
    $stmtUpdateRequest->bindParam(':adminId', $adminId, PDO::PARAM_INT);
    $stmtUpdateRequest->bindParam(':requestId', $requestId, PDO::PARAM_INT);
    $stmtUpdateRequest->execute();

    // Insert into original transactions table (Optional but good for history)
    $stmtInsertTransaction = $pdo->prepare("INSERT INTO transactions (user_id, user_email, product_id, points_spent, transaction_time, type, auth, request_id) VALUES (:userId, :email, :productId, :pointsCost, NOW(), 'exchange', 'yes', :requestId)");
    $stmtInsertTransaction->bindParam(':userId', $userId, PDO::PARAM_INT);
    $stmtInsertTransaction->bindParam(':email', $userEmail, PDO::PARAM_STR);
    $stmtInsertTransaction->bindParam(':productId', $productId, PDO::PARAM_INT);
    $stmtInsertTransaction->bindParam(':pointsCost', $pointsCost, PDO::PARAM_INT);
    $stmtInsertTransaction->bindParam(':requestId', $requestId, PDO::PARAM_INT); // Link back to request
    $stmtInsertTransaction->execute();

    // 5. Commit Transaction
    $pdo->commit();

    // 6. Send Notifications
    $subject = "Exchange Request Approved";
    $message = "Your exchange request (ID: {$requestId}) for '{$productName}' has been approved. {$pointsCost} points have been deducted from your account.";
    try {
        sendInAppMessage($pdo, $adminId, $userId, $message); // Admin ID as sender?
        $mail = initializeMailer();
        $mail->addAddress($userEmail);
        $mail->Subject = $subject;
        $mail->Body = $message;
        $mail->send();
    } catch (Exception $notifyError) {
        logException($notifyError, "Failed to send approval notification for exchange request {$requestId}");
        // Don't fail the overall success, but log the notification issue.
    }

    echo json_encode(['success' => true, 'message' => 'Exchange request approved successfully.']);

} catch (PDOException $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    logException($e, "Database error approving exchange request.");
    handleApiError(500, 'Database error.');
} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    logException($e, "General error approving exchange request.");
    handleApiError(500, 'An internal error occurred.');
}
?>
