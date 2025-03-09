<?php
require_once 'db.php';
require_once 'global_variables.php';
date_default_timezone_set('Asia/Shanghai');

// 获取当前日期和时间
$message ='';
//$inputData = json_decode(file_get_contents('php://input'), true);
$token = $_POST['token'];
$email = opensslDecrypt($token);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Token解密失败，不是有效的邮件地址。']);
    exit; // 停止脚本执行
}
$uid=$_POST['id'];
// 验证活动类型和数据
$activity = sanitizeInput($_POST['activity']);
$dataInput = floatval($_POST['oridata']);
$notes = isset($_POST['notes']) ? sanitizeInput($_POST['notes']) : NULL;
$activityDate = isset($_POST['date']) ? sanitizeInput($_POST['date']) : date('Y-m-d');

// 验证日期格式
if (!empty($activityDate)) {
    $dateObj = DateTime::createFromFormat('Y-m-d', $activityDate);
    if (!$dateObj || $dateObj->format('Y-m-d') !== $activityDate) {
        handleApiError(400, '日期格式无效');
    }
    
    // 检查日期是否是未来日期
    $today = new DateTime();
    if ($dateObj > $today) {
        handleApiError(400, '不能提交未来日期的记录');
    }
}

if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
    $image = $_FILES['image'];
    $uploadDirectory = "uploads/"; // 确保这个目录存在并且对PHP可写
    $imageType = $image['type'];
    $allowedTypes = [
        'image/jpeg',
        'image/png',
        'image/tiff',
        'image/heif',
        'image/heic',
        'image/heif-sequence',
        'image/heic-sequence'
    ];
    // 验证文件类型是否为允许的图片格式
    if (in_array($imageType, $allowedTypes)) {
        // 检查文件大小是否合适
        if ($image['size'] <= 5000000) { // 限制文件大小为5MB
            $fileExtension = pathinfo($image['name'], PATHINFO_EXTENSION);
            $dateTime = date('YmdHis');
            $newFileName = $email.$dateTime . '.' . $fileExtension;
            $uploadPath = $uploadDirectory . $newFileName;

            // 尝试将文件移动到最终目录
            if (move_uploaded_file($image['tmp_name'], $uploadPath)) {
                // 文件上传成功
                $message = 'true';
            } else {
                // 文件移动失败
                $message = '移动文件至上传目录时出错！';
            }
        } else {
            $message = '文件过大！';
        }
    } else {
        $message ='文件格式不允许！';
    }
} else {
    $message = '没有上传文件或上传过程中出现错误！';
}

if($message=='true'){
    
}else{
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}
    // 根据活动类型计算碳减排克数
$carbonSavings = 0;
// 根据活动类型计算碳减排克数
switch ($activity) {
    case '节约用电1度':
        $carbonSavings = $dataInput;
        break;
    case '节约用水1L':
        $carbonSavings = $dataInput;
        break;
    case '垃圾分类1次':
        $carbonSavings=145;
    default:
        echo json_encode(['success' => false, 'message' => '未知的活动类型。']);
        exit;
}
try {
    // 开始事务
    $pdo->beginTransaction();
    
    // 更新用户积分
    $updateSql = "UPDATE users SET points = points + :points WHERE email = :email";
    $updateStmt = $pdo->prepare($updateSql);
    $updateStmt->bindParam(':points', $carbonSavings, PDO::PARAM_STR);
    $updateStmt->bindParam(':email', $email, PDO::PARAM_STR);
    $updateStmt->execute();

    // 插入积分交易记录，添加notes和activity_date字段
    $insertSql = "INSERT INTO points_transactions (points, email, time, img, auth, raw, act, type, notes, activity_date, uid) 
                  VALUES (:points, :email, :time, :img, :auth, :raw, :act, :type, :notes, :activity_date, :uid)";
    $insertStmt = $pdo->prepare($insertSql);
    $now = date('Y-m-d H:i:s');
    $insertStmt->bindParam(':points', $carbonSavings, PDO::PARAM_STR);
    $insertStmt->bindParam(':email', $email, PDO::PARAM_STR);
    $insertStmt->bindParam(':time', $now, PDO::PARAM_STR);
    $insertStmt->bindParam(':img', $uploadPath, PDO::PARAM_STR);
    $insertStmt->bindValue(':auth', 'non', PDO::PARAM_STR);
    $insertStmt->bindParam(':raw', $dataInput, PDO::PARAM_STR);
    $insertStmt->bindParam(':act', $activity, PDO::PARAM_STR);
    $insertStmt->bindValue(':type', 'spec');  // 特殊类型 'spec'
    $insertStmt->bindParam(':notes', $notes, PDO::PARAM_STR);
    $insertStmt->bindParam(':activity_date', $activityDate, PDO::PARAM_STR);
    $id = getUid($pdo, $email);
    $insertStmt->bindParam(':uid', $id, PDO::PARAM_STR);
    $insertStmt->execute();

    // 提交事务
    $pdo->commit();
    
    echo json_encode(['success' => true, 'message' => '碳减排积分记录已加入审核队列，', 'points' => $carbonSavings]);
    //, 'path'=>$uploadPath]
} catch (Exception $e) {
    // 事务回滚
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => '执行更新时发生错误。' . $e->getMessage()]);
}

?>
