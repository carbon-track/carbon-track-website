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
            handleApiError(401, 'Token不合法。');
        }

        if (!isAdmin($email)) {
            handleApiError(401, 'Unauthorized');
        }

        // Pagination parameters
        $page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
        $limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 10; // Default limit 10 records per page
        if ($page < 1) $page = 1;
        if ($limit < 1) $limit = 10;
        $offset = ($page - 1) * $limit;

        // Search parameter
        $searchTerm = isset($_POST['searchTerm']) ? sanitizeInput($_POST['searchTerm']) : null;
        $searchSql = '';
        $params = [];

        if (!empty($searchTerm)) {
            $searchSql = " AND (uid LIKE :searchTerm OR email LIKE :searchTerm)";
            $params[':searchTerm'] = '%' . $searchTerm . '%';
        }

        $pdo = new PDO($dsn, $user, $pass, $options);

        // Count total matching records (with search filter)
        $countSql = "SELECT COUNT(*) FROM points_transactions WHERE auth = 'non'" . $searchSql;
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params); // Pass search params if they exist
        $totalRecords = $countStmt->fetchColumn();

        // Fetch records for the current page (with search filter)
        $dataSql = "SELECT id, email, time, img, points, auth, act, raw, type, uid, notes, activity_date 
                    FROM points_transactions 
                    WHERE auth = 'non'" . $searchSql . 
                   " ORDER BY time DESC 
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
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true, 
            'data' => $results, 
            'total' => (int)$totalRecords, 
            'page' => $page, 
            'limit' => $limit,
            'searchTerm' => $searchTerm // Optionally return the search term for context
        ]);

    }  catch (PDOException $e) {
        logException($e);
        handleApiError(500, 'Database error: ' . $e->getMessage()); // Include specific error in log/dev env
    } catch (Exception $e) {
        logException($e);
        handleApiError(500, 'Internal server error: ' . $e->getMessage()); // Include specific error in log/dev env
    }
} else {
    handleApiError(405, '不支持的请求方法。');
}
?>
