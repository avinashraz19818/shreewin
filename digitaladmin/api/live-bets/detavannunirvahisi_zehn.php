<?php


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Database connection info 
$dbDetails = array(
    'host' => 'localhost',
    'user' => 'club532583_veergame',
    'pass' => 'club532583_veergame',
    'db'   => 'club532583_veergame'
);

// Get period ID
$periodid = isset($_GET['periodid']) ? $_GET['periodid'] : '(SELECT atadaaidi FROM gelluonduhogu_zehn ORDER BY gelluonduhogu_zehn.kramasankhye DESC LIMIT 1)';

// Modified table query to exclude demo users
$table = <<<EOT
     (
        SELECT
          bajikattuttate_zehn.byabaharkarta,
          bajikattuttate_zehn.ojana,
          bajikattuttate_zehn.ketebida,
          (SELECT mobile FROM shonu_subjects WHERE id = bajikattuttate_zehn.byabaharkarta) AS mobile,
          (SELECT motta FROM shonu_kaichila WHERE balakedara = bajikattuttate_zehn.byabaharkarta) AS balance
        FROM bajikattuttate_zehn
        LEFT JOIN demo ON bajikattuttate_zehn.byabaharkarta = demo.balakedara
        WHERE bajikattuttate_zehn.kalaparichaya = '{$periodid}'
        AND demo.balakedara IS NULL
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
            return ($d == 10) ? 'Red' : (($d == 11) ? 'Green' : (($d == 12) ? 'Violet' : (($d == 13) ? 'Big' : (($d == 14) ? 'Small' : $d))));
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
