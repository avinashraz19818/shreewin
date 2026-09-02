<?php
	include("conn.php");
	require_once dirname(__DIR__, 2) . '/saas_lottery/admin_override.php';
	$username = "";
	$err = "";

	// if request method is post
	if ($_SERVER['REQUEST_METHOD'] == "POST"){
	 
		$username = isset($_POST['username']) ? trim((string)$_POST['username']) : '';
		$type = isset($_POST['type']) ? (string)$_POST['type'] : '';

		// Every manager targets one exact live SaaS issue. The selected result is
		// consumed once and subsequent issues automatically resume the real feed.
		$gameMap = array(
			'wingo30'=>'WinGo_30S','wingo1'=>'WinGo_1M','wingo3'=>'WinGo_3M','wingo5'=>'WinGo_5M',
			'k31'=>'K3_1M','k33'=>'K3_3M','k35'=>'K3_5M','k310'=>'K3_10M',
			'5d1'=>'D5_1M','5d3'=>'D5_3M','5d5'=>'D5_5M','5d10'=>'D5_10M',
			'ktrx'=>'TrxWinGo_1M','ktrx3'=>'TrxWinGo_3M','ktrx5'=>'TrxWinGo_5M','ktrx10'=>'TrxWinGo_10M',
			'motoracing'=>'MotoRace_1M',
		);
		if (isset($gameMap[$type])) {
			try {
				$requestedIssue = isset($_POST['issue_number']) ? (string)$_POST['issue_number'] : '';
				$createdBy = isset($_SESSION['unohs']) ? (string)$_SESSION['unohs'] : 'admin';
				sl_admin_override_set($conn, $gameMap[$type], $username, $requestedIssue, $createdBy);
				header('Location: ../' . $type . '.php');
				exit;
			} catch (Throwable $e) {
				http_response_code(400);
				echo '<h1 style="text-align:center">Unable to set next game issue</h1>';
				exit;
			}
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
		    if($type=="wingo30" || $type=="wingo1" || $type=="wingo3" || $type=="wingo5"){
    			$sqla = mysqli_query($conn,"UPDATE $table SET sthiti='0'");
    			$sql = "UPDATE $table SET sthiti='1' WHERE sankhye=$username";
		    }else{
		        $sqla = mysqli_query($conn,"UPDATE $table SET sthiti='0'");
    			$sql = "UPDATE $table SET sthiti='1', sankhye=$username WHERE shonu='1'";
		        
		                // For K3 games, also store the prediction number for force override
        if($type=="k31" || $type=="k33" || $type=="k35" || $type=="k310"){
            // Store the prediction number in a separate table for force override
            $predictionTable = "admin_predictions";
            
            // Check if table exists, if not create it
            $checkTable = mysqli_query($conn, "SHOW TABLES LIKE '$predictionTable'");
            if(mysqli_num_rows($checkTable) == 0) {
                $createTable = "CREATE TABLE $predictionTable (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    game_type VARCHAR(10) NOT NULL,
                    prediction_number VARCHAR(10) NOT NULL,
                    prediction_sum INT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    is_active BOOLEAN DEFAULT TRUE
                )";
                mysqli_query($conn, $createTable);
            }
            
            // Calculate sum for K3 games (3-digit number)
            $predictionSum = 0;
            if(is_numeric($username) && strlen($username) == 3) {
                $digits = str_split($username);
                $predictionSum = array_sum($digits);
            }
            
            // Deactivate old predictions for this game type
            mysqli_query($conn, "UPDATE $predictionTable SET is_active = FALSE WHERE game_type = '$type'");
            
            // Insert new prediction
            $insertPrediction = "INSERT INTO $predictionTable (game_type, prediction_number, prediction_sum) VALUES ('$type', '$username', '$predictionSum')";
            mysqli_query($conn, $insertPrediction);
        }
        
        // For 5D1 games, also store the prediction number for force override
        if($type=="5d1"){
            // Store the prediction number in a separate table for force override
            $predictionTable = "admin_predictions";
            
            // Check if table exists, if not create it
            $checkTable = mysqli_query($conn, "SHOW TABLES LIKE '$predictionTable'");
            if(mysqli_num_rows($checkTable) == 0) {
                $createTable = "CREATE TABLE $predictionTable (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    game_type VARCHAR(10) NOT NULL,
                    prediction_number VARCHAR(10) NOT NULL,
                    prediction_sum INT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    is_active BOOLEAN DEFAULT TRUE
                )";
                mysqli_query($conn, $createTable);
            }
            
            // Calculate sum for 5D1 games (5-digit number)
            $predictionSum = 0;
            if(is_numeric($username) && strlen($username) == 5) {
                $digits = str_split($username);
                $predictionSum = array_sum($digits);
            }
            
            // Deactivate old predictions for this game type
            mysqli_query($conn, "UPDATE $predictionTable SET is_active = FALSE WHERE game_type = '$type'");
            
            // Insert new prediction
            $insertPrediction = "INSERT INTO $predictionTable (game_type, prediction_number, prediction_sum) VALUES ('$type', '$username', '$predictionSum')";
            mysqli_query($conn, $insertPrediction);
            
            // Update main table for 5D1 games
            $sqla = mysqli_query($conn,"UPDATE $table SET sthiti='0'");
            $sql = "UPDATE $table SET sthiti='1', sankhye='$username' WHERE shonu='1'";
        }
        
        // For 5D3 games, also store the prediction number for force override
        if($type=="5d3"){
            // Store the prediction number in a separate table for force override
            $predictionTable = "admin_predictions";
            
            // Check if table exists, if not create it
            $checkTable = mysqli_query($conn, "SHOW TABLES LIKE '$predictionTable'");
            if(mysqli_num_rows($checkTable) == 0) {
                $createTable = "CREATE TABLE $predictionTable (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    game_type VARCHAR(10) NOT NULL,
                    prediction_number VARCHAR(10) NOT NULL,
                    prediction_sum INT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    is_active BOOLEAN DEFAULT TRUE
                )";
                mysqli_query($conn, $createTable);
            }
            
            // Calculate sum for 5D3 games (5-digit number)
            $predictionSum = 0;
            if(is_numeric($username) && strlen($username) == 5) {
                $digits = str_split($username);
                $predictionSum = array_sum($digits);
            }
            
            // Deactivate old predictions for this game type
            mysqli_query($conn, "UPDATE $predictionTable SET is_active = FALSE WHERE game_type = '$type'");
            
            // Insert new prediction
            $insertPrediction = "INSERT INTO $predictionTable (game_type, prediction_number, prediction_sum) VALUES ('$type', '$username', '$predictionSum')";
            mysqli_query($conn, $insertPrediction);
            
            // Update main table for 5D3 games
            $sqla = mysqli_query($conn,"UPDATE $table SET sthiti='0'");
            $sql = "UPDATE $table SET sthiti='1', sankhye='$username' WHERE shonu='1'";
        }
        
        // For 5D5 games, also store the prediction number for force override
        if($type=="5d5"){
            // Store the prediction number in a separate table for force override
            $predictionTable = "admin_predictions";
            
            // Check if table exists, if not create it
            $checkTable = mysqli_query($conn, "SHOW TABLES LIKE '$predictionTable'");
            if(mysqli_num_rows($checkTable) == 0) {
                $createTable = "CREATE TABLE $predictionTable (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    game_type VARCHAR(10) NOT NULL,
                    prediction_number VARCHAR(10) NOT NULL,
                    prediction_sum INT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    is_active BOOLEAN DEFAULT TRUE
                )";
                mysqli_query($conn, $createTable);
            }
            
            // Calculate sum for 5D5 games (5-digit number)
            $predictionSum = 0;
            if(is_numeric($username) && strlen($username) == 5) {
                $digits = str_split($username);
                $predictionSum = array_sum($digits);
            }
            
            // Deactivate old predictions for this game type
            mysqli_query($conn, "UPDATE $predictionTable SET is_active = FALSE WHERE game_type = '$type'");
            
            // Insert new prediction
            $insertPrediction = "INSERT INTO $predictionTable (game_type, prediction_number, prediction_sum) VALUES ('$type', '$username', '$predictionSum')";
            mysqli_query($conn, $insertPrediction);
            
            // Update main table for 5D5 games
            $sqla = mysqli_query($conn,"UPDATE $table SET sthiti='0'");
            $sql = "UPDATE $table SET sthiti='1', sankhye='$username' WHERE shonu='1'";
        }
        
        // For 5D10 games, also store the prediction number for force override
        if($type=="5d10"){
            // Store the prediction number in a separate table for force override
            $predictionTable = "admin_predictions";
            
            // Check if table exists, if not create it
            $checkTable = mysqli_query($conn, "SHOW TABLES LIKE '$predictionTable'");
            if(mysqli_num_rows($checkTable) == 0) {
                $createTable = "CREATE TABLE $predictionTable (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    game_type VARCHAR(10) NOT NULL,
                    prediction_number VARCHAR(10) NOT NULL,
                    prediction_sum INT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    is_active BOOLEAN DEFAULT TRUE
                )";
                mysqli_query($conn, $createTable);
            }
            
            // Calculate sum for 5D10 games (5-digit number)
            $predictionSum = 0;
            if(is_numeric($username) && strlen($username) == 5) {
                $digits = str_split($username);
                $predictionSum = array_sum($digits);
            }
            
            // Deactivate old predictions for this game type
            mysqli_query($conn, "UPDATE $predictionTable SET is_active = FALSE WHERE game_type = '$type'");
            
            // Insert new prediction
            $insertPrediction = "INSERT INTO $predictionTable (game_type, prediction_number, prediction_sum) VALUES ('$type', '$username', '$predictionSum')";
            mysqli_query($conn, $insertPrediction);
            
            // Update main table for 5D10 games
            $sqla = mysqli_query($conn,"UPDATE $table SET sthiti='0'");
            $sql = "UPDATE $table SET sthiti='1', sankhye='$username' WHERE shonu='1'";
        }
        
        // For TRX games, also store the prediction number for force override
        if($type=="ktrx"){
            // Store the prediction number in a separate table for force override
            $predictionTable = "admin_predictions";
            
            // Check if table exists, if not create it
            $checkTable = mysqli_query($conn, "SHOW TABLES LIKE '$predictionTable'");
            if(mysqli_num_rows($checkTable) == 0) {
                $createTable = "CREATE TABLE $predictionTable (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    game_type VARCHAR(10) NOT NULL,
                    prediction_number VARCHAR(10) NOT NULL,
                    prediction_sum INT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    is_active BOOLEAN DEFAULT TRUE
                )";
                mysqli_query($conn, $createTable);
            }
            
            // For TRX games, the prediction number IS the sum (single digit 0-9)
            $predictionSum = intval($username);
            
            // Deactivate old predictions for this game type
            mysqli_query($conn, "UPDATE $predictionTable SET is_active = FALSE WHERE game_type = '$type'");
            
            // Insert new prediction
            $insertPrediction = "INSERT INTO $predictionTable (game_type, prediction_number, prediction_sum) VALUES ('$type', '$username', '$predictionSum')";
            mysqli_query($conn, $insertPrediction);
        }
        
        // For TRX3 games, also store the prediction number for force override
        if($type=="ktrx3"){
            // Store the prediction number in a separate table for force override
            $predictionTable = "admin_predictions";
            
            // Check if table exists, if not create it
            $checkTable = mysqli_query($conn, "SHOW TABLES LIKE '$predictionTable'");
            if(mysqli_num_rows($checkTable) == 0) {
                $createTable = "CREATE TABLE $predictionTable (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    game_type VARCHAR(10) NOT NULL,
                    prediction_number VARCHAR(10) NOT NULL,
                    prediction_sum INT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    is_active BOOLEAN DEFAULT TRUE
                )";
                mysqli_query($conn, $createTable);
            }
            
            // For TRX3 games, the prediction number IS the sum (single digit 0-9)
            $predictionSum = intval($username);
            
            // Deactivate old predictions for this game type
            mysqli_query($conn, "UPDATE $predictionTable SET is_active = FALSE WHERE game_type = '$type'");
            
            // Insert new prediction
            $insertPrediction = "INSERT INTO $predictionTable (game_type, prediction_number, prediction_sum) VALUES ('$type', '$username', '$predictionSum')";
            mysqli_query($conn, $insertPrediction);
        }
        
        // For TRX5 games, also store the prediction number for force override
        if($type=="ktrx5"){
            // Store the prediction number in a separate table for force override
            $predictionTable = "admin_predictions";
            
            // Check if table exists, if not create it
            $checkTable = mysqli_query($conn, "SHOW TABLES LIKE '$predictionTable'");
            if(mysqli_num_rows($checkTable) == 0) {
                $createTable = "CREATE TABLE $predictionTable (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    game_type VARCHAR(10) NOT NULL,
                    prediction_number VARCHAR(10) NOT NULL,
                    prediction_sum INT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    is_active BOOLEAN DEFAULT TRUE
                )";
                mysqli_query($conn, $createTable);
            }
            
            // For TRX5 games, the prediction number IS the sum (single digit 0-9)
            $predictionSum = intval($username);
            
            // Deactivate old predictions for this game type
            mysqli_query($conn, "UPDATE $predictionTable SET is_active = FALSE WHERE game_type = '$type'");
            
            // Insert new prediction
            $insertPrediction = "INSERT INTO $predictionTable (game_type, prediction_number, prediction_sum) VALUES ('$type', '$username', '$predictionSum')";
            mysqli_query($conn, $insertPrediction);
        }
        
        // For TRX10 games, also store the prediction number for force override
        if($type=="ktrx10"){
            // Store the prediction number in a separate table for force override
            $predictionTable = "admin_predictions";
            
            // Check if table exists, if not create it
            $checkTable = mysqli_query($conn, "SHOW TABLES LIKE '$predictionTable'");
            if(mysqli_num_rows($checkTable) == 0) {
                $createTable = "CREATE TABLE $predictionTable (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    game_type VARCHAR(10) NOT NULL,
                    prediction_number VARCHAR(10) NOT NULL,
                    prediction_sum INT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    is_active BOOLEAN DEFAULT TRUE
                )";
                mysqli_query($conn, $createTable);
            }
            
            // For TRX10 games, the prediction number IS the sum (single digit 0-9)
            $predictionSum = intval($username);
            
            // Deactivate old predictions for this game type
            mysqli_query($conn, "UPDATE $predictionTable SET is_active = FALSE WHERE game_type = '$type'");
            
            // Insert new prediction
            $insertPrediction = "INSERT INTO $predictionTable (game_type, prediction_number, prediction_sum) VALUES ('$type', '$username', '$predictionSum')";
            mysqli_query($conn, $insertPrediction);
        }
		    }

			if ($conn->query($sql) === TRUE) {
    // 			echo "Successful";
				header("Location: ../$type.php");
			} else {
				echo '<h1  style="text-align: center;" > Does not Exists</h1>';
			}
		
		}
	}
?>
