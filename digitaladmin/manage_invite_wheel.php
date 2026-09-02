<?php
session_start();
if(empty($_SESSION['unohs'])){
    header("location: api/login.php?msg=unauthorized");
}
include ("api/conn.php");


if (isset($_POST['update_spin_settings'])) {
    $start_spin = $_POST['start_spin'];
    
    $stmt = $conn->prepare("UPDATE invitewheel_setting SET start_spin = ? WHERE id = 1");
    $stmt->bind_param("i", $start_spin);
    $stmt->execute();
    
    $_SESSION['success'] = "Spin settings updated successfully!";
    
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_settings'])) {
        $wheel_image = $_POST['wheel_image'];
        $spin_reward = $_POST['spin_reward'];
        $withdraw_rules = $_POST['withdraw_rules'];
        
        $stmt = $conn->prepare("UPDATE invitewheel_setting SET wheel_image = ?, spin_reward = ?, withdraw_rules = ? WHERE id = 1");
        $stmt->bind_param("sds", $wheel_image, $spin_reward, $withdraw_rules);
        $stmt->execute();
        
        $_SESSION['success'] = "Settings updated successfully!";
    }
    
    if (isset($_POST['update_prize'])) {
        $id = $_POST['prize_id'];
        $amount = $_POST['amount'];
        $probability = $_POST['probability'];
        $is_win = isset($_POST['is_win']) ? 1 : 0;
        
        $stmt = $conn->prepare("UPDATE invitewheel_amounts SET amount = ?, probability = ?, is_win = ? WHERE id = ?");
        $stmt->bind_param("ddii", $amount, $probability, $is_win, $id);
        $stmt->execute();
        
        $_SESSION['success'] = "Prize updated successfully!";
    }
    
    if (isset($_POST['add_prize'])) {
        $spin_type = $_POST['spin_type'];
        $amount = $_POST['amount'];
        $probability = $_POST['probability'];
        $is_win = isset($_POST['is_win']) ? 1 : 0;
        
        $stmt = $conn->prepare("INSERT INTO invitewheel_amounts (spin_type, amount, probability, is_win) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sddi", $spin_type, $amount, $probability, $is_win);
        $stmt->execute();
        
        $_SESSION['success'] = "Prize added successfully!";
    }
    
    if (isset($_POST['update_user_data'])) {
        $user_id = $_POST['user_id'];
        $total_spins = $_POST['total_spins'];
        $invited_wheel_amount = $_POST['invited_wheel_amount'];
        
        $stmt = $conn->prepare("UPDATE shonu_turntable SET total_spins = ?, invited_wheel_amount = ? WHERE user_id = ?");
        $stmt->bind_param("ids", $total_spins, $invited_wheel_amount, $user_id);
        $stmt->execute();
        
        $_SESSION['success'] = "User data updated successfully!";
    }
    
    if (isset($_POST['delete_prize'])) {
        $id = $_POST['prize_id'];
        $stmt = $conn->prepare("DELETE FROM invitewheel_amounts WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        $_SESSION['success'] = "Prize deleted successfully!";
    }
    
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}

// Get statistics
$total_users = $conn->query("SELECT COUNT(*) as total FROM shonu_turntable")->fetch_assoc()['total'];
$total_spins = $conn->query("SELECT SUM(total_spins) as total FROM shonu_turntable")->fetch_assoc()['total'];
$total_amount = $conn->query("SELECT SUM(invited_wheel_amount) as total FROM shonu_turntable")->fetch_assoc()['total'];
$today_spins = $conn->query("SELECT COUNT(*) as total FROM shonu_turntable_spins WHERE DATE(spin_time) = CURDATE()")->fetch_assoc()['total'];

// Get wheel settings
$settings = $conn->query("SELECT * FROM invitewheel_setting WHERE id = 1")->fetch_assoc();

// Get first spin prizes
$first_prizes = $conn->query("SELECT * FROM invitewheel_amounts WHERE spin_type = 'first' ORDER BY amount")->fetch_all(MYSQLI_ASSOC);

