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
		  COALESCE(shonu_kaichila.motta,0) AS motta,
		  (SELECT SUM(motta) FROM thevani WHERE balakedara = shonu_subjects.id and `sthiti`='1') AS total_deposit,
		  (SELECT motta FROM thevani WHERE balakedara = shonu_subjects.id and `sthiti`='1' ORDER BY shonu ASC LIMIT 1) AS deposit_motta,
		  COALESCE(shonu_kaichila.mottta,0) AS mottta,
		  (SELECT kod FROM khate WHERE byabaharkarta = shonu_subjects.id and `sthiti`='1' ORDER BY shonu ASC LIMIT 1) AS ifsc,
		  (SELECT khatesankhye FROM khate WHERE byabaharkarta = shonu_subjects.id and `sthiti`='1' and kodprakara=6 ORDER BY shonu ASC LIMIT 1) AS account,
		  (SELECT khatesankhye FROM khate WHERE byabaharkarta = shonu_subjects.id and kodprakara=7 ORDER BY shonu ASC LIMIT 1) AS usdt,
		  (SELECT sthiti FROM khate WHERE byabaharkarta = shonu_subjects.id and kodprakara=7 ORDER BY shonu ASC LIMIT 1) AS usdt_status
		FROM shonu_subjects
		LEFT JOIN shonu_kaichila ON shonu_subjects.id = shonu_kaichila.balakedara
	 ) temp
	EOT;

// Table's primary key 
$primaryKey = 'id';

