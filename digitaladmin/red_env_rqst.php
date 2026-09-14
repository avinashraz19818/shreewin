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

// Approve or reject red envelope function
function updateRedEnvelopeStatus($requestId, $action, $conn, $addToTurnover = false) {
    $requestId = intval($requestId);
    $res = $conn->query("SELECT * FROM red_envelope_requests WHERE id = $requestId AND status = 'pending'");
    if (!$res) return "❌ Query error: " . $conn->error;

    if ($row = $res->fetch_assoc()) {
        $teacherId = $row['teacher_id'];
        $userId = $row['user_id'];
        $amount = $row['amount'];
        $quantity = $row['quantity'];
        $grabbed = $row['grabbed'];
        $createdAt = $row['created_at'];
        $teacherRemark = $row['remark'];
        $serial = "AdminApproval";

        if ($action === 'approve') {
            // Check if we need to process multiple users (if grabbed has multiple user IDs)
            $userIdsToProcess = [];
            
            if (!empty($grabbed)) {
                $grabbedUsers = explode(',', $grabbed);
                foreach ($grabbedUsers as $grabbedUser) {
                    $grabbedUser = trim($grabbedUser);
                    if (is_numeric($grabbedUser)) {
                        $userIdsToProcess[] = intval($grabbedUser);
                    }
                }
            }
            
            if (empty($userIdsToProcess)) {
                $userIdsToProcess[] = $userId;
            }
            
            $amountPerUser = $amount / count($userIdsToProcess);
            
            foreach ($userIdsToProcess as $processedUserId) {
                // Insert into bonus table
                $stmt = $conn->prepare("INSERT INTO hodike_balakedara (userkani, price, serial, shonu, remark) VALUES (?, ?, ?, ?, ?)");
                if (!$stmt) {
                    return "❌ Bonus insert prepare failed: " . $conn->error;
                }
                $stmt->bind_param("idsss", $processedUserId, $amountPerUser, $serial, $createdAt, $teacherRemark);
                if (!$stmt->execute()) {
                    $stmt->close();
                    return "❌ Bonus insert failed for user $processedUserId: " . $conn->error;
                }
                $stmt->close();

                // Update main wallet
                $conn->query("UPDATE shonu_kaichila SET motta = motta + $amountPerUser WHERE balakedara = $processedUserId");
                
                // Add to turnover if enabled
                if ($addToTurnover) {
                    $conn->query("UPDATE shonu_kaichila SET mottta = mottta + $amountPerUser WHERE balakedara = $processedUserId");
                }
            }

            // Update status
            $conn->query("UPDATE red_envelope_requests SET status = 'approved' WHERE id = $requestId");

            $turnoverText = $addToTurnover ? " (Added to Turnover)" : "";
            return "✅ Request ID $requestId approved. Bonus applied to " . count($userIdsToProcess) . " user(s)." . $turnoverText;
            
        } elseif ($action === 'reject') {
            $conn->query("UPDATE red_envelope_requests SET status = 'rejected' WHERE id = $requestId");
            return "❌ Request ID $requestId rejected.";
        }
    } else {
        return "⚠️ Request ID $requestId not found or already processed.";
    }
    return "";
}

// Handle POST action
$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $requestId = intval($_POST['request_id']);
    $action = $_POST['action'];
    $addToTurnover = isset($_POST['add_to_turnover']) && $_POST['add_to_turnover'] == '1';

    if ($action === 'approve' || $action === 'reject') {
        $message = updateRedEnvelopeStatus($requestId, $action, $conn, $addToTurnover);
    }
}

// Pagination settings
$records_per_page = 20;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $records_per_page;

// Search functionality
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$search_condition = '';
if (!empty($search)) {
    $search_condition = " AND (r.id LIKE '%$search%' 
                          OR r.remark LIKE '%$search%')";
}

// Status filter
$status_filter = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';
$status_condition = '';
if (!empty($status_filter) && in_array($status_filter, ['pending', 'approved', 'rejected'])) {
    $status_condition = " AND r.status = '$status_filter'";
}

// SIMPLE QUERY WITHOUT COMPLEX JOINS
$base_query = "SELECT SQL_CALC_FOUND_ROWS r.* 
               FROM red_envelope_requests r 
               WHERE 1=1 $search_condition $status_condition
               ORDER BY r.created_at DESC 
               LIMIT $offset, $records_per_page";

