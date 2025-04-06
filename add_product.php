<?php
require_once 'global_variables.php';
require_once 'global_error_handler.php';
require 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $token = sanitizeInput($_POST['token']);
        $email = opensslDecrypt($token);
        if (!isAdmin($email)) {
            handleApiError(401, 'Unauthorized access.');
        }

        $name = isset($_POST['name']) ? sanitizeInput($_POST['name']) : '';
        $description = isset($_POST['description']) ? sanitizeInput($_POST['description']) : '';
        $points_required = isset($_POST['points_required']) ? intval($_POST['points_required']) : 0;
         $image_path = isset($_POST['image_path']) ? sanitizeInput($_POST['image_path']) : '';
        $stock = isset($_POST['stock']) ? intval($_POST['stock']) : 0;

        $sql = "INSERT INTO products (name, description, points_required, image_path, stock) VALUES (:name, :description, :points_required, :image_path, :stock)";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
        $stmt->bindParam(':description', $description, PDO::PARAM_STR);
        $stmt->bindParam(':points_required', $points_required, PDO::PARAM_INT);
         $stmt->bindParam(':image_path', $image_path, PDO::PARAM_STR);
        $stmt->bindParam(':stock', $stock, PDO::PARAM_INT);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Product added successfully.']);
        } else {
            throw new Exception('Failed to add product for an unknown reason.');
        }
    } catch (PDOException $e) {
        logException($e);
    } catch (Exception $e) {
        logException($e);
    }
} else {
    handleApiError(405, '114514');
}
?>
