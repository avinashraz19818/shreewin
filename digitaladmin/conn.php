<?php
date_default_timezone_set('Asia/Kolkata');

define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'club532583_shreewin');
define('DB_PASSWORD', 'club532583_shreewin');
define('DB_NAME', 'club532583_shreewin');

// Improved connection with error handling
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

if (!$conn) {
	header('Content-Type: application/json');
	die(json_encode([
		'status' => 'error',
		'message' => 'Database connection failed: ' . mysqli_connect_error()
	]));
}

require_once dirname(__DIR__) . '/database_auto.php';

// Simple DB helper functions
function DB()
{
	global $conn;
	return $conn;
}

function DB_select($query, $params = [])
{
	$stmt = mysqli_prepare(DB(), $query);
	if (!empty($params)) {
		mysqli_stmt_bind_param($stmt, str_repeat('s', count($params)), ...$params);
	}
	mysqli_stmt_execute($stmt);
	$result = mysqli_stmt_get_result($stmt);
	return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

function DB_selectOne($query, $params = [])
{
	$result = DB_select($query, $params);
	return $result[0] ?? null;
}

function DB_insert($query, $params = [])
{
	$stmt = mysqli_prepare(DB(), $query);
	if (!empty($params)) {
		mysqli_stmt_bind_param($stmt, str_repeat('s', count($params)), ...$params);
	}
	mysqli_stmt_execute($stmt);
	return mysqli_insert_id(DB());
}

function DB_update($query, $params = [])
{
	$stmt = mysqli_prepare(DB(), $query);
	if (!empty($params)) {
		mysqli_stmt_bind_param($stmt, str_repeat('s', count($params)), ...$params);
	}
	mysqli_stmt_execute($stmt);
	return mysqli_affected_rows(DB());
}

function DB_delete($query, $params = [])
{
	return DB_update($query, $params);
}

$numbermappings = array("zero", "one", "two", "three", "four", "five", "six", "seven", "eight", "nine");

function getusercount($a, $periodid, $value)
{
	$selectquery = mysqli_query($a, "select * from `bajikattuttate` where `kalaparichaya`='$periodid' and `ojana`in($value) group by `byabaharkarta`");
	$row = mysqli_num_rows($selectquery);
	return $row;
}

function winner($conn, $periodid, $column)
{
	$query = mysqli_query($conn, "SELECT 
		SUM(CASE
			WHEN prakar = '0' THEN ketebida
		END) button,
		
		SUM(CASE
			WHEN ojana = '11' THEN ketebida
		END) as green,
		
		(SUM(CASE
			WHEN ojana = '11' THEN ketebida
		END)-(SUM(CASE
			WHEN ojana = '11' THEN ketebida
		END)/100*2))*2 as greenwinamount,
		
		(SUM(CASE
			WHEN ojana = '11' THEN ketebida
		END)-(SUM(CASE
			WHEN ojana = '11' THEN ketebida
		END)/100*2))*1.5 as greenwinamountwithviolet,
		
		SUM(CASE
			WHEN ojana = '12' THEN ketebida
		END) violet,
		
		(SUM(CASE
			WHEN ojana = '12' THEN ketebida
		END)-(SUM(CASE
			WHEN ojana = '12' THEN ketebida
		END)/100*2))*4.5 as violetwinamount,
		
		SUM(CASE
			WHEN ojana = '10' THEN ketebida
		END) red,
		
		(SUM(CASE
			WHEN ojana = '10' THEN ketebida
		END)-(SUM(CASE
			WHEN ojana = '10' THEN ketebida
		END)/100*2))*2 as redwinamount,
		(SUM(CASE
			WHEN ojana = '10' THEN ketebida
		END)-(SUM(CASE
			WHEN ojana = '10' THEN ketebida
		END)/100*2))*1.5 as redwinamountwithviolet,
		
		SUM(CASE
			WHEN prakar = '1' THEN ketebida
		END) number,
		SUM(CASE
			WHEN ojana = '0' THEN ketebida
		END) `zero`,
		(SUM(CASE
			WHEN ojana = '0' THEN ketebida
		END)-(SUM(CASE
			WHEN ojana = '0' THEN ketebida
		END)/100*2))*9 as zerowinamount,
		
		SUM(CASE
			WHEN ojana = '1' THEN ketebida
		END) `one`,
		(SUM(CASE
			WHEN ojana = '1' THEN ketebida
		END)-(SUM(CASE
			WHEN ojana = '1' THEN ketebida
		END)/100*2))*9 as onewinamount,
		
		SUM(CASE
			WHEN ojana = '2' THEN ketebida
		END) `two`,
		(SUM(CASE
			WHEN ojana = '2' THEN ketebida
		END)-(SUM(CASE
			WHEN ojana = '2' THEN ketebida
		END)/100*2))*9 as twowinamount,
		
		SUM(CASE
			WHEN ojana = '3' THEN ketebida
		END) `three`,
		(SUM(CASE
			WHEN ojana = '3' THEN ketebida
		END)-(SUM(CASE
			WHEN ojana = '3' THEN ketebida
		END)/100*2))*9 as threewinamount,
		
		SUM(CASE
			WHEN ojana = '4' THEN ketebida
		END) `four`,
		(SUM(CASE
			WHEN ojana = '4' THEN ketebida
		END)-(SUM(CASE
			WHEN ojana = '4' THEN ketebida
		END)/100*2))*9 as fourwinamount,
		
		SUM(CASE
			WHEN ojana = '5' THEN ketebida
		END) `five`,
		(SUM(CASE
			WHEN ojana = '5' THEN ketebida
		END)-(SUM(CASE
			WHEN ojana = '5' THEN ketebida
		END)/100*2))*9 as fivewinamount,
		
		SUM(CASE
			WHEN ojana = '6' THEN ketebida
		END) `six`,
		(SUM(CASE
			WHEN ojana = '6' THEN ketebida
		END)-(SUM(CASE
			WHEN ojana = '6' THEN ketebida
		END)/100*2))*9 as sixwinamount,
		
		SUM(CASE
			WHEN ojana = '7' THEN ketebida
		END) `seven`,
		(SUM(CASE
			WHEN ojana = '7' THEN ketebida
		END)-(SUM(CASE
			WHEN ojana = '7' THEN ketebida
		END)/100*2))*9 as sevenwinamount,
		
		SUM(CASE
			WHEN ojana = '8' THEN ketebida
		END) `eight`,
		(SUM(CASE
			WHEN ojana = '8' THEN ketebida
		END)-(SUM(CASE
			WHEN ojana = '8' THEN ketebida
		END)/100*2))*9 as eightwinamount,
		
		SUM(CASE
			WHEN ojana = '9' THEN ketebida
		END) `nine`,
		(SUM(CASE
			WHEN ojana = '9' THEN ketebida
		END)-(SUM(CASE
			WHEN ojana = '9' THEN ketebida
		END)/100*2))*9 as ninewinamount,
		
		(SUM(CASE
			WHEN ojana = '13' THEN ketebida
		END)-(SUM(CASE
			WHEN ojana = '13' THEN ketebida
		END)/100*2))*2 as bigwinamount,
		
		(SUM(CASE
			WHEN ojana = '14' THEN ketebida
		END)-(SUM(CASE
			WHEN ojana = '14' THEN ketebida
		END)/100*2))*2 as smallwinamount
			
		FROM
		`bajikattuttate` where `kalaparichaya`='$periodid'");
	$result = mysqli_fetch_array($query);
	return $result["$column"];
}

function rlamt($conn, $periodid, $column)
{
	$query = mysqli_query($conn, "SELECT 
		SUM(CASE
			WHEN prakar = '0' THEN ketebida
		END) button,
		
		SUM(CASE
			WHEN ojana = '11' THEN ketebida
		END) as green,
		
		(SUM(CASE
			WHEN ojana = '11' THEN ketebida
		END)-(SUM(CASE
			WHEN ojana = '11' THEN ketebida
		END)/100*2)) as greenwinamount,
		
		(SUM(CASE
			WHEN ojana = '11' THEN ketebida
		END)-(SUM(CASE
			WHEN ojana = '11' THEN ketebida
		END)/100*2)) as greenwinamountwithviolet,
		
		SUM(CASE
			WHEN ojana = '12' THEN ketebida
		END) violet,
		
		(SUM(CASE
			WHEN ojana = '12' THEN ketebida
		END)-(SUM(CASE
			WHEN ojana = '12' THEN ketebida
		END)/100*2)) as violetwinamount,
		
		SUM(CASE
			WHEN ojana = '10' THEN ketebida
		END) red,
		
		(SUM(CASE
			WHEN ojana = '10' THEN ketebida
		END)-(SUM(CASE
			WHEN ojana = '10' THEN ketebida
		END)/100*2)) as redwinamount,
		(SUM(CASE
			WHEN ojana = '10' THEN ketebida
		END)-(SUM(CASE
			WHEN ojana = '10' THEN ketebida
		END)/100*2)) as redwinamountwithviolet,
		
		SUM(CASE
			WHEN prakar = '1' THEN ketebida
		END) number,
		SUM(CASE
			WHEN ojana = '0' THEN ketebida
		END) `zero`,
		(SUM(CASE
			WHEN ojana = '0' THEN ketebida
		END)-(SUM(CASE
			WHEN ojana = '0' THEN ketebida
		END)/100*2)) as zerowinamount,
		
		SUM(CASE
			WHEN ojana = '1' THEN ketebida
		END) `one`,
		(SUM(CASE
			WHEN ojana = '1' THEN ketebida
		END)-(SUM(CASE
			WHEN ojana = '1' THEN ketebida
		END)/100*2)) as onewinamount,
		
		SUM(CASE
			WHEN ojana = '2' THEN ketebida
		END) `two`,
		(SUM(CASE
			WHEN ojana = '2' THEN ketebida
		END)-(SUM(CASE
			WHEN ojana = '2' THEN ketebida
		END)/100*2)) as twowinamount,
		
		SUM(CASE
			WHEN ojana = '3' THEN ketebida
		END) `three`,
		(SUM(CASE
			WHEN ojana = '3' THEN ketebida
		END)-(SUM(CASE
			WHEN ojana = '3' THEN ketebida
		END)/100*2)) as threewinamount,
		
		SUM(CASE
			WHEN ojana = '4' THEN ketebida
		END) `four`,
		(SUM(CASE
			WHEN ojana = '4' THEN ketebida
		END)-(SUM(CASE
			WHEN ojana = '4' THEN ketebida
		END)/100*2)) as fourwinamount,
		
		SUM(CASE
			WHEN ojana = '5' THEN ketebida
		END) `five`,
		(SUM(CASE
			WHEN ojana = '5' THEN ketebida
		END)-(SUM(CASE
			WHEN ojana = '5' THEN ketebida
		END)/100*2)) as fivewinamount,
		
		SUM(CASE
			WHEN ojana = '6' THEN ketebida
		END) `six`,
		(SUM(CASE
			WHEN ojana = '6' THEN ketebida
		END)-(SUM(CASE
			WHEN ojana = '6' THEN ketebida
		END)/100*2)) as sixwinamount,
		
		SUM(CASE
			WHEN ojana = '7' THEN ketebida
		END) `seven`,
		(SUM(CASE
			WHEN ojana = '7' THEN ketebida
		END)-(SUM(CASE
			WHEN ojana = '7' THEN ketebida
		END)/100*2)) as sevenwinamount,
		
		SUM(CASE
			WHEN ojana = '8' THEN ketebida
		END) `eight`,
		(SUM(CASE
			WHEN ojana = '8' THEN ketebida
		END)-(SUM(CASE
			WHEN ojana = '8' THEN ketebida
		END)/100*2)) as eightwinamount,
		
		SUM(CASE
			WHEN ojana = '9' THEN ketebida
		END) `nine`,
		(SUM(CASE
			WHEN ojana = '9' THEN ketebida
		END)-(SUM(CASE
			WHEN ojana = '9' THEN ketebida
		END)/100*2)) as ninewinamount,
		
		(SUM(CASE
			WHEN ojana = '13' THEN ketebida
		END)-(SUM(CASE
			WHEN ojana = '13' THEN ketebida
		END)/100*2)) as bigwinamount,
		
		(SUM(CASE
			WHEN ojana = '14' THEN ketebida
		END)-(SUM(CASE
			WHEN ojana = '14' THEN ketebida
		END)/100*2)) as smallwinamount
			
		FROM
		`bajikattuttate` where `kalaparichaya`='$periodid'");
	$result = mysqli_fetch_array($query);
	return $result["$column"];
}

function encryptor($action, $string)
{
	$output = false;

	$encrypt_method = "AES-256-CBC";
	$secret_key = 'shonu';
	$secret_iv = 'kani123';

	$key = hash('sha256', $secret_key);

	$iv = substr(hash('sha256', $secret_iv), 0, 16);

	if ($action == 'encrypt') {
		$output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
		$output = base64_encode($output);
	} else if ($action == 'decrypt') {
		$output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
	}

	return $output;
}
