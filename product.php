<?php
require_once 'global_variables.php';
require_once 'global_error_handler.php';
require 'db.php';

header('Content-Type: application/json');

// Handle GET requests for product listing
if ($_SERVER['REQUEST_METHOD'] == 'GET') { 
    try {
        // Pagination parameters
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 12; 
        if ($page < 1) $page = 1;
        if ($limit < 1) $limit = 12;
        $offset = ($page - 1) * $limit;

        // Search parameter
        $searchTerm = isset($_GET['searchTerm']) ? sanitizeInput($_GET['searchTerm']) : null;
        $searchSql = '';
        $params = [];

        if (!empty($searchTerm)) {
            $searchSql = " WHERE name LIKE :searchTerm"; // Use WHERE since it's the first condition
            $params[':searchTerm'] = '%' . $searchTerm . '%';
        } else {
             $searchSql = ""; // Ensure it's empty if no search term
        }

        $pdo = new PDO($dsn, $user, $pass, $options);

        // Count total matching products (with search filter)
        $countSql = "SELECT COUNT(*) FROM products" . $searchSql;
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params); // Pass search params if they exist
        $totalRecords = $countStmt->fetchColumn();

        // Fetch products for the current page (with search filter)
        $dataSql = "SELECT name, product_id, description, points_required, image_path, stock 
                    FROM products" . $searchSql . 
                   " ORDER BY product_id DESC 
                    LIMIT :limit OFFSET :offset";
        $stmt = $pdo->prepare($dataSql);

        // Bind search term param if it exists
        if (!empty($searchTerm)) {
            $stmt->bindParam(':searchTerm', $params[':searchTerm'], PDO::PARAM_STR);
        }
        // Bind pagination params
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'products' => $products,
            'total' => (int)$totalRecords,
            'page' => $page,
            'limit' => $limit,
            'searchTerm' => $searchTerm // Return search term
        ]);

    } catch (PDOException $e) {
        logException($e);
    } catch (Exception $e) {
        logException($e);
    }
} else {
    // --- Handle POST requests for product listing with token validation ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Get the token from POST data
        $token = $_POST['token'] ?? null;

        if (!$token) {
            handleApiError(400, 'Token is required.');
        }

        // Validate the token using opensslDecrypt
        $decryptedData = opensslDecrypt($token);
        if ($decryptedData === false) { // Check if decryption failed
            handleApiError(401, 'Invalid or expired token.');
        }

        // If token is valid, fetch all products
        try {
            $pdo = new PDO($dsn, $user, $pass, $options);

            // Fetch all products (using correct column names)
            $stmt = $pdo->query("SELECT product_id as id, name, description, points_required as price, image_path as image_url, stock FROM products ORDER BY product_id DESC");
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'products' => $products
            ]);

        } catch (PDOException $e) {
            logException($e);
        } catch (Exception $e) {
            logException($e);
        }

    } else {
        // Reject other methods
        handleApiError(405, 'Invalid request method. Use GET for listing products or POST with a valid token to list products.');
    }
}
?>
