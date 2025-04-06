<?php
// Include global handlers and variables
require_once 'global_variables.php'; // Includes db.php indirectly
require_once 'global_error_handler.php';

header('Content-Type: application/json; charset=UTF-8');

// Define CSV file path
$csvFile = 'carbon_factors.csv';

// Wrap logic in try block
try {
    // Check file existence
    if (!file_exists($csvFile)) {
        // Use handleApiError for client-side (or configuration) errors
        handleApiError(404, 'CSV 文件不存在'); 
    }
    
    // Attempt to open the file
    $file = @fopen($csvFile, 'r'); // Use @ to suppress default warning on failure
    if ($file === false) {
        // Throw an exception if fopen fails, to be caught below
        throw new Exception("无法打开 CSV 文件: {$csvFile}");
    }

    $updated = 0;
    $inserted = 0;
    $lineNum = 0;

    // Ensure $pdo is available
    global $pdo;
    if (!$pdo) {
         handleApiError(500, 'Database connection is not available.');
    }
    
    // Start transaction
    $pdo->beginTransaction();

    while (($data = fgetcsv($file)) !== FALSE) {
        $lineNum++;
        // Basic validation for row data count
        if (count($data) < 4) {
            error_log("[update_factors.php] Skipping invalid row #{$lineNum}: Not enough columns.");
            continue; // Skip invalid row
        }

        // Trim and sanitize data
        $activity = trim($data[0]);
        $unit = trim($data[1]);
        $reduction_factor = isset($data[2]) ? floatval($data[2]) : 0.0;
        $bonus_points = isset($data[3]) ? intval($data[3]) : 0;

        // Basic validation for activity name
        if (empty($activity)) {
             error_log("[update_factors.php] Skipping invalid row #{$lineNum}: Activity name is empty.");
             continue;
        }

        // Check if activity exists
        $stmtCheck = $pdo->prepare("SELECT id FROM carbon_factors WHERE activity = :activity");
        $stmtCheck->execute(['activity' => $activity]);
        $existingId = $stmtCheck->fetchColumn(); // Use fetchColumn

        if ($existingId !== false) {
            // Update existing data
            $stmtUpdate = $pdo->prepare("UPDATE carbon_factors SET unit = :unit, reduction_factor = :reduction_factor, bonus_points = :bonus_points WHERE id = :id");
            $stmtUpdate->execute([
                'unit' => $unit,
                'reduction_factor' => $reduction_factor,
                'bonus_points' => $bonus_points,
                'id' => $existingId // Use ID for update
            ]);
            $updated++;
        } else {
            // Insert new data
            $stmtInsert = $pdo->prepare("INSERT INTO carbon_factors (activity, unit, reduction_factor, bonus_points) VALUES (:activity, :unit, :reduction_factor, :bonus_points)");
            $stmtInsert->execute([
                'activity' => $activity,
                'unit' => $unit,
                'reduction_factor' => $reduction_factor,
                'bonus_points' => $bonus_points
            ]);
            $inserted++;
        }
    }

    // Commit transaction
    $pdo->commit();

    // Close the file handle
    fclose($file);

    // Return success JSON response
    echo json_encode([
        'success' => true,
        'updated' => $updated,
        'inserted' => $inserted,
        'message' => "数据更新完成 ({$updated} updated, {$inserted} inserted)"
    ]);

} catch (PDOException $e) {
    // Rollback transaction on PDO error
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if (isset($file) && $file !== false) { fclose($file); } // Ensure file is closed on error
    logException($e); // Log and exit via global handler

} catch (Exception $e) {
    // Rollback transaction on general error if applicable
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
     if (isset($file) && $file !== false) { fclose($file); } // Ensure file is closed on error
    logException($e); // Log and exit via global handler
}

?>
