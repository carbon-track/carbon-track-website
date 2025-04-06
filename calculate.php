<?php
require_once 'global_variables.php';
require_once 'global_error_handler.php';
require 'db.php';

header('Content-Type: application/json');
date_default_timezone_set('Asia/Shanghai');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Verify Turnstile token first
        if (!isset($_POST['cf-turnstile-response'])) {
            handleApiError(400, 'Missing anti-bot verification token.');
        }
        $turnstileToken = $_POST['cf-turnstile-response'];
        if (!verifyTurnstileToken($turnstileToken)) {
            handleApiError(403, 'Anti-bot verification failed. Please try again.');
        }

        // Proceed with the rest of the logic if verification passes
        $token = sanitizeInput($_POST['token']);
        $email = opensslDecrypt($token);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            handleApiError(400, 'Token不合法');
        }

        $activity = sanitizeInput($_POST['activity']);
        $dataInput = floatval($_POST['oridata']);
        $notes = isset($_POST['notes']) ? sanitizeInput($_POST['notes']) : NULL;
        $activityDate = isset($_POST['date']) ? sanitizeInput($_POST['date']) : date('Y-m-d');

        if (!empty($activityDate)) {
            $dateObj = DateTime::createFromFormat('Y-m-d', $activityDate);
            if (!$dateObj || $dateObj->format('Y-m-d') !== $activityDate) {
                handleApiError(400, '日期格式无效');
            }
            
            $today = new DateTime();
            if ($dateObj > $today) {
                handleApiError(400, '不能提交未来日期的记录');
            }
        }

        $uploadPath = '';

        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $image = $_FILES['image'];
            $uploadDirectory = "uploads/";
            $imageType = $image['type'];
            $allowedTypes = ['image/jpeg', 'image/png', 'image/tiff', 'image/heif', 'image/heic', 'image/heif-sequence', 'image/heic-sequence'];

            if (in_array($imageType, $allowedTypes) && false !== getimagesize($image['tmp_name'])) {
                if ($image['size'] <= 5000000) {
                    $fileExtension = pathinfo($image['name'], PATHINFO_EXTENSION);
                    $dateTime = date('YmdHis');
                    $newFileName = $email . $dateTime . '.' . $fileExtension;
                    $uploadPath = $uploadDirectory . $newFileName;

                    if (!move_uploaded_file($image['tmp_name'], $uploadPath)) {
                        handleApiError(500, '移动文件至上传目录时出错！');
                    }
                } else {
                    handleApiError(400, '文件过大！File too big!');
                }
            } else {
                handleApiError(400, '文件格式不允许或不是有效的图片文件！');
            }
        } else {
            handleApiError(400, '没有上传文件或上传过程中出现错误！');
        }

        $id = getUid($pdo,$email);
        $carbonSavings = 0;

        // 碳核算算法2.0
        switch ($activity) {
            case '购物时自带袋子 / Bring your own bag when shopping':
                $carbonSavings = $dataInput * 0.0190;
                break;
            case '早睡觉一小时 / Sleep an hour earlier':
                $carbonSavings = $dataInput * 0.0950;
                break;
            case '刷牙时关掉水龙头 / Turn off the tap while brushing teeth':
                $carbonSavings = $dataInput * 0.0090;
                break;
            case '出门自带水杯 / Bring your own water bottle':
                $carbonSavings = $dataInput * 0.0400;
                break;
            case '垃圾分类 / Sort waste properly':
                $carbonSavings = $dataInput * 0.0004;
                break;
            case '减少打印纸 / Reduce unnecessary printing paper':
                $carbonSavings = $dataInput * 0.0040;
                break;
            case '减少使用一次性餐盒 / Reduce disposable meal boxes':
                $carbonSavings = $dataInput * 0.1900;
                break;
            case '简易包装礼物 / Use minimal gift wrapping':
                $carbonSavings = $dataInput * 0.1400;
                break;
            case '夜跑 / Night running':
                $carbonSavings = $dataInput * 0.0950;
                break;
            case '自然风干湿发 / Air-dry wet hair':
                $carbonSavings = $dataInput * 0.1520;
                break;
            case '点外卖选择"无需餐具" / Choose No-Cutlery when ordering delivery':
                $carbonSavings = $dataInput * 0.0540;
                break;
            case '下班时关电脑和灯 / Turn off computer and lights when off-duty':
                $carbonSavings = $dataInput * 0.1660;
                break;
            case '晚上睡觉全程关灯 / Keep lights off at night':
                $carbonSavings = $dataInput * 0.1100;
                break;
            case '快速洗澡 / Take a quick shower':
                $carbonSavings = $dataInput * 0.1200;
                break;
            case '阳光晾晒衣服 / Sun-dry clothes':
                $carbonSavings = $dataInput * 0.3230;
                break;
            case '夏天空调调至26°C以上 / Set AC to above 78°F during Summer':
                $carbonSavings = $dataInput * 0.2190;
                break;
            case '攒够一桶衣服再洗 / Save and wash a full load of clothes':
                $carbonSavings = $dataInput * 0.4730;
                break;
            case '化妆品用完购买替代装 / Buy refillable cosmetics or toiletries':
                $carbonSavings = $dataInput * 0.0850;
                break;
            case '购买本地应季水果 / Buy local seasonal fruits':
                $carbonSavings = $dataInput * 2.9800;
                break;
            case '自己做饭 / Cook at home':
                $carbonSavings = $dataInput * 0.1900;
                break; 
            case '吃一顿轻食 / Have a light meal':
                $carbonSavings = $dataInput * 0.3600;
                break;
            case '吃完水果蔬菜 / Finish all fruits and vegetables':
                $carbonSavings = $dataInput * 0.0163;
                break;
            case '光盘行动 / Finish all food on the plate':
                $carbonSavings = $dataInput * 0.0163;
                break;
            case '喝燕麦奶或植物基食品 / Drink oat milk or plant-based food':
                $carbonSavings = $dataInput * 0.6430;
                break;
            case '公交地铁通勤 / Use public transport':
                $carbonSavings = $dataInput * 0.1005;
                break;
            case '骑行探索城市 / Explore the city by bike':
                $carbonSavings = $dataInput * 0.1490;
                break;
            case '种一棵树 / Plant a tree':
                $carbonSavings = $dataInput * 10.0000;
                break;
            case '购买二手书 / Buy a second-hand book':
                $carbonSavings = $dataInput * 2.8800;
                break;
            case '乘坐快轨去机场 / Take high-speed rail to the airport':
                $carbonSavings = $dataInput * 3.8700;
                break;
            case '拼车 / Carpool':
                $carbonSavings = $dataInput * 0.0450;
                break;
            case '自行车出行 / Travel by bike':
                $carbonSavings = $dataInput * 0.1490;
                break;
            case '旅行时自备洗漱用品 / Bring your own toiletries when traveling':
                $carbonSavings = $dataInput * 0.0470;
                break;
            case '旧物改造 / Repurpose old items':
                $carbonSavings = $dataInput * 0.7700;
                break;
            case '购买一级能效家电 / Buy an energy-efficient appliance':
                $carbonSavings = $dataInput * 2.1500;
                break;
            case '购买白色或浅色衣物 / Buy white or light-colored clothes':
                $carbonSavings = $dataInput * 3.4300;
                break;
            case '花一天享受户外 / Spend a full day outdoors':
                $carbonSavings = $dataInput * 0.7570;
                break;  
            case '自己种菜并吃 / Grow and eat your own vegetables':
                $carbonSavings = $dataInput * 0.0250;
                break;
            case '减少使用手机时间 / Reduce screen time':
                $carbonSavings = $dataInput * 0.0003;
                break;  
            default:
                handleApiError(400, '未知的活动类型 Unknown activity type');
        }

        $pdo->beginTransaction();

        $updateSql = "UPDATE users SET points = points + :points WHERE email = :email";
        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->bindParam(':points', $carbonSavings, PDO::PARAM_STR);
        $updateStmt->bindParam(':email', $email, PDO::PARAM_STR);
        $updateStmt->execute();

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
        $insertStmt->bindValue(':type', 'ord');
        $insertStmt->bindParam(':notes', $notes, PDO::PARAM_STR);
        $insertStmt->bindParam(':activity_date', $activityDate, PDO::PARAM_STR);
        $insertStmt->bindParam(':uid', $id, PDO::PARAM_STR);
        $insertStmt->execute();

        $pdo->commit();
 
        echo json_encode(['success' => true, 'message' => '碳减排积分记录已加入审核队列。', 'points' => $carbonSavings]);
    } catch (PDOException $e) {
        if (isset($pdo)) {
            $pdo->rollBack();
        }
        logException($e);
    } catch (Exception $e) {
        logException($e);
    }
} else {
    handleApiError(405, 'Invalid request method');
}
?>
