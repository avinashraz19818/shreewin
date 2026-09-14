<?php include ("../serive/samparka.php");?>
<?php 
    if(isset($_GET['amt'])){
    $amt = $_GET['amt'];
    } else{
        $amt = 0;
    }
    if(isset($_GET['refnum'])){
    $refnum = $_GET['refnum'];
    } else{
        $refnum = 0;
    }
    if(isset($_GET['srl'])){
    $srl = $_GET['srl'];
    } else{
        $srl = 0;
    }
?>
<?php 
    $res = [
        'code' => 405,
        'message' => 'Illegal access!',
    ];
    
    if (isset($_GET['userId']) && isset($_GET['token'])) {
        $userId = htmlspecialchars(mysqli_real_escape_string($conn, $_GET['userId']));
        $userPhoto = '1';
        
        $numquery = "SELECT mobile, codechorkamukala
          FROM shonu_subjects
          WHERE id = ".$userId;
        $numresult = $conn->query($numquery);
        $numarr = mysqli_fetch_array($numresult);
        
        $userName = '91'.$numarr['mobile'];
        $nickName = $numarr['codechorkamukala'];
        
        $creaquery = "SELECT createdate
          FROM shonu_subjects
          WHERE id = ".$userId;
        $crearesult = $conn->query($creaquery);
        $creaarr = mysqli_fetch_array($crearesult);
        
        $knbdstr = '{"userId":'.$userId.',"userPhoto":"'.$userPhoto.'","userName":'.$userName.',"nickName":"'.$nickName.'","createdate":"'.$creaarr['createdate'].'"}';
        $shonusign = strtoupper(hash('sha256', $knbdstr));
        
        $token = htmlspecialchars(mysqli_real_escape_string($conn, $_GET['token']));
        
        if($shonusign == $token){
            // Log the payment attempt
            $log_query = "INSERT INTO payment_logs (user_id, amount, reference_num, serial_num, status, created_at) 
                         VALUES ('$userId', '$amt', '$refnum', '$srl', 'pending', NOW())";
            $conn->query($log_query);
            
            // Check if payment already exists to prevent duplicates
            $check_query = "SELECT id FROM payment_history WHERE reference_num = '$refnum' AND user_id = '$userId'";
            $check_result = $conn->query($check_query);
            
            if($check_result->num_rows > 0) {
                // Payment already exists, update status
                $update_query = "UPDATE payment_history SET status = 'verified', updated_at = NOW() 
                                WHERE reference_num = '$refnum' AND user_id = '$userId'";
                $conn->query($update_query);
                
                // Redirect immediately if payment already verified
                header("Refresh: 0; url=RechargeHistory.php?status=already_verified");
                exit();
            } else {
                // Insert new payment record
                $insert_query = "INSERT INTO payment_history (user_id, amount, reference_num, serial_num, status, created_at) 
                                 VALUES ('$userId', '$amt', '$refnum', '$srl', 'pending', NOW())";
                $conn->query($insert_query);
            }
?>
            <html lang="zh-cmn-Hans">
                <head>
                    <meta content="text/html; charset=UTF-8" http-equiv="Content-Type">
                    <title>Payment successful</title>
                   <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
<meta http-equiv="refresh" content="3;url=https://luckywin28.buzz/#/wallet/RechargeHistory" />

                    <style>
                        body {
                            font-family: Arial, sans-serif;
                            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
                            height: 100vh;
                            margin: 0;
                            display: flex;
                            justify-content: center;
                            align-items: center;
                            color: white;
                            text-align: center;
                        }
                        .container {
                            background: rgba(255, 255, 255, 0.1);
                            padding: 30px;
                            border-radius: 15px;
                            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
                            max-width: 500px;
                            width: 90%;
                        }
                        h1 {
                            margin-top: 0;
                            font-size: 24px;
                        }
                        .amount {
                            font-size: 32px;
                            font-weight: bold;
                            margin: 20px 0;
                        }
                        .details {
                            background: rgba(255, 255, 255, 0.2);
                            padding: 15px;
                            border-radius: 10px;
                            margin: 15px 0;
                        }
                        .detail-row {
                            display: flex;
                            justify-content: space-between;
                            margin: 10px 0;
                        }
                        .loader {
                            border: 5px solid #f3f3f3;
                            border-top: 5px solid #3498db;
                            border-radius: 50%;
                            width: 50px;
                            height: 50px;
                            animation: spin 1s linear infinite;
                            margin: 20px auto;
                        }
                        @keyframes spin {
                            0% { transform: rotate(0deg); }
                            100% { transform: rotate(360deg); }
                        }
                        .redirect-message {
                            margin-top: 20px;
                            font-size: 14px;
                        }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <h1>Payment Processing</h1>
                        <div class="amount">₹<?php echo $amt; ?></div>
                        
                        <div class="details">
                            <div class="detail-row">
                                <span>UTR/Ref No:</span>
                                <span><?php echo $refnum; ?></span>
                            </div>
                            <div class="detail-row">
                                <span>Transaction No:</span>
                                <span><?php echo $srl; ?></span>
                            </div>
                        </div>
                        
                        <div class="loader"></div>
                        
                        <p>Your payment is being processed. Please wait...</p>
                        <p class="redirect-message">You will be redirected to Recharge History in 3 seconds.</p>
                    </div>

                    <script>
                        // Enhanced payment verification
                        document.addEventListener('DOMContentLoaded', function() {
                            // Immediately check payment status
                            checkPaymentStatus();
                            
                            // Set interval for additional checks (every 2 seconds)
                            const statusInterval = setInterval(checkPaymentStatus, 2000);
                            
                            function checkPaymentStatus() {
                                fetch(`payment_verification.php?userId=<?php echo $userId; ?>&refnum=<?php echo $refnum; ?>&amt=<?php echo $amt; ?>`)
                                    .then(response => response.json())
                                    .then(data => {
                                        if(data.status === 'verified') {
                                            // Update UI for successful verification
                                            document.querySelector('.loader').style.borderTopColor = '#2ecc71';
                                            document.querySelector('h1').textContent = 'Payment Verified!';
                                            clearInterval(statusInterval);
                                            
                                            // Optional: Redirect immediately if verified
                                            // window.location.href = 'RechargeHistory.php?status=success';
                                        } else if(data.status === 'failed') {
                                            // Update UI for failed verification
                                            document.querySelector('.loader').style.borderTopColor = '#e74c3c';
                                            document.querySelector('h1').textContent = 'Payment Verification Failed';
                                            clearInterval(statusInterval);
                                        }
                                    })
                                    .catch(error => {
                                        console.error('Error checking payment status:', error);
                                    });
                            }
                        });
                    </script>
                </body>
            </html>
<?php
        }
        else{
            $res['code'] = 10000;
            $res['success'] = 'false';
            $res['message'] = 'Sorry, The system is busy, please try again later!';
            
            header('Content-Type: text/html; charset=utf-8');
            http_response_code(200);
            echo json_encode($res);
        }
    }
    else{
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(200);
        echo json_encode($res);    
    }
    
    // Additional functions for payment processing
    function verifyPayment($conn, $userId, $refnum, $amount) {
        // Check with payment gateway or database
        $query = "SELECT * FROM payment_history 
                 WHERE user_id = '$userId' 
                 AND reference_num = '$refnum' 
                 AND amount = '$amount' 
                 AND status = 'verified'";
        
        $result = $conn->query($query);
        
        if($result->num_rows > 0) {
            return ['status' => 'verified', 'message' => 'Payment already verified'];
        }
        
        // Simulate payment verification (replace with actual gateway API call)
        $isVerified = rand(0, 1); // Random verification for demo
        
        if($isVerified) {
            // Update payment status
            $update_query = "UPDATE payment_history SET status = 'verified', verified_at = NOW() 
                           WHERE user_id = '$userId' AND reference_num = '$refnum'";
            $conn->query($update_query);
            
            // Update user wallet balance
            $wallet_query = "UPDATE user_wallets SET balance = balance + $amount 
                           WHERE user_id = '$userId'";
            $conn->query($wallet_query);
            
            return ['status' => 'verified', 'message' => 'Payment verified successfully'];
        } else {
            return ['status' => 'failed', 'message' => 'Payment verification failed'];
        }
    }
    
    function logPaymentActivity($conn, $userId, $action, $details) {
        $query = "INSERT INTO payment_activity_logs 
                 (user_id, action, details, created_at) 
                 VALUES ('$userId', '$action', '$details', NOW())";
        $conn->query($query);
    }
?>