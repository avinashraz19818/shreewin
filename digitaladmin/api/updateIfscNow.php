<?php
	include('conn.php');
	if(isset($_POST['editid']))
	{
		$ifsc = mysqli_real_escape_string($conn, $_POST['ifsc']);
		$roleid = ($_POST['editid']);
		$oldifsc = mysqli_real_escape_string($conn, $_POST['oldifsc']);
		$role_query=mysqli_query($conn, "UPDATE `khate` SET `kod`='$ifsc' WHERE `byabaharkarta`=$roleid AND `kod`='$oldifsc'");
		if($role_query){	
			echo"1";
		}
		else{ 
			echo"0";
		}				
	}		
?>