$result = mysqli_query($conn, $base_query);

// Check if query was successful
if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}

// Get total rows for pagination
$total_rows_result = mysqli_query($conn, "SELECT FOUND_ROWS() as total");
if ($total_rows_result) {
    $total_rows = mysqli_fetch_assoc($total_rows_result)['total'];
    $total_pages = ceil($total_rows / $records_per_page);
} else {
    $total_rows = 0;
    $total_pages = 0;
}

// Store all requests in array with user details
$requests = [];
while($row = mysqli_fetch_assoc($result)) {
    // Get student details
    $studentQuery = "SELECT username, phone, refer_id FROM users WHERE id = " . intval($row['user_id']);
    $studentResult = mysqli_query($conn, $studentQuery);
    if($studentResult && $studentRow = mysqli_fetch_assoc($studentResult)) {
        $row['student_username'] = $studentRow['username'];
        $row['student_phone'] = $studentRow['phone'];
        $row['student_refer_id'] = $studentRow['refer_id'];
    } else {
        $row['student_username'] = 'N/A';
        $row['student_phone'] = '';
        $row['student_refer_id'] = '';
    }
    
    // Get teacher details WITH teacher_profile data
    $teacherId = intval($row['teacher_id']);
    $teacherQuery = "SELECT u.username, u.phone, 
                     tp.name as teacher_name, tp.tg as teacher_telegram,
                     tp.teacher_code, tp.commission_percent, 
                     tp.total_agents, tp.total_balance,
                     tp.referral_link
              FROM users u 
              LEFT JOIN teacher_profile tp ON u.id = tp.user_id
              WHERE u.id = $teacherId";

    $teacherResult = mysqli_query($conn, $teacherQuery);
    if($teacherResult && $teacherRow = mysqli_fetch_assoc($teacherResult)) {
        $row['teacher_username'] = $teacherRow['username'] ?: 'N/A';
        $row['teacher_phone'] = $teacherRow['phone'] ?: '';
        $row['teacher_name'] = $teacherRow['teacher_name'] ?: 'N/A';
        $row['teacher_telegram'] = $teacherRow['teacher_telegram'] ?: '';
        $row['teacher_code'] = $teacherRow['teacher_code'] ?: '';
        $row['commission_percent'] = $teacherRow['commission_percent'] ?: '0';
        $row['total_agents'] = $teacherRow['total_agents'] ?: '0';
        $row['total_balance'] = $teacherRow['total_balance'] ?: '0';
        $row['referral_link'] = $teacherRow['referral_link'] ?: '';
    } else {
        // Fallback to basic teacher info
        $row['teacher_username'] = 'N/A';
        $row['teacher_phone'] = '';
        $row['teacher_name'] = 'N/A';
        $row['teacher_telegram'] = '';
        $row['teacher_code'] = '';
        $row['commission_percent'] = '0';
        $row['total_agents'] = '0';
        $row['total_balance'] = '0';
        $row['referral_link'] = '';
    }
    
    $requests[] = $row;
}

// Get counts for stats
$pendingCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM red_envelope_requests WHERE status = 'pending'"))['count'];
$approvedCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM red_envelope_requests WHERE status = 'approved'"))['count'];
$rejectedCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM red_envelope_requests WHERE status = 'rejected'"))['count'];
$totalAmount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as total FROM red_envelope_requests WHERE status = 'pending'"))['total'];

