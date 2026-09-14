// Function to update deposit status in history
function updateDepositStatus($depositId, $status, $additionalData = []) {
    global $conn;
    
    $depositId = mysqli_real_escape_string($conn, $depositId);
    $status = mysqli_real_escape_string($conn, $status);
    $updatedAt = date('Y-m-d H:i:s');
    
    $query = "UPDATE deposit_history SET 
              status = '$status', 
              updated_at = '$updatedAt'";
    
    // Add additional data if provided
    if (!empty($additionalData['reference_id'])) {
        $refId = mysqli_real_escape_string($conn, $additionalData['reference_id']);
        $query .= ", reference_id = '$refId'";
    }
    
    $query .= " WHERE id = '$depositId'";
    
    return mysqli_query($conn, $query);
}

// Function to get user's deposit history
function getUserDepositHistory($userId, $limit = 10) {
    global $conn;
    
    $userId = mysqli_real_escape_string($conn, $userId);
    $limit = intval($limit);
    
    $query = "SELECT * FROM deposit_history 
              WHERE user_id = '$userId' 
              ORDER BY created_at DESC 
              LIMIT $limit";
    
    $result = mysqli_query($conn, $query);
    $history = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $history[] = $row;
    }
    
    return $history;
}

// Function to process successful payment callback
function processSuccessfulPayment($txnId, $amount, $currency, $referenceId) {
    global $conn;
    
    $txnId = mysqli_real_escape_string($conn, $txnId);
    $amount = floatval($amount);
    $currency = mysqli_real_escape_string($conn, $currency);
    $referenceId = mysqli_real_escape_string($conn, $referenceId);
    
    // Find the deposit record
    $query = "SELECT id, user_id FROM deposit_history 
              WHERE transaction_id = '$txnId' 
              AND amount = $amount 
              AND currency = '$currency' 
              LIMIT 1";
    
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) > 0) {
        $deposit = mysqli_fetch_assoc($result);
        
        // Update deposit status to completed
        if (updateDepositStatus($deposit['id'], 'completed', ['reference_id' => $referenceId])) {
            
            // Add funds to user account (implement your own user balance update function)
            // Example: updateUserBalance($deposit['user_id'], $amount);
            
            return [
                'status' => 'success',
                'message' => 'Payment completed and funds added'
            ];
        }
    }
    
    return [
        'status' => 'error',
        'message' => 'Deposit record not found'
    ];
}

// Function to process failed payment
function processFailedPayment($txnId, $reason) {
    global $conn;
    
    $txnId = mysqli_real_escape_string($conn, $txnId);
    $reason = mysqli_real_escape_string($conn, $reason);
    
    $query = "UPDATE deposit_history SET 
              status = 'failed', 
              failure_reason = '$reason',
              updated_at = NOW() 
              WHERE transaction_id = '$txnId' 
              AND status = 'pending'";
    
    if (mysqli_query($conn, $query)) {
        return [
            'status' => 'success',
            'message' => 'Payment marked as failed'
        ];
    }
    
    return [
        'status' => 'error',
        'message' => 'Failed to update payment status'
    ];
}

// Webhook response handler
function sendWebhookResponse($status, $message, $data = []) {
    $response = [
        'status' => $status,
        'message' => $message,
        'timestamp' => time()
    ];
    
    if (!empty($data)) {
        $response['data'] = $data;
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Example usage in payment.php:
/*
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payload = json_decode(file_get_contents('php://input'), true);
    
    // Verify signature
    if (!verifyWebhookSignature($payload, $_SERVER['HTTP_X_SIGNATURE'])) {
        sendWebhookResponse('error', 'Invalid signature');
    }
    
    // Handle different payment events
    switch ($payload['event_type']) {
        case 'payment.initiated':
            $result = handlePaymentRequest($payload);
            sendWebhookResponse($result['status'], $result['message'], $result);
            break;
            
        case 'payment.success':
            $result = processSuccessfulPayment(
                $payload['txn_id'],
                $payload['amount'],
                $payload['currency'],
                $payload['reference_id']
            );
            sendWebhookResponse($result['status'], $result['message'], $result);
            break;
            
        case 'payment.failed':
            $result = processFailedPayment(
                $payload['txn_id'],
                $payload['reason']
            );
            sendWebhookResponse($result['status'], $result['message'], $result);
            break;
            
        default:
            sendWebhookResponse('error', 'Unknown event type');
    }
}
*/