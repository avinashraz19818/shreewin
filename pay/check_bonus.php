<?php
include ("../serive/samparka.php");

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 1174207; // Aapka test user ID

echo "<h2>🔍 Bonus Check for User ID: $user_id</h2>";

// Check user balance
$balance_query = "SELECT * FROM shonu_kaichila WHERE balakedara = '$user_id'";
$balance_result = $conn->query($balance_query);

echo "<h3>💰 Current Balance</h3>";
if($balance_result->num_rows > 0) {
    $balance = $balance_result->fetch_assoc();
    echo "<p><strong>Balance:</strong> Rs " . number_format($balance['motta'], 2) . "</p>";
} else {
    echo "<p>User not found in balance table</p>";
}

// Get recent transactions
$trans_query = "SELECT * FROM thevani WHERE balakedara = '$user_id' ORDER BY shonu DESC LIMIT 5";
$trans_result = $conn->query($trans_query);

echo "<h3>📋 Recent Transactions</h3>";
echo "<table border='1' cellpadding='10' cellspacing='0' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #f0f0f0;'>
        <th>Order ID</th>
        <th>Base Amount</th>
        <th>Bonus</th>
        <th>Total</th>
        <th>Status</th>
        <th>Date</th>
      </tr>";

if($trans_result->num_rows > 0) {
    while($row = $trans_result->fetch_assoc()) {
        $status = ($row['sthiti'] == 1) ? "✅ Success" : "⏳ Pending";
        $status_color = ($row['sthiti'] == 1) ? "green" : "orange";
        
        echo "<tr>";
        echo "<td>" . $row['dharavahi'] . "</td>";
        echo "<td>Rs " . number_format($row['motta'], 2) . "</td>";
        echo "<td style='color: blue; font-weight: bold;'>Rs " . number_format($row['bonus_amount'], 2) . "</td>";
        echo "<td style='color: green; font-weight: bold;'>Rs " . number_format($row['total_amount'], 2) . "</td>";
        echo "<td style='color: $status_color;'>$status</td>";
        echo "<td>" . $row['dinankavannuracisi'] . "</td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='6' style='text-align: center;'>No transactions found</td></tr>";
}
echo "</table>";

// Manual update option
echo "<h3>⚡ Manual Update</h3>";
echo "<form method='post' style='background: #f9f9f9; padding: 20px; border-radius: 5px;'>";
echo "<input type='hidden' name='user_id' value='$user_id'>";
echo "<button type='submit' name='manual_update' style='background: blue; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>Force Update Balance</button>";
echo "</form>";

if(isset($_POST['manual_update'])) {
    // Get pending transactions
    $pending_query = "SELECT * FROM thevani WHERE balakedara = '$user_id' AND sthiti = '0'";
    $pending_result = $conn->query($pending_query);
    
    if($pending_result->num_rows > 0) {
        while($pending = $pending_result->fetch_assoc()) {
            $total = $pending['total_amount'];
            $order = $pending['dharavahi'];
            
            // Update balance
            $update = "UPDATE shonu_kaichila SET motta = motta + $total WHERE balakedara = '$user_id'";
            if($conn->query($update)) {
                // Mark as processed
                $conn->query("UPDATE thevani SET sthiti = '1' WHERE dharavahi = '$order'");
                echo "<p style='color: green;'>✅ Added Rs $total from order $order</p>";
            }
        }
    } else {
        echo "<p style='color: orange;'>No pending transactions found</p>";
    }
}
?>