<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

session_start();
if (!isset($_SESSION['unohs'])) {
    header("Location: index.php?msg=unauthorized");
    exit;
}
include("api/conn.php");

$agent_id = '';
$selected_date = isset($_GET['date']) && !empty($_GET['date']) ? $_GET['date'] : '';
$selected_level = isset($_GET['level']) ? intval($_GET['level']) : 0; // 0 = All levels
$agent_exists = false;
$agent_info = [];
$error_message = '';

// Levels data array
$levels_data = [
    1 => ['name' => 'Level 1', 'code_field' => 'code'],
    2 => ['name' => 'Level 2', 'code_field' => 'code1'],
    3 => ['name' => 'Level 3', 'code_field' => 'code2'],
    4 => ['name' => 'Level 4', 'code_field' => 'code3'],
    5 => ['name' => 'Level 5', 'code_field' => 'code4'],
    6 => ['name' => 'Level 6', 'code_field' => 'code5']
];

// Statistics arrays
$level_stats = [];
$overall_stats = [
    'total_users' => 0,
    'recharge_users' => 0,
    'recharge_count' => 0,
    'recharge_amount' => 0,
    'withdrawal_count' => 0,
    'withdrawal_amount' => 0,
    'unique_recharge_users' => []
];

// If form is submitted, redirect to GET
if (isset($_POST['search'])) {
    $agent_id = intval($_POST['agent_id']);
    if ($agent_id > 0) {
        header("Location: ".$_SERVER['PHP_SELF']."?agent_id=".$agent_id."&date=".$selected_date."&level=".$selected_level);
        exit;
    } else {
        $error_message = "Please enter a valid agent ID.";
    }
}

// If redirected or loaded with GET
if (isset($_GET['agent_id'])) {
    $agent_id = intval($_GET['agent_id']);
    
    // Check if agent exists and get agent info
    $agent_check = mysqli_query($conn, "SELECT id, mobile, owncode FROM shonu_subjects WHERE id='$agent_id'");
    if (mysqli_num_rows($agent_check) > 0) {
        $agent_exists = true;
        $agent_info = mysqli_fetch_assoc($agent_check);
        $owncode = $agent_info['owncode'] ?? '';

        if ($owncode) {
            // Initialize level stats
            foreach ($levels_data as $level => $level_info) {
                $level_stats[$level] = [
                    'name' => $level_info['name'],
                    'total_users' => 0,
                    'recharge_users' => 0,
                    'recharge_count' => 0,
                    'recharge_amount' => 0,
                    'withdrawal_count' => 0,
                    'withdrawal_amount' => 0,
                    'users' => []
                ];
            }

            // Process each level
            foreach ($levels_data as $level => $level_info) {
                $code_field = $level_info['code_field'];
                
                // Get users for this level
                $users_query = "SELECT id, mobile FROM shonu_subjects WHERE $code_field = '$owncode'";
                $users_result = mysqli_query($conn, $users_query);
                
                if ($users_result) {
                    $level_users = [];
                    while ($user = mysqli_fetch_assoc($users_result)) {
                        $userid = $user['id'];
                        $mobile = $user['mobile'];
                        
                        // Initialize user stats
                        $user_stats = [
                            'userid' => $userid,
                            'mobile' => $mobile,
                            'recharge_count' => 0,
                            'recharge_amount' => 0,
                            'withdrawal_count' => 0,
                            'withdrawal_amount' => 0
                        ];
                        
                        // Get recharge stats for this user
                        $recharge_query = "SELECT 
                            COUNT(shonu) as recharge_count,
                            SUM(motta) as recharge_amount 
                            FROM thevani 
                            WHERE balakedara = '$userid' 
                            AND sthiti = '1'";
                        
                        if (!empty($selected_date)) {
                            $recharge_query .= " AND DATE(dinankavannuracisi) = '$selected_date'";
                        }
                        
                        $recharge_result = mysqli_query($conn, $recharge_query);
                        if ($recharge_row = mysqli_fetch_assoc($recharge_result)) {
                            $user_stats['recharge_count'] = intval($recharge_row['recharge_count'] ?? 0);
                            $user_stats['recharge_amount'] = floatval($recharge_row['recharge_amount'] ?? 0);
                            
                            if ($user_stats['recharge_count'] > 0) {
                                $level_stats[$level]['recharge_users']++;
                                $overall_stats['unique_recharge_users'][$userid] = true;
                            }
                        }
                        
                        // Get withdrawal stats for this user
                        $withdrawal_query = "SELECT 
                            COUNT(shonu) as withdrawal_count,
                            SUM(motta) as withdrawal_amount 
                            FROM hintegedukolli 
                            WHERE balakedara = '$userid' 
                            AND sthiti = '1'";
                        
                        if (!empty($selected_date)) {
                            $withdrawal_query .= " AND DATE(createdate) = '$selected_date'";
                        }
                        
                        $withdrawal_result = mysqli_query($conn, $withdrawal_query);
                        if ($withdrawal_row = mysqli_fetch_assoc($withdrawal_result)) {
                            $user_stats['withdrawal_count'] = intval($withdrawal_row['withdrawal_count'] ?? 0);
                            $user_stats['withdrawal_amount'] = floatval($withdrawal_row['withdrawal_amount'] ?? 0);
                        }
                        
                        // Add to level stats
                        $level_users[] = $user_stats;
                        $level_stats[$level]['total_users']++;
                        $level_stats[$level]['recharge_count'] += $user_stats['recharge_count'];
                        $level_stats[$level]['recharge_amount'] += $user_stats['recharge_amount'];
                        $level_stats[$level]['withdrawal_count'] += $user_stats['withdrawal_count'];
                        $level_stats[$level]['withdrawal_amount'] += $user_stats['withdrawal_amount'];
                        
                        // Add to overall stats
                        $overall_stats['total_users']++;
                        $overall_stats['recharge_count'] += $user_stats['recharge_count'];
                        $overall_stats['recharge_amount'] += $user_stats['recharge_amount'];
                        $overall_stats['withdrawal_count'] += $user_stats['withdrawal_count'];
                        $overall_stats['withdrawal_amount'] += $user_stats['withdrawal_amount'];
                    }
                    
                    $level_stats[$level]['users'] = $level_users;
                }
            }
            
            $overall_stats['recharge_users'] = count($overall_stats['unique_recharge_users']);
        }
    } else {
        $error_message = "Invalid agent ID. Please enter a valid agent ID.";
    }
}
?>

