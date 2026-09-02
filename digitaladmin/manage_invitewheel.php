<?php
session_start();
if (!isset($_SESSION['unohs'])) {
    header("Location: index.php?msg=unauthorized");
    exit;
}
include("api/conn.php");

// Handle actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
    $spin_id = isset($_GET['spin_id']) ? intval($_GET['spin_id']) : 0;
    
    if ($user_id > 0) {
        switch ($action) {
            case 'update_spins':
                if (isset($_POST['total_spins'])) {
                    $total_spins = intval($_POST['total_spins']);
                    $sql = "UPDATE shonu_turntable SET total_spins = ?, updated_at = NOW() WHERE user_id = ?";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "ii", $total_spins, $user_id);
                    if (mysqli_stmt_execute($stmt)) {
                        $_SESSION['msg'] = "✅ Total spins updated successfully!";
                    } else {
                        $_SESSION['msg'] = "❌ Failed to update spins.";
                    }
                    mysqli_stmt_close($stmt);
                }
                break;
                
            case 'update_amount':
                if (isset($_POST['invited_wheel_amount'])) {
                    $amount = floatval($_POST['invited_wheel_amount']);
                    $sql = "UPDATE shonu_turntable SET invited_wheel_amount = ?, updated_at = NOW() WHERE user_id = ?";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "di", $amount, $user_id);
                    if (mysqli_stmt_execute($stmt)) {
                        $_SESSION['msg'] = "✅ Invited wheel amount updated successfully!";
                    } else {
                        $_SESSION['msg'] = "❌ Failed to update amount.";
                    }
                    mysqli_stmt_close($stmt);
                }
                break;
                
            case 'delete':
                $sql = "DELETE FROM shonu_turntable WHERE user_id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "i", $user_id);
                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION['msg'] = "✅ Record deleted successfully!";
                } else {
                    $_SESSION['msg'] = "❌ Failed to delete record.";
                }
                mysqli_stmt_close($stmt);
                break;
                
            case 'add_record':
                if (isset($_POST['new_user_id']) && isset($_POST['new_total_spins']) && isset($_POST['new_invited_wheel_amount'])) {
                    $new_user_id = intval($_POST['new_user_id']);
                    $new_total_spins = intval($_POST['new_total_spins']);
                    $new_amount = floatval($_POST['new_invited_wheel_amount']);
                    
                    // Check if user already exists
                    $check_sql = "SELECT user_id FROM shonu_turntable WHERE user_id = ?";
                    $check_stmt = mysqli_prepare($conn, $check_sql);
                    mysqli_stmt_bind_param($check_stmt, "i", $new_user_id);
                    mysqli_stmt_execute($check_stmt);
                    $result = mysqli_stmt_get_result($check_stmt);
                    
                    if (mysqli_num_rows($result) > 0) {
                        $_SESSION['msg'] = "❌ User ID already exists!";
                    } else {
                        $sql = "INSERT INTO shonu_turntable (user_id, total_spins, invited_wheel_amount, created_at, updated_at) 
                                VALUES (?, ?, ?, NOW(), NOW())";
                        $stmt = mysqli_prepare($conn, $sql);
                        mysqli_stmt_bind_param($stmt, "iid", $new_user_id, $new_total_spins, $new_amount);
                        if (mysqli_stmt_execute($stmt)) {
                            $_SESSION['msg'] = "✅ New record added successfully!";
                        } else {
                            $_SESSION['msg'] = "❌ Failed to add new record.";
                        }
                        mysqli_stmt_close($stmt);
                    }
                    mysqli_stmt_close($check_stmt);
                }
                break;
        }
    }
    
    // Handle spin history actions
    if ($spin_id > 0) {
        switch ($action) {
            case 'delete_spin':
                $sql = "DELETE FROM shonu_turntable_spins WHERE id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "i", $spin_id);
                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION['msg'] = "✅ Spin record deleted successfully!";
                } else {
                    $_SESSION['msg'] = "❌ Failed to delete spin record.";
                }
                mysqli_stmt_close($stmt);
                break;
                
            case 'update_spin':
                if (isset($_POST['prize_amount']) && isset($_POST['user_name'])) {
                    $prize_amount = floatval($_POST['prize_amount']);
                    $user_name = mysqli_real_escape_string($conn, $_POST['user_name']);
                    
                    $sql = "UPDATE shonu_turntable_spins SET prize_amount = ?, user_name = ? WHERE id = ?";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "dsi", $prize_amount, $user_name, $spin_id);
                    if (mysqli_stmt_execute($stmt)) {
                        $_SESSION['msg'] = "✅ Spin record updated successfully!";
                    } else {
                        $_SESSION['msg'] = "❌ Failed to update spin record.";
                    }
                    mysqli_stmt_close($stmt);
                }
                break;
                
            case 'add_spin':
                if (isset($_POST['spin_user_id']) && isset($_POST['spin_user_name']) && isset($_POST['spin_prize_amount'])) {
                    $spin_user_id = intval($_POST['spin_user_id']);
                    $spin_user_name = mysqli_real_escape_string($conn, $_POST['spin_user_name']);
                    $spin_prize_amount = floatval($_POST['spin_prize_amount']);
                    
                    $sql = "INSERT INTO shonu_turntable_spins (user_id, user_name, prize_amount, spin_time) 
                            VALUES (?, ?, ?, NOW())";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "isd", $spin_user_id, $spin_user_name, $spin_prize_amount);
                    if (mysqli_stmt_execute($stmt)) {
                        $_SESSION['msg'] = "✅ New spin record added successfully!";
                    } else {
                        $_SESSION['msg'] = "❌ Failed to add spin record.";
                    }
                    mysqli_stmt_close($stmt);
                }
                break;
        }
    }
    
    // Handle first spin prize actions
