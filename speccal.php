<?php
// require_once 'db.php'; // Included via global_variables
require_once 'global_variables.php';
require_once 'global_error_handler.php';

header('Content-Type: application/json; charset=UTF-8');
date_default_timezone_set('Asia/Shanghai');

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    handleApiError(405, 'Invalid request method.');
}

// Wrap in try block
try {
    // Check Turnstile token
    if (!isset($_POST['cf_token'])) { // Assuming token is sent as 'cf_token'
        handleApiError(400, 'Missing anti-bot verification token.');
    }
    $cftoken = $_POST['cf_token'];
    if (!verifyTurnstileToken($cftoken)) {
        handleApiError(403, 'Anti-bot verification failed.');
    }

    // Validate Auth Token and get Email
    if (!isset($_POST['token'])) {
         handleApiError(400, 'Auth token is required.');
    }
    $token = sanitizeInput($_POST['token']);
    $email = opensslDecrypt($token);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        handleApiError(401, 'Token解密失败，不是有效的邮件地址。');
    }
    
    // Get User ID - Use getUid from global_variables
    global $pdo;
    if (!$pdo) {
         handleApiError(500, 'Database connection is not available.');
    }
    $uid = getUid($pdo, $email);
     if (!$uid) {
         handleApiError(404, 'User not found for the given token.');
     }

    // Validate activity type and data
    $activity = sanitizeInput($_POST['activity'] ?? '');
    $dataInput = isset($_POST['oridata']) ? floatval($_POST['oridata']) : 0.0;
    $notes = isset($_POST['notes']) ? sanitizeInput($_POST['notes']) : NULL;
    $activityDate = isset($_POST['date']) ? sanitizeInput($_POST['date']) : date('Y-m-d');

    // Validate date format and ensure it's not in the future
    if (!empty($activityDate)) {
        $dateObj = DateTime::createFromFormat('Y-m-d', $activityDate);
        if (!$dateObj || $dateObj->format('Y-m-d') !== $activityDate) {
            handleApiError(400, '日期格式无效');
        }
        $today = new DateTime();
        // Allow today, reject future
        if ($dateObj->format('Y-m-d') > $today->format('Y-m-d')) { 
            handleApiError(400, '不能提交未来日期的记录');
        }
    }
    
    // File Upload Handling
    $uploadPath = ''; // Initialize upload path
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $image = $_FILES['image'];
        $uploadDirectory = "uploads/"; // Ensure this directory exists and is writable
        
        // Basic security checks
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($image['tmp_name']);
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/heic', 'image/heif']; // Adjust allowed types
        if (!in_array($mimeType, $allowedTypes)) {
             handleApiError(400, '不允许的文件类型。');
        }
        if ($image['size'] > 5 * 1024 * 1024) { // 5MB limit
            handleApiError(400, '文件过大，请上传小于5MB的图片。');
        }

        // Create unique filename
        $fileExtension = strtolower(pathinfo($image['name'], PATHINFO_EXTENSION));
        // Ensure extension is reasonable
        if (!in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'heic', 'heif'])) {
            $fileExtension = 'jpg'; // Default to jpg if extension is weird/missing
        }
        $dateTime = date('YmdHis');
        // Use UID instead of email for filename if possible
        $safeFilenamePart = preg_replace('/[^a-zA-Z0-9_-]/', '_', $email); // Basic sanitization for filename part
        $newFileName = $safeFilenamePart . '_' . $dateTime . '_' . uniqid() . '.' . $fileExtension;
        $uploadPath = $uploadDirectory . $newFileName;

        if (!move_uploaded_file($image['tmp_name'], $uploadPath)) {
             // Throw exception if move fails
             throw new Exception("无法移动上传的文件到: {$uploadPath}"); 
        }
        // File uploaded successfully, $uploadPath is set

    } elseif (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        // Handle other upload errors
        $uploadError = $_FILES['image']['error'];
        handleApiError(400, "文件上传失败，错误代码: {$uploadError}");
    } else {
         // No file uploaded or UPLOAD_ERR_NO_FILE - This might be acceptable depending on requirements
         // handleApiError(400, '需要上传图片证明。'); // Uncomment if image is mandatory
         $uploadPath = NULL; // Explicitly set to NULL if no image/not required
    }

    // Calculate carbon savings based on activity type
    $carbonSavings = 0;
    switch ($activity) {
        case '节约用电1度':
            $carbonSavings = $dataInput; // Assuming dataInput is kWh?
            break;
        case '节约用水1L':
            $carbonSavings = $dataInput; // Assuming dataInput is Liters?
            break;
        case '垃圾分类1次':
            $carbonSavings = 145; // Fixed value?
            break;
        default:
            handleApiError(400, '未知的活动类型。');
    }
    
    // Ensure points are non-negative
    if ($carbonSavings < 0) $carbonSavings = 0;

    // Start transaction
    $pdo->beginTransaction();

    // Update user points
    $updateSql = "UPDATE users SET points = points + :points WHERE id = :uid"; // Update by UID
    $updateStmt = $pdo->prepare($updateSql);
    $updateStmt->bindParam(':points', $carbonSavings, PDO::PARAM_STR); // Points might be float
    $updateStmt->bindParam(':uid', $uid, PDO::PARAM_INT);
    $updateStmt->execute();

    // Insert points transaction record
    $insertSql = "INSERT INTO points_transactions (points, email, time, img, auth, raw, act, type, notes, activity_date, uid) 
                  VALUES (:points, :email, :time, :img, :auth, :raw, :act, :type, :notes, :activity_date, :uid)";
    $insertStmt = $pdo->prepare($insertSql);
    $now = date('Y-m-d H:i:s');
    $insertStmt->bindParam(':points', $carbonSavings, PDO::PARAM_STR);
    $insertStmt->bindParam(':email', $email, PDO::PARAM_STR);
    $insertStmt->bindParam(':time', $now, PDO::PARAM_STR);
    $insertStmt->bindParam(':img', $uploadPath, PDO::PARAM_STR);
    $insertStmt->bindValue(':auth', 'non', PDO::PARAM_STR); // Assuming 'non' means non-authenticated/pending
    $insertStmt->bindParam(':raw', $dataInput, PDO::PARAM_STR);
    $insertStmt->bindParam(':act', $activity, PDO::PARAM_STR);
    $insertStmt->bindValue(':type', 'spec');  // Special type 'spec'
    $insertStmt->bindParam(':notes', $notes, PDO::PARAM_STR);
    $insertStmt->bindParam(':activity_date', $activityDate, PDO::PARAM_STR);
    $insertStmt->bindParam(':uid', $uid, PDO::PARAM_INT);
    $insertStmt->execute();

    // Commit transaction
    $pdo->commit();

    // Success response
    echo json_encode(['success' => true, 'message' => '碳减排积分记录已加入审核队列。', 'points' => $carbonSavings]);

} catch (PDOException $e) {
    // Rollback transaction on PDO error
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    logException($e); // Log and exit via global handler

} catch (Exception $e) {
    // Rollback transaction on general error if applicable
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    logException($e); // Log and exit via global handler
}

?>
