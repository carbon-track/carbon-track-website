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
            handleApiError(401, 'Unauthorized');
        }

        $product_id = sanitizeInput($_POST['product_id']);
        $fieldsToUpdate = [];
        $params = [];

        // 检查每个字段是否存在，如果存在，则添加到更新列表
        if (isset($_POST['name'])) {
            $fieldsToUpdate[] = "name = :name";
            $params[':name'] = sanitizeInput($_POST['name']);
        }
        if (isset($_POST['description'])) {
            $fieldsToUpdate[] = "description = :description";
            $params[':description'] = sanitizeInput($_POST['description']);
        }
        if (isset($_POST['points_required'])) {
            $fieldsToUpdate[] = "points_required = :points_required";
            $params[':points_required'] = sanitizeInput($_POST['points_required']);
        }
        if (isset($_POST['stock'])) {
            $fieldsToUpdate[] = "stock = :stock";
            $params[':stock'] = sanitizeInput($_POST['stock']);
        }
         if (isset($_POST['image_path'])) {
            $fieldsToUpdate[] = "image_path = :image_path";
            $params[':image_path'] = sanitizeInput($_POST['image_path']);
        }

        // 如果没有提供要更新的字段，返回错误
        if (empty($fieldsToUpdate)) {
            handleApiError(400, 'No fields provided for update');
        }

        // 构建SQL语句
        $sql = "UPDATE products SET " . implode(', ', $fieldsToUpdate) . " WHERE product_id = :product_id";
        $params[':product_id'] = $product_id;

        // 执行更新操作
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute($params)) {
             // Check if any row was actually updated
            if ($stmt->rowCount() > 0) {
                 echo json_encode(['success' => true, 'message' => 'Product updated successfully.']);
            } else {
                 // Product ID might not exist, or data was the same
                 handleApiError(404, 'Product not found or data unchanged.'); 
            }
        } else {
            // If execute fails, it should throw a PDOException
             throw new Exception('Failed to execute update statement for product ID: ' . $product_id);
        }

    } catch (PDOException $e) {
        // logException handles logging, response, and exiting
        logException($e);
    } catch (Exception $e) {
        // logException handles logging, response, and exiting
        logException($e);
    }
} else {
     // Added missing 405 handler
     handleApiError(405, 'Method not allowed.');
}
?>
