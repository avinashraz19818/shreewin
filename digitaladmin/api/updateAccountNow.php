<?php
	include('conn.php');
	if(isset($_POST['editid']))
	{
		$accountno = mysqli_real_escape_string($conn, $_POST['accountno']);
		$roleid = ($_POST['editid']);
		$oldaccountno = mysqli_real_escape_string($conn, $_POST['oldaccountno']);
		$role_query=mysqli_query($conn, "UPDATE `khate` SET `khatesankhye`='$accountno' WHERE `byabaharkarta`=$roleid AND `khatesankhye`='$oldaccountno'");
		if($role_query){	
			echo"1";
		}
		else{ 
			echo"0";
		}				
	}		
?>