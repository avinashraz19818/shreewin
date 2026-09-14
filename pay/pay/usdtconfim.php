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
?>
            <!DOCTYPE html>
            <html lang="en">
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title>Payment Successful | Premium Wallet</title>
                    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
                    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
                    <style>
                        :root {
                            --primary-color: #6c5ce7;
                            --secondary-color: #a29bfe;
                            --success-color: #00b894;
                            --danger-color: #d63031;
                            --light-color: #f8f9fa;
                            --dark-color: #343a40;
                            --text-color: #2d3436;
                            --border-radius: 12px;
                            --box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
                        }
                        
                        * {
                            margin: 0;
                            padding: 0;
                            box-sizing: border-box;
                        }
                        
                        body {
                            font-family: 'Poppins', sans-serif;
                            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
                            color: var(--text-color);
                            min-height: 100vh;
                            display: flex;
                            justify-content: center;
                            align-items: center;
                            padding: 20px;
                        }
                        
                        .payment-container {
                            background: white;
                            border-radius: var(--border-radius);
                            box-shadow: var(--box-shadow);
                            width: 100%;
                            max-width: 500px;
                            overflow: hidden;
                            position: relative;
                        }
                        
                        .payment-header {
                            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
                            color: white;
                            padding: 25px;
                            text-align: center;
                            position: relative;
                        }
                        
                        .payment-header h1 {
                            font-size: 1.8rem;
                            font-weight: 600;
                            margin-bottom: 5px;
                        }
                        
                        .payment-header p {
                            font-size: 0.9rem;
                            opacity: 0.9;
                        }
                        
                        .payment-icon {
                            font-size: 3rem;
                            margin-bottom: 15px;
                            color: white;
                        }
                        
                        .payment-amount {
                            text-align: center;
                            padding: 30px 20px;
                            border-bottom: 1px solid #eee;
                        }
                        
                        .payment-amount h2 {
                            font-size: 2.5rem;
                            font-weight: 700;
                            color: var(--primary-color);
                            margin-bottom: 5px;
                        }
                        
                        .payment-amount p {
                            color: var(--text-color);
                            opacity: 0.7;
                            font-size: 0.9rem;
                        }
                        
                        .payment-details {
                            padding: 25px;
                        }
                        
                        .detail-item {
                            display: flex;
                            justify-content: space-between;
                            margin-bottom: 15px;
                            padding-bottom: 15px;
                            border-bottom: 1px dashed #eee;
                        }
                        
                        .detail-item:last-child {
                            border-bottom: none;
                            margin-bottom: 0;
                            padding-bottom: 0;
                        }
                        
                        .detail-title {
                            font-weight: 500;
                            color: var(--text-color);
                            opacity: 0.7;
                        }
                        
                        .detail-content {
                            font-weight: 600;
                            color: var(--text-color);
                        }
                        
                        .payment-status {
                            padding: 20px;
                            background-color: rgba(0, 184, 148, 0.1);
                            margin: 20px;
                            border-radius: var(--border-radius);
                            text-align: center;
                            border-left: 4px solid var(--success-color);
                        }
                        
                        .payment-status h3 {
                            color: var(--success-color);
                            font-size: 1.1rem;
                            margin-bottom: 5px;
                        }
                        
                        .payment-status p {
                            font-size: 0.85rem;
                            color: var(--text-color);
                            opacity: 0.7;
                        }
                        
                        .payment-loader {
                            display: flex;
                            flex-direction: column;
                            align-items: center;
                            padding: 20px;
                        }
                        
                        .loader {
                            width: 50px;
                            height: 50px;
                            border: 4px solid #f3f3f3;
                            border-top: 4px solid var(--primary-color);
                            border-radius: 50%;
                            animation: spin 1s linear infinite;
                            margin-bottom: 15px;
                        }
                        
                        @keyframes spin {
                            0% { transform: rotate(0deg); }
                            100% { transform: rotate(360deg); }
                        }
                        
                        .redirect-notice {
                            text-align: center;
                            padding: 20px;
                            font-size: 0.9rem;
                            color: var(--text-color);
                            opacity: 0.7;
                        }
                        
                        .countdown {
                            font-weight: 600;
                            color: var(--primary-color);
                        }
                    </style>
                </head>
                <body>
                    <div class="payment-container">
                        <div class="payment-header">
                            <div class="payment-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <h1>Payment Successful</h1>
                            <p>Your transaction is being processed</p>
                        </div>
                        
                        <div class="payment-amount">
                            <h2>₹<?php echo number_format($amt, 2); ?></h2>
                            <p>Amount Paid</p>
                        </div>
                        
                        <div class="payment-details">
                            <div class="detail-item">
                                <span class="detail-title">UTR / Ref No</span>
                                <span class="detail-content"><?php echo $refnum; ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-title">Transaction No</span>
                                <span class="detail-content"><?php echo $srl; ?></span>
                            </div>
                        </div>
                        
                        <div class="payment-status">
                            <h3>Transaction Confirmation in Progress</h3>
                            <p>Your transfer will be confirmed automatically within 10 minutes</p>
                        </div>
                        
                        <div class="payment-loader">
                            <div class="loader"></div>
                            <p>Processing your transaction...</p>
                        </div>
                        
                        <div class="redirect-notice">
                            <p>You will be redirected to deposit history in <span class="countdown">3</span> seconds</p>
                        </div>
                    </div>

                    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
                    <script>
                        $(document).ready(function() {
                            // Countdown for redirect
                            let seconds = 3;
                            const countdownElement = $('.countdown');
                            
                            const countdownInterval = setInterval(function() {
                                seconds--;
                                countdownElement.text(seconds);
                                
                                if (seconds <= 0) {
                                    clearInterval(countdownInterval);
                                    window.location.href = "/#/wallet/RechargeHistory";
                                }
                            }, 1000);
                            
                            // Check payment status (kept from original)
                            const myInterval = setInterval(check, 3000);
                            function check() {
                                $.post(base_url + "checksuccesspay?refnum=" + refNo + "&userId=" + userId + "&token=" + token, function(e) {
                                    console.log(e)
                                    if (e == 1) {
                                        clearInterval(myInterval);
                                    }
                                    if (e == 2) {
                                        clearInterval(myInterval);
                                    }
                                });
                            }
                            
                            // Variables from original
                            var base_url = '';
                            var order_no = "ApOGapJbJkm6";
                            var refNo = "<?php echo $refnum; ?>";
                            var userId = "<?php echo $userId; ?>";
                            var token = "<?php echo $token; ?>";
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
?>