<?php
// Database connection info 
$dbDetails = array(
	'host' => 'localhost',
'user' => 'club532583_veergame',
'pass' => 'club532583_veergame',
'db'   => 'club532583_veergame'
);
$periodid = isset($_GET['periodid']) ? $_GET['periodid'] : '(SELECT atadaaidi FROM gelluonduhogu_trx10 ORDER BY gelluonduhogu_trx10.atadaaidi DESC LIMIT 1)';

// DB table to use 
//$table = 'tbl_user'; 
$table = <<<EOT
	 (
		SELECT
		  bajikattuttate_trx10.byabaharkarta,
		  bajikattuttate_trx10.ojana,
		  bajikattuttate_trx10.ketebida,
		  (SELECT mobile FROM  shonu_subjects WHERE id = bajikattuttate_trx10.byabaharkarta) AS mobile,
		  (SELECT motta FROM  shonu_kaichila WHERE balakedara = bajikattuttate_trx10.byabaharkarta) AS balance
		FROM bajikattuttate_trx10
		WHERE bajikattuttate_trx10.kalaparichaya = '{$periodid}'
	 ) temp
	EOT;

// Table's primary key 
$primaryKey = 'byabaharkarta';

// Array of database columns which should be read and sent back to DataTables. 
// The `db` parameter represents the column name in the database.  
// The `dt` parameter represents the DataTables column identifier. 
$columns = array(
	array('db' => 'byabaharkarta', 'dt' => 0),
	array(
		'db' => 'ojana',
		'dt' => 1,
		'formatter' => function ($d, $row) {
			return ($d == 10) ? 'Even' : (($d == 11) ? 'Odd' : (($d == 12) ? 'Big' : (($d == 13) ? 'Small' : (($d == 14) ? 'All' : $d))));
		}
	),
	array('db' => 'ketebida', 'dt' => 2),
	array('db' => 'mobile', 'dt' => 3),
	array('db' => 'balance', 'dt' => 4)
);

// Include SQL query processing class 
require '../ssp_without_quote_table.php';

// Output data as json format 
echo json_encode(
	SSP::simple($_GET, $dbDetails, $table, $primaryKey, $columns)
);
