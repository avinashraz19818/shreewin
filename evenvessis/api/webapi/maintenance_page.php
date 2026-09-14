<?php
$channel_id = isset($_GET['channel']) ? intval($_GET['channel']) : 0;
$channel_name = isset($_GET['name']) ? urldecode($_GET['name']) : 'Payment Channel';

// Get channel details from database
include "conn.php";
$channel_info = ['name' => 'Payment Channel', 'message_en' => '', 'message_ur' => ''];
if($channel_id > 0) {
    $sql = "SELECT payName, maintenance_message_en, maintenance_message_ur FROM payment_methods WHERE id = '$channel_id' LIMIT 1";
    $result = $conn->query($sql);
    if($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $channel_info['name'] = $row['payName'];
        $channel_info['message_en'] = $row['maintenance_message_en'];
        $channel_info['message_ur'] = $row['maintenance_message_ur'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Maintenance Mode - Rich28</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Noto+Nastaliq+Urdu:wght@400;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .maintenance-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 30px;
            padding: 40px 30px;
            max-width: 500px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            text-align: center;
            position: relative;
            overflow: hidden;
            animation: slideUp 0.5s ease;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .maintenance-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }
        
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .logo {
            font-size: 48px;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }
        
        .logo span {
            color: #764ba2;
        }
        
        .maintenance-icon {
            font-size: 80px;
            color: #ff9f1c;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        .channel-name {
            font-size: 24px;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }
        
        .message-container {
            position: relative;
            z-index: 1;
            margin: 20px 0;
            min-height: 100px;
        }
        
        .message {
            font-size: 16px;
            line-height: 1.6;
            color: #666;
            transition: opacity 0.3s ease;
        }
        
        .message.urdu {
            font-family: 'Noto Nastaliq Urdu', serif;
            font-size: 18px;
            line-height: 2;
            text-align: right;
            direction: rtl;
        }
        
        .safe-badge {
            background: linear-gradient(135deg, #06d6a0, #118ab2);
            color: white;
            padding: 12px 25px;
            border-radius: 50px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-size: 16px;
            font-weight: 500;
            margin: 20px 0;
            position: relative;
            z-index: 1;
            box-shadow: 0 10px 20px rgba(6, 214, 160, 0.3);
        }
        
        .safe-badge i {
            font-size: 20px;
        }
        
        .other-options {
            background: rgba(102, 126, 234, 0.1);
            border-radius: 15px;
            padding: 20px;
            margin: 20px 0;
            position: relative;
            z-index: 1;
        }
        
        .other-options h3 {
            font-size: 18px;
            color: #333;
            margin-bottom: 15px;
        }
        
        .other-options p {
            font-size: 14px;
            color: #666;
            margin-bottom: 20px;
        }
        
        .other-options .btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: transform 0.3s, box-shadow 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .other-options .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
        }
        
        .language-switcher {
            position: absolute;
            top: 20px;
            right: 20px;
            z-index: 10;
            display: flex;
            gap: 10px;
        }
        
        .lang-btn {
            background: white;
            border: 1px solid #ddd;
            padding: 8px 15px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .lang-btn.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        .lang-btn i {
            font-size: 16px;
        }
        
        .trust-badges {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 20px;
            position: relative;
            z-index: 1;
        }
        
        .trust-badge {
            text-align: center;
        }
        
        .trust-badge i {
            font-size: 24px;
            color: #667eea;
            margin-bottom: 5px;
        }
        
        .trust-badge span {
            font-size: 12px;
            color: #666;
            display: block;
        }
        
        @media (max-width: 480px) {
            .maintenance-card {
                padding: 30px 20px;
            }
            
            .logo {
                font-size: 36px;
            }
            
            .maintenance-icon {
                font-size: 60px;
            }
            
            .channel-name {
                font-size: 20px;
            }
            
            .safe-badge {
                padding: 10px 20px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="maintenance-card">
        <!-- Language Switcher -->
        <div class="language-switcher">
            <div class="lang-btn active" onclick="switchLanguage('en')">
                <i class="fas fa-globe"></i> English
            </div>
            <div class="lang-btn" onclick="switchLanguage('ur')">
                <i class="fas fa-globe"></i> اردو
            </div>
        </div>
        
        <!-- Logo -->
        <div class="logo">
            Rich<span>28</span>
        </div>
        
        <!-- Maintenance Icon -->
        <div class="maintenance-icon">
            <i class="fas fa-tools"></i>
        </div>
        
        <!-- Channel Name -->
        <div class="channel-name" id="channelName">
            <?php echo htmlspecialchars($channel_info['name']); ?>
        </div>
        
        <!-- Messages -->
        <div class="message-container">
            <div class="message" id="messageEn" style="display: block;">
                <?php echo htmlspecialchars($channel_info['message_en'] ?: 'This payment channel is currently under maintenance. We are working to restore it as soon as possible.'); ?>
            </div>
            <div class="message urdu" id="messageUr" style="display: none;">
                <?php echo htmlspecialchars($channel_info['message_ur'] ?: 'یہ ادائیگی چینل فی الحال زیر انتظام ہے۔ ہم اسے جلد از جلد بحال کرنے کے لیے کام کر رہے ہیں۔'); ?>
            </div>
        </div>
        
        <!-- Safe & Trusted Badge -->
        <div class="safe-badge">
            <i class="fas fa-shield-alt"></i>
            <span id="safeText">Safe & Trusted</span>
            <span id="safeTextUr" style="display: none;">محفوظ اور قابل اعتماد</span>
        </div>
        
        <!-- Other Options -->
        <div class="other-options">
            <h3 id="otherTitle">Try Other Payment Options</h3>
            <h3 id="otherTitleUr" style="display: none;">دیگر ادائیگی کے اختیارات آزمائیں</h3>
            
            <p id="otherDesc">You can use other available payment methods to complete your transaction.</p>
            <p id="otherDescUr" style="display: none;">آپ اپنی ادائیگی مکمل کرنے کے لیے دیگر دستیاب ادائیگی کے طریقے استعمال کر سکتے ہیں۔</p>
            
            <a href="javascript:history.back()" class="btn" id="backBtn">
                <i class="fas fa-arrow-left"></i> <span id="backText">Go Back</span>
                <span id="backTextUr" style="display: none;">واپس جائیں</span>
            </a>
        </div>
        
        <!-- Trust Badges -->
        <div class="trust-badges">
            <div class="trust-badge">
                <i class="fas fa-lock"></i>
                <span id="secureText">100% Secure</span>
                <span id="secureTextUr" style="display: none;">مکمل طور پر محفوظ</span>
            </div>
            <div class="trust-badge">
                <i class="fas fa-clock"></i>
                <span id="247Text">24/7 Support</span>
                <span id="247TextUr" style="display: none;">24/7 سپورٹ</span>
            </div>
            <div class="trust-badge">
                <i class="fas fa-check-circle"></i>
                <span id="verifiedText">Verified</span>
                <span id="verifiedTextUr" style="display: none;">تصدیق شدہ</span>
            </div>
        </div>
    </div>
    
    <script>
        function switchLanguage(lang) {
            // Update active button
            document.querySelectorAll('.lang-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.closest('.lang-btn').classList.add('active');
            
            if(lang === 'en') {
                // Show English, hide Urdu
                document.getElementById('messageEn').style.display = 'block';
                document.getElementById('messageUr').style.display = 'none';
                document.getElementById('safeText').style.display = 'inline';
                document.getElementById('safeTextUr').style.display = 'none';
                document.getElementById('otherTitle').style.display = 'block';
                document.getElementById('otherTitleUr').style.display = 'none';
                document.getElementById('otherDesc').style.display = 'block';
                document.getElementById('otherDescUr').style.display = 'none';
                document.getElementById('backText').style.display = 'inline';
                document.getElementById('backTextUr').style.display = 'none';
                document.getElementById('secureText').style.display = 'block';
                document.getElementById('secureTextUr').style.display = 'none';
                document.getElementById('247Text').style.display = 'block';
                document.getElementById('247TextUr').style.display = 'none';
                document.getElementById('verifiedText').style.display = 'block';
                document.getElementById('verifiedTextUr').style.display = 'none';
            } else {
                // Show Urdu, hide English
                document.getElementById('messageEn').style.display = 'none';
                document.getElementById('messageUr').style.display = 'block';
                document.getElementById('safeText').style.display = 'none';
                document.getElementById('safeTextUr').style.display = 'inline';
                document.getElementById('otherTitle').style.display = 'none';
                document.getElementById('otherTitleUr').style.display = 'block';
                document.getElementById('otherDesc').style.display = 'none';
                document.getElementById('otherDescUr').style.display = 'block';
                document.getElementById('backText').style.display = 'none';
                document.getElementById('backTextUr').style.display = 'inline';
                document.getElementById('secureText').style.display = 'none';
                document.getElementById('secureTextUr').style.display = 'block';
                document.getElementById('247Text').style.display = 'none';
                document.getElementById('247TextUr').style.display = 'block';
                document.getElementById('verifiedText').style.display = 'none';
                document.getElementById('verifiedTextUr').style.display = 'block';
            }
        }
        
        // Auto-detect browser language
        const userLang = navigator.language || navigator.userLanguage;
        if(userLang.includes('ur') || userLang.includes('pk')) {
            switchLanguage('ur');
            document.querySelectorAll('.lang-btn')[1].classList.add('active');
            document.querySelectorAll('.lang-btn')[0].classList.remove('active');
        }
    </script>
</body>
</html>