<?php
require_once 'global_variables.php';
require_once 'global_error_handler.php';
require 'db.php';

header('Content-Type: application/json');
date_default_timezone_set('Asia/Shanghai');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
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
                    handleApiError(400, '文件过大！');
                }
            } else {
                handleApiError(400, '文件格式不允许或不是有效的图片文件！');
            }
        } else {
            handleApiError(400, '没有上传文件或上传过程中出现错误！');
        }

        $id = getUid($pdo,$email);
        $carbonSavings = 0;

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
                break;
            case '光盘行动1次 / Finish everything on your plate 1 time':
                $carbonSavings = $dataInput * 0.0329041095890411;
                break;
            case '居家回收利用':
                $carbonSavings = $dataInput * 10 + 4;
                break;
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
                handleApiError(400, '未知的活动类型。');
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
        handleApiError(500, 'Database error');
    } catch (Exception $e) {
        logException($e);
        handleApiError(500, 'Internal server error');
    }
} else {
    handleApiError(400, 'Invalid request method');
}
?>
