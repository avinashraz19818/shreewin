<?php
include '../conn.php'; // Include your database connection file

header('Content-Type: application/json');

$ownCode = $_GET['ownCode'] ?? '';
$date = $_GET['date'] ?? date('Y-m-d');

$Query = mysqli_query($conn, "SELECT 
                (SELECT SUM(t.motta) FROM thevani t 
                 JOIN shonu_subjects ss ON t.balakedara = ss.id 
                 WHERE ss.code = '$ownCode' 
                 AND DATE(t.dinankavannuracisi) = '$date' 
                 AND t.sthiti = 1) AS level1_total,
            
                (SELECT SUM(t.motta) FROM thevani t 
                 JOIN shonu_subjects ss ON t.balakedara = ss.id 
                 WHERE ss.code1 = '$ownCode' 
                 AND DATE(t.dinankavannuracisi) = '$date' 
                 AND t.sthiti = 1) AS level2_total,
            
                (SELECT SUM(t.motta) FROM thevani t 
                 JOIN shonu_subjects ss ON t.balakedara = ss.id 
                 WHERE ss.code2 = '$ownCode' 
                 AND DATE(t.dinankavannuracisi) = '$date' 
                 AND t.sthiti = 1) AS level3_total,
            
                (SELECT SUM(t.motta) FROM thevani t 
                 JOIN shonu_subjects ss ON t.balakedara = ss.id 
                 WHERE ss.code3 = '$ownCode' 
                 AND DATE(t.dinankavannuracisi) = '$date' 
                 AND t.sthiti = 1) AS level4_total,
            
                (SELECT SUM(t.motta) FROM thevani t 
                 JOIN shonu_subjects ss ON t.balakedara = ss.id 
                 WHERE ss.code4 = '$ownCode' 
                 AND DATE(t.dinankavannuracisi) = '$date' 
                 AND t.sthiti = 1) AS level5_total,
            
                (SELECT SUM(t.motta) FROM thevani t 
                 JOIN shonu_subjects ss ON t.balakedara = ss.id 
                 WHERE ss.code5 = '$ownCode' 
                 AND DATE(t.dinankavannuracisi) = '$date' 
                 AND t.sthiti = 1) AS level6_total;");

$row = mysqli_fetch_assoc($Query);
echo json_encode($row);
?>
