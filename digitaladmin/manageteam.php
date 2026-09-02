<?php
session_start();
if (empty($_SESSION['unohs'])) {
    header("location: api/login.php?msg=unauthorized");
}
?>
<?php
include("api/conn.php");

$requestedUser = trim((string)($_GET['user'] ?? ($_SESSION['nirvahaka_hesaru'] ?? '')));
$level = preg_match('/^(?:[0-9]|10)$/', (string)($_GET['level'] ?? '0')) ? (string)($_GET['level'] ?? '0') : '0';
$transaction_type = (string)($_GET['type'] ?? '-1');
$transaction_date = isset($_GET['date'])?htmlspecialchars(mysqli_real_escape_string($conn, $_GET['date'])):'';
$transaction_page = max(1, (int)($_GET['transaction_page'] ?? 1));
$referral_page = max(1, (int)($_GET['referral_page'] ?? 1));

function mt_table_exists($db, $table) {
    $stmt = $db->prepare('SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=? LIMIT 1');
    if (!$stmt) return false;
    $stmt->bind_param('s', $table); $stmt->execute(); $stmt->store_result();
    $exists = $stmt->num_rows > 0; $stmt->close(); return $exists;
}
function mt_sum($db, $sql, $types, ...$params) {
    try {
        $stmt = $db->prepare($sql);
        if (!$stmt) return 0.0;
        if ($types !== '') $stmt->bind_param($types, ...$params);
        $stmt->execute(); $stmt->bind_result($value); $stmt->fetch(); $stmt->close();
        return (float)($value ?? 0);
    } catch (Throwable $e) {
        error_log('[manage-team sum] ' . $e->getMessage()); return 0.0;
    }
}

$snum = null;
if ($requestedUser !== '') {
    $stmt = $conn->prepare('SELECT * FROM shonu_subjects WHERE CAST(id AS CHAR)=? OR mobile=? LIMIT 1');
    if ($stmt) {
        $stmt->bind_param('ss', $requestedUser, $requestedUser); $stmt->execute();
        $result = $stmt->get_result(); $snum = $result ? $result->fetch_assoc() : null; $stmt->close();
    }
}
if (!$snum) {
    // This page is a per-user dashboard. A missing/invalid user must never
    // become a PHP 8 HTTP 500; send the admin to the searchable user list.
    header('Location: users.php?msg=select-user');
    exit;
}
$userid = (int)$snum['id'];

$owncode = $snum['owncode'];

$total_balance = mt_sum($conn, 'SELECT COALESCE(MAX(motta),0) FROM shonu_kaichila WHERE balakedara=?', 'i', $userid);
$total_refer = ['total'=>mt_sum($conn, 'SELECT COUNT(id) FROM shonu_subjects WHERE code=?', 's', $owncode)];

// The real game writes every Wingo/K3/5D/TRX/Moto bet here. Reading this
// unified ledger keeps the admin dashboard synchronized with the game UI.
$total_bet = mt_table_exists($conn, 'saas_lottery_bets')
    ? mt_sum($conn, 'SELECT COALESCE(SUM(stake),0) FROM saas_lottery_bets WHERE user_id=?', 'i', $userid)
    : 0.0;
$total_recharge = ['total'=>mt_sum($conn, "SELECT COALESCE(SUM(motta),0) FROM thevani WHERE sthiti='1' AND balakedara=?", 'i', $userid)];
$total_withdraw = ['total'=>mt_sum($conn, 'SELECT COALESCE(SUM(motta),0) FROM hintegedukolli WHERE sthiti=1 AND balakedara=?', 'i', $userid)];
$total_reward = ['total'=>mt_sum($conn, 'SELECT COALESCE(SUM(price),0) FROM hodike_balakedara WHERE userkani=?', 'i', $userid)];

