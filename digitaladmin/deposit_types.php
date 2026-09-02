<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (empty($_SESSION['unohs'])) {
    header("location: api/login.php?msg=unauthorized");
    exit();
}

include("api/conn.php");

mysqli_query($conn, "SET NAMES 'utf8mb4'");
mysqli_query($conn, "SET CHARACTER SET utf8mb4");
mysqli_query($conn, "SET SESSION collation_connection = 'utf8mb4_unicode_ci'");



// Handle status change via GET
if (isset($_GET['toggle_status'])) {
    $id = (int)$_GET['toggle_status'];
    
    $result = mysqli_query($conn, "SELECT status FROM payment_types WHERE id = '$id'");
    if ($result && mysqli_num_rows($result) > 0) {
        $currentStatus = mysqli_fetch_assoc($result);
        $newStatus = ($currentStatus['status'] == 'Active') ? 'Inactive' : 'Active';
        
        mysqli_query($conn, "UPDATE payment_types SET status = '$newStatus' WHERE id = '$id'");
        
        $_SESSION['message'] = 'Status updated successfully!';
        $_SESSION['message_type'] = 'success';
    }
    
    header("Location: deposit_types.php");
    exit;
}

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? null;

    if ($action === 'add') {
        $payID = (int)$_POST['payID'];
        $payTypeID = (int)$_POST['payTypeID'];
        $payName = mysqli_real_escape_string($conn, $_POST['payName']);
        $paySysName = mysqli_real_escape_string($conn, $_POST['paySysName']);
        $payNameUrl = mysqli_real_escape_string($conn, $_POST['payNameUrl']);
        $payNameUrl2 = mysqli_real_escape_string($conn, $_POST['payNameUrl2']);
        $minPrice = (float)$_POST['minPrice'];
        $maxPrice = (float)$_POST['maxPrice'];
        $scope = mysqli_real_escape_string($conn, $_POST['scope']);
        $typeName = mysqli_real_escape_string($conn, $_POST['typeName']);
        $typeNameCode = (int)$_POST['typeNameCode'];
        $maxRechargeRifts = (float)$_POST['maxRechargeRifts'];
        $sort = (int)$_POST['sort'];
        $status = mysqli_real_escape_string($conn, $_POST['status']);

        // Check if payID already exists
        $checkResult = mysqli_query($conn, "SELECT id FROM payment_types WHERE payID = '$payID'");
        if (mysqli_num_rows($checkResult) > 0) {
            $_SESSION['message'] = 'Payment ID already exists!';
            $_SESSION['message_type'] = 'error';
        } else {
            $sql = "INSERT INTO payment_types (payID, payTypeID, payName, paySysName, payNameUrl, payNameUrl2, minPrice, maxPrice, scope, typeName, typeNameCode, maxRechargeRifts, sort, status) 
                    VALUES ('$payID', '$payTypeID', '$payName', '$paySysName', '$payNameUrl', '$payNameUrl2', '$minPrice', '$maxPrice', '$scope', '$typeName', '$typeNameCode', '$maxRechargeRifts', '$sort', '$status')";
            
            if (mysqli_query($conn, $sql)) {
                $_SESSION['message'] = 'Payment type added successfully!';
                $_SESSION['message_type'] = 'success';
            } else {
                $_SESSION['message'] = 'Insert failed: ' . mysqli_error($conn);
                $_SESSION['message_type'] = 'error';
            }
        }
    } 
    elseif ($action === 'update') {
        $id = (int)$_POST['id'];
        $payID = (int)$_POST['payID'];
        $payTypeID = (int)$_POST['payTypeID'];
        $payName = mysqli_real_escape_string($conn, $_POST['payName']);
        $paySysName = mysqli_real_escape_string($conn, $_POST['paySysName']);
        $payNameUrl = mysqli_real_escape_string($conn, $_POST['payNameUrl']);
        $payNameUrl2 = mysqli_real_escape_string($conn, $_POST['payNameUrl2']);
        $minPrice = (float)$_POST['minPrice'];
        $maxPrice = (float)$_POST['maxPrice'];
        $scope = mysqli_real_escape_string($conn, $_POST['scope']);
        $typeName = mysqli_real_escape_string($conn, $_POST['typeName']);
        $typeNameCode = (int)$_POST['typeNameCode'];
        $maxRechargeRifts = (float)$_POST['maxRechargeRifts'];
        $sort = (int)$_POST['sort'];
        $status = mysqli_real_escape_string($conn, $_POST['status']);

        $sql = "UPDATE payment_types SET 
                payID = '$payID',
                payTypeID = '$payTypeID',
                payName = '$payName',
                paySysName = '$paySysName',
                payNameUrl = '$payNameUrl',
                payNameUrl2 = '$payNameUrl2',
                minPrice = '$minPrice',
                maxPrice = '$maxPrice',
                scope = '$scope',
                typeName = '$typeName',
                typeNameCode = '$typeNameCode',
                maxRechargeRifts = '$maxRechargeRifts',
                sort = '$sort',
                status = '$status'
                WHERE id = '$id'";
        
        if (mysqli_query($conn, $sql)) {
            $_SESSION['message'] = 'Payment type updated successfully!';
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = 'Update failed: ' . mysqli_error($conn);
            $_SESSION['message_type'] = 'error';
        }
    } 
    elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $sql = "DELETE FROM payment_types WHERE id = '$id'";
        
        if (mysqli_query($conn, $sql)) {
            $_SESSION['message'] = 'Payment type deleted successfully!';
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = 'Delete failed: ' . mysqli_error($conn);
            $_SESSION['message_type'] = 'error';
        }
    }

    header("Location: deposit_types.php");
    exit;
}

