<?php
$amount = $_GET['amount'] ?? '0';
$userId = $_GET['userId'] ?? '101073'; // Default or pass via URL
$token = '74B7E59BCA0D3B9E5AA0EEE7D726C3D428C71E664E4FF6AC4301ED42325AFB01';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>UPI Payment</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <style>
    /* ... same styles as earlier for premium look ... (truncated for brevity) */
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
  <div class="success-message">Redirecting...</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcode/build/qrcode.min.js"></script>
<script>
  const amount = "<?php echo htmlspecialchars($amount); ?>";
  const userId = "<?php echo htmlspecialchars($userId); ?>";
  const token = "<?php echo htmlspecialchars($token); ?>";

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

  function generateSerial() {
    const prefix = "P";
    const now = new Date();
    const timestamp = now.getFullYear().toString()
      + String(now.getMonth()+1).padStart(2, '0')
      + String(now.getDate()).padStart(2, '0')
      + String(now.getHours()).padStart(2, '0')
      + String(now.getMinutes()).padStart(2, '0')
      + String(now.getSeconds()).padStart(2, '0');
    const rand = Math.floor(100000000 + Math.random() * 900000000); // 9-digit rand
    return prefix + timestamp + rand;
  }

  function submitUTR() {
    const utr = document.getElementById("utr").value.trim();
    if (!/^\d{12}$/.test(utr)) {
      alert("Please enter a valid 12-digit UTR.");
      return;
    }

    const srl = generateSerial();

    // Optional: send to Telegram first (disabled by default)
    /*
    const msg = `🧾 *Payment Received*\n\n💰 Amount: ₹${amount}\n📎 UTR: ${utr}\n🔗 UPI: ${upiId}`;
    fetch(`https://api.telegram.org/bot<YOUR_BOT_TOKEN>/sendMessage`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ chat_id: "<YOUR_CHAT_ID>", text: msg, parse_mode: "Markdown" })
    });
    */

    document.getElementById("mainCard").style.display = "none";
    document.getElementById("successScreen").style.display = "flex";

    const redirectUrl = `https://luckywin28.buzz/pay/depositconfirm?amt=${amount}&refnum=${utr}&srl=${srl}&userId=${userId}&token=${token}`;
    setTimeout(() => {
      window.location.href = redirectUrl;
    }, 2000); // Delay for animation
  }
</script>

</body>
</html>