//$wingo = mysqli_fetch_assoc(mysqli_query($conn,"SELECT *  FROM `bajikattuttate_zehn` where byabaharkarta = '".$userid."'"));
$refferal_offset = ($referral_page - 1) * 10;
$levelColumns = [1=>'code',2=>'code1',3=>'code2',4=>'code3',5=>'code4',6=>'code5',7=>'code6',8=>'code7',9=>'code8',10=>'code9'];
$availableLevelColumns=[];
foreach($levelColumns as $lv=>$column){$chk=$conn->query("SHOW COLUMNS FROM shonu_subjects LIKE '".$conn->real_escape_string($column)."'");if($chk instanceof mysqli_result&&$chk->num_rows>0)$availableLevelColumns[$lv]=$column;}
$refer_record=false;$refferal_total_pages=0;
if($level==='0'){
    $conditions=[];$cases=[];
    foreach($availableLevelColumns as $lv=>$column){$safe=$conn->real_escape_string($owncode);$conditions[]="`{$column}`='{$safe}'";$cases[]="WHEN `{$column}`='{$safe}' THEN '{$lv}'";}
    if($conditions){$where=implode(' OR ',$conditions);$case=implode(' ',$cases);$refer_record=$conn->query("SELECT id,mobile,owncode,ishonup,createdate,CASE {$case} ELSE 'unknown' END AS lvl FROM shonu_subjects WHERE ({$where}) ORDER BY id DESC LIMIT 10 OFFSET {$refferal_offset}");$cnt=$conn->query("SELECT COUNT(*) AS total FROM shonu_subjects WHERE ({$where})");$row=$cnt?$cnt->fetch_assoc():['total'=>0];$refferal_total_pages=(int)ceil(((int)$row['total'])/10);}
}else{
    $lv=(int)$level;
    if(isset($availableLevelColumns[$lv])){$column=$availableLevelColumns[$lv];$safe=$conn->real_escape_string($owncode);$refer_record=$conn->query("SELECT id,mobile,owncode,ishonup,createdate,'{$lv}' AS lvl FROM shonu_subjects WHERE `{$column}`='{$safe}' ORDER BY id DESC LIMIT 10 OFFSET {$refferal_offset}");$cnt=$conn->query("SELECT COUNT(*) AS total FROM shonu_subjects WHERE `{$column}`='{$safe}'");$row=$cnt?$cnt->fetch_assoc():['total'=>0];$refferal_total_pages=(int)ceil(((int)$row['total'])/10);}
}
if(!$refer_record){$refer_record=$conn->query('SELECT id,mobile,owncode,ishonup,createdate,\'unknown\' AS lvl FROM shonu_subjects WHERE 1=0');}

$deposit_record = mysqli_query($conn, "SELECT * FROM `thevani` WHERE `balakedara` = '" . $userid . "' ORDER BY shonu DESC");

$withdraw_record = mysqli_query($conn, "SELECT * FROM `hintegedukolli` where balakedara = '" . $userid . "' ORDER BY shonu DESC");

$rbxquery = "SELECT SUM(ayoga) as sumayoga FROM vyavahara WHERE balakedara = '" . $userid . "' AND prakara LIKE 'LVLCOMM%'";
$rbxresult = $conn->query($rbxquery);
$rbxar = $rbxresult ? mysqli_fetch_array($rbxresult) : array('sumayoga'=>0);
$sumayoga = (float) ($rbxar['sumayoga'] ?? 0);
?>

<!doctype html>

<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr"
    data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template" data-style="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

    <title>User Detail</title>

    <meta name="description" content="" />

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/img/favicon/favicon.ico?v=20260814-admin" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&ampdisplay=swap"
        rel="stylesheet" />

    <!-- Icons -->
    <link rel="stylesheet" href="assets/vendor/fonts/remixicon/remixicon.css" />
    <link rel="stylesheet" href="assets/vendor/fonts/flag-icons.css" />

    <!-- Menu waves for no-customizer fix -->
    <link rel="stylesheet" href="assets/vendor/libs/node-waves/node-waves.css" />

    <!-- Core CSS -->
    <link rel="stylesheet" href="assets/vendor/css/rtl/core.css" class="template-customizer-core-css" />
    <link rel="stylesheet" href="assets/vendor/css/rtl/theme-default.css" class="template-customizer-theme-css" />
    <link rel="stylesheet" href="assets/css/demo.css" />

    <!-- Vendors CSS -->
    <link rel="stylesheet" href="assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
    <link rel="stylesheet" href="assets/vendor/libs/typeahead-js/typeahead.css" />
    <link rel="stylesheet" href="assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css" />
    <link rel="stylesheet" href="assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css" />
    <link rel="stylesheet" href="assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css" />
    <link rel="stylesheet" href="assets/vendor/libs/datatables-checkboxes-jquery/datatables.checkboxes.css" />
    <link rel="stylesheet" href="assets/vendor/libs/select2/select2.css" />
    <link rel="stylesheet" href="assets/vendor/libs/@form-validation/form-validation.css" />
    <style>   
    .overflow-x-auto{
        overflow-x: auto;
    }
    </style>

    <!-- Page CSS -->

    <!-- Helpers -->
    <script src="assets/vendor/js/helpers.js"></script>
    <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->
    <!--? Template customizer: To hide customizer set displayCustomizer value false in config.js.  -->
    <script src="assets/vendor/js/template-customizer.js"></script>
    <!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->
    <script src="assets/js/config.js"></script>
</head>

