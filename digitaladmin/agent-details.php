<?php
session_start();
if (empty($_SESSION['unohs'])) {
    header("location: api/login.php?msg=unauthorized");
}
include("api/conn.php");
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

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
    $previousMonth = (new DateTime())->modify('first day of last month')->format('F Y');


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
?>

<!doctype html>

<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr"
    data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template" data-style="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

    <title>Agent Details</title>

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
                                                    <a href="agent-monthly-details.php?userid=<?= $userid?>" class="btn btn-primary">Monthly Data</a>
                                                </li>
                                                
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card">
                            <div class="table-responsive text-nowrap">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h4 class="m-5">Total Recharge</h4>
                                    <form action="" method="get"
                                        class="d-flex gap-3 align-items-center px-5">
                                        <input type="date" class="form-control" id="date" name="date"
                                            value="<?= $date; ?>" max="<?= date('Y-m-d'); ?>">
                                        <input type="hidden" name="userid" value="<?= $userid; ?>" />
                                        <button type="submit" class="btn btn-primary">Search</button>
                                    </form>
                                </div>
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Level 1 <a href="agent-total-recharge.php?userid=<?= $userid?>&date=<?= $date?>&level=1"  class="update-person" style="color:#0E0E44; font-size:16px;" data-toggle="tooltip" title="User Deatil"><i class="ri-information-2-line ri-22px text-primary"></i></a></th>
                                            <th>Level 2 <a href="agent-total-recharge.php?userid=<?= $userid?>&date=<?= $date?>&level=2"  class="update-person" style="color:#0E0E44; font-size:16px;" data-toggle="tooltip" title="User Deatil"><i class="ri-information-2-line ri-22px text-primary"></i></a></th>
                                            <th>Level 3 <a href="agent-total-recharge.php?userid=<?= $userid?>&date=<?= $date?>&level=3"  class="update-person" style="color:#0E0E44; font-size:16px;" data-toggle="tooltip" title="User Deatil"><i class="ri-information-2-line ri-22px text-primary"></i></a></th>
                                            <th>Level 4 <a href="agent-total-recharge.php?userid=<?= $userid?>&date=<?= $date?>&level=4"  class="update-person" style="color:#0E0E44; font-size:16px;" data-toggle="tooltip" title="User Deatil"><i class="ri-information-2-line ri-22px text-primary"></i></a></th>
                                            <th>Level 5 <a href="agent-total-recharge.php?userid=<?= $userid?>&date=<?= $date?>&level=5"  class="update-person" style="color:#0E0E44; font-size:16px;" data-toggle="tooltip" title="User Deatil"><i class="ri-information-2-line ri-22px text-primary"></i></a></th>
                                            <th>Level 6 <a href="agent-total-recharge.php?userid=<?= $userid?>&date=<?= $date?>&level=6"  class="update-person" style="color:#0E0E44; font-size:16px;" data-toggle="tooltip" title="User Deatil"><i class="ri-information-2-line ri-22px text-primary"></i></a></th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody class="table-border-bottom-0" id="totalRechargeTable">
                                        <tr>
                                            <td colspan="7" class="text-center">Loading...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card mt-5">
                            <div class="table-responsive text-nowrap">
                                <h4 class="m-5">Total Withdrawal</h4>
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Level 1 <a href="agent-total-withdraw.php?userid=<?= $userid?>&date=<?= $date?>&level=1"  class="update-person" style="color:#0E0E44; font-size:16px;" data-toggle="tooltip" title="User Deatil"><i class="ri-information-2-line ri-22px text-primary"></i></a></th>
                                            <th>Level 2 <a href="agent-total-withdraw.php?userid=<?= $userid?>&date=<?= $date?>&level=2"  class="update-person" style="color:#0E0E44; font-size:16px;" data-toggle="tooltip" title="User Deatil"><i class="ri-information-2-line ri-22px text-primary"></i></a></th>
                                            <th>Level 3 <a href="agent-total-withdraw.php?userid=<?= $userid?>&date=<?= $date?>&level=3"  class="update-person" style="color:#0E0E44; font-size:16px;" data-toggle="tooltip" title="User Deatil"><i class="ri-information-2-line ri-22px text-primary"></i></a></th>
                                            <th>Level 4 <a href="agent-total-withdraw.php?userid=<?= $userid?>&date=<?= $date?>&level=4"  class="update-person" style="color:#0E0E44; font-size:16px;" data-toggle="tooltip" title="User Deatil"><i class="ri-information-2-line ri-22px text-primary"></i></a></th>
                                            <th>Level 5 <a href="agent-total-withdraw.php?userid=<?= $userid?>&date=<?= $date?>&level=5"  class="update-person" style="color:#0E0E44; font-size:16px;" data-toggle="tooltip" title="User Deatil"><i class="ri-information-2-line ri-22px text-primary"></i></a></th>
                                            <th>Level 6 <a href="agent-total-withdraw.php?userid=<?= $userid?>&date=<?= $date?>&level=6"  class="update-person" style="color:#0E0E44; font-size:16px;" data-toggle="tooltip" title="User Deatil"><i class="ri-information-2-line ri-22px text-primary"></i></a></th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody class="table-border-bottom-0" id="totalWithdrawTable">
                                        <tr>
                                            <td colspan="7" class="text-center">Loading...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card mt-5">
                            <div class="table-responsive text-nowrap">
                                <h4 class="m-5">Total Active Users</h4>
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Level 1 <a href="agent-total-activeusers.php?userid=<?= $userid?>&date=<?= $date?>&level=1"  class="update-person" style="color:#0E0E44; font-size:16px;" data-toggle="tooltip" title="User Deatil"><i class="ri-information-2-line ri-22px text-primary"></i></a></th>
                                            <th>Level 2 <a href="agent-total-activeusers.php?userid=<?= $userid?>&date=<?= $date?>&level=2"  class="update-person" style="color:#0E0E44; font-size:16px;" data-toggle="tooltip" title="User Deatil"><i class="ri-information-2-line ri-22px text-primary"></i></a></th>
                                            <th>Level 3 <a href="agent-total-activeusers.php?userid=<?= $userid?>&date=<?= $date?>&level=3"  class="update-person" style="color:#0E0E44; font-size:16px;" data-toggle="tooltip" title="User Deatil"><i class="ri-information-2-line ri-22px text-primary"></i></a></th>
                                            <th>Level 4 <a href="agent-total-activeusers.php?userid=<?= $userid?>&date=<?= $date?>&level=4"  class="update-person" style="color:#0E0E44; font-size:16px;" data-toggle="tooltip" title="User Deatil"><i class="ri-information-2-line ri-22px text-primary"></i></a></th>
                                            <th>Level 5 <a href="agent-total-activeusers.php?userid=<?= $userid?>&date=<?= $date?>&level=5"  class="update-person" style="color:#0E0E44; font-size:16px;" data-toggle="tooltip" title="User Deatil"><i class="ri-information-2-line ri-22px text-primary"></i></a></th>
                                            <th>Level 6 <a href="agent-total-activeusers.php?userid=<?= $userid?>&date=<?= $date?>&level=6"  class="update-person" style="color:#0E0E44; font-size:16px;" data-toggle="tooltip" title="User Deatil"><i class="ri-information-2-line ri-22px text-primary"></i></a></th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody class="table-border-bottom-0" id="activeUsersTable">
                                        <tr>
                                            <td colspan="7" class="text-center">Loading...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card mt-5">
                            <div class="table-responsive text-nowrap">
                                <h4 class="m-5">Total Members</h4>
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Level 1 <a href="agent-total-members.php?userid=<?= $userid?>&date=<?= $date?>&level=1"  class="update-person" style="color:#0E0E44; font-size:16px;" data-toggle="tooltip" title="User Deatil"><i class="ri-information-2-line ri-22px text-primary"></i></a></th>
                                            <th>Level 2 <a href="agent-total-members.php?userid=<?= $userid?>&date=<?= $date?>&level=2"  class="update-person" style="color:#0E0E44; font-size:16px;" data-toggle="tooltip" title="User Deatil"><i class="ri-information-2-line ri-22px text-primary"></i></a></th>
                                            <th>Level 3 <a href="agent-total-members.php?userid=<?= $userid?>&date=<?= $date?>&level=3"  class="update-person" style="color:#0E0E44; font-size:16px;" data-toggle="tooltip" title="User Deatil"><i class="ri-information-2-line ri-22px text-primary"></i></a></th>
                                            <th>Level 4 <a href="agent-total-members.php?userid=<?= $userid?>&date=<?= $date?>&level=4"  class="update-person" style="color:#0E0E44; font-size:16px;" data-toggle="tooltip" title="User Deatil"><i class="ri-information-2-line ri-22px text-primary"></i></a></th>
                                            <th>Level 5 <a href="agent-total-members.php?userid=<?= $userid?>&date=<?= $date?>&level=5"  class="update-person" style="color:#0E0E44; font-size:16px;" data-toggle="tooltip" title="User Deatil"><i class="ri-information-2-line ri-22px text-primary"></i></a></th>
                                            <th>Level 6 <a href="agent-total-members.php?userid=<?= $userid?>&date=<?= $date?>&level=6"  class="update-person" style="color:#0E0E44; font-size:16px;" data-toggle="tooltip" title="User Deatil"><i class="ri-information-2-line ri-22px text-primary"></i></a></th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody class="table-border-bottom-0" id="totalMembersTable">
                                        <tr>
                                            <td colspan="7" class="text-center">Loading...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card mt-5">
                            <div class="table-responsive text-nowrap">
                                <h4 class="m-5">Total First Recharge</h4>
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Level 1 <a href="agent-total-first-recharge.php?userid=<?= $userid?>&date=<?= $date?>&level=1"  class="update-person" style="color:#0E0E44; font-size:16px;" data-toggle="tooltip" title="User Deatil"><i class="ri-information-2-line ri-22px text-primary"></i></a></th>
                                            <th>Level 2 <a href="agent-total-first-recharge.php?userid=<?= $userid?>&date=<?= $date?>&level=2"  class="update-person" style="color:#0E0E44; font-size:16px;" data-toggle="tooltip" title="User Deatil"><i class="ri-information-2-line ri-22px text-primary"></i></a></th>
                                            <th>Level 3 <a href="agent-total-first-recharge.php?userid=<?= $userid?>&date=<?= $date?>&level=3"  class="update-person" style="color:#0E0E44; font-size:16px;" data-toggle="tooltip" title="User Deatil"><i class="ri-information-2-line ri-22px text-primary"></i></a></th>
                                            <th>Level 4 <a href="agent-total-first-recharge.php?userid=<?= $userid?>&date=<?= $date?>&level=4"  class="update-person" style="color:#0E0E44; font-size:16px;" data-toggle="tooltip" title="User Deatil"><i class="ri-information-2-line ri-22px text-primary"></i></a></th>
                                            <th>Level 5 <a href="agent-total-first-recharge.php?userid=<?= $userid?>&date=<?= $date?>&level=5"  class="update-person" style="color:#0E0E44; font-size:16px;" data-toggle="tooltip" title="User Deatil"><i class="ri-information-2-line ri-22px text-primary"></i></a></th>
                                            <th>Level 6 <a href="agent-total-first-recharge.php?userid=<?= $userid?>&date=<?= $date?>&level=6"  class="update-person" style="color:#0E0E44; font-size:16px;" data-toggle="tooltip" title="User Deatil"><i class="ri-information-2-line ri-22px text-primary"></i></a></th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody class="table-border-bottom-0" id="firstRechargeTable">
                                        <tr>
                                            <td colspan="7" class="text-center">Loading...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card mt-5">
                            <div class="table-responsive text-nowrap">
                                <h4 class="m-5">Total Bets</h4>
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Level 1 <a href="agent-total-bets.php?userid=<?= $userid?>&date=<?= $date?>&level=1"  class="update-person" style="color:#0E0E44; font-size:16px;" data-toggle="tooltip" title="User Deatil"><i class="ri-information-2-line ri-22px text-primary"></i></a></th>
                                            <th>Level 2 <a href="agent-total-bets.php?userid=<?= $userid?>&date=<?= $date?>&level=2"  class="update-person" style="color:#0E0E44; font-size:16px;" data-toggle="tooltip" title="User Deatil"><i class="ri-information-2-line ri-22px text-primary"></i></a></th>
                                            <th>Level 3 <a href="agent-total-bets.php?userid=<?= $userid?>&date=<?= $date?>&level=3"  class="update-person" style="color:#0E0E44; font-size:16px;" data-toggle="tooltip" title="User Deatil"><i class="ri-information-2-line ri-22px text-primary"></i></a></th>
                                            <th>Level 4 <a href="agent-total-bets.php?userid=<?= $userid?>&date=<?= $date?>&level=4"  class="update-person" style="color:#0E0E44; font-size:16px;" data-toggle="tooltip" title="User Deatil"><i class="ri-information-2-line ri-22px text-primary"></i></a></th>
                                            <th>Level 5 <a href="agent-total-bets.php?userid=<?= $userid?>&date=<?= $date?>&level=5"  class="update-person" style="color:#0E0E44; font-size:16px;" data-toggle="tooltip" title="User Deatil"><i class="ri-information-2-line ri-22px text-primary"></i></a></th>
                                            <th>Level 6 <a href="agent-total-bets.php?userid=<?= $userid?>&date=<?= $date?>&level=6"  class="update-person" style="color:#0E0E44; font-size:16px;" data-toggle="tooltip" title="User Deatil"><i class="ri-information-2-line ri-22px text-primary"></i></a></th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody class="table-border-bottom-0" id="totalBetsTable">
                                        <tr>
                                            <td colspan="7" class="text-center">Loading...</td>
                                        </tr>
                                    </tbody>
                                </table>
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
    <script>
        $(document).ready(function () {
            fetchActiveUsers();
            fetchTotalMembers();
            fetchFirstRecharge();
            fetchTotalRecharge();
            fetchTotalWithdraw();
            fetchTotalBets();
        });

        function fetchActiveUsers() {
            let ownCode = "<?= $ownCode ?>"; // Ensure it's a string
            let date = "<?= $date ?>"; // Ensure it's properly formatted
            $.ajax({
                url: `api/agent-list/fetch-active-users.php`,
                type: "GET",
                data: { ownCode: ownCode, date: date },
                dataType: "json",
                success: function (data) {
                    let tableRow = `
                        <tr>
                            <td>${(parseInt(data.level1_active_users) || 0).toLocaleString()}</td>
                            <td>${(parseInt(data.level2_active_users) || 0).toLocaleString()}</td>
                            <td>${(parseInt(data.level3_active_users) || 0).toLocaleString()}</td>
                            <td>${(parseInt(data.level4_active_users) || 0).toLocaleString()}</td>
                            <td>${(parseInt(data.level5_active_users) || 0).toLocaleString()}</td>
                            <td>${(parseInt(data.level6_active_users) || 0).toLocaleString()}</td>
                            <td>${(
                            (parseInt(data.level1_active_users) || 0) +
                            (parseInt(data.level2_active_users) || 0) +
                            (parseInt(data.level3_active_users) || 0) +
                            (parseInt(data.level4_active_users) || 0) +
                            (parseInt(data.level5_active_users) || 0) +
                            (parseInt(data.level6_active_users) || 0)
                        ).toLocaleString()}</td>
                        </tr>
                    `;

                    $("#activeUsersTable").html(tableRow);
                },
                error: function (xhr, status, error) {
                    console.error("Error fetching data:", error);
                    $("#activeUsersTable").html(`<tr><td colspan="7" class="text-center text-danger">Error loading data</td></tr>`);
                }
            });
        }
        function fetchTotalMembers() {
            let ownCode = "<?= $ownCode ?>"; // Ensure it's a string
            let date = "<?= $date ?>"; // Ensure it's properly formatted
            $.ajax({
                url: `api/agent-list/fetch-total-members.php`,
                type: "GET",
                data: { ownCode: ownCode, date: date },
                dataType: "json",
                success: function (data) {
                    let tableRow = `
                        <tr>
                            <td>${(parseInt(data.level1_count) || 0).toLocaleString()}</td>
                            <td>${(parseInt(data.level2_count) || 0).toLocaleString()}</td>
                            <td>${(parseInt(data.level3_count) || 0).toLocaleString()}</td>
                            <td>${(parseInt(data.level4_count) || 0).toLocaleString()}</td>
                            <td>${(parseInt(data.level5_count) || 0).toLocaleString()}</td>
                            <td>${(parseInt(data.level6_count) || 0).toLocaleString()}</td>
                            <td>${(
                            (parseInt(data.level1_count) || 0) +
                            (parseInt(data.level2_count) || 0) +
                            (parseInt(data.level3_count) || 0) +
                            (parseInt(data.level4_count) || 0) +
                            (parseInt(data.level5_count) || 0) +
                            (parseInt(data.level6_count) || 0)
                        ).toLocaleString()}</td>
                        </tr>
                    `;

                    $("#totalMembersTable").html(tableRow);
                },
                error: function (xhr, status, error) {
                    console.error("Error fetching data:", error);
                    $("#totalMembersTable").html(`<tr><td colspan="7" class="text-center text-danger">Error loading data</td></tr>`);
                }
            });
        }
        function fetchFirstRecharge() {
            let ownCode = "<?= $ownCode ?>"; // Ensure it's a string
            let date = "<?= $date ?>"; // Ensure it's properly formatted
            $.ajax({
                url: `api/agent-list/fetch-first-recharge.php`,
                type: "GET",
                data: { ownCode: ownCode, date: date },
                dataType: "json",
                success: function (data) {
                    let tableRow = `
                        <tr>
                            <td>₹ ${(parseFloat(data.level1_total) || 0).toLocaleString()}</td>
                            <td>₹ ${(parseFloat(data.level2_total) || 0).toLocaleString()}</td>
                            <td>₹ ${(parseFloat(data.level3_total) || 0).toLocaleString()}</td>
                            <td>₹ ${(parseFloat(data.level4_total) || 0).toLocaleString()}</td>
                            <td>₹ ${(parseFloat(data.level5_total) || 0).toLocaleString()}</td>
                            <td>₹ ${(parseFloat(data.level6_total) || 0).toLocaleString()}</td>
                            <td>₹ ${(
                            (parseFloat(data.level1_total) || 0) +
                            (parseFloat(data.level2_total) || 0) +
                            (parseFloat(data.level3_total) || 0) +
                            (parseFloat(data.level4_total) || 0) +
                            (parseFloat(data.level5_total) || 0) +
                            (parseFloat(data.level6_total) || 0)
                        ).toLocaleString()}</td>
                        </tr>
                    `;

                    $("#firstRechargeTable").html(tableRow);
                },
                error: function (xhr, status, error) {
                    console.error("Error fetching data:", error);
                    $("#firstRechargeTable").html(`<tr><td colspan="7" class="text-center text-danger">Error loading data</td></tr>`);
                }
            });
        }
        function fetchTotalRecharge() {
            let ownCode = "<?= $ownCode ?>"; // Ensure it's a string
            let date = "<?= $date ?>"; // Ensure it's properly formatted
            $.ajax({
                url: `api/agent-list/fetch-total-recharge.php`,
                type: "GET",
                data: { ownCode: ownCode, date: date },
                dataType: "json",
                success: function (data) {
                    let tableRow = `
                        <tr>
                            <td>₹ ${(parseFloat(data.level1_total) || 0).toLocaleString()}</td>
                            <td>₹ ${(parseFloat(data.level2_total) || 0).toLocaleString()}</td>
                            <td>₹ ${(parseFloat(data.level3_total) || 0).toLocaleString()}</td>
                            <td>₹ ${(parseFloat(data.level4_total) || 0).toLocaleString()}</td>
                            <td>₹ ${(parseFloat(data.level5_total) || 0).toLocaleString()}</td>
                            <td>₹ ${(parseFloat(data.level6_total) || 0).toLocaleString()}</td>
                            <td>₹ ${(
                            (parseFloat(data.level1_total) || 0) +
                            (parseFloat(data.level2_total) || 0) +
                            (parseFloat(data.level3_total) || 0) +
                            (parseFloat(data.level4_total) || 0) +
                            (parseFloat(data.level5_total) || 0) +
                            (parseFloat(data.level6_total) || 0)
                        ).toLocaleString()}</td>
                        </tr>
                    `;

                    $("#totalRechargeTable").html(tableRow);
                },
                error: function (xhr, status, error) {
                    console.error("Error fetching data:", error);
                    $("#totalRechargeTable").html(`<tr><td colspan="7" class="text-center text-danger">Error loading data</td></tr>`);
                }
            });
        }
        function fetchTotalWithdraw() {
            let ownCode = "<?= $ownCode ?>"; // Ensure it's a string
            let date = "<?= $date ?>"; // Ensure it's properly formatted
            $.ajax({
                url: `api/agent-list/fetch-total-withdraw.php`,
                type: "GET",
                data: { ownCode: ownCode, date: date },
                dataType: "json",
                success: function (data) {
                    let tableRow = `
                        <tr>
                            <td>₹ ${(parseFloat(data.level1_total) || 0).toLocaleString()}</td>
                            <td>₹ ${(parseFloat(data.level2_total) || 0).toLocaleString()}</td>
                            <td>₹ ${(parseFloat(data.level3_total) || 0).toLocaleString()}</td>
                            <td>₹ ${(parseFloat(data.level4_total) || 0).toLocaleString()}</td>
                            <td>₹ ${(parseFloat(data.level5_total) || 0).toLocaleString()}</td>
                            <td>₹ ${(parseFloat(data.level6_total) || 0).toLocaleString()}</td>
                            <td>₹ ${(
                            (parseFloat(data.level1_total) || 0) +
                            (parseFloat(data.level2_total) || 0) +
                            (parseFloat(data.level3_total) || 0) +
                            (parseFloat(data.level4_total) || 0) +
                            (parseFloat(data.level5_total) || 0) +
                            (parseFloat(data.level6_total) || 0)
                        ).toLocaleString()}</td>
                        </tr>
                    `;

                    $("#totalWithdrawTable").html(tableRow);
                },
                error: function (xhr, status, error) {
                    console.error("Error fetching data:", error);
                    $("#totalWithdrawTable").html(`<tr><td colspan="7" class="text-center text-danger">Error loading data</td></tr>`);
                }
            });
        }
        function fetchTotalBets() {
            let ownCode = "<?= $ownCode ?>"; // Ensure it's a string
            let date = "<?= $date ?>"; // Ensure it's properly formatted
            $.ajax({
                url: `api/agent-list/fetch-total-bets.php`,
                type: "GET",
                data: { ownCode: ownCode, date: date },
                dataType: "json",
                success: function (data) {
                    let tableRow = `
                        <tr>
                            <td>₹ ${(parseFloat(data.level1_total_bet) || 0).toLocaleString()}</td>
                            <td>₹ ${(parseFloat(data.level2_total_bet) || 0).toLocaleString()}</td>
                            <td>₹ ${(parseFloat(data.level3_total_bet) || 0).toLocaleString()}</td>
                            <td>₹ ${(parseFloat(data.level4_total_bet) || 0).toLocaleString()}</td>
                            <td>₹ ${(parseFloat(data.level5_total_bet) || 0).toLocaleString()}</td>
                            <td>₹ ${(parseFloat(data.level6_total_bet) || 0).toLocaleString()}</td>
                            <td>₹ ${(
                            (parseFloat(data.level1_total_bet) || 0) +
                            (parseFloat(data.level2_total_bet) || 0) +
                            (parseFloat(data.level3_total_bet) || 0) +
                            (parseFloat(data.level4_total_bet) || 0) +
                            (parseFloat(data.level5_total_bet) || 0) +
                            (parseFloat(data.level6_total_bet) || 0)
                        ).toLocaleString()}</td>
                        </tr>
                    `;

                    $("#totalBetsTable").html(tableRow);
                },
                error: function (xhr, status, error) {
                    console.error("Error fetching data:", error);
                    $("#totalBetsTable").html(`<tr><td colspan="7" class="text-center text-danger">Error loading data</td></tr>`);
                }
            });
        }

    </script>
</body>

</html>