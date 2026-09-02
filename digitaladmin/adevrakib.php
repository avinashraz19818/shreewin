<?php
session_start();


include "conn.php";

/* ===== COUNTS ===== */
$total_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM shonu_subjects"))['total'];

$active_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM shonu_subjects WHERE status = 1"))['total'];

$banned_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM shonu_subjects WHERE status = 0"))['total'];

$wallet_q = mysqli_query($conn, "SELECT SUM(CAST(motta AS DECIMAL(10,2))) AS total_wallet FROM shonu_kaichila");
$total_wallet = mysqli_fetch_assoc($wallet_q)['total_wallet'] ?? 0;

/* ===== USERS ===== */
$users_query = mysqli_query($conn, "
SELECT 
    s.id, s.mobile, s.owncode, s.ishonup, s.createdate,
    s.password, s.pwd,
    k.motta AS wallet,
    k.rebet AS recharge,
    k.bonus AS first_recharge,
    k.ifsc,
    k.accountno
FROM shonu_subjects s
LEFT JOIN shonu_kaichila k ON s.id = k.balakedara
ORDER BY s.id DESC
");

$users = [];
while($row = mysqli_fetch_assoc($users_query)){
    $row['wallet'] = $row['wallet'] ?? 0;
    $row['recharge'] = $row['recharge'] ?? 0;
    $row['first_recharge'] = $row['first_recharge'] ?? 0;
    $users[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Admin Dashboard</title>

<link rel="stylesheet" href="css/style.css">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

<style>
body{
    background:#f1f5f9;
    font-family:Arial;
}

/* Cards */
.card{
    background:#fff;
    border-radius:12px;
    padding:15px;
    box-shadow:0 5px 15px rgba(0,0,0,0.05);
}

/* Table */
table{
    width:100%;
    border-collapse:collapse;
    background:#fff;
}

th,td{
    padding:10px;
    border-bottom:1px solid #eee;
    text-align:center;
}

/* Button */
.btn{
    padding:5px 10px;
    border:1px solid #ccc;
    background:#fff;
    cursor:pointer;
    border-radius:5px;
}
.btn:hover{
    background:#f0f0f0;
}
</style>
</head>

<body>

<h2 style="text-align:center;">Admin Dashboard</h2>

<!-- SUMMARY -->
<div style="display:flex;gap:10px;justify-content:center;margin:20px;">
    <div class="card">Total Users<br><b><?= $total_users ?></b></div>
    <div class="card">Active Users<br><b><?= $active_users ?></b></div>
    <div class="card">Banned Users<br><b><?= $banned_users ?></b></div>
    <div class="card">Wallet<br><b>₹<?= number_format($total_wallet,2) ?></b></div>
</div>

<!-- TABLE -->
<div style="padding:20px;">
<table>
<thead>
<tr>
    <th>Mobile</th>
    <th>ID</th>
    <th>User Code</th>
    <th>IP</th>
    <th>Wallet</th>
    <th>Recharge</th>
    <th>Date</th>
    <th>Password</th>
    <th>IFSC</th>
    <th>Account</th>
</tr>
</thead>

<tbody>
<?php foreach($users as $u): ?>
<tr>
    <td><?= $u['mobile'] ?></td>
    <td><?= $u['id'] ?></td>
    <td><?= $u['owncode'] ?></td>
    <td><?= $u['ishonup'] ?></td>
    <td>₹<?= $u['wallet'] ?></td>
    <td>₹<?= $u['recharge'] ?></td>
    <td><?= $u['createdate'] ?></td>

    <!-- PASSWORD -->
    <td>
        <span id="p<?= $u['id'] ?>">••••••</span>
        <button class="btn"
        onclick="togglePass(<?= $u['id'] ?>,
        '<?= $u['pwd'] ? $u['pwd'] : $u['password'] ?>')">
        👁️
        </button>
    </td>

    <td><?= $u['ifsc'] ?: 'N/A' ?></td>
    <td><?= $u['accountno'] ?: 'N/A' ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<script>
function togglePass(id, pass){
    let el = document.getElementById("p"+id);

    if(el.innerText === "••••••"){
        el.innerText = pass;
    }else{
        el.innerText = "••••••";
    }
}
</script>

</body>
</html>