<?php
require_once 'global_variables.php';
require_once 'global_error_handler.php';
require 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        if (!isset($_POST['token'])) {
            handleApiError(400, 'Token is required');
        }

        $token = sanitizeInput($_POST['token']);
        $email = opensslDecrypt($token);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            handleApiError(401, 'Invalid token.');
        }

        $stmt = $pdo->prepare("SELECT name, product_id, description, points_required, image_path, stock FROM products WHERE stock > 0");
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'products' => $products
        ]);
    } catch (PDOException $e) {
        logException($e);
        handleApiError(500, 'Database error.');
    } catch (Exception $e) {
        logException($e);
        handleApiError(500, 'Internal server error.');
    }
} else {
    handleApiError(400, 'Invalid request method');
}
?>
