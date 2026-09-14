<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization");
include 'conn.php';
// Get the raw POST data
$json = file_get_contents("php://input");
$data = json_decode($json, true);

// Validate input
if (!isset($data['data'][0]) || !is_array($data['data'][0])) {
    echo json_encode(["error" => "Invalid or missing headers"]);
    exit;
}

$headers = array_map(fn($h) => strtolower(trim((string) $h)), $data['data'][0]);

// Required columns (lowercased)
$requiredColumns = ["uid", "bonus", "remark", "turnover"];

// Check if all required columns exist
$missingColumns = array_diff($requiredColumns, $headers);
if (!empty($missingColumns)) {
    echo json_encode(["error" => "Missing columns: " . implode(", ", $missingColumns)]);
    exit;
}

// Get column indexes dynamically
$columnIndexes = array_flip($headers);

// Extract data rows (excluding headers)
$excelData = array_slice($data['data'], 1);

// SQL Queries for Batch Insert & Single Update
$insertValues = [];
$mottaUpdateData = [];
$turnoverUpdateData = [];

$skippedRows = 0; // Counter for skipped rows

// Process rows
foreach ($excelData as $index => $row) {
    // Check if UID and Bonus are missing, then skip the row
    $uid = isset($row[$columnIndexes["uid"]]) ? intval(trim($row[$columnIndexes["uid"]])) : null;
    $bonus = isset($row[$columnIndexes["bonus"]]) ? intval(trim($row[$columnIndexes["bonus"]])) : null;

    if ($uid === null || $bonus === null) {
        $skippedRows++;
        continue; // Skip this row
    }

    $remark = isset($row[$columnIndexes["remark"]]) ? trim($row[$columnIndexes["remark"]]) : '';
    $turnover = isset($row[$columnIndexes["turnover"]]) ? intval(trim($row[$columnIndexes["turnover"]])) : 0;

    // Insert into `bonus_recharge_table` with balance calculation
    $insertValues[] = "($uid, $bonus, '".date("Y-m-d H:i:s")."', '$remark', 
                        (SELECT motta FROM shonu_kaichila WHERE balakedara = $uid) + $bonus)";

    // Collect sum for `motta` update
    $mottaUpdateData[$uid] = isset($mottaUpdateData[$uid]) ? $mottaUpdateData[$uid] + $bonus : $bonus;

    // Collect sum for `turnover` update (only if turnover = 1)
    if ($turnover === 1) {
        $turnoverUpdateData[$uid] = isset($turnoverUpdateData[$uid]) ? $turnoverUpdateData[$uid] + $bonus : $bonus;
    }
}

// Execute Bulk Insert into `bonus_recharge_table`
if (!empty($insertValues)) {
    $insertSQL = "INSERT INTO bonus_recharge_table (userkani, price, shonu, remark, balance) VALUES " . implode(", ", $insertValues);
    $conn->query($insertSQL);
}

// Execute Single Query to Update `motta`
if (!empty($mottaUpdateData)) {
    $updateSQL = "UPDATE shonu_kaichila SET motta = CASE";
    
    foreach ($mottaUpdateData as $uid => $bonus) {
        $updateSQL .= " WHEN balakedara = $uid THEN motta + $bonus";
    }
    
    $updateSQL .= " END WHERE balakedara IN (" . implode(",", array_keys($mottaUpdateData)) . ")";
    
    $conn->query($updateSQL);
}

// Execute Single Query to Update `turnover` (only where turnover = 1)
if (!empty($turnoverUpdateData)) {
    $turnoverSQL = "UPDATE shonu_kaichila SET turnover = CASE";

    foreach ($turnoverUpdateData as $uid => $bonus) {
        $turnoverSQL .= " WHEN balakedara = $uid THEN turnover + $bonus";
    }

    $turnoverSQL .= " END WHERE balakedara IN (" . implode(",", array_keys($turnoverUpdateData)) . ")";

    $conn->query($turnoverSQL);
}

echo json_encode([
    "success" => true,
    "message" => "Data updated successfully",
    "skipped_rows" => $skippedRows, // Return how many rows were skipped
]);

?>
