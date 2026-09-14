<?php include ("../serive/samparka.php");?>
<?php 
if(isset($_GET['amount'])){
    $ramt = htmlspecialchars(mysqli_real_escape_string($conn, $_GET['amount']));
} else{
    $ramt = 0;
}
$dot_pos = strpos($ramt, '.');
if ($dot_pos === false) {
    $ramt = $ramt . '.00';
}else {
    $after_dot = substr($ramt, $dot_pos + 1);
    $after_dot_length = strlen($after_dot);
    if ($after_dot_length > 2) {
        $after_dot = substr($after_dot, 0, 2);
        $ramt = substr($ramt, 0, $dot_pos + 1) . $after_dot;
    } elseif ($after_dot_length < 2) {
        $zeros_to_add = 2 - $after_dot_length;
        $ramt = $ramt . str_repeat('0', $zeros_to_add);
    }
}
$date = date("Ymd");
$time = time();
$serial = 'P' . $date . $time . rand(1000,9999);

$tyid = htmlspecialchars(mysqli_real_escape_string($conn, $_GET['tyid']));
$uid = htmlspecialchars(mysqli_real_escape_string($conn, $_GET['uid']));
$sign = htmlspecialchars(mysqli_real_escape_string($conn, $_GET['sign']));
$urlInfo = htmlspecialchars(mysqli_real_escape_string($conn, $_GET['urlInfo']));
?>
<?php 
    $s_upi = "SELECT maulya FROM deyyamrici WHERE sthiti='1'";
    $r_upi = mysqli_query($conn, $s_upi);
    $f_upi = mysqli_fetch_array($r_upi);
    $upi_id = $f_upi['maulya'];
    
    $selectupi_two=mysqli_query($conn,"select * from `images_usdt` where `status`=1");
    $selectupiresult_two=mysqli_fetch_array($selectupi_two);
