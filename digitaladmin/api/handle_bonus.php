<?php
// Bonus type mapping
include("conn.php");

ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

$bonusTypes = [
	3 => "Red envelope",
	8 => "Agent red envelope recharge",
	10 => "Recharge gift",
	13 => "Bonus",
	14 => "First full gift",
	20 => "Invite bonus",
	25 => "Card binding gift",
	107 => "Weekly Awards",
	124 => "Join channel rewards",
	118 => "Daily Awards",
	117 => "New members get bonuses by playing games",
	115 => "Return Awards",
];

// Function to update user balance
function updateBalance($userId, $amount, $conn, $turnover)
{
    // Ensure values are properly escaped
    $userId = (int) $userId;
    $amount = (int) $amount;

    // Construct the SQL query dynamically
    $sql = "UPDATE shonu_kaichila SET motta = motta + $amount";
    
    if (isset($turnover)) {
        $sql .= ", mottta = mottta + $amount";
    }
    
    $sql .= " WHERE balakedara = $userId";

    // Execute the query
    if (mysqli_query($conn, $sql)) {
        return "1";
    } else {
        return "Error executing statement: " . mysqli_error($conn);
    }
}


// Function to add bonus
function addBonus($userId, $type, $amount, $remark, $conn, $turnover)
{
    // Define table names based on type
    $tableNames = [
        3 => "hodike_balakedara",
        8 => "agent_red_envelope_recharge_table",
        10 => "recharge_gift_table",
        13 => "bonus_recharge_table",
        14 => "first_full_gift_table",
        20 => "invite_bonus_table",
        25 => "card_binding_gift_table",
        107 => "weekly_awards_table",
        124 => "agent_bonus_table",
        118 => "daily_awards_table",
        117 => "new_members_bonus_table",
        115 => "return_awards_table",
    ];

    // Validate bonus type
    if (!isset($tableNames[$type])) {
        return "Invalid bonus type.";
    }

    $tableName = $tableNames[$type];
    $date = date("Y-m-d H:i:s");
    $serial = "Imitator"; // Fixed serial value

    // Directly inserting variables into the query
    $sql = "
        INSERT INTO $tableName (userkani, price, serial, shonu, remark, balance)
        SELECT $userId, $amount, '$serial', '$date', '$remark', (motta + $amount) 
        FROM shonu_kaichila 
        WHERE balakedara = $userId
    ";

    // Execute the query
    if (mysqli_query($conn, $sql)) {
        // Update user balance
        $balanceResult = updateBalance($userId, $amount, $conn, $turnover);
        if ($balanceResult !== "1") {
            return $balanceResult;
        }        

        return "Bonus successfully added to table: $tableName.";
    } else {
        return "Error executing statement: " . mysqli_error($conn);
    }
}


// Input handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$userId = intval($_POST['user_id']);
	$type = intval($_POST['type']);
$turnover = $_POST['turnover']?? null;


	$amount = floatval($_POST['amount']);
	$remark = htmlspecialchars($_POST['remark'] ?? '');
	// Validate inputs
	if ($userId > 0 && $type > 0 && $amount > 0) {
		$result = addBonus($userId, $type, $amount, $remark, $conn, $turnover);
		echo 1;
		http_response_code(200);
	} else {
		echo 0;
		http_response_code(500);
	}
}
?>