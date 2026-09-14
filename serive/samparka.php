<?php
	$conn = mysqli_connect('localhost', 'club532583_veergame', 'club532583_veergame', 'club532583_veergame');
	
	if (!$conn) {
		echo "Error: " . mysqli_connect_error();
		exit();
	}
	
	date_default_timezone_set("Asia/Kolkata"); 
?>