<body>
    <!-- Layout wrapper -->
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <!-- Menu -->

            <?php require_once("layout-menu.php"); ?>
            <!-- / Menu -->

            <!-- Layout container -->
            <div class="layout-page">
                <!-- Navbar -->

                <?php require_once("nav.php"); ?>

                <!-- / Navbar -->

                <!-- Content wrapper -->
                <div class="content-wrapper">
                    <div class="container-xxl flex-grow-1 container-p-y">
                        <div class="row mb-5">
                            <!-- User Sidebar -->
                            <div class="col-xl-5 col-lg-5 col-md-6 order-1 order-md-0">
                                <!-- User Card -->
                                <div class="card mb-6">
                                    <div class="card-body pt-12">
                                        <!--<div class="user-avatar-section">-->
                                        <!--    <div class="d-flex align-items-center flex-column">-->
                                        <!--        <img class="img-fluid rounded mb-4" src="assets/img/avatars/1.png"-->
                                        <!--            height="120" width="120" alt="User avatar" />-->
                                        <!--        <div class="user-info text-center">-->
                                        <!--            <h5>Violet Mendoza</h5>-->
                                        <!--            <span class="badge bg-label-danger rounded-pill">Subscriber</span>-->
                                        <!--        </div>-->
                                        <!--    </div>-->
                                        <!--</div>-->
                                        <h5 class="pb-4 border-bottom mb-4">Details</h5>
                                        <div class="info-container">
                                            <ul class="list-unstyled mb-6">
                                                <li class="mb-2">
                                                    <span class="fw-medium text-heading me-2">User Id:</span>
                                                    <span
                                                        class="badge bg-label-success rounded-pill"><b><?= $snum['id']; ?></b></span>
                                                </li>
                                                <li class="mb-2">
                                                    <span class="fw-medium text-heading me-2">Referal Id :</span>
                                                    <span><b><?= $snum['owncode']; ?></b></span>
                                                </li>
                                                <li class="mb-2">
                                                    <span class="fw-medium text-heading me-2">IP Address:</span>
                                                    <span><b><?= $snum['ishonup']; ?></b></span>
                                                </li>
                                                <li class="mb-2">
                                                    <span class="fw-medium text-heading me-2">Phone No:</span>
                                                    <span><b><?= $snum['mobile']; ?></b></span>
                                                </li>
                                                <li class="mb-2">
                                                    <span class="fw-medium text-heading me-2">Created At:</span>
                                                    <span><b><?= $snum['createdate']; ?></b></span>
                                                </li>
                                                <li class="mb-2">
                                                    <span class="fw-medium text-heading me-2">Referred By:</span>
                                                    <span><b><?= $snum['code']; ?></b></span>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                              <?php 
                              $useridpost = $_SESSION['nirvahaka_hesaru'];
                             if($userid == $useridpost){
                              
                              
                              ?>
                                <div class="card mb-6">
                                    <h5 class="card-header">Change Password</h5>
                                    <div class="card-body">
                                        <form id="formChangePassword" action="api/update_userpass.php" method="POST">
                                            <div class="row gx-5">
                                                <div class="mb-3 col-12 col-sm-12 form-password-toggle">
                                                    <div class="input-group input-group-merge">
                                                        <div class="form-floating form-floating-outline">
                                                            <input type="hidden" name="user_id"
                                                                value="<?php echo $snum['id']; ?>">
                                                            <input class="form-control" type="password" id="newPassword"
                                                                name="resetpsw"
                                                                placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" />
                                                            <label for="newPassword">New Password</label>
                                                        </div>
                                                        <span class="input-group-text cursor-pointer text-heading"><i
                                                                class="ri-eye-off-line"></i></span>
                                                    </div>
                                                </div>
                                                <div>
                                                    <button type="submit" class="btn btn-primary me-2">Update</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                <div class="card mb-6">
                                    <h5 class="card-header">Change Reffered By</h5>
                                    <div class="card-body">
                                        <form id="formChangePassword" action="api/update_reffcode.php" method="POST">
                                            <div class="row gx-5">
                                                <div class="mb-3 col-12 col-sm-12 form-password-toggle">
                                                    <div class="input-group input-group-merge">
                                                        <div class="form-floating form-floating-outline">
                                                            <input type="hidden" name="user_id"
                                                                value="<?php echo $snum['id']; ?>">
                                                            <input class="form-control" type="text" id="newPassword"
                                                                name="referred_by" placeholder="Enter Reffered By"
                                                                value="<?= $snum['code']; ?>" />
                                                            <label for="newPassword">Reffered By</label>
                                                        </div>
                                                        <span class="input-group-text cursor-pointer text-heading"><i
                                                                class="ri-eye-off-line"></i></span>
                                                    </div>
                                                </div>
                                                <div>
                                                    <button type="submit" class="btn btn-primary me-2">Update</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div><?php }
                          ?>
                            <div class="col-xl-7 col-lg-7 col-md-6 order-0 order-md-1">
                                <div class="row g-6 mb-6">
                                    <div class="col-sm-6 col-xl-6">
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between">
                                                    <div class="me-1">
                                                        <p class="text-heading mb-1">Total Balance</p>
                                                        <div class="d-flex align-items-center">
                                                            <h4 class="mb-1 me-2 text-success">
                                                                ₹<?= round($total_balance, 2); ?>
                                                            </h4>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6 col-xl-6">
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between">
                                                    <div class="me-1">
                                                        <p class="text-heading mb-1">Total Referral</p>
                                                        <div class="d-flex align-items-center">
                                                            <h4 class="mb-1 me-2 text-success">
                                                                <?= (float) $total_refer['total']; ?>
                                                            </h4>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6 col-xl-6">
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between">
                                                    <div class="me-1">
                                                        <p class="text-heading mb-1">Total Withdraw</p>
                                                        <div class="d-flex align-items-center">
                                                            <h4 class="mb-1 me-2 text-success">
                                                                ₹<?= round($total_withdraw['total'], 2); ?>
                                                            </h4>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6 col-xl-6">
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between">
                                                    <div class="me-1">
                                                        <p class="text-heading mb-1">Total Gift</p>
                                                        <div class="d-flex align-items-center">
                                                            <h4 class="mb-1 me-2 text-success">
                                                                ₹<?= round($total_reward['total'], 2); ?>
                                                            </h4>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6 col-xl-6">
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between">
                                                    <div class="me-1">
                                                        <p class="text-heading mb-1">Total Bet</p>
                                                        <div class="d-flex align-items-center">
                                                            <h4 class="mb-1 me-2 text-success">
                                                                ₹<?= round($total_bet, 2); ?>
                                                            </h4>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6 col-xl-6">
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between">
                                                    <div class="me-1">
                                                        <p class="text-heading mb-1">Total Recharge</p>
                                                        <div class="d-flex align-items-center">
                                                            <h4 class="mb-1 me-2 text-success">
                                                                ₹<?= round($total_recharge['total'], 2); ?>
                                                            </h4>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6 col-xl-6">
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between">
                                                    <div class="me-1">
                                                        <p class="text-heading mb-1">Total Commission</p>
                                                        <div class="d-flex align-items-center">
                                                            <h4 class="mb-1 me-2 text-success">
                                                                ₹<?= $sumayoga; ?>
                                                            </h4>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                            <!--/ User Content -->
                        </div>
                        <div class="card mb-5" id="transaction-history">
                            <div class="table-responsive text-nowrap">
                                <div class="d-flex justify-content-between align-items-center px-4">
                                    <h4 class="m-5">Transaction History</h4>
                                    <form action="#transaction-history" method="get" class="d-flex gap-3 align-items-center">
                                        <select class="form-select" id="type" name="type">
                                            <option value="-1" <?= ($transaction_type == "-1") ? "selected" : ""; ?>>All</option>
                                            <option value="2" <?= ($transaction_type == '2') ? "selected" : ""; ?>>Salary</option>
                                            <option value="4" <?= ($transaction_type == '4') ? "selected" : ""; ?>>Deposit</option>
                                            <option value="119" <?= ($transaction_type == '119') ? "selected" : ""; ?>>Spin</option>
                                            <option value="12" <?= ($transaction_type == '12') ? "selected" : ""; ?>>Spin</option>
                                            <option value="5" <?= ($transaction_type == '5') ? "selected" : ""; ?>>Withdraw</option>
                                            <option value="6" <?= ($transaction_type == '6') ? "selected" : ""; ?>>Cancel Withdraw</option>
                                            <option value="2" <?= ($transaction_type == '2') ? "selected" : ""; ?>>Jackpot increase</option>
                                            <option value="14" <?= ($transaction_type == '14') ? "selected" : ""; ?>>First deposit bonus</option>
                                            <option value="3" <?= ($transaction_type == '3') ? "selected" : ""; ?>>Red envelope</option>
                                            <option value="8" <?= ($transaction_type == '8') ? "selected" : ""; ?>>Agent's red envelope</option>
                                            <option value="10" <?= ($transaction_type == '10') ? "selected" : ""; ?>>Deposit Gift</option>
                                            <option value="13" <?= ($transaction_type == '13') ? "selected" : ""; ?>>Bonus</option>
                                            <option value="20" <?= ($transaction_type == '20') ? "selected" : ""; ?>>Mission rewards</option>
                                            <option value="21" <?= ($transaction_type == '21') ? "selected" : ""; ?>>Game Moved in</option>
                                            <option value="22" <?= ($transaction_type == '22') ? "selected" : ""; ?>>Game Moved out</option>
                                            <option value="25" <?= ($transaction_type == '25') ? "selected" : ""; ?>>Bank binding bonus</option>
                                            <option value="107" <?= ($transaction_type == '107') ? "selected" : ""; ?>>Weekly Award</option>
                                            <option value="124" <?= ($transaction_type == '124') ? "selected" : ""; ?>>Join channel rewards</option>
                                            <option value="118" <?= ($transaction_type == '118') ? "selected" : ""; ?>>Daily Awards</option>
                                            <option value="117" <?= ($transaction_type == '117') ? "selected" : ""; ?>>New members get bonuses by playing games</option>
                                            <option value="115" <?= ($transaction_type == '115') ? "selected" : ""; ?>>Return Awards</option>
                                        </select>



                                       <input type="date" class="form-control" id="date"
                                           name="date" value="<?= $transaction_date; ?>"
                                           max="<?= date('Y-m-d'); ?>">
                                        <input type="hidden" name="transaction_page" value="1"/>
                                        <input type="hidden" name="user" value="<?= $userid; ?>"/>
                                        <button type="submit" class="btn btn-primary">Search</button>
                                </div>
                                </form>
                            </div>

                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Transaction Type</th>
                                        <th>Time</th>
                                        <th>Balance</th>
                                        <th>Remark</th>
                                    </tr>
                                </thead>
                                <tbody class="table-border-bottom-0">
                                    <?php
                                    $i = 0;
                                    include "api/get-transactions.php";
                                    $transactions = getTransactions($conn, $userid, $transaction_type, $transaction_date, $transaction_page);
                                    if(!empty($transactions["list"])){
                                    foreach ($transactions["list"] as $row) {
                                        $i++;
                                        ?>
                                        <tr>
                                            <td><?= ($transaction_page-1)*10+$i; ?></td>
                                            <td><?= $row['typeName']; ?></td>
                                            <td><?= $row['addTime']; ?></td>
                                            <td>₹<?= $row['amount']; ?></td>
                                            <td><?= $row['remark']; ?></td>
                                        </tr>
                                    <?php }
                                    }else{ ?>
                                        <tr><td colspan="5" class="text-center">No Data</td></tr>        
                                    <?php } ?>
                                </tbody>
                            </table>
                            <div class="px-4 d-flex justify-content-end mt-5">
                                <ul class="pagination overflow-x-auto">
                                    <?php for ($i = 1; $i <= $transactions['totalPage']; $i++): ?>
                                        <li class="paginate_button page-item <?= ($i == $transaction_page) ? 'active' : ''; ?>"><a href="?transaction_page=<?= $i; ?>&user=<?= $userid; ?>&date=<?= $transaction_date; ?>&type=<?= $transaction_type; ?>#transaction-history" aria-controls="DataTables_Table_0" role="link" aria-current="page" data-dt-idx="0" tabindex="0" class="page-link"><?= $i; ?></a></li>
                                    <?php endfor; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                        <div class="card mb-5" id="refferal-record">
                            <div class="table-responsive text-nowrap">
                                <div class="d-flex justify-content-between align-items-center px-4">
                                    <h4 class="m-5">Refferal Record</h4>
                                    <form action="#refferal-record" method="get" class="d-flex gap-3 align-items-center">
                                        <select class="form-select" id="level" name="level">
                                            <option value="0" <?= $level=="0"?"selected":""; ?>>ALL</option>
                                            <option value="1" <?= $level=="1"?"selected":""; ?>>Level 1</option>
                                            <option value="2" <?= $level=="2"?"selected":""; ?>>Level 2</option>
                                            <option value="3" <?= $level=="3"?"selected":""; ?>>Level 3</option>
                                            <option value="4" <?= $level=="4"?"selected":""; ?>>Level 4</option>
                                            <option value="5" <?= $level=="5"?"selected":""; ?>>Level 5</option>
                                            <option value="6" <?= $level=="6"?"selected":""; ?>>Level 6</option>
                                            <option value="7" <?= $level=="7"?"selected":""; ?>>Level 7</option>
                                            <option value="8" <?= $level=="8"?"selected":""; ?>>Level 8</option>
                                            <option value="9" <?= $level=="9"?"selected":""; ?>>Level 9</option>
                                            <option value="10" <?= $level=="10"?"selected":""; ?>>Level 10</option>
                                        </select>
                                        <input type="hidden" name="referral_page" value="1"/>
                                        <input type="hidden" name="user" value="<?= $userid; ?>"/>
                                        <button type="submit" class="btn btn-primary">Search</button>
                                </div>
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Join Date</th>
                                            <th>Number</th>
                                            <th>Level</th>
                                            <th>Invite Id</th>
                                            <th>IP Address</th>
                                            <th>Total Recharge</th>
                                            <th>Wallet</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="table-border-bottom-0">
                                        <?php
                                        $i = 0;
                                        while ($row = mysqli_fetch_array($refer_record)) {
                                            $i++;
                                            ?>
                                            <tr>
                                                <td>
                                                    <span class="fw-medium"><?= $refferal_offset+$i; ?></span>
                                                </td>
                                                <td><?= $row['createdate']; ?></td>
                                                <td><?= $row['mobile']; ?></td>
                                                <td><?= $row['lvl']; ?></td>
                                                <td><?= $row['owncode']; ?></td>
                                                <td><?= $row["ishonup"]; ?></td>
                                                <td>₹
                                                    <?php
                                                    $totalRecharge['total'] = 0;
        											$q = mysqli_query($conn,"SELECT sum(motta) as total FROM `thevani` WHERE `sthiti` = '1' AND `balakedara` = '".$row['id']."'");
        											$totalRecharge = mysqli_fetch_assoc($q);
        											echo round($totalRecharge['total'],2); 
                                                    ?>
                                                </td>
                                                <td>₹
                                                    <?php
                                                    $totalRecharge['total'] = 0;
        											$q = mysqli_query($conn,"SELECT motta as total FROM shonu_kaichila WHERE balakedara = '".$row['id']."' ");
        											$totalRecharge = mysqli_fetch_assoc($q);
        											echo round($totalRecharge['total'],2); 
                                                    ?>
                                                </td>
                                                <td>
                                                    <a href="manageteam.php?user=<?= $row['id']; ?>" target="_blank"
                                                        class="btn btn-warning" title="User Detail">User Detail</a>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                                <div class="px-4 d-flex justify-content-end mt-5">
                                <ul class="pagination overflow-x-auto">
                                    <?php for ($i = 1; $i <= $refferal_total_pages; $i++): ?>
                                        <li class="paginate_button page-item <?= ($i == $referral_page) ? 'active' : ''; ?>"><a href="?referral_page=<?= $i; ?>&user=<?= $userid; ?>&level=<?= $level; ?>#refferal-record" aria-controls="DataTables_Table_0" role="link" aria-current="page" data-dt-idx="0" tabindex="0" class="page-link"><?= $i; ?></a></li>
                                    <?php endfor; ?>
                                </ul>
                            </div>
                            </div>
                        </div>
                        <div class="card mb-5">
                            <div class="table-responsive text-nowrap">
                                <h4 class="m-5">Recharge Record</h4>
                                <table class="datatables-table1 table">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Updated At</th>
                                            <th>Transaction ID</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="table-border-bottom-0">
                                        <?php
                                        $i = 0;
                                        while ($row = mysqli_fetch_array($deposit_record)) {
                                            $i++;
                                            ?>
                                            <tr>
                                                <td>
                                                    <span class="fw-medium"><?= $i; ?></span>
                                                </td>
                                                <td><?= $row['dinankavannuracisi']; ?></td>
                                                <td><?= $row['ullekha']; ?></td>
                                                <td><?= $row['motta']; ?></td>
                                                <td>
                                                    <?php
                                                    if ($row['sthiti'] == 1) {
                                                        echo '<span class="badge bg-label-success rounded-pill">Success</span>';
                                                    } else if ($row['sthiti'] == 0) {
                                                        echo '<span class="badge bg-label-warning rounded-pill">Pending</span>';
                                                    } else if ($row['sthiti'] == 2) {
                                                        echo '<span class="badge bg-label-danger rounded-pill">Rejected</span>';
                                                    }
                                                    ?>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card mb-5">
                            <div class="table-responsive text-nowrap">
                                <h4 class="m-5">Withdraw Record</h4>
                                <table class="datatables-table1 table">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Updated At</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="table-border-bottom-0">
                                        <?php
                                        $i = 0;
                                        while ($row = mysqli_fetch_array($withdraw_record)) {
                                            $i++;
                                            ?>
                                            <tr>
                                                <td><?= $i; ?></td>
                                                <td><?= $row['dinankavannuracisi']; ?></td>
                                                <td><?= $row['motta']; ?></td>
                                                <td>₹
                                                    <?php
                                                    if ($row['sthiti'] == 1) {
                                                        echo '<span class="badge bg-label-success rounded-pill">Success</span>';
                                                    } else if ($row['sthiti'] == 0) {
                                                        echo '<span class="badge bg-label-warning rounded-pill">Pending</span>';
                                                    } else if ($row['sthiti'] == 2) {
                                                        echo '<span class="badge bg-label-danger rounded-pill">Rejected</span>';
                                                    }
                                                    ?>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <div class="card mb-5">
                        <div class="table-responsive text-nowrap">
                            <h4 class="m-5">Wingo Bet Record</h4>
                            <table class="datatables-table1 table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Wingo Type</th>
                                        <th>Period</th>
                                        <th>Amount</th>
                                        <th>Bet</th>
                                        <th>Result</th>
                                    </tr>
                                </thead>
                                <tbody class="table-border-bottom-0">
                                    <?php
                                    $wingo = false;
                                    if (mt_table_exists($conn, 'saas_lottery_bets')) {
                                        $stmt = $conn->prepare("SELECT game_code,issue_number,stake,bet_content,status FROM saas_lottery_bets WHERE user_id=? AND game_code LIKE 'WinGo\\_%' ORDER BY id DESC LIMIT 200");
                                        if ($stmt) { $stmt->bind_param('i', $userid); $stmt->execute(); $wingo = $stmt->get_result(); }
                                    }

                                    if (!$wingo) {
                                        echo "Error: " . mysqli_error($conn); // Error handling for query failure
                                    } else {
                                        // Loop through all rows returned by the query
                                        $i = 0;
                                        while ($row = mysqli_fetch_assoc($wingo)) {
                                            $i++;
                                            ?>
                                            <tr>
                                                <td><?= $i; ?></td>
                                                <td><?=htmlspecialchars(str_replace('_', ' ', $row['game_code'])); ?></td>
                                                <td><?=htmlspecialchars($row['issue_number']); ?></td>
                                                <td><?=htmlspecialchars($row['stake']); ?></td>
                                                <td><span class="badge bg-label-warning rounded-pill"><?=htmlspecialchars($row['bet_content']); ?></span></td>
                                                <td><?php
                                                $status = strtolower((string)$row['status']);
                                                echo $status === 'won' ? '<span class="badge bg-label-success rounded-pill">WIN</span>' : ($status === 'lost' ? '<span class="badge bg-label-danger rounded-pill">LOSS</span>' : '<span class="badge bg-label-warning rounded-pill">PENDING</span>');
                                                ?></td>
                                            </tr>
                                        <?php }
                                    } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card mb-5">
                        <div class="table-responsive text-nowrap">
                            <h4 class="m-5">Trx Wingo Bet Record</h4>
                            <table class="datatables-table1 table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Trx Wingo Type</th>
                                        <th>Period</th>
                                        <th>Amount</th>
                                        <th>Result</th>
                                    </tr>
                                </thead>
                                <tbody class="table-border-bottom-0">
                                    <?php
                                    $trx = false;
                                    if (mt_table_exists($conn, 'saas_lottery_bets')) {
                                        $stmtTrx = $conn->prepare("SELECT game_code,issue_number,stake,status FROM saas_lottery_bets WHERE user_id=? AND game_code LIKE 'TrxWinGo\\_%' ORDER BY id DESC LIMIT 200");
                                        if ($stmtTrx) { $stmtTrx->bind_param('i', $userid); $stmtTrx->execute(); $trx = $stmtTrx->get_result(); }
                                    }

                                    if (!$trx) {
                                        echo "Error: " . mysqli_error($conn); // Error handling for query failure
                                    } else {
                                        $i = 0;
                                        // Loop through all rows returned by the query
                                        while ($imitator = mysqli_fetch_assoc($trx)) {
                                            $i++;
                                            ?>
                                            <tr>
                                                <td><?= $i; ?></td>
                                                <td><?php echo htmlspecialchars(str_replace('_', ' ', $imitator['game_code'])); ?></td>
                                                <td><?php echo htmlspecialchars($imitator['issue_number']); ?></td>
                                                <td><?php echo htmlspecialchars($imitator['stake']); ?></td>
                                                <td><?php
                                                $status = strtolower((string)$imitator['status']);
                                                echo $status === 'won' ? '<span class="badge bg-label-success rounded-pill">WIN</span>' : ($status === 'lost' ? '<span class="badge bg-label-danger rounded-pill">LOSS</span>' : '<span class="badge bg-label-warning rounded-pill">PENDING</span>');
                                                ?></td>
                                            </tr>
                                        <?php }
                                    } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card mb-5">
                        <div class="table-responsive text-nowrap">
                            <h4 class="m-5">K3 / 5D / Moto Racing Bet Record</h4>
                            <table class="datatables-table1 table">
                                <thead><tr><th>#</th><th>Game</th><th>Period</th><th>Bet</th><th>Amount</th><th>Result</th></tr></thead>
                                <tbody class="table-border-bottom-0">
                                <?php
                                $otherGames = false;
                                if (mt_table_exists($conn, 'saas_lottery_bets')) {
                                    $stmtOther = $conn->prepare("SELECT game_code,issue_number,bet_content,stake,status FROM saas_lottery_bets WHERE user_id=? AND (game_code LIKE 'K3\\_%' OR game_code LIKE 'D5\\_%' OR game_code LIKE 'MotoRace\\_%') ORDER BY id DESC LIMIT 300");
                                    if ($stmtOther) { $stmtOther->bind_param('i', $userid); $stmtOther->execute(); $otherGames = $stmtOther->get_result(); }
                                }
                                $i = 0;
                                while ($otherGames && ($gameBet = $otherGames->fetch_assoc())) { $i++; $status = strtolower((string)$gameBet['status']); ?>
                                    <tr>
                                        <td><?= $i ?></td>
                                        <td><?= htmlspecialchars(str_replace('_', ' ', $gameBet['game_code'])) ?></td>
                                        <td><?= htmlspecialchars($gameBet['issue_number']) ?></td>
                                        <td><span class="badge bg-label-warning rounded-pill"><?= htmlspecialchars($gameBet['bet_content']) ?></span></td>
                                        <td><?= htmlspecialchars($gameBet['stake']) ?></td>
                                        <td><?= $status === 'won' ? '<span class="badge bg-label-success rounded-pill">WIN</span>' : ($status === 'lost' ? '<span class="badge bg-label-danger rounded-pill">LOSS</span>' : '<span class="badge bg-label-warning rounded-pill">PENDING</span>') ?></td>
                                    </tr>
                                <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Overlay -->
                <div class="layout-overlay layout-menu-toggle"></div>

                <!-- Drag Target Area To SlideIn Menu On Small Screens -->
                <div class="drag-target"></div>
            </div>
            <!-- / Layout wrapper -->

            <!-- Core JS -->
            <!-- build:js assets/vendor/js/core.js -->
            <script src="assets/vendor/libs/jquery/jquery.js"></script>
            <script src="assets/vendor/libs/popper/popper.js"></script>
            <script src="assets/vendor/js/bootstrap.js"></script>
            <script src="assets/vendor/libs/node-waves/node-waves.js"></script>
            <script src="assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
            <script src="assets/vendor/libs/hammer/hammer.js"></script>
            <script src="assets/vendor/libs/i18n/i18n.js"></script>
            <script src="assets/vendor/libs/typeahead-js/typeahead.js"></script>
            <script src="assets/vendor/js/menu.js"></script>

            <!-- endbuild -->

            <!-- Vendors JS -->
            <script src="assets/vendor/libs/moment/moment.js"></script>
            <script src="assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js"></script>
            <script src="assets/vendor/libs/select2/select2.js"></script>
            <script src="assets/vendor/libs/@form-validation/popular.js"></script>
            <script src="assets/vendor/libs/@form-validation/bootstrap5.js"></script>
            <script src="assets/vendor/libs/@form-validation/auto-focus.js"></script>
            <script src="assets/vendor/libs/cleavejs/cleave.js"></script>
            <script src="assets/vendor/libs/cleavejs/cleave-phone.js"></script>

            <!-- Main JS -->
            <script src="assets/js/main.js"></script>
            <script>
                $(function () {
                    $('.datatables-table1').DataTable({
                        "paging": true,
                        "lengthChange": true,
                        "searching": true,
                        "ordering": false,
                        "info": true,
                        "autoWidth": true,
                        "pageLength": 10,
                        "dom":
                            '<"row"' +
                            '<"col-md-12"<"d-flex align-items-center justify-content-md-end justify-content-center"<l><"me-4"f>>>' +
                            '>t' +
                            '<"row p-5"' +
                            '<"col-sm-12 col-md-6"i>' +
                            '<"col-sm-12 col-md-6"p>' +
                            '>',
                        "language": {
                            sLengthMenu: 'Show _MENU_',
                            search: '',
                            searchPlaceholder: 'Search User',
                            paginate: {
                                next: '<i class="ri-arrow-right-s-line"></i>',
                                previous: '<i class="ri-arrow-left-s-line"></i>'
                            }
                        }
                    });
                });
                if (window.history.replaceState) {
                    window.history.replaceState(null, null, window.location.href);
                }
            </script>
</body>

</html>
