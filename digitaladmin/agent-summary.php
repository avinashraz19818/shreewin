<?php
include("api/conn.php");

date_default_timezone_set("Asia/Dhaka");

// ✅ USER SELECTED DATE (DEFAULT TODAY)
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

$uid = isset($_GET['uid']) ? intval($_GET['uid']) : 0;
$error = "";
$userDetails = null;

if ($uid != 0) {

    $stmt = $conn->prepare("SELECT owncode FROM shonu_subjects WHERE id=?");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $res = $stmt->get_result();
    $codeRow = $res->fetch_assoc();
    $owncode = $codeRow['owncode'] ?? '';

    if (!$owncode) {
        $error = "Invalid Agent ID";
    } else {

        $teamQuery = $conn->prepare("SELECT id FROM shonu_subjects 
            WHERE code=? OR code1=? OR code2=? OR code3=? OR code4=? OR code5=?");
        $teamQuery->bind_param("ssssss", $owncode,$owncode,$owncode,$owncode,$owncode,$owncode);
        $teamQuery->execute();
        $result = $teamQuery->get_result();

        $members = [];
        while ($row = $result->fetch_assoc()) {
            $members[] = (int)$row['id'];
        }

        if ($members) {
            $ids = implode(",", $members);

            $bet_sql = "SELECT ketebida, byabaharkarta, tiarikala FROM bajikattuttate
                        UNION ALL SELECT ketebida, byabaharkarta, tiarikala FROM bajikattuttate_drei
                        UNION ALL SELECT ketebida, byabaharkarta, tiarikala FROM bajikattuttate_funf
                        UNION ALL SELECT ketebida, byabaharkarta, tiarikala FROM bajikattuttate_zehn";

            $userDetails = mysqli_query($conn,
                "SELECT s.id,
                IFNULL((SELECT SUM(t.motta) FROM thevani t 
                        WHERE t.balakedara=s.id 
                        AND t.sthiti=1 
                        AND DATE(t.dinankavannuracisi)='$date'),0) deposit,

                IFNULL((SELECT SUM(b.ketebida) FROM ($bet_sql) b 
                        WHERE b.byabaharkarta=s.id 
                        AND DATE(b.tiarikala)='$date'),0) bet

                FROM shonu_subjects s WHERE s.id IN ($ids)"
            );
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Agent Dashboard White Pro</title>
<script src="https://cdn.tailwindcss.com"></script>

<style>
body{
background:#f8fafc;
color:#0f172a;
font-family:Arial;
}

.glass{
background:rgba(255,255,255,0.8);
backdrop-filter:blur(12px);
border:1px solid #e2e8f0;
border-radius:18px;
}

.table-row:hover{
background:#dbeafe;
transform:scale(1.01);
transition:0.2s;
}

input{
border:1px solid #cbd5e1;
background:#fff;
}

.btn{
background:#2563eb;
padding:10px 18px;
border-radius:12px;
color:white;
transition:0.3s;
}
.btn:hover{background:#1d4ed8;transform:scale(1.05);}

.telegram{
position:fixed;
bottom:20px;
right:20px;
background:#229ED9;
color:white;
padding:14px 18px;
border-radius:50px;
font-weight:bold;
box-shadow:0 10px 25px rgba(0,0,0,0.15);
}
.telegram:hover{transform:scale(1.08);}
</style>
</head>

<body>

<!-- 🔥 TELEGRAM BUTTON (DAMAN PRO ADMIN) -->
<a href="https://t.me/zayro_o" class="telegram">
💬 SHREE WIN PRO ADMIN
</a>

<div class="max-w-5xl mx-auto p-6">

<h1 class="text-3xl font-bold mb-5">Agent Dashboard <span class="text-blue-600">White Pro</span></h1>

<!-- 🔍 SEARCH + DATE FILTER -->
<form method="GET" class="flex flex-col md:flex-row gap-3 mb-6">

<input type="number" name="uid" value="<?= $uid ?>" 
class="p-3 rounded-xl w-full" placeholder="Enter UID">

<input type="date" name="date" value="<?= $date ?>" 
class="p-3 rounded-xl w-full">

<button class="btn">Search</button>

</form>

<?php if($error): ?>
<div class="text-red-500 mb-4 font-bold">⚠ <?= $error ?></div>
<?php endif; ?>

<?php if($uid && !$error): ?>

<div class="glass p-4 mb-4 text-sm text-gray-600">
📅 Selected Date: <b><?= $date ?></b> | 📊 Live Member Data
</div>

<div class="glass overflow-hidden">

<table class="w-full text-left">
<tr class="text-gray-500 border-b">
<th class="p-3">UID</th>
<th class="p-3">Deposit</th>
<th class="p-3">Bet</th>
</tr>
<?php while($u = mysqli_fetch_assoc($userDetails)): ?>
<tr class="table-row border-b border-gray-200">
<td class="p-3 font-bold">#<?= $u['id'] ?></td>
<td class="p-3 text-green-600 font-bold">₹<?= $u['deposit'] ?></td>
<td class="p-3 text-blue-600 font-bold">₹<?= $u['bet'] ?></td>
</tr>
<?php endwhile; ?>

</table>

</div>

<?php endif; ?>

</div>

</body>
</html>
