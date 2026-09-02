<?php
include("api/conn.php");

$uid = isset($_GET['uid']) ? intval($_GET['uid']) : 0;

$error = "";
$data = [];
$totalUsers = 0;

if ($uid != 0) {

    // 🔥 GET OWN CODE
    $stmt = $conn->prepare("SELECT owncode FROM shonu_subjects WHERE id=?");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $owncode = $row['owncode'] ?? '';

    if (empty($owncode)) {
        $error = "Invalid UID";
    } else {

        // 🔥 LEVEL 1 USERS
        $users = mysqli_query($conn, "
            SELECT id 
            FROM shonu_subjects 
            WHERE code='$owncode'
        ");

        if (!$users) {
            die("USER QUERY ERROR: " . mysqli_error($conn));
        }

        while ($u = mysqli_fetch_assoc($users)) {

            $id = $u['id'];

            // 🔥 COUNT DEPOSIT TIMES + TOTAL AMOUNT
            $q = mysqli_query($conn, "
                SELECT 
                    COUNT(*) as total_times,
                    SUM(motta+0) as total_amount
                FROM thevani
                WHERE balakedara='$id'
                AND sthiti=1
            ");

            if (!$q) {
                die("DEPOSIT QUERY ERROR: " . mysqli_error($conn));
            }

            $d = mysqli_fetch_assoc($q);

            // 🔥 ONLY 3+ TIMES DEPOSIT USERS
            if ($d['total_times'] >= 3) {

                $data[] = [
                    "uid" => $id,
                    "times" => $d['total_times'],
                    "amount" => $d['total_amount']
                ];

                $totalUsers++;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>3x Deposit Users</title>
<script src="https://cdn.tailwindcss.com"></script>

<style>
body{
background:#f1f5f9;
font-family:Arial;
}

.card{
background:white;
border-radius:16px;
box-shadow:0 10px 25px rgba(0,0,0,0.08);
}

.row:hover{
background:#e0f2fe;
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
}
</style>
</head>

<body>

<!-- 🔥 TELEGRAM -->
<a href="https://t.me/zayro_o" class="telegram">
💬 SHREE WIN PRO ADMIN
</a>

<div class="max-w-5xl mx-auto p-6">

<h1 class="text-2xl font-bold mb-5">🔥 Level 1 - 3+ Times Deposit Users</h1>

<!-- SEARCH -->
<form method="GET" class="flex gap-3 mb-5">
<input type="number" name="uid" value="<?= $uid ?>" class="border p-3 rounded w-full" placeholder="Enter UID">
<button class="btn">Search</button>
</form>

<?php if($error): ?>
<div class="text-red-500 font-bold mb-3">⚠ <?= $error ?></div>
<?php endif; ?>

<?php if($uid && !$error): ?>

<!-- SUMMARY -->
<div class="card p-4 mb-4">
<p class="font-bold">👥 Users (3+ Deposits): <?= $totalUsers ?></p>
</div>

<!-- TABLE -->
<div class="card overflow-hidden">

<table class="w-full text-left">
<tr class="border-b bg-gray-100">
<th class="p-3">UID</th>
<th class="p-3">Deposit Times</th>
<th class="p-3">Total Amount</th>
</tr>

<?php if(!empty($data)): ?>
    <?php foreach($data as $r): ?>
    <tr class="row border-b">
        <td class="p-3 font-bold">#<?= $r['uid'] ?></td>
        <td class="p-3 text-blue-600 font-bold"><?= $r['times'] ?> Times</td>
        <td class="p-3 text-green-600 font-bold">₹<?= $r['amount'] ?></td>
    </tr>
    <?php endforeach; ?>
<?php else: ?>
    <tr>
        <td colspan="3" class="p-3 text-center text-gray-500">
            No users found with 3+ deposits
        </td>
    </tr>
<?php endif; ?>

</table>

</div>

<?php endif; ?>

</div>

</body>
</html>