switch ($action) {
    case 'add_first_spin_prize':
        if (isset($_POST['first_spin_amount'])) {
            $first_spin_amount = floatval($_POST['first_spin_amount']);
            
            // Check current count
            $count_sql = "SELECT COUNT(*) as count FROM invitewheel_first_spins WHERE type = 1";
            $count_result = mysqli_query($conn, $count_sql);
            $count_row = mysqli_fetch_assoc($count_result);
            $current_count = $count_row['count'];
            
            if ($current_count >= 4) {
                $_SESSION['msg'] = "❌ Maximum limit of 4 first spin prizes reached!";
            } else {
                $sql = "INSERT INTO invitewheel_first_spins (type, amount, created_at, updated_at) 
                        VALUES (1, ?, NOW(), NOW())";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "d", $first_spin_amount);
                
                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION['msg'] = "✅ First spin prize added successfully!";
                } else {
                    $_SESSION['msg'] = "❌ Failed to add first spin prize: " . mysqli_error($conn);
                }
                mysqli_stmt_close($stmt);
            }
        }
        break;

    case 'update_first_spin_prize':
        if (isset($_POST['first_prize_id']) && isset($_POST['first_spin_amount'])) {
            $first_prize_id = intval($_POST['first_prize_id']);
            $first_spin_amount = floatval($_POST['first_spin_amount']);
            
            $sql = "UPDATE invitewheel_first_spins SET amount = ?, updated_at = NOW() WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "di", $first_spin_amount, $first_prize_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['msg'] = "✅ First spin prize updated successfully!";
            } else {
                $_SESSION['msg'] = "❌ Failed to update first spin prize: " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        }
        break;

    case 'delete_first_spin_prize':
        if (isset($_GET['first_prize_id'])) {
            $first_prize_id = intval($_GET['first_prize_id']);
            $sql = "DELETE FROM invitewheel_first_spins WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $first_prize_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['msg'] = "✅ First spin prize deleted successfully!";
            } else {
                $_SESSION['msg'] = "❌ Failed to delete first spin prize.";
            }
            mysqli_stmt_close($stmt);
        }
        break;

        case 'update_wheel_image':
            if (isset($_POST['wheel_image_url'])) {
                $wheel_image_url = mysqli_real_escape_string($conn, $_POST['wheel_image_url']);
                
                // Update web_setting table
                $sql = "UPDATE web_setting SET invitewheel_img = ? WHERE id = 1";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "s", $wheel_image_url);
                
                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION['msg'] = "✅ Wheel image updated successfully!";
                } else {
                    $_SESSION['msg'] = "❌ Failed to update wheel image.";
                }
                mysqli_stmt_close($stmt);
            }
            break;

        case 'update_wheel_price':
            if (isset($_POST['wheel_price'])) {
                $wheel_price = floatval($_POST['wheel_price']);
                
                // Update web_setting table
                $sql = "UPDATE web_setting SET invitewheel_price = ? WHERE id = 1";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "d", $wheel_price);
                
                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION['msg'] = "✅ Wheel price updated successfully!";
                } else {
                    $_SESSION['msg'] = "❌ Failed to update wheel price.";
                }
                mysqli_stmt_close($stmt);
            }
            break;

        case 'update_withdrawal_rules':
            if (isset($_POST['withdrawal_rules'])) {
                $withdrawal_rules = mysqli_real_escape_string($conn, $_POST['withdrawal_rules']);
                
                // Update invite_wheel_mr table
                $sql = "UPDATE invite_wheel_mr SET withdraw_rules = ?, updated_at = NOW() WHERE type IN (1,2) LIMIT 1";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "s", $withdrawal_rules);
                
                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION['msg'] = "✅ Withdrawal rules updated successfully!";
                } else {
                    $_SESSION['msg'] = "❌ Failed to update withdrawal rules: " . mysqli_error($conn);
                }
                mysqli_stmt_close($stmt);
            }
            break;
    }
    
    header("Location: manage_invitewheel.php");
    exit;
}

// Get statistics for main table
$total_records = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM shonu_turntable"))['total'] ?? 0;
$total_spins = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_spins) as total FROM shonu_turntable"))['total'] ?? 0;
$total_amount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(invited_wheel_amount) as total FROM shonu_turntable"))['total'] ?? 0;
$avg_spins = $total_records > 0 ? round($total_spins / $total_records, 2) : 0;
$avg_amount = $total_records > 0 ? round($total_amount / $total_records, 2) : 0;

// Get statistics for spin history
$total_spin_records = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM shonu_turntable_spins"))['total'] ?? 0;
$total_prize_amount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(prize_amount) as total FROM shonu_turntable_spins"))['total'] ?? 0;
$avg_prize = $total_spin_records > 0 ? round($total_prize_amount / $total_spin_records, 2) : 0;

// Get prize counts for display - UPDATE THIS SECTION
$first_spin_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM invitewheel_first_spins WHERE type = 1"))['count'] ?? 0;
$regular_spin_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM invite_wheel_mr WHERE type = 2"))['count'] ?? 0;

// Get withdrawal rules
$withdrawal_rules = mysqli_fetch_assoc(mysqli_query($conn, "SELECT withdraw_rules FROM invite_wheel_mr WHERE type IN (1,2) LIMIT 1"));
$withdrawal_rules_content = $withdrawal_rules ? $withdrawal_rules['withdraw_rules'] : '';

// Get wheel image and price from web_setting
$web_setting = mysqli_fetch_assoc(mysqli_query($conn, "SELECT invitewheel_img, invitewheel_price FROM web_setting WHERE id = 1"));
$wheel_image_url = $web_setting ? $web_setting['invitewheel_img'] : '';
$wheel_price = $web_setting ? $web_setting['invitewheel_price'] : '500.00';

// Search and filter handling for main table
$search_condition = "";
$has_search = false;
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = mysqli_real_escape_string($conn, $_GET['search']);
    $search_condition = " WHERE user_id LIKE '%$search_term%'";
    $has_search = true;
} else {
    // Default: show no records until search
    $search_condition = " WHERE 1=0";
}

// Search and filter handling for spin history
$spin_search_condition = "";
if (isset($_GET['spin_search']) && !empty($_GET['spin_search'])) {
    $spin_search_term = mysqli_real_escape_string($conn, $_GET['spin_search']);
    $spin_search_condition = " WHERE user_id LIKE '%$spin_search_term%' OR user_name LIKE '%$spin_search_term%'";
}

// Sort handling for main table
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'user_id';
$order = isset($_GET['order']) ? $_GET['order'] : 'ASC';
$valid_sorts = ['user_id', 'total_spins', 'invited_wheel_amount', 'created_at', 'updated_at'];
$valid_orders = ['ASC', 'DESC'];

if (!in_array($sort, $valid_sorts)) $sort = 'user_id';
if (!in_array($order, $valid_orders)) $order = 'ASC';

