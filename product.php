<?php
require 'db.php'; // 包含数据库连接配置

header('Content-Type: application/json'); // 设置响应头

try {
    // 连接到MySQL数据库
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 准备SQL查询
    $stmt = $pdo->prepare("SELECT name, product_id, description, points_required, image_path, stock FROM products WHERE stock > 0");
    $stmt->execute(); // 执行查询

    $products = $stmt->fetchAll(PDO::FETCH_ASSOC); // 获取所有商品信息

    // 输出JSON响应
    echo json_encode([
        'success' => true,
        'products' => $products
    ]);
} catch (PDOException $e) {
    // 如果有错误，输出错误信息
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
