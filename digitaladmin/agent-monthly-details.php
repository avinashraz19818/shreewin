<?php
session_start();
if (empty($_SESSION['unohs'])) {
    header("location: api/login.php?msg=unauthorized");
}
include("api/conn.php");
$startDate = isset($_GET['start_date'])?$_GET['start_date']:date("Y-m-01", strtotime("first day of last month"));
$endDate = isset($_GET['last_date'])?$_GET['last_date']:date("Y-m-t", strtotime("last day of last month"));
$level=isset($_GET['level'])?$_GET['level']:0;

if (isset($_GET['userid'])) {
    $userid = $_GET['userid'];
    $sql = "SELECT ss.*, sk.motta 
        FROM shonu_subjects ss
        LEFT JOIN shonu_kaichila sk ON ss.id = sk.balakedara
        WHERE ss.id = '$userid';
        ";
    $result = $conn->query($sql);
    $query = mysqli_query($conn, "
            SELECT SUM(price) AS total_bonus
            FROM (
                SELECT price FROM hodike_balakedara WHERE serial='Imitator' AND userkani = '$userid'
                UNION ALL
                SELECT price FROM agent_red_envelope_recharge_table WHERE userkani = '$userid'
                UNION ALL
                SELECT price FROM recharge_gift_table WHERE userkani = '$userid'
                UNION ALL
                SELECT price FROM bonus_recharge_table WHERE userkani = '$userid'
                UNION ALL
                SELECT price FROM first_full_gift_table WHERE userkani = '$userid'
                UNION ALL
                SELECT price FROM invite_bonus_table WHERE userkani = '$userid'
                UNION ALL
                SELECT price FROM card_binding_gift_table WHERE userkani = '$userid'
                UNION ALL
                SELECT price FROM weekly_awards_table WHERE userkani = '$userid'
                UNION ALL
                SELECT price FROM agent_bonus_table WHERE userkani = '$userid'
                UNION ALL
                SELECT price FROM daily_awards_table WHERE userkani = '$userid'
                UNION ALL
                SELECT price FROM new_members_bonus_table WHERE userkani = '$userid'
                UNION ALL
                SELECT price FROM return_awards_table WHERE userkani = '$userid'
            ) AS combined_data
        ");

    $row = mysqli_fetch_assoc($query);
    $total_bonus = number_format($row['total_bonus'], 2);

    
    if ($result->num_rows > 0) {
        $snum = $result->fetch_assoc();
        $ownCode = $snum['owncode'];
    } else {
        header("location: agent-user.php");
        exit();
    }
} else {
    header("location: agent-user.php");
    exit();
}

function depositQuery($level,$ownCode,$startDate,$endDate){
    if((int)$level===0){
        return "SELECT 
            -- Sum of withdrawals for all levels (level 1 to level 6) in the previous month
            (SELECT SUM(h.motta) FROM thevani h 
             JOIN shonu_subjects ss ON h.balakedara = ss.id 
             WHERE (ss.code = '$ownCode' OR ss.code1 = '$ownCode' OR ss.code2 = '$ownCode' 
                    OR ss.code3 = '$ownCode' OR ss.code4 = '$ownCode' OR ss.code5 = '$ownCode') 
             AND h.sthiti = 1
             AND DATE(h.dinankavannuracisi) BETWEEN '$startDate' 
                                             AND '$endDate') AS total_deposit";
    }else if((int)$level===1){
        return "SELECT 
            -- Sum of withdrawals for all levels (level 1 to level 6) in the previous month
            (SELECT SUM(h.motta) FROM thevani h 
             JOIN shonu_subjects ss ON h.balakedara = ss.id 
             WHERE (ss.code = '$ownCode') 
             AND h.sthiti = 1
             AND DATE(h.dinankavannuracisi) BETWEEN '$startDate' 
                                             AND '$endDate') AS total_deposit";
    }else if((int)$level===2){
        return "SELECT 
            -- Sum of withdrawals for all levels (level 1 to level 6) in the previous month
            (SELECT SUM(h.motta) FROM thevani h 
             JOIN shonu_subjects ss ON h.balakedara = ss.id 
             WHERE (ss.code1 = '$ownCode') 
             AND h.sthiti = 1
             AND DATE(h.dinankavannuracisi) BETWEEN '$startDate' 
                                             AND '$endDate') AS total_deposit";
    }else if((int)$level===3){
        return "SELECT 
            -- Sum of withdrawals for all levels (level 1 to level 6) in the previous month
            (SELECT SUM(h.motta) FROM thevani h 
             JOIN shonu_subjects ss ON h.balakedara = ss.id 
             WHERE (ss.code2 = '$ownCode') 
             AND h.sthiti = 1
             AND DATE(h.dinankavannuracisi) BETWEEN '$startDate' 
                                             AND '$endDate') AS total_deposit";
    }else if((int)$level===4){
        return "SELECT 
            -- Sum of withdrawals for all levels (level 1 to level 6) in the previous month
            (SELECT SUM(h.motta) FROM thevani h 
             JOIN shonu_subjects ss ON h.balakedara = ss.id 
             WHERE (ss.code3 = '$ownCode') 
             AND h.sthiti = 1
             AND DATE(h.dinankavannuracisi) BETWEEN '$startDate' 
                                             AND '$endDate') AS total_deposit";
    }else if((int)$level===5){
        return "SELECT 
            -- Sum of withdrawals for all levels (level 1 to level 6) in the previous month
            (SELECT SUM(h.motta) FROM thevani h 
             JOIN shonu_subjects ss ON h.balakedara = ss.id 
             WHERE (ss.code4 = '$ownCode') 
             AND h.sthiti = 1
             AND DATE(h.dinankavannuracisi) BETWEEN '$startDate' 
                                             AND '$endDate') AS total_deposit";
    }else if((int)$level===6){
        return "SELECT 
            -- Sum of withdrawals for all levels (level 1 to level 6) in the previous month
            (SELECT SUM(h.motta) FROM thevani h 
             JOIN shonu_subjects ss ON h.balakedara = ss.id 
             WHERE (ss.code5 = '$ownCode') 
             AND h.sthiti = 1
             AND DATE(h.dinankavannuracisi) BETWEEN '$startDate' 
                                             AND '$endDate') AS total_deposit";
    }else{
      header("location: agent-user.php");
      exit();
    }
}

function withdrawQuery($level,$ownCode,$startDate,$endDate){
    if((int)$level===0){
        return "SELECT 
            -- Sum of withdrawals for all levels (level 1 to level 6) in the previous month
            (SELECT SUM(h.motta) FROM hintegedukolli h 
             JOIN shonu_subjects ss ON h.balakedara = ss.id 
             WHERE (ss.code = '$ownCode' OR ss.code1 = '$ownCode' OR ss.code2 = '$ownCode' 
                    OR ss.code3 = '$ownCode' OR ss.code4 = '$ownCode' OR ss.code5 = '$ownCode') 
             AND h.sthiti = 1
             AND DATE(h.dinankavannuracisi) BETWEEN '$startDate' 
                                             AND '$endDate') AS total_withdrawal";
    }else if((int)$level===1){
        return "SELECT 
            -- Sum of withdrawals for all levels (level 1 to level 6) in the previous month
            (SELECT SUM(h.motta) FROM hintegedukolli h 
             JOIN shonu_subjects ss ON h.balakedara = ss.id 
             WHERE (ss.code = '$ownCode') 
             AND h.sthiti = 1
             AND DATE(h.dinankavannuracisi) BETWEEN '$startDate' 
                                             AND '$endDate') AS total_withdrawal";
    }else if((int)$level===2){
        return "SELECT 
            -- Sum of withdrawals for all levels (level 1 to level 6) in the previous month
            (SELECT SUM(h.motta) FROM hintegedukolli h 
             JOIN shonu_subjects ss ON h.balakedara = ss.id 
             WHERE (ss.code1 = '$ownCode') 
             AND h.sthiti = 1
             AND DATE(h.dinankavannuracisi) BETWEEN '$startDate' 
                                             AND '$endDate') AS total_withdrawal";
    }else if((int)$level===3){
        return "SELECT 
            -- Sum of withdrawals for all levels (level 1 to level 6) in the previous month
            (SELECT SUM(h.motta) FROM hintegedukolli h 
             JOIN shonu_subjects ss ON h.balakedara = ss.id 
             WHERE (ss.code2 = '$ownCode') 
             AND h.sthiti = 1
             AND DATE(h.dinankavannuracisi) BETWEEN '$startDate' 
                                             AND '$endDate') AS total_withdrawal";
    }else if((int)$level===4){
        return "SELECT 
            -- Sum of withdrawals for all levels (level 1 to level 6) in the previous month
            (SELECT SUM(h.motta) FROM hintegedukolli h 
             JOIN shonu_subjects ss ON h.balakedara = ss.id 
             WHERE (ss.code3 = '$ownCode') 
             AND h.sthiti = 1
             AND DATE(h.dinankavannuracisi) BETWEEN '$startDate' 
                                             AND '$endDate') AS total_withdrawal";
    }else if((int)$level===5){
        return "SELECT 
            -- Sum of withdrawals for all levels (level 1 to level 6) in the previous month
            (SELECT SUM(h.motta) FROM hintegedukolli h 
             JOIN shonu_subjects ss ON h.balakedara = ss.id 
             WHERE (ss.code4 = '$ownCode') 
             AND h.sthiti = 1
             AND DATE(h.dinankavannuracisi) BETWEEN '$startDate' 
                                             AND '$endDate') AS total_withdrawal";
    }else if((int)$level===6){
        return "SELECT 
            -- Sum of withdrawals for all levels (level 1 to level 6) in the previous month
            (SELECT SUM(h.motta) FROM hintegedukolli h 
             JOIN shonu_subjects ss ON h.balakedara = ss.id 
             WHERE (ss.code5 = '$ownCode') 
             AND h.sthiti = 1
             AND DATE(h.dinankavannuracisi) BETWEEN '$startDate' 
                                             AND '$endDate') AS total_withdrawal";
    }else{
      header("location: agent-user.php");
      exit();
    }
}
?>

<!doctype html>

<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr"
    data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template" data-style="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

    <title>Agent Monthly Details</title>

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
                    <!-- Content -->

                    <div class="container-xxl flex-grow-1 container-p-y">

                        <!-- Users List Table -->
                        <div class="row mb-5">
                            <!-- User Sidebar -->
                            <div class="col-xl-6 col-lg-6 col-md-6 order-1 order-md-0">
                                <div class="card mb-6">
                                    <div class="card-body pt-12">
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
                                                <li class="mb-2">
                                                    <span class="fw-medium text-heading me-2">User Balance:</span>
                                                    <span><b>₹ <?= number_format($snum['motta'], 2); ?></b></span>
                                                </li>
                                                <li class="mb-2">
                                                    <span class="fw-medium text-heading me-2">Total Bonus:</span>
                                                    <span><b>₹ <?= $total_bonus ?></b></span>
                                                    <!--<span>Under work</span>-->
                                                </li>
                                                <li class="mb-2">
                                                    <a href="agent-details.php?userid=<?=$userid?>" class="btn btn-primary">Daily Data</a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-6 col-lg-6 col-md-6 order-1 order-md-0">
                                <div class="card mb-5">
                                    <div class="card-body">
                                        <form id="formChangePassword" action="" method="get">
                                            <div class="row gx-5">
                                                <div class="mb-3 col-12 col-sm-12 form-password-toggle">
                                                    <div class="input-group input-group-merge">
                                                        <div class="form-floating form-floating-outline">
                                                            <select class="form-select" id="level" name="level">
                                                                <option value="0" <?= $level == "0" ? "selected" : ""; ?>>ALL</option>
                                                                <option value="1" <?= $level == "1" ? "selected" : ""; ?>>Level 1</option>
                                                                <option value="2" <?= $level == "2" ? "selected" : ""; ?>>Level 2</option>
                                                                <option value="3" <?= $level == "3" ? "selected" : ""; ?>>Level 3</option>
                                                                <option value="4" <?= $level == "4" ? "selected" : ""; ?>>Level 4</option>
                                                                <option value="5" <?= $level == "5" ? "selected" : ""; ?>>Level 5</option>
                                                                <option value="6" <?= $level == "6" ? "selected" : ""; ?>>Level 6</option>
                                                            </select>
                                                            <label for="level">Level</label>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="mb-3 col-lg-6 col-sm-12 form-password-toggle">
                                                    <div class="input-group input-group-merge">
                                                        <div class="form-floating form-floating-outline">
                                                            <input class="form-control" type="date" id="start_date"
                                                                name="start_date" value="<?=$startDate?>" max="<?= date('Y-m-d'); ?>"/>
                                                            <label for="start_date">From</label>
                                                            
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="mb-3 col-lg-6 col-sm-12 form-password-toggle">
                                                    <div class="input-group input-group-merge">
                                                        <div class="form-floating form-floating-outline">
                                                            <input class="form-control" type="date" id="last_date"
                                                                name="last_date" value="<?=$endDate?>" max="<?= date('Y-m-d'); ?>"/>
                                                            <label for="last_date">To</label>
                                                        </div>
                                                    </div>
                                                </div>
                                                <input type="hidden" value="<?=$userid?>" name="userid"/>
                                                <div>
                                                    <button type="submit" class="btn btn-primary me-2">Search</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                <div class="card mb-5">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div class="me-1">
                                                <p class="text-heading mb-1">Total Recharge</p>
                                                <div class="d-flex align-items-center">
                                                    <h4 class="mb-1 me-2 text-success">
                                                        ₹<?php
                                                        $query = mysqli_query($conn, depositQuery($level,$ownCode,$startDate,$endDate));
                                                        $row = mysqli_fetch_assoc($query);
                                                        $monthly_total_deposits = number_format($row['total_deposit'], 2);
                                                        $monthly_total_deposits_float = (float)$row['total_deposit'];
                                                        echo $monthly_total_deposits;
                                                        ?>
                                                    </h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card mb-5">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div class="me-1">
                                                <p class="text-heading mb-1">Total Withdrawal</p>
                                                <div class="d-flex align-items-center">
                                                    <h4 class="mb-1 me-2 text-success">
                                                        ₹<?php
                                                        $query = mysqli_query($conn, withdrawQuery($level,$ownCode,$startDate,$endDate));

                                                        $row = mysqli_fetch_assoc($query);
                                                        $monthly_total_withdrawal = number_format($row['total_withdrawal'], 2);
                                                        $monthly_total_withdrawal_float = (float)$row['total_withdrawal'];
                                                        $monthly_pl=$monthly_total_deposits_float-$monthly_total_withdrawal_float;
                                                        echo $monthly_total_withdrawal;
                                                        ?>
                                                    </h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card mb-5">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div class="me-1">
                                                <p class="text-heading mb-1">P/L report</p>
                                                <div class="d-flex align-items-center">
                                                    <h4 class="mb-1 me-2 text-<?= ($monthly_pl<0)?'danger':'success' ?>">
                                                        ₹<?= number_format($monthly_pl,2) ?>
                                                    </h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- / Content -->

                    <!-- Footer -->
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
    <script src="assets/vendor/libs/jquery/jquery.js"></script>
    <script src="assets/vendor/libs/popper/popper.js"></script>
    <script src="assets/vendor/js/bootstrap.js"></script>
    <script src="assets/vendor/libs/node-waves/node-waves.js"></script>
    <script src="assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="assets/vendor/libs/hammer/hammer.js"></script>
    <script src="assets/vendor/libs/i18n/i18n.js"></script>
    <script src="assets/vendor/libs/typeahead-js/typeahead.js"></script>
    <script src="assets/vendor/js/menu.js"></script>
    <script src="assets/vendor/libs/moment/moment.js"></script>
    <script src="assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js"></script>
    <script src="assets/vendor/libs/select2/select2.js"></script>
    <script src="assets/vendor/libs/@form-validation/popular.js"></script>
    <script src="assets/vendor/libs/@form-validation/bootstrap5.js"></script>
    <script src="assets/vendor/libs/@form-validation/auto-focus.js"></script>
    <script src="assets/vendor/libs/cleavejs/cleave.js"></script>
    <script src="assets/vendor/libs/cleavejs/cleave-phone.js"></script>
    <script src="assets/js/main.js"></script>
</body>

</html>