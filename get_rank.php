<?php
include 'db.php'; // 引入数据库连接

function opensslDecrypt($data, $key)
{
    $data = base64_decode($data);
    $ivLength = openssl_cipher_iv_length('aes-256-cbc');
    $iv = substr($data, 0, $ivLength);
    $encrypted = substr($data, $ivLength);
    return openssl_decrypt($encrypted, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
}

$token = $_POST['token'] ?? '';
$base64Key = "28lVmS8LHIZIQdAmT6jyHal29N8g6aRZrHEA2mv/q/4=";
$key = base64_decode($base64Key);
$email = opensslDecrypt($token, $key);

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token不合法']);
    exit; // 停止脚本执行
}

// 获取请求参数
$type = $_POST['type'] ?? 'all';
$location = $_POST['location'] ?? '';
$school = $_POST['school'] ?? '';

// 准备SQL语句
$sql = "SELECT username, points FROM users";
$params = [];

if ($type === 'local' && !empty($location)) {
    $sql .= " WHERE location = :location";
    $params[':location'] = $location;
} elseif ($type === 'school' && !empty($school)) {
    $sql .= " WHERE school = :school";
    $params[':school'] = $school;
}

$sql .= " ORDER BY points DESC LIMIT 10"; // 限制结果为前10名

try {
    // 准备预处理语句
    $stmt = $pdo->prepare($sql);

    // 绑定参数并执行
    foreach ($params as $key => &$val) {
        $stmt->bindParam($key, $val);
    }
    $stmt->execute();

    // 获取结果
    $results = $stmt->fetchAll();

    // 返回JSON格式的结果
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'ranking' => $results]);
} catch (PDOException $e) {
    // 捕获异常并返回错误信息
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => '数据库错误: ' . $e->getMessage()]);
    exit;
}
