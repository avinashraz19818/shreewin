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

// Create table if not exists (UPDATED with with_time column)
$createTableSQL = "CREATE TABLE IF NOT EXISTS withdrawal_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    withdrawID INT NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    isAdd TINYINT DEFAULT 0,
    withBeforeImgUrl VARCHAR(500),
    withAfterImgUrl VARCHAR(500),
    with_time VARCHAR(50) DEFAULT '1', -- NEW COLUMN ADDED
    recommandWithAmount VARCHAR(500),
    withdrawTip TEXT,
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    min_amount DECIMAL(10,2) DEFAULT 0.00,
    max_amount DECIMAL(10,2) DEFAULT 0.00,
    processing_fee DECIMAL(5,2) DEFAULT 0.00,
    processing_time VARCHAR(50) DEFAULT '1-2 hours',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if (!$conn->query($createTableSQL)) {
    // If table exists, check if with_time column exists, if not add it
    $checkColumnSQL = "SHOW COLUMNS FROM withdrawal_types LIKE 'with_time'";
    $result = $conn->query($checkColumnSQL);
    if ($result->num_rows == 0) {
        $addColumnSQL = "ALTER TABLE withdrawal_types ADD COLUMN with_time VARCHAR(50) DEFAULT '1' AFTER withAfterImgUrl";
        $conn->query($addColumnSQL);
    }
}

// Handle status change via GET
if (isset($_GET['toggle_status'])) {
    $id = (int)$_GET['toggle_status'];
    
    $result = mysqli_query($conn, "SELECT status FROM withdrawal_types WHERE id = '$id'");
    if ($result && mysqli_num_rows($result) > 0) {
        $currentStatus = mysqli_fetch_assoc($result);
        $newStatus = ($currentStatus['status'] == 'Active') ? 'Inactive' : 'Active';
        
        mysqli_query($conn, "UPDATE withdrawal_types SET status = '$newStatus' WHERE id = '$id'");
        
        $_SESSION['message'] = 'Status updated successfully!';
        $_SESSION['message_type'] = 'success';
    }
    
    header("Location: withdrawal_types.php");
    exit;
}

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? null;

    if ($action === 'add') {
        $withdrawID = (int)$_POST['withdrawID'];
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $isAdd = (int)$_POST['isAdd'];
        $withBeforeImgUrl = mysqli_real_escape_string($conn, $_POST['withBeforeImgUrl']);
        $withAfterImgUrl = mysqli_real_escape_string($conn, $_POST['withAfterImgUrl']);
        $with_time = mysqli_real_escape_string($conn, $_POST['with_time']); // NEW FIELD
        $recommandWithAmount = mysqli_real_escape_string($conn, $_POST['recommandWithAmount']);
        $withdrawTip = mysqli_real_escape_string($conn, $_POST['withdrawTip']);
        $status = mysqli_real_escape_string($conn, $_POST['status']);
        $min_amount = (float)$_POST['min_amount'];
        $max_amount = (float)$_POST['max_amount'];
        $processing_fee = (float)$_POST['processing_fee'];
        $processing_time = mysqli_real_escape_string($conn, $_POST['processing_time']);

        // Check if withdrawID already exists
        $checkResult = mysqli_query($conn, "SELECT id FROM withdrawal_types WHERE withdrawID = '$withdrawID'");
        if (mysqli_num_rows($checkResult) > 0) {
            $_SESSION['message'] = 'Withdrawal ID already exists!';
            $_SESSION['message_type'] = 'error';
        } else {
            $stmt = $conn->prepare("INSERT INTO withdrawal_types (withdrawID, name, isAdd, withBeforeImgUrl, withAfterImgUrl, with_time, recommandWithAmount, withdrawTip, status, min_amount, max_amount, processing_fee, processing_time) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssssssddds", $withdrawID, $name, $isAdd, $withBeforeImgUrl, $withAfterImgUrl, $with_time, $recommandWithAmount, $withdrawTip, $status, $min_amount, $max_amount, $processing_fee, $processing_time);
            
            if ($stmt->execute()) {
                $_SESSION['message'] = 'Withdrawal type added successfully!';
                $_SESSION['message_type'] = 'success';
            } else {
                $_SESSION['message'] = 'Insert failed: ' . $conn->error;
                $_SESSION['message_type'] = 'error';
            }
            $stmt->close();
        }
    } 
    elseif ($action === 'update') {
        $id = (int)$_POST['id'];
        $withdrawID = (int)$_POST['withdrawID'];
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $isAdd = (int)$_POST['isAdd'];
        $withBeforeImgUrl = mysqli_real_escape_string($conn, $_POST['withBeforeImgUrl']);
        $withAfterImgUrl = mysqli_real_escape_string($conn, $_POST['withAfterImgUrl']);
        $with_time = mysqli_real_escape_string($conn, $_POST['with_time']); // NEW FIELD
        $recommandWithAmount = mysqli_real_escape_string($conn, $_POST['recommandWithAmount']);
        $withdrawTip = mysqli_real_escape_string($conn, $_POST['withdrawTip']);
        $status = mysqli_real_escape_string($conn, $_POST['status']);
        $min_amount = (float)$_POST['min_amount'];
        $max_amount = (float)$_POST['max_amount'];
        $processing_fee = (float)$_POST['processing_fee'];
        $processing_time = mysqli_real_escape_string($conn, $_POST['processing_time']);

        $sql = "UPDATE withdrawal_types SET 
                withdrawID = '$withdrawID',
                name = '$name',
                isAdd = '$isAdd',
                withBeforeImgUrl = '$withBeforeImgUrl',
                withAfterImgUrl = '$withAfterImgUrl',
                with_time = '$with_time',
                recommandWithAmount = '$recommandWithAmount',
                withdrawTip = '$withdrawTip',
                status = '$status',
                min_amount = '$min_amount',
                max_amount = '$max_amount',
                processing_fee = '$processing_fee',
                processing_time = '$processing_time'
                WHERE id = '$id'";
        
        if (mysqli_query($conn, $sql)) {
            $_SESSION['message'] = 'Withdrawal type updated successfully!';
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = 'Update failed: ' . mysqli_error($conn);
            $_SESSION['message_type'] = 'error';
        }
    } 
    elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $sql = "DELETE FROM withdrawal_types WHERE id = '$id'";
        
        if (mysqli_query($conn, $sql)) {
            $_SESSION['message'] = 'Withdrawal type deleted successfully!';
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = 'Delete failed: ' . mysqli_error($conn);
            $_SESSION['message_type'] = 'error';
        }
    }

    header("Location: withdrawal_types.php");
    exit;
}

