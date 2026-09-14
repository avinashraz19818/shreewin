<?php
include("api/conn.php");

if (isset($_POST["userid"]) && isset($_POST["status"])) {
    $userid = mysqli_real_escape_string($conn, $_POST["userid"]);
    $newStatus = mysqli_real_escape_string($conn, $_POST["status"]);

    $sql = "UPDATE shonu_subjects SET status = '$newStatus' WHERE id = '$userid'";
    if (mysqli_query($conn, $sql)) {
        echo "success";
    } else {
        echo "error";
    }
}
?>
