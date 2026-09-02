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

// Simple error logging function
function logError($message) {
    $logFile = 'manage_agent_errorlog.txt';
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] $message\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

// Helper: safely decode JSON to array of ints
function json_to_ids(string $json): array {
    $arr = json_decode($json, true);
    if (!is_array($arr)) return [];
    $ids = array_map('intval', $arr);
    $ids = array_filter($ids, function($v){ return $v > 0; });
    $ids = array_values(array_unique($ids));
    return $ids;
}

// Helper: return comma-separated list for IN(...) or '0' when empty
function ids_for_in(array $ids): string {
    if (empty($ids)) return '0';
    return implode(',', $ids);
}

/*
  -----------------------------
  Add Agent (POST)
  -----------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['serial']) && !isset($_POST['update_id'])) {
    // Sanitize / cast
    $userid = intval($_POST['serial']);
    $salarypercent = isset($_POST['salarypercent']) ? floatval($_POST['salarypercent']) : 0;
    $salary = isset($_POST['salary']) ? floatval($_POST['salary']) : 0.0;
    $type = isset($_POST['type']) ? trim($_POST['type']) : '';
    $minrecharge = isset($_POST['minrecharge']) ? intval($_POST['minrecharge']) : 300;
    $minbet = isset($_POST['minbet']) ? intval($_POST['minbet']) : 3;
    $minreferrals = isset($_POST['minreferrals']) ? intval($_POST['minreferrals']) : 3;
    $createdate = date("Y-m-d H:i:s");

    // Basic validation
    if ($userid <= 0) {
        logError("Add Agent: Invalid User ID: $userid");
        $_SESSION['msg'] = "❌ Invalid User ID.";
        header("Location: manage_agents.php");
        exit;
    }
    if (!in_array($type, ['day', 'week', 'month'])) {
        logError("Add Agent: Invalid salary type: $type");
        $_SESSION['msg'] = "❌ Invalid salary type.";
        header("Location: manage_agents.php");
        exit;
    }

    // Check user exists and fetch mobile
    $sql = "SELECT mobile FROM shonu_subjects WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        $error = mysqli_error($conn);
        logError("Add Agent: Prepare failed (select mobile): $error");
        $_SESSION['msg'] = "❌ Database error. Contact admin.";
        header("Location: manage_agents.php");
        exit;
    }
    mysqli_stmt_bind_param($stmt, "i", $userid);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if (!$res || mysqli_num_rows($res) !== 1) {
        logError("Add Agent: UID not found: $userid");
        $_SESSION['msg'] = "❌ UID not found in subjects table.";
        mysqli_stmt_close($stmt);
        header("Location: manage_agents.php");
        exit;
    }
    $row = mysqli_fetch_assoc($res);
    $mobile = $row['mobile'] ?? '';
    mysqli_stmt_close($stmt);

    // Check if active agent already exists
    $sql = "SELECT id FROM tb_agent WHERE userid = ? AND status = 1 LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        $error = mysqli_error($conn);
        logError("Add Agent: Prepare failed (exists check): $error");
        $_SESSION['msg'] = "❌ Database error. Contact admin.";
        header("Location: manage_agents.php");
        exit;
    }
    mysqli_stmt_bind_param($stmt, "i", $userid);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($res && mysqli_num_rows($res) > 0) {
        logError("Add Agent: Agent already exists: $userid");
        $_SESSION['msg'] = "⚠️ Agent already exists and is active.";
        mysqli_stmt_close($stmt);
        header("Location: manage_agents.php");
        exit;
    }
    mysqli_stmt_close($stmt);

    // Insert agent with explicit columns (prepared)
    $sql = "INSERT INTO tb_agent 
    (userid, mobile, salary, salarypercent, type, minrecharge, minbet, minreferrals, createdate, status, teacherid)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)";
    $stmt = mysqli_prepare($conn, $sql);

    $teacherid = 0;
    mysqli_stmt_bind_param($stmt, "isddsiiisi",
        $userid,
        $mobile,
        $salary,
        $salarypercent,
        $type,
        $minrecharge,
        $minbet,
        $minreferrals,
        $createdate,
        $teacherid
    );

    $ok = mysqli_stmt_execute($stmt);
    if ($ok) {
        logError("Add Agent: Success - Agent ID: $userid added");
        $_SESSION['msg'] = "✅ Agent Added Successfully.";
    } else {
        $error = mysqli_stmt_error($stmt);
        logError("Add Agent: Insert failed: $error");
        $_SESSION['msg'] = "❌ Failed to add agent. DB error logged.";
    }
    mysqli_stmt_close($stmt);

    header("Location: manage_agents.php");
    exit;
}

/*
  -----------------------------
  Update Agent (via modal POST)
  -----------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_id'])) {
    $updateId = intval($_POST['update_id']);
    $salary = isset($_POST['edit_salary']) ? floatval($_POST['edit_salary']) : 0.0;
    $percent = isset($_POST['edit_percent']) ? floatval($_POST['edit_percent']) : 0.0;
    $type = isset($_POST['edit_type']) ? trim($_POST['edit_type']) : '';
    $minrecharge = isset($_POST['edit_minrecharge']) ? intval($_POST['edit_minrecharge']) : 300;
    $minbet = isset($_POST['edit_minbet']) ? intval($_POST['edit_minbet']) : 3;
    $minreferrals = isset($_POST['edit_minreferrals']) ? intval($_POST['edit_minreferrals']) : 3;

    if ($updateId <= 0 || !in_array($type, ['day','week','month'])) {
        logError("Update Agent: Invalid update data - ID: $updateId, Type: $type");
        $_SESSION['msg'] = "❌ Invalid update data.";
        header("Location: manage_agents.php");
        exit;
    }

    $sql = "UPDATE tb_agent 
            SET salary=?, salarypercent=?, type=?, minrecharge=?, minbet=?, minreferrals=?
            WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        $error = mysqli_error($conn);
        logError("Update Agent: Prepare failed: $error");
        $_SESSION['msg'] = "❌ Database error. Contact admin.";
        header("Location: manage_agents.php");
        exit;
    }
    mysqli_stmt_bind_param($stmt, "ddsiiii", $salary, $percent, $type, $minrecharge, $minbet, $minreferrals, $updateId);
    $ok = mysqli_stmt_execute($stmt);
    if ($ok) {
        logError("Update Agent: Success - Agent ID: $updateId updated");
        $_SESSION['msg'] = "✅ Agent updated successfully.";
    } else {
        $error = mysqli_stmt_error($stmt);
        logError("Update Agent: Update failed: $error");
        $_SESSION['msg'] = "❌ Failed to update agent. DB error logged.";
    }
    mysqli_stmt_close($stmt);

    header("Location: manage_agents.php");
    exit;
}

/*
  -----------------------------
  Delete Agent (GET)
  -----------------------------
*/
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delId = intval($_GET['delete']);
    logError("Delete Agent: Attempting to delete Agent ID: $delId");
    
    $sql = "DELETE FROM tb_agent WHERE id = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $delId);
        $ok = mysqli_stmt_execute($stmt);
        if ($ok) {
            logError("Delete Agent: Success - Agent ID: $delId deleted");
            $_SESSION['msg'] = "❌ Agent deleted successfully.";
        } else {
            $error = mysqli_stmt_error($stmt);
            logError("Delete Agent: Failed - $error");
            $_SESSION['msg'] = "❌ Failed to delete agent. DB error logged.";
        }
        mysqli_stmt_close($stmt);
    } else {
        $error = mysqli_error($conn);
        logError("Delete Agent: Prepare failed: $error");
        $_SESSION['msg'] = "❌ Database error. Contact admin.";
    }
    header("Location: manage_agents.php");
    exit;
}

