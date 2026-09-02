<?php
// 1. Database Connection
include("../serive/samparka.php");

// ------------------- SQL QUERY LOGIC START (Same as provided) ------------------- //

$upi_ids = [];
// 'maulya' column holds UPI ID, 'sthiti' = 1 means Active
$upi_query = mysqli_query($conn, "SELECT maulya FROM deyya WHERE sthiti='1'");

if(mysqli_num_rows($upi_query) > 0){
    while($row = mysqli_fetch_assoc($upi_query)){
        $upi_ids[] = $row['maulya'];
    }
} else {
    // Backup UPI agar database khali ho ya error ho
    $upi_ids[] = 'demo@ybl'; 
}

// Session based UPI rotation function
function getSessionUpiId($upi_ids) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start(['cookie_lifetime' => 86400, 'read_and_close'  => false]);
    }
    if (!isset($_SESSION['upi_rotation'])) {
        $_SESSION['upi_rotation'] = ['index' => 0];
    }
    // Rotate Index
    $_SESSION['upi_rotation']['index'] = ($_SESSION['upi_rotation']['index'] + 1) % count($upi_ids);
    return $upi_ids[$_SESSION['upi_rotation']['index']];
}

// Get Rotated UPI from Database List
$upi_id = getSessionUpiId($upi_ids);

// ------------------- SQL QUERY LOGIC END ------------------- //


function sanitizeInput($conn, $input) {
    return htmlspecialchars(mysqli_real_escape_string($conn, $input));
}

// Parameters Receive karna
$ramt = isset($_GET['amount']) ? sanitizeInput($conn, $_GET['amount']) : '0';
$payId = isset($_GET['payid']) && is_numeric($_GET['payid']) ? (int)$_GET['payid'] : 13;
$tyid = isset($_GET['tyid']) ? sanitizeInput($conn, $_GET['tyid']) : '';
$uid = isset($_GET['uid']) ? sanitizeInput($conn, $_GET['uid']) : '';
$sign = isset($_GET['sign']) ? sanitizeInput($conn, $_GET['sign']) : '';

// Amount Formatting
$ramt = number_format((float)$ramt, 2, '.', '');

// Generate Transaction ID
$serial = 'TXN' . date("YmdHis") . rand(1000,9999);

// QR Code Generate Logic
$merchant_name = "Merchant"; 
$upi_string = "upi://pay?pa={$upi_id}&pn={$merchant_name}&am={$ramt}&cu=INR&tn=Recharge";
$encoded_upi = rawurlencode($upi_string);
// QR size increased slightly to match UI
$qr_code_url = "https://api.qrserver.com/v1/create-qr-code/?size=350x350&data={$encoded_upi}&margin=10&bgcolor=ffffff";