<!DOCTYPE html>
<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr" 
    data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template" data-style="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Agent Recharge Report</title>
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
        .level-card {
            transition: all 0.3s ease;
            border-left: 4px solid;
            cursor: pointer;
        }
        .level-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .level-card.active {
            background: rgba(78, 115, 223, 0.05);
            border-left-color: #4e73df !important;
        }
        .stat-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
        }
        .user-table th {
            font-size: 0.8125rem;
            font-weight: 600;
        }
        .level-1 { border-left-color: #4e73df; }
        .level-2 { border-left-color: #1cc88a; }
        .level-3 { border-left-color: #36b9cc; }
        .level-4 { border-left-color: #f6c23e; }
        .level-5 { border-left-color: #e74a3b; }
        .level-6 { border-left-color: #6f42c1; }
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
                        
                        <!-- Error Message -->
                        <?php if (!empty($error_message)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?= $error_message ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">Agent Levels Report</h5>
                            </div>
                            <div class="card-body">
                                <!-- Search Form -->
                                <div class="row mb-4">
                                    <div class="col-md-12">
                                        <form method="POST" class="row g-3">
                                            <div class="col-md-3">
                                                <label class="form-label">Agent ID</label>
                                                <input type="number" class="form-control" name="agent_id" 
                                                       value="<?= htmlspecialchars($agent_id) ?>" 
                                                       placeholder="Enter Agent ID" required>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Date</label>
                                                <input type="date" class="form-control" name="date" 
                                                       value="<?= htmlspecialchars($selected_date) ?>">
                                                <small class="text-muted">Leave empty for all time</small>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Level</label>
                                                <select class="form-select" name="level">
                                                    <option value="0" <?= $selected_level == 0 ? 'selected' : '' ?>>All Levels</option>
                                                    <!--<?php foreach($levels_data as $level => $data): ?>-->
                                                    <!--    <option value="<?= $level ?>" <?= $selected_level == $level ? 'selected' : '' ?>>-->
                                                    <!--        <?= $data['name'] ?>-->
                                                    <!--    </option>-->
                                                    <!--<?php endforeach; ?>-->
                                                </select>
                                            </div>
                                            <div class="col-md-3 d-flex align-items-end">
                                                <button type="submit" name="search" class="btn btn-primary">
                                                    <i class="ri-search-line me-1"></i>Search
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                                <?php if ($agent_exists): ?>
                                    <!-- Agent Information -->
                                    <div class="row mb-4">
                                        <div class="col-12">
                                            <div class="alert alert-info">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <h6 class="alert-heading mb-1">Agent Information</h6>
                                                        <p class="mb-0">
                                                            <strong>Agent ID:</strong> <?= htmlspecialchars($agent_info['id']) ?> | 
                                                            <strong>Mobile:</strong> <?= htmlspecialchars($agent_info['mobile']) ?> | 
                                                            <strong>Own Code:</strong> <?= htmlspecialchars($agent_info['owncode']) ?>
                                                        </p>
                                                    </div>
                                                    <div class="text-end">
                                                        <small>
                                                            <?= !empty($selected_date) ? 'Date: ' . date('d M Y', strtotime($selected_date)) : 'All Time' ?>
                                                            <?= $selected_level > 0 ? ' | Level: ' . $levels_data[$selected_level]['name'] : '' ?>
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Overall Statistics -->
                                    <div class="row mb-4">
                                        <div class="col-lg-2 col-md-4 col-sm-6 mb-4">
                                            <div class="card">
                                                <div class="card-body text-center">
                                                    <div class="stat-value text-primary"><?= number_format($overall_stats['total_users']) ?></div>
                                                    <div class="stat-label">Total Users</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-2 col-md-4 col-sm-6 mb-4">
                                            <div class="card">
                                                <div class="card-body text-center">
                                                    <div class="stat-value text-success"><?= number_format($overall_stats['recharge_users']) ?></div>
                                                    <div class="stat-label">Recharge Users</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-2 col-md-4 col-sm-6 mb-4">
                                            <div class="card">
                                                <div class="card-body text-center">
                                                    <div class="stat-value text-info"><?= number_format($overall_stats['recharge_count']) ?></div>
                                                    <div class="stat-label">Recharge Count</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-2 col-md-4 col-sm-6 mb-4">
                                            <div class="card">
                                                <div class="card-body text-center">
                                                    <div class="stat-value text-warning">₹<?= number_format($overall_stats['recharge_amount'], 2) ?></div>
                                                    <div class="stat-label">Total Recharge</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-2 col-md-4 col-sm-6 mb-4">
                                            <div class="card">
                                                <div class="card-body text-center">
                                                    <div class="stat-value text-danger"><?= number_format($overall_stats['withdrawal_count']) ?></div>
                                                    <div class="stat-label">Withdrawal Count</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-2 col-md-4 col-sm-6 mb-4">
                                            <div class="card">
                                                <div class="card-body text-center">
                                                    <div class="stat-value text-secondary">₹<?= number_format($overall_stats['withdrawal_amount'], 2) ?></div>
                                                    <div class="stat-label">Total Withdrawal</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Levels Statistics -->
                                    <div class="row mb-4">
                                        <?php foreach($level_stats as $level => $stats): ?>
                                            <?php 
                                                $is_active = ($selected_level == 0 || $selected_level == $level);
                                                $card_class = $is_active ? 'active' : '';
                                            ?>
                                            <div class="col-lg-4 col-md-6 mb-4">
                                                <div class="card level-card level-<?= $level ?> <?= $card_class ?>" 
                                                     onclick="filterLevel(<?= $level ?>)">
                                                    <div class="card-body">
                                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                                            <h6 class="card-title mb-0"><?= $stats['name'] ?></h6>
                                                            <span class="badge bg-label-primary"><?= $stats['total_users'] ?> Users</span>
                                                        </div>
                                                        <div class="row text-center">
                                                            <div class="col-6 mb-2">
                                                                <small class="text-muted d-block">Recharge Users</small>
                                                                <strong class="text-success"><?= $stats['recharge_users'] ?></strong>
                                                            </div>
                                                            <div class="col-6 mb-2">
                                                                <small class="text-muted d-block">Recharge Count</small>
                                                                <strong class="text-info"><?= $stats['recharge_count'] ?></strong>
                                                            </div>
                                                            <div class="col-6">
                                                                <small class="text-muted d-block">Recharge Amount</small>
                                                                <strong class="text-warning">₹<?= number_format($stats['recharge_amount'], 2) ?></strong>
                                                            </div>
                                                            <div class="col-6">
                                                                <small class="text-muted d-block">Withdrawal Amount</small>
                                                                <strong class="text-danger">₹<?= number_format($stats['withdrawal_amount'], 2) ?></strong>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>

                                    <!-- Users Table (Filtered by selected level) -->
                                    <?php if ($selected_level == 0 || isset($level_stats[$selected_level])): ?>
                                        <?php 
                                            $display_stats = $selected_level == 0 ? $overall_stats : $level_stats[$selected_level];
                                            $display_users = $selected_level == 0 ? [] : $level_stats[$selected_level]['users'];
                                        ?>
                                        
                                        <div class="card">
                                            <div class="card-header">
                                                <h6 class="card-title mb-0">
                                                    <?= $selected_level == 0 ? 'All Users Summary' : $levels_data[$selected_level]['name'] . ' Users' ?>
                                                    <span class="badge bg-primary ms-2"><?= $display_stats['total_users'] ?> Users</span>
                                                </h6>
                                            </div>
                                            <div class="card-body">
                                                <?php if ($selected_level > 0 && !empty($display_users)): ?>
                                                    <div class="table-responsive">
                                                        <table class="table table-hover user-table">
                                                            <thead>
                                                                <tr>
                                                                    <th>#</th>
                                                                    <th>User ID</th>
                                                                    <th>Mobile</th>
                                                                    <th>Recharge Count</th>
                                                                    <th>Recharge Amount</th>
                                                                    <th>Withdrawal Count</th>
                                                                    <th>Withdrawal Amount</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php foreach($display_users as $index => $user): ?>
                                                                    <tr>
                                                                        <td><?= $index + 1 ?></td>
                                                                        <td>
                                                                            <strong><?= htmlspecialchars($user['userid']) ?></strong>
                                                                        </td>
                                                                        <td><?= htmlspecialchars($user['mobile']) ?></td>
                                                                        <td>
                                                                            <span class="badge bg-info"><?= $user['recharge_count'] ?></span>
                                                                        </td>
                                                                        <td>
                                                                            <span class="text-success">₹<?= number_format($user['recharge_amount'], 2) ?></span>
                                                                        </td>
                                                                        <td>
                                                                            <span class="badge bg-danger"><?= $user['withdrawal_count'] ?></span>
                                                                        </td>
                                                                        <td>
                                                                            <span class="text-warning">₹<?= number_format($user['withdrawal_amount'], 2) ?></span>
                                                                        </td>
                                                                    </tr>
                                                                <?php endforeach; ?>
                                                            </tbody>
                                                            <tfoot>
                                                                <tr class="table-primary">
                                                                    <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                                                    <td><strong><?= $display_stats['recharge_count'] ?></strong></td>
                                                                    <td><strong>₹<?= number_format($display_stats['recharge_amount'], 2) ?></strong></td>
                                                                    <td><strong><?= $display_stats['withdrawal_count'] ?></strong></td>
                                                                    <td><strong>₹<?= number_format($display_stats['withdrawal_amount'], 2) ?></strong></td>
                                                                </tr>
                                                            </tfoot>
                                                        </table>
                                                    </div>
                                                <?php elseif ($selected_level > 0): ?>
                                                    <div class="text-center py-4">
                                                        <div class="text-muted">
                                                            <i class="ri-user-search-line display-4"></i>
                                                            <h5 class="mt-3">No Users Found</h5>
                                                            <p>No users found in <?= $levels_data[$selected_level]['name'] ?> for this agent.</p>
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <!-- Summary for All Levels -->
                                                    <div class="table-responsive">
                                                        <table class="table table-hover">
                                                            <thead>
                                                                <tr>
                                                                    <th>Level</th>
                                                                    <th>Total Users</th>
                                                                    <th>Recharge Users</th>
                                                                    <th>Recharge Count</th>
                                                                    <th>Recharge Amount</th>
                                                                    <th>Withdrawal Count</th>
                                                                    <th>Withdrawal Amount</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php foreach($level_stats as $level => $stats): ?>
                                                                    <tr onclick="filterLevel(<?= $level ?>)" style="cursor: pointer;">
                                                                        <td>
                                                                            <strong><?= $stats['name'] ?></strong>
                                                                        </td>
                                                                        <td><?= $stats['total_users'] ?></td>
                                                                        <td><?= $stats['recharge_users'] ?></td>
                                                                        <td><?= $stats['recharge_count'] ?></td>
                                                                        <td class="text-success">₹<?= number_format($stats['recharge_amount'], 2) ?></td>
                                                                        <td><?= $stats['withdrawal_count'] ?></td>
                                                                        <td class="text-warning">₹<?= number_format($stats['withdrawal_amount'], 2) ?></td>
                                                                    </tr>
                                                                <?php endforeach; ?>
                                                            </tbody>
                                                            <tfoot>
                                                                <tr class="table-primary">
                                                                    <td><strong>Total</strong></td>
                                                                    <td><strong><?= $overall_stats['total_users'] ?></strong></td>
                                                                    <td><strong><?= $overall_stats['recharge_users'] ?></strong></td>
                                                                    <td><strong><?= $overall_stats['recharge_count'] ?></strong></td>
                                                                    <td><strong class="text-success">₹<?= number_format($overall_stats['recharge_amount'], 2) ?></strong></td>
                                                                    <td><strong><?= $overall_stats['withdrawal_count'] ?></strong></td>
                                                                    <td><strong class="text-warning">₹<?= number_format($overall_stats['withdrawal_amount'], 2) ?></strong></td>
                                                                </tr>
                                                            </tfoot>
                                                        </table>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                <?php elseif (isset($_GET['agent_id'])): ?>
                                    <!-- No Agent Found -->
                                    <div class="text-center py-5">
                                        <div class="text-muted">
                                            <i class="ri-user-unfollow-line display-4"></i>
                                            <h5 class="mt-3">Agent Not Found</h5>
                                            <p>The agent ID <?= htmlspecialchars($_GET['agent_id']) ?> does not exist in the system.</p>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <!-- Initial State -->
                                    <div class="text-center py-5">
                                        <div class="text-muted">
                                            <i class="ri-user-search-line display-4"></i>
                                            <h5 class="mt-3">Search Agent Levels Report</h5>
                                            <p>Enter an Agent ID to view detailed statistics of all levels (1-6).</p>
                                        </div>
                                    </div>
                                <?php endif; ?>
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
        function filterLevel(level) {
            const url = new URL(window.location.href);
            url.searchParams.set('level', level);
            window.location.href = url.toString();
        }

        $(document).ready(function() {
            // Auto-submit form when date or level changes
            $('input[name="date"], select[name="level"]').on('change', function() {
                if ($('input[name="agent_id"]').val() && $('input[name="agent_id"]').val() > 0) {
                    $('form').submit();
                }
            });

            // Initialize DataTable for users table
            if ($('.user-table').length) {
                $('.user-table').DataTable({
                    "paging": true,
                    "lengthChange": true,
                    "searching": true,
                    "ordering": true,
                    "info": true,
                    "autoWidth": true,
                    "pageLength": 10,
                    "dom": '<"row"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6"p>>',
                    "language": {
                        search: '',
                        searchPlaceholder: 'Search users...',
                        paginate: {
                            next: '<i class="ri-arrow-right-s-line"></i>',
                            previous: '<i class="ri-arrow-left-s-line"></i>'
                        }
                    },
                    "order": [[0, 'asc']]
                });
            }
        });
    </script>
</body>
</html>