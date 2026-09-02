<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'user-details_log.txt');

session_start();
if (empty($_SESSION['unohs'])) {
    header("location: api/login.php?msg=unauthorized");
    exit();
}

include("api/conn.php");

// Input sanitization
$userid = isset($_GET['user']) ? intval($_GET['user']) : 0;
$level = isset($_GET['level']) ? intval($_GET['level']) : 0;
$transaction_type = isset($_GET['type']) ? $_GET['type'] : "-1";
$transaction_date = isset($_GET['date']) ? htmlspecialchars(mysqli_real_escape_string($conn, $_GET['date'])) : '';
$transaction_page = isset($_GET['transaction_page']) ? intval($_GET['transaction_page']) : 1;
$referral_page = isset($_GET['referral_page']) ? intval($_GET['referral_page']) : 1;

// Validate user ID
if ($userid <= 0) {
    die("Invalid user ID");
}

// Get user details
$snum = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM `shonu_subjects` WHERE `id` = '" . $userid . "'"));
if (!$snum) {
    die("User not found");
}

$owncode = $snum['owncode'];

// Get user balance
$total_balance = 0;
$balquery = "SELECT motta FROM shonu_kaichila WHERE balakedara = " . $userid;
$balresult = mysqli_query($conn, $balquery);
if ($balresult && $balarr = mysqli_fetch_array($balresult)) {
    $total_balance = floatval($balarr['motta']);
}

// Get total referrals
$total_refer = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(id) as total FROM `shonu_subjects` WHERE code = '" . mysqli_real_escape_string($conn, $owncode) . "'"));

// Calculate total bets using original file's method
$bet_wingo_1 = mysqli_fetch_assoc(mysqli_query($conn, "SELECT sum(ketebida) as total FROM `bajikattuttate` where byabaharkarta = '" . $userid . "'"));
$bet_wingo_3 = mysqli_fetch_assoc(mysqli_query($conn, "SELECT sum(ketebida) as total FROM `bajikattuttate_drei` where byabaharkarta = '" . $userid . "'"));
$bet_wingo_5 = mysqli_fetch_assoc(mysqli_query($conn, "SELECT sum(ketebida) as total FROM `bajikattuttate_funf` where byabaharkarta = '" . $userid . "'"));
$bet_wingo_10 = mysqli_fetch_assoc(mysqli_query($conn, "SELECT sum(ketebida) as total FROM `bajikattuttate_zehn` where byabaharkarta = '" . $userid . "'"));
$bet_k3_1 = mysqli_fetch_assoc(mysqli_query($conn, "SELECT sum(ketebida) as total FROM `bajikattuttate_kemuru` where byabaharkarta = '" . $userid . "'"));
$bet_k3_3 = mysqli_fetch_assoc(mysqli_query($conn, "SELECT sum(ketebida) as total FROM `bajikattuttate_kemuru_drei` where byabaharkarta = '" . $userid . "'"));
$bet_k3_5 = mysqli_fetch_assoc(mysqli_query($conn, "SELECT sum(ketebida) as total FROM `bajikattuttate_kemuru_funf` where byabaharkarta = '" . $userid . "'"));
$bet_k3_10 = mysqli_fetch_assoc(mysqli_query($conn, "SELECT sum(ketebida) as total FROM `bajikattuttate_kemuru_zehn` where byabaharkarta = '" . $userid . "'"));
$bet_5d_1 = mysqli_fetch_assoc(mysqli_query($conn, "SELECT sum(ketebida) as total FROM `bajikattuttate_aidudi` where byabaharkarta = '" . $userid . "'"));
$bet_5d_3 = mysqli_fetch_assoc(mysqli_query($conn, "SELECT sum(ketebida) as total FROM `bajikattuttate_aidudi_drei` where byabaharkarta = '" . $userid . "'"));
$bet_5d_5 = mysqli_fetch_assoc(mysqli_query($conn, "SELECT sum(ketebida) as total FROM `bajikattuttate_aidudi_funf` where byabaharkarta = '" . $userid . "'"));
$bet_5d_10 = mysqli_fetch_assoc(mysqli_query($conn, "SELECT sum(ketebida) as total FROM `bajikattuttate_aidudi_zehn` where byabaharkarta = '" . $userid . "'"));
$total_bet = $bet_wingo_1['total'] + $bet_wingo_3['total'] + $bet_wingo_5['total'] + $bet_wingo_10['total'] + $bet_k3_1['total'] + $bet_k3_3['total'] + $bet_k3_5['total'] + $bet_k3_10['total'] + $bet_5d_1['total'] + $bet_5d_3['total'] + $bet_5d_5['total'] + $bet_5d_10['total'];

// Secure function to get total
function getTotal($conn, $query) {
    $result = mysqli_query($conn, $query);
    if (!$result) {
        return 0;
    }
    $row = mysqli_fetch_assoc($result);
    return isset($row['total']) && $row['total'] !== null ? floatval($row['total']) : 0;
}

$total_recharge = getTotal($conn, "SELECT SUM(motta) as total FROM `thevani` WHERE `sthiti` = '1' AND `balakedara` = '" . mysqli_real_escape_string($conn, $userid) . "'");
$total_withdraw = getTotal($conn, "SELECT SUM(motta) as total FROM `hintegedukolli` WHERE sthiti = 1 AND balakedara = '" . mysqli_real_escape_string($conn, $userid) . "'");
$total_reward = getTotal($conn, "SELECT SUM(price) as total FROM `hodike_balakedara` WHERE userkani = '" . mysqli_real_escape_string($conn, $userid) . "'");

