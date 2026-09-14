<?php
include '../conn.php'; // Include your database connection file

header('Content-Type: application/json');

$ownCode = $_GET['ownCode'] ?? '';
$date = $_GET['date'] ?? date('Y-m-d');

$Query = mysqli_query($conn, "SELECT 
                SUM(CASE WHEN code = '$ownCode' THEN 1 ELSE 0 END) AS level1_count,
                SUM(CASE WHEN code1 = '$ownCode' THEN 1 ELSE 0 END) AS level2_count,
                SUM(CASE WHEN code2 = '$ownCode' THEN 1 ELSE 0 END) AS level3_count,
                SUM(CASE WHEN code3 = '$ownCode' THEN 1 ELSE 0 END) AS level4_count,
                SUM(CASE WHEN code4 = '$ownCode' THEN 1 ELSE 0 END) AS level5_count,
                SUM(CASE WHEN code5 = '$ownCode' THEN 1 ELSE 0 END) AS level6_count
            FROM shonu_subjects
            WHERE DATE(createdate) = '$date';");

$row = mysqli_fetch_assoc($Query);
echo json_encode($row);
?>
