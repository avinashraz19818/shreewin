<?php
	include("conn.php");
	require_once dirname(__DIR__, 2) . '/saas_lottery/admin_override.php';
	$username = "";
	$err = "";

	// if request method is post
	if ($_SERVER['REQUEST_METHOD'] == "POST"){
	 
		$username = trim($_POST['stat']);
		$type = isset($_POST['type']) ? (string)$_POST['type'] : '';

		$gameMap = array(
			'wingo30'=>'WinGo_30S','wingo1'=>'WinGo_1M','wingo3'=>'WinGo_3M','wingo5'=>'WinGo_5M',
			'k31'=>'K3_1M','k33'=>'K3_3M','k35'=>'K3_5M','k310'=>'K3_10M',
			'5d1'=>'D5_1M','5d3'=>'D5_3M','5d5'=>'D5_5M','5d10'=>'D5_10M',
			'ktrx'=>'TrxWinGo_1M','ktrx3'=>'TrxWinGo_3M','ktrx5'=>'TrxWinGo_5M','ktrx10'=>'TrxWinGo_10M',
			'motoracing'=>'MotoRace_1M',
		);
		if (isset($gameMap[$type])) {
			try {
				sl_admin_override_clear($conn, $gameMap[$type]);
				echo 'Reset';
			} catch (Throwable $e) {
				http_response_code(500);
				echo 'Unable to reset';
			}
			exit;
		}
		
		switch ($type) {
            case "wingo30":
                $table = "hastacalita_phalitansa_zehn";
                break;
            case "wingo1":
                $table = "hastacalita_phalitansa";
                break;
            case "wingo3":
                $table = "hastacalita_phalitansa_drei";
                break;
            case "wingo5":
                $table = "hastacalita_phalitansa_funf";
                break;
            case "k31":
                $table = "hastacalita_phalitansa_kemeru";
                break;
            case "k33":
                $table = "hastacalita_phalitansa_kemeru_drei";
                break;
            case "k35":
                $table = "hastacalita_phalitansa_kemeru_funf";
                break;
            case "k310":
                $table = "hastacalita_phalitansa_kemeru_zehn";
                break;
            case "5d1":
                $table = "hastacalita_phalitansa_aidudi";
                break;
            case "5d3":
                $table = "hastacalita_phalitansa_aidudi_drei";
                break;
            case "5d5":
                $table = "hastacalita_phalitansa_aidudi_funf";
                break;
            case "5d10":
                $table = "hastacalita_phalitansa_aidudi_zehn";
                break;
            default:
                header("location: ../$type.php"); // Fallback case
                break;
        }
        
		if(empty($err))
		{
		   
			$sqla = mysqli_query($conn,"UPDATE $table SET sthiti='0'");
			
			// For K3 games, also deactivate the prediction for force override
			if($type=="k31" || $type=="k33" || $type=="k35" || $type=="k310"){
    $predictionTable = "admin_predictions";
    mysqli_query($conn, "UPDATE $predictionTable SET is_active = FALSE WHERE game_type = '$type'");
}

if($type=="5d1"){
    $predictionTable = "admin_predictions";
    mysqli_query($conn, "UPDATE $predictionTable SET is_active = FALSE WHERE game_type = '$type'");
}

if($type=="5d3"){
    $predictionTable = "admin_predictions";
    mysqli_query($conn, "UPDATE $predictionTable SET is_active = FALSE WHERE game_type = '$type'");
}

if($type=="5d5"){
    $predictionTable = "admin_predictions";
    mysqli_query($conn, "UPDATE $predictionTable SET is_active = FALSE WHERE game_type = '$type'");
}

if($type=="5d10"){
    $predictionTable = "admin_predictions";
    mysqli_query($conn, "UPDATE $predictionTable SET is_active = FALSE WHERE game_type = '$type'");
}

if($type=="ktrx"){
    $predictionTable = "admin_predictions";
    mysqli_query($conn, "UPDATE $predictionTable SET is_active = FALSE WHERE game_type = '$type'");
}

if($type=="ktrx3"){
    $predictionTable = "admin_predictions";
    mysqli_query($conn, "UPDATE $predictionTable SET is_active = FALSE WHERE game_type = '$type'");
}

if($type=="ktrx5"){
    $predictionTable = "admin_predictions";
    mysqli_query($conn, "UPDATE $predictionTable SET is_active = FALSE WHERE game_type = '$type'");
}

if($type=="ktrx10"){
    $predictionTable = "admin_predictions";
    mysqli_query($conn, "UPDATE $predictionTable SET is_active = FALSE WHERE game_type = '$type'");
}
			
			echo "Reset";
		}
	}
?>
