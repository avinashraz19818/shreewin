<?php
// Database connection
include ("../serive/samparka.php");

// Webhook configuration for payment processing
define('PAYMENT_WEBHOOK_URL', 'payment.php');
define('PAYMENT_WEBHOOK_SECRET', 'your_secure_secret_key_here');

// Function to handle payment requests and record in deposit history
function handlePaymentRequest($paymentData) {
    global $conn;
    
    // Validate payment data
    if (!isset($paymentData['user_id']) || !isset($paymentData['amount']) || !isset($paymentData['currency'])) {
        return ['status' => 'error', 'message' => 'Invalid payment data'];
    }
    
    // Sanitize input
    $userId = mysqli_real_escape_string($conn, $paymentData['user_id']);
    $amount = floatval($paymentData['amount']);
    $currency = mysqli_real_escape_string($conn, $paymentData['currency']);
    $txnId = isset($paymentData['txn_id']) ? mysqli_real_escape_string($conn, $paymentData['txn_id']) : uniqid();
    $status = 'pending';
    $createdAt = date('Y-m-d H:i:s');
    
    // Insert into deposit history
    $query = "INSERT INTO deposit_history 
              (user_id, amount, currency, transaction_id, status, created_at) 
              VALUES ('$userId', $amount, '$currency', '$txnId', '$status', '$createdAt')";
    
    if (mysqli_query($conn, $query)) {
        $depositId = mysqli_insert_id($conn);
        return [
            'status' => 'success',
            'message' => 'Payment request recorded',
            'deposit_id' => $depositId
        ];
    } else {
        return [
            'status' => 'error',
            'message' => 'Database error: ' . mysqli_error($conn)
        ];
    }
}

// Function to verify webhook signature
function verifyWebhookSignature($payload, $signature) {
    $calculatedSignature = hash_hmac('sha256', $payload, PAYMENT_WEBHOOK_SECRET);
    return hash_equals($calculatedSignature, $signature);
}

// Function to process incoming webhook
function processPaymentWebhook() {
    $payload = file_get_contents('php://input');
    $signature = $_SERVER['HTTP_X_SIGNATURE'] ?? '';
    
    if (!verifyWebhookSignature($payload, $signature)) {
        http_response_code(401);
        die('Invalid signature');
    }
    
    $data = json_decode($payload, true);
    $result = handlePaymentRequest($data);
    
    header('Content-Type: application/json');
    echo json_encode($result);
}

// Uncomment to test the webhook processing
// processPaymentWebhook();
?>