// Get edit data if editing
$editData = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $editRes = mysqli_query($conn, "SELECT * FROM payment_types WHERE id = $editId");
    if ($editRes && mysqli_num_rows($editRes) > 0) {
        $editData = mysqli_fetch_assoc($editRes);
    }
}

// Fetch all payment types
$paymentTypes = [];
$result = mysqli_query($conn, "SELECT * FROM payment_types ORDER BY sort ASC, payID ASC");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $paymentTypes[] = $row;
    }
}

// Get statistics
$totalTypes = count($paymentTypes);
$activeTypes = 0;
$inactiveTypes = 0;

foreach ($paymentTypes as $type) {
    if ($type['status'] == 'Active') {
        $activeTypes++;
    } else {
        $inactiveTypes++;
    }
}

// Get current deposit status from web_setting table
$allowDepositQuery = "SELECT allow_deposit FROM web_setting LIMIT 1";
$allowDepositResult = mysqli_query($conn, $allowDepositQuery);
$allowDeposit = 1; // Default value

if ($allowDepositResult && mysqli_num_rows($allowDepositResult) > 0) {
    $settingRow = mysqli_fetch_assoc($allowDepositResult);
    $allowDeposit = intval($settingRow['allow_deposit']);
}

// Check if allow_deposit column exists, if not create it
$checkDepositColumn = "SHOW COLUMNS FROM web_setting LIKE 'allow_deposit'";
$columnResult = mysqli_query($conn, $checkDepositColumn);
if (!$columnResult || mysqli_num_rows($columnResult) == 0) {
    $addColumnQuery = "ALTER TABLE web_setting ADD COLUMN allow_deposit INT DEFAULT 1";
    mysqli_query($conn, $addColumnQuery);
    mysqli_query($conn, "UPDATE web_setting SET allow_deposit = 1 LIMIT 1");
    $allowDeposit = 1;
}

// Toggle function
if (isset($_GET['toggle_deposit'])) {
    $newStatus = $allowDeposit == 1 ? 0 : 1;
    $toggleQuery = "UPDATE web_setting SET allow_deposit = '$newStatus' LIMIT 1";
    if (mysqli_query($conn, $toggleQuery)) {
        $_SESSION['message'] = 'Deposit status updated successfully!';
        $_SESSION['message_type'] = 'success';
        header("Location: deposit_types.php");
        exit;
    }
}

?>

