<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'error_log.txt');

// Start secure session
session_start();

if(empty($_SESSION['unohs'])){
    header("location: api/login.php?msg=unauthorized");
    exit();
}

include ("api/conn.php");

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

// Inactive Users (status = 0)
$inactive_users = 0;
$result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM shonu_subjects WHERE status = 0");
if ($result) {
    $inactive_users = mysqli_fetch_assoc($result)['total'];
}

// Total Wallet (sum of motta)
$total_wallet = 0;
$wallet_q = mysqli_query($conn, "SELECT SUM(CAST(motta AS DECIMAL(10,2))) AS total_wallet FROM shonu_kaichila");
if ($wallet_q) {
    $total_wallet = mysqli_fetch_assoc($wallet_q)['total_wallet'] ?? 0;
}

// Total Recharge
$total_recharge = 0;
$recharge_q = mysqli_query($conn, "SELECT COALESCE(SUM(CAST(motta AS DECIMAL(18,2))),0) AS total_recharge FROM thevani WHERE sthiti='1'");
if ($recharge_q) {
    $total_recharge = mysqli_fetch_assoc($recharge_q)['total_recharge'] ?? 0;
}

// Today's Registrations
$today_registrations = 0;
$today_q = mysqli_query($conn, "SELECT COUNT(*) AS today FROM shonu_subjects WHERE DATE(createdate) = CURDATE()");
if ($today_q) {
    $today_registrations = mysqli_fetch_assoc($today_q)['today'] ?? 0;
}

// Verified/payment-bound users: count unique users with an active bank/USDT or saved UPI method.
$verified_users = 0;
$verifiedSql = "SELECT COUNT(DISTINCT user_id) AS verified FROM (
    SELECT byabaharkarta AS user_id FROM khate WHERE sthiti='1'
    UNION ALL
    SELECT user_id FROM upi_withdrawal
) payout_users";
$verified_q = mysqli_query($conn, $verifiedSql);
if ($verified_q) {
    $verified_users = (int)(mysqli_fetch_assoc($verified_q)['verified'] ?? 0);
}

// Calculate percentages
$active_percentage = $total_users > 0 ? round(($active_users / $total_users) * 100, 1) : 0;
$verified_percentage = $total_users > 0 ? round(($verified_users / $total_users) * 100, 1) : 0;

