<?php
include("api/conn.php");

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["userId"])) {
    $userId = intval($_POST["userId"]);
    $sql = "UPDATE thevani SET firstbonus = 1 WHERE balakedara = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);

    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "error";
    }
}
?>