// Get total commission
$sumayoga = 0;
$rbxquery = "SELECT SUM(ayoga) as sumayoga FROM vyavahara WHERE balakedara = '" . mysqli_real_escape_string($conn, $userid) . "' AND prakara LIKE 'LVLCOMM%'";
$rbxresult = mysqli_query($conn, $rbxquery);
if ($rbxresult && $rbxar = mysqli_fetch_array($rbxresult)) {
    $sumayoga = floatval($rbxar['sumayoga'] ?? 0);
}

// Get referral data with pagination - Using original file's logic
$refferal_offset = ($referral_page - 1) * 10;
$refferal_total_pages = 0;

if ($level == 0) {
    $refer_record = mysqli_query($conn, "SELECT id, mobile, owncode, ishonup, createdate, 
                      CASE
                          WHEN code = '$owncode' THEN '1'
                          WHEN code1 = '$owncode' THEN '2'
                          WHEN code2 = '$owncode' THEN '3'
                          WHEN code3 = '$owncode' THEN '4'
                          WHEN code4 = '$owncode' THEN '5'
                          WHEN code5 = '$owncode' THEN '6'
                          ELSE 'unknown'
                      END AS lvl FROM `shonu_subjects` where (code = '$owncode' OR code1 = '$owncode' OR code2 = '$owncode' OR code3 = '$owncode' OR code4 = '$owncode' OR code5 = '$owncode') ORDER BY id DESC LIMIT 10 OFFSET $refferal_offset");
    $total_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM `shonu_subjects` 
                                    WHERE (code = '$owncode' OR code1 = '$owncode' OR 
                                           code2 = '$owncode' OR code3 = '$owncode' OR 
                                           code4 = '$owncode' OR code5 = '$owncode')");
    $total_row = mysqli_fetch_assoc($total_query);
    $refferal_total_pages = ceil($total_row['total'] / 10);
} else if ($level == 1) {
    $refer_record = mysqli_query($conn, "SELECT id, mobile, owncode, ishonup, createdate, '1' as lvl FROM `shonu_subjects` where code='$owncode' ORDER BY id DESC LIMIT 10 OFFSET $refferal_offset");
    $total_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM `shonu_subjects` WHERE code = '$owncode'");
    $total_row = mysqli_fetch_assoc($total_query);
    $refferal_total_pages = ceil($total_row['total'] / 10);
} else if ($level == 2) {
    $refer_record = mysqli_query($conn, "SELECT id, mobile, owncode, ishonup, createdate, '2' as lvl FROM `shonu_subjects` where code1='$owncode' ORDER BY id DESC LIMIT 10 OFFSET $refferal_offset");
    $total_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM `shonu_subjects` WHERE code1 = '$owncode'");
    $total_row = mysqli_fetch_assoc($total_query);
    $refferal_total_pages = ceil($total_row['total'] / 10);
} else if ($level == 3) {
    $refer_record = mysqli_query($conn, "SELECT id, mobile, owncode, ishonup, createdate, '3' as lvl FROM `shonu_subjects` where code2='$owncode' ORDER BY id DESC LIMIT 10 OFFSET $refferal_offset");
    $total_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM `shonu_subjects` WHERE code2 = '$owncode'");
    $total_row = mysqli_fetch_assoc($total_query);
    $refferal_total_pages = ceil($total_row['total'] / 10);
} else if ($level == 4) {
    $refer_record = mysqli_query($conn, "SELECT id, mobile, owncode, ishonup, createdate, '4' as lvl FROM `shonu_subjects` where code3='$owncode' ORDER BY id DESC LIMIT 10 OFFSET $refferal_offset");
    $total_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM `shonu_subjects` WHERE code3 = '$owncode'");
    $total_row = mysqli_fetch_assoc($total_query);
    $refferal_total_pages = ceil($total_row['total'] / 10);
} else if ($level == 5) {
    $refer_record = mysqli_query($conn, "SELECT id, mobile, owncode, ishonup, createdate, '5' as lvl FROM `shonu_subjects` where code4='$owncode' ORDER BY id DESC LIMIT 10 OFFSET $refferal_offset");
    $total_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM `shonu_subjects` WHERE code4 = '$owncode'");
    $total_row = mysqli_fetch_assoc($total_query);
    $refferal_total_pages = ceil($total_row['total'] / 10);
} else {
    $refer_record = mysqli_query($conn, "SELECT id, mobile, owncode, ishonup, createdate, '6' as lvl FROM `shonu_subjects` where code5='$owncode' ORDER BY id DESC LIMIT 10 OFFSET $refferal_offset");
    $total_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM `shonu_subjects` WHERE code5 = '$owncode'");
    $total_row = mysqli_fetch_assoc($total_query);
    $refferal_total_pages = ceil($total_row['total'] / 10);
}

// Get transaction records
$deposit_record = mysqli_query($conn, "SELECT * FROM `thevani` WHERE `balakedara` = '" . mysqli_real_escape_string($conn, $userid) . "' ORDER BY shonu DESC");
$withdraw_record = mysqli_query($conn, "SELECT * FROM `hintegedukolli` WHERE balakedara = '" . mysqli_real_escape_string($conn, $userid) . "' ORDER BY shonu DESC");
?>

<!doctype html>
<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr"
    data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template" data-style="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    
    <title>User Details - <?= htmlspecialchars($snum['mobile']) ?> | Admin Panel</title>
    <meta name="description" content="" />
    <link rel="icon" type="image/x-icon" href="assets/img/favicon/favicon.ico?v=20260814-admin" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&amp;display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="assets/vendor/fonts/remixicon/remixicon.css" />
    <link rel="stylesheet" href="assets/vendor/fonts/flag-icons.css" />
    <link rel="stylesheet" href="assets/vendor/libs/node-waves/node-waves.css" />
    <link rel="stylesheet" href="assets/vendor/css/rtl/core.css" class="template-customizer-core-css" />
    <link rel="stylesheet" href="assets/vendor/css/rtl/theme-default.css" class="template-customizer-theme-css" />
    <link rel="stylesheet" href="assets/css/demo.css" />
    <link rel="stylesheet" href="assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
    <link rel="stylesheet" href="assets/vendor/libs/typeahead-js/typeahead.css" />
    <link rel="stylesheet" href="assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css" />
    <link rel="stylesheet" href="assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css" />
    <link rel="stylesheet" href="assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css" />
    <link rel="stylesheet" href="assets/vendor/libs/datatables-checkboxes-jquery/datatables.checkboxes.css" />
    <link rel="stylesheet" href="assets/vendor/libs/select2/select2.css" />
    <link rel="stylesheet" href="assets/vendor/libs/@form-validation/form-validation.css" />
    <script src="assets/vendor/js/helpers.js"></script>
    <script src="assets/vendor/js/template-customizer.js"></script>
    <script src="assets/js/config.js"></script>

    <!-- Page CSS -->
    <style>
        /* Custom Styles */
        .user-stats-card {
            transition: all 0.3s ease;
            border-left: 4px solid;
            border-radius: 8px;
            overflow: hidden;
            height: 100%;
        }
        
        .user-stats-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .stats-icon {
            width: 48px;
            height: 48px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            background: rgba(0,0,0,0.05);
        }
        
        .stat-label {
            font-size: 0.875rem;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.25rem;
            font-weight: 500;
        }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .user-detail-badge {
            padding: 0.375rem 0.75rem;
            border-radius: 6px;
            font-size: 0.8125rem;
            font-weight: 500;
        }
        
        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #566a7f;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .section-title i {
            color: #696cff;
        }
        
        .detail-item {
            padding: 0.75rem 0;
            border-bottom: 1px solid #e3e6f0;
        }
        
        .detail-item:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            font-weight: 500;
            color: #566a7f;
            min-width: 140px;
        }
        
        .detail-value {
            color: #697a8d;
            font-weight: 400;
        }
        
        .card-header-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 8px 8px 0 0 !important;
        }
        
        .card-header-custom .card-title {
            color: white;
            margin: 0;
            font-size: 1.125rem;
            font-weight: 600;
        }
        
        .form-section {
            /*background: #f8f9fa;*/
            border-radius: 8px;
            padding: 1.5rem;
        }
        
        /* Table enhancements */
        .dataTables_wrapper {
            margin-top: 1rem;
        }
        
        .table th {
            font-weight: 600;
            font-size: 0.8125rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #697a8d;
            background: #f9f9f9;
            border-bottom: 2px solid #e3e6f0;
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(67, 89, 113, 0.04);
        }
        
        /* Pagination */
        .pagination .page-item.active .page-link {
            background: #696cff;
            border-color: #696cff;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .user-stats-card {
                margin-bottom: 1rem;
            }
            
            .section-title {
                font-size: 1.125rem;
            }
            
            .detail-label {
                min-width: 120px;
            }
        }
        
        /* Original file's overflow style */
        .overflow-x-auto {
            overflow-x: auto;
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
                        <!-- Page Header -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h4 class="fw-bold mb-0">
                                        <i class="ri-user-line me-2"></i>User Details
                                    </h4>
                                    <a href="javascript:history.back()" class="btn btn-outline-secondary">
                                        <i class="ri-arrow-left-line me-1"></i>Back
                                    </a>
                                </div>
                                <div class="alert alert-info">
                                    <i class="ri-information-line me-2"></i>
                                    Viewing details for user: <strong><?= htmlspecialchars($snum['mobile']) ?></strong>
                                </div>
                            </div>
                        </div>

                        <!-- Main Content -->
                        <div class="row mb-5">
                            <!-- Left Column: User Info & Actions -->
                            <div class="col-xl-5 col-lg-5 col-md-6 mb-4">
                                <!-- User Profile Card -->
                                <div class="card mb-4">
                                    <div class="card-header card-header-custom">
                                        <h5 class="card-title mb-0">
                                            <i class="ri-user-settings-line me-2"></i>User Profile
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row mb-4">
                                            <div class="col-12">
                                                <div class="d-flex align-items-center mb-3">
                                                    <div class="flex-shrink-0">
                                                        <div class="stats-icon bg-label-primary">
                                                            <i class="ri-user-line text-primary"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <h5 class="mb-1">User ID: <?= $snum['id'] ?></h5>
                                                        <p class="text-muted mb-0"><?= htmlspecialchars($snum['mobile']) ?></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="section-title">
                                            <i class="ri-information-line"></i>Account Information
                                        </div>
                                        
                                        <div class="detail-item d-flex">
                                            <span class="detail-label">User ID:</span>
                                            <span class="detail-value">
                                                <span class="user-detail-badge bg-label-primary"><?= $snum['id'] ?></span>
                                            </span>
                                        </div>
                                        
                                        <div class="detail-item d-flex">
                                            <span class="detail-label">Referral Code:</span>
                                            <span class="detail-value">
                                                <span class="user-detail-badge bg-label-info"><?= htmlspecialchars($snum['owncode']) ?></span>
                                            </span>
                                        </div>
                                        
                                        <div class="detail-item d-flex">
                                            <span class="detail-label">Mobile Number:</span>
                                            <span class="detail-value"><?= htmlspecialchars($snum['mobile']) ?></span>
                                        </div>
                                        
                                        <div class="detail-item d-flex">
                                            <span class="detail-label">IP Address:</span>
                                            <span class="detail-value">
                                                <span class="user-detail-badge bg-label-warning"><?= htmlspecialchars($snum['ishonup']) ?></span>
                                            </span>
                                        </div>
                                        
                                        <div class="detail-item d-flex">
                                            <span class="detail-label">Registration Date:</span>
                                            <span class="detail-value"><?= date('d M Y, h:i A', strtotime($snum['createdate'])) ?></span>
                                        </div>
                                        
                                        <div class="detail-item d-flex">
                                            <span class="detail-label">Referred By:</span>
                                            <span class="detail-value">
                                                <?php if (!empty($snum['code'])): ?>
                                                    <span class="user-detail-badge bg-label-success"><?= htmlspecialchars($snum['code']) ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">Not referred</span>
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Password Update Card -->
                                <div class="card mb-4">
                                    <div class="card-header card-header-custom">
                                        <h5 class="card-title mb-0">
                                            <i class="ri-lock-line me-2"></i>Security Settings
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-section mb-4">
                                            <h6 class="mb-3">
                                                <i class="ri-key-line me-2"></i>Change Password
                                            </h6>
                                            <form id="formChangePassword" action="api/update_userpass.php" method="POST" class="needs-validation" novalidate>
                                                <input type="hidden" name="user_id" value="<?= $snum['id'] ?>">
                                                <div class="mb-3">
                                                    <label for="newPassword" class="form-label">New Password</label>
                                                    <div class="input-group input-group-merge">
                                                        <input type="password" class="form-control" id="newPassword" name="resetpsw" 
                                                               placeholder="Enter new password" required>
                                                        <span class="input-group-text cursor-pointer" id="togglePassword">
                                                            <i class="ri-eye-line"></i>
                                                        </span>
                                                    </div>
                                                    <div class="invalid-feedback">Please enter a new password.</div>
                                                </div>
                                                <button type="submit" class="btn btn-primary w-100">
                                                    <i class="ri-save-line me-1"></i>Update Password
                                                </button>
                                            </form>
                                        </div>
                                        
                                        <div class="form-section">
                                            <h6 class="mb-3">
                                                <i class="ri-user-shared-line me-2"></i>Update Referral Code
                                            </h6>
                                            <form id="formChangeReferral" action="api/update_reffcode.php" method="POST" class="needs-validation" novalidate>
                                                <input type="hidden" name="user_id" value="<?= $snum['id'] ?>">
                                                <div class="mb-3">
                                                    <label for="referred_by" class="form-label">Referred By</label>
                                                    <input type="text" class="form-control" id="referred_by" name="referred_by" 
                                                           value="<?= htmlspecialchars($snum['code']) ?>" placeholder="Enter referral code">
                                                    <div class="form-text">Enter the referral code of the user who referred this user.</div>
                                                </div>
                                                <button type="submit" class="btn btn-outline-primary w-100">
                                                    <i class="ri-refresh-line me-1"></i>Update Referral
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Column: Statistics -->
                            <div class="col-xl-7 col-lg-7 col-md-6 mb-4">
                                <div class="row g-4">
                                    <!-- Total Balance -->
                                    <div class="col-sm-6 col-xl-4">
                                        <div class="card user-stats-card border-left-primary">
                                            <div class="card-body">
                                                <div class="d-flex align-items-center justify-content-between mb-3">
                                                    <div class="stats-icon bg-label-primary">
                                                        <i class="ri-wallet-line text-primary"></i>
                                                    </div>
                                                    <span class="badge bg-primary rounded-pill">Live</span>
                                                </div>
                                                <p class="stat-label">Total Balance</p>
                                                <h4 class="stat-value text-primary">
                                                    ₹<?= number_format($total_balance, 2) ?>
                                                </h4>
                                                <div class="text-muted small">Current wallet balance</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Total Referrals -->
                                    <div class="col-sm-6 col-xl-4">
                                        <div class="card user-stats-card border-left-success">
                                            <div class="card-body">
                                                <div class="d-flex align-items-center justify-content-between mb-3">
                                                    <div class="stats-icon bg-label-success">
                                                        <i class="ri-group-line text-success"></i>
                                                    </div>
                                                    <span class="badge bg-success rounded-pill">Network</span>
                                                </div>
                                                <p class="stat-label">Total Referrals</p>
                                                <h4 class="stat-value text-success">
                                                    <?= number_format($total_refer['total'] ?? 0) ?>
                                                </h4>
                                                <div class="text-muted small">Direct referrals</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Total Withdraw -->
                                    <div class="col-sm-6 col-xl-4">
                                        <div class="card user-stats-card border-left-info">
                                            <div class="card-body">
                                                <div class="d-flex align-items-center justify-content-between mb-3">
                                                    <div class="stats-icon bg-label-info">
                                                        <i class="ri-money-dollar-circle-line text-info"></i>
                                                    </div>
                                                    <span class="badge bg-info rounded-pill">Withdrawn</span>
                                                </div>
                                                <p class="stat-label">Total Withdraw</p>
                                                <h4 class="stat-value text-info">
                                                    ₹<?= number_format($total_withdraw, 2) ?>
                                                </h4>
                                                <div class="text-muted small">Total withdrawn amount</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Total Gift -->
                                    <div class="col-sm-6 col-xl-4">
                                        <div class="card user-stats-card border-left-warning">
                                            <div class="card-body">
                                                <div class="d-flex align-items-center justify-content-between mb-3">
                                                    <div class="stats-icon bg-label-warning">
                                                        <i class="ri-gift-line text-warning"></i>
                                                    </div>
                                                    <span class="badge bg-warning rounded-pill">Bonus</span>
                                                </div>
                                                <p class="stat-label">Total Gift</p>
                                                <h4 class="stat-value text-warning">
                                                    ₹<?= number_format($total_reward, 2) ?>
                                                </h4>
                                                <div class="text-muted small">Total bonuses received</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Total Bet -->
                                    <div class="col-sm-6 col-xl-4">
                                        <div class="card user-stats-card border-left-danger">
                                            <div class="card-body">
                                                <div class="d-flex align-items-center justify-content-between mb-3">
                                                    <div class="stats-icon bg-label-danger">
                                                        <i class="ri-gamepad-line text-danger"></i>
                                                    </div>
                                                    <span class="badge bg-danger rounded-pill">Betting</span>
                                                </div>
                                                <p class="stat-label">Total Bet</p>
                                                <h4 class="stat-value text-danger">
                                                    ₹<?= number_format($total_bet, 2) ?>
                                                </h4>
                                                <div class="text-muted small">Total betting amount</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Total Recharge -->
                                    <div class="col-sm-6 col-xl-4">
                                        <div class="card user-stats-card border-left-secondary">
                                            <div class="card-body">
                                                <div class="d-flex align-items-center justify-content-between mb-3">
                                                    <div class="stats-icon bg-label-secondary">
                                                        <i class="ri-refresh-line text-secondary"></i>
                                                    </div>
                                                    <span class="badge bg-secondary rounded-pill">Deposit</span>
                                                </div>
                                                <p class="stat-label">Total Recharge</p>
                                                <h4 class="stat-value text-secondary">
                                                    ₹<?= number_format($total_recharge, 2) ?>
                                                </h4>
                                                <div class="text-muted small">Total deposit amount</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Total Commission -->
                                    <div class="col-sm-6 col-xl-4">
                                        <div class="card user-stats-card border-left-dark">
                                            <div class="card-body">
                                                <div class="d-flex align-items-center justify-content-between mb-3">
                                                    <div class="stats-icon bg-label-dark">
                                                        <i class="ri-percent-line text-dark"></i>
                                                    </div>
                                                    <span class="badge bg-dark rounded-pill">Commission</span>
                                                </div>
                                                <p class="stat-label">Total Commission</p>
                                                <h4 class="stat-value text-dark">
                                                    ₹<?= number_format($sumayoga, 2) ?>
                                                </h4>
                                                <div class="text-muted small">Total commission earned</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Transaction History Section - WITH COMPLETE FILTERS LIKE ORIGINAL FILE -->
<div class="card mb-5" id="transaction-history">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="section-title mb-0">
            <i class="ri-history-line"></i>Transaction History
        </h5>
        <form action="#transaction-history" method="get" class="d-flex gap-2 align-items-center">
            <!-- COMPLETE TRANSACTION FILTERS FROM ORIGINAL FILE -->
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
            <input type="date" class="form-control form-control-sm" id="date" name="date" 
                   value="<?= $transaction_date ?>" max="<?= date('Y-m-d') ?>" style="width: auto;">
            <input type="hidden" name="transaction_page" value="1">
            <input type="hidden" name="user" value="<?= $userid ?>">
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="ri-search-line me-1"></i>Filter
            </button>
            <a href="user-details.php?user=<?= $userid ?>#transaction-history" class="btn btn-outline-secondary btn-sm">
                <i class="ri-refresh-line"></i>
            </a>
        </form>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Transaction Type</th>
                        <th>Time</th>
                        <th>Amount</th>
                        <th>Remark</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $i = 0;
                    include "api/get-transactions.php";
                    $transactions = getTransactions($conn, $userid, $transaction_type, $transaction_date, $transaction_page);
                    if (!empty($transactions["list"])) {
                        foreach ($transactions["list"] as $row) {
                            $i++;
                            ?>
                            <tr>
                                <td><?= ($transaction_page - 1) * 10 + $i ?></td>
                                <td>
                                    <span class="badge bg-label-<?= 
                                        ($row['typeName'] == 'Deposit') ? 'success' : 
                                        (($row['typeName'] == 'Withdraw') ? 'danger' : 
                                        (($row['typeName'] == 'Bet') ? 'warning' : 'info')) 
                                    ?>">
                                        <?= htmlspecialchars($row['typeName']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($row['addTime']) ?></td>
                                <td>
                                    <span class="fw-medium <?= 
                                        (strpos($row['typeName'], 'Deposit') !== false || strpos($row['typeName'], 'Win') !== false || strpos($row['typeName'], 'Bonus') !== false) ? 'text-success' : 'text-danger' 
                                    ?>">
                                        ₹<?= number_format($row['amount'], 2) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($row['remark']) ?></td>
                            </tr>
                        <?php }
                    } else { ?>
                        <tr>
                            <td colspan="5" class="text-center py-4">
                                <div class="text-muted">
                                    <i class="ri-file-list-line fs-1 mb-2"></i>
                                    <p class="mb-0">No transactions found</p>
                                </div>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($transactions['totalPage'] > 1): ?>
        <div class="d-flex justify-content-between align-items-center mt-4">
            <div class="text-muted">
                Showing page <?= $transaction_page ?> of <?= $transactions['totalPage'] ?> 
                (Total <?= $transactions['totalCount'] ?> records)
            </div>
            <nav>
                <ul class="pagination mb-0 overflow-x-auto">
                    <!-- First Page -->
                    <?php if ($transaction_page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?transaction_page=1&user=<?= $userid ?>&date=<?= $transaction_date ?>&type=<?= $transaction_type ?>#transaction-history">
                            <i class="ri-skip-back-line"></i>
                        </a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?transaction_page=<?= $transaction_page-1 ?>&user=<?= $userid ?>&date=<?= $transaction_date ?>&type=<?= $transaction_type ?>#transaction-history">
                            <i class="ri-arrow-left-s-line"></i>
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <!-- Page Numbers -->
                    <?php
                    $startPage = max(1, $transaction_page - 2);
                    $endPage = min($transactions['totalPage'], $transaction_page + 2);
                    
                    for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <li class="page-item <?= ($i == $transaction_page) ? 'active' : '' ?>">
                            <a class="page-link" href="?transaction_page=<?= $i ?>&user=<?= $userid ?>&date=<?= $transaction_date ?>&type=<?= $transaction_type ?>#transaction-history">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    
                    <!-- Last Page -->
                    <?php if ($transaction_page < $transactions['totalPage']): ?>
                    <li class="page-item">
                        <a class="page-link" href="?transaction_page=<?= $transaction_page+1 ?>&user=<?= $userid ?>&date=<?= $transaction_date ?>&type=<?= $transaction_type ?>#transaction-history">
                            <i class="ri-arrow-right-s-line"></i>
                        </a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?transaction_page=<?= $transactions['totalPage'] ?>&user=<?= $userid ?>&date=<?= $transaction_date ?>&type=<?= $transaction_type ?>#transaction-history">
                            <i class="ri-skip-forward-line"></i>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Referral Record Section -->
<div class="card mb-5" id="refferal-record">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="section-title mb-0">
            <i class="ri-user-shared-line"></i>Referral Network
        </h5>
        <form action="#refferal-record" method="get" class="d-flex gap-2 align-items-center">
            <select class="form-select form-select-sm" id="level" name="level" style="width: auto;">
                <option value="0" <?= $level == 0 ? "selected" : "" ?>>All Levels</option>
                <?php for ($l = 1; $l <= 6; $l++): ?>
                    <option value="<?= $l ?>" <?= $level == $l ? "selected" : "" ?>>Level <?= $l ?></option>
                <?php endfor; ?>
            </select>
            <input type="hidden" name="referral_page" value="1">
            <input type="hidden" name="user" value="<?= $userid ?>">
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="ri-filter-line me-1"></i>Filter
            </button>
        </form>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>User ID</th>
                        <th>Join Date</th>
                        <th>Mobile</th>
                        <th>Level</th>
                        <th>Referral Code</th>
                        <th>IP Address</th>
                        <th>Recharge</th>
                        <th>Wallet</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (mysqli_num_rows($refer_record) > 0) {
                        $i = 0;
                        mysqli_data_seek($refer_record, 0);
                        while ($row = mysqli_fetch_assoc($refer_record)) {
                            $i++;
                            ?>
                            <tr>
                                <td><?= $refferal_offset + $i ?></td>
                                <td>
                                    <span class="fw-medium text-primary"><?= $row['id'] ?></span>
                                </td>
                                <td><?= date('d M Y', strtotime($row['createdate'])) ?></td>
                                <td><?= htmlspecialchars($row['mobile']) ?></td>
                                <td>
                                    <span class="badge bg-label-<?= 
                                        ($row['lvl'] == 1) ? 'success' : 
                                        (($row['lvl'] == 2) ? 'info' : 
                                        (($row['lvl'] == 3) ? 'warning' : 'secondary')) 
                                    ?>">
                                        Level <?= $row['lvl'] ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-label-primary"><?= htmlspecialchars($row['owncode']) ?></span>
                                </td>
                                <td><?= htmlspecialchars($row['ishonup']) ?></td>
                                <td>
                                    <?php
                                    $recharge_q = mysqli_query($conn, "SELECT SUM(motta) as total FROM `thevani` WHERE `sthiti` = '1' AND `balakedara` = '" . $row['id'] . "'");
                                    $recharge_data = mysqli_fetch_assoc($recharge_q);
                                    echo '₹' . number_format($recharge_data['total'] ?? 0, 2);
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    $wallet_q = mysqli_query($conn, "SELECT motta FROM shonu_kaichila WHERE balakedara = '" . $row['id'] . "'");
                                    $wallet_data = mysqli_fetch_assoc($wallet_q);
                                    echo '₹' . number_format($wallet_data['motta'] ?? 0, 2);
                                    ?>
                                </td>
                                <td>
                                    <a href="user-details.php?user=<?= $row['id'] ?>" 
                                       class="btn btn-sm btn-outline-primary" 
                                       title="View Details">
                                        <i class="ri-eye-line"></i> View
                                    </a>
                                </td>
                            </tr>
                        <?php }
                    } else { ?>
                        <tr>
                            <td colspan="10" class="text-center py-4">
                                <div class="text-muted">
                                    <i class="ri-user-unfollow-line fs-1 mb-2"></i>
                                    <p class="mb-0">No referrals found</p>
                                </div>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($refferal_total_pages > 1): ?>
        <div class="d-flex justify-content-between align-items-center mt-4">
            <div class="text-muted">
                Showing page <?= $referral_page ?> of <?= $refferal_total_pages ?> 
                (Total <?= $total_row['total'] ?? 0 ?> records)
            </div>
            <nav>
                <ul class="pagination mb-0 overflow-x-auto">
                    <!-- First Page -->
                    <?php if ($referral_page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?referral_page=1&user=<?= $userid ?>&level=<?= $level ?>#refferal-record">
                            <i class="ri-skip-back-line"></i>
                        </a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?referral_page=<?= $referral_page-1 ?>&user=<?= $userid ?>&level=<?= $level ?>#refferal-record">
                            <i class="ri-arrow-left-s-line"></i>
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <!-- Page Numbers -->
                    <?php
                    $startPage = max(1, $referral_page - 2);
                    $endPage = min($refferal_total_pages, $referral_page + 2);
                    
                    for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <li class="page-item <?= ($i == $referral_page) ? 'active' : '' ?>">
                            <a class="page-link" href="?referral_page=<?= $i ?>&user=<?= $userid ?>&level=<?= $level ?>#refferal-record">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    
                    <!-- Last Page -->
                    <?php if ($referral_page < $refferal_total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?referral_page=<?= $referral_page+1 ?>&user=<?= $userid ?>&level=<?= $level ?>#refferal-record">
                            <i class="ri-arrow-right-s-line"></i>
                        </a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?referral_page=<?= $refferal_total_pages ?>&user=<?= $userid ?>&level=<?= $level ?>#refferal-record">
                            <i class="ri-skip-forward-line"></i>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>

                        <!-- Recharge Record Section - ORIGINAL STYLE -->
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
                                        mysqli_data_seek($deposit_record, 0);
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
                        
                        <!-- Withdraw Record Section - ORIGINAL STYLE -->
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
                                        mysqli_data_seek($withdraw_record, 0);
                                        while ($row = mysqli_fetch_array($withdraw_record)) {
                                            $i++;
                                            ?>
                                            <tr>
                                                <td><?= $i; ?></td>
                                                <td><?= $row['dinankavannuracisi']; ?></td>
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
                        
                        <!-- WINGO BET RECORD SECTION - FROM ORIGINAL FILE -->
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
                                        $query = "
                                                    SELECT 'Wingo 30 Sec' AS wingo_type, kalaparichaya, ketebida, phalaphala, ojana 
                                                    FROM bajikattuttate_zehn WHERE byabaharkarta = '" . $userid . "'
                                                    UNION ALL
                                                    SELECT 'Wingo 1 Min' AS wingo_type, kalaparichaya, ketebida, phalaphala, ojana 
                                                    FROM bajikattuttate WHERE byabaharkarta = '" . $userid . "'
                                                    UNION ALL
                                                    SELECT 'Wingo 3 Min' AS wingo_type, kalaparichaya, ketebida, phalaphala, ojana  
                                                    FROM bajikattuttate_drei WHERE byabaharkarta = '" . $userid . "'
                                                    UNION ALL
                                                    SELECT 'Wingo 5 Min' AS wingo_type, kalaparichaya, ketebida, phalaphala, ojana  
                                                    FROM bajikattuttate_funf WHERE byabaharkarta = '" . $userid . "'
                                                ";

                                        $wingo = mysqli_query($conn, $query);

                                        if (!$wingo) {
                                            echo "Error: " . mysqli_error($conn); // Error handling for query failure
                                        } else {
                                            // Loop through all rows returned by the query
                                            $i = 0;
                                            $bet_types = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, "RED", "GREEN", "VIOLET", "BIG", "SMALL"];
                                            while ($row = mysqli_fetch_assoc($wingo)) {
                                                $i++;
                                                ?>
                                                <tr>
                                                    <td><?= $i; ?></td>
                                                    <td><?= htmlspecialchars($row['wingo_type']); ?></td>
                                                    <td><?= htmlspecialchars($row['kalaparichaya']); ?></td>
                                                    <td><?= htmlspecialchars($row['ketebida']); ?></td>
                                                    <td><span
                                                            class="badge bg-label-warning rounded-pill"><?= htmlspecialchars($bet_types[$row['ojana']]); ?></span>
                                                    </td>
                                                    <td><?php
                                                    echo ($row['phalaphala'] == "perte") ? '<span class="badge bg-label-danger rounded-pill">LOSS</span>' : '<span class="badge bg-label-success rounded-pill">WIN</span>';
                                                    ?></td>
                                                </tr>
                                            <?php }
                                        } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- TRX WINGO BET RECORD SECTION - FROM ORIGINAL FILE -->
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
                                        $query = "
                                                    SELECT 'Trx Wingo 1 Min' AS trx_type, kalaparichaya, ketebida, phalaphala 
                                                    FROM bajikattuttate_trx WHERE byabaharkarta = '" . $userid . "'
                                                    UNION ALL
                                                    SELECT 'Trx Wingo 3 Min' AS trx_type, kalaparichaya, ketebida, phalaphala 
                                                    FROM bajikattuttate_trx3 WHERE byabaharkarta = '" . $userid . "'
                                                    UNION ALL
                                                    SELECT 'Trx Wingo 5 Min' AS trx_type, kalaparichaya, ketebida, phalaphala 
                                                    FROM bajikattuttate_trx5 WHERE byabaharkarta = '" . $userid . "'
                                                    UNION ALL
                                                    SELECT 'Trx Wingo 10 Min' AS trx_type, kalaparichaya, ketebida, phalaphala 
                                                    FROM bajikattuttate_trx10 WHERE byabaharkarta = '" . $userid . "'
                                                ";

                                        $trx = mysqli_query($conn, $query);

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
                                                    <td><?php echo htmlspecialchars($imitator['trx_type']); ?></td>
                                                    <td><?php echo htmlspecialchars($imitator['kalaparichaya']); ?></td>
                                                    <td><?php echo htmlspecialchars($imitator['ketebida']); ?></td>
                                                    <td><?php
                                                    echo ($imitator['phalaphala'] == "perte") ? '<span class="badge bg-label-danger rounded-pill">LOSS</span>' : '<span class="badge bg-label-success rounded-pill">WIN</span>';
                                                    ?></td>
                                                </tr>
                                            <?php }
                                        } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Additional Bet Records from Original File -->
                        <!-- K3 BET RECORDS -->
                        <div class="card mb-5">
                            <div class="table-responsive text-nowrap">
                                <h4 class="m-5">K3 Bet Record</h4>
                                <table class="datatables-table1 table">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>K3 Type</th>
                                            <th>Period</th>
                                            <th>Amount</th>
                                            <th>Bet</th>
                                            <th>Result</th>
                                        </tr>
                                    </thead>
                                    <tbody class="table-border-bottom-0">
                                        <?php
                                        $k3_query = "
                                            SELECT 'K3 1 Min' AS k3_type, kalaparichaya, ketebida, phalaphala, ojana 
                                            FROM bajikattuttate_kemuru WHERE byabaharkarta = '" . $userid . "'
                                            UNION ALL
                                            SELECT 'K3 3 Min' AS k3_type, kalaparichaya, ketebida, phalaphala, ojana 
                                            FROM bajikattuttate_kemuru_drei WHERE byabaharkarta = '" . $userid . "'
                                            UNION ALL
                                            SELECT 'K3 5 Min' AS k3_type, kalaparichaya, ketebida, phalaphala, ojana  
                                            FROM bajikattuttate_kemuru_funf WHERE byabaharkarta = '" . $userid . "'
                                            UNION ALL
                                            SELECT 'K3 10 Min' AS k3_type, kalaparichaya, ketebida, phalaphala, ojana  
                                            FROM bajikattuttate_kemuru_zehn WHERE byabaharkarta = '" . $userid . "'
                                        ";

                                        $k3_result = mysqli_query($conn, $k3_query);

                                        if ($k3_result) {
                                            $i = 0;
                                            $k3_bet_types = ["BIG", "SMALL", "ODD", "EVEN", "4", "5", "6", "7", "8", "9", "10", "11", "12", "13", "14", "15", "16", "17"];
                                            while ($row = mysqli_fetch_assoc($k3_result)) {
                                                $i++;
                                                ?>
                                                <tr>
                                                    <td><?= $i; ?></td>
                                                    <td><?= htmlspecialchars($row['k3_type']); ?></td>
                                                    <td><?= htmlspecialchars($row['kalaparichaya']); ?></td>
                                                    <td><?= htmlspecialchars($row['ketebida']); ?></td>
                                                    <td>
                                                        <span class="badge bg-label-info rounded-pill">
                                                            <?= isset($k3_bet_types[$row['ojana']]) ? htmlspecialchars($k3_bet_types[$row['ojana']]) : $row['ojana']; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        echo ($row['phalaphala'] == "perte") ? 
                                                            '<span class="badge bg-label-danger rounded-pill">LOSS</span>' : 
                                                            '<span class="badge bg-label-success rounded-pill">WIN</span>';
                                                        ?>
                                                    </td>
                                                </tr>
                                            <?php }
                                        } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- 5D BET RECORDS -->
                        <div class="card mb-5">
                            <div class="table-responsive text-nowrap">
                                <h4 class="m-5">5D Bet Record</h4>
                                <table class="datatables-table1 table">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>5D Type</th>
                                            <th>Period</th>
                                            <th>Amount</th>
                                            <th>Bet</th>
                                            <th>Result</th>
                                        </tr>
                                    </thead>
                                    <tbody class="table-border-bottom-0">
                                        <?php
                                        $d5_query = "
                                            SELECT '5D 1 Min' AS d5_type, kalaparichaya, ketebida, phalaphala, ojana 
                                            FROM bajikattuttate_aidudi WHERE byabaharkarta = '" . $userid . "'
                                            UNION ALL
                                            SELECT '5D 3 Min' AS d5_type, kalaparichaya, ketebida, phalaphala, ojana 
                                            FROM bajikattuttate_aidudi_drei WHERE byabaharkarta = '" . $userid . "'
                                            UNION ALL
                                            SELECT '5D 5 Min' AS d5_type, kalaparichaya, ketebida, phalaphala, ojana  
                                            FROM bajikattuttate_aidudi_funf WHERE byabaharkarta = '" . $userid . "'
                                            UNION ALL
                                            SELECT '5D 10 Min' AS d5_type, kalaparichaya, ketebida, phalaphala, ojana  
                                            FROM bajikattuttate_aidudi_zehn WHERE byabaharkarta = '" . $userid . "'
                                        ";

                                        $d5_result = mysqli_query($conn, $d5_query);

                                        if ($d5_result) {
                                            $i = 0;
                                            while ($row = mysqli_fetch_assoc($d5_result)) {
                                                $i++;
                                                ?>
                                                <tr>
                                                    <td><?= $i; ?></td>
                                                    <td><?= htmlspecialchars($row['d5_type']); ?></td>
                                                    <td><?= htmlspecialchars($row['kalaparichaya']); ?></td>
                                                    <td><?= htmlspecialchars($row['ketebida']); ?></td>
                                                    <td>
                                                        <span class="badge bg-label-secondary rounded-pill">
                                                            <?= htmlspecialchars($row['ojana']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        echo ($row['phalaphala'] == "perte") ? 
                                                            '<span class="badge bg-label-danger rounded-pill">LOSS</span>' : 
                                                            '<span class="badge bg-label-success rounded-pill">WIN</span>';
                                                        ?>
                                                    </td>
                                                </tr>
                                            <?php }
                                        } ?>
                                    </tbody>
                                </table>
                            </div>
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
    $(document).ready(function() {
        // Toggle password visibility
        $('#togglePassword').click(function() {
            const passwordInput = $('#newPassword');
            const icon = $(this).find('i');
            
            if (passwordInput.attr('type') === 'password') {
                passwordInput.attr('type', 'text');
                icon.removeClass('ri-eye-line').addClass('ri-eye-off-line');
            } else {
                passwordInput.attr('type', 'password');
                icon.removeClass('ri-eye-off-line').addClass('ri-eye-line');
            }
        });
        
        // Form validation
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms).forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
        })();
        
        // Initialize DataTables for tables with class 'datatables-table1'
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
        
        // Prevent form resubmission on page refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
        
        // Auto-refresh for real-time data (optional)
        setInterval(function() {
            // You can add auto-refresh logic here if needed
        }, 30000); // 30 seconds
    });
    </script>
</body>
</html>