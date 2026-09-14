<?php
session_start();
if(empty($_SESSION['unohs'])){
 header("location:index.php?msg=unauthorized");
}
include ("conn.php");

// Summary
$total_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM shonu_subjects"))['total'];
$active_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM shonu_subjects WHERE status = 1"))['total'];
$banned_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM shonu_subjects WHERE status = 0"))['total'];

$wallet_q = mysqli_query($conn, "SELECT SUM(CAST(motta AS DECIMAL(10,2))) AS total_wallet FROM shonu_kaichila");
$total_wallet = mysqli_fetch_assoc($wallet_q)['total_wallet'] ?? 0;
?>

<!DOCTYPE html>
<html>
<head>
<title>Admin Dashboard</title>

<!-- Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">

<!-- DataTables -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.12.1/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>

<style>
body {
    background: #f5f7fb;
    font-family: 'Poppins', sans-serif;
}

/* Header */
.header {
    background:#fff;
    padding:15px 25px;
    box-shadow:0 2px 10px rgba(0,0,0,0.05);
    display:flex;
    justify-content:space-between;
    align-items:center;
    border-radius:10px;
    margin:15px;
}

/* Cards */
.cards {
    display:flex;
    gap:15px;
    margin:15px;
}
.card {
    flex:1;
    padding:20px;
    border-radius:15px;
    color:#fff;
    box-shadow:0 8px 20px rgba(0,0,0,0.05);
}
.c1 {background:linear-gradient(135deg,#4facfe,#00f2fe);}
.c2 {background:linear-gradient(135deg,#43e97b,#38f9d7);}
.c3 {background:linear-gradient(135deg,#fa709a,#fee140);}
.c4 {background:linear-gradient(135deg,#667eea,#764ba2);}

/* Table Box */
.table-box {
    background:#fff;
    margin:15px;
    padding:20px;
    border-radius:15px;
    box-shadow:0 5px 20px rgba(0,0,0,0.05);
}

/* Buttons */
.btn {
    padding:6px 10px;
    border:none;
    border-radius:8px;
    cursor:pointer;
}
.btn-show {
    background:#3498db;
    color:#fff;
}

/* Table */
table.dataTable {
    border-radius:10px;
    overflow:hidden;
}
</style>
</head>

<body>

<div class="header">
    <h2>Admin Dashboard</h2>
    <a href="logout.php">Logout</a>
</div>

<div class="cards">
    <div class="card c1">Total Users<br><h2><?= $total_users ?></h2></div>
    <div class="card c2">Active Users<br><h2><?= $active_users ?></h2></div>
    <div class="card c3">Banned Users<br><h2><?= $banned_users ?></h2></div>
    <div class="card c4">Wallet<br><h2>₹<?= number_format($total_wallet,2) ?></h2></div>
</div>

<div class="table-box">
    <h3>Manage Users</h3>
    <table id="userTable" class="display">
        <thead>
            <tr>
                <th>Mobile</th>
                <th>Cust ID</th>
                <th>IP</th>
                <th>User ID</th>
                <th>Password</th>
            </tr>
        </thead>
    </table>
</div>

<script>
$(document).ready(function(){
$('#userTable').DataTable({
    processing:true,
    serverSide:true,
    ajax:"manage_user_data.php",
    pageLength:25
});
});

// Show password
function showPass(btn, pass){
    btn.previousElementSibling.innerText = pass;
}
</script>

</body>
</html>