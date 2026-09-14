<?php
// Database connection
include("../serive/samparka.php");

// Define your UPI IDs array (add more as needed)
$upi_ids = [
    'nedigitalgateway@indianbk',
    'payment2@ybl',
    'payment3@ybl',
    'payment4@ybl',
    'payment5@ybl',
    'payment6@ybl',
    'payment7@ybl'
];

// Improved UPI ID rotation with session tracking
function getSessionUpiId($upi_ids) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start([
            'cookie_lifetime' => 86400,
            'read_and_close'  => false
        ]);
    }
    
    if (!isset($_SESSION['upi_rotation'])) {
        $_SESSION['upi_rotation'] = [
            'index' => 0,
            'last_used' => null,
            'used_ids' => []
        ];
    }
    
    if (count($_SESSION['upi_rotation']['used_ids']) >= count($upi_ids)) {
        $_SESSION['upi_rotation']['used_ids'] = [];
    }
    
    $attempts = 0;
    $max_attempts = count($upi_ids) * 2;
    
    do {
        $_SESSION['upi_rotation']['index'] = ($_SESSION['upi_rotation']['index'] + 1) % count($upi_ids);
        $current_upi = $upi_ids[$_SESSION['upi_rotation']['index']];
        $attempts++;
        
        if ($attempts >= $max_attempts) {
            break;
        }
    } while (in_array($current_upi, $_SESSION['upi_rotation']['used_ids']));
    
    $_SESSION['upi_rotation']['last_used'] = $current_upi;
    $_SESSION['upi_rotation']['used_ids'][] = $current_upi;
    
    if (count($_SESSION['upi_rotation']['used_ids']) > count($upi_ids) * 0.7) {
        array_shift($_SESSION['upi_rotation']['used_ids']);
    }
    
    return $current_upi;
}

// Security: Validate and sanitize inputs
function sanitizeInput($conn, $input) {
    return htmlspecialchars(mysqli_real_escape_string($conn, $input));
}

// Get amount from URL parameter
$ramt = isset($_GET['amount']) ? sanitizeInput($conn, $_GET['amount']) : 0;

// Format amount with 2 decimal places
function formatAmount($amount) {
    $amount = preg_replace('/[^0-9.]/', '', $amount);
    $dot_pos = strpos($amount, '.');
    
    if ($dot_pos === false) {
        return $amount . '.00';
    }
    
    $after_dot = substr($amount, $dot_pos + 1);
    $after_dot_length = strlen($after_dot);
    
    if ($after_dot_length > 2) {
        $after_dot = substr($after_dot, 0, 2);
        return substr($amount, 0, $dot_pos + 1) . $after_dot;
    } elseif ($after_dot_length < 2) {
        $zeros_to_add = 2 - $after_dot_length;
        return $amount . str_repeat('0', $zeros_to_add);
    }
    
    return $amount;
}

$ramt = formatAmount($ramt);

// Generate unique transaction ID
function generateTransactionId() {
    return 'TXN' . date("YmdHis") . rand(1000,9999);
}

$serial = generateTransactionId();

// Get other parameters
$tyid = isset($_GET['tyid']) ? sanitizeInput($conn, $_GET['tyid']) : '';
$uid = isset($_GET['uid']) ? sanitizeInput($conn, $_GET['uid']) : '';
$sign = isset($_GET['sign']) ? sanitizeInput($conn, $_GET['sign']) : '';
$urlInfo = isset($_GET['urlInfo']) ? sanitizeInput($conn, $_GET['urlInfo']) : '';

// Function to generate QR code URL
function generateQrCodeUrl($upiId, $amount, $name = "Payment") {
    $upiUri = rawurlencode("upi://pay?pa=$upiId&pn=$name&am=$amount&cu=INR&tn=Payment");
    return "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=$upiUri&margin=15&bgcolor=f8f9fa&color=487ef5";
}

// Get UPI ID for this session
$upi_id = getSessionUpiId($upi_ids);
$qr_code_url = generateQrCodeUrl($upi_id, $ramt);