// Sort handling for spin history
$spin_sort = isset($_GET['spin_sort']) ? $_GET['spin_sort'] : 'spin_time';
$spin_order = isset($_GET['spin_order']) ? $_GET['spin_order'] : 'DESC';
$valid_spin_sorts = ['id', 'user_id', 'user_name', 'prize_amount', 'spin_time'];
$valid_spin_orders = ['ASC', 'DESC'];

if (!in_array($spin_sort, $valid_spin_sorts)) $spin_sort = 'spin_time';
if (!in_array($spin_order, $valid_spin_orders)) $spin_order = 'DESC';

// Pagination for main table
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 25;
$valid_limits = [10, 25, 50, 100];
if (!in_array($limit, $valid_limits)) $limit = 25;

$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $limit;

// Pagination for spin history
$spin_limit = isset($_GET['spin_limit']) ? intval($_GET['spin_limit']) : 25;
if (!in_array($spin_limit, $valid_limits)) $spin_limit = 25;

$spin_current_page = isset($_GET['spin_page']) ? max(1, intval($_GET['spin_page'])) : 1;
$spin_offset = ($spin_current_page - 1) * $spin_limit;

// Get total count for pagination - main table
$count_sql = "SELECT COUNT(*) as total FROM shonu_turntable $search_condition";
$count_result = mysqli_query($conn, $count_sql);
$total_count = mysqli_fetch_assoc($count_result)['total'] ?? 0;

// Get total count for pagination - spin history
$spin_count_sql = "SELECT COUNT(*) as total FROM shonu_turntable_spins $spin_search_condition";
$spin_count_result = mysqli_query($conn, $spin_count_sql);
$total_spin_count = mysqli_fetch_assoc($spin_count_result)['total'] ?? 0;

// Get records - main table
$sql = "SELECT * FROM shonu_turntable $search_condition ORDER BY $sort $order LIMIT $limit OFFSET $offset";
$result = mysqli_query($conn, $sql);

// Get records - spin history
$spin_sql = "SELECT * FROM shonu_turntable_spins $spin_search_condition ORDER BY $spin_sort $spin_order LIMIT $spin_limit OFFSET $spin_offset";
$spin_result = mysqli_query($conn, $spin_sql);

$total_pages = ceil($total_count / $limit);
$total_spin_pages = ceil($total_spin_count / $spin_limit);
?>