/*
  -----------------------------
  Salary Calculation
  -----------------------------
*/
$salary_status = '';
if (isset($_POST['start_salary'])) {
    logError("Salary Calculation: Started");
    @include("../aks.php");
    $_SESSION['salary_status'] = "✅ Salary calculation run successfully based on tb_agent settings.";
    logError("Salary Calculation: Completed");
    header("Location: manage_agents.php");
    exit;
}

if (isset($_SESSION['salary_status'])) {
    $salary_status = $_SESSION['salary_status'];
    unset($_SESSION['salary_status']);
}

/*
  -----------------------------
  Get Agent Details for View Modal
  -----------------------------
*/
function getAgentDetails($conn, $agentId) {
    $details = [];
    
    // Get agent basic info
    $agent_sql = "SELECT a.*, s.owncode 
                  FROM tb_agent a 
                  LEFT JOIN shonu_subjects s ON s.id = a.userid 
                  WHERE a.id = ?";
    $stmt = mysqli_prepare($conn, $agent_sql);
    mysqli_stmt_bind_param($stmt, "i", $agentId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $agent = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if (!$agent) return null;
    
    $details['agent'] = $agent;
    $owncode = $agent['owncode'];
    
    if (!$owncode) {
        return $details;
    }
    
    // Get users by level
    $levels = ['code', 'code1', 'code2', 'code3', 'code4', 'code5'];
    $level_counts = [];
    $all_user_ids = [];
    
    foreach ($levels as $index => $level) {
        $level_sql = "SELECT id, mobile FROM shonu_subjects WHERE $level = ?";
        $stmt = mysqli_prepare($conn, $level_sql);
        mysqli_stmt_bind_param($stmt, "s", $owncode);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $users = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $users[] = $row;
            $all_user_ids[] = $row['id'];
        }
        mysqli_stmt_close($stmt);
        
        $level_counts['lvl' . ($index + 1)] = [
            'count' => count($users),
            'users' => $users
        ];
    }
    
    $details['level_counts'] = $level_counts;
    $all_user_ids = array_unique($all_user_ids);
    $details['all_user_ids'] = $all_user_ids;
    
    // Get deposit statistics
    if (!empty($all_user_ids)) {
        $user_ids_str = implode(',', $all_user_ids);
        
        // Successful deposits
        $success_sql = "SELECT balakedara, motta FROM thevani WHERE balakedara IN ($user_ids_str) AND sthiti = 1";
        $result = mysqli_query($conn, $success_sql);
        $success_deposits = [];
        $total_success = 0;
        while ($row = mysqli_fetch_assoc($result)) {
            $success_deposits[] = $row;
            $total_success += $row['motta'];
        }
        
        // Pending deposits
        $pending_sql = "SELECT balakedara, motta FROM thevani WHERE balakedara IN ($user_ids_str) AND sthiti = 0";
        $result = mysqli_query($conn, $pending_sql);
        $pending_deposits = [];
        $total_pending = 0;
        while ($row = mysqli_fetch_assoc($result)) {
            $pending_deposits[] = $row;
            $total_pending += $row['motta'];
        }
        
        // Rejected deposits
        $rejected_sql = "SELECT balakedara, motta FROM thevani WHERE balakedara IN ($user_ids_str) AND sthiti = 2";
        $result = mysqli_query($conn, $rejected_sql);
        $rejected_deposits = [];
        $total_rejected = 0;
        while ($row = mysqli_fetch_assoc($result)) {
            $rejected_deposits[] = $row;
            $total_rejected += $row['motta'];
        }
        
        $details['deposits'] = [
            'success' => [
                'count' => count($success_deposits),
                'total_amount' => $total_success,
                'users' => $success_deposits
            ],
            'pending' => [
                'count' => count($pending_deposits),
                'total_amount' => $total_pending,
                'users' => $pending_deposits
            ],
            'rejected' => [
                'count' => count($rejected_deposits),
                'total_amount' => $total_rejected,
                'users' => $rejected_deposits
            ]
        ];
        
        // Get betting statistics
        $bet_tables = [
            'bajikattuttate', 'bajikattuttate_drei', 'bajikattuttate_funf', 'bajikattuttate_zehn',
            'bajikattuttate_kemuru', 'bajikattuttate_kemuru_drei', 'bajikattuttate_kemuru_funf', 'bajikattuttate_kemuru_zehn',
            'bajikattuttate_aidudi', 'bajikattuttate_aidudi_drei', 'bajikattuttate_aidudi_funf', 'bajikattuttate_aidudi_zehn'
        ];
        
        $total_bet = 0;
        $bet_details = [];
        
        foreach ($bet_tables as $table) {
            $bet_sql = "SELECT SUM(ketebida) as total FROM `$table` WHERE byabaharkarta IN ($user_ids_str)";
            $result = mysqli_query($conn, $bet_sql);
            if ($result) {
                $row = mysqli_fetch_assoc($result);
                $table_total = $row['total'] ?? 0;
                $total_bet += $table_total;
                $bet_details[$table] = $table_total;
            }
        }
        
        $details['betting'] = [
            'total_bet' => $total_bet,
            'details' => $bet_details
        ];
        
        // Get withdrawal statistics
        $withdrawal_sql = "SELECT SUM(motta) as total FROM hintegedukolli WHERE sthiti = 1 AND balakedara IN ($user_ids_str)";
        $result = mysqli_query($conn, $withdrawal_sql);
        $withdrawal_row = mysqli_fetch_assoc($result);
        $total_withdrawal = $withdrawal_row['total'] ?? 0;
        
        $details['withdrawal'] = $total_withdrawal;
        
        // Get total recharge (all time)
        $recharge_sql = "SELECT SUM(motta) as total FROM thevani WHERE sthiti = 1 AND balakedara IN ($user_ids_str)";
        $result = mysqli_query($conn, $recharge_sql);
        $recharge_row = mysqli_fetch_assoc($result);
        $total_recharge = $recharge_row['total'] ?? 0;
        
        $details['recharge'] = $total_recharge;
    }
    
    return $details;
}

