<?php
// Database connection
include("../serive/samparka.php");

// Define your UPI IDs array
$upi_ids = [
    'fwrmalenterprise.202@oksbi',
    'payment2@ybl',
    'payment3@ybl',
    'payment4@ybl'
];

// UPI ID rotation function
function getSessionUpiId($upi_ids) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start(['cookie_lifetime' => 86400]);
    }
    
    if (!isset($_SESSION['upi_rotation'])) {
        $_SESSION['upi_rotation'] = ['index' => 0, 'used_ids' => []];
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
        
        if ($attempts >= $max_attempts) break;
    } while (in_array($current_upi, $_SESSION['upi_rotation']['used_ids']));
    
    $_SESSION['upi_rotation']['used_ids'][] = $current_upi;
    return $current_upi;
}

// Sanitize input
function sanitizeInput($conn, $input) {
    return htmlspecialchars(mysqli_real_escape_string($conn, $input));
}

// Get amount from URL
$ramt = isset($_GET['amount']) ? sanitizeInput($conn, $_GET['amount']) : 0;

// Format amount
function formatAmount($amount) {
    $amount = preg_replace('/[^0-9.]/', '', $amount);
    if (strpos($amount, '.') === false) {
        return $amount . '.00';
    }
    return number_format((float)$amount, 2, '.', '');
}

$ramt = formatAmount($ramt);

// Generate transaction ID
function generateTransactionId() {
    return 'TXN' . date("YmdHis") . rand(1000,9999);
}

$serial = generateTransactionId();

// Get other parameters
$tyid = isset($_GET['tyid']) ? sanitizeInput($conn, $_GET['tyid']) : '';
$uid = isset($_GET['uid']) ? sanitizeInput($conn, $_GET['uid']) : '';
$sign = isset($_GET['sign']) ? sanitizeInput($conn, $_GET['sign']) : '';
$urlInfo = isset($_GET['urlInfo']) ? sanitizeInput($conn, $_GET['urlInfo']) : '';

// Generate QR code URL
function generateQrCodeUrl($upiId, $amount) {
    $upiUri = rawurlencode("upi://pay?pa=$upiId&pn=Payment&am=$amount&cu=INR");
    return "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=$upiUri&margin=15";
}

// Get UPI ID for this session
$upi_id = getSessionUpiId($upi_ids);
$qr_code_url = generateQrCodeUrl($upi_id, $ramt);