// Fetch Login Notifications
$loginNotifs = [];
$notifQuery = mysqli_query($conn, "SELECT * FROM notification WHERE state = 0 ORDER BY id DESC LIMIT 5");
if ($notifQuery) {
    while ($row = mysqli_fetch_assoc($notifQuery)) {
        $loginNotifs[] = $row;
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

    <title>Manage Users - Admin Dashboard</title>

    <meta name="description" content="" />

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/img/favicon/favicon.ico?v=20260814-admin" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap"
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
    <script src="assets/vendor/js/template-customizer.js"></script>
    <script src="assets/js/config.js"></script>
    
    <style>
        /* Professional Stats Cards */
        .stats-card {
            transition: all 0.3s ease;
            border-left: 4px solid;
            height: 100%;
            position: relative;
            overflow: hidden;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        .stats-card .card-body {
            padding: 1.25rem;
        }
        .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
            font-family: 'Inter', sans-serif;
        }
        .stat-label {
            font-size: 0.875rem;
            color: #6c757d;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 500;
        }
        .stat-change {
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        
        /* Progress Bar */
        .progress-sm {
            height: 4px;
            border-radius: 2px;
            overflow: hidden;
            background: rgba(0,0,0,0.1);
        }
        .progress-bar {
            border-radius: 2px;
        }
        
        /* Card Colors */
        .card-primary {
            border-left-color: #4e73df;
        }
        .card-success {
            border-left-color: #1cc88a;
        }
        .card-warning {
            border-left-color: #f6c23e;
        }
        .card-info {
            border-left-color: #36b9cc;
        }
        .card-danger {
            border-left-color: #e74a3b;
        }
        .card-secondary {
            border-left-color: #858796;
        }
        
        /* Search Container */
        .search-container {
            position: relative;
            margin-bottom: 1.5rem;
        }
        .search-container .search-input {
            padding-left: 45px;
            border-radius: 8px;
            border: 1px solid #d9dee3;
            height: 46px;
            font-size: 0.9375rem;
        }
        .search-container .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #697a8d;
            z-index: 4;
        }
        
        /* Table Styles */
        .table-responsive {
            border-radius: 8px;
            border: 1px solid #e3e6f0;
            overflow: hidden;
        }
        .table th {
            font-weight: 600;
            font-size: 0.8125rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #697a8d;
            background: #f9f9f9;
            border-bottom: 2px solid #e3e6f0;
            padding: 0.9375rem 1rem;
        }
        .table td {
            padding: 0.9375rem 1rem;
            vertical-align: middle;
            border-color: #e3e6f0;
        }
        .table-hover tbody tr:hover {
            background-color: rgba(67, 89, 113, 0.04);
        }
        
        /* Status Badges */
        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-active {
            background: rgba(28, 200, 138, 0.1);
            color: #1cc88a;
        }
        .badge-inactive {
            background: rgba(108, 117, 125, 0.1);
            color: #6c757d;
        }
        .badge-banned {
            background: rgba(231, 74, 59, 0.1);
            color: #e74a3b;
        }
        .badge-verified {
            background: rgba(54, 185, 204, 0.1);
            color: #36b9cc;
        }
        
        /* Action Buttons */
        .btn-action {
            width: 32px;
            height: 32px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            margin: 2px;
            border: 1px solid transparent;
        }
        .btn-action-sm {
            width: 28px;
            height: 28px;
        }
        
        /* Custom DataTable Styles */
        .dataTables_wrapper .dataTables_filter {
            float: none;
            text-align: center;
        }
        .dataTables_wrapper .dataTables_filter input {
            margin-left: 0.5em;
            border: 1px solid #d9dee3;
            border-radius: 6px;
            padding: 0.375rem 0.75rem;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .stats-card .card-body {
                padding: 1rem;
            }
            .stat-value {
                font-size: 1.5rem;
            }
            .table-responsive {
                margin: 0 -1rem;
                border-radius: 0;
                border-left: none;
                border-right: none;
            }
        }
    </style>
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
                        
                        <!-- Notification Message -->
                        <?php if(isset($_GET['msg']) && $_GET['msg'] == "updt"): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="ri-checkbox-circle-line me-2"></i>
                            <strong>Success!</strong> Update completed successfully.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php endif; ?>

                        <!-- Professional Statistics Cards -->
                        <div class="row mb-4">
                            <div class="col-xl-2 col-lg-4 col-md-6 mb-4">
                                <div class="card stats-card card-primary h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <p class="stat-label">Total Users</p>
                                                <h4 class="stat-value text-primary"><?= number_format($total_users) ?></h4>
                                                <div class="stat-change text-success">
                                                    <i class="ri-user-add-line me-1"></i> Today: <?= number_format($today_registrations) ?>
                                                </div>
                                                <div class="progress progress-sm mt-2">
                                                    <div class="progress-bar bg-primary" style="width: 100%"></div>
                                                </div>
                                            </div>
                                            <div class="stat-icon bg-label-primary">
                                                <i class="ri-user-line text-primary"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-xl-2 col-lg-4 col-md-6 mb-4">
                                <div class="card stats-card card-success h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <p class="stat-label">Active Users</p>
                                                <h4 class="stat-value text-success"><?= number_format($active_users) ?></h4>
                                                <div class="stat-change text-muted">
                                                    <?= $active_percentage ?>% of total
                                                </div>
                                                <div class="progress progress-sm mt-2">
                                                    <div class="progress-bar bg-success" style="width: <?= $active_percentage ?>%"></div>
                                                </div>
                                            </div>
                                            <div class="stat-icon bg-label-success">
                                                <i class="ri-user-follow-line text-success"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-xl-2 col-lg-4 col-md-6 mb-4">
                                <div class="card stats-card card-warning h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <p class="stat-label">Inactive Users</p>
                                                <h4 class="stat-value text-warning"><?= number_format($inactive_users) ?></h4>
                                                <div class="stat-change text-muted">
                                                    <?= round(($inactive_users/$total_users)*100, 1) ?>% of total
                                                </div>
                                                <div class="progress progress-sm mt-2">
                                                    <div class="progress-bar bg-warning" style="width: <?= round(($inactive_users/$total_users)*100, 1) ?>%"></div>
                                                </div>
                                            </div>
                                            <div class="stat-icon bg-label-warning">
                                                <i class="ri-user-unfollow-line text-warning"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-xl-2 col-lg-4 col-md-6 mb-4">
                                <div class="card stats-card card-info h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <p class="stat-label">Wallet Balance</p>
                                                <h4 class="stat-value text-info">₹<?= number_format($total_wallet, 2) ?></h4>
                                                <div class="stat-change text-muted">
                                                    <i class="ri-wallet-line me-1"></i> Total Recharge: ₹<?= number_format($total_recharge, 2) ?>
                                                </div>
                                                <div class="progress progress-sm mt-2">
                                                    <div class="progress-bar bg-info" style="width: 100%"></div>
                                                </div>
                                            </div>
                                            <div class="stat-icon bg-label-info">
                                                <i class="ri-wallet-line text-info"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-xl-2 col-lg-4 col-md-6 mb-4">
                                <div class="card stats-card card-danger h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <p class="stat-label">Verified Users</p>
                                                <h4 class="stat-value text-danger"><?= number_format($verified_users) ?></h4>
                                                <div class="stat-change text-muted">
                                                    <?= $verified_percentage ?>% of total
                                                </div>
                                                <div class="progress progress-sm mt-2">
                                                    <div class="progress-bar bg-danger" style="width: <?= $verified_percentage ?>%"></div>
                                                </div>
                                            </div>
                                            <div class="stat-icon bg-label-danger">
                                                <i class="ri-verified-badge-line text-danger"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-xl-2 col-lg-4 col-md-6 mb-4">
                                <div class="card stats-card card-secondary h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <p class="stat-label">Today's Growth</p>
                                                <h4 class="stat-value text-secondary">+<?= number_format($today_registrations) ?></h4>
                                                <div class="stat-change text-muted">
                                                    <i class="ri-line-chart-line me-1"></i> New registrations
                                                </div>
                                                <div class="progress progress-sm mt-2">
                                                    <div class="progress-bar bg-secondary" style="width: 100%"></div>
                                                </div>
                                            </div>
                                            <div class="stat-icon bg-label-secondary">
                                                <i class="ri-bar-chart-line text-secondary"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Users List Table -->
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">
                                    <i class="ri-user-settings-line me-2"></i>Users Management
                                    <span class="badge bg-primary ms-2"><?= number_format($total_users) ?> Users</span>
                                </h5>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-outline-primary btn-sm" onclick="refreshTable()">
                                        <i class="ri-refresh-line me-1"></i>Refresh
                                    </button>
                                    <button class="btn btn-primary btn-sm" onclick="exportToExcel()">
                                        <i class="ri-download-line me-1"></i>Export
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <!-- Universal Search Bar -->
                                <div class="search-container">
                                    <i class="ri-search-line search-icon"></i>
                                    <input type="text" class="form-control search-input" id="globalSearch" placeholder="Search users by Mobile, Own Code, Cust ID, Ref Code, or Name...">
                                </div>
                                
                                <div class="table-responsive">
                                    <table class="table table-hover" id="usersTable">
                                        <thead>
                                            <tr>
                                                <th>Mobile</th>
                                                <th>Own Code</th>
                                                <th>Ref. Code</th>
                                                <th>User ID</th>
                                                <th>Wallet</th>         
                                                <th>Recharge</th>
                                                <th>1st Recharge</th>
                                                <th>Turn Over</th>
                                                <th>Reg. Date</th>
                                                <th>Status</th>
                                                <th>Password</th>
                                            </tr>
                                        </thead>
                                        <tbody class="table-border-bottom-0">
                                            <!-- Data will be loaded via DataTables -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Amount Modal -->
                    <div class="modal fade" id="excel" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <form class="modal-content" id="type">
                                <div class="modal-header">
                                    <h4 class="modal-title" id="exampleModalLabel1">
                                        <i class="ri-wallet-line me-2"></i>Update Wallet Balance
                                    </h4>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="alert alert-info">
                                        <i class="ri-information-line me-2"></i>
                                        <span id="mob"></span>
                                    </div>
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="form-floating form-floating-outline">
                                                <input class="form-control" id="editid" name="editid" type="hidden">
                                                <input type="number" min="0" id="amount" name="amount" class="form-control" placeholder="Enter Amount" />
                                                <input type="hidden" id="balance" name="balance" class="form-control" />
                                                <label for="amount">Amount</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                        Close
                                    </button>
                                    <button type="submit" class="btn btn-primary" id="modal-button">Update</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Turnover Modal -->
                    <div class="modal fade" id="turnover" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <form class="modal-content" id="type-turnover">
                                <div class="modal-header">
                                    <h4 class="modal-title" id="exampleModalLabel1">
                                        <i class="ri-exchange-dollar-line me-2"></i>Update Turnover
                                    </h4>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="alert alert-info">
                                        <i class="ri-information-line me-2"></i>
                                        <span id="mobturnover"></span>
                                    </div>
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="form-floating form-floating-outline">
                                                <input class="form-control" id="editidturnover" name="editid" type="hidden">
                                                <input type="text" id="amountturnover" name="turnover" class="form-control" placeholder="Enter Turnover Amount" />
                                                <label for="amountturnover">Turnover Amount</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                        Close
                                    </button>
                                    <button type="submit" class="btn btn-primary" id="modal-button">Update</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Account No. Modal -->
                    <div class="modal fade" id="account" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <form class="modal-content" id="type-account">
                                <div class="modal-header">
                                    <h4 class="modal-title" id="exampleModalLabel1">
                                        <i class="ri-bank-card-line me-2"></i>Update Account Details
                                    </h4>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="alert alert-info">
                                        <i class="ri-information-line me-2"></i>
                                        <span id="mobaccount"></span>
                                    </div>
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="form-floating form-floating-outline">
                                                <input class="form-control" id="editidaccount" name="editid" type="hidden">
                                                <input type="text" id="accountno" name="accountno" class="form-control" placeholder="Enter Account No." />
                                                <label for="accountno">Account No.</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                        Close
                                    </button>
                                    <button type="submit" class="btn btn-primary" id="modal-button">Update</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- IFSC Modal -->
                    <div class="modal fade" id="ifsc" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <form class="modal-content" id="type-ifsc">
                                <div class="modal-header">
                                    <h4 class="modal-title" id="exampleModalLabel1">
                                        <i class="ri-bank-line me-2"></i>Update IFSC Code
                                    </h4>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="alert alert-info">
                                        <i class="ri-information-line me-2"></i>
                                        <span id="mobifsc"></span>
                                    </div>
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="form-floating form-floating-outline">
                                                <input class="form-control" id="editidifsc" name="editid" type="hidden">
                                                <input class="form-control" id="oldifsc" name="oldifsc" type="hidden">
                                                <input type="text" id="ifsccode" name="ifsc" class="form-control" placeholder="Enter IFSC Code" />
                                                <label for="ifsccode">IFSC Code</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                        Close
                                    </button>
                                    <button type="submit" class="btn btn-primary" id="modal-button">Update</button>
                                </div>
                            </form>
                        </div>
                    </div>

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
        <div class="drag-target"></div>
    </div>
    <!-- / Layout wrapper -->

    <!-- Core JS -->
    <script src="assets/vendor/libs/jquery/jquery.js"></script>
    <script src="assets/vendor/libs/popper/popper.js"></script>
    <script src="assets/vendor/js/bootstrap.js"></script>
    <script src="assets/vendor/libs/node-waves/node-waves.js"></script>
    <script src="assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="assets/vendor/libs/hammer/hammer.js"></script>
    <script src="assets/vendor/libs/i18n/i18n.js"></script>
    <script src="assets/vendor/libs/typeahead-js/typeahead.js"></script>
    <script src="assets/vendor/js/menu.js"></script>

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
    $(document).ready(function() {
        // Initialize DataTable
        var table = $('#usersTable').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax": "datatable_api/datatable_users.php",
            "paging": true,
            "lengthChange": true,
            "searching": true,
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "scrollX": true,
            "pageLength": 50,
            "responsive": true,
            "language": {
                "search": "",
                "searchPlaceholder": "Search all columns...",
                "lengthMenu": "Show _MENU_ entries",
                "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                "infoEmpty": "Showing 0 to 0 of 0 entries",
                "infoFiltered": "(filtered from _MAX_ total entries)",
                "paginate": {
                    "next": '<i class="ri-arrow-right-s-line"></i>',
                    "previous": '<i class="ri-arrow-left-s-line"></i>'
                },
                "processing": '<div class="spinner-border spinner-border-sm text-primary me-2"></div> Loading...'
            },
            "order": [[0, 'desc']],
            "dom": '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-md-end"f>>rt<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>'
        });

        // Universal Search Implementation
        $('#globalSearch').on('keyup', function() {
            table.search(this.value).draw();
            
            // Show search status
            if (this.value.length > 0) {
                $('.search-icon').removeClass('ri-search-line').addClass('ri-search-eye-line');
            } else {
                $('.search-icon').removeClass('ri-search-eye-line').addClass('ri-search-line');
            }
        });

        // Add debounce to search for better performance
        var searchTimeout;
        $('#globalSearch').on('keyup', function() {
            clearTimeout(searchTimeout);
            var $this = $(this);
            searchTimeout = setTimeout(function() {
                table.search($this.val()).draw();
            }, 500);
        });

        function refreshTable() {
            table.ajax.reload();
            showNotification('Table refreshed successfully', 'success');
        }

        function exportToExcel() {
            // Simple export implementation
            window.location.href = 'api/export_users.php';
        }

        function showNotification(message, type) {
            const alertClass = type === 'success' ? 'alert-success' : 'alert-info';
            const icon = type === 'success' ? 'ri-check-line' : 'ri-information-line';
            
            const notification = $(`
                <div class="alert ${alertClass} alert-dismissible fade show position-fixed top-0 end-0 m-3" style="z-index: 9999; min-width: 300px;">
                    <i class="${icon} me-2"></i>${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `);
            
            $('body').append(notification);
            setTimeout(() => notification.alert('close'), 3000);
        }

        // Modal functions
        function edit(id, mob, balance) {
            $('#excel').modal({backdrop: 'static', keyboard: false});
            $('#excel').modal('show');
            $('#mob').text('Mobile: ' + mob);
            $('#amount').val(balance);
            $('#balance').val(balance);
            $('#editid').val(id);
        }

        function editturnover(id, mob, turnover) {
            $('#turnover').modal({backdrop: 'static', keyboard: false});
            $('#turnover').modal('show');
            $('#mobturnover').text('Mobile: ' + mob);
            $('#amountturnover').val(turnover);
            $('#editidturnover').val(id);
        }

        function editaccount(id, mob, account) {
            $('#account').modal({backdrop: 'static', keyboard: false});
            $('#account').modal('show');
            $('#mobaccount').text('Mobile: ' + mob);
            $('#accountno').val(account);
            $('#editidaccount').val(id);
        }

        function editifsc(id, mob, ifsc) {
            $('#ifsc').modal({backdrop: 'static', keyboard: false});
            $('#ifsc').modal('show');
            $('#mobifsc').text('Mobile: ' + mob);
            $('#ifsccode').val(ifsc);
            $('#editidifsc').val(id);
            $('#oldifsc').val(ifsc);
        }

        // User management functions
        function Respond(Id) {
            if (confirm("Are you sure you want to deactivate this user?")) {
                $.ajax({
                    type: "POST",
                    data: "id=" + Id + "&type=chk",
                    url: "api/manage_userAction.php",
                    success: function(response) {
                        if (response == 1) {
                            showNotification('User deactivated successfully', 'success');
                            table.ajax.reload();
                        }
                    }
                });
            }
        }

        function UnRespond(Id) {
            if (confirm("Are you sure you want to activate this user?")) {
                $.ajax({
                    type: "POST",
                    data: "id=" + Id + "&type=unchk",
                    url: "api/manage_userAction.php",
                    success: function(response) {
                        if (response == 1) {
                            showNotification('User activated successfully', 'success');
                            table.ajax.reload();
                        }
                    }
                });
            }
        }

        function MarkVerified(Id) {
            if (confirm("Are you sure you want to verify this user?")) {
                $.ajax({
                    type: "POST",
                    data: "id=" + Id + "&type=verify",
                    url: "api/manage_userAction.php",
                    success: function(response) {
                        if (response == 1) {
                            showNotification('User verified successfully', 'success');
                            table.ajax.reload();
                        }
                    }
                });
            }
        }

        function MarkUnverified(Id) {
            if (confirm("Are you sure you want to unverify this user?")) {
                $.ajax({
                    type: "POST",
                    data: "id=" + Id + "&type=unverify",
                    url: "api/manage_userAction.php",
                    success: function(response) {
                        if (response == 1) {
                            showNotification('User unverified successfully', 'success');
                            table.ajax.reload();
                        }
                    }
                });
            }
        }

        function delete_row(Id) {
            if (confirm("Are you sure you want to delete this user?")) {
                $.ajax({
                    type: "POST",
                    data: "id=" + Id + "&type=delete",
                    url: "api/manage_userAction.php",
                    success: function(response) {
                        if (response == 1) {
                            showNotification('User deleted successfully', 'success');
                            table.ajax.reload();
                        }
                    }
                });
            }
        }

        // Form submissions
        $("#type").on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                type: "POST",
                url: "api/updatewalletNow.php",
                data: new FormData(this),
                contentType: false,
                cache: false,
                processData: false,
                success: function(response) {
                    if (response == 1) {
                        showNotification("Wallet updated successfully", "success");
                        $("#type")[0].reset();
                        $('#excel').modal('hide');
                        table.ajax.reload();
                    } else {
                        showNotification("Some technical error occurred", "danger");
                    }
                }
            });
        });

        $("#type-turnover").on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                type: "POST",
                url: "api/updateTurnoverNow.php",
                data: new FormData(this),
                contentType: false,
                cache: false,
                processData: false,
                success: function(response) {
                    if (response == 1) {
                        showNotification("Turnover updated successfully", "success");
                        $("#type-turnover")[0].reset();
                        $('#turnover').modal('hide');
                        table.ajax.reload();
                    } else {
                        showNotification("Some technical error occurred", "danger");
                    }
                }
            });
        });

        $("#type-account").on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                type: "POST",
                url: "api/updateAccountNow.php",
                data: new FormData(this),
                contentType: false,
                cache: false,
                processData: false,
                success: function(response) {
                    if (response == 1) {
                        showNotification("Account details updated successfully", "success");
                        $("#type-account")[0].reset();
                        $('#account').modal('hide');
                        table.ajax.reload();
                    } else {
                        showNotification("Some technical error occurred", "danger");
                    }
                }
            });
        });

        $("#type-ifsc").on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                type: "POST",
                url: "api/updateIfscNow.php",
                data: new FormData(this),
                contentType: false,
                cache: false,
                processData: false,
                success: function(response) {
                    if (response == 1) {
                        showNotification("IFSC updated successfully", "success");
                        $("#type-ifsc")[0].reset();
                        $('#ifsc').modal('hide');
                        table.ajax.reload();
                    } else {
                        showNotification("Some technical error occurred", "danger");
                    }
                }
            });
        });

        // Global functions
        window.edit = edit;
        window.editturnover = editturnover;
        window.editaccount = editaccount;
        window.editifsc = editifsc;
        window.Respond = Respond;
        window.UnRespond = UnRespond;
        window.MarkVerified = MarkVerified;
        window.MarkUnverified = MarkUnverified;
        window.delete_row = delete_row;
        window.refreshTable = refreshTable;
        window.exportToExcel = exportToExcel;
    });
    </script>
</body>
</html>