// Handle view agent request
if (isset($_GET['view_agent']) && is_numeric($_GET['view_agent'])) {
    $agentDetails = getAgentDetails($conn, intval($_GET['view_agent']));
    if ($agentDetails) {
        header('Content-Type: application/json');
        echo json_encode($agentDetails);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Agent not found']);
    }
    exit;
}

// ========== PAGE DISPLAY LOGIC ==========

// Initialize variables
$total_agents = 0;
$total_succ_rech = 0;
$total_fail_rech = 0;
$lastRun = null;
$results = false;
$total_filtered_agents = 0;
$current_page = 1;
$total_pages = 0;

// total agents (active only)
$total_agents_query = "SELECT COUNT(*) AS cnt FROM tb_agent WHERE status = 1";
$total_agents_result = mysqli_query($conn, $total_agents_query);
if ($total_agents_result) {
    $total_agents_row = mysqli_fetch_assoc($total_agents_result);
    $total_agents = (int)($total_agents_row['cnt'] ?? 0);
} else {
    logError("Total agents query failed: " . mysqli_error($conn));
}

// Get all active agents userids first
$agent_ids = [];
$agent_query = mysqli_query($conn, "SELECT userid FROM tb_agent WHERE status = 1");
while($agent = mysqli_fetch_assoc($agent_query)) {
    $agent_ids[] = (int)$agent['userid'];
}