// Get regular spin prizes
$regular_prizes = $conn->query("SELECT * FROM invitewheel_amounts WHERE spin_type = 'regular' ORDER BY probability DESC")->fetch_all(MYSQLI_ASSOC);

// User Management with Pagination
$search_user = isset($_GET['search_user']) ? $_GET['search_user'] : '';
$user_page = isset($_GET['user_page']) ? (int)$_GET['user_page'] : 1;
$user_limit = 10;
$user_offset = ($user_page - 1) * $user_limit;

// Count total users
$count_query = "SELECT COUNT(*) as total FROM shonu_turntable";
if (!empty($search_user)) {
    $count_query .= " WHERE user_id LIKE '%$search_user%'";
}
$total_users_count = $conn->query($count_query)->fetch_assoc()['total'];
$total_user_pages = ceil($total_users_count / $user_limit);

// Get paginated users
$user_query = "SELECT * FROM shonu_turntable";
if (!empty($search_user)) {
    $user_query .= " WHERE user_id LIKE '%$search_user%'";
}
$user_query .= " LIMIT $user_limit OFFSET $user_offset";
$users = $conn->query($user_query)->fetch_all(MYSQLI_ASSOC);

// User History with Pagination
$history_user_id = isset($_GET['history_user']) ? $_GET['history_user'] : '';
$history_date = isset($_GET['history_date']) ? $_GET['history_date'] : '';
$history_page = isset($_GET['history_page']) ? (int)$_GET['history_page'] : 1;
$history_limit = 10;
$history_offset = ($history_page - 1) * $history_limit;

$user_history = [];
$total_history_records = 0;
$total_history_pages = 0;

if (!empty($history_user_id)) {
    // Count history records
    $history_count_query = "SELECT COUNT(*) as total FROM shonu_turntable_spins WHERE user_id = '$history_user_id'";
    if (!empty($history_date)) {
        $history_count_query .= " AND DATE(spin_time) = '$history_date'";
    }
    $total_history_records = $conn->query($history_count_query)->fetch_assoc()['total'];
    $total_history_pages = ceil($total_history_records / $history_limit);
    
    // Get paginated history
    $history_query = "SELECT * FROM shonu_turntable_spins WHERE user_id = '$history_user_id'";
    if (!empty($history_date)) {
        $history_query .= " AND DATE(spin_time) = '$history_date'";
    }
    $history_query .= " ORDER BY spin_time DESC LIMIT $history_limit OFFSET $history_offset";
    $user_history = $conn->query($history_query)->fetch_all(MYSQLI_ASSOC);
}
?>

<!doctype html>
<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr"
    data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template" data-style="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Invite Wheel Management</title>
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
</head>