// ------------------- USER VERIFICATION & TOKEN LOGIC ------------------- //
if (isset($_GET['amount']) && isset($_GET['uid'])) {
    
    $userId = $uid;
    $userPhoto = '1'; // Default
    
    // User Details Fetch karna (Signature ke liye zaruri hai)
    $numquery = "SELECT mobile, codechorkamukala FROM shonu_subjects WHERE id = ".$userId;
    $numresult = $conn->query($numquery);
    
    if ($numresult && mysqli_num_rows($numresult) > 0) {
        $numarr = mysqli_fetch_array($numresult);
        $mobileDigits = preg_replace('/\D+/', '', (string)$numarr['mobile']);
		$userName = (substr($mobileDigits, 0, 2) === '91') ? $mobileDigits : '91' . $mobileDigits;
        $nickName = $numarr['codechorkamukala'];
        
        $creaquery = "SELECT createdate FROM shonu_subjects WHERE id = ".$userId;
        $crearesult = $conn->query($creaquery);
        $creaarr = mysqli_fetch_array($crearesult);
        
        // Accept the current GetUserInfo sign and the older gateway sign so
        // existing clients keep working without bypassing user validation.
        $knbdstr = '{"userId":'.$userId.',"userPhoto":"'.$userPhoto.'","userName":'.$userName.',"nickName":"'.$nickName.'","createdate":"'.$creaarr['createdate'].'"}';
        $legacySign = strtoupper(hash('sha256', $knbdstr));
        $currentSign = strtoupper(hash('sha256', $userId . '|' . $userName . '|' . $creaarr['createdate']));
        $incomingSign = strtoupper(trim((string)$sign));

        if ($incomingSign !== '' && (hash_equals($currentSign, $incomingSign) || hash_equals($legacySign, $incomingSign))) {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Payment Gateway</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; -webkit-tap-highlight-color: transparent; }
        
        body { 
            background-color: #f5f5f5; 
            color: #333; 
            display: flex;
            justify-content: center;
            min-height: 100vh;
        }

        .main-container {
            width: 100%;
            max-width: 450px;
            background: #fff;
            min-height: 100vh;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            box-shadow: 0 0 20px rgba(0,0,0,0.05);
        }

        .header-amount {
            text-align: center;
            margin-top: 20px;
            margin-bottom: 20px;
        }

        .amount-label {
            font-size: 12px;
            color: #888;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .amount-value {
            font-size: 32px;
            font-weight: 800;
            color: #000;
            margin-top: 5px;
        }

        .qr-section {
            display: flex;
            justify-content: center;
            margin-bottom: 25px;
        }

        .qr-image {
            width: 200px;
            height: 200px;
            /* Sharp edges as per image */
            image-rendering: pixelated; 
        }

        /* The Light Blue UPI Box */
        .upi-copy-box {
            width: 100%;
            background-color: #EEF2FE; /* Light blueish purple */
            border-radius: 12px;
            padding: 12px 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .upi-text {
            font-family: monospace;
            font-size: 15px;
            color: #4A4A4A;
            font-weight: 600;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            margin-right: 10px;
        }

        .copy-btn-blue {
            background-color: #5D5FEF; /* Purple/Blue button */
            color: white;
            border: none;
            padding: 6px 15px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            text-transform: uppercase;
        }

        .separator-text {
            font-size: 14px;
            color: #666;
            margin-bottom: 15px;
            width: 100%;
            text-align: center;
            font-weight: 500;
        }

        /* App Buttons */
        .app-btn {
            width: 100%;
            background: #fff;
            border: 1px solid #f0f0f0;
            border-radius: 12px;
            padding: 15px;
            display: flex;
            align-items: center;
            margin-bottom: 12px;
            text-decoration: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05); /* Soft shadow */
            transition: transform 0.2s;
        }

        .app-btn:active {
            transform: scale(0.98);
        }

        .app-icon {
            width: 32px;
            height: 32px;
            margin-right: 15px;
            object-fit: contain;
        }

        .app-label {
            font-size: 15px;
            font-weight: 600;
            color: #000;
        }

        /* Footer / UTR Section */
        .footer-section {
            width: 100%;
            margin-top: auto;
            padding-top: 20px;
        }

        .step-label {
            font-size: 12px;
            font-weight: 700;
            color: #333;
            margin-bottom: 8px;
        }

        .input-wrapper {
            position: relative;
            width: 100%;
        }

        .utr-input {
            width: 100%;
            padding: 14px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
            background: #fafafa;
        }
        
        .utr-input:focus {
            border-color: #5D5FEF;
            background: #fff;
        }

        .submit-btn {
            width: 100%;
            background: #ccc;
            color: white;
            border: none;
            padding: 15px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            margin-top: 15px;
            cursor: pointer;
            pointer-events: none; /* Disabled initially */
        }

        .submit-btn.active {
            background: #000; /* Black button as per modern UI or Blue */
            pointer-events: auto;
        }

        /* Toast */
        .toast {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 12px 24px;
            border-radius: 4px;
            font-size: 14px;
            z-index: 1000;
            display: none;
        }
        
        /* Loader */
        .loader-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(255,255,255,0.9); display: none;
            align-items: center; justify-content: center; z-index: 200;
        }
        .spinner {
            width: 40px; height: 40px; border: 4px solid #f3f3f3;
            border-top: 4px solid #5D5FEF; border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin { 100% { transform: rotate(360deg); } }

    </style>
</head>
<body>

    <div class="toast" id="toast">Copied!</div>

    <div class="main-container">
        
        <div class="header-amount">
            <div class="amount-label">PAYMENT AMOUNT</div>
            <div class="amount-value">₹<?php echo $ramt; ?></div>
        </div>

        <div class="qr-section">
            <img src="<?php echo $qr_code_url; ?>" alt="QR Code" class="qr-image">
        </div>

        <div class="upi-copy-box">
            <div class="upi-text" id="upi_display"><?php echo $upi_id; ?></div>
            <button class="copy-btn-blue" onclick="copyUPI()">COPY</button>
        </div>

        <div class="separator-text">Pay directly with</div>

        <a href="phonepe://pay?pa=<?php echo $upi_id; ?>&pn=<?php echo $merchant_name; ?>&am=<?php echo $ramt; ?>&tn=Recharge&cu=INR" class="app-btn">
            <img src="https://static.vecteezy.com/system/resources/previews/049/116/753/non_2x/phonepe-app-icon-transparent-background-free-png.png" class="app-icon" alt="PhonePe">
            <span class="app-label">Pay with PhonePe</span>
        </a>

        <a href="paytmmp://pay?pa=<?php echo $upi_id; ?>&pn=<?php echo $merchant_name; ?>&am=<?php echo $ramt; ?>&tn=Recharge&cu=INR" class="app-btn">
            <img src="https://upload.wikimedia.org/wikipedia/commons/4/42/Paytm_logo.png" class="app-icon" alt="Paytm">
            <span class="app-label">Pay with Paytm</span>
        </a>

        <div class="footer-section">
            <div class="step-label">STEP 2: Enter 12-digit UTR/Ref. No.</div>
            <div class="input-wrapper">
                <input type="number" id="utr_input" class="utr-input" placeholder="Enter UTR from payment app" maxlength="12">
            </div>
            <button id="submit_btn" class="submit-btn" onclick="submitPayment()">Submit UTR</button>
        </div>

    </div>

    <div class="loader-overlay" id="loader">
        <div class="spinner"></div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script>
        // Variables passed from PHP
        const ramt = '<?php echo $ramt; ?>';
        const serial = '<?php echo $serial; ?>';
        const payId = <?php echo (int)$payId; ?>;
        const userId = '<?php echo $userId; ?>';
        const token = '<?php echo $sign; ?>';
        const upi = '<?php echo $upi_id; ?>';

        function showToast(msg) {
            const t = document.getElementById('toast');
            t.innerText = msg;
            t.style.display = 'block';
            setTimeout(() => t.style.display = 'none', 2000);
        }

        function copyUPI() {
            navigator.clipboard.writeText(upi).then(() => showToast('UPI Copied!'));
        }

        // Input Validation
        const utrInput = document.getElementById('utr_input');
        const submitBtn = document.getElementById('submit_btn');

        utrInput.addEventListener('input', function() {
            // Only allow numbers
            this.value = this.value.replace(/[^0-9]/g, '');
            
            if (this.value.length === 12) {
                submitBtn.classList.add('active');
                submitBtn.style.background = '#000'; // Active Color
            } else {
                submitBtn.classList.remove('active');
                submitBtn.style.background = '#ccc'; // Inactive Color
            }
        });

        // AJAX Submission
        function submitPayment() {
            const refNo = utrInput.value;
            if (refNo.length !== 12) return;

            document.getElementById('loader').style.display = 'flex';
            
            $.ajax({
                type: "POST",
                url: "adddeposit.php",
                data: {
                    amt: ramt,
                    refnum: refNo,
                    srl: serial,
                    payid: payId,
                    source: "wepay",
                    upi: upi,
                    userId: userId,
                    token: token
                },
                success: function(response) {
                    document.getElementById('loader').style.display = 'none';
                    const body = String(response).trim();
                    // adddeposit.php returns the exact scalar "1" only after the
                    // pending deposit row has really been inserted. Never treat an
                    // error JSON/code that merely contains digit 1 as success.
                    if (body === '1') {
                         alert("UTR submitted successfully");
                         window.location.href = window.location.origin + "/#/main";
                    } else if (body === '2') {
                        alert("This UTR/Ref. No. has already been submitted");
                    } else if (body === '3') {
                        alert("Please wait 60 seconds before submitting another deposit");
                    } else {
                        alert("Submission Failed");
                    }
                },
                error: function() {
                    document.getElementById('loader').style.display = 'none';
                    alert("Network Error");
                }
            });
        }
    </script>
</body>
</html>
<?php
        } else {
             // Invalid Signature Error
             $res = ['code' => 10000, 'success' => 'false', 'message' => 'Invalid request signature!'];
             echo json_encode($res);
        }
    } else {
         echo "User validation failed";
    }
} else {
    echo "Invalid Access";
}
?>
