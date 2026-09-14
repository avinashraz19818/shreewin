<?php
// Database connection
include("../serive/samparka.php");

// Define your UPI IDs array (add more as needed)
$upi_ids = [
    'payment1@ybl',
    'payment2@ybl',
    'payment3@ybl',
    'payment4@ybl',
    'payment5@ybl',
    'payment6@ybl',
    'payment7@ybl'
];

// Improved UPI ID rotation with session tracking and prevention of immediate repeats
function getSessionUpiId($upi_ids) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start([
            'cookie_lifetime' => 86400, // 24 hours
            'read_and_close'  => false
        ]);
    }
    
    // Initialize if not set
    if (!isset($_SESSION['upi_rotation'])) {
        $_SESSION['upi_rotation'] = [
            'index' => 0,
            'last_used' => null,
            'used_ids' => []
        ];
    }
    
    // If we've used all UPI IDs, reset the used list
    if (count($_SESSION['upi_rotation']['used_ids']) >= count($upi_ids)) {
        $_SESSION['upi_rotation']['used_ids'] = [];
    }
    
    // Find next available UPI ID that hasn't been used recently
    $attempts = 0;
    $max_attempts = count($upi_ids) * 2; // Prevent infinite loops
    
    do {
        $_SESSION['upi_rotation']['index'] = ($_SESSION['upi_rotation']['index'] + 1) % count($upi_ids);
        $current_upi = $upi_ids[$_SESSION['upi_rotation']['index']];
        $attempts++;
        
        // If we've tried all possibilities, just return the next one
        if ($attempts >= $max_attempts) {
            break;
        }
        
    } while (in_array($current_upi, $_SESSION['upi_rotation']['used_ids']));
    
    // Track this UPI ID as used
    $_SESSION['upi_rotation']['last_used'] = $current_upi;
    $_SESSION['upi_rotation']['used_ids'][] = $current_upi;
    
    // Clean up old used IDs if the list gets too long
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
    $myurl = 'https://damanpro.site';
    
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
            --primary: #487ef5;
            --secondary: #16bffa;
            --success: #01c158;
            --warning: #ff9500;
            --danger: #ff3b30;
            --dark: #1a1a1a;
            --light: #f5f5f5;
            --gray: #6c757d;
            --bg: #f8f9fa;
            --card-shadow: 0 4px 20px rgba(0,0,0,0.08);
            --transition: all 0.3s ease;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg);
            color: var(--dark);
            line-height: 1.6;
        }
        
        .container {
            max-width: 800px;
            margin: 20px auto;
            padding: 0 15px;
        }
        
        .card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
            margin-bottom: 20px;
        }
        
        .header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 20px;
            text-align: center;
            position: relative;
        }
        
        .header h1 {
            font-size: 22px;
            font-weight: 600;
        }
        
        .logo {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            width: 40px;
            height: 40px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .logo img {
            width: 30px;
            height: 30px;
        }
        
        .amount-display {
            padding: 25px;
            text-align: center;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        
        .amount-label {
            font-size: 14px;
            color: var(--gray);
            margin-bottom: 5px;
        }
        
        .amount-value {
            font-size: 36px;
            font-weight: 700;
            color: var(--dark);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .amount-value span:first-child {
            margin-right: 5px;
            color: var(--primary);
        }
        
        .payment-section {
            padding: 20px;
        }
        
        .section-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        
        .section-title .step-badge {
            background: var(--primary);
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            font-size: 12px;
        }
        
        .qr-container {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin: 0 auto;
            text-align: center;
            border: 1px solid rgba(0,0,0,0.08);
            max-width: 350px;
            position: relative;
            overflow: hidden;
        }
        
        .qr-container::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(
                to bottom right,
                rgba(72, 126, 245, 0) 0%,
                rgba(72, 126, 245, 0.1) 50%,
                rgba(72, 126, 245, 0) 100%
            );
            transform: rotate(30deg);
            animation: shine 3s infinite;
        }
        
        .qr-container img {
            width: 100%;
            max-width: 250px;
            height: auto;
            margin: 0 auto;
            display: block;
            border-radius: 8px;
            position: relative;
            z-index: 1;
        }
        
        .upi-id-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(72, 126, 245, 0.1);
            border-radius: 8px;
            padding: 12px 15px;
            margin-top: 20px;
            border: 1px solid rgba(72, 126, 245, 0.2);
        }
        
        .upi-id {
            font-family: 'Courier New', monospace;
            font-size: 15px;
            font-weight: 500;
            color: var(--dark);
            word-break: break-all;
            flex: 1;
            padding-right: 10px;
        }
        
        .btn {
            border: none;
            border-radius: 8px;
            padding: 10px 15px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background: #3a6bd6;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(72, 126, 245, 0.3);
        }
        
        .btn-success {
            background: var(--success);
            color: white;
        }
        
        .btn-success:hover {
            background: #01a84d;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(1, 193, 88, 0.3);
        }
        
        .btn i {
            margin-right: 5px;
        }
        
        .timer {
            text-align: center;
            font-size: 15px;
            color: var(--danger);
            font-weight: 600;
            margin: 20px 0;
            padding: 10px;
            background: rgba(255, 59, 48, 0.1);
            border-radius: 8px;
        }
        
        .upi-apps {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 12px;
            margin: 20px 0;
        }
        
        .upi-app {
            width: 65px;
            height: 65px;
            border-radius: 12px;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            cursor: pointer;
            transition: var(--transition);
            border: 1px solid rgba(0,0,0,0.05);
        }
        
        .upi-app:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
        }
        
        .upi-app img {
            width: 45px;
            height: 45px;
            object-fit: contain;
        }
        
        .utr-section {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-top: 20px;
            box-shadow: var(--card-shadow);
        }
        
        .input-group {
            position: relative;
            margin-bottom: 15px;
        }
        
        .input-label {
            position: absolute;
            left: 15px;
            top: -10px;
            background: white;
            padding: 0 5px;
            font-size: 13px;
            color: var(--primary);
            z-index: 1;
        }
        
        .input-field {
            width: 100%;
            padding: 15px;
            border: 2px solid var(--primary);
            border-radius: 8px;
            font-size: 16px;
            font-family: 'Courier New', monospace;
            outline: none;
            transition: var(--transition);
            background: rgba(72, 126, 245, 0.02);
        }
        
        .input-field:focus {
            border-color: var(--secondary);
            box-shadow: 0 0 0 3px rgba(22, 191, 250, 0.2);
        }
        
        .instructions {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-top: 20px;
            box-shadow: var(--card-shadow);
        }
        
        .instructions h3 {
            font-size: 16px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        
        .instructions h3 i {
            margin-right: 10px;
            color: var(--primary);
        }
        
        .instructions ol {
            padding-left: 20px;
            margin: 0;
        }
        
        .instructions li {
            margin-bottom: 10px;
            font-size: 14px;
            color: var(--gray);
        }
        
        .highlight {
            font-weight: 600;
            color: var(--danger);
        }
        
        /* Premium Floating Confirmation Modal */
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
            border-radius: 16px;
            width: 90%;
            max-width: 450px;
            overflow: hidden;
            transform: translateY(20px);
            transition: transform 0.3s ease;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
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
            font-size: 20px;
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
        }
        
        .payment-detail-label {
            font-weight: 500;
            color: var(--gray);
        }
        
        .payment-detail-value {
            font-weight: 600;
            color: var(--dark);
        }
        
        .premium-modal-footer {
            display: flex;
            gap: 15px;
            padding: 0 25px 25px;
        }
        
        .premium-modal-btn {
            flex: 1;
            border: none;
            border-radius: 8px;
            padding: 12px;
            font-size: 15px;
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
            background: #01a84d;
            box-shadow: 0 4px 12px rgba(1, 193, 88, 0.3);
        }
        
        /* Floating Loading Animation */
        .floating-loader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            z-index: 10000;
            display: none;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(5px);
        }
        
        .loader-content {
            background: white;
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            max-width: 350px;
            width: 90%;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            transform: translateY(20px);
            opacity: 0;
            transition: all 0.3s ease;
        }
        
        .loader-content.active {
            transform: translateY(0);
            opacity: 1;
        }
        
        .loader-spinner {
            width: 60px;
            height: 60px;
            border: 5px solid rgba(72, 126, 245, 0.2);
            border-radius: 50%;
            border-top-color: var(--primary);
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        
        .loader-text {
            font-size: 16px;
            font-weight: 500;
            color: var(--dark);
            margin-bottom: 15px;
        }
        
        .success-message {
            display: none;
            background: rgba(1, 193, 88, 0.1);
            color: var(--success);
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
            text-align: center;
            font-weight: 500;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        @keyframes shine {
            0% { transform: rotate(30deg) translate(-10%, -10%); }
            100% { transform: rotate(30deg) translate(10%, 10%); }
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .pulse {
            animation: pulse 2s infinite;
        }
        
        @media (max-width: 576px) {
            .header h1 {
                font-size: 18px;
                padding-left: 40px;
            }
            
            .amount-value {
                font-size: 28px;
            }
            
            .qr-container {
                padding: 20px;
            }
            
            .upi-app {
                width: 55px;
                height: 55px;
            }
            
            .upi-app img {
                width: 35px;
                height: 35px;
            }
            
            .premium-modal-content {
                width: 95%;
            }
            
            .premium-modal-footer {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="header">
                <div class="logo">
                    <img src="https://indiadesignsystem.bombaydc.com/assets/india-designs/display/UPI/black.svg" alt="UPI Logo">
                </div>
                <h1>UPI Payment Gateway</h1>
            </div>
            
            <div class="amount-display">
                <div class="amount-label">PAYMENT AMOUNT</div>
                <div class="amount-value">
                    <span>₹</span>
                    <span class="money"><?php echo $ramt; ?></span>
                </div>
            </div>
            
            <div class="payment-section">
                <div class="section-title">
                    <span class="step-badge">1</span>
                    <span>Scan QR Code or Copy UPI ID</span>
                </div>
                
                <div class="qr-container pulse">
                    <img src="<?php echo $qr_code_url; ?>" alt="UPI QR Code" id="qrCodeImage">
                </div>
                
                <div class="text-center" style="margin: 15px 0;">
                    <a href="<?php echo $qr_code_url; ?>" download="UPI-Payment-QR" class="btn btn-primary">
                        <i class="fas fa-download"></i> Download QR Code
                    </a>
                </div>
                
                <div class="upi-id-container">
                    <div class="upi-id" id="upi"><?php echo $upi_id; ?></div>
                    <button class="btn btn-primary" id="btncopy">
                        <i class="fas fa-copy"></i> Copy
                    </button>
                </div>
                
                <div class="timer">
                    <i class="fas fa-clock"></i> Complete payment within: <span id="countdown">05:00</span>
                </div>
                
                <div class="section-title" style="margin-top: 25px;">
                    <span class="step-badge">2</span>
                    <span>Pay with your favorite UPI app</span>
                </div>
                
                <div class="upi-apps">
                    <?php foreach($upi_apps as $app): ?>
                        <div class="upi-app" onclick="openUpiApp('<?php echo $upi_id; ?>', '<?php echo $ramt; ?>', '<?php echo $app['name']; ?>')">
                            <img src="https://indiadesignsystem.bombaydc.com/assets/india-designs/display/UPI/black.svg">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="utr-section">
                <div class="section-title">
                    <span class="step-badge">3</span>
                    <span>Submit Payment Reference</span>
                </div>
                
                <div class="input-group">
                    <span class="input-label">UTR/Reference Number</span>
                    <input type="text" class="input-field" id="refno" placeholder="Enter 12-digit UTR" minlength="12" maxlength="12">
                </div>
                
                <button class="btn btn-success" id="savebtn" style="width: 100%;">
                    <i class="fas fa-check-circle"></i> Verify Payment
                </button>
                
                <div class="success-message" id="successMessage">
                    <i class="fas fa-check-circle"></i> Payment verified successfully! Redirecting...
                </div>
            </div>
            
            <div class="instructions">
                <h3>
                    <i class="fas fa-info-circle"></i>
                    Payment Instructions
                </h3>
                <ol>
                    <li>Please save the QR code or copy the UPI ID for payment. Each UPI ID is valid for single transaction only.</li>
                    <li>Ensure the payment amount matches exactly <span class="highlight">₹<?php echo $ramt; ?></span> to avoid transaction failure.</li>
                    <li>Complete the payment within <span class="highlight">5 minutes</span> or the transaction may expire.</li>
                    <li>After payment, enter the <span class="highlight">12-digit UTR</span> number to verify your transaction.</li>
                    <li>For any issues, contact support with your transaction details.</li>
                </ol>
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
                <p style="text-align: center; color: var(--gray); font-size: 14px;">
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
                    document.getElementById("countdown").style.color = "var(--danger)";
                }
            }, 1000);
        }
        
        function updateTimerDisplay() {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            document.getElementById("countdown").textContent = 
                `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
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