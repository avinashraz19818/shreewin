<?php
session_start();
include("api/conn.php");

if(!isset($_SESSION['unohs'])){
    header("location: api/login.php");
    exit;
}

/* =========================
   ADD / REMOVE TURNOVER
========================= */
if(isset($_POST['action'])){

    $uid = mysqli_real_escape_string($conn, $_POST['uid']);
    $amount = floatval($_POST['amount']);
    $action = $_POST['action'];

    $q = mysqli_query($conn,"SELECT * FROM shonu_kaichila WHERE balakedara='$uid'");

    if(mysqli_num_rows($q)==0){
        echo "user_not_found";
        exit;
    }

    if($action=="add"){
        mysqli_query($conn,"
            UPDATE shonu_kaichila 
            SET mottta = mottta + $amount 
            WHERE balakedara='$uid'
        ");
        echo "added";
        exit;
    }

    if($action=="remove"){
        mysqli_query($conn,"
            UPDATE shonu_kaichila 
            SET mottta = GREATEST(mottta - $amount, 0)
            WHERE balakedara='$uid'
        ");
        echo "removed";
        exit;
    }

    echo "error";
    exit;
}

/* =========================
   TOP DATA FOR CHART
========================= */
$labels = [];
$data = [];

$q = mysqli_query($conn,"
    SELECT balakedara, mottta 
    FROM shonu_kaichila 
    ORDER BY mottta DESC 
    LIMIT 5
");

while($r=mysqli_fetch_assoc($q)){
    $labels[] = $r['balakedara'];
    $data[] = $r['mottta'];
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Turnover Dashboard</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
body{
    background:#f5f7fb;
    font-family:Arial;
}

.card{
    border:none;
    border-radius:15px;
    box-shadow:0 8px 25px rgba(0,0,0,0.06);
}

.title{
    font-weight:700;
}

.btn{
    border-radius:10px;
}

.table td, .table th{
    vertical-align:middle;
}
</style>
</head>

<body>

<div class="container mt-4">

<!-- INPUT -->
<div class="card p-4 mb-4">

<h3 class="title">📊 Turnover Dashboard</h3>

<div class="row g-2">
    <div class="col-md-4">
        <input type="text" id="uid" class="form-control" placeholder="User ID">
    </div>

    <div class="col-md-4">
        <input type="number" id="amount" class="form-control" placeholder="Amount">
    </div>

    <div class="col-md-4 d-flex gap-2">
        <button class="btn btn-success w-50" onclick="send('add')">➕ Add</button>
        <button class="btn btn-danger w-50" onclick="send('remove')">➖ Remove</button>
    </div>
</div>

</div>

<!-- CHART -->
<div class="card p-4 mb-4">
    <h5>📈 Top 5 Turnover Users</h5>
    <canvas id="chart"></canvas>
</div>

<!-- TABLE -->
<div class="card p-3">
<h5>👤 All Users Turnover</h5>

<table class="table table-striped">
<thead>
<tr>
    <th>UID</th>
    <th>Main Balance</th>
    <th>Turnover</th>
</tr>
</thead>

<tbody>

<?php
$q = mysqli_query($conn,"SELECT * FROM shonu_kaichila ORDER BY mottta DESC");

while($r=mysqli_fetch_assoc($q)){
?>
<tr>
    <td><?=$r['balakedara']?></td>
    <td>₹ <?=number_format($r['motta'],2)?></td>
    <td><b><?=$r['mottta']?></b></td>
</tr>
<?php } ?>

</tbody>
</table>

</div>

</div>

<!-- SOUND -->
<audio id="ok" src="https://www.soundjay.com/buttons/sounds/button-3.mp3"></audio>
<audio id="err" src="https://www.soundjay.com/buttons/sounds/button-10.mp3"></audio>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script>

/* =========================
   ACTION
========================= */
function send(type){

let uid = $("#uid").val();
let amount = $("#amount").val();

if(uid=="" || amount==""){
    document.getElementById("err").play();
    alert("Fill all fields");
    return;
}

$.post("",{
    action:type,
    uid:uid,
    amount:amount
},function(res){

    if(res=="added"){
        document.getElementById("ok").play();
        alert("Turnover Added");
        location.reload();
    }
    else if(res=="removed"){
        document.getElementById("ok").play();
        alert("Turnover Removed");
        location.reload();
    }
    else{
        document.getElementById("err").play();
        alert(res);
    }

});

}

/* =========================
   CHART
========================= */
const ctx = document.getElementById('chart');

new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?=json_encode($labels)?>,
        datasets: [{
            label: 'Turnover',
            data: <?=json_encode($data)?>,
            backgroundColor: '#4f46e5'
        }]
    }
});

</script>

</body>
</html>