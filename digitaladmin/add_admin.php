<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (empty($_SESSION['unohs'])) {
    header("location: api/login.php?msg=unauthorized");
    exit();
}

include ("api/conn.php");

// First, let's check the table structure
$check_structure = mysqli_query($conn, "DESCRIBE nirvahaka_shonu");
$columns = [];
while ($col = mysqli_fetch_assoc($check_structure)) {
    $columns[] = $col['Field'];
}

// Permission columns from your table structure
// Permission columns that are ACTUALLY used in sidebar.php
$permissions = [
    'dashboard' => 'Dashboard',
    'manageteam' => 'Team Management',
    'games' => 'Game Settings',
    'wingomanager' => 'Wingo Manager',
    'k3manager' => 'K3 Manager',
    '5dmanager' => '5D Manager',
    'finance' => 'Finance Operations',
    'setting' => 'System Settings',
    'admins' => 'Admin Management',
    'manageusers' => 'User Management',
    'manage_game' => 'Game Management',
    'manage_agent' => 'Agent Management',
    'support' => 'Customer Support'
];

// Get current user's identifier from session
$current_user_id = $_SESSION['unohs']; // This is likely the username from session

// Handle admin deletion
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $admin_identifier = mysqli_real_escape_string($conn, $_GET['delete']);
    
    // Don't allow deleting your own account
    if ($admin_identifier != $current_user_id) {
        // Check if this is SuperAdmin (unohs = 59)
        $check_superadmin = mysqli_query($conn, "SELECT unohs FROM nirvahaka_shonu WHERE 
                                                (unohs = '$admin_identifier' OR 
                                                 nirvahaka_hesaru = '$admin_identifier' OR 
                                                 hesaru = '$admin_identifier') 
                                                AND unohs = '59'");
        
        if (mysqli_num_rows($check_superadmin) > 0) {
            $_SESSION['error'] = "Cannot delete SuperAdmin account! This is a protected system account.";
        } else {
            // Try to delete by different possible identifiers
            $delete_query = "DELETE FROM nirvahaka_shonu WHERE 
                            (unohs = '$admin_identifier' OR 
                             nirvahaka_hesaru = '$admin_identifier' OR 
                             hesaru = '$admin_identifier')";
            
            if (mysqli_query($conn, $delete_query)) {
                $_SESSION['success'] = "Admin deleted successfully!";
            } else {
                $_SESSION['error'] = "Error deleting admin: " . mysqli_error($conn);
            }
        }
    } else {
        $_SESSION['error'] = "You cannot delete your own account!";
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Handle permission update
if (isset($_POST['update_permissions']) && isset($_POST['admin_identifier'])) {
    $admin_identifier = mysqli_real_escape_string($conn, $_POST['admin_identifier']);
    
    // Check if this is SuperAdmin (unohs = 59)
    $check_superadmin = mysqli_query($conn, "SELECT unohs FROM nirvahaka_shonu WHERE 
                                            (unohs = '$admin_identifier' OR 
                                             nirvahaka_hesaru = '$admin_identifier' OR 
                                             hesaru = '$admin_identifier') 
                                            AND unohs = '59'");
    
    if (mysqli_num_rows($check_superadmin) > 0) {
        $_SESSION['error'] = "Cannot modify SuperAdmin permissions! This is a protected system account.";
    } else {
        $update_query = "UPDATE nirvahaka_shonu SET ";
        
        foreach (array_keys($permissions) as $permission) {
            $value = isset($_POST[$permission]) ? 1 : 0;
            $update_query .= "$permission = $value, ";
        }
        
        $update_query .= "sthiti = '" . (isset($_POST['status']) ? 1 : 0) . "', ";
        $update_query .= "updated_at = NOW() WHERE 
                          (unohs = '$admin_identifier' OR 
                           nirvahaka_hesaru = '$admin_identifier' OR 
                           hesaru = '$admin_identifier')";
        
        if (mysqli_query($conn, $update_query)) {
            $_SESSION['success'] = "Permissions updated successfully!";
        } else {
            $_SESSION['error'] = "Error updating permissions: " . mysqli_error($conn);
        }
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Handle add new admin
if (isset($_POST['serial']) && isset($_POST['maxusers'])) {
    $chkserial = mysqli_query($conn, "SELECT * FROM nirvahaka_shonu WHERE nirvahaka_hesaru='" . $_POST['serial'] . "'");
    
    if (mysqli_num_rows($chkserial) == 0) {
        $serial   = mysqli_real_escape_string($conn, $_POST['serial']);
        $password = mysqli_real_escape_string($conn, $_POST['maxusers']);

        // Build column and value strings
        $columns = "";
        $values  = "";

        foreach (array_keys($permissions) as $permission) {
            $val = isset($_POST[$permission]) ? 1 : 0;
            $columns .= "$permission, ";
            $values  .= "'$val', ";
        }

        $status = 1;

        $sql = "
            INSERT INTO nirvahaka_shonu (
                hesaru,
                nirvahaka_hesaru,
                guptapada,
                sthiti,
                $columns
                updated_at
            ) VALUES (
                '$serial',
                '$serial',
                '" . md5($password) . "',
                '$status',
                $values
                NOW()
            )
        ";

        $add = mysqli_query($conn, $sql);

        if ($add) {
            $_SESSION['success'] = "Admin Added Successfully!";
        } else {
            $_SESSION['error'] = "Admin Add Failed: " . mysqli_error($conn);
        }
        
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();

    } else {
        $_SESSION['error'] = "Duplicate Username!";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Get all admins (excluding current user)
// First check what column exists to identify users
$current_admin_query = "SELECT * FROM nirvahaka_shonu WHERE nirvahaka_hesaru = '$current_user_id' OR hesaru = '$current_user_id' LIMIT 1";
$current_admin_result = mysqli_query($conn, $current_admin_query);
$current_admin = mysqli_fetch_assoc($current_admin_result);

$current_admin_name = $current_admin['nirvahaka_hesaru'] ?? $current_admin['hesaru'] ?? $current_user_id;

// Get all admins except current user
$admins_query = "SELECT * FROM nirvahaka_shonu 
                 WHERE (nirvahaka_hesaru != '$current_admin_name' AND hesaru != '$current_admin_name') 
                 ORDER BY updated_at DESC";
$admins_result = mysqli_query($conn, $admins_query);

// Get current user info
$current_user = $current_admin; // Use the admin we already fetched
?>

<!doctype html>
<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr"
    data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template" data-style="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Admin Management</title>
    <meta name="description" content="" />
    <link rel="icon" type="image/x-icon" href="assets/img/favicon/favicon.ico?v=20260814-admin" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&ampdisplay=swap"
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
    .superadmin-card {
    opacity: 0.5;
    position: relative;
    border-left: 4px solid #ff6b6b !important;
}

.superadmin-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    background: linear-gradient(45deg, #ff6b6b, #ff8e8e);
    color: white;
    font-weight: bold;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    z-index: 2;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.superadmin-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.7);
    z-index: 1;
    border-radius: 8px;
}

.locked-card {
    cursor: not-allowed;
}

.locked-card .action-buttons {
    display: none !important;
}
    /* Validation styles */
.is-valid {
    border-color: #28a745 !important;
    background-color: rgba(40, 167, 69, 0.05);
}

.is-invalid {
    border-color: #dc3545 !important;
    background-color: rgba(220, 53, 69, 0.05);
}

.form-control:focus.is-valid {
    box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
    border-color: #28a745;
}

.form-control:focus.is-invalid {
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
    border-color: #dc3545;
}

.invalid-feedback, .valid-feedback {
    display: none;
}

.form-control.is-invalid ~ .invalid-feedback,
.form-control.is-valid ~ .valid-feedback {
    display: block;
}

#strength-bar.progress-bar {
    transition: width 0.3s ease, background-color 0.3s ease;
}

.strength-weak {
    background-color: #dc3545 !important;
    width: 25% !important;
}

.strength-fair {
    background-color: #fd7e14 !important;
    width: 50% !important;
}

.strength-good {
    background-color: #ffc107 !important;
    width: 75% !important;
}

.strength-strong {
    background-color: #28a745 !important;
    width: 100% !important;
}

#submitBtn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.requirement-badge {
    transition: background-color 0.3s ease;
}

        .permission-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            margin: 0.1rem;
        }
        .admin-card {
            border-left: 4px solid #7367f0;
            margin-bottom: 1rem;
        }
        .modal-permission-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 10px;
            margin-top: 15px;
        }
        .permission-item {
            display: flex;
            align-items: center;
            padding: 8px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            /*background: #f8f9fa;*/
        }
        .action-buttons {
            opacity: 0;
            transition: opacity 0.2s;
        }
        .admin-card:hover .action-buttons {
            opacity: 1;
        }
        .status-active {
            color: #28a745;
        }
        .status-inactive {
            color: #dc3545;
        }
        .admin-id {
            font-family: monospace;
            background: #f1f1f1;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 0.85em;
        }
        
        /* Permissions Guide Styles */
        .permissions-guide {
            /*background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);*/
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            color: red;
            position: relative;
            overflow: hidden;
        }
        .permissions-guide::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 200px;
            height: 200px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            transform: translate(100px, -100px);
        }
        .permissions-guide::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 150px;
            height: 150px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            transform: translate(-75px, 75px);
        }
        .permission-card {
            /*background: rgba(255,255,255,0.95);*/
            border-radius: 8px;
            padding: 20px;
            height: 100%;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .permission-card:hover {
            transform: translateY(-5px);
        }
        .permission-icon {
            font-size: 2rem;
            margin-bottom: 15px;
            color: #7367f0;
        }
        .permission-features {
            list-style: none;
            padding-left: 0;
            margin-top: 10px;
        }
        .permission-features li {
            padding: 3px 0;
            font-size: 0.85rem;
            color: #666;
        }
        .permission-features li:before {
            content: '✓';
            color: #28a745;
            margin-right: 8px;
            font-weight: bold;
        }
        .guide-section-title {
            /*color: white;*/
            font-size: 1.5rem;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }
        .guide-section-title i {
            margin-right: 10px;
        }
        .permission-category {
            background: rgba(0,0,0,0.1);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            backdrop-filter: blur(10px);
        }
        .permission-category h6 {
            /*color: white;*/
            margin-bottom: 10px;
        }
        .permission-category .badge {
            margin-right: 5px;
            margin-bottom: 5px;
        }
        .collapsible-btn {
            /*background: rgba(255,255,255,0.2);*/
            border: none;
            /*color: white;*/
            padding: 10px 15px;
            border-radius: 6px;
            width: 100%;
            text-align: left;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
        }
        .collapsible-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        .collapsible-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
        }
        .collapsible-content.show {
            max-height: 500px;
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
                        
                        <?php if (isset($_SESSION['success'])): ?>
                            <div class="alert alert-success alert-dismissible" role="alert">
                                <?= $_SESSION['success']; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                            <?php unset($_SESSION['success']); ?>
                        <?php endif; ?>

                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger alert-dismissible" role="alert">
                                <?= $_SESSION['error']; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                            <?php unset($_SESSION['error']); ?>
                        <?php endif; ?>

                        <!-- Permissions Guide Section -->
                        <div class="permissions-guide mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h4 class="guide-section-title mb-0">
                                    <i class="ri-information-line"></i>
                                    Admin Permissions Guide
                                </h4>
                                <button type="button" class="btn btn-light btn-sm" onclick="toggleAllSections()">
                                    <i class="ri-expand-left-right-line me-1"></i>
                                    Toggle All
                                </button>
                            </div>
                            
                            <div class="row">
                                <!-- Dashboard -->
                                <div class="col-md-6 col-lg-4 mb-3">
                                    <div class="permission-card">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="permission-icon">
                                                <i class="ri-dashboard-line"></i>
                                            </div>
                                            <div class="ms-3">
                                                <h5 class="mb-0">Dashboard</h5>
                                                <small class="text-muted">Access to main dashboard</small>
                                            </div>
                                        </div>
                                        <p class="mb-2"><strong>Permission:</strong> <span class="badge bg-primary">dashboard</span></p>
                                        <ul class="permission-features">
                                            <li>View main admin dashboard</li>
                                            <li>Access analytics and reports</li>
                                            <li>See system overview</li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <!-- Game Management -->
                                <div class="col-md-6 col-lg-4 mb-3">
                                    <div class="permission-card">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="permission-icon">
                                                <i class="ri-gamepad-line"></i>
                                            </div>
                                            <div class="ms-3">
                                                <h5 class="mb-0">Game Management</h5>
                                                <small class="text-muted">Control game settings</small>
                                            </div>
                                        </div>
                                        <p class="mb-2"><strong>Permissions:</strong></p>
                                        <div class="d-flex flex-wrap mb-2">
                                            <span class="badge bg-primary me-1 mb-1">games</span>
                                            <span class="badge bg-info me-1 mb-1">wingomanager</span>
                                            <span class="badge bg-info me-1 mb-1">k3manager</span>
                                            <span class="badge bg-info me-1 mb-1">5dmanager</span>
                                            <span class="badge bg-secondary me-1 mb-1">manage_game</span>
                                        </div>
                                        <ul class="permission-features">
                                            <li>Game win ratio settings</li>
                                            <li>Same trend setup</li>
                                            <li>Wingo/K3/5D game management</li>
                                            <li>Auto bot prediction</li>
                                            <li>Finance TG Bot</li>
                                            <li>API games panel</li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <!-- Finance Management -->
                                <div class="col-md-6 col-lg-4 mb-3">
                                    <div class="permission-card">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="permission-icon">
                                                <i class="ri-money-dollar-circle-line"></i>
                                            </div>
                                            <div class="ms-3">
                                                <h5 class="mb-0">Finance Management</h5>
                                                <small class="text-muted">Handle financial operations</small>
                                            </div>
                                        </div>
                                        <p class="mb-2"><strong>Permissions:</strong></p>
                                        <div class="d-flex flex-wrap mb-2">
                                            <span class="badge bg-success me-1 mb-1">finance</span>
                                            <span class="badge bg-warning me-1 mb-1">setting</span>
                                        </div>
                                        <ul class="permission-features">
                                            <li>Deposit requests management</li>
                                            <li>Withdrawal processing</li>
                                            <li>USDT/Bank/UPI/E-Wallet withdrawals</li>
                                            <li>Finance settings and gateways</li>
                                            <li>Today's recharge/withdraw reports</li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <!-- User Management -->
                                <div class="col-md-6 col-lg-4 mb-3">
                                    <div class="permission-card">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="permission-icon">
                                                <i class="ri-group-line"></i>
                                            </div>
                                            <div class="ms-3">
                                                <h5 class="mb-0">User Management</h5>
                                                <small class="text-muted">Manage users and agents</small>
                                            </div>
                                        </div>
                                        <p class="mb-2"><strong>Permissions:</strong></p>
                                        <div class="d-flex flex-wrap mb-2">
                                            <span class="badge bg-danger me-1 mb-1">manageusers</span>
                                            <span class="badge bg-secondary me-1 mb-1">manageteam</span>
                                            <span class="badge bg-info me-1 mb-1">manage_agent</span>
                                        </div>
                                        <ul class="permission-features">
                                            <li>Bonus management</li>
                                            <li>Balance deduction</li>
                                            <li>User wallet reset</li>
                                            <li>Illegal bet tracking</li>
                                            <li>Agent management and salary</li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <!-- System Administration -->
                                <div class="col-md-6 col-lg-4 mb-3">
                                    <div class="permission-card">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="permission-icon">
                                                <i class="ri-settings-4-line"></i>
                                            </div>
                                            <div class="ms-3">
                                                <h5 class="mb-0">System Administration</h5>
                                                <small class="text-muted">System-wide settings</small>
                                            </div>
                                        </div>
                                        <p class="mb-2"><strong>Permissions:</strong></p>
                                        <div class="d-flex flex-wrap mb-2">
                                            <span class="badge bg-dark me-1 mb-1">admins</span>
                                            <span class="badge bg-warning me-1 mb-1">setting</span>
                                            <span class="badge bg-secondary me-1 mb-1">manage_game</span>
                                        </div>
                                        <ul class="permission-features">
                                            <li>Admin management (this page)</li>
                                            <li>Web settings and banners</li>
                                            <li>Commission settings</li>
                                            <li>Payment gateway setup</li>
                                            <li>Currency & language settings</li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <!-- Support & Customer Service -->
                                <div class="col-md-6 col-lg-4 mb-3">
                                    <div class="permission-card">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="permission-icon">
                                                <i class="ri-customer-service-2-line"></i>
                                            </div>
                                            <div class="ms-3">
                                                <h5 class="mb-0">Support & Service</h5>
                                                <small class="text-muted">Customer support functions</small>
                                            </div>
                                        </div>
                                        <p class="mb-2"><strong>Permission:</strong> <span class="badge bg-info">support</span></p>
                                        <ul class="permission-features">
                                            <li>Aviator lucky bonus</li>
                                            <li>Bank account modifications</li>
                                            <li>Deposit/withdrawal problems</li>
                                            <li>Game problem resolution</li>
                                            <li>User feedback and live chat</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Quick Reference Section -->
                            <div class="permission-category mt-4">
                                <button class="collapsible-btn" type="button" onclick="toggleSection('quickRef')">
                                    <span>
                                        <i class="ri-file-list-line me-2"></i>
                                        Quick Permission Reference
                                    </span>
                                    <i class="ri-arrow-down-s-line"></i>
                                </button>
                                <div class="collapsible-content mt-3" id="quickRef">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6 class="text-white mb-2">Primary Permissions:</h6>
                                            <span class="badge bg-primary mb-1">dashboard - Main Dashboard</span>
                                            <span class="badge bg-success mb-1">finance - All Financial Operations</span>
                                            <span class="badge bg-danger mb-1">manageusers - User Management</span>
                                            <span class="badge bg-dark mb-1">admins - Admin Management</span>
                                            <span class="badge bg-info mb-1">support - Customer Support</span>
                                        </div>
                                        <div class="col-md-6">
                                            <h6 class="text-white mb-2">Game Permissions:</h6>
                                            <span class="badge bg-info mb-1">games - Game Settings</span>
                                            <span class="badge bg-info mb-1">wingomanager - Wingo Games</span>
                                            <span class="badge bg-info mb-1">k3manager - K3 Games</span>
                                            <span class="badge bg-info mb-1">5dmanager - 5D Games</span>
                                            <span class="badge bg-secondary mb-1">manage_game - System Settings</span>
                                        </div>
                                        <div class="col-md-12 mt-2">
                                            <h6 class="text-white mb-2">Special Permissions:</h6>
                                            <span class="badge bg-warning mb-1">setting - Multiple System Functions</span>
                                            <span class="badge bg-secondary mb-1">manageteam - Team Dashboard</span>
                                            <span class="badge bg-info mb-1">manage_agent - Agent Management</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Important Notes -->
                            <div class="permission-category mt-3">
                                <button class="collapsible-btn" type="button" onclick="toggleSection('importantNotes')">
                                    <span>
                                        <i class="ri-alert-line me-2"></i>
                                        Important Notes for New Admins
                                    </span>
                                    <i class="ri-arrow-down-s-line"></i>
                                </button>
                                <div class="collapsible-content mt-3" id="importantNotes">
                                    <div class="text-white">
                                        <p><strong>⚠️ Critical Permissions:</strong></p>
                                        <ul>
                                            <li><strong>finance</strong> - Required for deposit/withdrawal processing</li>
                                            <li><strong>setting</strong> - Controls multiple system functions</li>
                                            <li><strong>admins</strong> - Allows admin management (like this page)</li>
                                        </ul>
                                        
                                        <p><strong>📊 Recommended Combinations:</strong></p>
                                        <ul>
                                            <li><strong>Support Staff:</strong> support + dashboard</li>
                                            <li><strong>Finance Staff:</strong> finance + dashboard + setting (for finance settings)</li>
                                            <li><strong>Game Manager:</strong> games + wingomanager + k3manager + 5dmanager</li>
                                            <li><strong>Super Admin:</strong> All permissions except maybe support</li>
                                        </ul>
                                        
                                        <p><strong>🔒 Security Notes:</strong></p>
                                        <ul>
                                            <li>Never give <strong>admins</strong> permission to junior staff</li>
                                            <li><strong>finance</strong> permission should be limited to trusted staff</li>
                                            <li>Regularly review admin permissions</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Rest of your existing code continues below... -->
                        <!-- Debug info (remove in production) -->
                        <div class="alert alert-info d-none">
                            <strong>Debug Info:</strong><br>
                            Session User: <?= $current_user_id; ?><br>
                            Table Columns: <?= implode(', ', $columns); ?><br>
                            Permissions: <?= count($permissions); ?> found
                        </div>

                        <!-- Header -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="mb-0">
                                <i class="ri-user-settings-line me-2"></i>
                                Admin Management
                            </h4>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAdminModal">
                                <i class="ri-user-add-line me-1"></i>Add New Admin
                            </button>
                        </div>

                        <!-- Rest of your existing HTML/PHP code continues... -->

                        <!-- Current Admin Info -->
                        <?php if ($current_user): ?>
                        <div class="card mb-4">
                            <div class="card-body">
                                <h6 class="text-primary mb-3">
                                    <i class="ri-user-star-line me-2"></i>
                                    Current User Information
                                </h6>
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar avatar-lg me-3">
                                                <span class="avatar-initial rounded-circle bg-label-primary">
                                                    <?= strtoupper(substr($current_user['hesaru'] ?? 'A', 0, 1)); ?>
                                                </span>
                                            </div>
                                            <div>
                                                <h5 class="mb-0"><?= htmlspecialchars($current_user['hesaru'] ?? $current_user_id); ?></h5>
                                                <small class="text-muted">Your Account</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-9">
                                        <div class="d-flex flex-wrap gap-1">
                                            <?php
                                            foreach ($permissions as $key => $label) {
                                                if (isset($current_user[$key]) && $current_user[$key] == 1) {
                                                    echo '<span class="badge bg-primary permission-badge">' . $label . '</span>';
                                                }
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                                                <!-- All Admins List -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="ri-group-line me-2"></i>
                                    All Administrators
                                    <span class="badge bg-primary rounded-pill ms-2">
                                        <?= mysqli_num_rows($admins_result); ?> Admins
                                    </span>
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (mysqli_num_rows($admins_result) > 0): ?>
                                    <div class="row">
                                        <?php while ($admin = mysqli_fetch_assoc($admins_result)): 
                                            // Get identifier for this admin
                                            $admin_identifier = $admin['unohs'] ?? $admin['nirvahaka_hesaru'] ?? $admin['hesaru'] ?? '';
                                            $admin_display_id = $admin['unohs'] ?? 'N/A';
                                            
                                            // Check if this is SuperAdmin (unohs = 59)
                                            $is_superadmin = ($admin['unohs'] == 59);
                                            
                                            // Determine card classes
                                            $card_classes = "admin-card";
                                            if ($is_superadmin) {
                                                $card_classes .= " superadmin-card locked-card";
                                            }
                                        ?>
                                            <div class="col-md-6 col-lg-4 mb-3">
                                                <div class="card <?= $card_classes; ?>">
                                                    <?php if ($is_superadmin): ?>
                                                        <div class="superadmin-badge">
                                                            <i class="ri-shield-keyhole-line me-1"></i>SuperAdmin
                                                        </div>
                                                        <div class="superadmin-overlay"></div>
                                                    <?php endif; ?>
                                                    
                                                    <div class="card-body" style="position: relative; z-index: 2;">
                                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                                            <div class="d-flex align-items-center">
                                                                <div class="avatar avatar-md me-3">
                                                                    <span class="avatar-initial rounded-circle <?= $is_superadmin ? 'bg-label-danger' : 'bg-label-info'; ?>">
                                                                        <?= strtoupper(substr($admin['hesaru'] ?? 'A', 0, 1)); ?>
                                                                    </span>
                                                                </div>
                                                                <div>
                                                                    <h6 class="mb-0">
                                                                        <?= htmlspecialchars($admin['hesaru'] ?? 'Unknown'); ?>
                                                                        <?php if ($is_superadmin): ?>
                                                                            <i class="ri-shield-check-line text-danger ms-1" title="Super Admin"></i>
                                                                        <?php endif; ?>
                                                                    </h6>
                                                                    <small class="text-muted">
                                                                        Username: <?= htmlspecialchars($admin['nirvahaka_hesaru'] ?? $admin['hesaru'] ?? 'N/A'); ?>
                                                                    </small>
                                                                    <br>
                                                                    <small class="text-muted">
                                                                        <span class="admin-id">ID: <?= $admin_display_id; ?></span>
                                                                    </small>
                                                                </div>
                                                            </div>
                                                            <?php if (!$is_superadmin): ?>
                                                                <div class="action-buttons">
                                                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                                                            data-bs-toggle="modal" 
                                                                            data-bs-target="#editModal<?= md5($admin_identifier); ?>">
                                                                        <i class="ri-edit-line"></i>
                                                                    </button>
                                                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                                                            onclick="confirmDelete('<?= $admin_identifier; ?>', '<?= htmlspecialchars($admin['hesaru'] ?? 'Unknown'); ?>')">
                                                                        <i class="ri-delete-bin-line"></i>
                                                                    </button>
                                                                </div>
                                                            <?php else: ?>
                                                                <div class="action-buttons">
                                                                    <span class="badge bg-danger">Locked</span>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                        
                                                        <div class="mb-2">
                                                            <span class="badge <?= ($admin['sthiti'] ?? 1) == 1 ? 'bg-success' : 'bg-danger'; ?>">
                                                                <?= ($admin['sthiti'] ?? 1) == 1 ? 'Active' : 'Inactive'; ?>
                                                            </span>
                                                            <?php if ($is_superadmin): ?>
                                                                <span class="badge bg-danger ms-1">
                                                                    <i class="ri-shield-star-line me-1"></i>System Owner
                                                                </span>
                                                            <?php endif; ?>
                                                            <small class="text-muted ms-2">
                                                                <i class="ri-calendar-line me-1"></i>
                                                                <?= date('d M Y', strtotime($admin['updated_at'] ?? date('Y-m-d'))); ?>
                                                            </small>
                                                        </div>
                                                        
                                                        <div class="mb-2">
                                                            <small class="text-muted">Permissions:</small>
                                                            <div class="d-flex flex-wrap mt-1">
                                                                <?php
                                                                $active_permissions = 0;
                                                                foreach ($permissions as $key => $label) {
                                                                    if (isset($admin[$key]) && $admin[$key] == 1) {
                                                                        $active_permissions++;
                                                                        echo '<span class="badge bg-primary permission-badge">' . $label . '</span>';
                                                                    }
                                                                }
                                                                ?>
                                                            </div>
                                                            <small class="text-muted d-block mt-1">
                                                                <?= $active_permissions; ?> of <?= count($permissions); ?> permissions
                                                            </small>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                    </div>
                                    
                                    <!-- SuperAdmin Note -->
                                    <div class="alert alert-warning mt-3">
                                        <div class="d-flex">
                                            <div class="me-3">
                                                <i class="ri-shield-keyhole-line text-warning" style="font-size: 2rem;"></i>
                                            </div>
                                            <div>
                                                <h6 class="alert-heading">
                                                    <i class="ri-information-line me-2"></i>
                                                    SuperAdmin Protection
                                                </h6>
                                                <p class="mb-1">
                                                    The  <span class="badge bg-danger">SuperAdmin</span> 
                                                    is protected from editing or deletion.
                                                </p>
                                                <small class="text-muted">
                                                    This is typically the system owner or root administrator account.
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                <?php else: ?>
                                    <div class="text-center py-5">
                                        <div class="text-muted">
                                            <i class="ri-user-shared-line display-4"></i>
                                            <p class="mt-3 mb-0">No other administrators found</p>
                                            <p class="text-muted">Add a new admin using the button above</p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                    </div>
                    <?php require_once("footer.php"); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Admin Modal -->
<div class="modal fade" id="addAdminModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Admin</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="addAdminForm">
                <div class="modal-body">
                    <!-- Caution/Validation Notice -->
                    <div class="alert alert-warning mb-4">
                        <div class="d-flex">
                            <div class="me-3">
                                <i class="ri-alert-line text-warning" style="font-size: 1.5rem;"></i>
                            </div>
                            <div>
                                <h6 class="alert-heading mb-1">
                                    <i class="ri-shield-keyhole-line me-1"></i>
                                    Important Requirements
                                </h6>
                                <ul class="mb-0 ps-3">
                                    <li>Username must be <strong>at least 6 characters</strong> long</li>
                                    <li>Password must be <strong>at least 6 characters</strong> long</li>
                                    <li>Both fields will turn <span class="text-danger">red</span> if requirements are not met</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Username
                                <span class="text-danger">*</span>
                                <span id="username-count" class="badge bg-secondary float-end">0/6</span>
                            </label>
                            <input type="text" class="form-control" name="serial" id="usernameInput" required
                                   minlength="6" 
                                   pattern=".{6,}"
                                   oninput="validateUsername()">
                            <div class="form-text">
                                Minimum 6 characters required. This will be used for login.
                            </div>
                            <div class="invalid-feedback" id="username-error">
                                Username must be at least 6 characters long.
                            </div>
                            <div class="valid-feedback" id="username-success">
                                ✓ Username meets requirements.
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Password
                                <span class="text-danger">*</span>
                                <span id="password-count" class="badge bg-secondary float-end">0/6</span>
                            </label>
                            <div class="input-group">
                                <input type="password" class="form-control" name="maxusers" id="passwordInput" required
                                       minlength="6"
                                       pattern=".{6,}"
                                       oninput="validatePassword()">
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="ri-eye-line"></i>
                                </button>
                            </div>
                            <div class="form-text">
                                Minimum 6 characters required. Password will be encrypted.
                            </div>
                            <div class="invalid-feedback" id="password-error">
                                Password must be at least 6 characters long.
                            </div>
                            <div class="valid-feedback" id="password-success">
                                ✓ Password meets requirements.
                            </div>
                        </div>
                    </div>
                    
                    <!-- Password Strength Indicator -->
                    <div class="mb-4">
                        <label class="form-label d-flex justify-content-between">
                            <span>Password Strength</span>
                            <small id="strength-text" class="text-muted">Weak</small>
                        </label>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-danger" id="strength-bar" 
                                 role="progressbar" style="width: 0%" 
                                 aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <div class="mt-1">
                            <small class="text-muted">
                                <i class="ri-information-line me-1"></i>
                                Tip: Use a combination of letters, numbers, and symbols for stronger security.
                            </small>
                        </div>
                    </div>
                    
                    <h6 class="mb-3">Permissions</h6>
                    <div class="modal-permission-grid">
                        <?php foreach ($permissions as $key => $label): ?>
                            <div class="permission-item">
                                <input class="form-check-input me-2" type="checkbox" 
                                       id="modal_<?= $key; ?>" 
                                       name="<?= $key; ?>">
                                <label class="form-check-label" for="modal_<?= $key; ?>">
                                    <?= $label; ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" id="selectAllPermissions">
                        <label class="form-check-label" for="selectAllPermissions">Select All Permissions</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
                        <i class="ri-user-add-line me-1"></i>Add Admin
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

    <!-- Edit Permission Modals -->
    <?php
    mysqli_data_seek($admins_result, 0); // Reset pointer
    while ($admin = mysqli_fetch_assoc($admins_result)):
        $admin_identifier = $admin['unohs'] ?? $admin['nirvahaka_hesaru'] ?? $admin['hesaru'] ?? '';
        $modal_id = md5($admin_identifier); // Create unique modal ID
    ?>
    <div class="modal fade" id="editModal<?= $modal_id; ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        Edit Admin: <?= htmlspecialchars($admin['hesaru'] ?? 'Unknown'); ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="admin_identifier" value="<?= $admin_identifier; ?>">
                    <input type="hidden" name="update_permissions" value="1">
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Username</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($admin['hesaru'] ?? 'Unknown'); ?>" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Login Name</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($admin['nirvahaka_hesaru'] ?? $admin['hesaru'] ?? 'N/A'); ?>" readonly>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select class="form-control" name="status">
                                    <option value="1" <?= ($admin['sthiti'] ?? 1) == 1 ? 'selected' : ''; ?>>Active</option>
                                    <option value="0" <?= ($admin['sthiti'] ?? 1) == 0 ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                        </div>
                        
                        <h6 class="mb-3">Permissions</h6>
                        <div class="modal-permission-grid">
                            <?php foreach ($permissions as $key => $label): ?>
                                <div class="permission-item">
                                    <input class="form-check-input me-2" type="checkbox" 
                                           id="edit_<?= $key . '_' . $modal_id; ?>" 
                                           name="<?= $key; ?>" 
                                           <?= (isset($admin[$key]) && $admin[$key] == 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="edit_<?= $key . '_' . $modal_id; ?>">
                                        <?= $label; ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="form-check mt-3">
                            <input class="form-check-input" type="checkbox" 
                                   id="selectAllEdit<?= $modal_id; ?>" 
                                   onclick="toggleAllPermissions('<?= $modal_id; ?>')">
                            <label class="form-check-label" for="selectAllEdit<?= $modal_id; ?>">
                                Select All Permissions
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Permissions</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endwhile; ?>

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
    // Confirm delete
    function confirmDelete(adminId, adminName) {
        // Prevent deletion of SuperAdmin (ID 59)
        if (adminId == '59') {
            alert('Cannot delete SuperAdmin account! This is a protected system account.');
            return false;
        }
        
        if (confirm(`Are you sure you want to delete admin "${adminName}"?\nThis action cannot be undone.`)) {
            window.location.href = `?delete=${encodeURIComponent(adminId)}`;
        }
    }
    
    // Select all permissions in add modal
    document.getElementById('selectAllPermissions').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('#addAdminForm input[type="checkbox"]:not(#selectAllPermissions)');
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
    });
    
    // Toggle all permissions in edit modal
    function toggleAllPermissions(modalId) {
        const checkAll = document.getElementById(`selectAllEdit${modalId}`);
        const checkboxes = document.querySelectorAll(`#editModal${modalId} input[type="checkbox"]:not(#selectAllEdit${modalId})`);
        checkboxes.forEach(checkbox => {
            checkbox.checked = checkAll.checked;
        });
    }
    
    // Auto-check select all when all checkboxes are checked
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('show.bs.modal', function() {
            const modalId = this.id.replace('editModal', '').replace('addAdminModal', '');
            const checkboxes = this.querySelectorAll('input[type="checkbox"]:not([id^="selectAll"])');
            const selectAll = this.querySelector('input[id^="selectAll"]');
            
            if (selectAll) {
                // Check if all checkboxes are checked
                const allChecked = Array.from(checkboxes).every(checkbox => checkbox.checked);
                selectAll.checked = allChecked;
                selectAll.indeterminate = !allChecked && checkboxes.some(checkbox => checkbox.checked);
                
                // Add event listeners to individual checkboxes
                checkboxes.forEach(checkbox => {
                    checkbox.addEventListener('change', function() {
                        const allChecked = Array.from(checkboxes).every(cb => cb.checked);
                        selectAll.checked = allChecked;
                        selectAll.indeterminate = !allChecked && checkboxes.some(cb => cb.checked);
                    });
                });
            }
        });
    });
    
    // Validation Functions for Add Admin Modal
    function validateUsername() {
        const input = document.getElementById('usernameInput');
        const countBadge = document.getElementById('username-count');
        const value = input.value.trim();
        const isValid = value.length >= 6;
        
        // Update character count
        countBadge.textContent = `${value.length}/6`;
        
        // Update badge color
        if (value.length === 0) {
            countBadge.className = 'badge bg-secondary float-end';
        } else if (value.length < 6) {
            countBadge.className = 'badge bg-danger float-end requirement-badge';
        } else {
            countBadge.className = 'badge bg-success float-end requirement-badge';
        }
        
        // Update input styling
        if (value.length === 0) {
            input.classList.remove('is-valid', 'is-invalid');
        } else if (isValid) {
            input.classList.remove('is-invalid');
            input.classList.add('is-valid');
        } else {
            input.classList.remove('is-valid');
            input.classList.add('is-invalid');
        }
        
        validateForm();
        return isValid;
    }
    
    function validatePassword() {
        const input = document.getElementById('passwordInput');
        const countBadge = document.getElementById('password-count');
        const strengthBar = document.getElementById('strength-bar');
        const strengthText = document.getElementById('strength-text');
        const value = input.value;
        const isValid = value.length >= 6;
        
        // Update character count
        countBadge.textContent = `${value.length}/6`;
        
        // Update badge color
        if (value.length === 0) {
            countBadge.className = 'badge bg-secondary float-end';
        } else if (value.length < 6) {
            countBadge.className = 'badge bg-danger float-end requirement-badge';
        } else {
            countBadge.className = 'badge bg-success float-end requirement-badge';
        }
        
        // Calculate password strength
        let strength = 0;
        let strengthClass = 'strength-weak';
        let strengthLabel = 'Weak';
        
        if (value.length >= 6) {
            strength += 25;
        }
        
        if (/[a-z]/.test(value)) strength += 25;
        if (/[A-Z]/.test(value)) strength += 25;
        if (/[0-9]/.test(value)) strength += 25;
        if (/[^a-zA-Z0-9]/.test(value)) strength += 25;
        
        // Cap at 100
        strength = Math.min(strength, 100);
        
        // Update strength indicator
        if (strength >= 100) {
            strengthClass = 'strength-strong';
            strengthLabel = 'Strong';
        } else if (strength >= 75) {
            strengthClass = 'strength-good';
            strengthLabel = 'Good';
        } else if (strength >= 50) {
            strengthClass = 'strength-fair';
            strengthLabel = 'Fair';
        } else if (strength >= 25) {
            strengthClass = 'strength-weak';
            strengthLabel = 'Weak';
        }
        
        strengthBar.className = `progress-bar ${strengthClass}`;
        strengthBar.style.width = `${strength}%`;
        strengthText.textContent = strengthLabel;
        
        // Update input styling
        if (value.length === 0) {
            input.classList.remove('is-valid', 'is-invalid');
        } else if (isValid) {
            input.classList.remove('is-invalid');
            input.classList.add('is-valid');
        } else {
            input.classList.remove('is-valid');
            input.classList.add('is-invalid');
        }
        
        validateForm();
        return isValid;
    }
    
    function validateForm() {
        const usernameValid = validateUsername();
        const passwordValid = validatePassword();
        const submitBtn = document.getElementById('submitBtn');
        
        // Enable/disable submit button
        if (usernameValid && passwordValid) {
            submitBtn.disabled = false;
            submitBtn.classList.remove('btn-secondary');
            submitBtn.classList.add('btn-primary');
        } else {
            submitBtn.disabled = true;
            submitBtn.classList.remove('btn-primary');
            submitBtn.classList.add('btn-secondary');
        }
    }
    
    // Toggle password visibility
    document.getElementById('togglePassword').addEventListener('click', function() {
        const passwordInput = document.getElementById('passwordInput');
        const icon = this.querySelector('i');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            icon.className = 'ri-eye-off-line';
            this.setAttribute('title', 'Hide Password');
        } else {
            passwordInput.type = 'password';
            icon.className = 'ri-eye-line';
            this.setAttribute('title', 'Show Password');
        }
    });
    
    // Form validation for add admin
    document.getElementById('addAdminForm').addEventListener('submit', function(e) {
        const username = document.getElementById('usernameInput').value.trim();
        const password = document.getElementById('passwordInput').value;
        
        if (!validateUsername() || !validatePassword()) {
            e.preventDefault();
            
            // Show validation errors
            if (!validateUsername()) {
                document.getElementById('usernameInput').classList.add('is-invalid');
            }
            if (!validatePassword()) {
                document.getElementById('passwordInput').classList.add('is-invalid');
            }
            
            alert('Please fix the validation errors before submitting.');
            return false;
        }
        
        if (username.length < 6) {
            e.preventDefault();
            alert('Username must be at least 6 characters long.');
            return false;
        }
        
        if (password.length < 6) {
            e.preventDefault();
            alert('Password must be at least 6 characters long.');
            return false;
        }
        
        return true;
    });
    
    // Initialize validation when add admin modal opens
    document.getElementById('addAdminModal').addEventListener('show.bs.modal', function() {
        // Reset form
        document.getElementById('usernameInput').value = '';
        document.getElementById('passwordInput').value = '';
        
        // Reset validation
        document.getElementById('usernameInput').classList.remove('is-valid', 'is-invalid');
        document.getElementById('passwordInput').classList.remove('is-valid', 'is-invalid');
        
        // Reset counters
        document.getElementById('username-count').textContent = '0/6';
        document.getElementById('password-count').textContent = '0/6';
        document.getElementById('username-count').className = 'badge bg-secondary float-end';
        document.getElementById('password-count').className = 'badge bg-secondary float-end';
        
        // Reset strength indicator
        document.getElementById('strength-bar').className = 'progress-bar strength-weak';
        document.getElementById('strength-bar').style.width = '0%';
        document.getElementById('strength-text').textContent = 'Weak';
        
        // Reset button
        document.getElementById('submitBtn').disabled = true;
        document.getElementById('submitBtn').classList.remove('btn-primary');
        document.getElementById('submitBtn').classList.add('btn-secondary');
        
        // Reset password visibility
        document.getElementById('passwordInput').type = 'password';
        document.getElementById('togglePassword').querySelector('i').className = 'ri-eye-line';
        
        // Focus on username field
        setTimeout(() => {
            document.getElementById('usernameInput').focus();
        }, 500);
    });
    
    // Auto-focus username field when modal opens
    document.getElementById('addAdminModal').addEventListener('shown.bs.modal', function() {
        document.getElementById('usernameInput').focus();
    });
    
    // Real-time validation on input
    document.getElementById('usernameInput').addEventListener('input', validateUsername);
    document.getElementById('passwordInput').addEventListener('input', validatePassword);
    
    // Initialize form validation on page load
    document.addEventListener('DOMContentLoaded', function() {
        // Only validate if the elements exist (for add admin modal)
        if (document.getElementById('usernameInput')) {
            validateForm();
        }
    });
    
    // Prevent form resubmission on page refresh
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
    
    // Permissions Guide Functions
    function toggleSection(sectionId) {
        const section = document.getElementById(sectionId);
        const icon = event.currentTarget.querySelector('.ri-arrow-down-s-line');
        
        if (section.classList.contains('show')) {
            section.classList.remove('show');
            icon.style.transform = 'rotate(0deg)';
        } else {
            section.classList.add('show');
            icon.style.transform = 'rotate(180deg)';
        }
    }
    
    function toggleAllSections() {
        const sections = document.querySelectorAll('.collapsible-content');
        const allIcons = document.querySelectorAll('.collapsible-btn .ri-arrow-down-s-line');
        const btn = event.currentTarget;
        
        const isExpanded = sections[0]?.classList.contains('show');
        
        sections.forEach(section => {
            if (isExpanded) {
                section.classList.remove('show');
            } else {
                section.classList.add('show');
            }
        });
        
        allIcons.forEach(icon => {
            icon.style.transform = isExpanded ? 'rotate(0deg)' : 'rotate(180deg)';
        });
        
        btn.innerHTML = isExpanded ? 
            '<i class="ri-expand-left-right-line me-1"></i> Expand All' : 
            '<i class="ri-collapse-left-right-line me-1"></i> Collapse All';
    }
    
    // Initialize collapsible sections
    document.addEventListener('DOMContentLoaded', function() {
        const collapsibleIcons = document.querySelectorAll('.collapsible-btn .ri-arrow-down-s-line');
        collapsibleIcons.forEach(icon => {
            icon.style.transition = 'transform 0.3s ease';
        });
    });
</script>

</body>
</html>