?>
<?php
    $res = [
        'code' => 405,
        'message' => 'Illegal access!',
    ];
    if (isset($_GET['tyid']) && isset($_GET['amount']) && isset($_GET['uid']) && isset($_GET['sign']) && isset($_GET['urlInfo'])) {
        $userId = $uid;
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
        
        $urlarr = explode (",", $urlInfo);
        $theirurl = $urlarr[0];
        $myurl = 'https://luckywin28.buzz';
        
        if($myurl){
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>USDT Payment Gateway</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        :root {
            --primary: #6c5ce7;
            --secondary: #a29bfe;
            --accent: #fd79a8;
            --dark: #2d3436;
            --light: #f5f6fa;
            --success: #00b894;
            --warning: #fdcb6e;
            --danger: #d63031;
            --info: #0984e3;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .payment-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
            overflow: hidden;
            position: relative;
            transform: translateY(0);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .payment-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        
        .payment-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 25px;
            text-align: center;
            position: relative;
        }
        
        .payment-header h1 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .payment-header p {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .payment-logo {
            width: 60px;
            height: 60px;
            background: white;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0 auto 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .payment-logo img {
            width: 40px;
            height: 40px;
        }
        
        .payment-body {
            padding: 25px;
        }
        
        .amount-display {
            background: var(--light);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            margin-bottom: 25px;
            position: relative;
            overflow: hidden;
        }
        
        .amount-display::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--accent));
        }
        
        .amount-value {
            font-size: 42px;
            font-weight: 700;
            color: var(--primary);
            margin: 10px 0;
            position: relative;
            display: inline-block;
        }
        
        .amount-value::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 50%;
            transform: translateX(-50%);
            width: 50px;
            height: 3px;
            background: var(--accent);
            border-radius: 3px;
        }
        
        .amount-currency {
            font-size: 18px;
            color: var(--dark);
            opacity: 0.7;
        }
        
        .transaction-id {
            font-size: 12px;
            color: var(--dark);
            opacity: 0.7;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .payment-method {
            margin-bottom: 25px;
        }
        
        .payment-method h3 {
            font-size: 16px;
            color: var(--dark);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        
        .payment-method h3::before {
            content: '';
            display: inline-block;
            width: 6px;
            height: 6px;
            background: var(--primary);
            border-radius: 50%;
            margin-right: 8px;
        }
        
        .qr-code-container {
            background: white;
            border-radius: 12px;
            padding: 15px;
            display: flex;
            flex-direction: column;
            align-items: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .qr-code {
            width: 200px;
            height: 200px;
            margin: 10px 0;
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            overflow: hidden;
            position: relative;
        }
        
        .qr-code img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        
        .wallet-address {
            background: var(--light);
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 15px;
            position: relative;
            word-break: break-all;
            font-size: 14px;
            text-align: center;
            font-weight: 500;
            color: var(--dark);
        }
        
        .copy-btn {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            border: none;
            border-radius: 6px;
            padding: 8px 15px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(108, 92, 231, 0.3);
        }
        
        .copy-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(108, 92, 231, 0.4);
        }
        
        .copy-btn i {
            margin-right: 5px;
        }
        
        .transaction-input {
            margin-bottom: 25px;
        }
        
        .transaction-input label {
            display: block;
            font-size: 14px;
            color: var(--dark);
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .input-group {
            position: relative;
        }
        
        .transaction-input input {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .transaction-input input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(108, 92, 231, 0.2);
            outline: none;
        }
        
        .transaction-input input::placeholder {
            color: #bdbdbd;
        }
        
        .submit-btn {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 15px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(108, 92, 231, 0.3);
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(108, 92, 231, 0.4);
        }
        
        .payment-steps {
            margin-bottom: 25px;
        }
        
        .step {
            display: flex;
            margin-bottom: 15px;
            align-items: flex-start;
        }
        
        .step-number {
            background: var(--primary);
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 12px;
            font-weight: 600;
            margin-right: 12px;
            flex-shrink: 0;
        }
        
        .step-content {
            flex: 1;
        }
        
        .step-title {
            font-size: 14px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 5px;
        }
        
        .step-description {
            font-size: 13px;
            color: #666;
            line-height: 1.5;
        }
        
        .payment-tips {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 15px;
            margin-top: 20px;
        }
        
        .tips-title {
            font-size: 14px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }
        
        .tips-title::before {
            content: '!';
            display: inline-block;
            width: 18px;
            height: 18px;
            background: var(--warning);
            color: white;
            border-radius: 50%;
            text-align: center;
            line-height: 18px;
            font-size: 12px;
            margin-right: 8px;
        }
        
        .tip {
            font-size: 12px;
            color: #666;
            margin-bottom: 8px;
            display: flex;
        }
        
        .tip::before {
            content: '•';
            color: var(--primary);
            margin-right: 8px;
        }
        
        .highlight {
            color: var(--primary);
            font-weight: 500;
        }
        
        .danger {
            color: var(--danger);
            font-weight: 500;
        }
        
        .pulse {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(108, 92, 231, 0.4);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(108, 92, 231, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(108, 92, 231, 0);
            }
        }
        
        .vibrate {
            animation: vibrate 0.3s linear infinite;
        }
        
        @keyframes vibrate {
            0%, 100% { transform: translateX(0); }
            20% { transform: translateX(-2px); }
            40% { transform: translateX(2px); }
            60% { transform: translateX(-2px); }
            80% { transform: translateX(2px); }
        }
        
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            display: none;
        }
        
        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 5px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .confirmation-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            display: none;
        }
        
        .modal-content {
            background: white;
            border-radius: 15px;
            width: 90%;
            max-width: 400px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            transform: scale(0.9);
            opacity: 0;
            transition: all 0.3s ease;
        }
        
        .modal-header {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .modal-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 10px;
        }
        
        .modal-icon {
            width: 60px;
            height: 60px;
            background: var(--success);
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0 auto 15px;
        }
        
        .modal-icon i {
            color: white;
            font-size: 30px;
        }
        
        .modal-body {
            margin-bottom: 25px;
        }
        
        .modal-message {
            font-size: 14px;
            color: #666;
            line-height: 1.5;
            text-align: center;
        }
        
        .modal-footer {
            display: flex;
            justify-content: center;
        }
        
        .modal-btn {
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .modal-btn:hover {
            background: var(--secondary);
        }
        
        .toast {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--dark);
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 14px;
            z-index: 1000;
            opacity: 0;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        @media (max-width: 480px) {
            .payment-header {
                padding: 20px;
            }
            
            .payment-body {
                padding: 20px;
            }
            
            .amount-value {
                font-size: 36px;
            }
        }
    </style>
</head>
<body>
    <div class="payment-container animate__animated animate__fadeIn">
        <div class="payment-header">
            <div class="payment-logo pulse">
                <img src="../assets/png/usdt-40311708.png" alt="USDT">
            </div>
            <h1>USDT Payment Gateway</h1>
            <p>Secure TRC20 Network Transaction</p>
        </div>
        
        <div class="payment-body">
            <div class="amount-display animate__animated animate__fadeInUp">
                <div class="amount-currency">Amount to Pay</div>
                <div class="amount-value">$<?php echo $ramt; ?></div>
                <div class="amount-currency">USDT (TRC20)</div>
            </div>
            
            <div class="transaction-id animate__animated animate__fadeInUp animate__delay-1s">
                Transaction ID: <?php echo $serial; ?>
            </div>
            
            <div class="payment-steps animate__animated animate__fadeInUp animate__delay-1s">
                <div class="step">
                    <div class="step-number">1</div>
                    <div class="step-content">
                        <div class="step-title">Transfer USDT</div>
                        <div class="step-description">
                            Send exactly <span class="highlight"><?php echo $ramt; ?> USDT</span> to the wallet address below using TRC20 network
                        </div>
                    </div>
                </div>
                
                <div class="step">
                    <div class="step-number">2</div>
                    <div class="step-content">
                        <div class="step-title">Submit Transaction ID</div>
                        <div class="step-description">
                            After payment, enter the Transaction ID (TXID) below and click submit
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="payment-method animate__animated animate__fadeInUp animate__delay-2s">
                <h3>Payment Details</h3>
                
                <div class="qr-code-container">
                    <div class="qr-code">
                        <img src="<?php echo '../images_usdt/'.$selectupiresult_two['filename']; ?>" alt="USDT QR Code">
                    </div>
                    
                    <div class="wallet-address" id="walletAddress">
                        <?php echo $upi_id;?>
                    </div>
                    
                    <button class="copy-btn" id="copyAddressBtn">
                        <i class="fas fa-copy"></i> Copy Wallet Address
                    </button>
                </div>
                
                <div class="transaction-input">
                    <label for="transactionId">Transaction ID (TXID)</label>
                    <div class="input-group">
                        <input type="text" id="transactionId" placeholder="Enter your USDT transaction hash" class="vibrate">
                    </div>
                </div>
                
                <button class="submit-btn" id="submitBtn">Submit Transaction</button>
            </div>
            
            <div class="payment-tips animate__animated animate__fadeInUp animate__delay-3s">
                <div class="tips-title">Important Notes</div>
                <div class="tip">This channel only supports <span class="highlight">USDT-TRC20</span> transactions</div>
                <div class="tip">The wallet address is <span class="danger">one-time use only</span>, do not reuse</div>
                <div class="tip">Minimum deposit: <span class="danger">10 USDT</span> | Maximum deposit: <span class="danger">5000 USDT</span></div>
                <div class="tip">Transactions typically confirm within <span class="highlight">1-5 minutes</span></div>
                <div class="tip">Contact support if you encounter any issues</div>
            </div>
        </div>
    </div>
    
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>
    
    <div class="confirmation-modal" id="confirmationModal">
        <div class="modal-content" id="modalContent">
            <div class="modal-header">
                <div class="modal-icon">
                    <i class="fas fa-check"></i>
                </div>
                <h3 class="modal-title">Confirm Payment Details</h3>
            </div>
            <div class="modal-body">
                <p class="modal-message" id="confirmationMessage"></p>
            </div>
            <div class="modal-footer">
                <button class="modal-btn" id="confirmBtn">Confirm</button>
                <button class="modal-btn" id="cancelBtn" style="background: var(--danger); margin-left: 10px;">Cancel</button>
            </div>
        </div>
    </div>
    
    <div class="toast" id="toast"></div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/js/all.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.8/clipboard.min.js"></script>
    <script>
        $(document).ready(function() {
            // Animation for elements
            $('.payment-container').addClass('animate__animated animate__fadeIn');
            $('.amount-display').addClass('animate__animated animate__fadeInUp');
            $('.transaction-id').addClass('animate__animated animate__fadeInUp animate__delay-1s');
            $('.payment-steps').addClass('animate__animated animate__fadeInUp animate__delay-1s');
            $('.payment-method').addClass('animate__animated animate__fadeInUp animate__delay-2s');
            $('.payment-tips').addClass('animate__animated animate__fadeInUp animate__delay-3s');
            
            // Copy wallet address
            new ClipboardJS('#copyAddressBtn', {
                text: function() {
                    return $('#walletAddress').text();
                }
            });
            
            $('#copyAddressBtn').click(function() {
                showToast('Wallet address copied to clipboard');
            });
            
            // Submit button click
            $('#submitBtn').click(function() {
                const txId = $('#transactionId').val().trim();
                if (!txId) {
                    showToast('Please enter your Transaction ID');
                    $('#transactionId').addClass('vibrate');
                    setTimeout(() => {
                        $('#transactionId').removeClass('vibrate');
                    }, 1000);
                    return;
                }
                
                // Show confirmation modal
                const amount = '<?php echo $ramt; ?>';
                const wallet = $('#walletAddress').text();
                const message = `Please confirm your payment details:<br><br>
                    <strong>Amount:</strong> ${amount} USDT<br>
                    <strong>Wallet:</strong> ${wallet}<br>
                    <strong>TXID:</strong> ${txId}`;
                
                $('#confirmationMessage').html(message);
                showModal();
            });
            
            // Confirm payment
            $('#confirmBtn').click(function() {
                hideModal();
                $('#loadingOverlay').fadeIn();
                
                // Simulate processing (replace with actual AJAX call)
                setTimeout(() => {
                    processPayment();
                }, 1500);
            });
            
            $('#cancelBtn').click(function() {
                hideModal();
            });
            
            function showModal() {
                $('#confirmationModal').fadeIn();
                setTimeout(() => {
                    $('#modalContent').css({
                        'transform': 'scale(1)',
                        'opacity': '1'
                    });
                }, 10);
            }
            
            function hideModal() {
                $('#modalContent').css({
                    'transform': 'scale(0.9)',
                    'opacity': '0'
                });
                setTimeout(() => {
                    $('#confirmationModal').fadeOut();
                }, 300);
            }
            
            function showToast(message) {
                $('#toast').text(message).css('opacity', '1');
                setTimeout(() => {
                    $('#toast').css('opacity', '0');
                }, 3000);
            }
            
            function processPayment() {
                const txId = $('#transactionId').val().trim();
                const amount = <?php echo $ramt; ?>;
                const serial = '<?php echo $serial; ?>';
                const upi = '<?php echo $upi_id; ?>';
                const userId = <?php echo $userId; ?>;
                const token = '<?php echo $shonusign; ?>';
                
                $.ajax({
                    type: "POST",
                    url: "adddeposit.php",
                    data: {
                        amt: amount,
                        refnum: txId,
                        srl: serial,
                        source: "usdt",
                        upi: upi,
                        userId: userId,
                        token: token
                    },
                    success: function(response) {
                        $('#loadingOverlay').fadeOut();
                        const arr = response.split('~');
                        
                        if (arr[0] == 1) {
                            window.location.href = 'usdtconfim.php?amt=' + amount + '&refnum=' + txId + '&srl=' + serial + "&userId=" + userId + "&token=" + token;
                        } else if(arr[0] == 0) {
                            showToast('Error processing payment');
                        } else if(arr[0] == 2) {
                            showToast('This transaction ID was already used');
                        } else if(arr[0] == 3) {
                            showToast('Please wait 1 minute before trying again');
                        } else if(arr[0] == 4) {
                            showToast('Service temporarily unavailable. Contact support');
                        }
                    },
                    error: function() {
                        $('#loadingOverlay').fadeOut();
                        showToast('Network error. Please try again');
                    }
                });
            }
            
            // Stop vibration when user starts typing
            $('#transactionId').on('input', function() {
                $(this).removeClass('vibrate');
            });
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