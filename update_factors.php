<?php
// 引入数据库连接
require_once 'db.php';

// 设定上传的 CSV 文件路径
$csvFile = 'carbon_factors.csv';

// 检查文件是否存在
if (!file_exists($csvFile)) {
    die(json_encode(['success' => false, 'message' => 'CSV 文件不存在']));
}

// 读取 CSV 文件
$file = fopen($csvFile, 'r');
$updated = 0;
$inserted = 0;

while (($data = fgetcsv($file)) !== FALSE) {
    if (count($data) < 4) {
        continue; // 忽略无效行
    }

    $activity = trim($data[0]);  // 行为名称
    $unit = trim($data[1]);      // 计算单位
    $reduction_factor = floatval($data[2]); // 碳减排因子
    $bonus_points = intval($data[3]); // 额外积分

    // 检查行为是否已存在
    $stmt = $pdo->prepare("SELECT id FROM carbon_factors WHERE activity = :activity");
    $stmt->execute(['activity' => $activity]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        // 更新已存在的数据
        $stmt = $pdo->prepare("UPDATE carbon_factors SET unit = :unit, reduction_factor = :reduction_factor, bonus_points = :bonus_points WHERE activity = :activity");
        $stmt->execute([
            'unit' => $unit,
            'reduction_factor' => $reduction_factor,
            'bonus_points' => $bonus_points,
            'activity' => $activity
        ]);
        $updated++;
    } else {
        // 插入新数据
        $stmt = $pdo->prepare("INSERT INTO carbon_factors (activity, unit, reduction_factor, bonus_points) VALUES (:activity, :unit, :reduction_factor, :bonus_points)");
        $stmt->execute([
            'activity' => $activity,
            'unit' => $unit,
            'reduction_factor' => $reduction_factor,
            'bonus_points' => $bonus_points
        ]);
        $inserted++;
    }
}

fclose($file);

// 返回 JSON 响应
echo json_encode([
    'success' => true,
    'updated' => $updated,
    'inserted' => $inserted,
    'message' => '数据更新完成'
]);
?>
