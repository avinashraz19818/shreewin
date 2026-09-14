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
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;600;700&family=Rajdhani:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        :root {
            --primary: #00f0ff;
            --secondary: #0088ff;
            --accent: #ff00aa;
            --dark: #0a0e17;
            --darker: #05080f;
            --light: #e0f8ff;
            --success: #00ffaa;
            --warning: #ffcc00;
            --danger: #ff0055;
            --info: #00aaff;
            --glow: 0 0 10px rgba(0, 240, 255, 0.7);
            --text-glow: 0 0 5px rgba(0, 240, 255, 0.7);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Rajdhani', sans-serif;
        }
        
        body {
            background: radial-gradient(ellipse at bottom, var(--darker) 0%, var(--dark) 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            color: var(--light);
            overflow-x: hidden;
        }
        
        .stars {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
        }
        
        .star {
            position: absolute;
            background: white;
            border-radius: 50%;
            animation: twinkle var(--duration) infinite ease-in-out;
            opacity: 0;
        }
        
        @keyframes twinkle {
            0%, 100% { opacity: 0; }
            50% { opacity: 1; }
        }
        
        .payment-container {
            background: rgba(10, 14, 23, 0.8);
            border-radius: 20px;
            box-shadow: 0 0 30px rgba(0, 240, 255, 0.3);
            width: 100%;
            max-width: 500px;
            overflow: hidden;
            position: relative;
            border: 1px solid rgba(0, 240, 255, 0.2);
            backdrop-filter: blur(10px);
            transform: translateY(0);
            transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            z-index: 10;
        }
        
        .payment-container::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(45deg, var(--primary), var(--secondary), var(--accent), var(--primary));
            z-index: -1;
            border-radius: 22px;
            animation: borderGlow 4s linear infinite;
            background-size: 400%;
            opacity: 0.7;
        }
        
        @keyframes borderGlow {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .payment-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 40px rgba(0, 240, 255, 0.5);
        }
        
        .payment-header {
            background: linear-gradient(135deg, rgba(0, 240, 255, 0.1) 0%, rgba(0, 136, 255, 0.1) 100%);
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .payment-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--primary), transparent);
        }
        
        .payment-header h1 {
            font-family: 'Orbitron', sans-serif;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 5px;
            letter-spacing: 1px;
            text-shadow: var(--text-glow);
        }
        
        .payment-header p {
            font-size: 14px;
            opacity: 0.8;
            letter-spacing: 0.5px;
        }
        
        .payment-logo {
            width: 80px;
            height: 80px;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0 auto 20px;
            box-shadow: 0 0 20px rgba(0, 240, 255, 0.5);
            border: 2px solid var(--primary);
            position: relative;
            overflow: hidden;
        }
        
        .payment-logo::before {
            content: '';
            position: absolute;
            width: 150%;
            height: 150%;
            background: conic-gradient(transparent, var(--primary), transparent);
            animation: rotate 3s linear infinite;
        }
        
        .payment-logo img {
            width: 50px;
            height: 50px;
            z-index: 2;
            background: rgba(10, 14, 23, 0.9);
            border-radius: 50%;
            padding: 5px;
        }
        
        @keyframes rotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .payment-body {
            padding: 30px;
        }
        
        .amount-display {
            background: rgba(0, 0, 0, 0.3);
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(0, 240, 255, 0.2);
            box-shadow: inset 0 0 10px rgba(0, 240, 255, 0.1);
        }
        
        .amount-display::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg, var(--primary), var(--accent));
        }
        
        .amount-value {
            font-family: 'Orbitron', sans-serif;
            font-size: 48px;
            font-weight: 700;
            color: var(--primary);
            margin: 15px 0;
            position: relative;
            display: inline-block;
            text-shadow: var(--text-glow);
        }
        
        .amount-value::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 3px;
            background: var(--accent);
            border-radius: 3px;
            box-shadow: 0 0 10px var(--accent);
        }
        
        .amount-currency {
            font-size: 18px;
            color: var(--light);
            opacity: 0.8;
            letter-spacing: 1px;
        }
        
        .transaction-id {
            font-size: 13px;
            color: var(--light);
            opacity: 0.7;
            text-align: center;
            margin-bottom: 25px;
            letter-spacing: 0.5px;
            font-family: 'Orbitron', sans-serif;
        }
        
        .payment-method {
            margin-bottom: 30px;
        }
        
        .payment-method h3 {
            font-family: 'Orbitron', sans-serif;
            font-size: 18px;
            color: var(--primary);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            letter-spacing: 1px;
        }
        
        .payment-method h3::before {
            content: '';
            display: inline-block;
            width: 8px;
            height: 8px;
            background: var(--primary);
            border-radius: 50%;
            margin-right: 10px;
            box-shadow: 0 0 5px var(--primary);
        }
        
        .qr-code-container {
            background: rgba(0, 0, 0, 0.3);
            border-radius: 15px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            box-shadow: 0 0 20px rgba(0, 240, 255, 0.1);
            margin-bottom: 25px;
            border: 1px solid rgba(0, 240, 255, 0.2);
            position: relative;
            overflow: hidden;
        }
        
        .qr-code-container::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(0, 240, 255, 0.1) 0%, transparent 70%);
            animation: pulse 6s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(0.8); opacity: 0; }
            50% { transform: scale(1.2); opacity: 0.3; }
            100% { transform: scale(0.8); opacity: 0; }
        }
        
        .qr-code {
            width: 220px;
            height: 220px;
            margin: 15px 0;
            border: 1px solid rgba(0, 240, 255, 0.3);
            border-radius: 10px;
            overflow: hidden;
            position: relative;
            background: white;
            padding: 10px;
        }
        
        .qr-code img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        
        .wallet-address {
            background: rgba(0, 0, 0, 0.5);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            position: relative;
            word-break: break-all;
            font-size: 15px;
            text-align: center;
            font-weight: 500;
            color: var(--light);
            font-family: 'Orbitron', sans-serif;
            letter-spacing: 0.5px;
            border: 1px solid rgba(0, 240, 255, 0.2);
        }
        
        .copy-btn {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: var(--dark);
            border: none;
            border-radius: 8px;
            padding: 12px 25px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0, 240, 255, 0.3);
            font-family: 'Orbitron', sans-serif;
            letter-spacing: 1px;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }
        
        .copy-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, var(--secondary) 0%, var(--primary) 100%);
            z-index: -1;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .copy-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 240, 255, 0.5);
        }
        
        .copy-btn:hover::before {
            opacity: 1;
        }
        
        .copy-btn i {
            margin-right: 8px;
            font-size: 16px;
        }
        
        .transaction-input {
            margin-bottom: 30px;
        }
        
        .transaction-input label {
            display: block;
            font-size: 15px;
            color: var(--light);
            margin-bottom: 12px;
            font-weight: 500;
            letter-spacing: 0.5px;
        }
        
        .input-group {
            position: relative;
        }
        
        .transaction-input input {
            width: 100%;
            padding: 18px 25px;
            border: 2px solid rgba(0, 240, 255, 0.3);
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: rgba(0, 0, 0, 0.3);
            color: var(--light);
            font-family: 'Orbitron', sans-serif;
            letter-spacing: 1px;
        }
        
        .transaction-input input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 15px rgba(0, 240, 255, 0.3);
            outline: none;
            background: rgba(0, 0, 0, 0.5);
        }
        
        .transaction-input input::placeholder {
            color: rgba(224, 248, 255, 0.5);
            letter-spacing: 0.5px;
        }
        
        .submit-btn {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: var(--dark);
            border: none;
            border-radius: 12px;
            padding: 18px;
            font-size: 17px;
            font-weight: 700;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s ease;
            box-shadow: 0 5px 20px rgba(0, 240, 255, 0.3);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-family: 'Orbitron', sans-serif;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }
        
        .submit-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, var(--accent) 0%, var(--primary) 100%);
            z-index: -1;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 240, 255, 0.5);
        }
        
        .submit-btn:hover::before {
            opacity: 1;
        }
        
        .payment-steps {
            margin-bottom: 30px;
        }
        
        .step {
            display: flex;
            margin-bottom: 20px;
            align-items: flex-start;
        }
        
        .step-number {
            background: var(--dark);
            color: var(--primary);
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 14px;
            font-weight: 700;
            margin-right: 15px;
            flex-shrink: 0;
            border: 2px solid var(--primary);
            font-family: 'Orbitron', sans-serif;
            box-shadow: 0 0 10px rgba(0, 240, 255, 0.3);
        }
        
        .step-content {
            flex: 1;
        }
        
        .step-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }
        
        .step-description {
            font-size: 14px;
            color: rgba(224, 248, 255, 0.8);
            line-height: 1.6;
        }
        
        .highlight {
            color: var(--primary);
            font-weight: 600;
            text-shadow: var(--text-glow);
        }
        
        .danger {
            color: var(--danger);
            font-weight: 600;
        }
        
        .payment-tips {
            background: rgba(0, 0, 0, 0.3);
            border-radius: 15px;
            padding: 20px;
            margin-top: 30px;
            border: 1px solid rgba(0, 240, 255, 0.2);
            position: relative;
            overflow: hidden;
        }
        
        .payment-tips::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: linear-gradient(to bottom, var(--primary), var(--accent));
        }
        
        .tips-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            letter-spacing: 0.5px;
            font-family: 'Orbitron', sans-serif;
        }
        
        .tips-title::before {
            content: '!';
            display: inline-block;
            width: 22px;
            height: 22px;
            background: var(--warning);
            color: var(--dark);
            border-radius: 50%;
            text-align: center;
            line-height: 22px;
            font-size: 14px;
            font-weight: 700;
            margin-right: 10px;
            font-family: 'Rajdhani', sans-serif;
        }
        
        .tip {
            font-size: 14px;
            color: rgba(224, 248, 255, 0.8);
            margin-bottom: 10px;
            display: flex;
            align-items: flex-start;
            line-height: 1.5;
        }
        
        .tip::before {
            content: '•';
            color: var(--primary);
            margin-right: 10px;
            font-size: 20px;
            line-height: 1;
        }
        
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(5, 8, 15, 0.9);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            display: none;
        }
        
        .loading-spinner {
            width: 60px;
            height: 60px;
            border: 4px solid rgba(0, 240, 255, 0.2);
            border-radius: 50%;
            border-top-color: var(--primary);
            animation: spin 1s ease-in-out infinite;
            margin-bottom: 20px;
            box-shadow: 0 0 20px var(--primary);
        }
        
        .loading-text {
            color: var(--light);
            font-family: 'Orbitron', sans-serif;
            letter-spacing: 2px;
            text-transform: uppercase;
            font-size: 14px;
            margin-top: 20px;
            text-shadow: var(--text-glow);
            animation: pulseText 1.5s infinite;
        }
        
        @keyframes pulseText {
            0%, 100% { opacity: 0.5; }
            50% { opacity: 1; }
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
            background: rgba(5, 8, 15, 0.9);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            display: none;
            backdrop-filter: blur(5px);
        }
        
        .modal-content {
            background: rgba(10, 14, 23, 0.95);
            border-radius: 20px;
            width: 90%;
            max-width: 450px;
            padding: 30px;
            box-shadow: 0 0 30px rgba(0, 240, 255, 0.3);
            transform: scale(0.9);
            opacity: 0;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 1px solid rgba(0, 240, 255, 0.2);
            position: relative;
            overflow: hidden;
        }
        
        .modal-content::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(0, 240, 255, 0.1) 0%, transparent 70%);
            animation: pulse 6s infinite;
            z-index: -1;
        }
        
        .modal-header {
            text-align: center;
            margin-bottom: 25px;
        }
        
        .modal-title {
            font-family: 'Orbitron', sans-serif;
            font-size: 22px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 15px;
            letter-spacing: 1px;
            text-shadow: var(--text-glow);
        }
        
        .modal-icon {
            width: 80px;
            height: 80px;
            background: rgba(0, 255, 170, 0.1);
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0 auto 20px;
            border: 2px solid var(--success);
            box-shadow: 0 0 20px rgba(0, 255, 170, 0.3);
        }
        
        .modal-icon i {
            color: var(--success);
            font-size: 40px;
            text-shadow: 0 0 10px var(--success);
        }
        
        .modal-body {
            margin-bottom: 30px;
        }
        
        .modal-message {
            font-size: 15px;
            color: rgba(224, 248, 255, 0.8);
            line-height: 1.6;
            text-align: center;
        }
        
        .modal-message strong {
            color: var(--light);
            font-weight: 600;
        }
        
        .modal-footer {
            display: flex;
            justify-content: center;
        }
        
        .modal-btn {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: var(--dark);
            border: none;
            border-radius: 8px;
            padding: 12px 25px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            margin: 0 10px;
            font-family: 'Orbitron', sans-serif;
            letter-spacing: 1px;
            min-width: 120px;
            text-align: center;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }
        
        .modal-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, var(--secondary) 0%, var(--primary) 100%);
            z-index: -1;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .modal-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 240, 255, 0.5);
        }
        
        .modal-btn:hover::before {
            opacity: 1;
        }
        
        #cancelBtn {
            background: linear-gradient(135deg, var(--danger) 0%, #ff2b6b 100%);
        }
        
        #cancelBtn::before {
            background: linear-gradient(135deg, #ff2b6b 0%, var(--danger) 100%);
        }
        
        .toast {
            position: fixed;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 15px 25px;
            border-radius: 8px;
            font-size: 15px;
            z-index: 1000;
            opacity: 0;
            transition: all 0.3s ease;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(0, 240, 255, 0.3);
            backdrop-filter: blur(5px);
            max-width: 90%;
            text-align: center;
        }
        
        .vibrate {
            animation: vibrate 0.3s linear infinite;
        }
        
        @keyframes vibrate {
            0%, 100% { transform: translateX(0); }
            20% { transform: translateX(-3px); }
            40% { transform: translateX(3px); }
            60% { transform: translateX(-3px); }
            80% { transform: translateX(3px); }
        }
        
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
        }
        
        .particle {
            position: absolute;
            background: var(--primary);
            border-radius: 50%;
            pointer-events: none;
            opacity: 0;
        }
        
        @media (max-width: 480px) {
            .payment-header {
                padding: 25px 20px;
            }
            
            .payment-body {
                padding: 25px 20px;
            }
            
            .amount-value {
                font-size: 40px;
            }
            
            .qr-code {
                width: 200px;
                height: 200px;
            }
            
            .modal-content {
                padding: 25px 20px;
            }
            
            .modal-btn {
                padding: 10px 15px;
                min-width: 100px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="stars" id="stars"></div>
    <div class="particles" id="particles"></div>
    
    <div class="payment-container animate__animated animate__fadeIn">
        <div class="payment-header">
            <div class="payment-logo">
                <img src="../assets/png/usdt-40311708.png" alt="USDT">
            </div>
            <h1>USDT PAYMENT GATEWAY</h1>
            <p>SECURE TRC20 TRANSACTION</p>
        </div>
        
        <div class="payment-body">
            <div class="amount-display animate__animated animate__fadeInUp">
                <div class="amount-currency">AMOUNT TO PAY</div>
                <div class="amount-value">$<?php echo $ramt; ?></div>
                <div class="amount-currency">USDT (TRC20 NETWORK)</div>
            </div>
            
            <div class="transaction-id animate__animated animate__fadeInUp animate__delay-1s">
                TXN: <?php echo $serial; ?>
            </div>
            
            <div class="payment-steps animate__animated animate__fadeInUp animate__delay-1s">
                <div class="step">
                    <div class="step-number">1</div>
                    <div class="step-content">
                        <div class="step-title">INITIATE TRANSFER</div>
                        <div class="step-description">
                            Send exactly <span class="highlight"><?php echo $ramt; ?> USDT</span> to the wallet address below using <span class="highlight">TRC20 network only</span>
                        </div>
                    </div>
                </div>
                
                <div class="step">
                    <div class="step-number">2</div>
                    <div class="step-content">
                        <div class="step-title">VERIFY TRANSACTION</div>
                        <div class="step-description">
                            After successful transfer, enter the <span class="highlight">Transaction Hash (TXID)</span> below and confirm
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="payment-method animate__animated animate__fadeInUp animate__delay-2s">
                <h3>PAYMENT DETAILS</h3>
                
                <div class="qr-code-container">
                    <div class="qr-code">
                        <img src="<?php echo '../images_usdt/'.$selectupiresult_two['filename']; ?>" alt="USDT QR Code">
                    </div>
                    
                    <div class="wallet-address" id="walletAddress">
                        <?php echo $upi_id;?>
                    </div>
                    
                    <button class="copy-btn" id="copyAddressBtn">
                        <i class="fas fa-copy"></i> COPY ADDRESS
                    </button>
                </div>
                
                <div class="transaction-input">
                    <label for="transactionId">TRANSACTION HASH (TXID)</label>
                    <div class="input-group">
                        <input type="text" id="transactionId" placeholder="Paste your USDT transaction hash here" class="vibrate">
                    </div>
                </div>
                
                <button class="submit-btn" id="submitBtn">
                    <i class="fas fa-paper-plane"></i> CONFIRM TRANSACTION
                </button>
            </div>
            
            <div class="payment-tips animate__animated animate__fadeInUp animate__delay-3s">
                <div class="tips-title">IMPORTANT NOTES</div>
                <div class="tip">Only <span class="highlight">USDT-TRC20</span> network is supported for this transaction</div>
                <div class="tip">Wallet address is <span class="danger">ONE-TIME USE</span> only - do not reuse</div>
                <div class="tip">Minimum deposit: <span class="danger">10 USDT</span> | Maximum: <span class="danger">5,000 USDT</span></div>
                <div class="tip">Average confirmation time: <span class="highlight">1-3 minutes</span></div>
                <div class="tip">Contact support immediately for any issues</div>
            </div>
        </div>
    </div>
    
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
        <div class="loading-text">PROCESSING TRANSACTION</div>
    </div>
    
    <div class="confirmation-modal" id="confirmationModal">
        <div class="modal-content" id="modalContent">
            <div class="modal-header">
                <div class="modal-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h3 class="modal-title">CONFIRM TRANSACTION</h3>
            </div>
            <div class="modal-body">
                <p class="modal-message" id="confirmationMessage"></p>
            </div>
            <div class="modal-footer">
                <button class="modal-btn" id="confirmBtn">CONFIRM</button>
                <button class="modal-btn" id="cancelBtn">CANCEL</button>
            </div>
        </div>
    </div>
    
    <div class="toast" id="toast"></div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/js/all.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.8/clipboard.min.js"></script>
    <script>
        $(document).ready(function() {
            // Create stars background
            const stars = $('#stars');
            const starCount = 100;
            
            for (let i = 0; i < starCount; i++) {
                const star = $('<div class="star"></div>');
                const size = Math.random() * 3;
                const duration = Math.random() * 5 + 5;
                const delay = Math.random() * 5;
                
                star.css({
                    width: `${size}px`,
                    height: `${size}px`,
                    top: `${Math.random() * 100}%`,
                    left: `${Math.random() * 100}%`,
                    '--duration': `${duration}s`,
                    animationDelay: `${delay}s`
                });
                
                stars.append(star);
            }
            
            // Create floating particles
            const particles = $('#particles');
            const particleCount = 20;
            
            for (let i = 0; i < particleCount; i++) {
                const particle = $('<div class="particle"></div>');
                const size = Math.random() * 5 + 2;
                const duration = Math.random() * 20 + 10;
                const delay = Math.random() * 5;
                const opacity = Math.random() * 0.3 + 0.1;
                
                particle.css({
                    width: `${size}px`,
                    height: `${size}px`,
                    top: `${Math.random() * 100}%`,
                    left: `${Math.random() * 100}%`,
                    opacity: opacity,
                    animation: `float ${duration}s linear ${delay}s infinite`
                });
                
                particles.append(particle);
            }
            
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
                
                // Create confirmation particles
                for (let i = 0; i < 10; i++) {
                    const particle = $('<div class="particle"></div>');
                    const size = Math.random() * 4 + 2;
                    const duration = Math.random() * 1 + 0.5;
                    const left = Math.random() * 100;
                    
                    particle.css({
                        width: `${size}px`,
                        height: `${size}px`,
                        top: '50%',
                        left: `${left}%`,
                        opacity: 0.8,
                        background: 'var(--primary)',
                        animation: `floatUp ${duration}s ease-out forwards`
                    });
                    
                    $('body').append(particle);
                    
                    setTimeout(() => {
                        particle.remove();
                    }, duration * 1000);
                }
            });
            
            // Submit button click
            $('#submitBtn').click(function() {
                const txId = $('#transactionId').val().trim();
                if (!txId) {
                    showToast('Please enter your Transaction Hash');
                    $('#transactionId').addClass('vibrate');
                    setTimeout(() => {
                        $('#transactionId').removeClass('vibrate');
                    }, 1000);
                    return;
                }
                
                // Show confirmation modal
                const amount = '<?php echo $ramt; ?>';
                const wallet = $('#walletAddress').text();
                const message = `<strong>Please verify your transaction details:</strong><br><br>
                    <strong>AMOUNT:</strong> <span class="highlight">${amount} USDT</span><br>
                    <strong>WALLET:</strong> ${wallet}<br>
                    <strong>TX HASH:</strong> ${txId}<br><br>
                    Confirm only if all details are correct`;
                
                $('#confirmationMessage').html(message);
                showModal();
            });
            
            // Confirm payment
            $('#confirmBtn').click(function() {
                hideModal();
                $('#loadingOverlay').fadeIn();
                
                // Create loading particles
                const spinner = $('.loading-spinner');
                for (let i = 0; i < 15; i++) {
                    const particle = $('<div class="particle"></div>');
                    const size = Math.random() * 4 + 2;
                    const duration = Math.random() * 2 + 1;
                    const angle = Math.random() * 360;
                    const distance = Math.random() * 30 + 20;
                    
                    particle.css({
                        width: `${size}px`,
                        height: `${size}px`,
                        background: 'var(--primary)',
                        opacity: 0.8,
                        animation: `orbit ${duration}s linear infinite`,
                        transformOrigin: `${distance}px ${distance}px`
                    });
                    
                    spinner.append(particle);
                    
                    setTimeout(() => {
                        particle.css({
                            left: `${Math.cos(angle * Math.PI / 180) * distance}px`,
                            top: `${Math.sin(angle * Math.PI / 180) * distance}px`
                        });
                    }, 10);
                }
                
                // Process payment after short delay
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
                
                // Create success particles
                for (let i = 0; i < 30; i++) {
                    const particle = $('<div class="particle"></div>');
                    const size = Math.random() * 6 + 2;
                    const duration = Math.random() * 2 + 1;
                    const angle = Math.random() * 360;
                    const distance = Math.random() * 200 + 100;
                    
                    particle.css({
                        width: `${size}px`,
                        height: `${size}px`,
                        background: i % 2 === 0 ? 'var(--primary)' : 'var(--success)',
                        opacity: 0.8,
                        animation: `explode ${duration}s ease-out forwards`,
                        left: '50%',
                        top: '50%'
                    });
                    
                    $('body').append(particle);
                    
                    setTimeout(() => {
                        particle.css({
                            left: `${50 + Math.cos(angle * Math.PI / 180) * distance}px`,
                            top: `${50 + Math.sin(angle * Math.PI / 180) * distance}px`
                        });
                    }, 10);
                    
                    setTimeout(() => {
                        particle.remove();
                    }, duration * 1000);
                }
                
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
            
            // Add floating animation to particles
            const style = document.createElement('style');
            style.innerHTML = `
                @keyframes float {
                    0% { transform: translateY(0) translateX(0); opacity: 0; }
                    10% { opacity: 1; }
                    90% { opacity: 1; }
                    100% { transform: translateY(-100vh) translateX(20px); opacity: 0; }
                }
                
                @keyframes floatUp {
                    0% { transform: translateY(0) scale(1); opacity: 0.8; }
                    100% { transform: translateY(-100px) scale(0.5); opacity: 0; }
                }
                
                @keyframes orbit {
                    0% { transform: rotate(0deg) translateX(20px) rotate(0deg); }
                    100% { transform: rotate(360deg) translateX(20px) rotate(-360deg); }
                }
                
                @keyframes explode {
                    0% { transform: translate(-50%, -50%) scale(0); opacity: 0.8; }
                    100% { transform: translate(var(--tx), var(--ty)) scale(1); opacity: 0; }
                }
            `;
            document.head.appendChild(style);
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