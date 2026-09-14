<?php
include '../conn.php'; // Include your database connection file

header('Content-Type: application/json');

$ownCode = $_GET['ownCode'] ?? '';
$date = $_GET['date'] ?? date('Y-m-d');

$Query = mysqli_query($conn, "WITH FirstRecharges AS (
            -- Get the first recharge for each user
            SELECT t.balakedara AS user_id, 
                   MIN(t.dinankavannuracisi) AS first_recharge_date,
                   (SELECT motta FROM thevani t2 
                    WHERE t2.balakedara = t.balakedara 
                    AND t2.sthiti = 1 
                    ORDER BY t2.dinankavannuracisi ASC 
                    LIMIT 1) AS first_recharge_amount
            FROM thevani t
            WHERE t.sthiti = 1  -- Only approved deposits
            GROUP BY t.balakedara
        )
        SELECT 
            (SELECT SUM(fr.first_recharge_amount) FROM FirstRecharges fr 
             JOIN shonu_subjects ss ON fr.user_id = ss.id 
             WHERE ss.code = '$ownCode' AND DATE(fr.first_recharge_date) = '$date') AS level1_total,
            
            (SELECT SUM(fr.first_recharge_amount) FROM FirstRecharges fr 
             JOIN shonu_subjects ss ON fr.user_id = ss.id 
             WHERE ss.code1 = '$ownCode' AND DATE(fr.first_recharge_date) = '$date') AS level2_total,
            
            (SELECT SUM(fr.first_recharge_amount) FROM FirstRecharges fr 
             JOIN shonu_subjects ss ON fr.user_id = ss.id 
             WHERE ss.code2 = '$ownCode' AND DATE(fr.first_recharge_date) = '$date') AS level3_total,
            
            (SELECT SUM(fr.first_recharge_amount) FROM FirstRecharges fr 
             JOIN shonu_subjects ss ON fr.user_id = ss.id 
             WHERE ss.code3 = '$ownCode' AND DATE(fr.first_recharge_date) = '$date') AS level4_total,
            
            (SELECT SUM(fr.first_recharge_amount) FROM FirstRecharges fr 
             JOIN shonu_subjects ss ON fr.user_id = ss.id 
             WHERE ss.code4 = '$ownCode' AND DATE(fr.first_recharge_date) = '$date') AS level5_total,
            
            (SELECT SUM(fr.first_recharge_amount) FROM FirstRecharges fr 
             JOIN shonu_subjects ss ON fr.user_id = ss.id 
             WHERE ss.code5 = '$ownCode' AND DATE(fr.first_recharge_date) = '$date') AS level6_total;");

$row = mysqli_fetch_assoc($Query);
echo json_encode($row);
?>
