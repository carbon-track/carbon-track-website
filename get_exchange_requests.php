<?php
// get_exchange_requests.php
require_once 'global_variables.php';
require_once 'global_error_handler.php';
require 'db.php'; // Contains $pdo initialization

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    handleApiError(405, 'Method Not Allowed.');
}

try {
    // --- Authentication ---
    if (!isset($_POST['token'])) {
        handleApiError(400, 'Admin token is required.');
    }
    $token = sanitizeInput($_POST['token']);
    $adminEmail = opensslDecrypt($token);
    if (!isAdmin($adminEmail)) { // Assumes isAdmin() function exists and checks against your admin list/logic
        handleApiError(403, 'Unauthorized access. Admin privileges required.');
    }
    // Optionally get admin ID if needed later
    // $adminId = getUid($pdo, $adminEmail); 

    // --- Input Validation & Pagination ---
    $page = isset($_POST['page']) ? filter_input(INPUT_POST, 'page', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : 1;
    $limit = isset($_POST['limit']) ? filter_input(INPUT_POST, 'limit', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 50]]) : 10; // Max 50 per page
    $statusFilter = isset($_POST['status']) ? sanitizeInput($_POST['status']) : 'pending'; // Default to pending
    
    if ($page === false || $limit === false) {
        handleApiError(400, 'Invalid pagination parameters.');
    }
    
    $validStatuses = ['pending', 'approved', 'rejected', 'all'];
    if (!in_array($statusFilter, $validStatuses)) {
         handleApiError(400, 'Invalid status filter value.');
    }

    $offset = ($page - 1) * $limit;

    // --- Database Query ---
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Base query with joins
    $sqlBase = "FROM exchange_requests er 
                LEFT JOIN products p ON er.product_id = p.product_id";
    $whereClause = "";
    $params = [];

    if ($statusFilter !== 'all') {
        $whereClause = " WHERE er.status = :status";
        $params[':status'] = $statusFilter;
    }
    
    // Count total records matching the filter
    $sqlCount = "SELECT COUNT(*) " . $sqlBase . $whereClause;
    $stmtCount = $pdo->prepare($sqlCount);
    $stmtCount->execute($params);
    $totalRecords = $stmtCount->fetchColumn();

    // Fetch records for the current page
    $sqlFetch = "SELECT er.*, p.name as product_name " . $sqlBase . $whereClause . " ORDER BY er.request_time DESC LIMIT :limit OFFSET :offset";
    $stmtFetch = $pdo->prepare($sqlFetch);
    
    // Bind status param if applicable
    if ($statusFilter !== 'all') {
        $stmtFetch->bindParam(':status', $statusFilter, PDO::PARAM_STR);
    }
    $stmtFetch->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmtFetch->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmtFetch->execute();
    $requests = $stmtFetch->fetchAll(PDO::FETCH_ASSOC);

    // --- Response ---
    echo json_encode([
        'success' => true,
        'data' => $requests,
        'total' => (int)$totalRecords,
        'page' => $page,
        'limit' => $limit,
        'status' => $statusFilter // Return the filter used
    ]);

} catch (PDOException $e) {
    logException($e, "Database error fetching exchange requests.");
    handleApiError(500, 'Database error processing request.');
} catch (Exception $e) {
    logException($e, "General error fetching exchange requests.");
    handleApiError(500, 'An internal error occurred.');
}
?>
