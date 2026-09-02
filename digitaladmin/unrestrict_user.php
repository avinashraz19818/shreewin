<?php
include("api/conn.php");

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["userid"])) {
    $userid = mysqli_real_escape_string($conn, $_POST["userid"]);
    
    // Update query to unrestrict the user
    $updateQuery = "UPDATE shonu_subjects SET restrictbet = 0 WHERE id = '$userid'";
    if (mysqli_query($conn, $updateQuery)) {
        echo "<script>alert('User unrestricted successfully!'); window.location.href='restrictlist.php';</script>";
    } else {
        echo "<script>alert('Failed to unrestrict user!'); window.location.href='restrictlist.php';</script>";
    }
}
?>