// Success count - FIXED SIMPLE VERSION
if (!empty($agent_ids)) {
    // Convert to strings for comparison with code columns
    $agent_str_ids = array_map('strval', $agent_ids);
    $agent_id_list = "'" . implode("','", $agent_str_ids) . "'";
    
    $succ_sql = "
      SELECT COUNT(DISTINCT t.shonu) AS cnt
      FROM thevani t
      JOIN shonu_subjects s ON s.id = t.balakedara
      WHERE t.sthiti = 1
      AND (
        s.code1 IN ($agent_id_list) OR
        s.code2 IN ($agent_id_list) OR
        s.code3 IN ($agent_id_list) OR
        s.code4 IN ($agent_id_list) OR
        s.code5 IN ($agent_id_list)
      )
    ";
    
    $succ_result = mysqli_query($conn, $succ_sql);
    if ($succ_result) {
        $succ_data = mysqli_fetch_assoc($succ_result);
        $total_succ_rech = (int)($succ_data['cnt'] ?? 0);
    } else {
        logError("Success query failed: " . mysqli_error($conn));
        $total_succ_rech = 0;
    }
    
    // Failed count - FIXED SIMPLE VERSION
    $fail_sql = "
      SELECT COUNT(DISTINCT t.shonu) AS cnt
      FROM thevani t
      JOIN shonu_subjects s ON s.id = t.balakedara
      WHERE t.sthiti = 0
      AND (
        s.code1 IN ($agent_id_list) OR
        s.code2 IN ($agent_id_list) OR
        s.code3 IN ($agent_id_list) OR
        s.code4 IN ($agent_id_list) OR
        s.code5 IN ($agent_id_list)
      )
    ";
    
    $fail_result = mysqli_query($conn, $fail_sql);
    if ($fail_result) {
        $fail_data = mysqli_fetch_assoc($fail_result);
        $total_fail_rech = (int)($fail_data['cnt'] ?? 0);
    } else {
        logError("Failed query error: " . mysqli_error($conn));
        $total_fail_rech = 0;
    }
} else {
    // No active agents
    $total_succ_rech = 0;
    $total_fail_rech = 0;
}

// last run from dailysalary table
$lastRun_result = mysqli_query($conn, "SELECT MAX(createdate) as lastRun FROM dailysalary");
if ($lastRun_result) {
    $lastRun_row = mysqli_fetch_assoc($lastRun_result);
    $lastRun = $lastRun_row['lastRun'] ?? null;
}

// limit handling
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
$valid_limits = [10, 50, 100, 200];
if (!in_array($limit, $valid_limits)) $limit = 50;

// Build search condition
$search_condition = "";
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = mysqli_real_escape_string($conn, $_GET['search']);
    $search_condition = " AND (a.userid LIKE '%$search_term%' OR a.mobile LIKE '%$search_term%')";
}

// Calculate total agents for pagination (with search filter)
$total_query = "SELECT COUNT(*) AS cnt FROM tb_agent a WHERE a.status = 1 $search_condition";
$total_result = mysqli_query($conn, $total_query);
if ($total_result) {
    $total_row = mysqli_fetch_assoc($total_result);
    $total_filtered_agents = (int)($total_row['cnt'] ?? 0);
} else {
    logError("Total filtered agents query failed: " . mysqli_error($conn));
}

// Calculate offset for pagination
$current_page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($current_page - 1) * $limit;

// Calculate pagination
if ($total_filtered_agents > 0) {
    $total_pages = ceil($total_filtered_agents / $limit);
}

