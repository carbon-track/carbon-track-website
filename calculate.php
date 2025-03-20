<?php
// 连接数据库
$dsn = 'mysql:host=localhost;dbname=carbontrack;charset=utf8mb4';
$db_user = 'your_username';
$db_pass = 'your_password';

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    die(json_encode(['success' => false, 'message' => '数据库连接失败Fail to connect to database']));
}

// 获取用户提交的数据
$activity = $_POST['activity'] ?? '';
$quantity = floatval($_POST['oridata']) ?? 0;
$token = $_POST['token'] ?? '';
$image = $_FILES['image'] ?? null;

// 认证用户
$stmt = $pdo->prepare("SELECT id, carbon_credits FROM users WHERE token = :token");
$stmt->execute(['token' => $token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die(json_encode(['success' => false, 'message' => '用户认证失败']));
}

$user_id = $user['id'];

// 获取活动的碳减排因子
$stmt = $pdo->prepare("SELECT reduction_factor, bonus_points FROM carbon_factors WHERE activity = :activity");
$stmt->execute(['activity' => $activity]);
$factor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$factor) {
    die(json_encode(['success' => false, 'message' => '活动类型无效']));
}

$reduction_factor = $factor['reduction_factor'];
$bonus_points = $factor['bonus_points'];

// 计算碳减排量和积分
$carbon_reduction = $quantity * $reduction_factor;
$points = round($carbon_reduction * 10 + $bonus_points);

// 处理文件上传
$image_path = null;
if ($image && $image['error'] === UPLOAD_ERR_OK) {
    $target_dir = "uploads/";
    $image_name = uniqid() . "_" . basename($image["name"]);
    $target_file = $target_dir . $image_name;
    move_uploaded_file($image["tmp_name"], $target_file);
    $image_path = $target_file;
}

// 记录用户行为
$stmt = $pdo->prepare("
    INSERT INTO carbon_activities (user_id, activity, quantity, carbon_reduction, points, image_path)
    VALUES (:user_id, :activity, :quantity, :carbon_reduction, :points, :image_path)
");
$stmt->execute([
    'user_id' => $user_id,
    'activity' => $activity,
    'quantity' => $quantity,
    'carbon_reduction' => $carbon_reduction,
    'points' => $points,
    'image_path' => $image_path
]);

// 更新用户碳积分
$stmt = $pdo->prepare("UPDATE users SET carbon_credits = carbon_credits + :points WHERE id = :user_id");
$stmt->execute(['points' => $points, 'user_id' => $user_id]);

// 返回 JSON 响应
echo json_encode([
    'success' => true,
    'carbon_reduction' => $carbon_reduction,
    'points' => $points,
    'message' => '记录成功'
]);
?>