<body>
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <?php require_once("layout-menu.php"); ?>
            <div class="layout-page">
                <?php require_once("nav.php"); ?>
                <div class="content-wrapper">
                    <div class="container-xxl flex-grow-1 container-p-y">
                        
                        <!-- Success Message -->
                        <?php if(isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible" role="alert">
                            <?= $_SESSION['success'] ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['success']); endif; ?>

                        <!-- Statistics Cards -->
                        <div class="row mb-4">
                            <div class="col-lg-3 col-sm-6 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="card-title d-flex align-items-start justify-content-between">
                                            <div class="avatar flex-shrink-0">
                                                <div class="bg-label-primary rounded p-2">
                                                    <i class="ri-user-line text-primary"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <span class="fw-semibold d-block mb-1">Total Users</span>
                                        <h3 class="card-title mb-2"><?= $total_users ?></h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-sm-6 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="card-title d-flex align-items-start justify-content-between">
                                            <div class="avatar flex-shrink-0">
                                                <div class="bg-label-success rounded p-2">
                                                    <i class="ri-refresh-line text-success"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <span class="fw-semibold d-block mb-1">Total Spins</span>
                                        <h3 class="card-title mb-2"><?= $total_spins ?: 0 ?></h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-sm-6 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="card-title d-flex align-items-start justify-content-between">
                                            <div class="avatar flex-shrink-0">
                                                <div class="bg-label-warning rounded p-2">
                                                    <i class="ri-money-dollar-circle-line text-warning"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <span class="fw-semibold d-block mb-1">Total Amount</span>
                                        <h3 class="card-title mb-2">₹<?= number_format($total_amount ?: 0, 2) ?></h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-sm-6 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="card-title d-flex align-items-start justify-content-between">
                                            <div class="avatar flex-shrink-0">
                                                <div class="bg-label-info rounded p-2">
                                                    <i class="ri-calendar-line text-info"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <span class="fw-semibold d-block mb-1">Today's Spins</span>
                                        <h3 class="card-title mb-2"><?= $today_spins ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- First Spin Settings -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">First Spin Settings</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Set Users First Spin</label>
                        <select class="form-select" name="start_spin" required>
                            <option value="1" <?= isset($settings['start_spin']) && $settings['start_spin'] == 1 ? 'selected' : '' ?>>1 Spins</option>
                            <option value="2" <?= isset($settings['start_spin']) && $settings['start_spin'] == 2 ? 'selected' : '' ?>>2 Spins</option>
                            <option value="3" <?= isset($settings['start_spin']) && $settings['start_spin'] == 3 ? 'selected' : '' ?>>3 Spins</option>
                            <option value="4" <?= isset($settings['start_spin']) && $settings['start_spin'] == 4 ? 'selected' : '' ?>>4 Spins</option>
                            <option value="5" <?= isset($settings['start_spin']) && $settings['start_spin'] == 5 ? 'selected' : '' ?>>5 Spins</option>
                            <option value="6" <?= isset($settings['start_spin']) && $settings['start_spin'] == 6 ? 'selected' : '' ?>>6 Spins</option>
                            <option value="7" <?= isset($settings['start_spin']) && $settings['start_spin'] == 7 ? 'selected' : '' ?>>7 Spins</option>
                            <option value="8" <?= isset($settings['start_spin']) && $settings['start_spin'] == 8 ? 'selected' : '' ?>>8 Spins</option>
                            <option value="9" <?= isset($settings['start_spin']) && $settings['start_spin'] == 9 ? 'selected' : '' ?>>9 Spins</option>
                            <option value="10" <?= isset($settings['start_spin']) && $settings['start_spin'] == 10 ? 'selected' : '' ?>>10 Spins</option>
                        </select>
                        <div class="form-text">Set how many spins users get when they first join</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button type="submit" name="update_spin_settings" class="btn btn-primary">
                                <i class="ri-save-line"></i> Update Spin Settings
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>


                        <!-- Wheel Settings -->
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Wheel Settings</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Wheel Image URL</label>
                                                <input type="text" class="form-control" name="wheel_image" id="wheelImageInput" 
                                                       value="<?= $settings['wheel_image'] ?>" required
                                                       onchange="updateImagePreview()">
                                                <div class="form-text">Paste image URL above to see preview</div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Spin Reward (₹)</label>
                                                <input type="number" step="0.01" class="form-control" name="spin_reward" value="<?= $settings['spin_reward'] ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <!-- Image Preview -->
                                            <div class="mb-3">
                                                <label class="form-label">Wheel Preview</label>
                                                <div class="border rounded p-3 text-center bg-light" style="min-height: 200px;">
                                                    <img id="wheelImagePreview" 
                                                         src="<?= $settings['wheel_image'] ?>" 
                                                         alt="Wheel Preview" 
                                                         class="img-fluid rounded" 
                                                         style="max-height: 180px; display: <?= !empty($settings['wheel_image']) ? 'block' : 'none' ?>;">
                                                    <div id="noImageText" class="text-muted" style="display: <?= empty($settings['wheel_image']) ? 'block' : 'none' ?>;">
                                                        <i class="ri-image-line display-4"></i>
                                                        <p class="mt-2 mb-0">No image preview available</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="mb-3">
                                                <label class="form-label">Withdraw Rules</label>
                                                <textarea class="form-control" name="withdraw_rules" rows="4" required><?= $settings['withdraw_rules'] ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="submit" name="update_settings" class="btn btn-primary">Update Settings</button>
                                </form>
                            </div>
                        </div>

                        <div class="row">
                            <!-- First Spin Prizes -->
                            <div class="col-lg-6 mb-4">
                                <div class="card h-100">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0">First Spin Prizes</h5>
                                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addFirstPrizeModal" <?= count($first_prizes) >= 4 ? 'disabled' : '' ?>>
                                            <i class="ri-add-line"></i> Add Prize
                                        </button>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Amount</th>
                                                        <th>Probability</th>
                                                        <th>Status</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach($first_prizes as $prize): ?>
                                                    <tr>
                                                        <td><strong>₹<?= number_format($prize['amount'], 2) ?></strong></td>
                                                        <td><span class="badge bg-label-primary"><?= ($prize['probability'] * 100) ?>%</span></td>
                                                        <td>
                                                            <span class="badge <?= $prize['is_win'] ? 'bg-label-success' : 'bg-label-danger' ?>">
                                                                <?= $prize['is_win'] ? 'Win' : 'Lose' ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <div class="d-flex gap-2">
                                                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editPrizeModal" 
                                                                    data-id="<?= $prize['id'] ?>" 
                                                                    data-amount="<?= $prize['amount'] ?>" 
                                                                    data-probability="<?= $prize['probability'] ?>" 
                                                                    data-iswin="<?= $prize['is_win'] ?>">
                                                                    <i class="ri-edit-line"></i>
                                                                </button>
                                                                <form method="POST" class="d-inline">
                                                                    <input type="hidden" name="prize_id" value="<?= $prize['id'] ?>">
                                                                    <button type="submit" name="delete_prize" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure?')">
                                                                        <i class="ri-delete-bin-line"></i>
                                                                    </button>
                                                                </form>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Regular Spin Prizes -->
                            <div class="col-lg-6 mb-4">
                                <div class="card h-100">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0">Regular Spin Prizes</h5>
                                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addRegularPrizeModal" <?= count($regular_prizes) >= 15 ? 'disabled' : '' ?>>
                                            <i class="ri-add-line"></i> Add Prize
                                        </button>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Amount</th>
                                                        <th>Probability</th>
                                                        <th>Status</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach($regular_prizes as $prize): ?>
                                                    <tr>
                                                        <td><strong>₹<?= number_format($prize['amount'], 2) ?></strong></td>
                                                        <td><span class="badge bg-label-info"><?= ($prize['probability'] * 100) ?>%</span></td>
                                                        <td>
                                                            <span class="badge <?= $prize['is_win'] ? 'bg-label-success' : 'bg-label-danger' ?>">
                                                                <?= $prize['is_win'] ? 'Win' : 'Lose' ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <div class="d-flex gap-2">
                                                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editPrizeModal" 
                                                                    data-id="<?= $prize['id'] ?>" 
                                                                    data-amount="<?= $prize['amount'] ?>" 
                                                                    data-probability="<?= $prize['probability'] ?>" 
                                                                    data-iswin="<?= $prize['is_win'] ?>">
                                                                    <i class="ri-edit-line"></i>
                                                                </button>
                                                                <form method="POST" class="d-inline">
                                                                    <input type="hidden" name="prize_id" value="<?= $prize['id'] ?>">
                                                                    <button type="submit" name="delete_prize" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure?')">
                                                                        <i class="ri-delete-bin-line"></i>
                                                                    </button>
                                                                </form>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- User Management with Pagination -->
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">User Management</h5>
                                <form method="GET" class="d-flex gap-2">
                                    <input type="hidden" name="user_page" value="1">
                                    <input type="text" class="form-control" name="search_user" placeholder="Search User ID" value="<?= $search_user ?>" style="width: 200px;">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="ri-search-line"></i> Search
                                    </button>
                                    <?php if(!empty($search_user)): ?>
                                        <a href="manage_invite_wheel.php" class="btn btn-secondary">Clear</a>
                                    <?php endif; ?>
                                </form>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>User ID</th>
                                                <th>Total Spins</th>
                                                <th>Wheel Amount</th>
                                                <th>Created At</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if(empty($users)): ?>
                                                <tr>
                                                    <td colspan="5" class="text-center">No users found</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach($users as $user): ?>
                                                <tr>
                                                    <td><strong><?= $user['user_id'] ?></strong></td>
                                                    <td><span class="badge bg-label-primary"><?= $user['total_spins'] ?></span></td>
                                                    <td><strong>₹<?= number_format($user['invited_wheel_amount'], 2) ?></strong></td>
                                                    <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                                                    <td>
                                                        <div class="d-flex gap-2">
                                                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editUserModal" 
                                                                data-userid="<?= $user['user_id'] ?>" 
                                                                data-totalspins="<?= $user['total_spins'] ?>" 
                                                                data-wheelamount="<?= $user['invited_wheel_amount'] ?>">
                                                                <i class="ri-edit-line"></i> Edit
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-outline-info history-btn" 
                                                                data-userid="<?= $user['user_id'] ?>">
                                                                <i class="ri-history-line"></i> History
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <!-- User Pagination -->
                                <?php if($total_user_pages > 1): ?>
                                <nav aria-label="Page navigation" class="mt-4">
                                    <ul class="pagination justify-content-center">
                                        <li class="page-item <?= $user_page <= 1 ? 'disabled' : '' ?>">
                                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['user_page' => $user_page - 1])) ?>" aria-label="Previous">
                                                <span aria-hidden="true">&laquo;</span>
                                            </a>
                                        </li>
                                        
                                        <?php for($i = 1; $i <= $total_user_pages; $i++): ?>
                                            <li class="page-item <?= $i == $user_page ? 'active' : '' ?>">
                                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['user_page' => $i])) ?>"><?= $i ?></a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <li class="page-item <?= $user_page >= $total_user_pages ? 'disabled' : '' ?>">
                                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['user_page' => $user_page + 1])) ?>" aria-label="Next">
                                                <span aria-hidden="true">&raquo;</span>
                                            </a>
                                        </li>
                                    </ul>
                                </nav>
                                
                                <div class="text-center text-muted">
                                    Showing <?= count($users) ?> of <?= $total_users_count ?> users
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

    <!-- Modals -->
    <!-- Edit Prize Modal -->
    <div class="modal fade" id="editPrizeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Prize</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="prize_id" id="edit_prize_id">
                        <div class="mb-3">
                            <label class="form-label">Amount (₹)</label>
                            <input type="number" step="0.01" class="form-control" name="amount" id="edit_amount" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Probability (0-1)</label>
                            <input type="number" step="0.01" min="0" max="1" class="form-control" name="probability" id="edit_probability" required>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_win" id="edit_is_win">
                                <label class="form-check-label">Is Winning Prize</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="update_prize" class="btn btn-primary">Update Prize</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add First Prize Modal -->
    <div class="modal fade" id="addFirstPrizeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add First Spin Prize</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="spin_type" value="first">
                        <div class="mb-3">
                            <label class="form-label">Amount (₹)</label>
                            <input type="number" step="0.01" class="form-control" name="amount" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Probability (0-1)</label>
                            <input type="number" step="0.01" min="0" max="1" class="form-control" name="probability" required>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_win" checked>
                                <label class="form-check-label">Is Winning Prize</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="add_prize" class="btn btn-primary">Add Prize</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Regular Prize Modal -->
    <div class="modal fade" id="addRegularPrizeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Regular Spin Prize</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="spin_type" value="regular">
                        <div class="mb-3">
                            <label class="form-label">Amount (₹)</label>
                            <input type="number" step="0.01" class="form-control" name="amount" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Probability (0-1)</label>
                            <input type="number" step="0.01" min="0" max="1" class="form-control" name="probability" required>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_win" checked>
                                <label class="form-check-label">Is Winning Prize</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="add_prize" class="btn btn-primary">Add Prize</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit User Data</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="user_id" id="edit_user_id">
                        <div class="mb-3">
                            <label class="form-label">Total Spins</label>
                            <input type="number" class="form-control" name="total_spins" id="edit_total_spins" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Wheel Amount (₹)</label>
                            <input type="number" step="0.01" class="form-control" name="invited_wheel_amount" id="edit_wheel_amount" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="update_user_data" class="btn btn-primary">Update User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- User History Modal -->
    <div class="modal fade" id="userHistoryModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Spin History - User: <span id="historyUserName"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="GET" class="d-flex gap-2 mb-3">
                        <input type="hidden" name="history_user" id="historyUserInput" value="<?= $history_user_id ?>">
                        <input type="hidden" name="history_page" value="1">
                        <input type="date" class="form-control" name="history_date" id="historyDateInput" value="<?= $history_date ?>">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="?history_user=<?= $history_user_id ?>" class="btn btn-secondary">Clear</a>
                    </form>
                    
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Spin Time</th>
                                    <th>User Name</th>
                                    <th>Prize Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($user_history)): ?>
                                    <tr>
                                        <td colspan="3" class="text-center">No spin history found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach($user_history as $spin): ?>
                                    <tr>
                                        <td><?= date('M d, Y H:i:s', strtotime($spin['spin_time'])) ?></td>
                                        <td><?= $spin['user_name'] ?></td>
                                        <td><strong>₹<?= number_format($spin['prize_amount'], 2) ?></strong></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- History Pagination -->
                    <?php if($total_history_pages > 1): ?>
                    <nav aria-label="History pagination" class="mt-3">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?= $history_page <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['history_page' => $history_page - 1])) ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                            
                            <?php for($i = 1; $i <= $total_history_pages; $i++): ?>
                                <li class="page-item <?= $i == $history_page ? 'active' : '' ?>">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['history_page' => $i])) ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?= $history_page >= $total_history_pages ? 'disabled' : '' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['history_page' => $history_page + 1])) ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                    
                    <div class="text-center text-muted">
                        Showing <?= count($user_history) ?> of <?= $total_history_records ?> spin records
                    </div>
                    <?php endif; ?>
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
    <script src="assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js"></script>
    <script src="assets/js/main.js"></script>

    <script>
    // Wheel Image Preview Function
    function updateImagePreview() {
        const imageUrl = document.getElementById('wheelImageInput').value;
        const preview = document.getElementById('wheelImagePreview');
        const noImageText = document.getElementById('noImageText');
        
        if (imageUrl) {
            preview.src = imageUrl;
            preview.style.display = 'block';
            noImageText.style.display = 'none';
            
            preview.onload = function() {
                console.log('Image loaded successfully');
            }
            
            preview.onerror = function() {
                preview.style.display = 'none';
                noImageText.style.display = 'block';
                noImageText.innerHTML = '<i class="ri-error-warning-line display-4 text-danger"></i><p class="mt-2 mb-0 text-danger">Invalid image URL</p>';
            }
        } else {
            preview.style.display = 'none';
            noImageText.style.display = 'block';
            noImageText.innerHTML = '<i class="ri-image-line display-4"></i><p class="mt-2 mb-0">No image preview available</p>';
        }
    }

    // Initialize preview on page load
    document.addEventListener('DOMContentLoaded', function() {
        updateImagePreview();
        
        // History button click handler
        $('.history-btn').click(function() {
            const userId = $(this).data('userid');
            $('#historyUserInput').val(userId);
            $('#historyUserName').text(userId);
            $('#userHistoryModal').modal('show');
        });
    });

    // Edit Prize Modal
    $('#editPrizeModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var id = button.data('id');
        var amount = button.data('amount');
        var probability = button.data('probability');
        var isWin = button.data('iswin');
        
        var modal = $(this);
        modal.find('#edit_prize_id').val(id);
        modal.find('#edit_amount').val(amount);
        modal.find('#edit_probability').val(probability);
        modal.find('#edit_is_win').prop('checked', isWin == 1);
    });

    // Edit User Modal
    $('#editUserModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var userId = button.data('userid');
        var totalSpins = button.data('totalspins');
        var wheelAmount = button.data('wheelamount');
        
        var modal = $(this);
        modal.find('#edit_user_id').val(userId);
        modal.find('#edit_total_spins').val(totalSpins);
        modal.find('#edit_wheel_amount').val(wheelAmount);
    });

    // Auto show history modal if URL has history parameter
    <?php if(!empty($history_user_id)): ?>
    $(document).ready(function() {
        $('#userHistoryModal').modal('show');
    });
    <?php endif; ?>
    </script>
</body>
</html>