// Verify request signature
$res = ['code' => 405, 'message' => 'Illegal access!'];

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
            --danger: #d63031;
            --dark: #2d3436;
            --light: #f5f6fa;
            --gray: #636e72;
            --card-shadow: 0 10px 30px rgba(0,0,0,0.08);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
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
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
        }
        
        .payment-header {
            padding: 25px;
            text-align: center;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            position: relative;
        }
        
        .logo {
            position: absolute;
            top: 15px;
            left: 15px;
            font-weight: 700;
            font-size: 18px;
        }
        
        .payment-title {
            font-size: 16px;
            margin-bottom: 10px;
            opacity: 0.9;
        }
        
        .payment-amount {
            font-size: 36px;
            font-weight: 700;
            margin: 15px 0;
        }
        
        .payment-subtitle {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .payment-body {
            padding: 25px;
        }
        
        .qr-section {
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
        
        .qr-actions {
            margin-top: 15px;
            display: flex;
            justify-content: center;
            gap: 15px;
        }
        
        .qr-btn {
            background: rgba(74, 107, 255, 0.1);
            border: none;
            padding: 8px 15px;
            border-radius: 8px;
            font-size: 14px;
            color: var(--primary);
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .qr-btn:hover {
            background: rgba(74, 107, 255, 0.2);
        }
        
        .payment-options {
            margin: 25px 0;
        }
        
        .options-title {
            font-size: 16px;
            margin-bottom: 15px;
            font-weight: 500;
            text-align: center;
        }
        
        .options-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }
        
        .option-card {
            background: white;
            border-radius: 12px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
            cursor: pointer;
            transition: all 0.3s ease;
            border: 1px solid rgba(0,0,0,0.05);
        }
        
        .option-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .option-icon {
            width: 40px;
            height: 40px;
            margin: 0 auto 8px;
        }
        
        .option-name {
            font-size: 12px;
            font-weight: 500;
        }
        
        .upi-id-section {
            background: rgba(74, 107, 255, 0.05);
            border-radius: 12px;
            padding: 15px;
            margin: 20px 0;
            border: 1px solid rgba(74, 107, 255, 0.1);
        }
        
        .upi-id-label {
            font-size: 12px;
            color: var(--gray);
            margin-bottom: 5px;
        }
        
        .upi-id-value {
            font-family: 'Courier New', monospace;
            font-size: 16px;
            font-weight: 600;
            word-break: break-all;
        }
        
        .copy-btn {
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
            transition: all 0.3s ease;
            margin-left: auto;
        }
        
        .copy-btn:hover {
            background: rgba(74, 107, 255, 0.2);
        }
        
        .utr-section {
            margin-top: 25px;
        }
        
        .utr-title {
            font-size: 16px;
            margin-bottom: 15px;
            font-weight: 500;
            text-align: center;
        }
        
        .input-group {
            margin-bottom: 20px;
        }
        
        .input-label {
            display: block;
            font-size: 12px;
            color: var(--gray);
            margin-bottom: 8px;
        }
        
        .input-field {
            width: 100%;
            padding: 15px;
            border: 1px solid rgba(0,0,0,0.1);
            border-radius: 12px;
            font-size: 14px;
            outline: none;
            transition: all 0.3s ease;
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
            transition: all 0.3s ease;
        }
        
        .submit-btn:hover {
            background: #3a5bed;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(74, 107, 255, 0.3);
        }
        
        .notice {
            font-size: 12px;
            color: var(--gray);
            text-align: center;
            margin-top: 20px;
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
            transition: all 0.3s ease;
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
            
            .options-grid {
                grid-template-columns: repeat(2, 1fr);
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
                <div class="logo">LGP</div>
                <div class="payment-title">The amount you need to Pay</div>
                <div class="payment-amount">₹<?php echo $ramt; ?></div>
                <div class="payment-subtitle">Use mobile scan code to pay.</div>
            </div>
            
            <div class="payment-body">
                <div class="qr-section">
                    <div class="qr-code pulse">
                        <img src="<?php echo $qr_code_url; ?>" alt="UPI QR Code">
                    </div>
                    <div class="qr-actions">
                        <button class="qr-btn" onclick="downloadQR()">
                            <i class="fas fa-download"></i> Download
                        </button>
                        <button class="qr-btn">
                            <i class="fas fa-qrcode"></i> Scan
                        </button>
                    </div>
                </div>
                
                <div class="payment-options">
                    <div class="options-title">Select an option to pay (UPI)</div>
                    <div class="options-grid">
                        <div class="option-card" onclick="openUpiApp('<?php echo $upi_id; ?>', '<?php echo $ramt; ?>', 'Paytm')">
                            <img src="https://upload.wikimedia.org/wikipedia/commons/4/42/Paytm_logo.png" class="option-icon">
                            <div class="option-name">Paytm</div>
                        </div>
                        <div class="option-card" onclick="openUpiApp('<?php echo $upi_id; ?>', '<?php echo $ramt; ?>', 'PhonePe')">
                            <img src="https://i.pinimg.com/originals/b2/e1/af/b2e1af76fbbe9bc446544b8fa71b37b1.png" class="option-icon">
                            <div class="option-name">PhonePe</div>
                        </div>
                        <div class="option-card" onclick="copyUpiId()">
                            <img src="https://cdn-icons-png.flaticon.com/512/477/477103.png" class="option-icon">
                            <div class="option-name">Manual</div>
                        </div>
                    </div>
                </div>
                
                <div class="upi-id-section">
                    <div style="display: flex; align-items: center;">
                        <div>
                            <div class="upi-id-label">UPI ID</div>
                            <div class="upi-id-value" id="upi"><?php echo $upi_id; ?></div>
                        </div>
                        <button class="copy-btn" id="btncopy">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </div>
                
                <div class="utr-section">
                    <div class="utr-title">Paid? Submit UTR NO. for fast verification.</div>
                    <div class="input-group">
                        <label class="input-label">UTR/UPI Ref No/UPI Transaction ID</label>
                        <input type="text" class="input-field" id="refno" placeholder="Enter 12-digit UTR" minlength="12" maxlength="12">
                    </div>
                    <button class="submit-btn" id="savebtn">
                        Submit
                    </button>
                    <div class="success-message" id="successMessage">
                        <i class="fas fa-check-circle"></i> Payment verified successfully! Redirecting...
                    </div>
                </div>
                
                <div class="notice">
                    Notice: Please ensure the payment amount matches exactly ₹<?php echo $ramt; ?>
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
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.8/clipboard.min.js"></script>
    <script>
        // Variables
        const ramt = <?php echo $ramt; ?>;
        const serial = '<?php echo $serial; ?>';
        const upi = document.getElementById("upi").innerHTML;
        const userId = <?php echo $userId; ?>;
        const token = '<?php echo $shonusign; ?>';
        
        // Initialize clipboard
        new ClipboardJS("#btncopy", {
            text: () => document.getElementById("upi").innerText
        }).on("success", () => {
            showAlert("UPI ID copied to clipboard!", "success");
        }).on("error", () => {
            showAlert("Failed to copy. Please copy manually.", "error");
        });
        
        // Function to open UPI app
        function openUpiApp(upiId, amount, appName) {
            const apps = {
                'google pay': `tez://upi/pay?pa=${upiId}&pn=Payment&am=${amount}&cu=INR`,
                'phonepe': `phonepe://upi/pay?pa=${upiId}&pn=Payment&am=${amount}&cu=INR`,
               'paytm': `paytmmp://upi/pay?pa=${upiId}&pn=Payment&am=${amount}&cu=INR&tn=Payment`,
                'bhim': `bhim://upi/pay?pa=${upiId}&pn=Payment&am=${amount}&cu=INR`
            };
            
            const url = apps[appName.toLowerCase()] || `upi://pay?pa=${upiId}&pn=Payment&am=${amount}&cu=INR`;
            window.location.href = url;
        }
        
        // Copy UPI ID
        function copyUpiId() {
            navigator.clipboard.writeText(upi).then(() => {
                showAlert("UPI ID copied to clipboard!", "success");
            }).catch(() => {
                showAlert("Failed to copy. Please copy manually.", "error");
            });
        }
        
        // Download QR code
        function downloadQR() {
            const link = document.createElement('a');
            link.href = '<?php echo $qr_code_url; ?>';
            link.download = 'UPI-Payment-QR.png';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
        
        // Show premium modal
        function showPremiumModal(upiId, amount, refNo) {
            const modal = document.getElementById("premiumModal");
            document.getElementById("modalUtr").textContent = refNo;
            
            modal.style.display = "flex";
            setTimeout(() => modal.classList.add("active"), 10);
            
            setTimeout(() => document.getElementById("modalConfirmBtn").focus(), 300);
        }
        
        // Hide premium modal
        function hidePremiumModal() {
            const modal = document.getElementById("premiumModal");
            modal.classList.remove("active");
            setTimeout(() => modal.style.display = "none", 300);
        }
        
        // Show alert message
        function showAlert(message, type = "success") {
            const icon = {
                success: "fas fa-check-circle",
                error: "fas fa-times-circle",
                warning: "fas fa-exclamation-circle"
            }[type];
            
            const color = {
                success: "#00b894",
                error: "#d63031",
                warning: "#fdcb6e"
            }[type];
            
            const alertEl = document.createElement("div");
            alertEl.innerHTML = `
                <i class="${icon}" style="margin-right: 8px; color: ${color};"></i>
                ${message}
            `;
            
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
            
            setTimeout(() => {
                alertEl.style.opacity = "1";
                alertEl.style.bottom = "30px";
            }, 10);
            
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
            
            showPremiumModal(upi, ramt, refNo);
        }
        
        // Process payment
        function processPayment(refNo) {
            document.getElementById("successMessage").style.display = "block";
            
            // Submit to server
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
        
        document.getElementById("modalCancelBtn").addEventListener("click", function() {
            hidePremiumModal();
        });
        
        document.getElementById("modalConfirmBtn").addEventListener("click", function() {
            hidePremiumModal();
            processPayment(document.getElementById("refno").value);
        });
        
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