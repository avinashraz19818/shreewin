<?php
	require_once dirname(__DIR__) . '/developer-maruf/error_logger.php';

	$conn = mysqli_connect('localhost', 'club532583_shreewin', 'club532583_shreewin', 'club532583_shreewin');
	
	if (!$conn) {
		app_log_event('critical', 'Payment database connection failed', [
			'database_error' => mysqli_connect_error(),
			'database_error_number' => mysqli_connect_errno(),
		]);
		http_response_code(500);
		exit('Database connection unavailable');
	}
	
	date_default_timezone_set("Asia/Kolkata"); 
?>
