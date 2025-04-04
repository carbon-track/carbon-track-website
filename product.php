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
        handleApiError(500, 'Database error: ' . $e->getMessage());
    } catch (Exception $e) {
        logException($e);
        handleApiError(500, 'Internal server error: ' . $e->getMessage());
    }
} else {
    // Optionally handle POST or other methods if needed for other actions in the future
    handleApiError(405, 'Invalid request method. Use GET for listing products.');
}
?>
