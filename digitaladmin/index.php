<?php
// debug_errors.php
$error_log_file = 'index_error.log';

// All types of errors enable करें
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', $error_log_file);

session_start();
if (empty($_SESSION['unohs'])) {
    header('Location: login.php?msg=true');
    exit;
}
date_default_timezone_set("Asia/Kolkata");

include("api/conn.php");

$curdate = date('Y-m-d h:i:s');

// Total Users
$total_users = 0;
$result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM shonu_subjects");
if ($result) {
    $total_users = mysqli_fetch_assoc($result)['total'];
}

// Active Users (status = 1)
$active_users = 0;
$result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM shonu_subjects WHERE status = 1");
if ($result) {
    $active_users = mysqli_fetch_assoc($result)['total'];
}

// Banned Users (status = 0)
$banned_users = 0;
$result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM shonu_subjects WHERE status = 0");
if ($result) {
    $banned_users = mysqli_fetch_assoc($result)['total'];
}

// Total Wallet (sum of motta)
$total_wallet = 0;
$wallet_q = mysqli_query($conn, "SELECT SUM(CAST(motta AS DECIMAL(10,2))) AS total_wallet FROM shonu_kaichila");
if ($wallet_q) {
    $total_wallet = mysqli_fetch_assoc($wallet_q)['total_wallet'] ?? 0;
}