// Get UPI app logos
$select_upi_apps = mysqli_query($conn, "SELECT * FROM `images` WHERE `status`=1");
$upi_apps = [];
while($app = mysqli_fetch_array($select_upi_apps)) {
    $upi_apps[] = $app;
}

// Verify request signature
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
    
    $urlarr = explode(",", $urlInfo);
    $theirurl = $urlarr[0];
    $myurl = 'https://luckywin28.buzz';
    
    if($shonusign == $sign && $theirurl == $myurl) {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UPI Payment Gateway</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4a6bff;
            --secondary: #6c5ce7;
            --success: #00b894;
            --warning: #fdcb6e;
            --danger: #d63031;
            --dark: #2d3436;
            --light: #f5f6fa;
            --gray: #636e72;
            --bg: #ffffff;
            --card-shadow: 0 10px 30px rgba(0,0,0,0.08);
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--light);
            color: var(--dark);
            line-height: 1.6;
        }
        
        .container {
            max-width: 420px;
            margin: 20px auto;
            padding: 0 15px;
        }
        
        .payment-card {
            background: var(--bg);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
            margin-bottom: 20px;
            position: relative;
        }
        
        .payment-header {
            padding: 25px;
            text-align: center;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            position: relative;
        }
        
        .payment-amount {
            font-size: 36px;
            font-weight: 700;
            margin: 10px 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .payment-amount span {
            margin-right: 5px;
        }
        
        .payment-time {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(255,255,255,0.2);
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .payment-body {
            padding: 25px;
        }
        
        .section-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 15px;
            position: relative;
            padding-left: 25px;
        }
        
        .section-title:before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 18px;
            height: 18px;
            background: var(--primary);
            border-radius: 4px;
        }
        
        .upi-id-container {
            background: rgba(74, 107, 255, 0.05);
            border: 1px solid rgba(74, 107, 255, 0.1);
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 20px;
            position: relative;
        }
        
        .upi-id-label {
            font-size: 12px;
            color: var(--gray);
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .upi-id-value {
            font-family: 'Courier New', monospace;
            font-size: 16px;
            font-weight: 600;
            color: var(--dark);
            word-break: break-all;
        }
        
        .copy-btn {
            position: absolute;
            right: 15px;
            top: 15px;
            background: rgba(74, 107, 255, 0.1);
            border: none;
            width: 30px;
            height: 30px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: var(--primary);
            transition: var(--transition);
        }
        
        .copy-btn:hover {
            background: rgba(74, 107, 255, 0.2);
        }
        
        .payment-methods {
            margin: 25px 0;
        }
        
        .method-title {
            font-size: 14px;
            color: var(--gray);
            margin-bottom: 15px;
            text-align: center;
            font-weight: 500;
        }
        
        .method-list {
            display: flex;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .method-item {
            width: 70px;
            height: 70px;
            background: white;
            border-radius: 15px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            cursor: pointer;
            transition: var(--transition);
            border: 1px solid rgba(0,0,0,0.05);
        }
        
        .method-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .method-icon {
            width: 35px;
            height: 35px;
            margin-bottom: 5px;
        }
        
        .method-name {
            font-size: 10px;
            font-weight: 500;
            color: var(--dark);
        }
        
        .divider {
            height: 1px;
            background: rgba(0,0,0,0.05);
            margin: 25px 0;
            position: relative;
        }
        
        .divider:after {
            content: 'OR';
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            background: var(--bg);
            padding: 0 10px;
            font-size: 12px;
            color: var(--gray);
        }
        
        .qr-container {
            text-align: center;
            margin: 20px 0;
        }
        
        .qr-code {
            width: 200px;
            height: 200px;
            margin: 0 auto;
            background: white;
            padding: 15px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            border: 1px solid rgba(0,0,0,0.05);
        }
        
        .qr-code img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        
        .download-btn {
            display: inline-block;
            margin-top: 15px;
            font-size: 12px;
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }
        
        .utr-section {
            margin-top: 25px;
        }
        
        .input-group {
            margin-bottom: 20px;
            position: relative;
        }
        
        .input-label {
            display: block;
            font-size: 12px;
            color: var(--gray);
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .input-field {
            width: 100%;
            padding: 15px;
            border: 1px solid rgba(0,0,0,0.1);
            border-radius: 12px;
            font-size: 14px;
            font-family: 'Courier New', monospace;
            outline: none;
            transition: var(--transition);
            background: rgba(0,0,0,0.02);
        }
        
        .input-field:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(74, 107, 255, 0.1);
        }
        
        .submit-btn {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 12px;
            background: var(--primary);
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .submit-btn:hover {
            background: #3a5bed;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(74, 107, 255, 0.3);
        }
        
        .submit-btn i {
            margin-right: 8px;
        }
        
        .security-badge {
            text-align: center;
            margin-top: 25px;
            font-size: 12px;
            color: var(--gray);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .security-badge i {
            color: var(--success);
            margin-right: 5px;
        }
        
        /* Premium Modal */
        .premium-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            z-index: 9999;
            display: none;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(5px);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .premium-modal.active {
            opacity: 1;
        }
        
        .premium-modal-content {
            background: white;
            border-radius: 20px;
            width: 90%;
            max-width: 400px;
            overflow: hidden;
            transform: translateY(20px);
            transition: transform 0.3s ease;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }
        
        .premium-modal.active .premium-modal-content {
            transform: translateY(0);
        }
        
        .premium-modal-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 20px;
            text-align: center;
            position: relative;
        }
        
        .premium-modal-header h3 {
            font-size: 18px;
            font-weight: 600;
            margin: 0;
        }
        
        .premium-modal-body {
            padding: 25px;
        }
        
        .payment-details {
            margin-bottom: 20px;
        }
        
        .payment-detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        
        .payment-detail-label {
            font-weight: 500;
            color: var(--gray);
            font-size: 14px;
        }
        
        .payment-detail-value {
            font-weight: 600;
            color: var(--dark);
            font-size: 14px;
        }
        
        .premium-modal-footer {
            display: flex;
            gap: 15px;
            padding: 0 25px 25px;
        }
        
        .premium-modal-btn {
            flex: 1;
            border: none;
            border-radius: 12px;
            padding: 12px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .premium-modal-btn i {
            margin-right: 8px;
        }
        
        .premium-modal-btn-cancel {
            background: #f1f3f5;
            color: var(--gray);
        }
        
        .premium-modal-btn-cancel:hover {
            background: #e9ecef;
        }
        
        .premium-modal-btn-confirm {
            background: var(--success);
            color: white;
        }
        
        .premium-modal-btn-confirm:hover {
            background: #00a884;
            box-shadow: 0 5px 15px rgba(0, 184, 148, 0.3);
        }
        
        /* Timer */
        .timer-container {
            background: rgba(214, 48, 49, 0.1);
            padding: 10px;
            border-radius: 12px;
            text-align: center;
            margin: 20px 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .timer-icon {
            color: var(--danger);
            margin-right: 8px;
        }
        
        .timer-text {
            font-size: 14px;
            font-weight: 500;
            color: var(--danger);
        }
        
        /* Success Message */
        .success-message {
            display: none;
            background: rgba(0, 184, 148, 0.1);
            color: var(--success);
            padding: 15px;
            border-radius: 12px;
            margin-top: 15px;
            text-align: center;
            font-weight: 500;
            font-size: 14px;
        }
        
        .success-message i {
            margin-right: 8px;
        }
        
        /* Animation */
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .pulse {
            animation: pulse 2s infinite;
        }
        
        @media (max-width: 480px) {
            .container {
                padding: 0 10px;
            }
            
            .payment-header {
                padding: 20px;
            }
            
            .payment-amount {
                font-size: 32px;
            }
            
            .method-item {
                width: 60px;
                height: 60px;
            }
            
            .method-icon {
                width: 30px;
                height: 30px;
            }
            
            .qr-code {
                width: 180px;
                height: 180px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="payment-card">
            <div class="payment-header">
                <div class="payment-time" id="countdown">14:48</div>
                <div class="payment-amount">
                    <span>₹</span>
                    <span class="money"><?php echo $ramt; ?></span>
                </div>
            </div>
            
            <div class="payment-body">
                <div class="section-title">UPI ID</div>
                
                <div class="upi-id-container">
                    <div class="upi-id-label">Payment UPI Address</div>
                    <div class="upi-id-value" id="upi"><?php echo $upi_id; ?></div>
                    <button class="copy-btn" id="btncopy">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
                
                <div class="timer-container">
                    <i class="fas fa-clock timer-icon"></i>
                    <span class="timer-text">Complete payment within: <span id="timer">05:00</span></span>
                </div>
                
                <div class="payment-methods">
                    <div class="method-title">Recommended payment methods</div>
                    <div class="method-list">
                        <?php foreach($upi_apps as $app): ?>
                            <div class="method-item" onclick="openUpiApp('<?php echo $upi_id; ?>', '<?php echo $ramt; ?>', '<?php echo $app['name']; ?>')">
                                <img src="https://indiadesignsystem.bombaydc.com/assets/india-designs/display/UPI/black.svg" class="method-icon">
                                <span class="method-name"><?php echo $app['name']; ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="divider"></div>
                
                <div class="qr-container">
                    <div class="qr-code pulse">
                        <img src="<?php echo $qr_code_url; ?>" alt="UPI QR Code">
                    </div>
                    <a href="<?php echo $qr_code_url; ?>" download="UPI-Payment-QR" class="download-btn">
                        <i class="fas fa-download"></i> Download QR Code
                    </a>
                </div>
                
                <div class="utr-section">
                    <div class="section-title">Submit Transaction Reference (UTR)</div>
                    
                    <div class="input-group">
                        <label class="input-label">Enter 12-digit UTR number</label>
                        <input type="text" class="input-field" id="refno" placeholder="XXXXXXXXXXXX" minlength="12" maxlength="12">
                    </div>
                    
                    <button class="submit-btn" id="savebtn">
                        <i class="fas fa-check-circle"></i> Verify Payment
                    </button>
                    
                    <div class="success-message" id="successMessage">
                        <i class="fas fa-check-circle"></i> Payment verified successfully! Redirecting...
                    </div>
                </div>
                
                <div class="security-badge">
                    <i class="fas fa-shield-alt"></i> 100% Secure Payments Powered by UPI
                </div>
            </div>
        </div>
    </div>
    
    <!-- Premium Confirmation Modal -->
    <div class="premium-modal" id="premiumModal">
        <div class="premium-modal-content">
            <div class="premium-modal-header">
                <h3>Confirm Payment Details</h3>
            </div>
            <div class="premium-modal-body">
                <div class="payment-details">
                    <div class="payment-detail-row">
                        <span class="payment-detail-label">UPI ID:</span>
                        <span class="payment-detail-value" id="modalUpiId"><?php echo $upi_id; ?></span>
                    </div>
                    <div class="payment-detail-row">
                        <span class="payment-detail-label">Amount:</span>
                        <span class="payment-detail-value" id="modalAmount">₹<?php echo $ramt; ?></span>
                    </div>
                    <div class="payment-detail-row">
                        <span class="payment-detail-label">UTR Number:</span>
                        <span class="payment-detail-value" id="modalUtr"></span>
                    </div>
                </div>
                <p style="text-align: center; color: var(--gray); font-size: 13px;">
                    Please verify all details before confirming the payment.
                </p>
            </div>
            <div class="premium-modal-footer">
                <button class="premium-modal-btn premium-modal-btn-cancel" id="modalCancelBtn">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button class="premium-modal-btn premium-modal-btn-confirm" id="modalConfirmBtn">
                    <i class="fas fa-check"></i> Confirm
                </button>
            </div>
        </div>
    </div>
    
    <!-- Floating Loader -->
    <div class="floating-loader" id="floatingLoader">
        <div class="loader-content" id="loaderContent">
            <div class="loader-spinner"></div>
            <div class="loader-text">Processing your payment...</div>
            <div class="progress-bar" style="height: 4px; background: #eee; border-radius: 2px; overflow: hidden;">
                <div id="progressBar" style="height: 100%; width: 0%; background: var(--primary); transition: width 0.3s ease;"></div>
            </div>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.8/clipboard.min.js"></script>
    <script>
        // Variables
        const ramt = <?php echo $ramt; ?>;
        const serial = '<?php echo $serial; ?>';
        const upi = document.getElementById("upi").innerHTML;
        const userId = <?php echo $userId; ?>;
        const token = '<?php echo $shonusign; ?>';
        let timer;
        let timeLeft = 300; // 5 minutes in seconds
        
        // Initialize clipboard
        new ClipboardJS("#btncopy", {
            text: () => document.getElementById("upi").innerText
        }).on("success", () => {
            showAlert("UPI ID copied to clipboard!", "success");
        }).on("error", () => {
            showAlert("Failed to copy. Please copy manually.", "error");
        });
        
        // Start countdown timer
        startTimer();
        
        // Function to open UPI app
        function openUpiApp(upiId, amount, appName) {
            const apps = {
                'google pay': `tez://upi/pay?pa=${upiId}&pn=Payment&am=${amount}&cu=INR&tn=Payment`,
                'phonepe': `phonepe://upi/pay?pa=${upiId}&pn=Payment&am=${amount}&cu=INR&tn=Payment`,
                'paytm': `paytmmp://upi/pay?pa=${upiId}&pn=Payment&am=${amount}&cu=INR&tn=Payment`,
                'bhim': `bhim://upi/pay?pa=${upiId}&pn=Payment&am=${amount}&cu=INR&tn=Payment`,
                'amazon pay': `amazonpay://upi/pay?pa=${upiId}&pn=Payment&am=${amount}&cu=INR&tn=Payment`
            };
            
            const url = apps[appName.toLowerCase()] || `upi://pay?pa=${upiId}&pn=Payment&am=${amount}&cu=INR&tn=Payment`;
            window.location.href = url;
        }
        
        // Countdown timer
        function startTimer() {
            updateTimerDisplay();
            timer = setInterval(() => {
                timeLeft--;
                updateTimerDisplay();
                
                if (timeLeft <= 0) {
                    clearInterval(timer);
                    showAlert("Payment time expired! Please refresh to get new UPI ID.", "warning");
                }
                
                if (timeLeft <= 60) {
                    document.getElementById("timer").style.color = "var(--danger)";
                }
            }, 1000);
        }
        
        function updateTimerDisplay() {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            const timerText = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            document.getElementById("timer").textContent = timerText;
            document.getElementById("countdown").textContent = timerText;
        }
        
        // Show premium modal
        function showPremiumModal(upiId, amount, refNo) {
            const modal = document.getElementById("premiumModal");
            document.getElementById("modalUtr").textContent = refNo;
            
            modal.style.display = "flex";
            setTimeout(() => modal.classList.add("active"), 10);
            
            // Focus confirm button for better UX
            setTimeout(() => document.getElementById("modalConfirmBtn").focus(), 300);
        }
        
        // Hide premium modal
        function hidePremiumModal() {
            const modal = document.getElementById("premiumModal");
            modal.classList.remove("active");
            setTimeout(() => modal.style.display = "none", 300);
        }
        
        // Show floating loader
        function showLoader() {
            const loader = document.getElementById("floatingLoader");
            const content = document.getElementById("loaderContent");
            
            loader.style.display = "flex";
            setTimeout(() => content.classList.add("active"), 10);
            
            // Animate progress bar
            let progress = 0;
            const progressInterval = setInterval(() => {
                progress += 1;
                document.getElementById("progressBar").style.width = `${progress}%`;
                if (progress >= 100) clearInterval(progressInterval);
            }, 30);
        }
        
        // Hide floating loader
        function hideLoader() {
            const loader = document.getElementById("floatingLoader");
            const content = document.getElementById("loaderContent");
            
            content.classList.remove("active");
            setTimeout(() => loader.style.display = "none", 300);
        }
        
        // Show alert message
        function showAlert(message, type = "success") {
            const icon = {
                success: "fas fa-check-circle",
                error: "fas fa-times-circle",
                warning: "fas fa-exclamation-circle"
            }[type];
            
            const color = {
                success: "var(--success)",
                error: "var(--danger)",
                warning: "var(--warning)"
            }[type];
            
            // Create alert element
            const alertEl = document.createElement("div");
            alertEl.innerHTML = `
                <i class="${icon}" style="margin-right: 8px; color: ${color};"></i>
                ${message}
            `;
            
            // Style alert
            alertEl.style.position = "fixed";
            alertEl.style.bottom = "20px";
            alertEl.style.left = "50%";
            alertEl.style.transform = "translateX(-50%)";
            alertEl.style.backgroundColor = "white";
            alertEl.style.padding = "12px 20px";
            alertEl.style.borderRadius = "8px";
            alertEl.style.boxShadow = "0 5px 15px rgba(0,0,0,0.15)";
            alertEl.style.zIndex = "9999";
            alertEl.style.display = "flex";
            alertEl.style.alignItems = "center";
            alertEl.style.opacity = "0";
            alertEl.style.transition = "all 0.3s ease";
            
            document.body.appendChild(alertEl);
            
            // Animate in
            setTimeout(() => {
                alertEl.style.opacity = "1";
                alertEl.style.bottom = "30px";
            }, 10);
            
            // Remove after delay
            setTimeout(() => {
                alertEl.style.opacity = "0";
                alertEl.style.bottom = "20px";
                setTimeout(() => alertEl.remove(), 300);
            }, 3000);
        }
        
        // Payment verification
        function verifyPayment(refNo) {
            if (!refNo || refNo.length !== 12) {
                showAlert("Please enter a valid 12-digit UTR number", "error");
                return;
            }
            
            // Show premium modal instead of default confirm
            showPremiumModal(upi, ramt, refNo);
        }
        
        // Process payment
        function processPayment(refNo) {
            showLoader();
            
            // Simulate processing delay
            setTimeout(() => {
                hideLoader();
                document.getElementById("successMessage").style.display = "block";
                
                // Submit to server
                setTimeout(() => {
                    submitPaymentToServer(refNo);
                }, 2000);
            }, 2000);
        }
        
        // Submit payment to server
        function submitPaymentToServer(refNo) {
            $.ajax({
                type: "POST",
                url: "adddeposit.php",
                data: {
                    amt: ramt,
                    refnum: refNo,
                    srl: serial,
                    source: "wepay",
                    upi: upi,
                    userId: userId,
                    token: token
                },
                success: function(response) {
                    const arr = response.split('~');
                    
                    if (arr[0] == 1) {
                        // Success - redirect
                        window.location.href = `depositconfirm.php?amt=${ramt}&refnum=${refNo}&srl=${serial}&userId=${userId}&token=${token}`;
                    } else {
                        const errors = {
                            0: "Error processing payment",
                            2: "This UTR has already been used",
                            3: "Please wait 1 minute before trying again",
                            4: "Recharge option suspended. Contact support"
                        };
                        showAlert(errors[arr[0]] || "Unknown error occurred", "error");
                    }
                },
                error: function() {
                    showAlert("Network error. Please try again", "error");
                }
            });
        }
        
        // Event listeners
        document.getElementById("refno").addEventListener("input", function() {
            if (this.value.length === 12) {
                document.getElementById("savebtn").click();
            }
        });
        
        document.getElementById("savebtn").addEventListener("click", function() {
            verifyPayment(document.getElementById("refno").value);
        });
        
        // Modal event listeners
        document.getElementById("modalCancelBtn").addEventListener("click", function() {
            hidePremiumModal();
        });
        
        document.getElementById("modalConfirmBtn").addEventListener("click", function() {
            hidePremiumModal();
            processPayment(document.getElementById("refno").value);
        });
        
        // Close modal when clicking outside content
        document.getElementById("premiumModal").addEventListener("click", function(e) {
            if (e.target === this) {
                hidePremiumModal();
            }
        });
        
        // Auto-focus UTR input
        setTimeout(() => document.getElementById("refno").focus(), 1000);
    </script>
</body>
</html>
<?php
    } else {
        $res['code'] = 10000;
        $res['success'] = 'false';
        $res['message'] = 'Invalid request signature!';
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($res);
    }
} else {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($res);
}
?>