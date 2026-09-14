<?php
session_start();
include "conn.php";



if(isset($_POST['go'])){
    $uid = trim($_POST['uid']);

    if(!empty($uid)){
        header("Location: user-details.php?user=".$uid);
        exit();
    } else {
        $msg = "❌ UID লিখুন!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>UID Redirect Panel</title>

<style>
body{
    margin:0;
    font-family:Arial;
    background:#fff;
}

.container{
    width:400px;
    margin:80px auto;
    padding:20px;
    box-shadow:0 0 20px rgba(0,0,0,0.1);
    border-radius:12px;
}

h2{
    text-align:center;
}

input{
    width:100%;
    padding:10px;
    margin:10px 0;
    border:1px solid #ccc;
    border-radius:8px;
}

button{
    width:100%;
    padding:10px;
    background:#000;
    color:#fff;
    border:none;
    border-radius:8px;
    cursor:pointer;
}

button:hover{
    background:red;
}

.msg{
    text-align:center;
    color:red;
    font-weight:bold;
}

.telegram{
    display:block;
    text-align:center;
    background:#0088cc;
    color:#fff;
    padding:10px;
    border-radius:8px;
    text-decoration:none;
    margin-bottom:10px;
}
</style>
</head>

<body>

<div class="container">

<h2>🔎 UID SEARCH & OPEN</h2>

<!-- DAMAN PRO ADMIN BUTTON -->
<a class="telegram" href="https://t.me/zayro_o" target="_blank">SHREE WIN PRO ADMIN</a>

<?php if(isset($msg)){ ?>
<div class="msg"><?php echo $msg; ?></div>
<?php } ?>

<form method="post">
    <input type="text" name="uid" placeholder="Enter UID" required>
    <button name="go">OPEN USER</button>
</form>

</div>

</body>
</html>
