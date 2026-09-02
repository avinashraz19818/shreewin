<?php
include("api/conn.php");

$uid  = isset($_GET['uid']) ? intval($_GET['uid']) : 0;

$error = "";
$data = [];
$totalUsers = 0;
$totalAmount = 0;

if ($uid != 0) {

    $stmt = $conn->prepare("SELECT owncode FROM shonu_subjects WHERE id=?");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $owncode = $row['owncode'] ?? '';

    if (empty($owncode)) {
        $error = "Invalid UID";
    } else {

        $users = mysqli_query($conn, "
            SELECT id 
            FROM shonu_subjects 
            WHERE code='$owncode'
        ");

        if (!$users) {
            die("USERS QUERY ERROR: " . mysqli_error($conn));
        }

        while ($u = mysqli_fetch_assoc($users)) {

            $id = $u['id'];

            $q = mysqli_query($conn, "
                SELECT SUM(motta+0) as amount
                FROM thevani t
                WHERE t.balakedara='$id'
                AND t.sthiti=1
                AND t.dinankavannuracisi >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                AND NOT EXISTS (
                    SELECT 1 
                    FROM thevani t2 
                    WHERE t2.balakedara = t.balakedara
                    AND t2.shonu < t.shonu
                )
            ");

            if (!$q) {
                die("DEPOSIT QUERY ERROR: " . mysqli_error($conn));
            }

            $d = mysqli_fetch_assoc($q);

            if (!empty($d['amount']) && $d['amount'] > 0) {

                $data[] = [
                    "uid" => $id,
                    "amount" => $d['amount']
                ];

                $totalUsers++;
                $totalAmount += $d['amount'];
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>SHREE WIN PRO ADMIN | WHITE PRO DASHBOARD</title>
<script src="https://cdn.tailwindcss.com"></script>

<style>
body{
background:#f1f5f9;
font-family:Arial;
}

/* 🔥 TOP BRAND BAR */
.topbar{
background:white;
padding:15px;
border-radius:16px;
box-shadow:0 10px 30px rgba(0,0,0,0.08);
margin-bottom:20px;
animation:fadeIn 1s ease;
}

/* GLASS CARD */
.card{
background:white;
border-radius:18px;
box-shadow:0 10px 25px rgba(0,0,0,0.06);
border:1px solid #e2e8f0;
}

/* ROW EFFECT */
.row:hover{
background:#eff6ff;
transform:scale(1.01);
transition:0.2s;
}

/* BUTTON */
.btn{
background:#2563eb;
color:white;
padding:10px 18px;
border-radius:12px;
transition:0.3s;
}
.btn:hover{
transform:scale(1.05);
background:#1d4ed8;
}

/* TELEGRAM FLOAT */
.telegram{
position:fixed;
bottom:20px;
right:20px;
background:#229ED9;
color:white;
padding:14px 18px;
border-radius:50px;
font-weight:bold;
box-shadow:0 10px 20px rgba(0,0,0,0.15);
transition:0.3s;
}
.telegram:hover{
transform:scale(1.1);
}

/* ANIMATION */
@keyframes fadeIn{
from{opacity:0; transform:translateY(-10px);}
to{opacity:1; transform:translateY(0);}
}
</style>
</head>

<body>

<!-- 🔥 TELEGRAM BUTTON -->
<a href="https://t.me/zayro_o" class="telegram">
💬 SHREE WIN PRO ADMIN
</a>

<div class="max-w-5xl mx-auto p-6">

<!-- 🔥 BRAND HEADER -->
<div class="topbar text-center">
    <h1 class="text-2xl font-bold text-gray-800">💎 PRICE: PREMIUM SYSTEM</h1>
    <p class="text-blue-600 font-bold">SHREE WIN PRO ADMIN DASHBOARD SYSTEM</p>
</div>

<!-- TITLE -->
<h1 class="text-2xl font-bold mb-5">🔥 Level 1 First Deposit (Last 7 Days)</h1>

<!-- SEARCH -->
<form method="GET" class="flex gap-3 mb-5">
<input type="number" name="uid" value="<?= $uid ?>" placeholder="Enter UID" class="border p-3 rounded w-full shadow-sm">
<button class="btn">Search</button>
</form>

<?php if($error): ?>
<div class="text-red-500 font-bold mb-3">⚠ <?= $error ?></div>
<?php endif; ?>

<?php if($uid && !$error): ?>

<!-- SUMMARY -->
<div class="card p-4 mb-4">
<p class="font-bold">👥 First Deposit Users: <?= $totalUsers ?></p>
<p class="font-bold text-green-600">💰 Total Amount: ₹<?= $totalAmount ?></p>
</div>

<!-- TABLE -->
<div class="card overflow-hidden">
    <table class="w-full text-left">
<tr class="border-b bg-gray-100">
<th class="p-3">UID</th>
<th class="p-3">First Deposit Amount</th>
</tr>

<?php if(!empty($data)): ?>
    <?php foreach($data as $r): ?>
    <tr class="row border-b">
        <td class="p-3 font-bold">#<?= $r['uid'] ?></td>
        <td class="p-3 text-green-600 font-bold">₹<?= $r['amount'] ?></td>
    </tr>
    <?php endforeach; ?>
<?php else: ?>
    <tr>
        <td colspan="2" class="p-3 text-center text-gray-500">
            No data found
        </td>
    </tr>
<?php endif; ?>

</table>

</div>

<?php endif; ?>

</div>

</body>
</html>
