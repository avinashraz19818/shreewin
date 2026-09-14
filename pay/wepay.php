<?php include ("../serive/samparka.php");?>
<?php 
// Amount processing
if(isset($_GET['amount'])){
    $ramt = htmlspecialchars(mysqli_real_escape_string($conn, $_GET['amount']));
} else{
    $ramt = 0;
}
$dot_pos = strpos($ramt, '.');
if ($dot_pos === false) {
    $ramt = $ramt . '.00';
} else {
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
$serial = $date . $time . rand(100000, 999900);

$tyid = htmlspecialchars(mysqli_real_escape_string($conn, $_GET['tyid']));
$uid = htmlspecialchars(mysqli_real_escape_string($conn, $_GET['uid']));
$sign = htmlspecialchars(mysqli_real_escape_string($conn, $_GET['sign']));
$urlInfo = htmlspecialchars(mysqli_real_escape_string($conn, $_GET['urlInfo']));

// Gateway selection - default EasyPaisa
$gateway = isset($_GET['gateway']) ? $_GET['gateway'] : 'easypaisa';

// DATABASE SE ACTIVE PAYMENT PAIRS FETCH KARO
$payment_pairs = [];

// Gateway type ke according filter
$gateway_type = $gateway == 'easypaisa' ? 'easypaisa' : 'jazzcash';

// Active pairs fetch karo
$pair_query = "SELECT * FROM payment_pairs 
               WHERE gateway_type = '$gateway_type' AND status = '1' 
               ORDER BY RAND() LIMIT 1";

$pair_result = mysqli_query($conn, $pair_query);

if(mysqli_num_rows($pair_result) > 0) {
    $payment_pair = mysqli_fetch_assoc($pair_result);
    $gateway_number = $payment_pair['gateway_number'];
    $qr_filename = $payment_pair['qr_filename'];
    $pair_name = $payment_pair['pair_name'];
    $pair_id = $payment_pair['id'];
    
    // Full QR image path
    $qr_image_path = "../images/" . $qr_filename;
    
    // Check if file exists
    if(!file_exists($qr_image_path)) {
        // Agar QR image nahi hai to QR code generate karo
        $qr_data = $gateway == 'easypaisa' ? 
            "easypaisa://payment/?pa=" . $gateway_number . "&am=" . $ramt . "&tn=Payment_" . $serial :
            "jazzcash://payment/?pa=" . $gateway_number . "&am=" . $ramt . "&tn=Payment_" . $serial;
        $qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=" . urlencode($qr_data);
    } else {
        // Agar QR image file hai to use karo
        $qr_url = "https://luckywin28.buzz/images/" . $qr_filename;
    }
    
} else {
    // Agar koi active pair nahi hai to old method use karo
    $s_gateway = "SELECT maulya FROM deyya WHERE sthiti='1' LIMIT 1";
    $r_gateway = mysqli_query($conn, $s_gateway);
    $f_gateway = mysqli_fetch_array($r_gateway);
    $gateway_number = $f_gateway ? $f_gateway['maulya'] : '';
    
    // QR code generate karo
    $qr_data = $gateway == 'easypaisa' ? 
        "easypaisa://payment/?pa=" . $gateway_number . "&am=" . $ramt . "&tn=Payment_" . $serial :
        "jazzcash://payment/?pa=" . $gateway_number . "&am=" . $ramt . "&tn=Payment_" . $serial;
    $qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=" . urlencode($qr_data);
    
    $pair_name = "Default Account";
    $pair_id = 0;
}

// Gateway images URLs
$easy_image = 'https://luckywin28.buzz/pay/easy.png';
$jazz_image = 'https://luckywin28.buzz/pay/jazz.png';

// Transaction ID lengths - ab sirf digit count check hoga
$transaction_length = ($gateway == 'easypaisa') ? 11 : 12;
$placeholder = ($gateway == 'easypaisa') ? '03123456789' : '030012345678';
?>
<?php
    $res = [
        'code' => 405,
        'message' => 'Illegal access!',
    ];
    if (isset($_GET['tyid']) && isset($_GET['amount']) && isset($_GET['uid']) && isset($_GET['sign']) && isset($_GET['urlInfo'])) {
        $userId = $uid;
        $userPhoto = '1';
        
        $numquery = "SELECT mobile, codechorkamukala FROM shonu_subjects WHERE id = ".$userId;
        $numresult = $conn->query($numquery);
        $numarr = mysqli_fetch_array($numresult);
        
        $userName = '91'.$numarr['mobile'];
        $nickName = $numarr['codechorkamukala'];
        
        $creaquery = "SELECT createdate FROM shonu_subjects WHERE id = ".$userId;
        $crearesult = $conn->query($creaquery);
        $creaarr = mysqli_fetch_array($crearesult);
        
        $knbdstr = '{"userId":'.$userId.',"userPhoto":"'.$userPhoto.'","userName":'.$userName.',"nickName":"'.$nickName.'","createdate":"'.$creaarr['createdate'].'"}';
        $shonusign = strtoupper(hash('sha256', $knbdstr));
        
        $urlarr = explode (",", $urlInfo);
        $theirurl = $urlarr[0];
        $myurl = 'https://luckywin28.buzz/#/wallet/RechargeHistory';
        
        if($myurl){
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $gateway == 'easypaisa' ? 'EasyPaisa Payment' : 'JazzCash Payment'; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="assets/js/wepay/jquery-2.2.4.min.js"></script>
    <script src="assets/js/wepay/clipboard.min.js"></script>
    <script src="assets/js/wepay/layer.js"></script>
    <link rel="stylesheet" href="assets/css/wepay/layer.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        body {
            background: #f5f5f5;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 15px;
        }
        
        .payment-container {
            width: 100%;
            max-width: 400px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
            position: relative;
        }
        
        /* Small Timer in Top Right Corner */
        .small-timer {
            position: absolute;
            top: 10px;
            right: 10px;
            background: linear-gradient(135deg, #006837, #00a65a);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            z-index: 10;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .timer-expired {
            background: linear-gradient(135deg, #dc3545, #e74c3c);
        }
        
        .timer-warning {
            background: linear-gradient(135deg, #ff9800, #ff5722);
        }
        
        /* Header Section - Simple */
        .header {
            background: white;
            padding: 20px 15px;
            text-align: center;
            border-bottom: 1px solid #eee;
        }
        
        .payment-title {
            font-size: 22px;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        
        .payment-amount {
            font-size: 36px;
            font-weight: 700;
            color: #006837;
            margin: 10px 0;
        }
        
        .payment-label {
            font-size: 16px;
            color: #666;
        }
        
        /* Gateway Selector - Images as buttons */
        .gateway-selector {
            padding: 20px 15px;
            background: white;
            text-align: center;
        }
        
        .selector-title {
            font-size: 16px;
            color: #333;
            margin-bottom: 15px;
            font-weight: 500;
        }
        
        .gateway-images {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-bottom: 20px;
        }
        
        .gateway-image-btn {
            width: 140px;
            height: 60px;
            border: 2px solid #ddd;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background: white;
            padding: 5px;
        }
        
        .gateway-image-btn.active {
            border-color: #006837;
            box-shadow: 0 0 10px rgba(0, 104, 55, 0.2);
        }
        
        .gateway-image-btn img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        
        /* QR Code Section */
        .qr-section {
            text-align: center;
            padding: 20px 15px;
            background: #f8f9fa;
            margin: 0 15px;
            border-radius: 10px;
            border: 1px solid #e0e0e0;
        }
        
        .qr-title {
            font-size: 16px;
            color: #333;
            margin-bottom: 15px;
            font-weight: 500;
        }
        
        .qr-code {
            width: 200px;
            height: 200px;
            margin: 0 auto 15px;
            padding: 10px;
            background: white;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
        
        .qr-code img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        
        /* Account Number Section */
        .account-section {
            padding: 20px 15px;
            text-align: center;
        }
        
        .account-title {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }
        
        .account-number {
            font-size: 24px;
            font-weight: 600;
            color: #006837;
            margin: 15px 0;
            letter-spacing: 1px;
            font-family: monospace;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
        }
        
        .copy-btn {
            width: 200px;
            padding: 12px;
            background: #006837;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .copy-btn:hover {
            background: #00552c;
        }
        
        /* Transaction Section */
        .transaction-section {
            padding: 20px 15px;
        }
        
        .input-label {
            font-size: 14px;
            color: #666;
            margin-bottom: 8px;
            display: block;
            text-align: center;
        }
        
        .transaction-input {
            width: 100%;
            padding: 14px;
            font-size: 16px;
            border: 2px solid #006837;
            border-radius: 8px;
            text-align: center;
            background: white;
            color: #333;
            font-weight: 500;
            letter-spacing: 1px;
            margin-bottom: 15px;
            font-family: monospace;
        }
        
        .transaction-input:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 104, 55, 0.1);
        }
        
        /* Submit Button */
        .submit-btn {
            width: 100%;
            padding: 16px;
            background: #006837;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .submit-btn:hover {
            background: #00552c;
        }
        
        .submit-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        
        /* Notice Section */
        .notice-section {
            background: #fff8e1;
            padding: 15px;
            text-align: center;
            border-top: 1px solid #ffe58f;
            font-size: 13px;
            color: #333;
            line-height: 1.4;
        }
        
        /* Serial Info */
        .serial-info {
            text-align: center;
            padding: 12px;
            background: #f8f9fa;
            border-top: 1px solid #e0e0e0;
            font-size: 12px;
            color: #666;
        }
        
        /* Loading */
        .loading {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.95);
            z-index: 1000;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        
        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid #006837;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 20px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Digit Counter */
        .digit-counter {
            font-size: 12px;
            color: #666;
            text-align: center;
            margin-top: -10px;
            margin-bottom: 10px;
        }
        
        /* Mobile Responsive */
        @media (max-width: 480px) {
            .payment-container {
                max-width: 100%;
                border-radius: 8px;
            }
            
            .payment-amount {
                font-size: 32px;
            }
            
            .gateway-image-btn {
                width: 120px;
                height: 50px;
            }
            
            .qr-code {
                width: 180px;
                height: 180px;
            }
            
            .account-number {
                font-size: 20px;
            }
            
            .small-timer {
                font-size: 11px;
                padding: 5px 10px;
            }
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <!-- Small Timer in Top Right Corner -->
        <div class="small-timer" id="timerSection">
            <i class="fas fa-clock"></i>
            <span id="timerDisplay">02:00</span>
        </div>
        
        <!-- Header Section -->
        <div class="header">
            <div class="payment-title">Payment</div>
            <div class="payment-amount"><?php echo $ramt; ?> PKR</div>
            <div class="payment-label">Amount</div>
        </div>
        
        <!-- Gateway Selector - Images as buttons -->
        <div class="gateway-selector">
            <div class="selector-title">Select Payment Type - ادائیگی کی قسم منتخب کریں۔</div>
            
            <div class="gateway-images">
                <a href="?gateway=easypaisa&amount=<?php echo $ramt; ?>&tyid=<?php echo $tyid; ?>&uid=<?php echo $uid; ?>&sign=<?php echo $sign; ?>&urlInfo=<?php echo $urlInfo; ?>" 
                   class="gateway-image-btn <?php echo $gateway == 'easypaisa' ? 'active' : ''; ?>">
                    <img src="<?php echo $easy_image; ?>" alt="EasyPaisa">
                </a>
                
                <a href="?gateway=jazzcash&amount=<?php echo $ramt; ?>&tyid=<?php echo $tyid; ?>&uid=<?php echo $uid; ?>&sign=<?php echo $sign; ?>&urlInfo=<?php echo $urlInfo; ?>" 
                   class="gateway-image-btn <?php echo $gateway == 'jazzcash' ? 'active' : ''; ?>">
                    <img src="<?php echo $jazz_image; ?>" alt="JazzCash">
                </a>
            </div>
        </div>
        
        <!-- QR Code Section -->
        <div class="qr-section">
            <div class="qr-title">Scan QR Code to Pay</div>
            <div class="qr-code">
                <img src="<?php echo $qr_url; ?>" alt="<?php echo $gateway == 'easypaisa' ? 'EasyPaisa QR Code' : 'JazzCash QR Code'; ?>">
            </div>
            <div style="font-size: 13px; color: #666;">
                Scan with <?php echo $gateway == 'easypaisa' ? 'EasyPaisa' : 'JazzCash'; ?> app
            </div>
        </div>
        
        <!-- Account Number Section -->
        <div class="account-section">
            <div class="account-title">Wallet account number - والیت کاٹاؤ نمبر</div>
            <div class="account-number" id="accountNumber">
                <?php echo $gateway_number; ?>
            </div>
            <button class="copy-btn" id="copyBtn">
                <i class="far fa-copy"></i>
                Copy <?php echo $gateway == 'easypaisa' ? 'EasyPaisa' : 'JazzCash'; ?> Number
            </button>
        </div>
        
        <!-- Transaction ID Section -->
        <div class="transaction-section">
            <label class="input-label">Enter Transaction ID</label>
            <div class="digit-counter" id="digitCounter">Enter <?php echo $transaction_length; ?> digits</div>
            <input type="text" 
                   class="transaction-input" 
                   id="transactionId"
                   placeholder="<?php echo $placeholder; ?>"
                   maxlength="<?php echo $transaction_length; ?>"
                   pattern="[0-9]*"
                   inputmode="numeric"
                   oninput="validateTransactionId(this)"
                   required>
            
            <!-- Submit Button -->
            <button class="submit-btn" id="submitBtn">
                <i class="fas fa-paper-plane"></i>
                Pay
            </button>
        </div>
        
        <!-- Notice Section -->
        <div class="notice-section">
            <div style="margin-bottom: 5px;">
                Please submit your order and payment for <?php echo $gateway == 'easypaisa' ? 'EasyPaisa' : 'JazzCash'; ?> within 120s.
            </div>
            <div style="direction: rtl; font-size: 12px; color: #666;">
                براہ کرم اپنا آرڈر اور <?php echo $gateway == 'easypaisa' ? 'ایزی پیسا' : 'جاز کیش'; ?> کے ذریعے ادائیگی 120 سیکنڈ کے اندر جمع کر دیں۔
            </div>
        </div>
        
        <!-- Serial Info -->
        <div class="serial-info">
            Order ID: <?php echo $serial; ?>
        </div>
    </div>
    
    <!-- Loading Overlay -->
    <div class="loading" id="loadingOverlay" style="display: none;">
        <div class="spinner"></div>
        <div style="font-size: 15px; color: #333; margin-top: 15px;">Processing your payment...</div>
    </div>
    
    <script>
        // Variables
        const ramt = <?php echo $ramt; ?>;
        const serial = '<?php echo $serial; ?>';
        const gateway = '<?php echo $gateway; ?>';
        const gatewayNumber = '<?php echo $gateway_number; ?>';
        const userId = <?php echo $userId; ?>;
        const token = '<?php echo $shonusign; ?>';
        const transactionLength = <?php echo $transaction_length; ?>;
        const pairId = <?php echo $pair_id; ?>;
        
        // Timer Variables
        let timerInterval;
        let timeLeft = 120; // 120 seconds = 2 minutes
        let timerActive = true;
        
        // Unique session key for localStorage
        const sessionKey = `payment_timer_${userId}_${serial}`;
        
        // Initialize Timer from localStorage or start new
        function initializeTimer() {
            const savedTimer = localStorage.getItem(sessionKey);
            
            if(savedTimer) {
                const timerData = JSON.parse(savedTimer);
                const elapsedSeconds = Math.floor((Date.now() - timerData.startTime) / 1000);
                timeLeft = Math.max(0, timerData.timeLeft - elapsedSeconds);
                
                if(timeLeft <= 0) {
                    // Timer expired in previous session
                    timerExpired();
                    return;
                }
            } else {
                // Start new timer
                const timerData = {
                    startTime: Date.now(),
                    timeLeft: 120,
                    userId: userId,
                    serial: serial
                };
                localStorage.setItem(sessionKey, JSON.stringify(timerData));
            }
            
            // Start timer
            startTimer();
        }
        
        // Start Timer Function
        function startTimer() {
            updateTimerDisplay();
            
            timerInterval = setInterval(() => {
                if(timeLeft > 0) {
                    timeLeft--;
                    updateTimerDisplay();
                    updateTimerStorage();
                    
                    // Visual warnings
                    if(timeLeft <= 30) {
                        document.getElementById('timerSection').classList.add('timer-warning');
                    }
                    
                    if(timeLeft <= 10) {
                        document.getElementById('timerSection').classList.remove('timer-warning');
                        document.getElementById('timerSection').classList.add('timer-expired');
                    }
                } else {
                    timerExpired();
                }
            }, 1000);
        }
        
        // Update Timer Display
        function updateTimerDisplay() {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            const timerDisplay = document.getElementById('timerDisplay');
            
            timerDisplay.textContent = 
                minutes.toString().padStart(2, '0') + ':' + 
                seconds.toString().padStart(2, '0');
        }
        
        // Update Timer in localStorage
        function updateTimerStorage() {
            const timerData = {
                startTime: Date.now(),
                timeLeft: timeLeft,
                userId: userId,
                serial: serial
            };
            localStorage.setItem(sessionKey, JSON.stringify(timerData));
        }
        
        // Timer Expired Function
        function timerExpired() {
            clearInterval(timerInterval);
            timerActive = false;
            
            // Update UI
            document.getElementById('timerDisplay').textContent = '00:00';
            
            // Disable submit button
            document.getElementById('submitBtn').disabled = true;
            document.getElementById('submitBtn').innerHTML = '<i class="fas fa-clock"></i> Time Expired';
            
            // Show expired message
            layer.msg('Payment time expired! Please refresh the page.', {
                icon: 2,
                time: 3000
            });
            
            // Auto-refresh after 3 seconds
            setTimeout(() => {
                window.location.reload();
            }, 3000);
        }
        
        // Transaction ID validation function - NO 03 START VALIDATION
        function validateTransactionId(input) {
            let value = input.value.replace(/[^0-9]/g, '');
            
            // Restrict to max length
            if(value.length > transactionLength) {
                value = value.slice(0, transactionLength);
            }
            
            // Update input value
            input.value = value;
            
            // Update digit counter
            const counter = document.getElementById('digitCounter');
            if(counter) {
                if(value.length === 0) {
                    counter.textContent = `Enter ${transactionLength} digits`;
                    counter.style.color = '#666';
                } else {
                    counter.textContent = `${value.length}/${transactionLength} digits`;
                    counter.style.color = value.length === transactionLength ? '#006837' : '#ff9800';
                }
            }
        }
        
        // Copy Account Number
        document.getElementById('copyBtn').addEventListener('click', function() {
            const accountNumber = document.getElementById('accountNumber').textContent.trim();
            navigator.clipboard.writeText(accountNumber).then(() => {
                layer.msg('Number copied!', {time: 1500});
            }).catch(err => {
                layer.msg('Copy failed. Please copy manually.', {time: 1500});
            });
        });
        
        // Submit Payment - SIMPLE POPUP VERSION
        document.getElementById('submitBtn').addEventListener('click', function() {
            // Check if timer expired
            if(!timerActive || timeLeft <= 0) {
                layer.msg('Payment time expired! Please refresh the page.', {icon: 2, time: 3000});
                return;
            }
            
            const transactionInput = document.getElementById('transactionId');
            const transactionId = transactionInput.value.trim();
            
            if (!transactionId) {
                layer.msg('Please enter Transaction ID', {time: 1500});
                transactionInput.focus();
                return;
            }
            
            // Check length
            if(transactionId.length !== transactionLength) {
                layer.msg(`Transaction ID must be exactly ${transactionLength} digits`, {time: 1500});
                transactionInput.focus();
                return;
            }
            
            // Check if all digits
            if(!/^\d+$/.test(transactionId)) {
                layer.msg('Transaction ID must contain only numbers', {time: 1500});
                transactionInput.focus();
                return;
            }
            
            // Show confirmation
            layer.confirm(
                '<div style="text-align: center; padding: 20px;">' +
                '<div style="margin-bottom: 15px;">' +
                '<div style="font-size: 14px; color: #666; margin-bottom: 5px;">Amount:</div>' +
                '<div style="font-size: 24px; font-weight: 700; color: #006837;">PKR ' + ramt + '</div>' +
                '</div>' +
                '<div style="margin-bottom: 15px;">' +
                '<div style="font-size: 14px; color: #666; margin-bottom: 5px;">Method:</div>' +
                '<div style="font-size: 16px; font-weight: 600; color: #333;">' + (gateway === 'easypaisa' ? 'EasyPaisa' : 'JazzCash') + '</div>' +
                '</div>' +
                '<div style="margin-bottom: 20px;">' +
                '<div style="font-size: 14px; color: #666; margin-bottom: 5px;">Transaction ID:</div>' +
                '<div style="font-size: 18px; font-weight: 600; color: #333; font-family: monospace;">' + transactionId + '</div>' +
                '</div>' +
                '</div>',
                {
                    title: 'Confirm Payment',
                    btn: ['Confirm', 'Cancel'],
                    btnAlign: 'c',
                    area: ['350px', 'auto']
                },
                function() {
                    submitPayment(transactionId);
                }
            );
        });
        
        function submitPayment(transactionId) {
            // Stop timer
            clearInterval(timerInterval);
            timerActive = false;
            
            // Remove timer from localStorage
            localStorage.removeItem(sessionKey);
            
            document.getElementById('loadingOverlay').style.display = 'flex';
            
            $.ajax({
                type: "POST",
                url: "adddeposit.php",
                data: {
                    amt: ramt,
                    refnum: transactionId,
                    srl: serial,
                    gatewayId: gatewayNumber,
                    gateway: gateway,
                    userId: userId,
                    token: token,
                    source: "PK-Payment",
                    pairId: pairId  // Pair ID bhi send karo for tracking
                },
                success: function(response) {
                    document.getElementById('loadingOverlay').style.display = 'none';
                    
                    const arr = response.split('~');
                    
                    if (arr[0] == 1) {
                        // SUCCESS - SHOW POPUP ONLY
                        layer.open({
                            type: 1,
                            title: '<i class="fas fa-check-circle" style="color: #006837;"></i> Payment Submitted Successfully',
                            closeBtn: 1,
                            area: ['400px', 'auto'],
                            shadeClose: false,
                            content: '<div style="padding: 30px; text-align: center;">' +
                                     '<div style="margin-bottom: 20px;">' +
                                     '<i class="fas fa-check-circle" style="font-size: 60px; color: #006837; margin-bottom: 15px;"></i>' +
                                     '<h3 style="color: #006837; margin-bottom: 10px;">Your Payment Request Submitted</h3>' +
                                     '</div>' +
                                     
                                     '<div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: left;">' +
                                     '<div style="margin-bottom: 10px;"><strong>Amount:</strong> PKR ' + ramt + '</div>' +
                                     '<div style="margin-bottom: 10px;"><strong>Transaction ID:</strong> ' + transactionId + '</div>' +
                                     '<div style="margin-bottom: 10px;"><strong>Order ID:</strong> ' + serial + '</div>' +
                                     '<div><strong>Method:</strong> ' + (gateway === 'easypaisa' ? 'EasyPaisa' : 'JazzCash') + '</div>' +
                                     '</div>' +
                                     
                                     '<div style="background: #e8f5e8; padding: 15px; border-radius: 8px; margin-bottom: 20px;">' +
                                     '<h4 style="color: #006837; margin-bottom: 10px;"><i class="fas fa-info-circle"></i> What happens next?</h4>' +
                                     '<p style="margin-bottom: 8px; font-size: 14px;">✓ Payment will be automatically added to your account</p>' +
                                     '<p style="margin-bottom: 8px; font-size: 14px;">✓ Admin will verify within 5-10 minutes</p>' +
                                     '<p style="font-size: 14px;">✓ You can check wallet balance</p>' +
                                     '</div>' +
                                     
                                     '<div style="background: #fff8e1; padding: 15px; border-radius: 8px; margin-bottom: 20px;">' +
                                     '<p style="font-size: 13px; margin-bottom: 5px;">پیلیز انتظار کریں، آپ کی ادائیگی خودکار طریقے سے آپ کے اکاؤنٹ میں شامل ہو جائے گی۔</p>' +
                                     '<p style="font-size: 13px;">منتظم 5-10 منٹ میں تصدیق کرے گا، آپ والیٹ بیلنس چیک کر سکتے ہیں۔</p>' +
                                     '</div>' +
                                     
                                     '<div style="display: flex; gap: 10px; justify-content: center;">' +
                                     '<button class="btn btn-primary" onclick="layer.closeAll(); window.location.href=\'https://luckywin28.buzz/#/wallet/RechargeHistory\'" style="padding: 10px 20px; background: #006837; color: white; border: none; border-radius: 5px; cursor: pointer;">' +
                                     '<i class="fas fa-wallet"></i> Go to Wallet' +
                                     '</button>' +
                                     '<button class="btn btn-secondary" onclick="layer.closeAll(); window.history.back();" style="padding: 10px 20px; background: #6c757d; color: white; border: none; border-radius: 5px; cursor: pointer;">' +
                                     '<i class="fas fa-redo"></i> Make Another Payment' +
                                     '</button>' +
                                     '</div>' +
                                     '</div>',
                            btn: ['Close'],
                            btnAlign: 'c',
                            yes: function(index) {
                                layer.close(index);
                            }
                        });
                        
                    } else if(arr[0] == 2) {
                        layer.msg('This Transaction ID is already used!', {icon: 2, time: 2000});
                        // Restart timer since payment failed
                        initializeTimer();
                    } else if(arr[0] == 3) {
                        layer.msg('Please wait 1 minute!', {icon: 2, time: 2000});
                        initializeTimer();
                    } else if(arr[0] == 4) {
                        layer.msg('Payment method unavailable!', {icon: 2, time: 2000});
                        initializeTimer();
                    } else if(arr[0] == 5) {
                        layer.msg('Transaction ID must be ' + transactionLength + ' digits', {icon: 2, time: 2000});
                        initializeTimer();
                    } else if(arr[0] == 6) {
                        layer.msg('Only numbers allowed!', {icon: 2, time: 2000});
                        initializeTimer();
                    } else {
                        layer.msg('Error! Please try again.', {icon: 2, time: 2000});
                        initializeTimer();
                    }
                },
                error: function() {
                    document.getElementById('loadingOverlay').style.display = 'none';
                    layer.msg('Network error! Try again.', {icon: 2, time: 2000});
                    // Restart timer on network error
                    initializeTimer();
                }
            });
        }
        
        // Auto focus on transaction ID input
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize timer
            initializeTimer();
            
            setTimeout(() => {
                const input = document.getElementById('transactionId');
                input.focus();
                validateTransactionId(input);
            }, 300);
            
            // Cleanup old timers on page load
            cleanupOldTimers();
        });
        
        // Cleanup old timers from localStorage
        function cleanupOldTimers() {
            const oneHourAgo = Date.now() - (60 * 60 * 1000); // 1 hour ago
            
            for(let i = 0; i < localStorage.length; i++) {
                const key = localStorage.key(i);
                if(key && key.startsWith('payment_timer_')) {
                    try {
                        const timerData = JSON.parse(localStorage.getItem(key));
                        if(timerData.startTime && timerData.startTime < oneHourAgo) {
                            localStorage.removeItem(key);
                        }
                    } catch(e) {
                        localStorage.removeItem(key);
                    }
                }
            }
        }
        
        // Show payment instructions
        setTimeout(() => {
            layer.alert(
                '<div style="text-align: center; padding: 15px;">' +
                '<div style="margin-bottom: 15px; font-size: 16px; color: #006837; font-weight: 600;">Payment Instructions</div>' +
                '<div style="text-align: left; font-size: 14px; line-height: 1.6;">' +
                '<div>1. Click on the QR code image to save</div>' +
                '<div>2. Open <?php echo $gateway == 'easypaisa' ? 'EasyPaisa' : 'JazzCash'; ?> app and scan QR</div>' +
                '<div>3. Or send <strong>PKR ' + ramt + '</strong> to the number above</div>' +
                '<div>4. Enter ' + transactionLength + ' digit Transaction ID</div>' +
                '<div>5. Click <strong>Pay</strong> button to submit within 2 minutes</div>' +
                '</div>' +
                '</div>',
                {
                    title: false,
                    btn: ['Got It'],
                    area: ['380px', 'auto']
                }
            );
        }, 600);
        
        // Handle page unload - cleanup timer
        window.addEventListener('beforeunload', function() {
            // Don't cleanup if payment was submitted
            if(timerActive && timeLeft > 0) {
                // Keep timer in localStorage
                updateTimerStorage();
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
?>