<?php
require 'db.php'; // 确保已经包含了数据库连接

try {
    $stmt = $pdo->query("SELECT SUM(points) AS total_reduction FROM points_transactions WHERE auth ='yes'");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $num_stmt = $pdo->query("SELECT COUNT(*) AS total_times FROM points_transactions");
    $num_row = $num_stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode([
        'success' => true,
        'points' => number_format((float)$row['total_reduction'], 2, '.', ''),
        'times' => $num_row['total_times']
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
