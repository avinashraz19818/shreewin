<?php
session_start();
if (empty($_SESSION['unohs'])) {
    die("Unauthorized");
}
include("conn.php");

$userid = $_GET['userid'] ?? '';
$date = $_GET['date'] ?? '';

if (empty($userid)) {
    echo "<p class='text-danger'>User ID required</p>";
    exit();
}

// Function to check illegal bets for specific user
function checkIllegalBets($conn, $specific_user_id = null) {
    $whereClause = $specific_user_id ? "WHERE byabaharkarta = '$specific_user_id'" : "";

    $query = "
        SELECT byabaharkarta, kalaparichaya, COUNT(DISTINCT ojana) as bet_type_count
        FROM (
            SELECT byabaharkarta, kalaparichaya, ojana FROM bajikattuttate_zehn
            UNION ALL
            SELECT byabaharkarta, kalaparichaya, ojana FROM bajikattuttate
            UNION ALL
            SELECT byabaharkarta, kalaparichaya, ojana FROM bajikattuttate_drei
            UNION ALL
            SELECT byabaharkarta, kalaparichaya, ojana FROM bajikattuttate_funf
            UNION ALL
            SELECT byabaharkarta, kalaparichaya, ojana FROM bajikattuttate_trx
            UNION ALL
            SELECT byabaharkarta, kalaparichaya, ojana FROM bajikattuttate_trx3
            UNION ALL
            SELECT byabaharkarta, kalaparichaya, ojana FROM bajikattuttate_trx5
            UNION ALL
            SELECT byabaharkarta, kalaparichaya, ojana FROM bajikattuttate_trx10
        ) AS all_bets
        $whereClause
        GROUP BY byabaharkarta, kalaparichaya
        HAVING bet_type_count > 1
    ";

    return $conn->query($query);
}

// Function to get detailed bet information for a specific user and period
function getUserBetDetails($conn, $userid, $datetime = null) {
    $dateCondition = "";
    if ($datetime) {
        $date = date('Y-m-d', strtotime($datetime));
        $dateCondition = "AND DATE(samaya) = '$date'";
    }
    
    $query = "
        SELECT 
            'bajikattuttate_zehn' as table_name,
            byabaharkarta, 
            kalaparichaya, 
            ojana,
            samaya,
            rashi,
            sankhya
        FROM bajikattuttate_zehn 
        WHERE byabaharkarta = '$userid' $dateCondition
        
        UNION ALL
        
        SELECT 
            'bajikattuttate' as table_name,
            byabaharkarta, 
            kalaparichaya, 
            ojana,
            samaya,
            rashi,
            sankhya
        FROM bajikattuttate 
        WHERE byabaharkarta = '$userid' $dateCondition
        
        UNION ALL
        
        SELECT 
            'bajikattuttate_drei' as table_name,
            byabaharkarta, 
            kalaparichaya, 
            ojana,
            samaya,
            rashi,
            sankhya
        FROM bajikattuttate_drei 
        WHERE byabaharkarta = '$userid' $dateCondition
        
        UNION ALL
        
        SELECT 
            'bajikattuttate_funf' as table_name,
            byabaharkarta, 
            kalaparichaya, 
            ojana,
            samaya,
            rashi,
            sankhya
        FROM bajikattuttate_funf 
        WHERE byabaharkarta = '$userid' $dateCondition
        
        UNION ALL
        
        SELECT 
            'bajikattuttate_trx' as table_name,
            byabaharkarta, 
            kalaparichaya, 
            ojana,
            samaya,
            rashi,
            sankhya
        FROM bajikattuttate_trx 
        WHERE byabaharkarta = '$userid' $dateCondition
        
        UNION ALL
        
        SELECT 
            'bajikattuttate_trx3' as table_name,
            byabaharkarta, 
            kalaparichaya, 
            ojana,
            samaya,
            rashi,
            sankhya
        FROM bajikattuttate_trx3 
        WHERE byabaharkarta = '$userid' $dateCondition
        
        UNION ALL
        
        SELECT 
            'bajikattuttate_trx5' as table_name,
            byabaharkarta, 
            kalaparichaya, 
            ojana,
            samaya,
            rashi,
            sankhya
        FROM bajikattuttate_trx5 
        WHERE byabaharkarta = '$userid' $dateCondition
        
        UNION ALL
        
        SELECT 
            'bajikattuttate_trx10' as table_name,
            byabaharkarta, 
            kalaparichaya, 
            ojana,
            samaya,
            rashi,
            sankhya
        FROM bajikattuttate_trx10 
        WHERE byabaharkarta = '$userid' $dateCondition
        
        ORDER BY samaya DESC
    ";

    return $conn->query($query);
}

// Get illegal bets for this user
$illegal_bets = checkIllegalBets($conn, $userid);

// Get detailed bet information
$bet_details = getUserBetDetails($conn, $userid, $date);

// Display illegal bet status
echo "<div class='alert alert-info'>";
echo "<h6>Illegal Bet Status:</h6>";
if ($illegal_bets && $illegal_bets->num_rows > 0) {
    echo "<span class='badge bg-danger'>ILLEGAL BETS FOUND</span>";
    echo "<p class='mb-0 mt-2'><small>This user has placed multiple bet types on same periods</small></p>";
} else {
    echo "<span class='badge bg-success'>No Illegal Bets</span>";
}
echo "</div>";

// Display detailed bet information
if ($bet_details && $bet_details->num_rows > 0) {
    echo "<h6>Detailed Bet History:</h6>";
    echo "<div class='table-responsive' style='max-height: 400px; overflow-y: auto;'>";
    echo "<table class='table table-sm table-bordered table-striped'>";
    echo "<thead class='table-light'>";
    echo "<tr>
            <th>Table</th>
            <th>Period</th>
            <th>Type</th>
            <th>Number</th>
            <th>Amount</th>
            <th>Time</th>
          </tr>";
    echo "</thead>";
    echo "<tbody>";
    
    $total_amount = 0;
    while ($row = mysqli_fetch_assoc($bet_details)) {
        echo "<tr>";
        echo "<td><small>" . htmlspecialchars($row['table_name']) . "</small></td>";
        echo "<td>" . htmlspecialchars($row['kalaparichaya']) . "</td>";
        echo "<td>" . htmlspecialchars($row['ojana']) . "</td>";
        echo "<td>" . htmlspecialchars($row['sankhya']) . "</td>";
        echo "<td>₹" . htmlspecialchars($row['rashi']) . "</td>";
        echo "<td><small>" . htmlspecialchars($row['samaya']) . "</small></td>";
        echo "</tr>";
        $total_amount += floatval($row['rashi']);
    }
    
    echo "</tbody>";
    echo "<tfoot class='table-light'>";
    echo "<tr>
            <td colspan='4'><strong>Total Bet Amount:</strong></td>
            <td colspan='2'><strong>₹" . number_format($total_amount, 2) . "</strong></td>
          </tr>";
    echo "</tfoot>";
    echo "</table></div>";
    
    echo "<div class='mt-2'>";
    echo "<p class='text-muted'><small>Total Bets: " . $bet_details->num_rows . " | Total Amount: ₹" . number_format($total_amount, 2) . "</small></p>";
    echo "</div>";
} else {
    echo "<div class='alert alert-warning'>";
    echo "<p class='mb-0'>No bet details found for User ID: <strong>" . htmlspecialchars($userid) . "</strong>";
    if ($date) {
        echo " on date: <strong>" . htmlspecialchars($date) . "</strong>";
    }
    echo "</p></div>";
}

// Close connection
mysqli_close($conn);
?>