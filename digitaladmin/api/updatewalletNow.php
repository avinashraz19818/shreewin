<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('conn.php');

if(isset($_POST['editid']))
{
    $amount = mysqli_real_escape_string($conn, $_POST['amount']);
    $balance = mysqli_real_escape_string($conn, $_POST['balance']);
    $roleid = $_POST['editid'];
    
    // Sirf wallet update karo
    $update_query = "UPDATE `shonu_kaichila` SET `motta` = '$amount' WHERE `balakedara` = '$roleid'";
    
    if(mysqli_query($conn, $update_query)){
        // Always return success
        echo "1";
    } else {
        echo "0";
    }
}
?>