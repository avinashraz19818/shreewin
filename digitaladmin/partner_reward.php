<?php
session_start();
if (empty($_SESSION['unohs'])) {
    header("location: api/login.php?msg=unauthorized");
    exit();
}
include("api/conn.php");

// UTF-8 connection ensure karo
mysqli_set_charset($conn, "utf8mb4");

// Create extra_game_settings table if not exists
$check_settings_table = $conn->query("SHOW TABLES LIKE 'extra_game_settings'");
if ($check_settings_table->num_rows == 0) {
    $create_settings_table = "CREATE TABLE extra_game_settings (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL UNIQUE,
        status INT(11) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $conn->query($create_settings_table);
    
    // Insert default partner reward setting
    $insert_settings = "INSERT INTO extra_game_settings (name, status) 
                       VALUES ('partner_reward', '1')";
    $conn->query($insert_settings);
}

// Get partner rewards status
$status_result = $conn->query("SELECT status FROM extra_game_settings WHERE name = 'partner_reward'");
if ($status_result->num_rows > 0) {
    $status_row = $status_result->fetch_assoc();
    $partner_rewards_active = (int)$status_row['status'];
} else {
    $partner_rewards_active = 1; // Default active
    // Insert if not exists
    $conn->query("INSERT INTO extra_game_settings (name, status) VALUES ('partner_reward', '1')");
}