// Calculate grabbed users count for pending requests
$grabbedQuery = mysqli_query($conn, "SELECT grabbed FROM red_envelope_requests WHERE status = 'pending' AND grabbed IS NOT NULL");
$totalGrabbed = 0;
if($grabbedQuery) {
    while($grabbedRow = mysqli_fetch_assoc($grabbedQuery)) {
        if(!empty($grabbedRow['grabbed'])) {
            $grabbedUsers = explode(',', $grabbedRow['grabbed']);
            $totalGrabbed += count(array_filter($grabbedUsers));
        }
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
    <title>Red Envelope Requests - Admin Panel</title>
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
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: capitalize;
        }
        .status-pending {
            background-color: #e5ea8e;
            color: #000000;
        }
        .status-approved {
            background-color: #00c22f;
            color: #000000;
        }
        .status-rejected {
            background-color: #d33a48;
            color: #000000;
        }
        .amount-badge {
            background-color: #e7f3ff;
            color: #0066cc;
            padding: 5px 12px;
            border-radius: 20px;
            font-weight: 600;
        }
        .quantity-badge {
            background-color: #e6f7e6;
            color: #28a745;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
        }
        .grabbed-badge {
            background-color: #fff0e6;
            color: #fd7e14;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
        }
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        .view-btn {
            padding: 6px 12px;
            border-radius: 6px;
            border: none;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            background-color: #0d6efd;
            color: white;
        }
        .view-btn:hover {
            background-color: #0b5ed7;
        }
        .stats-card {
            border-radius: 10px;
            padding: 20px;
            color: white;
            margin-bottom: 20px;
        }
        .stats-card h5 {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 10px;
        }
        .stats-card h3 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        .stats-card.pending {
            background: linear-gradient(135deg, #ffc107, #ff9800);
        }
        .stats-card.approved {
            background: linear-gradient(135deg, #28a745, #20c997);
        }
        .stats-card.rejected {
            background: linear-gradient(135deg, #dc3545, #fd7e14);
        }
        .stats-card.total {
            background: linear-gradient(135deg, #0066cc, #6610f2);
        }
        .stats-card.grabbed {
            background: linear-gradient(135deg, #fd7e14, #e83e8c);
        }
        .remark-text {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            cursor: pointer;
        }
        .user-info {
            font-size: 12px;
            color: #666;
        }
        .teacher-info {
            font-size: 11px;
            color: #888;
            padding: 3px 8px;
            border-radius: 4px;
            margin-top: 3px;
        }
        .search-box {
            max-width: 400px;
        }
        .pagination .page-link {
            color: #0d6efd;
        }
        .pagination .page-item.active .page-link {
            background-color: #0d6efd;
            border-color: #0d6efd;
            color: white;
        }
        .turnover-toggle {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 15px 0;
            padding: 10px;
            border-radius: 8px;
        }
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
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
            background-color: #202238;
            transition: .4s;
            border-radius: 24px;
        }
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        input:checked + .toggle-slider {
            background-color: #28a745;
        }
        input:checked + .toggle-slider:before {
            transform: translateX(26px);
        }
        .modal-details-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin: 15px 0;
        }
        .detail-item {
            padding: 10px;
            border: 1px solid #55565f;
            border-radius: 6px;
        }
        .detail-label {
            font-size: 12px;
            margin-bottom: 5px;
        }
        .detail-value {
            font-weight: 600;
        }
        /* Telegram link styling */
        .text-decoration-none {
            text-decoration: none !important;
        }
        .ri-telegram-line {
            color: #0088cc;
            margin-right: 5px;
        }
        .teacher-profile-badge {
            background-color: #f0f8ff;
            color: #0066cc;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 10px;
            margin-top: 3px;
            display: inline-block;
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
                        <?php if (!empty($message)): ?>
                            <div class="alert alert-<?= strpos($message, '✅') !== false ? 'success' : (strpos($message, '⚠️') !== false ? 'warning' : 'danger') ?> alert-dismissible fade show" role="alert">
                                <?= htmlspecialchars($message) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <h4 class="fw-bold py-3 mb-4">
                            <span class="text-muted fw-light">Admin /</span> Red Envelope Requests
                        </h4>

                        <!-- Statistics Cards -->
                        <div class="row mb-4">
                            <div class="col-xl-2 col-md-6 mb-4">
                                <div class="stats-card pending">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h5>Pending</h5>
                                            <h3><?= $pendingCount ?></h3>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="ri-time-line ri-2x opacity-50"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-2 col-md-6 mb-4">
                                <div class="stats-card approved">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h5>Approved</h5>
                                            <h3><?= $approvedCount ?></h3>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="ri-check-double-line ri-2x opacity-50"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-2 col-md-6 mb-4">
                                <div class="stats-card rejected">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h5>Rejected</h5>
                                            <h3><?= $rejectedCount ?></h3>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="ri-close-circle-line ri-2x opacity-50"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6 mb-4">
                                <div class="stats-card total">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h5>Pending Amount</h5>
                                            <h3>₹<?= number_format($totalAmount ?: 0, 2) ?></h3>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="ri-money-rupee-circle-line ri-2x opacity-50"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6 mb-4">
                                <div class="stats-card grabbed">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h5>Grabbed Users</h5>
                                            <h3><?= $totalGrabbed ?: 0 ?></h3>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="ri-user-follow-line ri-2x opacity-50"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Search and Filter Section -->
                        <div class="card mb-4">
                            <div class="card-body">
                                <form method="GET" action="" class="row g-3">
                                    <div class="col-md-6">
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="ri-search-line"></i></span>
                                            <input type="text" class="form-control" name="search" 
                                                   placeholder="Search by ID, Username, Phone, Remark..." 
                                                   value="<?= htmlspecialchars($search) ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <select class="form-select" name="status">
                                            <option value="">All Status</option>
                                            <option value="pending" <?= $status_filter == 'pending' ? 'selected' : '' ?>>Pending</option>
                                            <option value="approved" <?= $status_filter == 'approved' ? 'selected' : '' ?>>Approved</option>
                                            <option value="rejected" <?= $status_filter == 'rejected' ? 'selected' : '' ?>>Rejected</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="ri-filter-line me-2"></i>Filter
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Requests Table -->
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    Red Envelope Requests 
                                    <span class="text-muted fs-6">
                                        (Total: <?= $total_rows ?>, Showing: <?= ($offset + 1) ?>-<?= min($offset + $records_per_page, $total_rows) ?>)
                                    </span>
                                </h5>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-outline-secondary" id="refreshBtn">
                                        <i class="ri-refresh-line"></i> Refresh
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover" id="requestsTable">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Teacher</th>
                                                <th>Student</th>
                                                <th>Amount</th>
                                                <th>Quantity</th>
                                                <th>Grabbed</th>
                                                <th>Remark</th>
                                                <th>Status</th>
                                                <th>Date</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if(count($requests) > 0): ?>
                                                <?php foreach($requests as $row): ?>
                                                <tr data-status="<?= $row['status'] ?>">
                                                    <td><strong>#<?= $row['id'] ?></strong></td>
                                                    
                                                    <!-- Teacher Column -->
                                                    <td>
                                                        <div>
                                                            <strong><?= htmlspecialchars($row['teacher_name'] !== 'N/A' ? $row['teacher_name'] : $row['teacher_username']) ?></strong>
                                                            <div class="teacher-info">
                                                                ID: <?= $row['teacher_id'] ?>
                                                                <?php if(!empty($row['teacher_telegram'])): ?>
                                                                <br>📱 TG: @<?= htmlspecialchars($row['teacher_telegram']) ?>
                                                                <?php endif; ?>
                                                                <?php if(!empty($row['teacher_code'])): ?>
                                                                <br>🔑 Code: <?= htmlspecialchars($row['teacher_code']) ?>
                                                                <?php endif; ?>
                                                                <?php if(!empty($row['commission_percent']) && $row['commission_percent'] > 0): ?>
                                                                <br>📊 Commission: <?= $row['commission_percent'] ?>%
                                                                <?php endif; ?>
                                                                <?php if(!empty($row['total_agents']) && $row['total_agents'] > 0): ?>
                                                                <div class="teacher-profile-badge">
                                                                    Agents: <?= $row['total_agents'] ?>
                                                                </div>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    
                                                    <!-- Student Column -->
                                                    <td>
                                                        <div>
                                                            <strong><?= htmlspecialchars($row['student_username'] ?: 'N/A') ?></strong>
                                                            <div class="user-info">
                                                                ID: <?= $row['user_id'] ?>
                                                                <?php if(!empty($row['student_phone'])): ?>
                                                                <br>📱 <?= htmlspecialchars($row['student_phone']) ?>
                                                                <?php endif; ?>
                                                                <?php if(!empty($row['student_refer_id'])): ?>
                                                                <br>👤 Ref: <?= htmlspecialchars($row['student_refer_id']) ?>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    
                                                    <!-- Amount -->
                                                    <td>
                                                        <span class="amount-badge">
                                                            ₹<?= number_format($row['amount'], 2) ?>
                                                        </span>
                                                    </td>
                                                    
                                                    <!-- Quantity -->
                                                    <td>
                                                        <?php if($row['quantity'] > 0): ?>
                                                        <span class="quantity-badge">
                                                            <i class="ri-group-line"></i> <?= $row['quantity'] ?>
                                                        </span>
                                                        <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    
                                                    <!-- Grabbed -->
                                                    <td>
                                                        <?php if(!empty($row['grabbed'])): ?>
                                                        <span class="grabbed-badge" 
                                                              data-bs-toggle="tooltip" 
                                                              title="Grabbed Users: <?= htmlspecialchars($row['grabbed']) ?>">
                                                            <i class="ri-handbag-line"></i> 
                                                            <?php 
                                                                $grabbedCount = count(array_filter(explode(',', $row['grabbed'])));
                                                                echo $grabbedCount > 0 ? $grabbedCount : '0';
                                                            ?>
                                                        </span>
                                                        <?php else: ?>
                                                        <span class="text-muted">0</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    
                                                    <!-- Remark -->
                                                    <td>
                                                        <?php if(!empty($row['remark'])): ?>
                                                        <div class="remark-text" 
                                                             onclick="showRemark('<?= htmlspecialchars(addslashes($row['remark'])) ?>')">
                                                            <?= htmlspecialchars(substr($row['remark'], 0, 25)) ?>
                                                            <?= strlen($row['remark']) > 25 ? '...' : '' ?>
                                                        </div>
                                                        <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    
                                                    <!-- Status -->
                                                    <td>
                                                        <span class="status-badge status-<?= $row['status'] ?>">
                                                            <?= $row['status'] ?>
                                                        </span>
                                                    </td>
                                                    
                                                    <!-- Date -->
                                                    <td>
                                                        <div class="small text-muted">
                                                            <?= date('d-m-Y', strtotime($row['created_at'])) ?>
                                                            <br>
                                                            <small><?= date('H:i', strtotime($row['created_at'])) ?></small>
                                                        </div>
                                                    </td>
                                                    
                                                    <!-- Action Button -->
                                                    <td>
                                                        <?php if($row['status'] == 'pending'): ?>
                                                        <button class="view-btn" 
                                                                onclick="showRequestDetails(<?= htmlspecialchars(json_encode($row)) ?>)">
                                                            <i class="ri-eye-line"></i> View
                                                        </button>
                                                        <?php else: ?>
                                                        <span class="text-muted">Processed</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="10" class="text-center py-4">
                                                        <div class="text-muted">
                                                            <i class="ri-inbox-line ri-3x mb-3"></i>
                                                            <p>No red envelope requests found</p>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <!-- Pagination -->
                                <?php if($total_pages > 1): ?>
                                <nav aria-label="Page navigation" class="mt-4">
                                    <ul class="pagination justify-content-center">
                                        <!-- Previous Page -->
                                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                            <a class="page-link" href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&status=<?= $status_filter ?>" aria-label="Previous">
                                                <span aria-hidden="true">&laquo;</span>
                                            </a>
                                        </li>
                                        
                                        <!-- Page Numbers -->
                                        <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                            <?php if($i == 1 || $i == $total_pages || ($i >= $page-2 && $i <= $page+2)): ?>
                                                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                                    <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= $status_filter ?>"><?= $i ?></a>
                                                </li>
                                            <?php elseif($i == $page-3 || $i == $page+3): ?>
                                                <li class="page-item disabled">
                                                    <span class="page-link">...</span>
                                                </li>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                        
                                        <!-- Next Page -->
                                        <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                            <a class="page-link" href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&status=<?= $status_filter ?>" aria-label="Next">
                                                <span aria-hidden="true">&raquo;</span>
                                            </a>
                                        </li>
                                    </ul>
                                    <p class="text-center text-muted mt-2">
                                        Page <?= $page ?> of <?= $total_pages ?> | Total Records: <?= $total_rows ?>
                                    </p>
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

    <!-- Modal for Request Details -->
    <div class="modal fade" id="requestModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Request Details - <span id="modalRequestId"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Details Grid -->
                    <div class="modal-details-grid" id="requestDetails">
                        <!-- Details will be populated by JavaScript -->
                    </div>
                    
                    <!-- Turnover Toggle -->
                    <div class="turnover-toggle">
                        <label class="form-check-label" for="addToTurnover">
                            <strong>Add to Turnover</strong>
                            <small class="text-muted d-block">If enabled, amount will also be added to turnover balance</small>
                        </label>
                        <label class="toggle-switch">
                            <input type="checkbox" id="addToTurnover" name="add_to_turnover" value="1">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="d-flex gap-3 justify-content-end mt-4">
                        <form method="post" id="rejectForm">
                            <input type="hidden" name="request_id" id="rejectRequestId">
                            <input type="hidden" name="action" value="reject">
                            <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to REJECT this request?')">
                                <i class="ri-close-line me-2"></i>Reject
                            </button>
                        </form>
                        
                        <form method="post" id="approveForm">
                            <input type="hidden" name="request_id" id="approveRequestId">
                            <input type="hidden" name="action" value="approve">
                            <input type="hidden" name="add_to_turnover" id="approveTurnover" value="0">
                            <button type="submit" class="btn btn-success" onclick="return confirmApprove()">
                                <i class="ri-check-line me-2"></i>Approve
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for full remark -->
    <div class="modal fade" id="remarkModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Teacher Remark</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="modal-remark" id="fullRemarkText"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
    <script src="assets/vendor/libs/datatables-bs5/datatables.bootstrap5.js"></script>
    <script src="assets/vendor/libs/select2/select2.js"></script>
    <script src="assets/vendor/libs/@form-validation/popular.js"></script>
    <script src="assets/vendor/libs/@form-validation/bootstrap5.js"></script>
    <script src="assets/vendor/libs/@form-validation/auto-focus.js"></script>
    <script src="assets/vendor/libs/cleavejs/cleave.js"></script>
    <script src="assets/vendor/libs/cleavejs/cleave-phone.js"></script>
    <script src="assets/js/main.js"></script>

    <script>
        // Show request details in modal
        function showRequestDetails(request) {
            // Set modal title
            document.getElementById('modalRequestId').textContent = '#' + request.id;
            
            // Prepare grabbed users info
            let grabbedInfo = '0';
            let userCount = 1;
            if (request.grabbed && request.grabbed.trim() !== '') {
                const grabbedUsers = request.grabbed.split(',').filter(u => u.trim() !== '');
                userCount = grabbedUsers.length > 0 ? grabbedUsers.length : 1;
                grabbedInfo = `${userCount} user(s): ${request.grabbed}`;
            }
            
            // Calculate amount per user
            const amountPerUser = request.amount / userCount;
            
            // Prepare teacher info
            let teacherName = request.teacher_name !== 'N/A' ? request.teacher_name : request.teacher_username;
            let teacherTelegram = request.teacher_telegram ? `@${request.teacher_telegram}` : 'Not available';
            let teacherPhone = request.teacher_phone || 'Not available';
            
            // Build details HTML
            const detailsHTML = `
                <div class="detail-item">
                    <div class="detail-label">Request ID</div>
                    <div class="detail-value">#${request.id}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Status</div>
                    <div class="detail-value">
                        <span class="status-badge status-${request.status}">${request.status}</span>
                    </div>
                </div>
                
                <!-- Teacher Information -->
                <div class="detail-item">
                    <div class="detail-label">Teacher Name</div>
                    <div class="detail-value">${teacherName}</div>
                    <small class="text-muted">ID: ${request.teacher_id}</small>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Teacher Telegram</div>
                    <div class="detail-value">
                        ${request.teacher_telegram ? 
                            `<a href="https://t.me/${request.teacher_telegram}" target="_blank" class="text-decoration-none">
                                <i class="ri-telegram-line"></i> @${request.teacher_telegram}
                            </a>` : 
                            'Not available'}
                    </div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Teacher Phone</div>
                    <div class="detail-value">${teacherPhone}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Teacher Code</div>
                    <div class="detail-value">${request.teacher_code || 'Not available'}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Commission %</div>
                    <div class="detail-value">${request.commission_percent || '0'}%</div>
                </div>
                
                <!-- Teacher Stats -->
                <div class="detail-item">
                    <div class="detail-label">Total Agents</div>
                    <div class="detail-value">${request.total_agents || '0'}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Total Balance</div>
                    <div class="detail-value">₹${parseFloat(request.total_balance || 0).toFixed(2)}</div>
                </div>
                
                <!-- Student Information -->
                <div class="detail-item">
                    <div class="detail-label">Student</div>
                    <div class="detail-value">${request.student_username || 'N/A'}</div>
                    <small class="text-muted">ID: ${request.user_id}</small>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Student Phone</div>
                    <div class="detail-value">${request.student_phone || 'Not available'}</div>
                </div>
                
                <!-- Amount Information -->
                <div class="detail-item">
                    <div class="detail-label">Amount</div>
                    <div class="detail-value">₹${parseFloat(request.amount).toFixed(2)}</div>
                    <small class="text-muted">Per user: ₹${amountPerUser.toFixed(2)}</small>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Quantity</div>
                    <div class="detail-value">${request.quantity || '1'}</div>
                </div>
                
                <!-- Additional Info -->
                <div class="detail-item">
                    <div class="detail-label">Grabbed Users</div>
                    <div class="detail-value">${grabbedInfo}</div>
                </div>
                
                <!-- Referral Link -->
                ${request.referral_link ? `
                <div class="detail-item">
                    <div class="detail-label">Referral Link</div>
                    <div class="detail-value">
                        <a href="${request.referral_link}" target="_blank" class="text-decoration-none">
                            <i class="ri-link"></i> View Link
                        </a>
                    </div>
                </div>
                ` : ''}
                
                <div class="detail-item" style="grid-column: span 2;">
                    <div class="detail-label">Teacher Remark</div>
                    <div class="detail-value">${request.remark || 'No remark'}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Created Date</div>
                    <div class="detail-value">${new Date(request.created_at).toLocaleDateString('en-IN')}</div>
                    <small class="text-muted">${new Date(request.created_at).toLocaleTimeString('en-IN')}</small>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Users to Credit</div>
                    <div class="detail-value">${userCount} user(s)</div>
                </div>
            `;
            
            // Populate details
            document.getElementById('requestDetails').innerHTML = detailsHTML;
            
            // Reset turnover toggle
            document.getElementById('addToTurnover').checked = false;
            document.getElementById('approveTurnover').value = '0';
            
            // Set form values
            document.getElementById('rejectRequestId').value = request.id;
            document.getElementById('approveRequestId').value = request.id;
            
            // Show modal
            new bootstrap.Modal(document.getElementById('requestModal')).show();
        }
        
        // Update turnover value before approve
        document.getElementById('addToTurnover').addEventListener('change', function() {
            document.getElementById('approveTurnover').value = this.checked ? '1' : '0';
        });
        
        // Confirm approve action
        function confirmApprove() {
            const requestId = document.getElementById('approveRequestId').value;
            const addToTurnover = document.getElementById('addToTurnover').checked;
            
            // Get amount from modal
            const amountText = document.querySelector('#requestDetails .detail-item:nth-child(13) .detail-value').textContent;
            const amount = parseFloat(amountText.replace('₹', ''));
            
            // Get user count from modal
            const userCountText = document.querySelectorAll('#requestDetails .detail-item')[15].querySelector('.detail-value').textContent;
            const userCount = parseInt(userCountText);
            
            const amountPerUser = amount / userCount;
            
            let message = `APPROVE Request #${requestId}?\n\n`;
            message += `Amount: ₹${amount.toFixed(2)}\n`;
            message += `Users to credit: ${userCount}\n`;
            message += `Amount per user: ₹${amountPerUser.toFixed(2)}\n\n`;
            
            if (addToTurnover) {
                message += `✅ Amount will also be added to turnover balance (mottta)\n\n`;
            }
            
            message += `This will add bonus to ${userCount} user wallet(s).`;
            
            return confirm(message);
        }
        
        // Refresh page
        document.getElementById('refreshBtn').addEventListener('click', function() {
            window.location.reload();
        });
        
        // Show full remark in modal
        function showRemark(remark) {
            document.getElementById('fullRemarkText').textContent = remark;
            new bootstrap.Modal(document.getElementById('remarkModal')).show();
        }
        
        // Initialize tooltips
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            });
        });
    </script>
</body>
</html>