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
            handleApiError(403, 'Unauthorized access.');
        }

        $product_id = isset($_POST['product_id']) ? filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT) : null;
        if ($product_id === null || $product_id === false || $product_id <= 0) {
            handleApiError(400, 'Invalid product ID.');
        }

        $sql = "DELETE FROM products WHERE product_id = :product_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Product deleted successfully.']);
            } else {
                handleApiError(404, 'Product not found or already deleted.');
            }
        } else {
            throw new Exception('Failed to execute delete statement for product ID: ' . $product_id);
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