// Updated results query with search and pagination
$results_sql = "
  SELECT a.*, ds.salary as last_salary, ds.createdate AS salary_createdate, ds.tsruser, ds.tfruser, ds.tsbuser, ds.tfbuser
  FROM tb_agent a
  LEFT JOIN (
    SELECT d1.*
    FROM dailysalary d1
    INNER JOIN (
      SELECT userid, MAX(createdate) AS mx
      FROM dailysalary
      GROUP BY userid
    ) d2 ON d1.userid = d2.userid AND d1.createdate = d2.mx
  ) ds ON ds.userid = a.userid
  WHERE a.status = 1
  $search_condition
  ORDER BY a.createdate DESC
  LIMIT $limit OFFSET $offset
";

$results = mysqli_query($conn, $results_sql);
if (!$results) {
    logError("Main results query failed: " . mysqli_error($conn));
    // Fallback query
    $results = mysqli_query($conn, "SELECT * FROM tb_agent WHERE status = 1 $search_condition ORDER BY createdate DESC LIMIT $limit OFFSET $offset");
}
?>

<!DOCTYPE html>
<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr" 
    data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template" data-style="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Manage Agents</title>
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
        .stat-change {
            font-size: 0.75rem;
        }
        .progress-sm {
            height: 4px;
        }
        .user-actions .btn {
            margin: 1px;
            padding: 0.25rem 0.5rem;
        }
        .table-actions {
            white-space: nowrap;
        }
        .status-badge {
            font-size: 0.7rem;
        }
        
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        .table-responsive table {
            min-width: 1200px;
            width: 100%;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .level-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
        }
        .user-list {
            max-height: 200px;
            overflow-y: auto;
        }
        .stats-number {
            font-size: 1.5rem;
            font-weight: bold;
        }
        .stats-label {
            font-size: 0.875rem;
            color: #6c757d;
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

                        <?php if (!empty($salary_status)): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?= $salary_status ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <div class="row">
                            <!-- Statistics Cards -->
                            <div class="col-lg-3 col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div class="card-info">
                                                <p class="card-text">Total Agents</p>
                                                <div class="d-flex align-items-end mb-2">
                                                    <h4 class="card-title mb-0 me-2"><?= $total_agents ?></h4>
                                                </div>
                                            </div>
                                            <div class="card-icon">
                                                <span class="badge bg-label-primary rounded p-2">
                                                    <i class="ri-team-line"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-lg-3 col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div class="card-info">
                                                <p class="card-text">Successful Recharges</p>
                                                <div class="d-flex align-items-end mb-2">
                                                    <h4 class="card-title mb-0 me-2"><?= $total_succ_rech ?></h4>
                                                </div>
                                            </div>
                                            <div class="card-icon">
                                                <span class="badge bg-label-success rounded p-2">
                                                    <i class="ri-check-double-line"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-lg-3 col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div class="card-info">
                                                <p class="card-text">Failed Recharges</p>
                                                <div class="d-flex align-items-end mb-2">
                                                    <h4 class="card-title mb-0 me-2"><?= $total_fail_rech ?></h4>
                                                </div>
                                            </div>
                                            <div class="card-icon">
                                                <span class="badge bg-label-danger rounded p-2">
                                                    <i class="ri-close-line"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-lg-3 col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div class="card-info">
                                                <p class="card-text">Last Salary Run</p>
                                                <div class="d-flex align-items-end mb-2">
                                                    <h4 class="card-title mb-0 me-2">
                                                        <?= $lastRun ? date('d M Y', strtotime($lastRun)) : 'Never' ?>
                                                    </h4>
                                                </div>
                                            </div>
                                            <div class="card-icon">
                                                <span class="badge bg-label-info rounded p-2">
                                                    <i class="ri-calendar-event-line"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">Manage Agents</h5>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAgentModal">
                                        <i class="ri-user-add-line me-1"></i>Add New Agent
                                    </button>
                                    <form method="GET" class="d-flex gap-2">
                                        <input type="text" name="search" class="form-control" placeholder="Search by User ID or Mobile..." 
                                               value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                                        <button type="submit" class="btn btn-outline-primary">
                                            <i class="ri-search-line me-1"></i>Search
                                        </button>
                                        <?php if (isset($_GET['search'])): ?>
                                            <a href="manage_agents.php" class="btn btn-secondary">Clear</a>
                                        <?php endif; ?>
                                    </form>
                                    <form method="POST" class="ms-2">
                                        <button type="submit" name="start_salary" class="btn btn-success" 
                                                onclick="return confirm('Are you sure you want to run salary calculation?')">
                                            <i class="ri-calculator-line me-1"></i>Run Salary
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <div class="card-body">
                                
                                <!-- Pagination and Limit Controls -->
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <span>Show:</span>
                                        <select class="form-select form-select-sm" style="width: auto;" onchange="window.location.href='?limit='+this.value+'<?= isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?>'">
                                            <?php foreach ($valid_limits as $l): ?>
                                                <option value="<?= $l ?>" <?= $limit == $l ? 'selected' : '' ?>><?= $l ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <span>entries</span>
                                    </div>
                                    <div class="text-muted">
                                        Showing <?= min($offset + 1, $total_filtered_agents) ?> to <?= min($offset + $limit, $total_filtered_agents) ?> of <?= $total_filtered_agents ?> entries
                                    </div>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>User ID</th>
                                                <th>Mobile</th>
                                                <th>Salary Type</th>
                                                <th>Base Salary</th>
                                                <th>Salary %</th>
                                                <th>Min Requirements</th>
                                                <th>Last Salary</th>
                                                <th>Salary Stats</th>
                                                <th>Status</th>
                                                <th>Created Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            if ($results && mysqli_num_rows($results) > 0):
                                            $counter = $offset + 1;
                                            while ($agent = mysqli_fetch_assoc($results)): 
                                            ?>
                                                <tr>
                                                    <td><?= $counter++ ?></td>
                                                    <td>
                                                        <strong><?= htmlspecialchars($agent['userid']) ?></strong>
                                                    </td>
                                                    <td><?= htmlspecialchars($agent['mobile']) ?></td>
                                                    <td>
                                                        <span class="badge bg-label-<?= $agent['type'] == 'day' ? 'primary' : ($agent['type'] == 'week' ? 'warning' : 'success') ?>">
                                                            <?= ucfirst($agent['type']) ?>
                                                        </span>
                                                    </td>
                                                    <td>₹<?= number_format($agent['salary'], 2) ?></td>
                                                    <td><?= number_format($agent['salarypercent'], 2) ?>%</td>
                                                    <td>
                                                        <small class="text-muted">
                                                            Recharge: ₹<?= number_format($agent['minrecharge']) ?><br>
                                                            Bet: <?= $agent['minbet'] ?><br>
                                                            Referrals: <?= $agent['minreferrals'] ?>
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <?php if (!empty($agent['salary_createdate'])): ?>
                                                            ₹<?= number_format($agent['last_salary'] ?? 0, 2) ?><br>
                                                            <small class="text-muted"><?= date('d M Y', strtotime($agent['salary_createdate'])) ?></small>
                                                        <?php else: ?>
                                                            <span class="text-muted">Not Paid</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($agent['tsruser'] || $agent['tfruser'] || $agent['tsbuser'] || $agent['tfbuser']): ?>
                                                            <small>
                                                                S.Rech: <?= $agent['tsruser'] ?? 0 ?><br>
                                                                F.Rech: <?= $agent['tfruser'] ?? 0 ?><br>
                                                                S.Bet: <?= $agent['tsbuser'] ?? 0 ?><br>
                                                                F.Bet: <?= $agent['tfbuser'] ?? 0 ?>
                                                            </small>
                                                        <?php else: ?>
                                                            <span class="text-muted">No Data</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-label-<?= $agent['status'] == 1 ? 'success' : 'danger' ?>">
                                                            <?= $agent['status'] == 1 ? 'Active' : 'Inactive' ?>
                                                        </span>
                                                    </td>
                                                    <td><?= date('d M Y', strtotime($agent['createdate'])) ?></td>
                                                    <td>
                                                        <div class="d-flex gap-2">
                                                            <button type="button" class="btn btn-sm btn-icon btn-outline-info view-agent" 
                                                                    data-bs-toggle="modal" data-bs-target="#viewAgentModal"
                                                                    data-id="<?= $agent['id'] ?>">
                                                                <i class="ri-eye-line"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-icon btn-outline-primary edit-agent" 
                                                                    data-bs-toggle="modal" data-bs-target="#editAgentModal"
                                                                    data-id="<?= $agent['id'] ?>"
                                                                    data-salary="<?= $agent['salary'] ?>"
                                                                    data-percent="<?= $agent['salarypercent'] ?>"
                                                                    data-type="<?= $agent['type'] ?>"
                                                                    data-minrecharge="<?= $agent['minrecharge'] ?>"
                                                                    data-minbet="<?= $agent['minbet'] ?>"
                                                                    data-minreferrals="<?= $agent['minreferrals'] ?>">
                                                                <i class="ri-edit-line"></i>
                                                            </button>
                                                            <a href="manage_agents.php?delete=<?= $agent['id'] ?>" 
                                                               class="btn btn-sm btn-icon btn-outline-danger"
                                                               onclick="return confirm('Are you sure you want to delete this agent?')">
                                                                <i class="ri-delete-bin-line"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="12" class="text-center py-4">
                                                        <div class="text-muted">
                                                            <i class="ri-search-line display-4"></i>
                                                            <p class="mt-2">No agents found</p>
                                                            <?php if (isset($_GET['search'])): ?>
                                                                <a href="manage_agents.php" class="btn btn-primary btn-sm">Clear Search</a>
                                                            <?php endif; ?>
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
                                                <a class="page-link" href="?page=<?= $current_page - 1 ?>&limit=<?= $limit ?><?= isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?>" aria-label="Previous">
                                                    <i class="ri-arrow-left-s-line"></i>
                                                </a>
                                            </li>
                                            
                                            <!-- Page Numbers -->
                                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                                <li class="page-item <?= $i == $current_page ? 'active' : '' ?>">
                                                    <a class="page-link" href="?page=<?= $i ?>&limit=<?= $limit ?><?= isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?>">
                                                        <?= $i ?>
                                                    </a>
                                                </li>
                                            <?php endfor; ?>
                                            
                                            <!-- Next Page -->
                                            <li class="page-item <?= $current_page >= $total_pages ? 'disabled' : '' ?>">
                                                <a class="page-link" href="?page=<?= $current_page + 1 ?>&limit=<?= $limit ?><?= isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?>" aria-label="Next">
                                                    <i class="ri-arrow-right-s-line"></i>
                                                </a>
                                            </li>
                                        </ul>
                                    </nav>
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

    <!-- Add Agent Modal -->
    <div class="modal fade" id="addAgentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Agent</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">User ID</label>
                                <input type="number" class="form-control" name="serial" required placeholder="Enter User ID">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Salary</label>
                                <input type="number" step="0.01" class="form-control" name="salary" placeholder="0.00">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Salary Percentage</label>
                                <input type="number" step="0.01" class="form-control" name="salarypercent" placeholder="0.00">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Salary Type</label>
                                <select class="form-select" name="type" required>
                                    <option value="day">Daily</option>
                                    <option value="week">Weekly</option>
                                    <option value="month">Monthly</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Min Recharge</label>
                                <input type="number" class="form-control" name="minrecharge" value="300">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Min Bet</label>
                                <input type="number" class="form-control" name="minbet" value="3">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Min Referrals</label>
                                <input type="number" class="form-control" name="minreferrals" value="3">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add Agent</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Agent Modal -->
    <div class="modal fade" id="editAgentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Agent</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="update_id" id="edit_agent_id">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Salary</label>
                                <input type="number" step="0.01" class="form-control" name="edit_salary" id="edit_salary" placeholder="0.00">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Salary Percentage</label>
                                <input type="number" step="0.01" class="form-control" name="edit_percent" id="edit_percent" placeholder="0.00">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Salary Type</label>
                                <select class="form-select" name="edit_type" id="edit_type" required>
                                    <option value="day">Daily</option>
                                    <option value="week">Weekly</option>
                                    <option value="month">Monthly</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Min Recharge</label>
                                <input type="number" class="form-control" name="edit_minrecharge" id="edit_minrecharge">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Min Bet</label>
                                <input type="number" class="form-control" name="edit_minbet" id="edit_minbet">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Min Referrals</label>
                                <input type="number" class="form-control" name="edit_minreferrals" id="edit_minreferrals">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Update Agent</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Agent Modal -->
    <div class="modal fade" id="viewAgentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Agent Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="agentDetailsContent">
                        <div class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2">Loading agent details...</p>
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
        $(document).ready(function() {
            // Edit agent modal handler
            $('.edit-agent').on('click', function() {
                const agentId = $(this).data('id');
                const salary = $(this).data('salary');
                const percent = $(this).data('percent');
                const type = $(this).data('type');
                const minrecharge = $(this).data('minrecharge');
                const minbet = $(this).data('minbet');
                const minreferrals = $(this).data('minreferrals');

                $('#edit_agent_id').val(agentId);
                $('#edit_salary').val(salary);
                $('#edit_percent').val(percent);
                $('#edit_type').val(type);
                $('#edit_minrecharge').val(minrecharge);
                $('#edit_minbet').val(minbet);
                $('#edit_minreferrals').val(minreferrals);
            });

            // View agent modal handler
            $('.view-agent').on('click', function() {
                const agentId = $(this).data('id');
                $('#agentDetailsContent').html(`
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading agent details...</p>
                    </div>
                `);

                $.ajax({
                    url: 'manage_agents.php?view_agent=' + agentId,
                    type: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        if (data.error) {
                            $('#agentDetailsContent').html(`
                                <div class="alert alert-danger">
                                    <i class="ri-error-warning-line me-2"></i>
                                    ${data.error}
                                </div>
                            `);
                            return;
                        }

                        let html = `
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <h6>Agent Information</h6>
                                    <table class="table table-sm table-borderless">
                                        <tr>
                                            <td><strong>User ID:</strong></td>
                                            <td>${data.agent.userid}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Mobile:</strong></td>
                                            <td>${data.agent.mobile || 'N/A'}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Own Code:</strong></td>
                                            <td>${data.agent.owncode || 'N/A'}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Salary Type:</strong></td>
                                            <td><span class="badge bg-label-${data.agent.type === 'day' ? 'primary' : (data.agent.type === 'week' ? 'warning' : 'success')}">${data.agent.type ? data.agent.type.charAt(0).toUpperCase() + data.agent.type.slice(1) : 'N/A'}</span></td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h6>Performance Summary</h6>
                                    <div class="row text-center">
                                        <div class="col-4">
                                            <div class="stats-number text-primary">${data.level_counts ? Object.values(data.level_counts).reduce((sum, level) => sum + level.count, 0) : 0}</div>
                                            <div class="stats-label">Total Users</div>
                                        </div>
                                        <div class="col-4">
                                            <div class="stats-number text-success">₹${data.recharge ? Number(data.recharge).toLocaleString('en-IN', {maximumFractionDigits: 2}) : '0.00'}</div>
                                            <div class="stats-label">Total Recharge</div>
                                        </div>
                                        <div class="col-4">
                                            <div class="stats-number text-info">₹${data.withdrawal ? Number(data.withdrawal).toLocaleString('en-IN', {maximumFractionDigits: 2}) : '0.00'}</div>
                                            <div class="stats-label">Total Withdrawal</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;

                        // Level-wise user counts
                        if (data.level_counts) {
                            html += `
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <h6>User Distribution by Level</h6>
                                        <div class="row">
                            `;
                            
                            Object.entries(data.level_counts).forEach(([level, levelData]) => {
                                const levelNumber = level.replace('lvl', '');
                                html += `
                                    <div class="col-md-2 col-6 mb-3">
                                        <div class="card text-center">
                                            <div class="card-body">
                                                <div class="stats-number text-primary">${levelData.count}</div>
                                                <div class="stats-label">${level}</div>
                                            </div>
                                        </div>
                                    </div>
                                `;
                            });
                            
                            html += `
                                        </div>
                                    </div>
                                </div>
                            `;
                        }

                        // Deposit Statistics
                        if (data.deposits) {
                            html += `
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <h6>Deposit Statistics</h6>
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <div class="card border-success">
                                                    <div class="card-body text-center">
                                                        <div class="stats-number text-success">${data.deposits.success.count || 0}</div>
                                                        <div class="stats-label">Successful Deposits</div>
                                                        <small class="text-muted">₹${Number(data.deposits.success.total_amount || 0).toLocaleString('en-IN', {maximumFractionDigits: 2})}</small>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <div class="card border-warning">
                                                    <div class="card-body text-center">
                                                        <div class="stats-number text-warning">${data.deposits.pending.count || 0}</div>
                                                        <div class="stats-label">Pending Deposits</div>
                                                        <small class="text-muted">₹${Number(data.deposits.pending.total_amount || 0).toLocaleString('en-IN', {maximumFractionDigits: 2})}</small>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <div class="card border-danger">
                                                    <div class="card-body text-center">
                                                        <div class="stats-number text-danger">${data.deposits.rejected.count || 0}</div>
                                                        <div class="stats-label">Rejected Deposits</div>
                                                        <small class="text-muted">₹${Number(data.deposits.rejected.total_amount || 0).toLocaleString('en-IN', {maximumFractionDigits: 2})}</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                        }

                        // Betting Statistics
                        if (data.betting) {
                            html += `
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <h6>Betting Statistics</h6>
                                        <div class="card">
                                            <div class="card-body text-center">
                                                <div class="stats-number text-info">₹${Number(data.betting.total_bet || 0).toLocaleString('en-IN', {maximumFractionDigits: 2})}</div>
                                                <div class="stats-label">Total Bet Amount</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                        }

                        $('#agentDetailsContent').html(html);
                    },
                    error: function() {
                        $('#agentDetailsContent').html(`
                            <div class="alert alert-danger">
                                <i class="ri-error-warning-line me-2"></i>
                                Failed to load agent details. Please try again.
                            </div>
                        `);
                    }
                });
            });
        });
    </script>
</body>
</html>
<?php
// Close database connection
if ($conn) {
    mysqli_close($conn);
}
?>