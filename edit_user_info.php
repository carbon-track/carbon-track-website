<?php
require 'db.php'; // 引入数据库连接
function opensslDecrypt($data, $key)
{
    $data = base64_decode($data);
    $ivLength = openssl_cipher_iv_length('aes-256-cbc');
    $iv = substr($data, 0, $ivLength);
    $encrypted = substr($data, $ivLength);
    return openssl_decrypt($encrypted, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
}

// 初始化响应数组
$response = ["success" => false];

// 假设已通过某种方式验证用户身份，并获取了用户ID
// $userId = ...;
$token = $_POST['token'];
$base64Key = "28lVmS8LHIZIQdAmT6jyHal29N8g6aRZrHEA2mv/q/4=";
$key = base64_decode($base64Key);
$email = opensslDecrypt($token, $key);

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Token不合法。']);
    exit; // 停止脚本执行
}
// 初始化更新字段数组
$updateFields = [];
$params = [];

// 检查是否有学校信息传入
if (isset($_POST['school']) && !empty(trim($_POST['school']))) {
    if ($_POST['school'] != '请选择学校Select school') {
        $updateFields[] = "school = :school";
        $params[':school'] = $_POST['school'];

        // 检查是否指定为新学校
        if (isset($_POST['new_sch']) && $_POST['new_sch'] === 'true') {
            // 检查学校是否已存在
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM schools WHERE name = :school");
            $stmt->execute([':school' => $_POST['school']]);
            $count = $stmt->fetchColumn();

            // 如果学校不存在，则添加
            if ($count == 0) {
                $stmt = $pdo->prepare("INSERT INTO schools (name) VALUES (:school)");
                $stmt->execute([':school' => $_POST['school']]);
            }
        }
    }
}

// 检查是否有位置信息传入
if (isset($_POST['state']) && !empty(trim($_POST['state']))) {
    $updateFields[] = "location = :location";
    $params[':location'] = $_POST['state'];
}

// 如果没有任何更新字段，则退出
if (empty($updateFields)) {
    $response["message"] = "没有提供更新信息";
    echo json_encode($response);
    exit;
}

// 构建SQL语句
$sql = "UPDATE users SET " . join(', ', $updateFields) . " WHERE email = :email";
$params[':email'] = $email;

// 准备语句
$stmt = $pdo->prepare($sql);

// 执行语句
try {
    $stmt->execute($params);
    $response["success"] = true;
} catch (\PDOException $e) {
    // 错误处理
    $response["message"] = "更新失败: " . $e->getMessage();
}

// 返回JSON格式的响应
echo json_encode($response);
?>
