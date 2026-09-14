<?php
// Database connection info 
$dbDetails = array(
	'host' => 'localhost',
'user' => 'club532583_veergame',
'pass' => 'club532583_veergame',
'db' => 'club532583_veergame'
);

// DB table to use 
//$table = 'shonu_subjects'; 
$table = <<<EOT
	 (
		SELECT
		  shonu_subjects.mobile,
		  shonu_subjects.owncode,
		  shonu_subjects.code,
		  shonu_subjects.id,
		  shonu_subjects.createdate,
		  shonu_subjects.status,
		  shonu_subjects.pwd,
		  shonu_kaichila.motta,
		  (SELECT SUM(motta) FROM thevani WHERE balakedara = shonu_subjects.id and `status`='1') AS total_deposit,
		  (SELECT motta FROM thevani WHERE balakedara = shonu_subjects.id and `status`='1' ORDER BY motta ASC LIMIT 1) AS deposit_motta,
		  shonu_kaichila.turnover,
		  (SELECT kod FROM khate WHERE byabaharkarta = shonu_subjects.id and `sthiti`='1' ORDER BY shonu ASC LIMIT 1) AS ifsc,
		  (SELECT khatesankhye FROM khate WHERE byabaharkarta = shonu_subjects.id and `sthiti`='1' and kodprakara=6 ORDER BY shonu ASC LIMIT 1) AS account,
		  (SELECT khatesankhye FROM khate WHERE byabaharkarta = shonu_subjects.id and kodprakara=7 ORDER BY shonu ASC LIMIT 1) AS usdt,
		  (SELECT sthiti FROM khate WHERE byabaharkarta = shonu_subjects.id and kodprakara=7 ORDER BY shonu ASC LIMIT 1) AS usdt_status
		FROM shonu_subjects
		JOIN shonu_kaichila ON shonu_subjects.id = shonu_kaichila.balakedara
	 ) temp
	EOT;

// Table's primary key 
$primaryKey = 'id';

// Array of database columns which should be read and sent back to DataTables. 
// The `db` parameter represents the column name in the database.  
// The `dt` parameter represents the DataTables column identifier. 
$columns = array(
	array('db' => 'id', 'dt' => 0)
);

// Include SQL query processing class 
require 'ssp_without_quote_table.php';

// Output data as json format 
echo json_encode(
	SSP::simple($_GET, $dbDetails, $table, $primaryKey, $columns)
);
