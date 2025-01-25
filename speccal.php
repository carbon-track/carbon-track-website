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
$activity = $_POST['activity'];
$dataInput = floatval($_POST['oridata']); // 假设数据是数值类型
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
    
    // 插入积分交易记录
    $insertSql = "INSERT INTO points_transactions (points, email, time, img, auth, raw, act, type) VALUES (:points, :email, :time, :img, :auth, :raw, :act, :type)";
    $insertStmt = $pdo->prepare($insertSql);
    $now = date('Y-m-d H:i:s');
    $insertStmt->bindValue(':points', "0");
    $insertStmt->bindParam(':email', $email);
    $insertStmt->bindParam(':time', $now);
    $insertStmt->bindParam(':img', $uploadPath);
    $insertStmt->bindValue(':auth', 'non');
    $insertStmt->bindParam(':raw', $dataInput);
    $insertStmt->bindParam(':act', $activity);
    $insertStmt->bindValue(':type', 'spec');
    $insertStmt->bindParam(':uid', $uid);
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
