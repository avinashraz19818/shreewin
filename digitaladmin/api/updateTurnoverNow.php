<?php
	include('conn.php');
	if(isset($_POST['editid']))
	{
		$turnover       = mysqli_real_escape_string($conn, $_POST['turnover']);
		$plus_mins      = mysqli_real_escape_string($conn, $_POST['turnover_action']);
		$mottta_type    = mysqli_real_escape_string($conn, $_POST['fixTurnoverSwitch']);
		$roleid         = ($_POST['editid']);
		$date           = date( 'Y-m-d h:i:s' );

		$role_query=mysqli_query($conn, "UPDATE `shonu_kaichila` SET `mottta`= '".$turnover."'  WHERE `balakedara` ='".$roleid."'");
		if($role_query){	
			echo"1";
		}
		else{ 
			echo"0";
		}				
	}		
?>