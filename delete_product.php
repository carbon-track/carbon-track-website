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
            exit;
        }

        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        if ($product_id <= 0) {
            handleApiError(422, 'Invalid product ID.');
            exit;
        }

        $sql = "DELETE FROM products WHERE product_id = :product_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Product deleted successfully.']);
        } else {
            handleApiError(500, 'Failed to delete product.');
        }
    } catch (PDOException $e) {
        logException($e);
        handleApiError(500, 'Database error.');
    } catch (Exception $e) {
        logException($e);
        handleApiError(500, 'Internal server error.');
    }
} else {
    handleApiError(405, '114514');
}
?>
