<?php
require 'db.php'; // 确保已经包含了数据库连接

try {
    $stmt = $pdo->query("SELECT COUNT(*) AS user_count FROM users");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'count' => $row['user_count']]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