// Handle toggle status
if (isset($_POST['toggle_status'])) {
    // FIX: Get the correct new status
    $new_status = (int)$_POST['new_status']; // This will be 1 for active, 0 for inactive
    
    $update_sql = "UPDATE extra_game_settings SET status = '$new_status' WHERE name = 'partner_reward'";
    if ($conn->query($update_sql)) {
        $_SESSION['success'] = $new_status == 1 
            ? "✅ Partner Rewards system activated successfully!" 
            : "⚠️ Partner Rewards system deactivated successfully!";
        $partner_rewards_active = $new_status;
    } else {
        $_SESSION['error'] = "Failed to update status!";
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Handle form submissions for reward levels
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action == 'add') {
        $type = $conn->real_escape_string($_POST['type']);
        $min_recharge = $conn->real_escape_string($_POST['min_recharge']);
        $max_recharge = $conn->real_escape_string($_POST['max_recharge']);
        $bet = $conn->real_escape_string($_POST['bet']);
        $reward = $conn->real_escape_string($_POST['reward']);
        
        $sql = "INSERT INTO partner_rewards_config (type, min_recharge, max_recharge, bet, reward) 
                VALUES ('$type', '$min_recharge', '$max_recharge', '$bet', '$reward')";
        
        if ($conn->query($sql)) {
            $_SESSION['success'] = "New reward level added successfully!";
        } else {
            $_SESSION['error'] = "Failed to add reward level!";
        }
        
    } elseif ($action == 'edit') {
        $id = $conn->real_escape_string($_POST['id']);
        $type = $conn->real_escape_string($_POST['type']);
        $min_recharge = $conn->real_escape_string($_POST['min_recharge']);
        $max_recharge = $conn->real_escape_string($_POST['max_recharge']);
        $bet = $conn->real_escape_string($_POST['bet']);
        $reward = $conn->real_escape_string($_POST['reward']);
        
        $sql = "UPDATE partner_rewards_config SET 
                type = '$type',
                min_recharge = '$min_recharge',
                max_recharge = '$max_recharge',
                bet = '$bet',
                reward = '$reward'
                WHERE id = '$id'";
        
        if ($conn->query($sql)) {
            $_SESSION['success'] = "Reward level updated successfully!";
        } else {
            $_SESSION['error'] = "Failed to update reward level!";
        }
        
    } elseif ($action == 'delete') {
        $id = $conn->real_escape_string($_POST['id']);
        
        $sql = "DELETE FROM partner_rewards_config WHERE id = '$id'";
        
        if ($conn->query($sql)) {
            $_SESSION['success'] = "Reward level deleted successfully!";
        } else {
            $_SESSION['error'] = "Failed to delete reward level!";
        }
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Fetch reward configurations
$reward_config = [1 => [], 2 => [], 3 => []];
$result = $conn->query("SELECT * FROM partner_rewards_config ORDER BY type, min_recharge");

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $type = (int)$row['type'];
        $reward_config[$type][] = [
            'id' => $row['id'],
            'type' => $type,
            'min_recharge' => $row['min_recharge'],
            'max_recharge' => $row['max_recharge'],
            'bet' => $row['bet'],
            'reward' => $row['reward'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at']
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template-free">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Partner Reward Management</title>
    <meta name="description" content="Manage partner reward configurations" />
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
        .control-container {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            padding: 1.5rem;
            color: white;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            border: none;
        }
        .control-header {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }
        .control-icon {
            background: rgba(255,255,255,0.2);
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            font-size: 1.5rem;
        }
        .control-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        .control-subtitle {
            font-size: 0.875rem;
            opacity: 0.9;
        }
        .control-body {
            background: rgba(255,255,255,0.1);
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .control-description {
            font-size: 0.9rem;
            line-height: 1.5;
            margin-bottom: 1rem;
        }
        .control-status {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 1rem;
            border-top: 1px solid rgba(255,255,255,0.2);
        }
        .status-text {
            font-size: 0.9rem;
        }
        .status-indicator {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            margin-left: 0.5rem;
        }
        .status-active {
            background: rgba(113, 221, 55, 0.2);
            color: #71dd37;
        }
        .status-inactive {
            background: rgba(255, 62, 29, 0.2);
            color: #ff3e1d;
        }
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 30px;
        }
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(255,255,255,0.3);
            transition: .4s;
            border-radius: 34px;
        }
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 22px;
            width: 22px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        input:checked + .toggle-slider {
            background-color: #71dd37;
        }
        input:checked + .toggle-slider:before {
            transform: translateX(30px);
        }
        .reward-card {
            transition: all 0.3s;
            border: 1px solid #e0e0e0;
        }
        .reward-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .badge-type-1 { background-color: #696cff; }
        .badge-type-2 { background-color: #71dd37; }
        .badge-type-3 { background-color: #ff3e1d; }
        .table-actions {
            white-space: nowrap;
        }
        .type-icon {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            margin-right: 10px;
        }
        .form-spinner {
            display: none;
        }
        .form-spinner.active {
            display: inline-block;
            margin-right: 5px;
        }
        .action-buttons .btn {
            margin-right: 5px;
        }
        .action-buttons .btn:last-child {
            margin-right: 0;
        }
        .disabled-overlay {
            position: relative;
        }
        .disabled-overlay::after {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255,255,255,0.7);
            border-radius: inherit;
            pointer-events: none;
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
                        <!-- Main Header -->
                        <div class="row mb-4">
                            <div class="col-lg-8">
                                <h4 class="fw-bold py-3 mb-0">
                                    <span class="text-muted fw-light">Settings /</span> Partner Reward Management
                                </h4>
                                <p class="text-muted">Configure and manage partner reward levels</p>
                            </div>
                        </div>

                        <!-- Global Control Container -->
                        <div class="control-container">
                            <div class="control-header">
                                <div class="control-icon">
                                    <i class="ri-award-line"></i>
                                </div>
                                <div>
                                    <div class="control-title">Partner Reward Control</div>
                                    <div class="control-subtitle">Enable or disable partner rewards system-wide</div>
                                </div>
                            </div>
                            
                            <div class="control-body">
                                <div class="control-description">
                                    Partner rewards will be hidden from users if disabled. The system will stop calculating and distributing partner rewards.
                                </div>
                                
                                <!-- In the control-container section -->
<form method="POST" id="toggleForm">
    <input type="hidden" name="toggle_status" value="1">
    <!-- FIX: Toggle checked = Active (1), unchecked = Inactive (0) -->
    <input type="hidden" name="new_status" id="newStatus" value="<?php echo $partner_rewards_active == 1 ? '0' : '1'; ?>">
    
    <div class="control-status">
        <div class="status-text">
            Current Status: 
            <span class="status-indicator <?php echo $partner_rewards_active == 1 ? 'status-active' : 'status-inactive'; ?>">
                <i class="ri-<?php echo $partner_rewards_active == 1 ? 'check' : 'close'; ?>-circle-line me-1"></i>
                <?php echo $partner_rewards_active == 1 ? 'Active' : 'Inactive'; ?>
            </span>
            <br>
            <small>
                <?php echo $partner_rewards_active == 1 
                    ? 'Partner rewards are enabled. Users can earn rewards through the referral system.' 
                    : 'Partner rewards are disabled. The referral reward system is temporarily unavailable.'; ?>
            </small>
        </div>
        <!-- FIX: Toggle should be checked when active (1) -->
        <label class="toggle-switch mb-0">
            <input type="checkbox" id="statusToggle" <?php echo $partner_rewards_active == 1 ? 'checked' : ''; ?> onchange="toggleSystemStatus()">
            <span class="toggle-slider"></span>
        </label>
    </div>
</form>
                            </div>
                        </div>

                        <!-- Notification Messages -->
                        <?php if(isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert" id="successAlert">
                            <i class="ri-checkbox-circle-line me-2"></i>
                            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <script>localStorage.setItem('showSuccess', 'true');</script>
                        <?php endif; ?>
                        
                        <?php if(isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert" id="errorAlert">
                            <i class="ri-error-warning-line me-2"></i>
                            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <script>localStorage.setItem('showError', 'true');</script>
                        <?php endif; ?>

                        <!-- Add New Button -->
                        <div class="row mb-4">
                            <div class="col-12 text-end">
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal" <?php echo $partner_rewards_active == 0 ? 'disabled' : ''; ?>>
                                    <i class="ri-add-circle-line me-2"></i>Add New Level
                                </button>
                                <?php if($partner_rewards_active == 0): ?>
                                <small class="text-danger d-block mt-1"><i class="ri-information-line me-1"></i>System is inactive. Enable system to add new levels.</small>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Stats Cards -->
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="card reward-card <?php echo $partner_rewards_active == 0 ? 'disabled-overlay' : ''; ?>">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="type-icon bg-label-primary">
                                                <i class="ri-1-fill"></i>
                                            </div>
                                            <div>
                                                <h5 class="card-title mb-1">First Deposit</h5>
                                                <p class="mb-0">Type 1 Levels</p>
                                            </div>
                                        </div>
                                        <div class="mt-3">
                                            <h3 class="mb-0"><?php echo count($reward_config[1]); ?></h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card reward-card <?php echo $partner_rewards_active == 0 ? 'disabled-overlay' : ''; ?>">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="type-icon bg-label-success">
                                                <i class="ri-2-fill"></i>
                                            </div>
                                            <div>
                                                <h5 class="card-title mb-1">Second Deposit</h5>
                                                <p class="mb-0">Type 2 Levels</p>
                                            </div>
                                        </div>
                                        <div class="mt-3">
                                            <h3 class="mb-0"><?php echo count($reward_config[2]); ?></h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card reward-card <?php echo $partner_rewards_active == 0 ? 'disabled-overlay' : ''; ?>">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="type-icon bg-label-danger">
                                                <i class="ri-3-fill"></i>
                                            </div>
                                            <div>
                                                <h5 class="card-title mb-1">Third Deposit</h5>
                                                <p class="mb-0">Type 3 Levels</p>
                                            </div>
                                        </div>
                                        <div class="mt-3">
                                            <h3 class="mb-0"><?php echo count($reward_config[3]); ?></h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Type 1 Table -->
                        <div class="card mb-4 <?php echo $partner_rewards_active == 0 ? 'disabled-overlay' : ''; ?>">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <span class="badge badge-type-1 me-2">Type 1</span>
                                    First Deposit Reward Configuration
                                </h5>
                                <span class="badge bg-label-primary">Levels: <?php echo count($reward_config[1]); ?></span>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive text-nowrap">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Min Recharge (₹)</th>
                                                <th>Max Recharge (₹)</th>
                                                <th>Required Bet (₹)</th>
                                                <th>Reward (₹)</th>
                                                <th>Last Updated</th>
                                                <th class="table-actions">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="table-border-bottom-0">
                                            <?php if (!empty($reward_config[1])): ?>
                                                <?php foreach ($reward_config[1] as $level): ?>
                                                <tr>
                                                    <td><strong><?php echo $level['id']; ?></strong></td>
                                                    <td>₹<?php echo number_format($level['min_recharge'], 2); ?></td>
                                                    <td>₹<?php echo $level['max_recharge'] == 99999999.99 ? '∞' : number_format($level['max_recharge'], 2); ?></td>
                                                    <td>₹<?php echo number_format($level['bet'], 2); ?></td>
                                                    <td><span class="badge bg-label-success">₹<?php echo number_format($level['reward'], 2); ?></span></td>
                                                    <td><?php echo date('d/m/Y H:i', strtotime($level['updated_at'])); ?></td>
                                                    <td class="table-actions action-buttons">
                                                        <button class="btn btn-sm btn-icon btn-label-warning edit-btn" 
                                                                data-id="<?php echo $level['id']; ?>"
                                                                data-type="1"
                                                                data-min="<?php echo $level['min_recharge']; ?>"
                                                                data-max="<?php echo $level['max_recharge']; ?>"
                                                                data-bet="<?php echo $level['bet']; ?>"
                                                                data-reward="<?php echo $level['reward']; ?>"
                                                                <?php echo $partner_rewards_active == 0 ? 'disabled' : ''; ?>>
                                                            <i class="ri-edit-line"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-icon btn-label-danger delete-btn" 
                                                                data-id="<?php echo $level['id']; ?>"
                                                                <?php echo $partner_rewards_active == 0 ? 'disabled' : ''; ?>>
                                                            <i class="ri-delete-bin-line"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="7" class="text-center text-muted py-4">
                                                        <i class="ri-information-line me-2"></i>
                                                        No Type 1 reward levels configured
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Type 2 Table -->
                        <div class="card mb-4 <?php echo $partner_rewards_active == 0 ? 'disabled-overlay' : ''; ?>">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <span class="badge badge-type-2 me-2">Type 2</span>
                                    Second Deposit Reward Configuration
                                </h5>
                                <span class="badge bg-label-success">Levels: <?php echo count($reward_config[2]); ?></span>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive text-nowrap">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Min Recharge (₹)</th>
                                                <th>Max Recharge (₹)</th>
                                                <th>Required Bet (₹)</th>
                                                <th>Reward (₹)</th>
                                                <th>Last Updated</th>
                                                <th class="table-actions">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="table-border-bottom-0">
                                            <?php if (!empty($reward_config[2])): ?>
                                                <?php foreach ($reward_config[2] as $level): ?>
                                                <tr>
                                                    <td><strong><?php echo $level['id']; ?></strong></td>
                                                    <td>₹<?php echo number_format($level['min_recharge'], 2); ?></td>
                                                    <td>₹<?php echo $level['max_recharge'] == 99999999.99 ? '∞' : number_format($level['max_recharge'], 2); ?></td>
                                                    <td>₹<?php echo number_format($level['bet'], 2); ?></td>
                                                    <td><span class="badge bg-label-success">₹<?php echo number_format($level['reward'], 2); ?></span></td>
                                                    <td><?php echo date('d/m/Y H:i', strtotime($level['updated_at'])); ?></td>
                                                    <td class="table-actions action-buttons">
                                                        <button class="btn btn-sm btn-icon btn-label-warning edit-btn" 
                                                                data-id="<?php echo $level['id']; ?>"
                                                                data-type="2"
                                                                data-min="<?php echo $level['min_recharge']; ?>"
                                                                data-max="<?php echo $level['max_recharge']; ?>"
                                                                data-bet="<?php echo $level['bet']; ?>"
                                                                data-reward="<?php echo $level['reward']; ?>"
                                                                <?php echo $partner_rewards_active == 0 ? 'disabled' : ''; ?>>
                                                            <i class="ri-edit-line"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-icon btn-label-danger delete-btn" 
                                                                data-id="<?php echo $level['id']; ?>"
                                                                <?php echo $partner_rewards_active == 0 ? 'disabled' : ''; ?>>
                                                            <i class="ri-delete-bin-line"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="7" class="text-center text-muted py-4">
                                                        <i class="ri-information-line me-2"></i>
                                                        No Type 2 reward levels configured
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Type 3 Table -->
                        <div class="card mb-4 <?php echo $partner_rewards_active == 0 ? 'disabled-overlay' : ''; ?>">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <span class="badge badge-type-3 me-2">Type 3</span>
                                    Third Deposit Reward Configuration
                                </h5>
                                <span class="badge bg-label-danger">Levels: <?php echo count($reward_config[3]); ?></span>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive text-nowrap">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Min Recharge (₹)</th>
                                                <th>Max Recharge (₹)</th>
                                                <th>Required Bet (₹)</th>
                                                <th>Reward (₹)</th>
                                                <th>Last Updated</th>
                                                <th class="table-actions">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="table-border-bottom-0">
                                            <?php if (!empty($reward_config[3])): ?>
                                                <?php foreach ($reward_config[3] as $level): ?>
                                                <tr>
                                                    <td><strong><?php echo $level['id']; ?></strong></td>
                                                    <td>₹<?php echo number_format($level['min_recharge'], 2); ?></td>
                                                    <td>₹<?php echo $level['max_recharge'] == 99999999.99 ? '∞' : number_format($level['max_recharge'], 2); ?></td>
                                                    <td>₹<?php echo number_format($level['bet'], 2); ?></td>
                                                    <td><span class="badge bg-label-success">₹<?php echo number_format($level['reward'], 2); ?></span></td>
                                                    <td><?php echo date('d/m/Y H:i', strtotime($level['updated_at'])); ?></td>
                                                    <td class="table-actions action-buttons">
                                                        <button class="btn btn-sm btn-icon btn-label-warning edit-btn" 
                                                                data-id="<?php echo $level['id']; ?>"
                                                                data-type="3"
                                                                data-min="<?php echo $level['min_recharge']; ?>"
                                                                data-max="<?php echo $level['max_recharge']; ?>"
                                                                data-bet="<?php echo $level['bet']; ?>"
                                                                data-reward="<?php echo $level['reward']; ?>"
                                                                <?php echo $partner_rewards_active == 0 ? 'disabled' : ''; ?>>
                                                            <i class="ri-edit-line"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-icon btn-label-danger delete-btn" 
                                                                data-id="<?php echo $level['id']; ?>"
                                                                <?php echo $partner_rewards_active == 0 ? 'disabled' : ''; ?>>
                                                            <i class="ri-delete-bin-line"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="7" class="text-center text-muted py-4">
                                                        <i class="ri-information-line me-2"></i>
                                                        No Type 3 reward levels configured
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <?php require_once("footer.php"); ?>
            </div>
        </div>
    </div>

    <!-- Add Modal -->
    <div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ri-add-circle-line me-2"></i>Add New Reward Level</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="addForm">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Deposit Type <span class="text-danger">*</span></label>
                                <select class="form-select" name="type" required>
                                    <option value="">Select Type</option>
                                    <option value="1">Type 1 (First Deposit)</option>
                                    <option value="2">Type 2 (Second Deposit)</option>
                                    <option value="3">Type 3 (Third Deposit)</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Reward Amount (₹) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="reward" step="0.01" min="0" placeholder="Enter reward amount" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Min Recharge (₹) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="min_recharge" step="0.01" min="0" placeholder="Minimum recharge amount" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Max Recharge (₹) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="max_recharge" step="0.01" min="0" placeholder="Maximum recharge amount" required>
                                <div class="form-text">Use 99999999 for unlimited</div>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Required Bet Amount (₹) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="bet" step="0.01" min="0" placeholder="Required bet amount" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" id="addSubmitBtn">
                            <span class="form-spinner spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                            Add Level
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ri-edit-line me-2"></i>Edit Reward Level</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="editForm">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Deposit Type <span class="text-danger">*</span></label>
                                <select class="form-select" name="type" id="edit_type" required>
                                    <option value="">Select Type</option>
                                    <option value="1">Type 1 (First Deposit)</option>
                                    <option value="2">Type 2 (Second Deposit)</option>
                                    <option value="3">Type 3 (Third Deposit)</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Reward Amount (₹) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="reward" id="edit_reward" step="0.01" min="0" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Min Recharge (₹) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="min_recharge" id="edit_min" step="0.01" min="0" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Max Recharge (₹) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="max_recharge" id="edit_max" step="0.01" min="0" required>
                                <div class="form-text">Use 99999999 for unlimited</div>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Required Bet Amount (₹) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="bet" id="edit_bet" step="0.01" min="0" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" id="editSubmitBtn">
                            <span class="form-spinner spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                            Update Level
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ri-delete-bin-line me-2"></i>Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <i class="ri-alert-line text-danger display-4"></i>
                    </div>
                    <p class="text-center">Are you sure you want to delete this reward level?</p>
                    <p class="text-danger text-center"><strong>This action cannot be undone!</strong></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                        <span class="form-spinner spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                        Delete
                    </button>
                </div>
            </div>
        </div>
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
        let deleteId = null;
        
        function toggleSystemStatus() {
    var toggle = document.getElementById('statusToggle');
    // FIX: Active (checked) = 1, Inactive (unchecked) = 0
    var newStatus = toggle.checked ? '1' : '0';
    document.getElementById('newStatus').value = newStatus;
    
    // Show confirmation
    var statusText = toggle.checked ? 'Active' : 'Inactive';
    var confirmMessage = 'Are you sure you want to ' + (toggle.checked ? 'activate' : 'deactivate') + ' the Partner Rewards system?\n\n' +
                       (toggle.checked 
                        ? '✅ Users will be able to earn rewards through referrals.\n✅ Reward calculations will be active.'
                        : '⚠️ Users will NOT be able to earn rewards.\n⚠️ Reward calculations will be paused.');
    
    if (confirm(confirmMessage)) {
        // Show loading toast
        showToast('Updating system status...', 'info');
        
        // Submit form
        document.getElementById('toggleForm').submit();
    } else {
        // Reset toggle if cancelled
        toggle.checked = !toggle.checked;
    }
}
        
        function showToast(message, type = 'success') {
            // Remove existing toast
            const existingToast = document.querySelector('.toast-container');
            if (existingToast) {
                existingToast.remove();
            }
            
            const icon = type === 'success' ? 'check' : 
                        type === 'info' ? 'information' : 
                        type === 'warning' ? 'alert' : 'error-warning';
            
            const toastHTML = `
                <div class="toast-container position-fixed top-0 end-0 p-3">
                    <div class="toast align-items-center text-white bg-${type} border-0" role="alert">
                        <div class="d-flex">
                            <div class="toast-body">
                                <i class="ri-${icon}-line me-2"></i>
                                ${message}
                            </div>
                            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', toastHTML);
            const toastEl = document.querySelector('.toast');
            const toast = new bootstrap.Toast(toastEl);
            toast.show();
            
            // Auto remove after 3 seconds
            setTimeout(() => {
                if (toastEl) toastEl.remove();
            }, 3000);
        }
        
        // Check localStorage for success/error messages
        document.addEventListener('DOMContentLoaded', function() {
            if (localStorage.getItem('showSuccess') === 'true') {
                showToast('Operation completed successfully!', 'success');
                localStorage.removeItem('showSuccess');
            }
            
            if (localStorage.getItem('showError') === 'true') {
                showToast('Operation failed!', 'danger');
                localStorage.removeItem('showError');
            }
            
            // Check if system is active/inactive
            const systemStatus = <?php echo $partner_rewards_active; ?>;
            if (systemStatus === 0) {
                showToast('Partner Rewards system is currently inactive. Enable it to manage levels.', 'warning');
            }
            
            // Auto close alerts after 5 seconds
            setTimeout(function() {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(alert => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 5000);
            
            // Edit button click
            $('.edit-btn').click(function() {
                $('#edit_id').val($(this).data('id'));
                $('#edit_type').val($(this).data('type'));
                $('#edit_min').val($(this).data('min'));
                $('#edit_max').val($(this).data('max'));
                $('#edit_bet').val($(this).data('bet'));
                $('#edit_reward').val($(this).data('reward'));
                
                $('#editModal').modal('show');
            });
            
            // Delete button click
            $('.delete-btn').click(function() {
                deleteId = $(this).data('id');
                $('#deleteModal').modal('show');
            });
            
            // Confirm delete
            $('#confirmDeleteBtn').click(function() {
                $('#confirmDeleteBtn').prop('disabled', true);
                $('#confirmDeleteBtn .form-spinner').addClass('active');
                
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = '';
                
                var actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'delete';
                form.appendChild(actionInput);
                
                var idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'id';
                idInput.value = deleteId;
                form.appendChild(idInput);
                
                document.body.appendChild(form);
                form.submit();
            });
            
            // Add form submit
            $('#addForm').submit(function(e) {
                e.preventDefault();
                $('#addSubmitBtn').prop('disabled', true);
                $('#addSubmitBtn .form-spinner').addClass('active');
                this.submit();
            });
            
            // Edit form submit
            $('#editForm').submit(function(e) {
                e.preventDefault();
                $('#editSubmitBtn').prop('disabled', true);
                $('#editSubmitBtn .form-spinner').addClass('active');
                this.submit();
            });
            
            // Modal focus
            $('#addModal').on('shown.bs.modal', function () {
                $('#addModal [name="type"]').focus();
            });
            
            $('#editModal').on('shown.bs.modal', function () {
                $('#editModal [name="type"]').focus();
            });
            
            // Reset form states
            $('#addModal').on('hidden.bs.modal', function () {
                $('#addForm')[0].reset();
                $('#addSubmitBtn').prop('disabled', false);
                $('#addSubmitBtn .form-spinner').removeClass('active');
            });
            
            $('#editModal').on('hidden.bs.modal', function () {
                $('#editSubmitBtn').prop('disabled', false);
                $('#editSubmitBtn .form-spinner').removeClass('active');
            });
            
            $('#deleteModal').on('hidden.bs.modal', function () {
                $('#confirmDeleteBtn').prop('disabled', false);
                $('#confirmDeleteBtn .form-spinner').removeClass('active');
            });
        });
    </script>
</body>
</html>