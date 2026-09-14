<?php
// ১. এরর দেখার জন্য
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include("conn.php"); 

$message = "";
$status = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
    $userReason = isset($_POST['reason']) ? htmlspecialchars($_POST['reason']) : "";

    if ($userId > 0 && $amount > 0) {
        try {
            if (!$conn) { die("Connection failed: " . mysqli_connect_error()); }
            $conn->begin_transaction();

            $q1 = $conn->query("UPDATE shonu_kaichila SET motta = motta + $amount WHERE balakedara = $userId");
            if (!$q1) throw new Exception("Update error: " . $conn->error);

            $finalRemark = number_format($amount, 2) . " TK Turnover Added | Reason: " . ($userReason ?: "None");
            
            $stmt = $conn->prepare("INSERT INTO hodike_balakedara (userkani, price, serial, shonu, remark) VALUES (?, ?, 'Imitator', NOW(), ?)");
            if (!$stmt) throw new Exception("Prepare Error: " . $conn->error);

            $stmt->bind_param("ids", $userId, $amount, $finalRemark);
            $stmt->execute();

            $q2 = $conn->query("UPDATE shonu_kaichila SET motta = motta - $amount WHERE balakedara = $userId");
            if (!$q2) throw new Exception("Rollback error: " . $conn->error);

            $conn->commit();
            $message = "Transaction Processed Successfully!";
            $status = "success";
        } catch (Exception $e) {
            $conn->rollback();
            $message = "Error: " . $e->getMessage();
            $status = "error";
        }
    } else {
        $message = "Please enter valid credentials.";
        $status = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Professional Turnover Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    
    <style>
        :root {
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --bg-gradient: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            --card-bg: rgba(255, 255, 255, 0.9);
            --text-main: #1e293b;
            --text-sub: #64748b;
        }

        body {
            background: var(--bg-gradient);
            color: var(--text-main);
            font-family: 'Outfit', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            overflow: hidden;
        }

        /* Background animated circles */
        .circle {
            position: absolute;
            border-radius: 50%;
            background: white;
            filter: blur(80px);
            z-index: -1;
            animation: float 10s infinite alternate ease-in-out;
        }

        @keyframes float {
            0% { transform: translateY(0px) translateX(0px); }
            100% { transform: translateY(50px) translateX(30px); }
        }

        .container {
            width: 100%;
            max-width: 420px;
            padding: 20px;
        }

        .card {
            background: var(--card-bg);
            backdrop-filter: blur(15px);
            padding: 40px;
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.7);
            box-shadow: 0 20px 40px rgba(0,0,0,0.05);
            transition: transform 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h2 {
            font-weight: 600;
            margin: 0;
            color: var(--primary);
            letter-spacing: -0.5px;
        }

        .header p {
            font-size: 14px;
            color: var(--text-sub);
            margin-top: 5px;
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--text-sub);
            padding-left: 5px;
        }

        input, textarea {
            width: 100%;
            background: #ffffff;
            border: 1.5px solid #e2e8f0;
            color: var(--text-main);
            padding: 14px 18px;
            border-radius: 14px;
            box-sizing: border-box;
            font-size: 15px;
            transition: all 0.3s ease;
            outline: none;
        }

        input:focus, textarea:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }

        button {
            width: 100%;
            padding: 16px;
            background: var(--primary);
            border: none;
            border-radius: 14px;
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 10px 20px rgba(99, 102, 241, 0.2);
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
        }

        button:hover {
            background: var(--primary-hover);
            box-shadow: 0 15px 25px rgba(99, 102, 241, 0.3);
            transform: scale(1.02);
        }

        button:active {
            transform: scale(0.98);
        }

        .status-msg {
            margin-top: 20px;
            padding: 12px;
            border-radius: 12px;
            text-align: center;
            font-size: 14px;
            font-weight: 500;
            animation: fadeInDown 0.5s;
        }

        .success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

        /* Smooth page entrance */
        .animate-pop {
            animation: zoomIn 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
    </style>
</head>
<body>

<div class="circle" style="width: 300px; height: 300px; top: -100px; left: -100px; background: rgba(99,102,241,0.15);"></div>
<div class="circle" style="width: 250px; height: 250px; bottom: -50px; right: -50px; background: rgba(14,165,233,0.15);"></div>

<div class="container">
    <div class="card animate-pop">
        <div class="header animate__animated animate__fadeIn">
            <h2>Turnover Portal</h2>
            <p>Enter user details to process balance</p>
        </div>

        <form method="POST" action="">
            <div class="form-group animate__animated animate__fadeInUp" style="animation-delay: 0.1s;">
                <label>User ID</label>
                <input type="number" name="user_id" placeholder="Ex: 10254" required>
            </div>

            <div class="form-group animate__animated animate__fadeInUp" style="animation-delay: 0.2s;">
                <label>Amount (TK)</label>
                <input type="number" step="0.01" name="amount" placeholder="0.00" required>
            </div>

            <div class="form-group animate__animated animate__fadeInUp" style="animation-delay: 0.3s;">
                <label>Remark / Reason</label>
                <textarea name="reason" placeholder="Optional notes..." rows="2"></textarea>
            </div>

            <button type="submit" class="animate__animated animate__fadeInUp" style="animation-delay: 0.4s;">
                Process Transaction
            </button>
        </form>
        
        <?php if($message): ?>
            <div class="status-msg <?php echo $status; ?> animate__animated animate__pulse">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>