<!DOCTYPE html>
<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr" 
    data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template" data-style="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Manage Invite Wheel Spin</title>
    <meta name="description" content="" />
    <link rel="icon" type="image/x-icon" href="assets/img/favicon/favicon.ico?v=20260814-admin" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&ampdisplay=swap" rel="stylesheet" />
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
    
    <style>
        .stats-card {
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
            height: 100%;
        }
        .stats-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .stat-value {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        .stat-label {
            font-size: 0.875rem;
            color: #6c757d;
            margin-bottom: 0.5rem;
        }
        .table-actions {
            white-space: nowrap;
        }
        .sortable {
            cursor: pointer;
        }
        .sortable:hover {
            background-color: #f8f9fa;
        }
        .tab-content {
            padding-top: 1rem;
        }
        .nav-tabs .nav-link.active {
            border-bottom: 2px solid #696cff;
            font-weight: 600;
        }
        .user-details-modal .modal-xl {
            max-width: 95%;
        }
        .spin-history-table {
            font-size: 0.875rem;
        }
        .prize-count-badge {
            font-size: 0.75rem;
            margin-left: 8px;
        }
        .withdrawal-rules-editor {
            min-height: 300px;
        }
    </style>
</head>

<body>
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <?php require_once("layout-menu.php"); ?>
            <div class="layout-page">
                <?php require_once("nav.php"); ?>
                <div class="content-wrapper">
                    <div class="container-xxl flex-grow-1 container-p-y">
                        
                        <!-- Success/Error Messages -->
                        <?php if (isset($_SESSION['msg'])): ?>
                            <div class="alert alert-<?= strpos($_SESSION['msg'], '❌') !== false ? 'danger' : 'success' ?> alert-dismissible fade show" role="alert">
                                <?= $_SESSION['msg'] ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                            <?php unset($_SESSION['msg']); ?>
                        <?php endif; ?>

                        <div class="row mb-4">
                            <!-- Statistics Cards -->
                            <div class="col-xl-2 col-md-4 col-6 mb-4">
                                <div class="card stats-card border-left-primary">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div class="card-info">
                                                <p class="stat-label">Total Users</p>
                                                <h4 class="stat-value text-primary"><?= number_format($total_records) ?></h4>
                                            </div>
                                            <div class="card-icon">
                                                <span class="badge bg-label-primary rounded p-2">
                                                    <i class="ri-database-2-line"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-xl-2 col-md-4 col-6 mb-4">
                                <div class="card stats-card border-left-success">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div class="card-info">
                                                <p class="stat-label">Total Spins</p>
                                                <h4 class="stat-value text-success"><?= number_format($total_spins) ?></h4>
                                            </div>
                                            <div class="card-icon">
                                                <span class="badge bg-label-success rounded p-2">
                                                    <i class="ri-refresh-line"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-xl-2 col-md-4 col-6 mb-4">
                                <div class="card stats-card border-left-info">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div class="card-info">
                                                <p class="stat-label">Total Amount</p>
                                                <h4 class="stat-value text-info">₹<?= number_format($total_amount, 2) ?></h4>
                                            </div>
                                            <div class="card-icon">
                                                <span class="badge bg-label-info rounded p-2">
                                                    <i class="ri-money-rupee-circle-line"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-xl-2 col-md-4 col-6 mb-4">
                                <div class="card stats-card border-left-warning">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div class="card-info">
                                                <p class="stat-label">Total Spin Records</p>
                                                <h4 class="stat-value text-warning"><?= number_format($total_spin_records) ?></h4>
                                            </div>
                                            <div class="card-icon">
                                                <span class="badge bg-label-warning rounded p-2">
                                                    <i class="ri-history-line"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-xl-2 col-md-4 col-6 mb-4">
                                <div class="card stats-card border-left-danger">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div class="card-info">
                                                <p class="stat-label">Total Prize Given</p>
                                                <h4 class="stat-value text-danger">₹<?= number_format($total_prize_amount, 2) ?></h4>
                                            </div>
                                            <div class="card-icon">
                                                <span class="badge bg-label-danger rounded p-2">
                                                    <i class="ri-gift-line"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-xl-2 col-md-4 col-6 mb-4">
                                <div class="card stats-card border-left-secondary">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div class="card-info">
                                                <p class="stat-label">Avg. Prize</p>
                                                <h4 class="stat-value text-secondary">₹<?= number_format($avg_prize, 2) ?></h4>
                                            </div>
                                            <div class="card-icon">
                                                <span class="badge bg-label-secondary rounded p-2">
                                                    <i class="ri-calculator-line"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Invite Wheel Settings Section -->
                        <div class="card mt-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">
                                    <i class="ri-settings-3-line me-2"></i>Invite Wheel Settings
                                </h5>
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPrizeModal">
                                    <i class="ri-add-line me-1"></i>Add Prize
                                </button>
                            </div>
                            <div class="card-body">
                                
                                <!-- Wheel Price Settings -->
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <h6 class="mb-3">Wheel Price Settings</h6>
                                        <form method="POST" action="?action=update_wheel_price">
                                            <div class="row g-3">
                                                <div class="col-md-8">
                                                    <label class="form-label">Wheel Main Price (₹)</label>
                                                    <input type="number" step="0.01" class="form-control" name="wheel_price" 
                                                           value="<?= htmlspecialchars($wheel_price) ?>" 
                                                           placeholder="500.00" required>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">&nbsp;</label>
                                                    <button type="submit" class="btn btn-primary w-100">Update Wheel Price</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                                <!-- Wheel Image Settings -->
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <h6 class="mb-3">Wheel Image Settings</h6>
                                        <form method="POST" action="?action=update_wheel_image">
                                            <div class="row g-3">
                                                <div class="col-md-8">
                                                    <label class="form-label">Wheel Image URL</label>
                                                    <input type="url" class="form-control" name="wheel_image_url" 
                                                           value="<?= htmlspecialchars($wheel_image_url) ?>" 
                                                           placeholder="https://example.com/wheel-image.png">
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">&nbsp;</label>
                                                    <button type="submit" class="btn btn-primary w-100">Update Wheel Image</button>
                                                </div>
                                            </div>
                                            <?php if (!empty($wheel_image_url)): ?>
                                                <div class="mt-2">
                                                    <small class="text-muted">Current Image:</small>
                                                    <div class="mt-1">
                                                        <img src="<?= htmlspecialchars($wheel_image_url) ?>" alt="Wheel Image" style="max-height: 100px; max-width: 100px;" class="img-thumbnail">
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </form>
                                    </div>
                                </div>
                                
<!--<div class="card-header d-flex justify-content-between align-items-center">-->
<!--    <h5 class="card-title mb-0">-->
<!--        <i class="ri-settings-3-line me-2"></i>Invite Wheel Settings-->
<!--    </h5>-->
<!--First SPin Prize Button-->
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addFirstSpinPrizeModal">
            <i class="ri-add-line me-1"></i>Add First Spin Prize
        </button>
        <!--<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPrizeModal">-->
        <!--    <i class="ri-add-line me-1"></i>Add Regular Prize-->
        <!--</button>-->
    </div>
</div>

<!-- Add First Spin Prize Button -->
<!--<div class="mt-3">-->
<!--    <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addFirstSpinPrizeModal">-->
<!--        <i class="ri-add-line me-1"></i>Add First Spin Prize-->
<!--    </button>-->
<!--</div>-->
                                
                                <!-- First Spin Prizes Section -->
<div class="row mb-4">
    <div class="col-12">
        <h6 class="mb-3">
            First Spin Prizes 
            <span class="badge bg-primary prize-count-badge"><?= $first_spin_count ?>/4</span>
        </h6>
        <?php if ($first_spin_count >= 4): ?>
            <div class="alert alert-warning">
                <i class="ri-alert-line me-2"></i>Maximum limit of 4 first spin prizes reached.
            </div>
        <?php endif; ?>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Amount</th>
                        <th>Created At</th>
                        <th>Updated At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $firstSpinPrizes = mysqli_query($conn, "SELECT * FROM invitewheel_first_spins WHERE type = 1 ORDER BY amount ASC");
                    while ($prize = mysqli_fetch_assoc($firstSpinPrizes)):
                    ?>
                        <tr>
                            <td>
                                <span class="badge bg-label-secondary">#<?= $prize['id'] ?></span>
                            </td>
                            <td>
                                <span class="badge bg-label-success">₹<?= number_format($prize['amount'], 2) ?></span>
                            </td>
                            <td>
                                <small class="text-muted">
                                    <?= date('d M Y H:i', strtotime($prize['created_at'])) ?>
                                </small>
                            </td>
                            <td>
                                <small class="text-muted">
                                    <?= date('d M Y H:i', strtotime($prize['updated_at'])) ?>
                                </small>
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-sm btn-icon btn-outline-primary edit-first-spin-prize" 
                                            data-bs-toggle="modal" data-bs-target="#editFirstSpinPrizeModal"
                                            data-id="<?= $prize['id'] ?>"
                                            data-amount="<?= $prize['amount'] ?>">
                                        <i class="ri-edit-line"></i>
                                    </button>
                                    <a href="manage_invitewheel.php?action=delete_first_spin_prize&first_prize_id=<?= $prize['id'] ?>" 
                                       class="btn btn-sm btn-icon btn-outline-danger"
                                       onclick="return confirm('Are you sure you want to delete this first spin prize?')">
                                        <i class="ri-delete-bin-line"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    
                    <?php if (mysqli_num_rows($firstSpinPrizes) === 0): ?>
                        <tr>
                            <td colspan="5" class="text-center py-3 text-muted">
                                No first spin prizes configured
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Add First Spin Prize Button -->
        <!--<div class="mt-3">-->
        <!--    <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addFirstSpinPrizeModal">-->
        <!--        <i class="ri-add-line me-1"></i>Add First Spin Prize-->
        <!--    </button>-->
        <!--</div>-->
    </div>
</div>
                                
                                <!-- Add First Spin Prize Modal -->
<div class="modal fade" id="addFirstSpinPrizeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add First Spin Prize</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="?action=add_first_spin_prize">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Prize Amount (₹) *</label>
                            <input type="number" step="0.01" class="form-control" name="first_spin_amount" required min="0" placeholder="0.00">
                            <small class="text-muted">This prize will be available for users' first spin</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add Prize</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit First Spin Prize Modal -->
<div class="modal fade" id="editFirstSpinPrizeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit First Spin Prize</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="?action=update_first_spin_prize" id="editFirstSpinPrizeForm">
                <div class="modal-body">
                    <input type="hidden" name="first_prize_id" id="edit_first_prize_id">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Prize Amount (₹) *</label>
                            <input type="number" step="0.01" class="form-control" name="first_spin_amount" id="edit_first_spin_amount" required min="0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update Prize</button>
                </div>
            </form>
        </div>
    </div>
</div>  

    <div class="d-flex gap-2">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPrizeModal">
            <i class="ri-add-line me-1"></i>Add Regular Prize
        </button>
    </div> 

                                <!-- Regular Spin Prizes -->
                                <div class="row">
                                    <div class="col-12">
                                        <h6 class="mb-3">
                                            Regular Spin Prizes 
                                            <span class="badge bg-primary prize-count-badge"><?= $regular_spin_count ?>/20</span>
                                        </h6>
                                        <?php if ($regular_spin_count >= 20): ?>
                                            <div class="alert alert-warning">
                                                <i class="ri-alert-line me-2"></i>Maximum limit of 20 regular spin prizes reached.
                                            </div>
                                        <?php endif; ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Amount</th>
                                                        <th>Probability</th>
                                                        <th>Is Win</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    $regularPrizes = mysqli_query($conn, "SELECT * FROM invite_wheel_mr WHERE type = 2 ORDER BY prob DESC");
                                                    while ($prize = mysqli_fetch_assoc($regularPrizes)):
                                                    ?>
                                                        <tr>
                                                            <td>
                                                                <span class="badge bg-label-<?= $prize['amount'] > 0 ? 'success' : 'secondary' ?>">
                                                                    ₹<?= number_format($prize['amount'], 2) ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-label-info"><?= number_format($prize['prob'] * 100, 1) ?>%</span>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-label-<?= $prize['is_win'] ? 'success' : 'danger' ?>">
                                                                    <?= $prize['is_win'] ? 'Win' : 'Lose' ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <div class="d-flex gap-2">
                                                                    <button type="button" class="btn btn-sm btn-icon btn-outline-primary edit-prize" 
                                                                            data-bs-toggle="modal" data-bs-target="#editPrizeModal"
                                                                            data-id="<?= $prize['id'] ?>"
                                                                            data-type="<?= $prize['type'] ?>"
                                                                            data-amount="<?= $prize['amount'] ?>"
                                                                            data-is-selected="<?= $prize['is_selected'] ?>"
                                                                            data-probability="<?= $prize['prob'] ?>"
                                                                            data-is-win="<?= $prize['is_win'] ?>">
                                                                        <i class="ri-edit-line"></i>
                                                                    </button>
                                                                    <a href="manage_invitewheel.php?action=delete_prize&id=<?= $prize['id'] ?>" 
                                                                       class="btn btn-sm btn-icon btn-outline-danger"
                                                                       onclick="return confirm('Are you sure you want to delete this prize?')">
                                                                        <i class="ri-delete-bin-line"></i>
                                                                    </a>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php endwhile; ?>
                                                    
                                                    <?php if (mysqli_num_rows($regularPrizes) === 0): ?>
                                                        <tr>
                                                            <td colspan="4" class="text-center py-3 text-muted">
                                                                No regular spin prizes configured
                                                            </td>
                                                        </tr>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <!-- Withdrawal Rules Settings -->
                                <div class="row mt-4">
                                    <div class="col-12">
                                        <h6 class="mb-3">Withdrawal Rules</h6>
                                        <form method="POST" action="?action=update_withdrawal_rules">
                                            <div class="row g-3">
                                                <div class="col-12">
                                                    <label class="form-label">Withdrawal Rules Content</label>
                                                    <textarea class="form-control withdrawal-rules-editor" name="withdrawal_rules" 
                                                              placeholder="Enter withdrawal rules content here..." 
                                                              rows="15"><?= htmlspecialchars($withdrawal_rules_content) ?></textarea>
                                                </div>
                                                <div class="col-12">
                                                    <button type="submit" class="btn btn-primary">Update Withdrawal Rules</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Rest of your existing code for tabs and tables remains the same -->
                        <!-- Tabs for Main Table and Spin History -->
                        <div class="card">
                            <div class="card-header">
                                <ul class="nav nav-tabs card-header-tabs" role="tablist">
                                    <li class="nav-item">
                                        <a class="nav-link active" data-bs-toggle="tab" href="#mainTable" role="tab">
                                            <i class="ri-database-2-line me-1"></i>User Records
                                        </a>
                                    </li>
                                    <!--<li class="nav-item">-->
                                    <!--    <a class="nav-link" data-bs-toggle="tab" href="#spinHistory" role="tab">-->
                                    <!--        <i class="ri-history-line me-1"></i>Spin History-->
                                    <!--    </a>-->
                                    <!--</li>-->
                                </ul>
                            </div>
                            <div class="card-body">
                                <div class="tab-content">
                                    
                                    <!-- Main Table Tab -->
                                    <div class="tab-pane fade show active" id="mainTable" role="tabpanel">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h6 class="card-title mb-0">User Spin Records</h6>
                                            <div class="d-flex gap-2">
                                                <form method="GET" class="d-flex gap-2">
                                                    <input type="hidden" name="tab" value="mainTable">
                                                    <input type="text" name="search" class="form-control" placeholder="Search by User ID..." 
                                                           value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                                                    <button type="submit" class="btn btn-outline-primary">
                                                        <i class="ri-search-line me-1"></i>Search
                                                    </button>
                                                    <?php if (isset($_GET['search'])): ?>
                                                        <a href="manage_invitewheel.php" class="btn btn-secondary">Clear</a>
                                                    <?php endif; ?>
                                                </form>
                                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRecordModal">
                                                    <i class="ri-add-line me-1"></i>Add New
                                                </button>
                                            </div>
                                        </div>

                                        <?php if ($has_search): ?>
                                            <!-- Pagination and Limit Controls -->
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <div class="d-flex align-items-center gap-2">
                                                    <span>Show:</span>
                                                    <select class="form-select form-select-sm" style="width: auto;" onchange="updateLimit(this.value, 'main')">
                                                        <?php foreach ($valid_limits as $l): ?>
                                                            <option value="<?= $l ?>" <?= $limit == $l ? 'selected' : '' ?>><?= $l ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <span>entries</span>
                                                </div>
                                                <div class="text-muted">
                                                    Showing <?= min($offset + 1, $total_count) ?> to <?= min($offset + $limit, $total_count) ?> of <?= $total_count ?> entries
                                                </div>
                                            </div>

                                            <div class="table-responsive">
                                                <table class="table table-hover">
                                                    <thead>
                                                        <tr>
                                                            <th class="sortable" onclick="sortTable('user_id', 'main')">
                                                                User ID 
                                                                <?php if ($sort == 'user_id'): ?>
                                                                    <i class="ri-arrow-<?= $order == 'ASC' ? 'up' : 'down' ?>-line"></i>
                                                                <?php endif; ?>
                                                            </th>
                                                            <th class="sortable" onclick="sortTable('total_spins', 'main')">
                                                                Total Spins
                                                                <?php if ($sort == 'total_spins'): ?>
                                                                    <i class="ri-arrow-<?= $order == 'ASC' ? 'up' : 'down' ?>-line"></i>
                                                                <?php endif; ?>
                                                            </th>
                                                            <th class="sortable" onclick="sortTable('invited_wheel_amount', 'main')">
                                                                Invited Wheel Amount
                                                                <?php if ($sort == 'invited_wheel_amount'): ?>
                                                                    <i class="ri-arrow-<?= $order == 'ASC' ? 'up' : 'down' ?>-line"></i>
                                                                <?php endif; ?>
                                                            </th>
                                                            <th class="sortable" onclick="sortTable('created_at', 'main')">
                                                                Created At
                                                                <?php if ($sort == 'created_at'): ?>
                                                                    <i class="ri-arrow-<?= $order == 'ASC' ? 'up' : 'down' ?>-line"></i>
                                                                <?php endif; ?>
                                                            </th>
                                                            <th class="sortable" onclick="sortTable('updated_at', 'main')">
                                                                Updated At
                                                                <?php if ($sort == 'updated_at'): ?>
                                                                    <i class="ri-arrow-<?= $order == 'ASC' ? 'up' : 'down' ?>-line"></i>
                                                                <?php endif; ?>
                                                            </th>
                                                            <th>Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                                            <tr>
                                                                <td>
                                                                    <strong><?= htmlspecialchars($row['user_id']) ?></strong>
                                                                </td>
                                                                <td>
                                                                    <span class="badge bg-label-primary"><?= number_format($row['total_spins']) ?></span>
                                                                </td>
                                                                <td>
                                                                    <span class="badge bg-label-success">₹<?= number_format($row['invited_wheel_amount'], 2) ?></span>
                                                                </td>
                                                                <td>
                                                                    <small class="text-muted">
                                                                        <?= date('d M Y H:i', strtotime($row['created_at'])) ?>
                                                                    </small>
                                                                </td>
                                                                <td>
                                                                    <small class="text-muted">
                                                                        <?= date('d M Y H:i', strtotime($row['updated_at'])) ?>
                                                                    </small>
                                                                </td>
                                                                <td>
                                                                    <div class="d-flex gap-2">
                                                                        <button type="button" class="btn btn-sm btn-icon btn-outline-info view-user-details" 
                                                                                data-bs-toggle="modal" data-bs-target="#viewUserDetailsModal"
                                                                                data-user-id="<?= $row['user_id'] ?>">
                                                                            <i class="ri-eye-line"></i>
                                                                        </button>
                                                                        <button type="button" class="btn btn-sm btn-icon btn-outline-primary edit-spins" 
                                                                                data-bs-toggle="modal" data-bs-target="#editSpinsModal"
                                                                                data-user-id="<?= $row['user_id'] ?>"
                                                                                data-total-spins="<?= $row['total_spins'] ?>">
                                                                            <i class="ri-refresh-line"></i>
                                                                        </button>
                                                                        <button type="button" class="btn btn-sm btn-icon btn-outline-warning edit-amount" 
                                                                                data-bs-toggle="modal" data-bs-target="#editAmountModal"
                                                                                data-user-id="<?= $row['user_id'] ?>"
                                                                                data-amount="<?= $row['invited_wheel_amount'] ?>">
                                                                            <i class="ri-money-rupee-circle-line"></i>
                                                                        </button>
                                                                        <a href="manage_invitewheel.php?action=delete&user_id=<?= $row['user_id'] ?>" 
                                                                           class="btn btn-sm btn-icon btn-outline-danger"
                                                                           onclick="return confirm('Are you sure you want to delete this record?')">
                                                                            <i class="ri-delete-bin-line"></i>
                                                                        </a>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        <?php endwhile; ?>
                                                        
                                                        <?php if (mysqli_num_rows($result) === 0): ?>
                                                            <tr>
                                                                <td colspan="6" class="text-center py-4">
                                                                    <div class="text-muted">
                                                                        <i class="ri-search-line display-4"></i>
                                                                        <p class="mt-2">No records found for your search</p>
                                                                        <a href="manage_invitewheel.php" class="btn btn-primary btn-sm">Clear Search</a>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        <?php endif; ?>
                                                    </tbody>
                                                </table>
                                            </div>

                                            <!-- Pagination -->
                                            <?php if ($total_pages > 1): ?>
                                                <nav aria-label="Page navigation" class="mt-4">
                                                    <ul class="pagination justify-content-center">
                                                        <!-- Previous Page -->
                                                        <li class="page-item <?= $current_page <= 1 ? 'disabled' : '' ?>">
                                                            <a class="page-link" href="<?= buildPaginationUrl($current_page - 1, $limit, $sort, $order, 'main') ?>" aria-label="Previous">
                                                                <i class="ri-arrow-left-s-line"></i>
                                                            </a>
                                                        </li>
                                                        
                                                        <!-- Page Numbers -->
                                                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                                            <li class="page-item <?= $i == $current_page ? 'active' : '' ?>">
                                                                <a class="page-link" href="<?= buildPaginationUrl($i, $limit, $sort, $order, 'main') ?>">
                                                                    <?= $i ?>
                                                                </a>
                                                            </li>
                                                        <?php endfor; ?>
                                                        
                                                        <!-- Next Page -->
                                                        <li class="page-item <?= $current_page >= $total_pages ? 'disabled' : '' ?>">
                                                            <a class="page-link" href="<?= buildPaginationUrl($current_page + 1, $limit, $sort, $order, 'main') ?>" aria-label="Next">
                                                                <i class="ri-arrow-right-s-line"></i>
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </nav>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <div class="text-center py-5">
                                                <div class="text-muted">
                                                    <i class="ri-search-line display-1"></i>
                                                    <h4 class="mt-3">Search User Records</h4>
                                                    <p class="mb-4">Enter a User ID to search for user records</p>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Spin History Tab -->
                                    <div class="tab-pane fade" id="spinHistory" role="tabpanel">
                                        <!-- ... (your existing spin history tab content remains same) ... -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php require_once("footer.php"); ?>
                    <div class="content-backdrop fade"></div>
                </div>
            </div>
        </div>
        <div class="layout-overlay layout-menu-toggle"></div>
        <div class="drag-target"></div>
    </div>

    <!-- Add Record Modal -->
    <div class="modal fade" id="addRecordModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New User Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="?action=add_record">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">User ID *</label>
                                <input type="number" class="form-control" name="new_user_id" required placeholder="Enter User ID">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Total Spins</label>
                                <input type="number" class="form-control" name="new_total_spins" value="0" min="0">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Invited Wheel Amount</label>
                                <input type="number" step="0.01" class="form-control" name="new_invited_wheel_amount" value="0.00" min="0">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add Record</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Spin Modal -->
    <div class="modal fade" id="addSpinModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Spin Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="?action=add_spin">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">User ID *</label>
                                <input type="number" class="form-control" name="spin_user_id" required placeholder="Enter User ID">
                            </div>
                            <div class="col-12">
                                <label class="form-label">User Name *</label>
                                <input type="text" class="form-control" name="spin_user_name" required placeholder="Enter User Name">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Prize Amount *</label>
                                <input type="number" step="0.01" class="form-control" name="spin_prize_amount" required placeholder="Enter Prize Amount" min="0">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add Spin</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Spins Modal -->
    <div class="modal fade" id="editSpinsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Total Spins</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="editSpinsForm">
                    <div class="modal-body">
                        <input type="hidden" name="user_id" id="edit_spins_user_id">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Total Spins</label>
                                <input type="number" class="form-control" name="total_spins" id="edit_total_spins" required min="0">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Update Spins</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Amount Modal -->
    <div class="modal fade" id="editAmountModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Invited Wheel Amount</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="editAmountForm">
                    <div class="modal-body">
                        <input type="hidden" name="user_id" id="edit_amount_user_id">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Invited Wheel Amount</label>
                                <input type="number" step="0.01" class="form-control" name="invited_wheel_amount" id="edit_invited_wheel_amount" required min="0">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Update Amount</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Spin Modal -->
    <div class="modal fade" id="editSpinModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Spin Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="editSpinForm">
                    <div class="modal-body">
                        <input type="hidden" name="spin_id" id="edit_spin_id">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">User ID</label>
                                <input type="number" class="form-control" id="edit_spin_user_id" readonly>
                            </div>
                            <div class="col-12">
                                <label class="form-label">User Name *</label>
                                <input type="text" class="form-control" name="user_name" id="edit_spin_user_name" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Prize Amount *</label>
                                <input type="number" step="0.01" class="form-control" name="prize_amount" id="edit_spin_prize_amount" required min="0">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Update Spin</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
  <!-- Add Prize Modal (ONLY FOR REGULAR SPIN) -->
<div class="modal fade" id="addPrizeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Regular Spin Prize</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="?action=add_prize">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Prize Amount (₹) *</label>
                            <input type="number" step="0.01" class="form-control" name="prize_amount" required min="0" placeholder="0.00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Probability (0.00 to 1.00) *</label>
                            <input type="number" step="0.01" class="form-control" name="probability" min="0" max="1" placeholder="0.20" required>
                            <small class="text-muted">Enter decimal value between 0.00 and 1.00 (e.g., 0.20 for 20%)</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Is Win</label>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="is_win" id="is_win" value="1" checked>
                                <label class="form-check-label" for="is_win">
                                    This is a winning prize
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add Prize</button>
                </div>
            </form>
        </div>
    </div>
</div>



    <!-- Edit Prize Modal -->
    <div class="modal fade" id="editPrizeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Prize</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="editPrizeForm">
                    <div class="modal-body">
                        <input type="hidden" name="prize_id" id="edit_prize_id">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Prize Type</label>
                                <input type="text" class="form-control" id="edit_prize_type_display" readonly>
                                <input type="hidden" name="prize_type" id="edit_prize_type">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Prize Amount (₹) *</label>
                                <input type="number" step="0.01" class="form-control" name="prize_amount" id="edit_prize_amount" required min="0">
                            </div>
                            
                            <!-- First Spin Specific Fields -->
                            <div class="col-12 edit-first-spin-fields" style="display: none;">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_selected" id="edit_is_selected" value="1">
                                    <label class="form-check-label" for="edit_is_selected">
                                        Is Selected (Will be randomly chosen for first spin)
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Regular Spin Specific Fields -->
                            <div class="col-12 edit-regular-spin-fields" style="display: none;">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Probability (0-1) *</label>
                                        <input type="number" step="0.01" class="form-control" name="probability" id="edit_probability" min="0" max="1" required>
                                        <small class="text-muted">Enter value between 0 and 1 (e.g., 0.35 for 35%)</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Is Win</label>
                                        <div class="form-check mt-2">
                                            <input class="form-check-input" type="checkbox" name="is_win" id="edit_is_win" value="1">
                                            <label class="form-check-label" for="edit_is_win">
                                                This is a winning prize
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Update Prize</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View User Details Modal -->
    <div class="modal fade user-details-modal" id="viewUserDetailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">User Spin History - <span id="modalUserName"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="userDetailsContent">
                        <div class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2">Loading user details...</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
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
        function sortTable(column, type) {
            const url = new URL(window.location.href);
            if (type === 'main') {
                const currentSort = '<?= $sort ?>';
                const currentOrder = '<?= $order ?>';
                let newOrder = 'ASC';
                
                if (currentSort === column) {
                    newOrder = currentOrder === 'ASC' ? 'DESC' : 'ASC';
                }
                
                url.searchParams.set('sort', column);
                url.searchParams.set('order', newOrder);
                url.searchParams.set('tab', 'mainTable');
            } else {
                const currentSort = '<?= $spin_sort ?>';
                const currentOrder = '<?= $spin_order ?>';
                let newOrder = 'ASC';
                
                if (currentSort === column) {
                    newOrder = currentOrder === 'ASC' ? 'DESC' : 'ASC';
                }
                
                url.searchParams.set('spin_sort', column);
                url.searchParams.set('spin_order', newOrder);
                url.searchParams.set('tab', 'spinHistory');
            }
            window.location.href = url.toString();
        }

        function updateLimit(newLimit, type) {
            const url = new URL(window.location.href);
            if (type === 'main') {
                url.searchParams.set('limit', newLimit);
                url.searchParams.set('tab', 'mainTable');
            } else {
                url.searchParams.set('spin_limit', newLimit);
                url.searchParams.set('tab', 'spinHistory');
            }
            window.location.href = url.toString();
        }

        // function togglePrizeFields() {
        //     const prizeType = document.getElementById('prize_type').value;
            
        //     // Hide all fields first
        //     document.querySelectorAll('.first-spin-fields, .regular-spin-fields').forEach(field => {
        //         field.style.display = 'none';
        //     });
            
        //     // Show relevant fields
        //     if (prizeType === '1') {
        //         document.querySelector('.first-spin-fields').style.display = 'block';
        //     } else if (prizeType === '2') {
        //         document.querySelector('.regular-spin-fields').style.display = 'block';
        //     }
        // }

        function loadUserSpinHistory(userId, page = 1) {
            $.ajax({
                url: `api/get_user_spin_history.php?user_id=${userId}&page=${page}`,
                type: 'GET',
                dataType: 'html',
                success: function(data) {
                    $('#userDetailsContent').html(data);
                },
                error: function() {
                    $('#userDetailsContent').html(`
                        <div class="alert alert-danger">
                            <i class="ri-error-warning-line me-2"></i>
                            Failed to load user spin history. Please try again.
                        </div>
                    `);
                }
            });
        }

        $(document).ready(function() {
            // Set active tab based on URL parameter
            const urlParams = new URLSearchParams(window.location.search);
            const activeTab = urlParams.get('tab');
            if (activeTab) {
                $('.nav-tabs a[href="#' + activeTab + '"]').tab('show');
            }

            // Edit spins modal handler
            $('.edit-spins').on('click', function() {
                const userId = $(this).data('user-id');
                const totalSpins = $(this).data('total-spins');
                
                $('#edit_spins_user_id').val(userId);
                $('#edit_total_spins').val(totalSpins);
                $('#editSpinsForm').attr('action', '?action=update_spins&user_id=' + userId);
            });
            
            // Edit first spin prize modal handler
$(document).on('click', '.edit-first-spin-prize', function() {
    const prizeId = $(this).data('id');
    const amount = $(this).data('amount');
    
    console.log('Editing first spin prize:', prizeId, amount); // Debug ke liye
    
    $('#edit_first_prize_id').val(prizeId);
    $('#edit_first_spin_amount').val(amount);
    
    // Form action set karna zaroori nahi, kyunki form mein already action hai
    // $('#editFirstSpinPrizeForm').attr('action', '?action=update_first_spin_prize');
});

            // Edit amount modal handler
            $('.edit-amount').on('click', function() {
                const userId = $(this).data('user-id');
                const amount = $(this).data('amount');
                
                $('#edit_amount_user_id').val(userId);
                $('#edit_invited_wheel_amount').val(amount);
                $('#editAmountForm').attr('action', '?action=update_amount&user_id=' + userId);
            });

            // Edit spin record modal handler
            $('.edit-spin-record').on('click', function() {
                const spinId = $(this).data('spin-id');
                const userId = $(this).data('user-id');
                const userName = $(this).data('user-name');
                const prizeAmount = $(this).data('prize-amount');
                
                $('#edit_spin_id').val(spinId);
                $('#edit_spin_user_id').val(userId);
                $('#edit_spin_user_name').val(userName);
                $('#edit_spin_prize_amount').val(prizeAmount);
                $('#editSpinForm').attr('action', '?action=update_spin&spin_id=' + spinId);
            });

            // Edit prize modal handler - COMPLETELY FIXED VERSION
            $('.edit-prize').on('click', function() {
                const prizeId = $(this).data('id');
                const prizeType = $(this).data('type');
                const amount = $(this).data('amount');
                const isSelected = $(this).data('is-selected');
                const probability = $(this).data('probability');
                const isWin = $(this).data('is-win');
                
                console.log('Editing prize:', {prizeId, prizeType, amount, isSelected, probability, isWin});
                
                $('#edit_prize_id').val(prizeId);
                $('#edit_prize_type').val(prizeType);
                $('#edit_prize_type_display').val(prizeType == 1 ? 'First Spin Prize' : 'Regular Spin Prize');
                $('#edit_prize_amount').val(amount);
                
                // Reset and show/hide fields based on type
                $('.edit-first-spin-fields').hide();
                $('.edit-regular-spin-fields').hide();
                
                if (prizeType == 1) {
                    $('.edit-first-spin-fields').show();
                    $('#edit_is_selected').prop('checked', isSelected == 1);
                    // Remove required from probability field for first spin
                    $('#edit_probability').prop('required', false);
                    $('#edit_probability').val('0.00');
                } else {
                    $('.edit-regular-spin-fields').show();
                    $('#edit_probability').val(parseFloat(probability).toFixed(2));
                    $('#edit_is_win').prop('checked', isWin == 1);
                    // Add required to probability field for regular spin
                    $('#edit_probability').prop('required', true);
                }
                
                $('#editPrizeForm').attr('action', '?action=update_prize');
            });

            // View user details modal handler
            $('.view-user-details').on('click', function() {
                const userId = $(this).data('user-id');
                $('#modalUserName').text('User ID: ' + userId);
                loadUserSpinHistory(userId);
            });

            // Handle pagination in user details modal
            $(document).on('click', '.user-spin-pagination a', function(e) {
                e.preventDefault();
                const page = $(this).data('page');
                const userId = $('#modalUserName').text().replace('User ID: ', '');
                loadUserSpinHistory(userId, page);
            });
        });
    </script>
</body>
</html>

<?php
// Helper function to build pagination URLs
function buildPaginationUrl($page, $limit, $sort, $order, $type) {
    $url = 'manage_invitewheel.php?';
    
    if ($type === 'main') {
        $url .= "page=$page&limit=$limit&sort=$sort&order=$order&tab=mainTable";
        if (isset($_GET['search'])) {
            $url .= "&search=" . urlencode($_GET['search']);
        }
    } else {
        $url .= "spin_page=$page&spin_limit=$limit&spin_sort=$sort&spin_order=$order&tab=spinHistory";
        if (isset($_GET['spin_search'])) {
            $url .= "&spin_search=" . urlencode($_GET['spin_search']);
        }
    }
    
    return $url;
}
?>