// Array of database columns which should be read and sent back to DataTables. 
// The `db` parameter represents the column name in the database.  
// The `dt` parameter represents the DataTables column identifier. 
$columns = array(
	array('db' => 'mobile', 'dt' => 0),
	array('db' => 'owncode', 'dt' => 1),
	array('db' => 'code', 'dt' => 2),
	array('db' => 'id', 'dt' => 3),
	array(
		'db' => 'motta',
		'dt' => 4,
		'formatter' => function ($d, $row) {
			$aid = $row['id'];
			$mobile = $row['mobile'];
			$motta = $row['motta'];
			return
				number_format($d, 2) . '&nbsp;<a href="javascript:void(0);" onClick="edit(' . $aid . ',' . $mobile . ',' . $motta . ')" class="text-aqua" title="Delete"><i class="ri-pencil-fill ri-22px text-primary"></i></a>';
		}
	),
	array(
		'db' => 'total_deposit',
		'dt' => 5,
		'formatter' => function ($d, $row) {
			return ($d == null) ? '0.00' : number_format($d, 2);
		}
	),
	array(
		'db' => 'deposit_motta',
		'dt' => 6,
		'formatter' => function ($d, $row) {
			return ($d == null) ? '0.00' : number_format($d, 2);
		}
	),
	array(
		'db' => 'mottta',
		'dt' => 7,
		'formatter' => function ($d, $row) {
			$aid = $row['id'];
			$mobile = $row['mobile'];
			$turnover = $row['mottta'];
			return
				number_format($d, 2) . '&nbsp;<a href="javascript:void(0);" onClick="editturnover(' . $aid . ',' . $mobile . ',' . $turnover . ')" class="text-aqua" title="Edit Turnover"><i class="ri-pencil-fill ri-22px text-primary"></i></a>';
		}
	),
	array(
		'db' => 'createdate',
		'dt' => 8,
		'formatter' => function ($d, $row) {
			return date('jS M Y', strtotime($d));
		}
	),
	array(
		'db' => 'status',
		'dt' => 9,
		'formatter' => function ($d, $row) {
			$id = $row['id'];
			return ($d == 1) ?
				'<a href="javascript:void(0);" onClick="delete_row(' . $id . ')" class="update-person" style="color:#f56954; font-size:16px;" title="Delete"><i class="ri-delete-bin-fill ri-22px text-danger mr--9px"></i></a>
				&nbsp;
				<a href="javascript:void(0);" onClick="Respond(' . $id . ')" class="update-person" style="color:#090; font-size:16px;" data-toggle="tooltip" title="Publish"><i class="ri-checkbox-circle-line ri-22px text-success"></i></a>
				<a href="user-details.php?user=' . $id . '"  class="update-person" style="color:#0E0E44; font-size:16px;" data-toggle="tooltip" title="User Deatil"><i class="ri-information-2-line ri-22px text-primary"></i></a>'
				:
				'<a href="javascript:void(0);" onClick="delete_row(' . $id . ')" class="update-person" style="color:#f56954; font-size:16px;" title="Delete"><i class="ri-delete-bin-fill ri-22px text-danger mr--9px"></i></a>
				&nbsp;
				<a href="javascript:void(0);" onClick="UnRespond(' . $id . ')" class="update-person" style="color:#f00; font-size:16px;" data-toggle="tooltip" title="Unpublish"><i class="ri-checkbox-blank-circle-line ri-22px text-danger"></i></a>
				<a href="user-details.php?user=' . $id . '"  class="update-person" style="color:#0E0E44; font-size:16px;" data-toggle="tooltip" title="User Deatil"><i class="ri-information-2-line ri-22px text-primary"></i></a>';
		}
	),
	array('db' => 'pwd', 'dt' => 10),
	array(
		'db' => 'ifsc',
		'dt' => 11,
		'formatter' => function ($d, $row) {
			$aid = $row['id'];
			$mobile = $row['mobile'];
			$ifsc = $row['ifsc'];
			return
				strlen($d) > 2 ? ($d . '&nbsp;<a href="javascript:void(0);" onClick="editifsc(' . $aid . ',' . $mobile . ',\'' . $ifsc . '\')" class="text-aqua" title="Edit Ifsc"><i class="ri-pencil-fill ri-22px text-primary"></i></a>') : '';
		}
	),
	array(
		'db' => 'account',
		'dt' => 12,
		'formatter' => function ($d, $row) {
			$aid = $row['id'];
			$mobile = $row['mobile'];
			$account = $row['account'];
			return
				strlen($d) > 2 ? $d . '&nbsp;<a href="javascript:void(0);" onClick="editaccount(' . $aid . ',' . $mobile . ',\'' . $account . '\')" class="text-aqua" title="Edit Account No."><i class="ri-pencil-fill ri-22px text-primary"></i></a>' : '';
		}
	),
	array(
		'db' => 'usdt',
		'dt' => 13,
		'formatter' => function ($d, $row) {
			$aid = $row['id'];
			$mobile = $row['mobile'];
			$usdt = $row['usdt'];
			$usdt_status = $row['usdt_status'];
			if (strlen($d) > 2 && $usdt_status == 1) {
				return $d . '&nbsp;<a href="javascript:void(0);" onClick="editaccount(' . $aid . ',' . $mobile . ',\'' . $usdt . '\')" class="text-aqua" title="Edit USDT"><i class="ri-pencil-fill ri-22px text-primary"></i></a>&nbsp;
				<a href="javascript:void(0);" onClick="MarkUnverified(' . $aid . ')" class="update-person" style="color:#090; font-size:16px;" data-toggle="tooltip" title="Publish"><i class="ri-checkbox-circle-line ri-22px text-success"></i></a>';
			} else if (strlen($d) > 2 && $usdt_status == 0) {
				return $d . '&nbsp;<a href="javascript:void(0);" onClick="editaccount(' . $aid . ',' . $mobile . ',\'' . $usdt . '\')" class="text-aqua" title="Edit USDT"><i class="ri-pencil-fill ri-22px text-primary"></i></a>&nbsp;
				<a href="javascript:void(0);" onClick="MarkVerified(' . $aid . ')" class="update-person" style="color:#f00; font-size:16px;" data-toggle="tooltip" title="Unpublish"><i class="ri-checkbox-blank-circle-line ri-22px text-danger"></i></a>';
			} else
				return $d;
		}
	),
	array('db' => 'usdt_status', 'dt' => 14)
);

// Include SQL query processing class 
require 'ssp_without_quote_table.php';

// Output data as json format 
echo json_encode(
	SSP::simple($_GET, $dbDetails, $table, $primaryKey, $columns)
);