// Get edit data if editing
$editData = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $editRes = mysqli_query($conn, "SELECT * FROM withdrawal_types WHERE id = $editId");
    if ($editRes && mysqli_num_rows($editRes) > 0) {
        $editData = mysqli_fetch_assoc($editRes);
    }
}

// Fetch all withdrawal types
$withdrawalTypes = [];
$result = mysqli_query($conn, "SELECT * FROM withdrawal_types ORDER BY withdrawID ASC");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $withdrawalTypes[] = $row;
    }
}

// Get statistics
$totalTypes = count($withdrawalTypes);
$activeTypes = 0;
$inactiveTypes = 0;

foreach ($withdrawalTypes as $type) {
    if ($type['status'] == 'Active') {
        $activeTypes++;
    } else {
        $inactiveTypes++;
    }
}

// Get current withdrawal status from web_setting table
$allowWithdrawQuery = "SELECT allow_withdraw FROM web_setting LIMIT 1";
$allowWithdrawResult = mysqli_query($conn, $allowWithdrawQuery);
$allowWithdraw = 0; // Default value

if ($allowWithdrawResult && mysqli_num_rows($allowWithdrawResult) > 0) {
    $settingRow = mysqli_fetch_assoc($allowWithdrawResult);
    $allowWithdraw = intval($settingRow['allow_withdraw']);
}

