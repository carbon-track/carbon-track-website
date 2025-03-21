<?php
// 引入 db.php 进行数据库连接
require_once 'db.php';

// 获取用户提交的数据
$activity = $_POST['activity'] ?? '';
$quantity = floatval($_POST['oridata']) ?? 0;

// 从数据库获取碳减排行为的计算因子
$stmt = $pdo->prepare("SELECT reduction_factor, unit, bonus_points FROM carbon_factors WHERE activity = :activity");
$stmt->execute(['activity' => $activity]);
$factor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$factor) {
    die(json_encode(['success' => false, 'message' => '活动类型无效']));
}

$reduction_factor = $factor['reduction_factor'];
$bonus_points = $factor['bonus_points'];
$unit = $factor['unit'];

// 计算碳减排量和积分
$carbon_reduction = $quantity * $reduction_factor;
$points = round($carbon_reduction * 10 + $bonus_points);

// 记录用户行为
$stmt = $pdo->prepare("
    INSERT INTO carbon_activities (activity, quantity, carbon_reduction, points)
    VALUES (:activity, :quantity, :carbon_reduction, :points)
");
$stmt->execute([
    'activity' => $activity,
    'quantity' => $quantity,
    'carbon_reduction' => $carbon_reduction,
    'points' => $points
]);

// 返回 JSON 响应
echo json_encode([
    'success' => true,
    'carbon_reduction' => $carbon_reduction,
    'points' => $points,
    'message' => '记录成功'
]);
?>
