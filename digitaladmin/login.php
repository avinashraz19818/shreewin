<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <title>VIP Access Portal</title>

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@600;800&family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet" />

    <link rel="stylesheet" href="assets/vendor/fonts/remixicon/remixicon.css" />
    <link rel="stylesheet" href="assets/vendor/css/rtl/core.css" />

    <style>
        /* --- OLD BUG FIX & ABSOLUTE CENTERED UI --- */
        html, body {
            margin: 0 !important;
            padding: 0 !important;
            height: 100% !important;
            width: 100% !important;
            background: #060002 !important;
            font-family: 'Space Grotesk', sans-serif;
            overflow-x: hidden;
        }

        /* স্ক্রিন বাঁকা হওয়া রোধ করতে ফুল-স্ক্রিন ফ্লেক্স সেন্টারিং */
        .master-wrapper {
            min-height: 100vh;
            width: 100vw;
            display: flex;
            align-items: center;
            justify-content: center;
            background: radial-gradient(circle at 50% 50%, #160205 0%, #050001 100%);
            padding: 20px;
            box-sizing: border-box;
            position: relative;
        }

        /* ব্যাকগ্রাউন্ড নিয়ন গ্লো */
        .master-wrapper::before {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(255, 0, 60, 0.2) 0%, rgba(0,0,0,0) 70%);
            top: 10%;
            left: 10%;
            filter: blur(40px);
            pointer-events: none;
        }

        /* মেইন প্রিমিয়াম বক্স - সাইজ একদম ফিক্সড */
        .vip-premium-box {
            background: rgba(14, 2, 4, 0.95) !important;
            border: 1px solid rgba(255, 0, 60, 0.3) !important;
            border-radius: 24px !important;
            box-shadow: 0 20px 45px rgba(0, 0, 0, 0.8), 
                        0 0 30px rgba(255, 0, 60, 0.15) !important;
            width: 100%;
            max-width: 400px; /* মোবাইলে পারফেক্ট স্ট্যান্ডার্ড সাইজ */
            padding: 35px 25px !important;
            box-sizing: border-box;
            z-index: 10;
            position: relative;
        }

        /* টপ গোল্ডেন গ্লো লাইন */
        .top-neon-line {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg, #ff003c, #ffd700, #ff003c);
            border-radius: 24px 24px 0 0;
        }

        /* ব্র্যান্ড ও টাইটেল */
        .brand-zone {
            font-family: 'Orbitron', sans-serif;
            font-weight: 800;
            letter-spacing: 2px;
            background: linear-gradient(135deg, #ffffff 40%, #ffd700 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 1.5rem;
            margin-bottom: 5px;
            text-align: center;
        }

        .section-title {
            font-family: 'Orbitron', sans-serif;
            color: #ffffff;
            font-weight: 600;
            letter-spacing: 3px;
            font-size: 1.6rem;
            text-align: center;
            margin-bottom: 25px;
            text-shadow: 0 0 10px rgba(255, 255, 255, 0.1);
        }

        /* ইনপুট ব্লক স্টাইলিং (কোনো ভাঙা বর্ডার নেই) */
        .input-wrapper-block {
            margin-bottom: 20px;
            width: 100%;
        }

        .custom-label {
            display: block;
            font-family: 'Orbitron', sans-serif;
            font-size: 0.75rem;
            color: #ff003c;
            letter-spacing: 1px;
            margin-bottom: 8px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .modern-input-group {
            display: flex;
            align-items: center;
            background: rgba(255, 255, 255, 0.03) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            border-radius: 12px !important;
            transition: all 0.3s ease;
            width: 100%;
            overflow: hidden;
            box-sizing: border-box;
        }

        .modern-input-group:focus-within {
            border-color: #ff003c !important;
            box-shadow: 0 0 15px rgba(255, 0, 60, 0.3);
            background: rgba(255, 0, 60, 0.02) !important;
        }

        .input-icon-zone {
            padding: 12px 0 12px 15px;
            color: rgba(255, 255, 255, 0.3);
            display: flex;
            align-items: center;
            font-size: 1.1rem;
        }

        .modern-input-group:focus-within .input-icon-zone {
            color: #ff003c;
        }

        .clean-field {
            background: transparent !important;
            border: none !important;
            box-shadow: none !important;
            color: #ffffff !important;
            padding: 12px 14px !important;
            font-size: 0.95rem !important;
            width: 100% !important;
            outline: none !important;
        }

        .clean-field::placeholder {
            color: rgba(255, 255, 255, 0.2);
        }

        .eye-toggle {
            padding-right: 15px;
            color: rgba(255, 255, 255, 0.3);
            cursor: pointer;
        }
        .eye-toggle:hover { color: #ffffff; }

        /* ক্যাসিনো প্রিমিয়াম বাটন */
        .btn-premium-auth {
            background: linear-gradient(135deg, #ff003c 0%, #aa0024 100%) !important;
            border: none !important;
            border-radius: 12px !important;
            font-family: 'Orbitron', sans-serif;
            color: #ffffff !important;
            font-weight: 700 !important;
            font-size: 0.95rem !important;
            letter-spacing: 1.5px;
            padding: 14px !important;
            text-transform: uppercase;
            width: 100%;
            box-shadow: 0 4px 15px rgba(255, 0, 60, 0.3) !important;
            transition: all 0.3s ease;
            margin-top: 10px;
            cursor: pointer;
        }

        .btn-premium-auth:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(255, 0, 60, 0.5) !important;
        }

        /* রেসপন্সিভ অ্যালার্ট বক্স */
        .clean-alert {
            border-radius: 12px !important;
            padding: 12px !important;
            font-size: 0.85rem !important;
            font-weight: 500 !important;
            border: 1px solid transparent !important;
            display: flex !important;
            align-items: center !important;
            width: 100% !important;
            box-sizing: border-box;
            margin-bottom: 20px;
        }
        .alert-err { background: rgba(255, 0, 60, 0.1) !important; border-color: rgba(255, 0, 60, 0.2) !important; color: #ff4d79 !important; }
        .alert-warn { background: rgba(212, 175, 55, 0.1) !important; border-color: rgba(212, 175, 55, 0.2) !important; color: #f1c40f !important; }
        .alert-out { background: rgba(0, 184, 212, 0.1) !important; border-color: rgba(0, 184, 212, 0.2) !important; color: #00e5ff !important; }

        /* বটম সিকিউরিটি ব্যাজ */
        .bottom-badge {
            background: rgba(255, 255, 255, 0.01);
            border: 1px solid rgba(212, 175, 55, 0.15) !important;
            border-radius: 10px;
            padding: 8px 15px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-top: 15px;
        }
        .bottom-badge span {
            font-family: 'Orbitron', sans-serif;
            font-size: 0.7rem;
            color: rgba(255, 255, 255, 0.5);
            letter-spacing: 1px;
            font-weight: 600;
        }
    </style>
</head>

<body>

    <div class="master-wrapper">
        <div class="vip-premium-box">
            <div class="top-neon-line"></div>
            
            <div class="brand-zone">
                <i class="ri-flashlight-fill" style="color: #ffd700; margin-right: 5px;"></i>SHREE WIN PRO ADMIN
            </div>

            <h2 class="section-title">SYSTEM LOGIN</h2>

            <?php if (in_array((string)($_GET['err'] ?? ''), ['true', 'ture'], true)) { ?>
                <div class="clean-alert alert-err">
                    <i class="ri-error-warning-fill me-2 fs-5"></i><span>Invalid Admin Credentials</span>
                </div>
            <?php } else if ((string)($_GET['server'] ?? '') === 'true') { ?>
                <div class="clean-alert alert-warn">
                    <i class="ri-database-2-fill me-2 fs-5"></i><span>Admin database is temporarily unavailable</span>
                </div>
            <?php } else if (in_array((string)($_GET['msg'] ?? ''), ['true', 'ture', 'unauthorized'], true)) { ?>
                <div class="clean-alert alert-warn">
                    <i class="ri-alert-fill me-2 fs-5"></i><span>Unauthorized Intrusion Detected</span>
                </div>
            <?php } else if (isset($_GET['logout']) && $_GET['logout'] == "true") { ?>
                <div class="clean-alert alert-out">
                    <i class="ri-shield-check-fill me-2 fs-5"></i><span>Secure Session Terminated</span>
                </div>
            <?php } ?>

            <form id="formAuthentication" action="api/login.php" method="POST">
                
                <div class="input-wrapper-block">
                    <label class="custom-label">Admin Identification</label>
                    <div class="modern-input-group">
                        <div class="input-icon-zone"><i class="ri-user-settings-line"></i></div>
                        <input type="text" class="clean-field" id="email" name="username"
                            placeholder="Username or Email" autofocus autocomplete="off" required />
                    </div>
                </div>

                <div class="input-wrapper-block">
                    <label class="custom-label">Access Key</label>
                    <div class="modern-input-group">
                        <div class="input-icon-zone"><i class="ri-lock-password-line"></i></div>
                        <input type="password" id="password" class="clean-field" name="password"
                            placeholder="••••••••••••" required />
                        <div class="eye-toggle"><i class="ri-eye-off-line"></i></div>
                    </div>
                </div>

                <button class="btn-premium-auth" type="submit">Authenticate</button>

                <div style="text-align: center;">
                    <div class="bottom-badge">
                        <i class="ri-shield-flash-line me-2" style="color: #ffd700;"></i>
                        <span>CORE SECURITY ACTIVE</span>
                    </div>
                </div>

            </form>
        </div>
    </div>

    <script src="assets/vendor/libs/jquery/jquery.js"></script>
    <script src="assets/vendor/js/bootstrap.js"></script>
</body>

</html>
