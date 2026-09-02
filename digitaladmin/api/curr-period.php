<?php
	header('Content-Type: application/json');
	include("conn.php");
    $samasye = "SELECT atadaaidi FROM `gelluonduhogu_zehn` ORDER BY kramasankhye DESC LIMIT 1";
    $samasyephalitansa = $conn->query($samasye);
    $samasyephalitansa_dhadi = mysqli_fetch_assoc($samasyephalitansa);
	echo json_encode(['curr_period' => $samasyephalitansa_dhadi['atadaaidi']]);
?>