// Toggle function
if (isset($_GET['toggle_withdraw'])) {
    $newStatus = $allowWithdraw == 1 ? 0 : 1;
    $toggleQuery = "UPDATE web_setting SET allow_withdraw = '$newStatus' LIMIT 1";
    if (mysqli_query($conn, $toggleQuery)) {
        $_SESSION['message'] = 'Withdrawal status updated successfully!';
        $_SESSION['message_type'] = 'success';
        header("Location: withdrawal_types.php");
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
    <title>Withdrawal Types Management</title>
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
        .with-time-badge {
            background-color: #17a2b8;
            color: white;
            padding: 4px 8px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 500;
            display: inline-block;
            min-width: 30px;
            text-align: center;
        }
        .with-time-label {
            background-color: #6c757d;
            color: white;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 10px;
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

                        <!-- Statistics Cards -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="stats-card">
                                    <span class="stats-value"><?= $totalTypes ?></span>
                                    <span class="stats-label">Total Withdrawal Types</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                                    <span class="stats-value"><?= $activeTypes ?></span>
                                    <span class="stats-label">Active Types</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card" style="background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);">
                                    <span class="stats-value"><?= $inactiveTypes ?></span>
                                    <span class="stats-label">Inactive Types</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card" style="background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);">
                                    <span class="stats-value"><?= count(array_filter($withdrawalTypes, fn($type) => $type['with_time'] > 1)) ?></span>
                                    <span class="stats-label">Multi-Time Types</span>
                                </div>
                            </div>
                        </div>
                        
                        
                        <div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="ri-toggle-line me-2"></i>
                    Global Withdrawal Control
                </h5>
            </div>
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h6 class="mb-1">Allow Withdrawals System-Wide</h6>
                        <p class="text-muted mb-0">
                            This setting controls whether users can make withdrawal requests or not.
                            When disabled, all withdrawal options will be hidden from users.
                        </p>
                    </div>
                    <div class="col-md-6 text-end">
                        <div class="d-flex align-items-center justify-content-end gap-3">
                            <span class="fs-6 <?= $allowWithdraw == 1 ? 'text-success' : 'text-danger' ?>">
                                <i class="ri-<?= $allowWithdraw == 1 ? 'check' : 'close' ?>-circle-fill me-1"></i>
                                <?= $allowWithdraw == 1 ? 'Withdrawals Allowed' : 'Withdrawals Not Allowed' ?>
                            </span>
                            <a href="?toggle_withdraw=1" 
                               class="btn btn-lg <?= $allowWithdraw == 1 ? 'btn-danger' : 'btn-success' ?>"
                               onclick="return confirm('Are you sure you want to <?= $allowWithdraw == 1 ? 'disable' : 'enable' ?> withdrawals system-wide?')">
                                <i class="ri-toggle-<?= $allowWithdraw == 1 ? 'off' : 'on' ?>-line me-1"></i>
                                <?= $allowWithdraw == 1 ? 'Disable Withdrawals' : 'Enable Withdrawals' ?>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Status Indicator -->
                <div class="mt-3">
                    <div class="alert <?= $allowWithdraw == 1 ? 'alert-success' : 'alert-danger' ?> mb-0">
                        <div class="d-flex align-items-center">
                            <i class="ri-<?= $allowWithdraw == 1 ? 'check' : 'close' ?>-circle-line fs-4 me-2"></i>
                            <div>
                                <strong>Current Status:</strong> 
                                <?php if ($allowWithdraw == 1): ?>
                                    <span class="text-success">Withdrawals are enabled.</span> 
                                    Users can submit withdrawal requests through all active methods.
                                <?php else: ?>
                                    <span class="text-danger">Withdrawals are disabled.</span> 
                                    Users cannot submit any withdrawal requests.
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
                            <!-- Withdrawal Type Form -->
                            <div class="col-12">
                                <?php if (isset($_GET['edit']) && $editData): ?>
                                <div class="edit-form-container">
                                    <h4 class="text-primary mb-3"><i class="ri-edit-line ri-16px me-2"></i>Editing: <?= htmlspecialchars($editData['name']) ?></h4>
                                <?php endif; ?>
                                
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <i class="ri-bank-card-line ri-16px me-2"></i>
                                            <?= (isset($_GET['edit']) && $editData) ? 'Edit Withdrawal Type' : 'Add New Withdrawal Type' ?>
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
                                            
                                            <!-- Withdraw Id "Read Only" -->
                                            <div class="col-md-3 mb-3">
                                                <label for="withdrawID" class="form-label">Withdrawal ID *</label>
                                                <?php if (isset($_GET['edit']) && $editData): ?>
                                                    <!-- Edit mode: Show as readonly -->
                                                    <input type="number" class="form-control" id="withdrawID" name="withdrawID" 
                                                           value="<?= htmlspecialchars($editData['withdrawID'] ?? '') ?>" 
                                                           readonly 
                                                           style="background-color: #f8f9fa; cursor: not-allowed;"
                                                           placeholder="Unique ID (Cannot be changed)">
                                                    <small class="text-muted">Withdrawal ID cannot be changed in edit mode</small>
                                                <?php else: ?>
                                                    <!-- Add mode: Normal input -->
                                                    <input type="number" class="form-control" id="withdrawID" name="withdrawID" 
                                                           value="<?= htmlspecialchars($editData['withdrawID'] ?? '') ?>" required 
                                                           placeholder="Unique ID">
                                                <?php endif; ?>
                                            </div>

                                            <div class="col-md-3 mb-3">
                                                <label for="name" class="form-label">Name *</label>
                                                <input type="text" class="form-control" id="name" name="name" 
                                                       value="<?= htmlspecialchars($editData['name'] ?? '') ?>" required 
                                                       placeholder="e.g., UPI, BANK CARD">
                                            </div>

                                            <div class="col-md-2 mb-3">
                                                <label for="with_time" class="form-label">Withdraw Times</label>
                                                <select class="form-select" id="with_time" name="with_time" required>
                                                    <option value="">Select Times</option>
                                                    <?php for($i = 1; $i <= 10; $i++): ?>
                                                        <option value="<?= $i ?>" <?= (isset($editData['with_time']) && $editData['with_time'] == $i) ? 'selected' : '' ?>>
                                                            <?= $i ?> Time<?= $i > 1 ? 's' : '' ?>
                                                        </option>
                                                    <?php endfor; ?>
                                                </select>
                                                <small class="text-muted">Number of allowed withdrawals</small>
                                            </div>

                                            <div class="col-md-2 mb-3">
                                                <label for="isAdd" class="form-label">Is Add</label>
                                                <select class="form-select" id="isAdd" name="isAdd">
                                                    <option value="0" <?= ($editData['isAdd'] ?? 0) == 0 ? 'selected' : '' ?>>No</option>
                                                    <option value="1" <?= ($editData['isAdd'] ?? 0) == 1 ? 'selected' : '' ?>>Yes</option>
                                                </select>
                                            </div>

                                            <div class="col-md-2 mb-3">
                                                <label for="status" class="form-label">Status</label>
                                                <select class="form-select" id="status" name="status">
                                                    <option value="Active" <?= ($editData['status'] ?? 'Active') == 'Active' ? 'selected' : '' ?>>Active</option>
                                                    <option value="Inactive" <?= ($editData['status'] ?? 'Active') == 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                                                </select>
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label for="withBeforeImgUrl" class="form-label">Before Image URL</label>
                                                <input type="url" class="form-control" id="withBeforeImgUrl" name="withBeforeImgUrl" 
                                                       value="<?= htmlspecialchars($editData['withBeforeImgUrl'] ?? '') ?>" 
                                                       placeholder="https://example.com/image.png">
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label for="withAfterImgUrl" class="form-label">After Image URL</label>
                                                <input type="url" class="form-control" id="withAfterImgUrl" name="withAfterImgUrl" 
                                                       value="<?= htmlspecialchars($editData['withAfterImgUrl'] ?? '') ?>" 
                                                       placeholder="https://example.com/image.png">
                                            </div>

                                            <div class="col-md-3 mb-3">
                                                <label for="min_amount" class="form-label">Minimum Amount</label>
                                                <input type="number" step="0.01" class="form-control" id="min_amount" name="min_amount" 
                                                       value="<?= htmlspecialchars($editData['min_amount'] ?? '0.00') ?>" 
                                                       placeholder="0.00">
                                            </div>

                                            <div class="col-md-3 mb-3">
                                                <label for="max_amount" class="form-label">Maximum Amount</label>
                                                <input type="number" step="0.01" class="form-control" id="max_amount" name="max_amount" 
                                                       value="<?= htmlspecialchars($editData['max_amount'] ?? '0.00') ?>" 
                                                       placeholder="0.00">
                                            </div>

                                            <div class="col-md-3 mb-3">
                                                <label for="processing_fee" class="form-label">Processing Fee (%)</label>
                                                <input type="number" step="0.01" class="form-control" id="processing_fee" name="processing_fee" 
                                                       value="<?= htmlspecialchars($editData['processing_fee'] ?? '0.00') ?>" 
                                                       placeholder="0.00">
                                            </div>

                                            <div class="col-md-3 mb-3">
                                                <label for="processing_time" class="form-label">Processing Time</label>
                                                <input type="text" class="form-control" id="processing_time" name="processing_time" 
                                                       value="<?= htmlspecialchars($editData['processing_time'] ?? '1-2 hours') ?>" 
                                                       placeholder="e.g., 1-2 hours, Instant">
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label for="recommandWithAmount" class="form-label">Recommended Amounts</label>
                                                <input type="text" class="form-control" id="recommandWithAmount" name="recommandWithAmount" 
                                                       value="<?= htmlspecialchars($editData['recommandWithAmount'] ?? '') ?>" 
                                                       placeholder="800,1700,5000,10000">
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label for="withdrawTip" class="form-label">Withdrawal Tips</label>
                                                <textarea class="form-control" id="withdrawTip" name="withdrawTip" rows="1" 
                                                          placeholder="Enter withdrawal tips..."><?= htmlspecialchars($editData['withdrawTip'] ?? '') ?></textarea>
                                            </div>

                                            <div class="col-12">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="ri-save-line ri-16px me-2"></i>
                                                    <?= (isset($_GET['edit']) && $editData) ? 'Update Withdrawal Type' : 'Add Withdrawal Type' ?>
                                                </button>
                                                <?php if (isset($_GET['edit']) && $editData): ?>
                                                    <a href="withdrawal_types.php" class="btn btn-secondary">
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
                                            <div class="col-md-4 mb-3">
                                                <label for="searchInput" class="form-label">Search Withdrawal Types</label>
                                                <input type="text" class="form-control" id="searchInput" placeholder="Search by name or ID...">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="statusFilter" class="form-label">Filter by Status</label>
                                                <select class="form-select" id="statusFilter">
                                                    <option value="all">All Status</option>
                                                    <option value="Active">Active Only</option>
                                                    <option value="Inactive">Inactive Only</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="timeFilter" class="form-label">Filter by Times</label>
                                                <select class="form-select" id="timeFilter">
                                                    <option value="all">All Times</option>
                                                    <option value="1">1 Time</option>
                                                    <option value="2">2 Times</option>
                                                    <option value="3">3 Times</option>
                                                    <option value="4">4 Times</option>
                                                    <option value="5">5 Times</option>
                                                    <option value="6+">6+ Times</option>
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
                                                    <button type="button" class="btn btn-outline-info sort-btn" data-sort="multitime">
                                                        <i class="ri-repeat-line ri-16px me-2"></i>Multi-Time
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Withdrawal Types Table -->
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h5 class="card-title mb-0"><i class="ri-bank-card-line ri-16px me-2"></i>Withdrawal Types List</h5>
                                            <span class="badge bg-info">
                                                Showing <?= $totalTypes ?> Types
                                            </span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-bordered">
                                                <thead class="table-dark">
                                                    <tr>
                                                        <th>#</th>
                                                        <th>ID</th>
                                                        <th>Name</th>
                                                        <th>Withdraw Times</th>
                                                        <th>Icons</th>
                                                        <th>Is Add</th>
                                                        <th>Min/Max Amount</th>
                                                        <th>Processing</th>
                                                        <th>Recommended Amounts</th>
                                                        <th>Status</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="withdrawalTableBody">
                                                    <?php
                                                    if (count($withdrawalTypes) > 0) {
                                                        $count = 1;
                                                        foreach ($withdrawalTypes as $type) {
                                                            echo "<tr>";
                                                            echo "<td>" . $count++ . "</td>";
                                                            echo "<td>" . $type['withdrawID'] . "</td>";
                                                            echo "<td>
                                                                <strong>" . htmlspecialchars($type['name']) . "</strong>
                                                            </td>";
                                                            echo "<td>
                                                                <span class='with-time-badge' title='Withdraw Times'>
                                                                    " . htmlspecialchars($type['with_time'] ?? '1') . "
                                                                </span>
                                                            </td>";
                                                            echo "<td>
                                                                <div class='d-flex gap-2'>
                                                                    " . (!empty($type['withBeforeImgUrl']) ? "<img src='" . htmlspecialchars($type['withBeforeImgUrl']) . "' class='payment-icon' alt='Before' title='Before Icon'>" : "<span class='text-muted'>No Icon</span>") . "
                                                                    " . (!empty($type['withAfterImgUrl']) ? "<img src='" . htmlspecialchars($type['withAfterImgUrl']) . "' class='payment-icon' alt='After' title='After Icon'>" : "") . "
                                                                </div>
                                                            </td>";
                                                            echo "<td>" . ($type['isAdd'] ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>') . "</td>";
                                                            echo "<td>
                                                                <small>Min: ₹" . number_format($type['min_amount'], 2) . "</small><br>
                                                                <small>Max: ₹" . number_format($type['max_amount'], 2) . "</small>
                                                            </td>";
                                                            echo "<td>
                                                                <small>Fee: " . $type['processing_fee'] . "%</small><br>
                                                                <small>Time: " . $type['processing_time'] . "</small>
                                                            </td>";
                                                            echo "<td>
                                                                <div class='d-flex flex-wrap gap-1'>
                                                                    " . ($type['recommandWithAmount'] ? 
                                                                        implode('', array_map(function($amt) {
                                                                            return '<span class="badge bg-info">₹' . trim($amt) . '</span>';
                                                                        }, explode(',', $type['recommandWithAmount']))) : 
                                                                        '<span class="text-muted">N/A</span>') . "
                                                                </div>
                                                            </td>";
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
                                                                    <form method='POST' style='display:inline;' onsubmit='return confirm(\"Are you sure you want to delete this withdrawal type?\")'>
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
                                                        echo "<tr><td colspan='11' class='text-center'>No withdrawal types found. Add your first withdrawal type above.</td></tr>";
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
            const timeFilter = document.getElementById('timeFilter');
            const sortButtons = document.querySelectorAll('.sort-btn');
            const tableBody = document.getElementById('withdrawalTableBody');
            const originalRows = Array.from(tableBody.querySelectorAll('tr'));

            // Search functionality
            searchInput.addEventListener('input', filterTable);
            statusFilter.addEventListener('change', filterTable);
            timeFilter.addEventListener('change', filterTable);

            // Sort buttons functionality
            sortButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const sortType = this.getAttribute('data-sort');
                    
                    // Remove active class from all buttons
                    sortButtons.forEach(btn => btn.classList.remove('active', 'btn-primary', 'btn-success', 'btn-secondary', 'btn-info'));
                    
                    // Add appropriate class based on sort type
                    switch(sortType) {
                        case 'all':
                            this.classList.add('active', 'btn-primary');
                            statusFilter.value = 'all';
                            timeFilter.value = 'all';
                            break;
                        case 'active':
                            this.classList.add('active', 'btn-success');
                            statusFilter.value = 'Active';
                            break;
                        case 'inactive':
                            this.classList.add('active', 'btn-secondary');
                            statusFilter.value = 'Inactive';
                            break;
                        case 'multitime':
                            this.classList.add('active', 'btn-info');
                            timeFilter.value = '6+';
                            break;
                    }
                    
                    filterTable();
                });
            });

            function filterTable() {
                const searchTerm = searchInput.value.toLowerCase();
                const statusValue = statusFilter.value;
                const timeValue = timeFilter.value;
                
                tableBody.innerHTML = '';
                
                const filteredRows = originalRows.filter(row => {
                    const cells = row.querySelectorAll('td');
                    if (cells.length < 11) return false;
                    
                    const withdrawID = cells[1].textContent.toLowerCase();
                    const name = cells[2].textContent.toLowerCase();
                    const withTimeBadge = cells[3].querySelector('.with-time-badge');
                    const withTime = withTimeBadge ? withTimeBadge.textContent.trim() : '1';
                    const status = cells[9].querySelector('.status-badge').textContent.trim();
                    
                    // Search filter
                    const matchesSearch = withdrawID.includes(searchTerm) || 
                                        name.includes(searchTerm);
                    
                    // Status filter
                    const matchesStatus = statusValue === 'all' || status === statusValue;
                    
                    // Time filter
                    let matchesTime = true;
                    if (timeValue !== 'all') {
                        if (timeValue === '6+') {
                            matchesTime = parseInt(withTime) >= 6;
                        } else {
                            matchesTime = withTime === timeValue;
                        }
                    }
                    
                    return matchesSearch && matchesStatus && matchesTime;
                });
                
                // Add filtered rows back to table
                filteredRows.forEach(row => {
                    tableBody.appendChild(row);
                });
                
                // Show message if no results
                if (filteredRows.length === 0) {
                    const noResultsRow = document.createElement('tr');
                    noResultsRow.innerHTML = `<td colspan="11" class="text-center text-muted">No matching withdrawal types found.</td>`;
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

            // Auto-scroll to edit form when editing
            <?php if (isset($_GET['edit']) && $editData): ?>
                document.addEventListener('DOMContentLoaded', function() {
                    document.querySelector('.edit-form-container')?.scrollIntoView({
                        behavior: 'smooth'
                    });
                });
            <?php endif; ?>
        });
    </script>
</body>
</html>