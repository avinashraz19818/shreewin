<?php
session_start();
include("api/conn.php");

if(!isset($_SESSION['unohs'])){
    header("location: api/login.php");
    exit;
}

/* =========================
   APPROVE / REJECT
========================= */
if(isset($_POST['action']) && isset($_POST['id'])){

    $id = intval($_POST['id']);
    $action = $_POST['action'];

    $q = mysqli_query($conn,"SELECT * FROM mrcoder_withdrawals WHERE id='$id' AND sthithi='0'");

    if(mysqli_num_rows($q)==0){
        echo "already_done";
        exit;
    }

    $row = mysqli_fetch_assoc($q);

    $user   = $row['user_id'];
    $amount = floatval($row['amount']);

    if($action == "approve"){

        mysqli_query($conn,"UPDATE mrcoder_withdrawals SET sthithi='1' WHERE id='$id'");

        // MAIN WALLET ADD ✔ FIX
        mysqli_query($conn,"
            UPDATE shonu_kaichila 
            SET motta = COALESCE(motta,0) + $amount 
            WHERE balakedara='$user'
        ");

    } else {

        mysqli_query($conn,"UPDATE mrcoder_withdrawals SET sthithi='2' WHERE id='$id'");
    }

    echo "success";
    exit;
}

/* =========================
   ANALYTICS DATA
========================= */
$total = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as c FROM mrcoder_withdrawals"))['c'];
$pending = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as c FROM mrcoder_withdrawals WHERE sthithi=0"))['c'];
$approved = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as c FROM mrcoder_withdrawals WHERE sthithi=1"))['c'];
$rejected = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as c FROM mrcoder_withdrawals WHERE sthithi=2"))['c'];
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Admin Withdraw Dashboard</title>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

<style>

body{
    margin:0;
    font-family:Segoe UI;
    background:#f5f7fb;
}

/* HEADER */
.header{
    background:#fff;
    padding:15px 20px;
    font-size:22px;
    font-weight:700;
    box-shadow:0 3px 20px rgba(0,0,0,0.05);
}

/* GRID */
.container{
    padding:20px;
}

/* CARDS */
.card{
    background:#fff;
    padding:20px;
    border-radius:15px;
    box-shadow:0 10px 30px rgba(0,0,0,0.05);
    margin-bottom:20px;
}

/* BUTTON */
button{
    border:none;
    padding:6px 12px;
    border-radius:8px;
    cursor:pointer;
}

.view{background:#4f46e5;color:#fff;}
.approve{background:#16a34a;color:#fff;}
.reject{background:#dc2626;color:#fff;}

/* BADGE */
.badge{
    padding:5px 10px;
    border-radius:999px;
}

.pending{background:#facc15;}
.approved{background:#22c55e;color:#fff;}
.rejected{background:#ef4444;color:#fff;}

/* GRID ANALYTICS */
.grid{
    display:grid;
    grid-template-columns: repeat(4,1fr);
    gap:15px;
}

.box{
    background:#fff;
    padding:20px;
    border-radius:12px;
    box-shadow:0 5px 20px rgba(0,0,0,0.05);
    text-align:center;
}

.box h2{
    margin:0;
}

/* MODAL */
.modal{
    display:none;
    position:fixed;
    top:25%;
    left:50%;
    transform:translateX(-50%);
    background:#fff;
    padding:25px;
    border-radius:12px;
    box-shadow:0 10px 40px rgba(0,0,0,0.2);
}

</style>
</head>

<body>

<div class="header">💎 Admin Withdraw Dashboard</div>

<div class="container">

<!-- ANALYTICS -->
<div class="grid">

<div class="box">
<h2><?=$total?></h2>
<p>Total</p>
</div>

<div class="box">
<h2><?=$pending?></h2>
<p>Pending</p>
</div>

<div class="box">
<h2><?=$approved?></h2>
<p>Approved</p>
</div>

<div class="box">
<h2><?=$rejected?></h2>
<p>Rejected</p>
</div>

</div>

<!-- CHART -->
<div class="card">
<canvas id="chart" height="100"></canvas>
</div>

<!-- TABLE -->
<div class="card">

<table id="tbl" class="table table-striped">
<thead>
<tr>
<th>ID</th>
<th>User</th>
<th>Amount</th>
<th>Status</th>
<th>Action</th>
</tr>
</thead>

<tbody>

<?php
$q = mysqli_query($conn,"SELECT * FROM mrcoder_withdrawals ORDER BY id DESC");

while($r=mysqli_fetch_assoc($q)){
    if($r['sthithi']==0){
$status="Pending"; $cls="pending";
}elseif($r['sthithi']==1){
$status="Approved"; $cls="approved";
}else{
$status="Rejected"; $cls="rejected";
}
?>

<tr>
<td><?=$r['id']?></td>
<td><?=$r['user_id']?></td>
<td>₹<?=number_format($r['amount'],2)?></td>
<td><span class="badge <?=$cls?>"><?=$status?></span></td>
<td>

<?php if($r['sthithi']==0){ ?>
<button class="view"
data-id="<?=$r['id']?>"
data-user="<?=$r['user_id']?>"
data-amount="<?=$r['amount']?>">
View
</button>
<?php } else echo "-"; ?>

</td>
</tr>

<?php } ?>

</tbody>
</table>

</div>

</div>

<!-- MODAL -->
<div id="modal" class="modal">

<h3>Withdraw Action</h3>
<p id="info"></p>

<button id="ap" class="approve">Approve</button>
<button id="rp" class="reject">Reject</button>
<button onclick="$('#modal').hide()">Close</button>

</div>

<!-- SOUND -->
<audio id="soundApprove" src="https://www.soundjay.com/buttons/sounds/button-3.mp3"></audio>
<audio id="soundReject" src="https://www.soundjay.com/buttons/sounds/button-10.mp3"></audio>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
$(function(){

$('#tbl').DataTable();

let id="";

/* OPEN MODAL */
$('.view').click(function(){
id=$(this).data('id');

$('#info').html(
"User: <b>"+$(this).data('user')+"</b><br>Amount: <b>₹"+$(this).data('amount')+"</b>"
);

$('#modal').show();
});

/* APPROVE */
$('#ap').click(function(){

document.getElementById("soundApprove").play();

$.post("",{action:"approve",id:id},function(res){
alert("Approved ✔");
location.reload();
});

});

/* REJECT */
$('#rp').click(function(){

document.getElementById("soundReject").play();

$.post("",{action:"reject",id:id},function(res){
alert("Rejected ❌");
location.reload();
});

});

/* CHART */
new Chart(document.getElementById("chart"),{
type:"bar",
data:{
labels:["Total","Pending","Approved","Rejected"],
datasets:[{
label:"Withdraw Stats",
data:[<?=$total?>,<?=$pending?>,<?=$approved?>,<?=$rejected?>],
backgroundColor:["#6366f1","#facc15","#22c55e","#ef4444"]
}]
}
});

});
</script>

</body>
</html>