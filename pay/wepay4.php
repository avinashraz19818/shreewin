<?php
// Database connection
include("../serive/samparka.php");

// Define your UPI IDs array
$upi_ids = [
    'innovixscraptraders.61237849@sbi',
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
    return "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=$upiUri&margin=15&bgcolor=1a1a2e&color=4cc9f0";
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
    <title>Premium UPI Payment</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4cc9f0;  /* Light Blue */
            --secondary: #4361ee; /* Darker Blue */
            --success: #4ad66d;  /* Green */
            --danger: #f72585;   /* Pink/Red */
            --dark: #1a1a2e;     /* Dark Blue */
            --darker: #16213e;
            --light: #f8f9fa;
            --gray: #a8a8c3;
            --card-shadow: 0 10px 30px rgba(0,0,0,0.3);
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background-color: var(--darker);
            color: var(--light);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .payment-container {
            max-width: 500px;
            width: 100%;
            background: var(--dark);
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
            border: 1px solid rgba(76, 201, 240, 0.1);
            position: relative;
        }
        
        .payment-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--success), var(--danger));
            animation: rainbow 5s linear infinite;
        }
        
        .payment-header {
            padding: 25px;
            text-align: center;
            position: relative;
        }
        
        .payment-title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .payment-amount {
            font-size: 42px;
            font-weight: 700;
            margin: 15px 0;
            color: white;
            text-shadow: 0 0 10px rgba(76, 201, 240, 0.3);
            animation: pulse 2s infinite;
        }
        
        .payment-body {
            padding: 25px;
        }
        
        .section {
            margin-bottom: 30px;
            position: relative;
        }
        
        .section::after {
            content: '';
            position: absolute;
            bottom: -15px;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(76, 201, 240, 0.5), transparent);
        }
        
        .section-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        
        .section-title i {
            margin-right: 10px;
            color: var(--primary);
        }
        
        .upi-id-container {
            background: rgba(76, 201, 240, 0.1);
            border: 1px solid rgba(76, 201, 240, 0.2);
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 20px;
            position: relative;
            overflow: hidden;
        }
        
        .upi-id-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--primary);
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
            color: white;
        }
        
        .qr-section {
            text-align: center;
            margin: 25px 0;
        }
        
        .qr-container {
            display: inline-block;
            position: relative;
        }
        
        .qr-code {
            width: 200px;
            height: 200px;
            background: white;
            padding: 15px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            border: 2px solid var(--primary);
            animation: float 3s ease-in-out infinite;
        }
        
        .qr-code img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        
        .qr-actions {
            margin-top: 20px;
            display: flex;
            justify-content: center;
            gap: 15px;
        }
        
        .qr-btn {
            background: rgba(76, 201, 240, 0.1);
            border: 1px solid var(--primary);
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 14px;
            color: var(--primary);
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .qr-btn:hover {
            background: rgba(76, 201, 240, 0.2);
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(76, 201, 240, 0.3);
        }
        
        .instruction-list {
            margin: 25px 0;
        }
        
        .instruction-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .instruction-number {
            background: var(--primary);
            color: var(--dark);
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            flex-shrink: 0;
            font-size: 12px;
            font-weight: 700;
        }
        
        .instruction-text {
            font-size: 14px;
            color: var(--gray);
        }
        
        .utr-section {
            margin-top: 30px;
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
            border: 1px solid rgba(76, 201, 240, 0.3);
            border-radius: 12px;
            font-size: 14px;
            outline: none;
            transition: var(--transition);
            background: rgba(76, 201, 240, 0.05);
            color: white;
            font-family: 'Courier New', monospace;
        }
        
        .input-field:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(76, 201, 240, 0.1);
        }
        
        .submit-btn {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            margin-top: 10px;
            position: relative;
            overflow: hidden;
        }
        
        .submit-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: 0.5s;
        }
        
        .submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(76, 201, 240, 0.3);
        }
        
        .submit-btn:hover::before {
            left: 100%;
        }
        
        /* Premium Modal */
        .premium-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
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
            background: var(--dark);
            border-radius: 20px;
            width: 90%;
            max-width: 400px;
            overflow: hidden;
            transform: translateY(20px);
            transition: transform 0.3s ease;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            border: 1px solid rgba(76, 201, 240, 0.2);
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
        
        .premium-modal-header::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, var(--primary), var(--success), var(--danger), var(--primary));
            background-size: 200% 100%;
            animation: rainbow 3s linear infinite;
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
            border-bottom: 1px solid rgba(76, 201, 240, 0.1);
        }
        
        .payment-detail-label {
            font-weight: 500;
            color: var(--gray);
            font-size: 14px;
        }
        
        .payment-detail-value {
            font-weight: 600;
            color: white;
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
            background: rgba(247, 37, 133, 0.1);
            color: var(--danger);
            border: 1px solid var(--danger);
        }
        
        .premium-modal-btn-cancel:hover {
            background: rgba(247, 37, 133, 0.2);
        }
        
        .premium-modal-btn-confirm {
            background: rgba(74, 213, 109, 0.1);
            color: var(--success);
            border: 1px solid var(--success);
        }
        
        .premium-modal-btn-confirm:hover {
            background: rgba(74, 213, 109, 0.2);
        }
        
        /* Animations */
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        @keyframes rainbow {
            0% { background-position: 0% 50%; }
            100% { background-position: 100% 50%; }
        }
        
        @keyframes glow {
            0%, 100% { box-shadow: 0 0 5px rgba(76, 201, 240, 0.5); }
            50% { box-shadow: 0 0 20px rgba(76, 201, 240, 0.8); }
        }
        
        /* Responsive */
        @media (max-width: 480px) {
            body {
                padding: 10px;
            }
            
            .payment-header {
                padding: 20px;
            }
            
            .payment-amount {
                font-size: 36px;
            }
            
            .qr-code {
                width: 180px;
                height: 180px;
            }
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <div class="payment-header">
            <div class="payment-title">Transfer Amount</div>
            <div class="payment-amount">₹<?php echo $ramt; ?></div>
        </div>
        
        <div class="payment-body">
            <div class="section">
                <div class="section-title">
                    <i class="fas fa-id-card"></i> UPI ID
                </div>
                <div class="upi-id-container">
                    <div class="upi-id-value"><?php echo $upi_id; ?></div>
                </div>
            </div>
            
            <div class="section">
                <div class="section-title">
                    <i class="fas fa-qrcode"></i> QR Code Payment
                </div>
                <div class="qr-section">
                    <div class="qr-container">
                        <div class="qr-code">
                            <img src="<?php echo $qr_code_url; ?>" alt="UPI QR Code">
                        </div>
                    </div>
                    <div class="qr-actions">
                        <button class="qr-btn" onclick="downloadQR()">
                            <i class="fas fa-download"></i> Download QR
                        </button>
                        <button class="qr-btn" onclick="copyUpiId()">
                            <i class="fas fa-copy"></i> Copy UPI ID
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="section">
                <div class="section-title">
                    <i class="fas fa-info-circle"></i> Instructions
                </div>
                <div class="instruction-list">
                    <div class="instruction-item">
                        <div class="instruction-number">1</div>
                        <div class="instruction-text">
                            Please scan the QR code with your favorite UPI app and copy the UTR code
                        </div>
                    </div>
                    <div class="instruction-item">
                        <div class="instruction-number">2</div>
                        <div class="instruction-text">
                            Please fill in the Ref No./UTR No after the transaction is completed
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="utr-section">
                <div class="input-group">
                    <label class="input-label">UTR / UPI Ref No / UPI Transaction ID</label>
                    <input type="text" class="input-field" id="refno" placeholder="Enter 12-digit UTR" minlength="12" maxlength="12">
                </div>
                <button class="submit-btn" id="savebtn">
                    <i class="fas fa-paper-plane"></i> Submit
                </button>
            </div>
            
            <div class="section">
                <div class="section-title">
                    <i class="fas fa-list-ol"></i> Step by Step Process
                </div>
                <div class="instruction-list">
                    <div class="instruction-item">
                        <div class="instruction-number">1</div>
                        <div class="instruction-text">
                            Download QR Code Click on Download QR Code Button And Take Screenshot of that
                        </div>
                    </div>
                    <div class="instruction-item">
                        <div class="instruction-number">2</div>
                        <div class="instruction-text">
                            Scan With Scanner of any UPI app
                        </div>
                    </div>
                    <div class="instruction-item">
                        <div class="instruction-number">3</div>
                        <div class="instruction-text">
                            Complete the transaction through QR Code
                        </div>
                    </div>
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
                        <span class="payment-detail-value"><?php echo $upi_id; ?></span>
                    </div>
                    <div class="payment-detail-row">
                        <span class="payment-detail-label">Amount:</span>
                        <span class="payment-detail-value">₹<?php echo $ramt; ?></span>
                    </div>
                    <div class="payment-detail-row">
                        <span class="payment-detail-label">UTR Number:</span>
                        <span class="payment-detail-value" id="modalUtr"></span>
                    </div>
                </div>
                <p style="text-align: center; color: var(--gray); font-size: 13px; margin-top: 20px;">
                    Please verify all details before confirming
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
    <script>
        // Variables
        const ramt = <?php echo $ramt; ?>;
        const serial = '<?php echo $serial; ?>';
        const upi = '<?php echo $upi_id; ?>';
        const userId = <?php echo $userId; ?>;
        const token = '<?php echo $shonusign; ?>';
        
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
            showAlert("QR Code download started", "success");
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
                success: "#4ad66d",
                error: "#f72585",
                warning: "#f8961e"
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
            alertEl.style.backgroundColor = "var(--dark)";
            alertEl.style.padding = "12px 20px";
            alertEl.style.borderRadius = "8px";
            alertEl.style.boxShadow = "0 5px 15px rgba(0,0,0,0.3)";
            alertEl.style.border = "1px solid ${color}";
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