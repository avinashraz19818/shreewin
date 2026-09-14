<?php
include ("../serive/samparka.php");

// Validate request
if(!isset($_POST['userId']) || !isset($_POST['amount']) || !isset($_POST['upi_id']) || !isset($_POST['serial']) || !isset($_POST['token'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$userId = intval($_POST['userId']);
$amount = floatval($_POST['amount']);
$upi_id = mysqli_real_escape_string($conn, $_POST['upi_id']);
$serial = mysqli_real_escape_string($conn, $_POST['serial']);
$token = mysqli_real_escape_string($conn, $_POST['token']);

// Verify token (use your existing token verification logic)
// ...

// Check if payment was received (this is where you'd integrate with your payment gateway API)
// For demonstration, we'll simulate a 50% chance of payment being detected
$paymentReceived = mt_rand(0, 1) === 1;
$utr = 'UTR' . time() . mt_rand(1000, 9999);

if($paymentReceived) {
    // Record in recharge history
    $query = "INSERT INTO recharge_history 
              (user_id, amount, upi_id, utr, transaction_id, status) 
              VALUES 
              ($userId, $amount, '$upi_id', '$utr', '$serial', 'completed')";
    
    if($conn->query($query)) {
        // Update user balance or perform other post-payment actions
        // $updateBalanceQuery = "UPDATE users SET balance = balance + $amount WHERE id = $userId";
        // $conn->query($updateBalanceQuery);
        
        echo json_encode([
            'success' => true,
            'payment_received' => true,
            'utr' => $utr,
            'message' => 'Payment received and recorded'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'payment_received' => false,
            'message' => 'Payment received but failed to record history'
        ]);
    }
} else {
    // Check if we have a pending record for this transaction
    $checkQuery = "SELECT id FROM recharge_history WHERE transaction_id = '$serial'";
    $result = $conn->query($checkQuery);
    
    if($result->num_rows === 0) {
        // Create pending record if doesn't exist
        $conn->query("INSERT INTO recharge_history 
                     (user_id, amount, upi_id, utr, transaction_id, status) 
                     VALUES 
                     ($userId, $amount, '$upi_id', 'PENDING', '$serial', 'pending')");
    }
    
    echo json_encode([
        'success' => true,
        'payment_received' => false,
        'message' => 'Payment not yet received'
    ]);
}