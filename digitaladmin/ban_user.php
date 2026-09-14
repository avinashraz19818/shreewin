<?php
include("conn.php");

$id=$_POST['id'];
$status=$_POST['status'];

mysqli_query($conn,"UPDATE shonu_subjects SET status='$status' WHERE id='$id'");