// Fetch All Users & Join wallet table
$users = [];
$users_query = mysqli_query($conn, "
    SELECT * 
    FROM shonu_subjects s
    LEFT JOIN shonu_kaichila k ON s.id = k.balakedara
    ORDER BY s.id DESC
");
if ($users_query) {
    while ($row = mysqli_fetch_assoc($users_query)) {
        $row['wallet'] = $row['wallet'] ?? 0;
        $row['recharge'] = $row['rebet'] ?? 0;
        $row['first_recharge'] = $row['bonus'] ?? 0;
        $row['ifsc'] = $row['ifsc'] ?? 'N/A';
        $row['account_no'] = $row['accountno'] ?? 'N/A';
        $users[] = $row;
    }
}


// ✅ Process form submission for allowbet
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['allowbet'])) {
        $allow = ($_POST['allowbet'] == 0) ? 0 : 1;
        // 👇 Change id from 0 to 1
        mysqli_query($conn, "UPDATE web_setting SET allowbet = $allow WHERE id = 1");
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    // Same Trend is a required game service and is permanently enabled.
    // Keep accepting the old dashboard form for compatibility, but never
    // allow it to disable settlement/result synchronization.
    if (isset($_POST['wingo30'])) {
        mysqli_query($conn, "UPDATE sametrend SET id=1, status='active' LIMIT 1");
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

// ✅ Get current allowbet value (from id = 1)
$allowbet = 1; // default
$res = mysqli_query($conn, "SELECT allowbet FROM web_setting WHERE id = 1");
if ($row = mysqli_fetch_assoc($res)) {
    $allowbet = $row['allowbet'];
}

// ✅ Handle Recharge Allow/Block toggle (reliable version)
if (isset($_POST['allow_recharge'])) {
    // sanitize and cast
    $allowRechargeVal = intval($_POST['allow_recharge']);
    // restrict to 0 or 1
    $allowRechargeVal = ($allowRechargeVal === 0) ? 0 : 1;
    mysqli_query($conn, "UPDATE web_setting SET allow_recharge = $allowRechargeVal WHERE id = 1");
    // redirect back to avoid resubmit
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// ✅ Get current allow_recharge value (from id = 1)
$allow_recharge = 1; // default ON
$res_r = mysqli_query($conn, "SELECT allow_recharge FROM web_setting WHERE id = 1 LIMIT 1");
if ($res_r && $row_r = mysqli_fetch_assoc($res_r)) {
    $allow_recharge = intval($row_r['allow_recharge']);
}



// ✅ Get current Wingo 30s Same Trend status
$wingo30status = 'active';
// Self-heal any direct/stale database change whenever the dashboard opens.
mysqli_query($conn, "UPDATE sametrend SET id=1, status='active' LIMIT 1");
$wingoRow = mysqli_fetch_assoc(mysqli_query($conn, "SELECT status FROM sametrend LIMIT 1"));
if ($wingoRow) {
    $wingo30status = 'active';
}

// Server Details
$server_ip = $_SERVER['SERVER_ADDR'];
$ip_parts = explode('.', $server_ip);
$masked_ip = $ip_parts[0] . '.' . $ip_parts[1] . '.***.***';


// Fetch Login Notifications

// $loginNotifs = [];
// $notifQuery = mysqli_query($conn, "SELECT * FROM notification WHERE state = 0 ORDER BY id DESC LIMIT 5");
// if ($notifQuery) {
//     while ($row = mysqli_fetch_assoc($notifQuery)) {
//         $loginNotifs[] = $row;
//     }
// }

?>

<?php
function dash_table_exists(mysqli $db, string $table): bool {
    if (!preg_match('/^[A-Za-z0-9_]+$/',$table)) return false;
    $safe=$db->real_escape_string($table);
    $q=$db->query("SHOW TABLES LIKE '{$safe}'");
    return $q instanceof mysqli_result && $q->num_rows>0;
}
function dash_scalar(mysqli $db, string $sql): float {
    try { $q=$db->query($sql); if(!$q) return 0.0; $r=$q->fetch_row(); return (float)($r[0]??0); }
    catch(Throwable $e){ error_log('[dashboard] '.$e->getMessage()); return 0.0; }
}
function dash_game_totals(mysqli $db, string $date): array {
    $bet=0.0; $win=0.0;
    $legacy=['bajikattuttate','bajikattuttate_drei','bajikattuttate_funf','bajikattuttate_zehn','bajikattuttate_kemuru','bajikattuttate_kemuru_drei','bajikattuttate_kemuru_funf','bajikattuttate_kemuru_zehn','bajikattuttate_aidudi','bajikattuttate_aidudi_drei','bajikattuttate_aidudi_funf','bajikattuttate_aidudi_zehn','bajikattuttate_trx','bajikattuttate_trx3','bajikattuttate_trx5','bajikattuttate_trx10'];
    $safeDate=$db->real_escape_string($date);
    foreach($legacy as $table){
        if(!dash_table_exists($db,$table)) continue;
        $bet += dash_scalar($db,"SELECT COALESCE(SUM(ketebida),0) FROM `{$table}` WHERE DATE(tiarikala)='{$safeDate}'");
        $win += dash_scalar($db,"SELECT COALESCE(SUM(sesabida),0) FROM `{$table}` WHERE phalaphala='gagner' AND DATE(tiarikala)='{$safeDate}'");
    }
    if(dash_table_exists($db,'saas_lottery_bets')){
        $bet += dash_scalar($db,"SELECT COALESCE(SUM(stake),0) FROM saas_lottery_bets WHERE DATE(created_at)='{$safeDate}' AND status<>'cancelled'");
        $win += dash_scalar($db,"SELECT COALESCE(SUM(payout),0) FROM saas_lottery_bets WHERE DATE(created_at)='{$safeDate}' AND status IN ('win','won','settled')");
    }
    return ['bet'=>round($bet,2),'win'=>round($win,2)];
}

// Optional support-problem counter: no hard dependency on a placeholder table.
$problemCount=0;
if(dash_table_exists($conn,'your_table')){
    $problemCount=(int)dash_scalar($conn,"SELECT COUNT(*) FROM your_table WHERE prob='Game Problems' AND status=2");
}

$deposits=[];
$todayStart=date('Y-m-d 00:00:00');
$todayEnd=date('Y-m-d 23:59:59');
if(dash_table_exists($conn,'thevani')){
    // Server-generated date values are escaped and queried directly so the
    // dashboard also works on mysqli installations without mysqlnd/get_result.
    $safeTodayStart=$conn->real_escape_string($todayStart);
    $safeTodayEnd=$conn->real_escape_string($todayEnd);
    $rs=$conn->query("SELECT * FROM thevani WHERE sthiti=0 AND dinankavannuracisi BETWEEN '{$safeTodayStart}' AND '{$safeTodayEnd}' ORDER BY dinankavannuracisi DESC LIMIT 5");
    if($rs){while($r=$rs->fetch_assoc())$deposits[]=$r;}
}

$profitLossData=[];$profitLabels=[];
for($i=6;$i>=0;$i--){
    $date=date('Y-m-d',strtotime("-$i days"));
    $profitLabels[]=date('M d',strtotime($date));
    $tot=dash_game_totals($conn,$date);
    $profitLossData[]=round($tot['bet']-$tot['win'],2);
}
$todayDate=date('Y-m-d');
$todayTotals=dash_game_totals($conn,$todayDate);
$todayTotalBet=$todayTotals['bet'];
$todayTotalWin=$todayTotals['win'];
$todayProfit=round($todayTotalBet-$todayTotalWin,2);
$todayLoss=$todayTotalWin;
$todayCommission=dash_table_exists($conn,'app_saas_commissions') ? dash_scalar($conn,"SELECT COALESCE(SUM(commission_amount),0) FROM app_saas_commissions WHERE DATE(created_at)=CURDATE()") : 0.0;

$demoFilter = dash_table_exists($conn,'demo') ? " AND balakedara NOT IN (SELECT balakedara FROM demo WHERE sthiti='1')" : '';
$userDemoFilter = dash_table_exists($conn,'demo') ? " AND id NOT IN (SELECT balakedara FROM demo WHERE sthiti='1')" : '';
$dashTodayJoin = dash_scalar($conn, "SELECT COUNT(*) FROM shonu_subjects WHERE status=1 {$userDemoFilter} AND DATE(createdate)=CURDATE()");
$dashTotalUsers = dash_scalar($conn, "SELECT COUNT(*) FROM shonu_subjects WHERE status=1 {$userDemoFilter}");
$dashTodayRecharge = dash_table_exists($conn,'thevani') ? dash_scalar($conn, "SELECT COALESCE(SUM(motta),0) FROM thevani WHERE sthiti='1' {$demoFilter} AND DATE(dinankavannuracisi)=CURDATE()") : 0;
$dashTodayWithdrawal = dash_table_exists($conn,'hintegedukolli') ? dash_scalar($conn, "SELECT COALESCE(SUM(motta),0) FROM hintegedukolli WHERE sthiti='1' {$demoFilter} AND DATE(dinankavannuracisi)=CURDATE()") : 0;
$dashUserBalance = dash_table_exists($conn,'shonu_kaichila') ? dash_scalar($conn, "SELECT COALESCE(SUM(motta),0) FROM shonu_kaichila WHERE motta>0 {$demoFilter}") : 0;
$dashPendingRecharge = dash_table_exists($conn,'thevani') ? dash_scalar($conn, "SELECT COALESCE(SUM(motta),0) FROM thevani WHERE sthiti='0' {$demoFilter}") : 0;
$dashSuccessRecharge = dash_table_exists($conn,'thevani') ? dash_scalar($conn, "SELECT COALESCE(SUM(motta),0) FROM thevani WHERE sthiti='1' {$demoFilter}") : 0;
$dashTotalWithdrawal = dash_table_exists($conn,'hintegedukolli') ? dash_scalar($conn, "SELECT COALESCE(SUM(motta),0) FROM hintegedukolli WHERE sthiti='1' {$demoFilter}") : 0;
$dashPendingWithdrawal = dash_table_exists($conn,'hintegedukolli') ? dash_scalar($conn, "SELECT COALESCE(SUM(motta),0) FROM hintegedukolli WHERE sthiti='0' {$demoFilter}") : 0;


$depositData=[];$withdrawalData=[];$depositLabels=[];
for($i=6;$i>=0;$i--){
    $date=date('Y-m-d',strtotime("-$i days"));$safe=$conn->real_escape_string($date);$depositLabels[]=date('M d',strtotime($date));
    $depositData[]=dash_table_exists($conn,'thevani')?dash_scalar($conn,"SELECT COALESCE(SUM(motta),0) FROM thevani WHERE sthiti='1' AND DATE(dinankavannuracisi)='{$safe}'"):0.0;
    $withdrawalData[]=dash_table_exists($conn,'hintegedukolli')?dash_scalar($conn,"SELECT COALESCE(SUM(motta),0) FROM hintegedukolli WHERE sthiti='1' AND DATE(dinankavannuracisi)='{$safe}'"):0.0;
}
$userGrowthData=[];$userGrowthLabels=[];
for($i=5;$i>=0;$i--){
    $month=date('Y-m',strtotime("-$i months"));$safe=$conn->real_escape_string($month);$userGrowthLabels[]=date('M Y',strtotime($month));
    $userGrowthData[]=(int)dash_scalar($conn,"SELECT COUNT(*) FROM shonu_subjects WHERE DATE_FORMAT(createdate,'%Y-%m')='{$safe}'");
}
?>

<!doctype html>

<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr"
    data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template" data-style="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

    <title>Dashboard</title>

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
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons" />

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
    <style>
        /* Professional Cards Styling */
        .card {
            transition: all 0.3s ease;
            border: none;
            border-radius: 12px;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15) !important;
        }

        .bg-gradient-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        }

        /* Chart Container Styling */
        canvas {
            border-radius: 8px;
        }

        /* Welcome Modal Styling */
        .modal-content {
            border-radius: 16px;
            overflow: hidden;
        }

        .modal-header {
            padding: 1.5rem;
        }

        .modal-body {
            padding: 2rem;
        }

        .modal-footer {
            padding: 1.5rem;
        }

        /* Button Styling */
        .btn {
            border-radius: 8px;
            font-weight: 600;
            padding: 0.5rem 1.5rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
            transform: translateY(-2px);
        }


        /**/
        .notification-box {
            position: fixed;
            bottom: 60px;
            right: -400px;
            width: 340px;
            z-index: 9999;
            transition: right 0.5s ease-in-out;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .notification-box.show {
            right: 20px;
        }

        .notification-message {
            background: #31334e;
            color: #fff;
            padding: 18px 20px;
            border-radius: 12px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.25);
            font-size: 14.5px;
            line-height: 1.6em;
            word-wrap: break-word;
            word-break: break-word;
            display: flex;
            flex-direction: column;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .notification-message strong {
            font-size: 16px;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .notification-message .notif-body {
            padding: 5px 0;
            font-weight: 400;
        }

        .notification-message .notif-time {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.85);
            text-align: right;
        }

        .notification-box {
            position: fixed;
            bottom: 60px;
            right: -400px;
            width: 340px;
            z-index: 9999;
            transition: right 0.5s ease-in-out;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .notification-box.show {
            right: 20px;
        }

        .notification-messages {
            background: #31334e;
            color: #fff;
            padding: 18px 20px;
            border-radius: 12px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.25);
            font-size: 14.5px;
            line-height: 1.6em;
            word-wrap: break-word;
            word-break: break-word;
            display: flex;
            flex-direction: column;
            transition: all 0.3s ease;
        }

        @keyframes fadeInUp {
            0% {
                opacity: 0;
                transform: translateY(30px);
            }

            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in-up {
            animation: fadeInUp 0.4s ease-out;
        }

        .notification-box-login,
        .notification-box-deposit {
            position: fixed;
            bottom: 60px;
            right: -400px;
            width: 340px;
            z-index: 9999;
            transition: right 0.5s ease-in-out;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .notification-box-login.show,
        .notification-box-deposit.show {
            right: 20px;
        }
    </style>


    <!--Manual Css-->

    <style>
        .setting-section {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin: 30px auto;
            max-width: 1200px;
            justify-content: center;
        }

        .setting-card {
            /*background: #292c43;*/
            border-radius: 12px;
            padding: 20px 25px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.06);
            flex: 1 1 500px;
            min-width: 300px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-left {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            flex: 1;
        }

        .card-left .icon {
            font-size: 28px;
            color: #ff0000;
            margin-top: 3px;
        }

        .title {
            font-weight: 700;
            font-size: 16px;
            /*color: #ffffff;*/
            margin-bottom: 5px;
        }

        .desc {
            font-size: 13px;
            color: #8a8a8a;
        }

        .card-right {
            display: flex;
            flex-direction: column;
            align-items: center;
            min-width: 80px;
        }

        /* Toggle */
        .switch {
            position: relative;
            display: inline-block;
            width: 44px;
            height: 24px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 24px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked+.slider {
            background-color: #6c3ff2;
        }

        input:checked+.slider:before {
            transform: translateX(20px);
        }

        .slider.round {
            border-radius: 34px;
        }

        .status {
            margin-top: 5px;
            font-size: 12px;
            color: #333;
        }



        /* Floating Transaction Icon */
        #floating-transaction-icon {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #3362ff;
            color: white;
            padding: 15px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 20px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.2);
            z-index: 9999;
            transition: all 0.3s ease-in-out;
        }

        #floating-transaction-icon:hover {
            background: #e64a19;
        }

        /* Transaction Panel */
        #transaction-panel {
            position: fixed;
            bottom: 70px;
            right: 20px;
            width: 300px;
            height: 400px;
            background: white;
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.2);
            display: none;
            flex-direction: column;
            z-index: 10000;
        }

        /* Header */
        .transaction-header {
            background: #3362ff;
            color: white;
            padding: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
        }

        .transaction-header button {
            background: none;
            border: none;
            color: white;
            font-size: 20px;
            cursor: pointer;
        }

        /* Content */
        #transaction-content {
            padding: 10px;
            overflow-y: auto;
            height: 350px;
        }

        .transaction-item {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }

        .transaction-item.success {
            color: green;
        }

        .transaction-item.failed {
            color: red;
        }
    </style>

    <style>
        /* Chart Container Styling - Smaller Size */
        .chart-container {
            position: relative;
            height: 200px !important;
            /* Reduced from 250px */
            width: 100%;
        }

        .card-body {
            padding: 1rem !important;
            /* Reduced padding */
        }

        /* Smaller chart headers */
        .card-header h6 {
            font-size: 14px !important;
            margin-bottom: 0.5rem !important;
        }

        /* Responsive charts for mobile */
        @media (max-width: 768px) {
            .chart-container {
                height: 180px !important;
            }

            .card-body {
                padding: 0.75rem !important;
            }
        }
    </style>

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



                    <!--Server Details-->
                    <!--          		<div class="main-panel">-->
                    <!--<div class="content-wrapper">-->
                    <!--  <div class="row">-->
                    <!--    <div class="col-sm-12 mb-4 mb-xl-0">-->
                    <!--      <div class="card shadow-sm border-0 rounded p-4 d-flex flex-md-row flex-column justify-content-between align-items-center" style="background:; color: #fff;">-->
                    <!--        <div>-->
                    <!--          <h4 class="font-weight-bold mb-1">👋 Hi, DAMAN PRO ADMIN Welcome back!</h4>-->
                    <!--          <p class="mb-0">-->
                    <!--            <i class="material-icons align-middle" style="font-size: 18px; vertical-align: middle;">event</i>-->
                    <!--            <?php echo date("F d, Y"); ?>-->
                    <!--          </p>-->
                    <!--        </div>-->

                    <!--        <div class="d-flex align-items-center gap-3">-->
                    <!--          <div class="text-white text-end me-3" style="font-size: 12px; line-height: 1.5;">-->
                    <!--            <div><i class="material-icons" style="font-size: 16px; vertical-align: middle;">dns</i> IP: <?php echo $masked_ip; ?></div>-->
                    <!--            <div><i class="material-icons" style="font-size: 16px; vertical-align: middle;">memory</i> PHP: <?php echo phpversion(); ?></div>-->
                    <!--            <div><i class="material-icons" style="font-size: 16px; vertical-align: middle;">storage</i> <?php echo $_SERVER['SERVER_NAME']; ?></div>-->
                    <!--          </div>-->
                    <!--          <img src="https://shreewin.club9.eu.cc/digitaladmin/assets/img/avatars/1.png" width="50" height="50" alt="User Avatar" style="border-radius: 50%;">-->
                    <!--        </div>-->

                    <!--      </div>-->
                    <!--    </div>-->
                    <!--  </div>-->


                    <!-- Professional Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-0 shadow-sm h-100 py-2" style="border-left: 4px solid #4e73df !important;">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Total Users
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $total_users; ?></div>
                                            <div class="mt-2">
                                                <a href="users.php" class="text-xs text-primary font-weight-bold">
                                                    View Details <i class="ri-arrow-right-line"></i>
                                                </a>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="ri-user-line fa-2x text-primary"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-0 shadow-sm h-100 py-2" style="border-left: 4px solid #1cc88a !important;">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                Active Users
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $active_users; ?></div>
                                            <div class="mt-2">
                                                <a href="users.php" class="text-xs text-success font-weight-bold">
                                                    View Details <i class="ri-arrow-right-line"></i>
                                                </a>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="ri-user-follow-line fa-2x text-success"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-0 shadow-sm h-100 py-2" style="border-left: 4px solid #e74a3b !important;">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                                Banned Users
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $banned_users; ?></div>
                                            <div class="mt-2">
                                                <a href="users.php" class="text-xs text-danger font-weight-bold">
                                                    View Details <i class="ri-arrow-right-line"></i>
                                                </a>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="ri-user-unfollow-line fa-2x text-danger"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-0 shadow-sm h-100 py-2" style="border-left: 4px solid #f6c23e !important;">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                Wallet Balance
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">₹<?= number_format($total_wallet, 2); ?></div>
                                            <div class="mt-2">
                                                <a href="users.php" class="text-xs text-warning font-weight-bold">
                                                    View Details <i class="ri-arrow-right-line"></i>
                                                </a>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="ri-wallet-3-line fa-2x text-warning"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Section with Smaller Containers -->
                    <div class="row mb-4">
                        <!-- Total Profit/Loss Chart -->
                        <div class="col-xl-6 col-lg-6 mb-4">
                            <div class="card shadow-sm border-0">
                                <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center"> <!-- Reduced py-3 to py-2 -->
                                    <h6 class="m-0 font-weight-bold text-primary" style="font-size: 14px;">
                                        <i class="ri-line-chart-line me-2"></i>Profit/Loss (7 Days)
                                    </h6>
                                    <span class="badge bg-primary" style="font-size: 12px;">₹<?= number_format(array_sum($profitLossData), 2) ?></span>
                                </div>
                                <div class="card-body p-2"> <!-- Reduced padding -->
                                    <div class="chart-container">
                                        <canvas id="totalProfitLossChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Today's Performance Chart -->
                        <div class="col-xl-6 col-lg-6 mb-4">
                            <div class="card shadow-sm border-0">
                                <div class="card-header bg-white py-2"> <!-- Reduced padding -->
                                    <h6 class="m-0 font-weight-bold text-success" style="font-size: 14px;">
                                        <i class="ri-bar-chart-line me-2"></i>Today's Performance
                                    </h6>
                                </div>
                                <div class="card-body p-2"> <!-- Reduced padding -->
                                    <div class="text-center mb-2" style="margin-bottom: 0.5rem !important;">
                                        <h4 class="text-<?= $todayProfit >= 0 ? 'success' : 'danger' ?>" style="font-size: 18px;">
                                            ₹<?= number_format(abs($todayProfit), 2) ?>
                                        </h4>
                                        <small class="text-muted" style="font-size: 12px;"><?= $todayProfit >= 0 ? 'Profit' : 'Loss' ?> Today</small>
                                    </div>
                                    <div class="chart-container">
                                        <canvas id="todayPerformanceChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Deposit vs Withdrawal Chart -->
                        <div class="col-xl-6 col-lg-6 mb-4">
                            <div class="card shadow-sm border-0">
                                <div class="card-header bg-white py-2"> <!-- Reduced padding -->
                                    <h6 class="m-0 font-weight-bold text-info" style="font-size: 14px;">
                                        <i class="ri-exchange-dollar-line me-2"></i>Deposit vs Withdrawal
                                    </h6>
                                </div>
                                <div class="card-body p-2"> <!-- Reduced padding -->
                                    <div class="row text-center mb-2" style="margin-bottom: 0.5rem !important;">
                                        <div class="col-6">
                                            <h6 class="text-success" style="font-size: 14px;">₹<?= number_format(array_sum($depositData), 2) ?></h6>
                                            <small class="text-muted" style="font-size: 11px;">Total Deposit</small>
                                        </div>
                                        <div class="col-6">
                                            <h6 class="text-danger" style="font-size: 14px;">₹<?= number_format(array_sum($withdrawalData), 2) ?></h6>
                                            <small class="text-muted" style="font-size: 11px;">Total Withdrawal</small>
                                        </div>
                                    </div>
                                    <div class="chart-container">
                                        <canvas id="depositWithdrawalChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- User Growth Chart -->
                        <div class="col-xl-6 col-lg-6 mb-4">
                            <div class="card shadow-sm border-0">
                                <div class="card-header bg-white py-2"> <!-- Reduced padding -->
                                    <h6 class="m-0 font-weight-bold text-warning" style="font-size: 14px;">
                                        <i class="ri-user-add-line me-2"></i>User Growth (6 Months)
                                    </h6>
                                </div>
                                <div class="card-body p-2"> <!-- Reduced padding -->
                                    <div class="text-center mb-2" style="margin-bottom: 0.5rem !important;">
                                        <h4 class="text-warning" style="font-size: 18px;"><?= array_sum($userGrowthData) ?></h4>
                                        <small class="text-muted" style="font-size: 12px;">New Users</small>
                                    </div>
                                    <div class="chart-container">
                                        <canvas id="userGrowthChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php
                    $chkserial = mysqli_query($conn, "select * from `nirvahaka_shonu` where `unohs`='" . $_SESSION['unohs'] . "'");
                    $salu = mysqli_fetch_array($chkserial);
                    $dashboard = $salu['dashboard'];
                    if ($dashboard == 1) {
                    ?>
                        <div class="row g-6 mb-6">
                            <div class="col-sm-6 col-xl-3">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div class="me-1">
                                                <p class="text-heading mb-1">Total User Join</p>
                                                <div class="d-flex align-items-center">
                                                    <h4 class="mb-1 me-2 text-success">
                                                        <?= number_format((float)$dashTodayJoin,0) ?>
                                                    </h4>
                                                </div>
                                                <a href="" class="text-success">See in Detail</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6 col-xl-3">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div class="me-1">
                                                <p class="text-heading mb-1">Today's Recharge</p>
                                                <div class="d-flex align-items-center">
                                                    <h4 class="mb-1 me-2 text-success">
                                                        <?= number_format((float)$dashTodayRecharge,0) ?>
                                                    </h4>
                                                </div>
                                                <a href="todays-recharge.php" class="text-success">See in Detail</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6 col-xl-3">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div class="me-1">
                                                <p class="text-heading mb-1">Today's Withdrawal</p>
                                                <div class="d-flex align-items-center">
                                                    <h4 class="mb-1 me-2 text-success">
                                                        <?= number_format((float)$dashTodayWithdrawal,0) ?>
                                                    </h4>
                                                </div>
                                                <a href="todays-withdraw.php" class="text-success">See in Detail</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6 col-xl-3">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div class="me-1">
                                                <p class="text-heading mb-1">User Balance</p>
                                                <div class="d-flex align-items-center">
                                                    <h4 class="mb-1 me-2 text-success">
                                                        <?= number_format((float)$dashUserBalance,2) ?>
                                                    </h4>
                                                </div>
                                                <a href="users.php" class="text-success">See in Detail</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6 col-xl-3">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div class="me-1">
                                                <p class="text-heading mb-1">Total Users</p>
                                                <div class="d-flex align-items-center">
                                                    <h4 class="mb-1 me-2 text-success">
                                                        <?= number_format((float)$dashTotalUsers,0) ?>
                                                    </h4>
                                                </div>
                                                <a href="users.php" class="text-success">See in Detail</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6 col-xl-3">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div class="me-1">
                                                <p class="text-heading mb-1">Pending Recharge</p>
                                                <div class="d-flex align-items-center">
                                                    <h4 class="mb-1 me-2 text-success">
                                                        <?= number_format((float)$dashPendingRecharge,0) ?>
                                                    </h4>
                                                </div>
                                                <a href="depositupdate.php" class="text-white">See in Detail</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6 col-xl-3">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div class="me-1">
                                                <p class="text-heading mb-1">Success Recharge</p>
                                                <div class="d-flex align-items-center">
                                                    <h4 class="mb-1 me-2 text-success">
                                                        <?= number_format((float)$dashSuccessRecharge,0) ?>
                                                    </h4>
                                                </div>
                                                <a href="depositupdate.php" class="text-success">See in Detail</a>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6 col-xl-3">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div class="me-1">
                                                <p class="text-heading mb-1">Total Withdrawal</p>
                                                <div class="d-flex align-items-center">
                                                    <h4 class="mb-1 me-2 text-success">
                                                        <?= number_format((float)$dashTotalWithdrawal,0) ?>
                                                    </h4>
                                                </div>
                                                <a href="withdrawsent.php" class="text-success">See in Detail</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6 col-xl-3">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div class="me-1">
                                                <p class="text-heading mb-1">Withdrawal Requests</p>
                                                <div class="d-flex align-items-center">
                                                    <h4 class="mb-1 me-2 text-success">
                                                        <?= number_format((float)$dashPendingWithdrawal,0) ?>
                                                    </h4>
                                                </div>
                                                <a href="withdrawapply.php" class="text-white">See in Detail</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6 col-xl-3">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div class="me-1">
                                                <p class="text-heading mb-1">Today's total bet</p>
                                                <div class="d-flex align-items-center">
                                                    <h4 class="mb-1 me-2 text-success">
                                                        <?= number_format((float)$todayTotalBet,2) ?>
                                                    </h4>
                                                </div>
                                                <a href="" class="text-success">See in Detail</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6 col-xl-3">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div class="me-1">
                                                <p class="text-heading mb-1">Today's total win</p>
                                                <div class="d-flex align-items-center">
                                                    <h4 class="mb-1 me-2 text-success">
                                                        <?= number_format((float)$todayTotalWin,2) ?>
                                                    </h4>
                                                </div>
                                                <a href="" class="text-success">See in Detail</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6 col-xl-3">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div class="me-1">
                                                <p class="text-heading mb-1">Today's profit</p>
                                                <div class="d-flex align-items-center">
                                                    <h4 class="mb-1 me-2 text-success">
                                                        <?= number_format((float)$todayProfit,2) ?>
                                                    </h4>
                                                </div>
                                                <a href="" class="text-success">See in Detail</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>


    <div class="setting-section">
        <!-- Card 1 -->
        <!--<div class="setting-card">-->
        <!--  <div class="card-left">-->
        <!--    <span class="material-icons icon">casino</span>-->
        <!--    <div>-->
        <!--      <div class="title">Game Winning Type</div>-->
        <!--      <div class="desc">Random Betting chooses a winner by luck, while Least Bet lets the smallest unique bet win, mixing chance and strategy.</div>-->
        <!--    </div>-->
        <!--  </div>-->
        <!--  <div class="card-right">-->
        <!--    <label class="switch">-->
        <!--      <input type="checkbox" disabled>-->
        <!--      <span class="slider round"></span>-->
        <!--    </label>-->
        <!--    <span class="status" style="color: #999;">Coming Soon</span>-->
        <!--  </div>-->
        <!--</div>-->


        <!-- ✅ Card 1: Need to Deposit First -->
        <!--<div class="setting-card">-->
        <!--  <div class="card-left">-->
        <!--    <span class="material-icons icon">account_balance</span>-->
        <!--    <div>-->
        <!--      <div class="title">Need to Deposit First</div>-->
        <!--      <div class="desc">-->
        <!--        'Required' means players must deposit to play, while 'Optional' allows play without deposit, giving flexibility to join as desired.-->
        <!--      </div>-->
        <!--    </div>-->
        <!--  </div>-->
        <!--  <div class="card-right">-->
        <!--    <form method="post">-->
        <!-- Always sends allowbet=1 unless checkbox overrides -->
        <!--      <input type="hidden" name="allowbet" value="1">-->
        <!--      <label class="switch">-->
        <!--        <input type="checkbox" name="allowbet" value="0" onchange="this.form.submit()" <?= $allowbet == 0 ? 'checked' : '' ?>>-->
        <!--        <span class="slider round"></span>-->
        <!--      </label>-->
        <!--    </form>-->
        <!--    <span class="status"><?= $allowbet == 0 ? 'ON' : 'OFF' ?></span>-->
        <!--  </div>-->
        <!--</div>-->


        <!-- ✅ Card 2: Wingo 30s Same Trend -->
        <!--<div class="setting-card">-->
        <!--  <div class="card-left">-->
        <!--    <span class="material-icons icon">sports_esports</span>-->
        <!--    <div>-->
        <!--      <div class="title">Wingo  Same Trend</div>-->
        <!--      <div class="desc">Toggle to enable or disable Wingo Game Mode.</div>-->
        <!--    </div>-->
        <!--  </div>-->
        <!--  <div class="card-right">-->
        <!--    <form method="post">-->
        <!-- inactive = default (OFF) -->
        <!--      <input type="hidden" name="wingo30" value="inactive">-->
        <!--      <label class="switch">-->
        <!--        <input type="checkbox" name="wingo30" value="active" onchange="this.form.submit()" <?= $wingo30status === 'active' ? 'checked' : '' ?>>-->
        <!--        <span class="slider round"></span>-->
        <!--      </label>-->
        <!--    </form>-->
        <!--    <span class="status"><?= $wingo30status === 'active' ? 'ON' : 'OFF' ?></span>-->
        <!--  </div>-->
        <!--</div>-->



        <!-- Wingo 1min / 3min / 5min Coming Soon Card (simple and compact) -->
        <!--<div class="setting-card">-->
        <!--  <div class="card-left">-->
        <!--    <span class="material-icons icon">schedule</span>-->
        <!--    <div>-->
        <!--      <div class="title">K3, 5D &Trx Same Trend</div>-->
        <!--      <div class="desc">Coming Soon</div>-->
        <!--    </div>-->
        <!--  </div>-->
        <!--  <div class="card-right">-->
        <!--    <span class="status">Coming</span>-->
        <!--  </div>-->
        <!--</div>-->

        <!-- ✅ Card: Allow / Block Recharge (reliable JS submit) -->
        <!--<div class="setting-card">-->
        <!--  <div class="card-left">-->
        <!--    <span class="material-icons icon">payment</span>-->
        <!--    <div>-->
        <!--      <div class="title">Allow Recharge For All Users</div>-->
        <!--      <div class="desc">Toggle to allow or block user recharge. When ON users can deposit; when OFF deposit blocked.</div>-->
        <!--    </div>-->
        <!--  </div>-->
        <!--  <div class="card-right">-->
        <!--    <form method="post" id="allowRechargeForm">-->
        <!-- final value sent to server -->
        <!--      <input type="hidden" name="allow_recharge" id="allowRechargeInput" value="<?= ($allow_recharge == 0) ? 0 : 1 ?>">-->
        <!--      <label class="switch">-->
        <!-- checkbox has no name; JS will set hidden value -->
        <!--        <input type="checkbox" id="allowRechargeCheckbox" <?= ($allow_recharge == 0) ? 'checked' : '' ?>>-->
        <!--        <span class="slider round"></span>-->
        <!--      </label>-->
        <!--    </form>-->
        <!--    <span class="status" id="allowRechargeStatus"><?= ($allow_recharge == 0) ? 'ON' : 'OFF' ?></span>-->
        <!--  </div>-->
        <!--</div>                -->


        <!-- / Content -->

        <?php require_once("footer.php"); ?>
        <!-- / Footer -->

        <div class="content-backdrop fade"></div>
    </div>
    <!-- Content wrapper -->
    </div>
    <!-- / Layout page -->
    </div>

    <!-- Overlay -->
    <div class="layout-overlay layout-menu-toggle"></div>

    <!-- Drag Target Area To SlideIn Menu On Small Screens -->
    <div class="drag-target"></div>
    </div>
    <!-- / Layout wrapper -->

    <script>
        (function() {
            var chk = document.getElementById('allowRechargeCheckbox');
            var hid = document.getElementById('allowRechargeInput');
            var status = document.getElementById('allowRechargeStatus');
            chk.addEventListener('change', function() {
                // when checked -> admin wants "ON" behaviour: we follow your existing DB semantics:
                // checked => store 0 (ON), unchecked => store 1 (OFF)
                hid.value = this.checked ? 0 : 1;
                status.innerText = this.checked ? 'ON' : 'OFF';
                // submit the form
                document.getElementById('allowRechargeForm').submit();
            });
        })();
    </script>

    <!---->
    <!-- Add Chart.js library in the head section -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- In your JavaScript section, add this to initialize the charts -->
    <script>
        // Initialize charts when the page loads
        document.addEventListener('DOMContentLoaded', function() {
            initializeCharts();
        });

        function initializeCharts() {
            // Total Profit/Loss Chart - Smaller Configuration
            const profitLossCtx = document.getElementById('totalProfitLossChart');
            if (profitLossCtx) {
                new Chart(profitLossCtx, {
                    type: 'line',
                    data: {
                        labels: <?= json_encode($profitLabels) ?>,
                        datasets: [{
                            label: 'Profit/Loss',
                            data: <?= json_encode($profitLossData) ?>,
                            borderColor: '#4e73df',
                            backgroundColor: 'rgba(78, 115, 223, 0.1)',
                            borderWidth: 2, // Reduced from 3
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: '#4e73df',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 1, // Reduced from 2
                            pointRadius: 3, // Reduced from 5
                            pointHoverRadius: 5 // Reduced from 7
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false, // Important for custom sizing
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const value = context.parsed.y;
                                        const sign = value >= 0 ? 'Profit' : 'Loss';
                                        return `${sign}: ₹${Math.abs(value).toLocaleString('en-IN')}`;
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return '₹' + value.toLocaleString('en-IN');
                                    },
                                    font: {
                                        size: 10 // Smaller font
                                    }
                                },
                                grid: {
                                    color: 'rgba(0,0,0,0.1)'
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    font: {
                                        size: 10 // Smaller font
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // Today's Performance Chart - Smaller Configuration
            const todayCtx = document.getElementById('todayPerformanceChart');
            if (todayCtx) {
                new Chart(todayCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Profit', 'Loss', 'Commission'],
                        datasets: [{
                            data: [
                                <?= max(0, $todayProfit) ?>,
                                <?= max(0, -$todayProfit) ?>,
                                <?= $todayCommission ?>
                            ],
                            backgroundColor: ['#1cc88a', '#e74a3b', '#f6c23e'],
                            hoverBackgroundColor: ['#17a673', '#d52a1e', '#dda20a'],
                            borderWidth: 2, // Reduced from 3
                            borderColor: '#fff',
                            hoverOffset: 10 // Reduced from 15
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false, // Important for custom sizing
                        cutout: '50%', // Reduced from 60%
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 15, // Reduced from 20
                                    usePointStyle: true,
                                    font: {
                                        size: 10 // Smaller font
                                    }
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.label + ': ₹' + context.parsed.toLocaleString('en-IN');
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // Deposit vs Withdrawal Chart - Smaller Configuration
            const depositWithdrawalCtx = document.getElementById('depositWithdrawalChart');
            if (depositWithdrawalCtx) {
                new Chart(depositWithdrawalCtx, {
                    type: 'bar',
                    data: {
                        labels: <?= json_encode($depositLabels) ?>,
                        datasets: [{
                            label: 'Deposit',
                            data: <?= json_encode($depositData) ?>,
                            backgroundColor: '#1cc88a',
                            borderColor: '#1cc88a',
                            borderWidth: 1,
                            borderRadius: 3, // Reduced from 5
                            barPercentage: 0.5 // Reduced from 0.6
                        }, {
                            label: 'Withdrawal',
                            data: <?= json_encode($withdrawalData) ?>,
                            backgroundColor: '#e74a3b',
                            borderColor: '#e74a3b',
                            borderWidth: 1,
                            borderRadius: 3, // Reduced from 5
                            barPercentage: 0.5 // Reduced from 0.6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false, // Important for custom sizing
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return '₹' + value.toLocaleString('en-IN');
                                    },
                                    font: {
                                        size: 10 // Smaller font
                                    }
                                },
                                grid: {
                                    color: 'rgba(0,0,0,0.1)'
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    font: {
                                        size: 10 // Smaller font
                                    }
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                labels: {
                                    font: {
                                        size: 10 // Smaller font
                                    }
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.dataset.label + ': ₹' + context.parsed.y.toLocaleString('en-IN');
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // User Growth Chart - Smaller Configuration
            const userGrowthCtx = document.getElementById('userGrowthChart');
            if (userGrowthCtx) {
                new Chart(userGrowthCtx, {
                    type: 'line',
                    data: {
                        labels: <?= json_encode($userGrowthLabels) ?>,
                        datasets: [{
                            label: 'New Users',
                            data: <?= json_encode($userGrowthData) ?>,
                            borderColor: '#f6c23e',
                            backgroundColor: 'rgba(246, 194, 62, 0.1)',
                            borderWidth: 2, // Reduced from 3
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: '#f6c23e',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 1, // Reduced from 2
                            pointRadius: 3, // Reduced from 5
                            pointHoverRadius: 5 // Reduced from 7
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false, // Important for custom sizing
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0,
                                    font: {
                                        size: 10 // Smaller font
                                    }
                                },
                                grid: {
                                    color: 'rgba(0,0,0,0.1)'
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    font: {
                                        size: 10 // Smaller font
                                    }
                                }
                            }
                        }
                    }
                });
            }
        }
    </script>
    <!---->

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

    <!-- Page JS -->
    <script src="assets/js/app-user-list.js"></script>

</body>

</html>
