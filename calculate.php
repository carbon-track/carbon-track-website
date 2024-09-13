<?php
date_default_timezone_set('Asia/Shanghai');

// 获取当前日期和时间
$message = '';
// 假设已经有了数据库连接 $pdo
require 'db.php';
function opensslDecrypt($data, $key)
{
    $data = base64_decode($data);
    $ivLength = openssl_cipher_iv_length('aes-256-cbc');
    // 分离IV和加密数据
    $iv = substr($data, 0, $ivLength);
    $encrypted = substr($data, $ivLength);
    return openssl_decrypt($encrypted, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
}

//$inputData = json_decode(file_get_contents('php://input'), true);
$token = $_POST['token'];
$base64Key = "28lVmS8LHIZIQdAmT6jyHal29N8g6aRZrHEA2mv/q/4=";
$key = base64_decode($base64Key);
$email = opensslDecrypt($token, $key);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Token不合法']);
    exit; // 停止脚本执行
}

// 验证活动类型和数据
$activity = $_POST['activity'];
$dataInput = floatval($_POST['oridata']); // 假设数据是数值类型
if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
    $image = $_FILES['image'];
    $uploadDirectory = "uploads/";
    $imageType = $image['type'];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/tiff', 'image/heif', 'image/heic', 'image/heif-sequence', 'image/heic-sequence'];

    if (in_array($imageType, $allowedTypes) && false !== $imgDetails = getimagesize($image['tmp_name'])) {
        if ($image['size'] <= 5000000) {
            $fileExtension = pathinfo($image['name'], PATHINFO_EXTENSION);
            $dateTime = date('YmdHis');
            $newFileName = $email . $dateTime . '.' . $fileExtension;
            $uploadPath = $uploadDirectory . $newFileName;

            if (move_uploaded_file($image['tmp_name'], $uploadPath)) {
                $message = 'true';
            } else {
                $message = '移动文件至上传目录时出错！';
            }
        } else {
            $message = '文件过大！';
        }
    } else {
        $message = '文件格式不允许或不是有效的图片文件！';
    }
} else {
    $message = '没有上传文件或上传过程中出现错误！';
}

if ($message != 'true') {
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

$id = $_POST['id'];
// 根据活动类型计算碳减排克数
$carbonSavings = 0;
// 根据活动类型计算碳减排克数
switch ($activity) {
    case '旧衣回收1kg / Recycle 1kg old clothes':
        $carbonSavings = $dataInput * 3.6;
        break;
    case '二手交易1次 / Second-hand transaction 1 time':
        $carbonSavings = $dataInput * 10 + 4;
        break;
    case '衣物租赁1次 / Clothing rental service 1 time':
        $carbonSavings = $dataInput * 10 + 4;
        break;
    case '减少肉类消费1kg / Reduce meat consumption 1kg':
        $carbonSavings = $dataInput * 15.54;
        break;/*
    case '节约用电1度':
        $carbonSavings = $dataInput * 0.638;
        break;*/
    case '光盘行动1次 / Finish everything on your plate 1 time':
        $carbonSavings = $dataInput * 0.0329041095890411;
        break;
    case '居家回收利用':
        $carbonSavings = $dataInput * 10 + 4;
        break;
    /*
case '节约用水1L':
    $carbonSavings = $dataInput *0.194;
    break;*/
    case '公交出行1km / Bus transport 1km':
        $carbonSavings = $dataInput * 0.094;
        break;
    case '地铁出行1km / Subway travel 1km':
        $carbonSavings = $dataInput * 0.089;
        break;
    case '步行1km / Walk 1km':
        $carbonSavings = $dataInput * 0.135;
        break;
    case '骑行1km / Cycle 1km':
        $carbonSavings = $dataInput * 0.05;
        break;
    case '拼车1km / Carpool 1km':
        $carbonSavings = $dataInput * 0.0675;
        break;
    case '上网课1h / Online class 1h':
        $carbonSavings = $dataInput * 0.15;
        break;
    case '提交电子作业1次 / Write assignment electronically 1 time':
        $carbonSavings = $dataInput * 0.05;
        break;
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
    $updateStmt->bindParam(':points', $carbonSavings);
    $updateStmt->bindParam(':email', $email);
    $updateStmt->execute();

    // 插入积分交易记录
    $insertSql = "INSERT INTO points_transactions (points, email, time, img, auth, raw, act, uid) VALUES (:points, :email, :time, :img, :auth, :raw, :act, :uid)";
    $insertStmt = $pdo->prepare($insertSql);
    $now = date('Y-m-d H:i:s');
    $insertStmt->bindParam(':points', $carbonSavings);
    $insertStmt->bindParam(':email', $email);
    $insertStmt->bindParam(':time', $now);
    $insertStmt->bindParam(':img', $uploadPath);
    $insertStmt->bindValue(':auth', 'non');
    $insertStmt->bindParam(':raw', $dataInput);
    $insertStmt->bindParam(':act', $activity);
    $insertStmt->bindParam(':uid', $id);
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
