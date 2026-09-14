<?php
session_start();
if(empty($_SESSION['unohs'])){
 header("location:index.php?msg=unauthorized");
}
include("conn.php");

// Summary
$total = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) t FROM shonu_subjects"))['t'];
$active = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) t FROM shonu_subjects WHERE status=1"))['t'];
$today = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) t FROM shonu_subjects WHERE DATE(createdate)=CURDATE()"))['t'];
?>

<!DOCTYPE html>
<html>
<head>
<title>Pro Admin Panel</title>

<link href="https://fonts.googleapis.com/css2?family=Inter&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.datatables.net/1.12.1/css/jquery.dataTables.min.css">

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>

<style>
body{font-family:Inter;background:#f5f7fb;margin:0;}
.header{padding:20px;background:#fff;margin:10px;border-radius:12px;}
.cards{display:flex;gap:10px;margin:10px;}
.card{flex:1;background:#fff;padding:20px;border-radius:12px;box-shadow:0 5px 20px rgba(0,0,0,0.05);}
.box{margin:10px;background:#fff;padding:20px;border-radius:12px;}
.btn{padding:5px 10px;border:none;border-radius:6px;cursor:pointer;}
.btn-ban{background:#e74c3c;color:#fff;}
.btn-unban{background:#2ecc71;color:#fff;}
.btn-edit{background:#3498db;color:#fff;}
</style>
</head>

<body>

<div class="header"><h2>🔥 Admin Panel</h2></div>

<div class="cards">
<div class="card">Total Users<br><h2><?= $total ?></h2></div>
<div class="card">Active<br><h2><?= $active ?></h2></div>
<div class="card">Today<br><h2><?= $today ?></h2></div>
</div>

<div class="box">
<table id="table">
<thead>
<tr>
<th>Mobile</th>
<th>Wallet</th>
<th>Status</th>
<th>UID</th>
<th>Action</th>
</tr>
</thead>
</table>
</div>

<!-- Wallet Modal -->
<div id="modal" style="display:none;position:fixed;top:30%;left:40%;background:#fff;padding:20px;border-radius:10px;">
<input type="text" id="amount" placeholder="Amount">
<input type="hidden" id="uid">
<button onclick="saveAmount()">Save</button>
</div>

<script>
var table;

$(document).ready(function(){
table = $('#table').DataTable({
processing:true,
serverSide:true,
ajax:"manage_user_data.php"
});
});

// Ban / Unban
function toggleBan(id,status){
$.post("ban_user.php",{id:id,status:status},function(){
table.ajax.reload();
});
}

// Edit wallet
function editWallet(id){
document.getElementById("uid").value=id;
document.getElementById("modal").style.display="block";
}

// Save wallet
function saveAmount(){
var id=$("#uid").val();
var amount=$("#amount").val();

$.post("wallet_update.php",{id:id,amount:amount},function(){
alert("Updated");
location.reload();
});
}

// User details
function viewUser(id){
$.get("user_detail.php?id="+id,function(data){
alert(data);
});
}
</script>

</body>
</html>