<!DOCTYPE html>
<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr"
    data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template" data-style="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Payment Types Management</title>
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
    
    <style>
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            text-align: center;
        }
        .stats-value {
            font-size: 24px;
            font-weight: bold;
            display: block;
        }
        .stats-label {
            font-size: 14px;
            opacity: 0.9;
        }
        .edit-form-container {
            background: #e3f2fd;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #2196f3;
        }
        .payment-icon {
            width: 40px;
            height: 40px;
            object-fit: contain;
            border-radius: 5px;
        }
        .badge-active {
            background-color: #28a745;
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
        }
        .badge-inactive {
            background-color: #6c757d;
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
        }
        .badge-active:hover {
            background-color: #218838;
            transform: scale(1.05);
        }
        .badge-inactive:hover {
            background-color: #5a6268;
            transform: scale(1.05);
        }
        .search-filter-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .action-buttons {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        .btn-sm {
            padding: 4px 8px;
            font-size: 12px;
        }
        .status-badge {
            display: inline-block;
            min-width: 80px;
            text-align: center;
        }
        .amount-display {
            font-size: 12px;
            color: #666;
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
                        <!-- Display Messages -->
                        <div id="messageContainer">
                            <?php if (isset($_SESSION['message'])): ?>
                                <div class="alert alert-<?= $_SESSION['message_type'] == 'success' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
                                    <?= $_SESSION['message'] ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                                <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Display Messages -->
                        <div id="messageContainer">
                            <?php if (isset($_SESSION['message'])): ?>
                                <div class="alert alert-<?= $_SESSION['message_type'] == 'success' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
                                    <?= $_SESSION['message'] ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                                <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
                            <?php endif; ?>
                        </div>

                        <!-- Statistics Cards -->
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="stats-card">
                                    <span class="stats-value"><?= $totalTypes ?></span>
                                    <span class="stats-label">Total Payment Types</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="stats-card" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                                    <span class="stats-value"><?= $activeTypes ?></span>
                                    <span class="stats-label">Active Types</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="stats-card" style="background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);">
                                    <span class="stats-value"><?= $inactiveTypes ?></span>
                                    <span class="stats-label">Inactive Types</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Global Deposit Control Section -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="ri-toggle-line me-2"></i>
                    Global Deposit Control
                </h5>
            </div>
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h6 class="mb-1">Allow Deposits System-Wide</h6>
                        <p class="text-muted mb-0">
                            This setting controls whether users can make deposit requests or not.
                            When disabled, all deposit options will be hidden from users.
                        </p>
                    </div>
                    <div class="col-md-6 text-end">
                        <div class="d-flex align-items-center justify-content-end gap-3">
                            <span class="fs-6 <?= $allowDeposit == 1 ? 'text-success' : 'text-danger' ?>">
                                <i class="ri-<?= $allowDeposit == 1 ? 'check' : 'close' ?>-circle-fill me-1"></i>
                                <?= $allowDeposit == 1 ? 'Deposits Allowed' : 'Deposits Not Allowed' ?>
                            </span>
                            <a href="?toggle_deposit=1" 
                               class="btn btn-lg <?= $allowDeposit == 1 ? 'btn-danger' : 'btn-success' ?>"
                               onclick="return confirm('Are you sure you want to <?= $allowDeposit == 1 ? 'disable' : 'enable' ?> deposits system-wide?')">
                                <i class="ri-toggle-<?= $allowDeposit == 1 ? 'off' : 'on' ?>-line me-1"></i>
                                <?= $allowDeposit == 1 ? 'Disable Deposits' : 'Enable Deposits' ?>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Status Indicator -->
                <div class="mt-3">
                    <div class="alert <?= $allowDeposit == 1 ? 'alert-success' : 'alert-danger' ?> mb-0">
                        <div class="d-flex align-items-center">
                            <i class="ri-<?= $allowDeposit == 1 ? 'check' : 'close' ?>-circle-line fs-4 me-2"></i>
                            <div>
                                <strong>Current Status:</strong> 
                                <?php if ($allowDeposit == 1): ?>
                                    <span class="text-success">Deposits are enabled.</span> 
                                    Users can submit deposit requests through all active methods.
                                <?php else: ?>
                                    <span class="text-danger">Deposits are disabled.</span> 
                                    Users cannot submit any deposit requests.
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

                        <div class="row">
                            <!-- Payment Type Form -->
                            <div class="col-12">
                                <?php if (isset($_GET['edit']) && $editData): ?>
                                <div class="edit-form-container">
                                    <h4 class="text-primary mb-3"><i class="ri-edit-line ri-16px me-2"></i>Editing: <?= htmlspecialchars($editData['payName']) ?></h4>
                                <?php endif; ?>
                                
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <i class="ri-money-dollar-circle-line ri-16px me-2"></i>
                                            <?= (isset($_GET['edit']) && $editData) ? 'Edit Payment Type' : 'Add New Payment Type' ?>
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <form method="POST" class="row">
                                            <?php if (isset($_GET['edit']) && $editData): ?>
                                                <input type="hidden" name="id" value="<?= $editData['id'] ?>">
                                                <input type="hidden" name="action" value="update">
                                            <?php else: ?>
                                                <input type="hidden" name="action" value="add">
                                            <?php endif; ?>
                                            
                                            <div class="col-md-3 mb-3">
                                                <label for="payID" class="form-label">Payment ID *</label>
                                                <input type="number" class="form-control" id="payID" name="payID" 
                                                       value="<?= htmlspecialchars($editData['payID'] ?? '') ?>" required 
                                                       placeholder="Unique Payment ID">
                                            </div>

                                            <div class="col-md-3 mb-3">
                                                <label for="payTypeID" class="form-label">Pay Type ID</label>
                                                <input type="number" class="form-control" id="payTypeID" name="payTypeID" 
                                                       value="<?= htmlspecialchars($editData['payTypeID'] ?? '0') ?>" 
                                                       placeholder="0">
                                            </div>

                                            <div class="col-md-3 mb-3">
                                                <label for="payName" class="form-label">Payment Name *</label>
                                                <input type="text" class="form-control" id="payName" name="payName" 
                                                       value="<?= htmlspecialchars($editData['payName'] ?? '') ?>" required 
                                                       placeholder="e.g., Easy Paisa, Jazz Cash">
                                            </div>

                                            <div class="col-md-3 mb-3">
                                                <label for="paySysName" class="form-label">System Name *</label>
                                                <input type="text" class="form-control" id="paySysName" name="paySysName" 
                                                       value="<?= htmlspecialchars($editData['paySysName'] ?? '') ?>" required 
                                                       placeholder="e.g., Online Pay, USDT">
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label for="payNameUrl" class="form-label">Icon URL 1</label>
                                                <input type="url" class="form-control" id="payNameUrl" name="payNameUrl" 
                                                       value="<?= htmlspecialchars($editData['payNameUrl'] ?? '') ?>" 
                                                       placeholder="https://example.com/icon1.png">
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label for="payNameUrl2" class="form-label">Icon URL 2</label>
                                                <input type="url" class="form-control" id="payNameUrl2" name="payNameUrl2" 
                                                       value="<?= htmlspecialchars($editData['payNameUrl2'] ?? '') ?>" 
                                                       placeholder="https://example.com/icon2.png">
                                            </div>

                                            <div class="col-md-3 mb-3">
                                                <label for="minPrice" class="form-label">Minimum Price</label>
                                                <input type="number" step="0.01" class="form-control" id="minPrice" name="minPrice" 
                                                       value="<?= htmlspecialchars($editData['minPrice'] ?? '0.00') ?>" 
                                                       placeholder="0.00">
                                            </div>

                                            <div class="col-md-3 mb-3">
                                                <label for="maxPrice" class="form-label">Maximum Price</label>
                                                <input type="number" step="0.01" class="form-control" id="maxPrice" name="maxPrice" 
                                                       value="<?= htmlspecialchars($editData['maxPrice'] ?? '0.00') ?>" 
                                                       placeholder="0.00">
                                            </div>

                                            <div class="col-md-3 mb-3">
                                                <label for="maxRechargeRifts" class="form-label">Max Recharge Rifts (%)</label>
                                                <input type="number" step="0.0001" class="form-control" id="maxRechargeRifts" name="maxRechargeRifts" 
                                                       value="<?= htmlspecialchars($editData['maxRechargeRifts'] ?? '0.0000') ?>" 
                                                       placeholder="0.0300">
                                            </div>

                                            <div class="col-md-3 mb-3">
                                                <label for="sort" class="form-label">Sort Order</label>
                                                <input type="number" class="form-control" id="sort" name="sort" 
                                                       value="<?= htmlspecialchars($editData['sort'] ?? '0') ?>" 
                                                       placeholder="9">
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="scope" class="form-label">Scope</label>
                                                <input type="text" class="form-control" id="scope" name="scope" 
                                                       value="<?= htmlspecialchars($editData['scope'] ?? '') ?>" 
                                                       placeholder="Optional scope">
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="typeName" class="form-label">Type Name *</label>
                                                <input type="text" class="form-control" id="typeName" name="typeName" 
                                                       value="<?= htmlspecialchars($editData['typeName'] ?? '') ?>" required 
                                                       placeholder="e.g., Easy Paisa, Jazz Cash">
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="typeNameCode" class="form-label">Type Name Code</label>
                                                <input type="number" class="form-control" id="typeNameCode" name="typeNameCode" 
                                                       value="<?= htmlspecialchars($editData['typeNameCode'] ?? '0') ?>" 
                                                       placeholder="0">
                                            </div>

                                            <div class="col-md-3 mb-3">
                                                <label for="status" class="form-label">Status</label>
                                                <select class="form-select" id="status" name="status">
                                                    <option value="Active" <?= ($editData['status'] ?? 'Active') == 'Active' ? 'selected' : '' ?>>Active</option>
                                                    <option value="Inactive" <?= ($editData['status'] ?? 'Active') == 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                                                </select>
                                            </div>

                                            <div class="col-12">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="ri-save-line ri-16px me-2"></i>
                                                    <?= (isset($_GET['edit']) && $editData) ? 'Update Payment Type' : 'Add Payment Type' ?>
                                                </button>
                                                <?php if (isset($_GET['edit']) && $editData): ?>
                                                    <a href="deposit_types.php" class="btn btn-secondary">
                                                        <i class="ri-close-line ri-16px me-2"></i>Cancel Edit
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                
                                <?php if (isset($_GET['edit']) && $editData): ?>
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- Search and Filter Section -->
                            <div class="col-12">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h5 class="card-title mb-0"><i class="ri-search-line ri-16px me-2"></i>Search & Filter</h5>
                                            <span class="badge bg-primary">
                                                <?= $totalTypes ?> Total Types
                                            </span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="searchInput" class="form-label">Search Payment Types</label>
                                                <input type="text" class="form-control" id="searchInput" placeholder="Search by name or ID...">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="statusFilter" class="form-label">Filter by Status</label>
                                                <select class="form-select" id="statusFilter">
                                                    <option value="all">All Status</option>
                                                    <option value="Active">Active Only</option>
                                                    <option value="Inactive">Inactive Only</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-12">
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-outline-primary sort-btn active" data-sort="all">
                                                        <i class="ri-list-check ri-16px me-2"></i>All
                                                    </button>
                                                    <button type="button" class="btn btn-outline-success sort-btn" data-sort="active">
                                                        <i class="ri-checkbox-circle-line ri-16px me-2"></i>Active
                                                    </button>
                                                    <button type="button" class="btn btn-outline-secondary sort-btn" data-sort="inactive">
                                                        <i class="ri-close-circle-line ri-16px me-2"></i>Inactive
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Payment Types Table -->
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0"><i class="ri-money-dollar-circle-line ri-16px me-2"></i>Payment Types List</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-bordered">
                                                <thead class="table-dark">
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Pay ID</th>
                                                        <th>Payment Name</th>
                                                        <th>Icons</th>
                                                        <th>System Name</th>
                                                        <th>Min/Max Price</th>
                                                        <th>Recharge Rifts</th>
                                                        <th>Sort</th>
                                                        <th>Status</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="paymentTableBody">
                                                    <?php
                                                    if (count($paymentTypes) > 0) {
                                                        $count = 1;
                                                        foreach ($paymentTypes as $type) {
                                                            echo "<tr>";
                                                            echo "<td>" . $count++ . "</td>";
                                                            echo "<td><strong>" . $type['payID'] . "</strong></td>";
                                                            echo "<td>
                                                                <strong>" . htmlspecialchars($type['payName']) . "</strong><br>
                                                                <small class='text-muted'>" . htmlspecialchars($type['typeName']) . "</small>
                                                            </td>";
                                                            echo "<td>
                                                                <div class='d-flex gap-2'>
                                                                    " . (!empty($type['payNameUrl']) ? "<img src='" . htmlspecialchars($type['payNameUrl']) . "' class='payment-icon' alt='Icon 1'>" : "<span class='text-muted'>No Icon</span>") . "
                                                                    " . (!empty($type['payNameUrl2']) ? "<img src='" . htmlspecialchars($type['payNameUrl2']) . "' class='payment-icon' alt='Icon 2'>" : "") . "
                                                                </div>
                                                            </td>";
                                                            echo "<td>" . htmlspecialchars($type['paySysName']) . "</td>";
                                                            echo "<td>
                                                                <div class='amount-display'>
                                                                    Min: ₹" . number_format($type['minPrice'], 2) . "<br>
                                                                    Max: ₹" . number_format($type['maxPrice'], 2) . "
                                                                </div>
                                                            </td>";
                                                            echo "<td>" . ($type['maxRechargeRifts'] * 100) . "%</td>";
                                                            echo "<td>" . $type['sort'] . "</td>";
                                                            echo "<td>
                                                                <button type='button' class='status-badge " . ($type['status'] == 'Active' ? 'badge-active' : 'badge-inactive') . "' 
                                                                        onclick=\"toggleStatus(" . $type['id'] . ")\"
                                                                        title='Click to toggle status'>
                                                                    " . $type['status'] . "
                                                                </button>
                                                            </td>";
                                                            echo "<td>
                                                                <div class='action-buttons'>
                                                                    <a href='?edit=" . $type['id'] . "' class='btn btn-warning btn-sm' title='Edit'>
                                                                        <i class='ri-edit-line ri-14px'></i>
                                                                    </a>
                                                                    <form method='POST' style='display:inline;' onsubmit='return confirm(\"Are you sure you want to delete this payment type?\")'>
                                                                        <input type='hidden' name='action' value='delete'>
                                                                        <input type='hidden' name='id' value='" . $type['id'] . "'>
                                                                        <button type='submit' class='btn btn-danger btn-sm' title='Delete'>
                                                                            <i class='ri-delete-bin-line ri-14px'></i>
                                                                        </button>
                                                                    </form>
                                                                </div>
                                                            </td>";
                                                            echo "</tr>";
                                                        }
                                                    } else {
                                                        echo "<tr><td colspan='10' class='text-center'>No payment types found. Add your first payment type above.</td></tr>";
                                                    }
                                                    ?>
                                                </tbody>
                                            </table>
                                        </div>
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
        // Toggle status function
        function toggleStatus(id) {
            if (confirm('Are you sure you want to change the status?')) {
                window.location.href = '?toggle_status=' + id;
            }
        }

        // Search and Filter Functionality
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const statusFilter = document.getElementById('statusFilter');
            const sortButtons = document.querySelectorAll('.sort-btn');
            const tableBody = document.getElementById('paymentTableBody');
            const originalRows = Array.from(tableBody.querySelectorAll('tr'));

            // Search functionality
            searchInput.addEventListener('input', filterTable);
            statusFilter.addEventListener('change', filterTable);

            // Sort buttons functionality
            sortButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const sortType = this.getAttribute('data-sort');
                    
                    // Remove active class from all buttons
                    sortButtons.forEach(btn => btn.classList.remove('active', 'btn-primary', 'btn-success', 'btn-secondary'));
                    
                    // Add appropriate class based on sort type
                    switch(sortType) {
                        case 'all':
                            this.classList.add('active', 'btn-primary');
                            statusFilter.value = 'all';
                            break;
                        case 'active':
                            this.classList.add('active', 'btn-success');
                            statusFilter.value = 'Active';
                            break;
                        case 'inactive':
                            this.classList.add('active', 'btn-secondary');
                            statusFilter.value = 'Inactive';
                            break;
                    }
                    
                    filterTable();
                });
            });

            function filterTable() {
                const searchTerm = searchInput.value.toLowerCase();
                const statusValue = statusFilter.value;
                
                tableBody.innerHTML = '';
                
                const filteredRows = originalRows.filter(row => {
                    const cells = row.querySelectorAll('td');
                    if (cells.length < 10) return false;
                    
                    const payID = cells[1].textContent.toLowerCase();
                    const payName = cells[2].textContent.toLowerCase();
                    const status = cells[8].querySelector('.status-badge').textContent.trim();
                    
                    // Search filter
                    const matchesSearch = payID.includes(searchTerm) || 
                                        payName.includes(searchTerm);
                    
                    // Status filter
                    const matchesStatus = statusValue === 'all' || status === statusValue;
                    
                    return matchesSearch && matchesStatus;
                });
                
                // Add filtered rows back to table
                filteredRows.forEach(row => {
                    tableBody.appendChild(row);
                });
                
                // Show message if no results
                if (filteredRows.length === 0) {
                    const noResultsRow = document.createElement('tr');
                    noResultsRow.innerHTML = `<td colspan="10" class="text-center text-muted">No matching payment types found.</td>`;
                    tableBody.appendChild(noResultsRow);
                }
            }

            // Auto-hide alerts after 5 seconds
            setTimeout(() => {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(alert => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 5000);
        });
    </script>
</body>
</html>