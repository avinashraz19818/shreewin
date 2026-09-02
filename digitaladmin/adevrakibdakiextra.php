<?php
include("api/conn.php");

date_default_timezone_set("Asia/Dhaka");

$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$uid  = isset($_GET['uid']) ? intval($_GET['uid']) : 0;

$error = "";
$data = [];
$totalUsers = 0;
$totalAmount = 0;

if ($uid != 0) {

    // 🔥 GET OWN CODE
    $stmt = $conn->prepare("SELECT owncode FROM shonu_subjects WHERE id=?");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $owncode = $row['owncode'] ?? '';

    if (empty($owncode)) {
        $error = "Invalid UID or Owncode Missing";
    } else {

        // 🔥 LEVEL 1 USERS
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

            // 🔥 FIRST DEPOSIT ONLY (SAFE FOR VARCHAR DATE FIELD)
            $q = mysqli_query($conn, "
                SELECT SUM(motta+0) as amount
                FROM thevani
                WHERE balakedara='$id'
                AND sthiti=1
                AND dinankavannuracisi LIKE '$date%'
                AND NOT EXISTS (
                    SELECT 1 
                    FROM thevani t2 
                    WHERE t2.balakedara = '$id'
                    AND t2.dinankavannuracisi < '$date'
                    LIMIT 1
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
<title>Level 1 First Deposit Report</title>
<script src="https://cdn.tailwindcss.com"></script>

<style>
body{
background:#f8fafc;
font-family:Arial;
}

.card{
background:white;
border:1px solid #e2e8f0;
border-radius:16px;
}

.row:hover{
background:#dbeafe;
transform:scale(1.01);
transition:0.2s;
}

.btn{
background:#2563eb;
color:white;
padding:10px 18px;
border-radius:12px;
}

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
}
</style>
</head>

<body>

<!-- 🔥 TELEGRAM BUTTON -->
<a href="https://t.me/zayro_o" class="telegram">
💬 SHREE WIN PRO ADMIN
</a>

<div class="max-w-5xl mx-auto p-6">

<h1 class="text-2xl font-bold mb-5">🔥 Level 1 First Deposit Report</h1>

<!-- SEARCH -->
<form method="GET" class="flex gap-3 mb-5">
<input type="number" name="uid" value="<?= $uid ?>" placeholder="Enter UID" class="border p-2 rounded w-full">
<input type="date" name="date" value="<?= $date ?>" class="border p-2 rounded">
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
            No first deposit users found
        </td>
    </tr>
<?php endif; ?>

</table>

</div>
<?php endif; ?>

</div>

</body>
</html>
