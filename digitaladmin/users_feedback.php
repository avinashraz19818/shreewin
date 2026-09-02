<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (empty($_SESSION['unohs'])) {
    header("location: api/login.php?msg=unauthorized");
    exit();
}
include("api/conn.php");

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $feedback_id = intval($_GET['id']);
    $delete_query = "DELETE FROM users_feedback WHERE id = $feedback_id";
    
    if (mysqli_query($conn, $delete_query)) {
        $_SESSION['success'] = "Feedback deleted successfully!";
    } else {
        $_SESSION['error'] = "Error deleting feedback: " . mysqli_error($conn);
    }
    
    header("Location: users_feedback.php");
    exit();
}

// Search functionality
$search_query = '';
$where_condition = '';

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = mysqli_real_escape_string($conn, $_GET['search']);
    $where_condition = "WHERE (content LIKE '%$search_term%' OR user_id LIKE '%$search_term%')";
    $search_query = "&search=" . urlencode($search_term);
}

// Date filter functionality
$date_filter = '';
if (isset($_GET['date']) && !empty($_GET['date'])) {
    $filter_date = mysqli_real_escape_string($conn, $_GET['date']);
    if ($where_condition) {
        $where_condition .= " AND DATE(created_at) = '$filter_date'";
    } else {
        $where_condition = "WHERE DATE(created_at) = '$filter_date'";
    }
    $search_query .= "&date=" . urlencode($filter_date);
}

// Pagination
$limit = 50;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// Get total count
$count_query = "SELECT COUNT(*) as total FROM users_feedback $where_condition";
$count_result = mysqli_query($conn, $count_query);
$total_rows = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_rows / $limit);

// Get feedback data - SIMPLE SELECT *
$feedback_query = "SELECT * FROM users_feedback $where_condition ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
$feedback_result = mysqli_query($conn, $feedback_query);

// Get stats
$stats_query = "SELECT 
    COUNT(*) as total_feedback,
    COUNT(DISTINCT user_id) as unique_users,
    DATE(created_at) as feedback_date
    FROM users_feedback 
    GROUP BY DATE(created_at) 
    ORDER BY feedback_date DESC 
    LIMIT 30";
$stats_result = mysqli_query($conn, $stats_query);
$daily_stats = [];
if ($stats_result) {
    while ($stat = mysqli_fetch_assoc($stats_result)) {
        $daily_stats[] = $stat;
    }
}

// Get top users with most feedback
$top_users_query = "SELECT 
    user_id,
    COUNT(*) as feedback_count
    FROM users_feedback
    GROUP BY user_id 
    ORDER BY feedback_count DESC 
    LIMIT 10";
$top_users_result = mysqli_query($conn, $top_users_query);
$top_users = [];
if ($top_users_result) {
    while ($user = mysqli_fetch_assoc($top_users_result)) {
        $top_users[] = $user;
    }
}

// Get today's feedback count
$today = date('Y-m-d');
$today_query = "SELECT COUNT(*) as today_count FROM users_feedback WHERE DATE(created_at) = '$today'";
$today_result = mysqli_query($conn, $today_query);
if ($today_result) {
    $today_data = mysqli_fetch_assoc($today_result);
    $today_count = $today_data['today_count'];
} else {
    $today_count = 0;
}

// Get most active user
$most_active_user_query = "SELECT 
    user_id,
    COUNT(*) as count
    FROM users_feedback
    GROUP BY user_id 
    ORDER BY count DESC 
    LIMIT 1";
$most_active_result = mysqli_query($conn, $most_active_user_query);
$most_active_user = null;
if ($most_active_result && mysqli_num_rows($most_active_result) > 0) {
    $most_active_user = mysqli_fetch_assoc($most_active_result);
    
    // Get user details for most active user if exists
    if ($most_active_user) {
        $user_details_query = "SELECT name, mobile FROM users WHERE id = " . $most_active_user['user_id'];
        $user_details_result = mysqli_query($conn, $user_details_query);
        if ($user_details_result && mysqli_num_rows($user_details_result) > 0) {
            $user_details = mysqli_fetch_assoc($user_details_result);
            if ($user_details) {
                $most_active_user['user_name'] = $user_details['name'];
                $most_active_user['user_mobile'] = $user_details['mobile'];
            }
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
    <title>Users Feedback Management</title>
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
        .feedback-card { border-left: 4px solid #7367f0; padding: 1rem; margin-bottom: 1rem; border-radius: 0.35rem; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .feedback-content { white-space: pre-wrap; word-wrap: break-word; line-height: 1.6; }
        .user-badge { display: inline-block; padding: 0.25rem 0.75rem; background: #e7e7ff; color: #7367f0; border-radius: 1rem; font-size: 0.875rem; }
        .stats-card { border-left: 4px solid; padding: 1rem; margin-bottom: 1rem; }
        .stats-card.primary { border-color: #7367f0; background: rgba(115, 103, 240, 0.1); }
        .stats-card.success { border-color: #28a745; background: rgba(40, 167, 69, 0.1); }
        .stats-card.info { border-color: #17a2b8; background: rgba(23, 162, 184, 0.1); }
        .stats-card.warning { border-color: #ffc107; background: rgba(255, 193, 7, 0.1); }
        .stats-number { font-size: 1.8rem; font-weight: bold; margin-bottom: 0.5rem; }
        .date-badge { font-size: 0.75rem; color: #6c757d; }
        .search-box { max-width: 300px; }
        .feedback-actions { opacity: 0; transition: opacity 0.2s; }
        .feedback-card:hover .feedback-actions { opacity: 1; }
        .top-user-avatar { width: 40px; height: 40px; border-radius: 50%; background: #7367f0; color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; }
        .export-btn { position: relative; }
        .dropdown-menu { min-width: 200px; }
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

                        <!-- Header -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="mb-0">
                                <i class="ri-feedback-line me-2"></i>
                                Users Feedback Management
                            </h4>
                            <div class="d-flex gap-2">
                                <form method="GET" class="d-flex gap-2">
                                    <input type="text" class="form-control search-box" name="search" 
                                           placeholder="Search feedback or user ID..." 
                                           value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                                    <input type="date" class="form-control" name="date" 
                                           value="<?= isset($_GET['date']) ? htmlspecialchars($_GET['date']) : ''; ?>">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="ri-search-line"></i>
                                    </button>
                                    <?php if (isset($_GET['search']) || isset($_GET['date'])): ?>
                                        <a href="users_feedback.php" class="btn btn-secondary">Clear</a>
                                    <?php endif; ?>
                                </form>
                                
                                <div class="dropdown export-btn">
                                    <button class="btn btn-success dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        <i class="ri-download-line me-1"></i> Export
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="#" onclick="exportToCSV()"><i class="ri-file-excel-line me-2"></i> Export to CSV</a></li>
                                        <li><a class="dropdown-item" href="#" onclick="printFeedback()"><i class="ri-printer-line me-2"></i> Print</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Stats Cards -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="stats-card primary">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="stats-number"><?= $total_rows; ?></div>
                                            <div>Total Feedback</div>
                                        </div>
                                        <i class="ri-message-3-line text-primary" style="font-size: 2rem;"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card success">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="stats-number"><?= $today_count; ?></div>
                                            <div>Today's Feedback</div>
                                            <small><?= date('d M Y'); ?></small>
                                        </div>
                                        <i class="ri-calendar-event-line text-success" style="font-size: 2rem;"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card info">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="stats-number"><?= $most_active_user ? $most_active_user['count'] : 0; ?></div>
                                            <div>Most Active User</div>
                                            <small>
                                                <?php 
                                                if ($most_active_user && isset($most_active_user['user_name'])) {
                                                    echo htmlspecialchars($most_active_user['user_name']);
                                                } else if ($most_active_user) {
                                                    echo "User #" . $most_active_user['user_id'];
                                                } else {
                                                    echo 'N/A';
                                                }
                                                ?>
                                            </small>
                                        </div>
                                        <i class="ri-user-star-line text-info" style="font-size: 2rem;"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card warning">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="stats-number"><?= count($top_users); ?></div>
                                            <div>Active Users</div>
                                            <small>With 1+ feedback</small>
                                        </div>
                                        <i class="ri-group-line text-warning" style="font-size: 2rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Top Users -->
                        <?php if (!empty($top_users)): ?>
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="ri-award-line me-2"></i>
                                    Top Users with Most Feedback
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php foreach ($top_users as $index => $user): 
                                        // Get user details for each top user - FIXED CODE
                                        $user_detail = null;
                                        $user_detail_query = "SELECT name, mobile FROM users WHERE id = " . $user['user_id'];
                                        $user_detail_result = mysqli_query($conn, $user_detail_query);
                                        if ($user_detail_result && mysqli_num_rows($user_detail_result) > 0) {
                                            $user_detail = mysqli_fetch_assoc($user_detail_result);
                                        }
                                    ?>
                                        <div class="col-md-4 col-lg-3 col-sm-6 mb-3">
                                            <div class="d-flex align-items-center p-2 border rounded">
                                                <div class="flex-shrink-0 me-3">
                                                    <div class="top-user-avatar">
                                                        <?= $index + 1; ?>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1">
                                                        <?php 
                                                        if ($user_detail && !empty($user_detail['name'])) {
                                                            echo htmlspecialchars($user_detail['name']);
                                                        } else {
                                                            echo 'User #' . $user['user_id'];
                                                        }
                                                        ?>
                                                    </h6>
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <small class="text-muted">ID: <?= $user['user_id']; ?></small>
                                                        <span class="badge bg-primary rounded-pill"><?= $user['feedback_count']; ?> feedback</span>
                                                    </div>
                                                    <?php if ($user_detail && !empty($user_detail['mobile'])): ?>
                                                        <small class="text-muted">
                                                            <i class="ri-phone-line me-1"></i>
                                                            <?= htmlspecialchars($user_detail['mobile']); ?>
                                                        </small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Feedback List -->
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">
                                    <i class="ri-chat-3-line me-2"></i>
                                    All Feedback
                                    <?php if (isset($_GET['date'])): ?>
                                        <span class="text-muted"> - <?= date('d M Y', strtotime($_GET['date'])); ?></span>
                                    <?php endif; ?>
                                </h5>
                                <div>
                                    <span class="badge bg-primary">Page <?= $page; ?> of <?= $total_pages; ?></span>
                                    <span class="ms-2">Showing <?= ($offset + 1); ?>-<?= min($offset + $limit, $total_rows); ?> of <?= $total_rows; ?></span>
                                </div>
                            </div>
                            <div class="card-body">
                                <?php if ($total_pages > 1): ?>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <nav>
                                        <ul class="pagination mb-0">
                                            <?php if ($page > 1): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?page=<?= $page - 1; ?><?= $search_query; ?>">
                                                        <i class="ri-arrow-left-s-line"></i> Previous
                                                    </a>
                                                </li>
                                            <?php endif; ?>
                                            
                                            <?php 
                                            $start_page = max(1, $page - 2);
                                            $end_page = min($total_pages, $page + 2);
                                            
                                            for ($i = $start_page; $i <= $end_page; $i++): ?>
                                                <li class="page-item <?= $i == $page ? 'active' : ''; ?>">
                                                    <a class="page-link" href="?page=<?= $i; ?><?= $search_query; ?>"><?= $i; ?></a>
                                                </li>
                                            <?php endfor; ?>
                                            
                                            <?php if ($page < $total_pages): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?page=<?= $page + 1; ?><?= $search_query; ?>">
                                                        Next <i class="ri-arrow-right-s-line"></i>
                                                    </a>
                                                </li>
                                            <?php endif; ?>
                                        </ul>
                                    </nav>
                                    <div class="form-group">
                                        <select class="form-select form-select-sm" onchange="location.href = '?page=' + this.value + '<?= $search_query; ?>'">
                                            <option>Jump to page</option>
                                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                                <option value="<?= $i; ?>" <?= $i == $page ? 'selected' : ''; ?>>
                                                    Page <?= $i; ?>
                                                </option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <?php if (mysqli_num_rows($feedback_result) > 0): ?>
                                    <div id="feedbackList">
                                        <?php while ($feedback = mysqli_fetch_assoc($feedback_result)): 
                                            // Get user details for this feedback - FIXED CODE
                                            $user_detail = null;
                                            $user_detail_query = "SELECT name, mobile FROM users WHERE id = " . $feedback['user_id'];
                                            $user_detail_result = mysqli_query($conn, $user_detail_query);
                                            if ($user_detail_result && mysqli_num_rows($user_detail_result) > 0) {
                                                $user_detail = mysqli_fetch_assoc($user_detail_result);
                                            }
                                        ?>
                                            <div class="feedback-card" id="feedback-<?= $feedback['id']; ?>">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <div>
                                                        <div class="d-flex align-items-center gap-2 mb-1">
                                                            <span class="user-badge">
                                                                <i class="ri-user-line me-1"></i>
                                                                User ID: <?= $feedback['user_id']; ?>
                                                            </span>
                                                            <?php if ($user_detail && !empty($user_detail['name'])): ?>
                                                                <span class="badge bg-label-primary">
                                                                    <i class="ri-user-smile-line me-1"></i>
                                                                    <?= htmlspecialchars($user_detail['name']); ?>
                                                                </span>
                                                            <?php endif; ?>
                                                            <?php if ($user_detail && !empty($user_detail['mobile'])): ?>
                                                                <span class="badge bg-label-secondary">
                                                                    <i class="ri-phone-line me-1"></i>
                                                                    <?= htmlspecialchars($user_detail['mobile']); ?>
                                                                </span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <span class="date-badge">
                                                            <i class="ri-time-line me-1"></i>
                                                            <?= date('d M Y, h:i A', strtotime($feedback['created_at'])); ?>
                                                        </span>
                                                    </div>
                                                    <div class="feedback-actions">
                                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                                onclick="copyFeedback(<?= $feedback['id']; ?>, '<?= addslashes($feedback['content']); ?>')"
                                                                title="Copy to clipboard">
                                                            <i class="ri-file-copy-line"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                                onclick="if(confirm('Are you sure you want to delete this feedback?')) { window.location.href='?action=delete&id=<?= $feedback['id']; ?><?= $search_query ? '&' . ltrim($search_query, '&') : ''; ?>' }"
                                                                title="Delete feedback">
                                                            <i class="ri-delete-bin-line"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="feedback-content">
                                                    <?= nl2br(htmlspecialchars($feedback['content'])); ?>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-5">
                                        <div class="text-muted">
                                            <i class="ri-inbox-line display-4"></i>
                                            <p class="mt-3 mb-0">No feedback found</p>
                                            <?php if (isset($_GET['search']) || isset($_GET['date'])): ?>
                                                <p class="text-muted">Try different search criteria</p>
                                                <a href="users_feedback.php" class="btn btn-primary mt-2">
                                                    <i class="ri-refresh-line me-1"></i> View All Feedback
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <?php if ($total_pages > 1 && mysqli_num_rows($feedback_result) > 0): ?>
                                <div class="d-flex justify-content-center mt-4">
                                    <nav>
                                        <ul class="pagination mb-0">
                                            <?php if ($page > 1): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?page=1<?= $search_query; ?>">
                                                        <i class="ri-skip-back-line"></i> First
                                                    </a>
                                                </li>
                                                <li class="page-item">
                                                    <a class="page-link" href="?page=<?= $page - 1; ?><?= $search_query; ?>">
                                                        <i class="ri-arrow-left-s-line"></i> Prev
                                                    </a>
                                                </li>
                                            <?php endif; ?>
                                            
                                            <li class="page-item disabled">
                                                <span class="page-link">Page <?= $page; ?> of <?= $total_pages; ?></span>
                                            </li>
                                            
                                            <?php if ($page < $total_pages): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?page=<?= $page + 1; ?><?= $search_query; ?>">
                                                        Next <i class="ri-arrow-right-s-line"></i>
                                                    </a>
                                                </li>
                                                <li class="page-item">
                                                    <a class="page-link" href="?page=<?= $total_pages; ?><?= $search_query; ?>">
                                                        Last <i class="ri-skip-forward-line"></i>
                                                    </a>
                                                </li>
                                            <?php endif; ?>
                                        </ul>
                                    </nav>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Daily Stats -->
                        <?php if (!empty($daily_stats)): ?>
                        <div class="card mt-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="ri-bar-chart-line me-2"></i>
                                    Daily Feedback Statistics (Last 30 Days)
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Date</th>
                                                <th>Total Feedback</th>
                                                <th>Unique Users</th>
                                                <th>Avg per User</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($daily_stats as $stat): ?>
                                                <tr>
                                                    <td>
                                                        <i class="ri-calendar-line me-2"></i>
                                                        <?= date('d M Y', strtotime($stat['feedback_date'])); ?>
                                                        <?php if ($stat['feedback_date'] == $today): ?>
                                                            <span class="badge bg-success ms-2">Today</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-primary rounded-pill px-3 py-1">
                                                            <?= $stat['total_feedback']; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-success rounded-pill px-3 py-1">
                                                            <?= $stat['unique_users']; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                        $avg = $stat['unique_users'] > 0 ? round($stat['total_feedback'] / $stat['unique_users'], 1) : 0;
                                                        ?>
                                                        <span class="badge bg-info rounded-pill px-3 py-1">
                                                            <?= $avg; ?> per user
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <a href="?date=<?= $stat['feedback_date']; ?>" 
                                                           class="btn btn-sm btn-outline-primary">
                                                            <i class="ri-eye-line me-1"></i> View
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                    </div>
                    <?php require_once("footer.php"); ?>
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
    // Copy feedback content to clipboard
    function copyFeedback(id, content) {
        navigator.clipboard.writeText(content).then(function() {
            showToast('Feedback #' + id + ' copied to clipboard!', 'success');
            
            // Visual feedback
            const btn = document.querySelector(`button[onclick*="copyFeedback(${id},"]`);
            if (btn) {
                const originalHTML = btn.innerHTML;
                btn.innerHTML = '<i class="ri-check-line"></i>';
                btn.classList.remove('btn-outline-primary');
                btn.classList.add('btn-success');
                
                setTimeout(() => {
                    btn.innerHTML = originalHTML;
                    btn.classList.remove('btn-success');
                    btn.classList.add('btn-outline-primary');
                }, 2000);
            }
        }, function() {
            // Fallback for older browsers
            const textArea = document.createElement('textarea');
            textArea.value = content;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            showToast('Feedback #' + id + ' copied to clipboard!', 'success');
        });
    }
    
    // Export to CSV
    function exportToCSV() {
        let csvContent = "ID,User ID,User Name,Mobile,Feedback,Date\n";
        
        document.querySelectorAll('.feedback-card').forEach(card => {
            const id = card.id.replace('feedback-', '');
            const userId = card.querySelector('.user-badge').textContent.replace('User ID: ', '').trim();
            
            let userName = '';
            let userMobile = '';
            
            const nameBadge = card.querySelector('.badge.bg-label-primary');
            if (nameBadge) {
                userName = nameBadge.textContent.replace(/\n/g, '').trim();
            }
            
            const mobileBadge = card.querySelector('.badge.bg-label-secondary');
            if (mobileBadge) {
                userMobile = mobileBadge.textContent.replace(/\n/g, '').trim();
            }
            
            const feedback = card.querySelector('.feedback-content').textContent.trim().replace(/,/g, ';').replace(/\n/g, ' ');
            const date = card.querySelector('.date-badge').textContent.trim();
            
            csvContent += `"${id}","${userId}","${userName}","${userMobile}","${feedback}","${date}"\n`;
        });
        
        downloadCSV(csvContent, 'feedback_export_' + new Date().toISOString().slice(0,10) + '.csv');
    }
    
    function downloadCSV(csvContent, fileName) {
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        
        link.setAttribute('href', url);
        link.setAttribute('download', fileName);
        link.style.visibility = 'hidden';
        
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        showToast('CSV exported successfully!', 'success');
    }
    
    // Print feedback
    function printFeedback() {
        const printContent = document.getElementById('feedbackList').innerHTML;
        const originalContent = document.body.innerHTML;
        
        document.body.innerHTML = `
            <!DOCTYPE html>
            <html>
            <head>
                <title>Feedback Report - <?= date('d M Y'); ?></title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    .feedback-card { border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; border-radius: 5px; page-break-inside: avoid; }
                    .feedback-content { white-space: pre-wrap; margin-top: 10px; }
                    .user-info { font-weight: bold; color: #333; }
                    .date-info { color: #666; font-size: 0.9em; }
                    h1 { text-align: center; color: #333; }
                    .print-header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 20px; }
                    .print-footer { text-align: center; margin-top: 30px; color: #666; font-size: 0.9em; }
                    @media print {
                        .feedback-card { border: 1px solid #000; }
                    }
                </style>
            </head>
            <body>
                <div class="print-header">
                    <h1>Users Feedback Report</h1>
                    <p><strong>Generated on:</strong> <?= date('d M Y, h:i A'); ?></p>
                    <p><strong>Total Feedback:</strong> <?= $total_rows; ?></p>
                    <?php if (isset($_GET['date'])): ?>
                        <p><strong>Date Filter:</strong> <?= date('d M Y', strtotime($_GET['date'])); ?></p>
                    <?php endif; ?>
                </div>
                ${printContent}
                <div class="print-footer">
                    <p>Page 1 of 1 - End of Report</p>
                </div>
            </body>
            </html>
        `;
        
        window.print();
        document.body.innerHTML = originalContent;
        location.reload();
    }
    
    // Show toast notification
    function showToast(message, type = 'info') {
        // Create toast container if not exists
        let toastContainer = document.getElementById('toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'toast-container';
            toastContainer.style.position = 'fixed';
            toastContainer.style.top = '20px';
            toastContainer.style.right = '20px';
            toastContainer.style.zIndex = '9999';
            document.body.appendChild(toastContainer);
        }
        
        const toastId = 'toast-' + Date.now();
        const toast = document.createElement('div');
        toast.id = toastId;
        toast.className = `alert alert-${type} alert-dismissible fade show`;
        toast.style.minWidth = '300px';
        toast.style.marginBottom = '10px';
        toast.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        toastContainer.appendChild(toast);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            if (document.getElementById(toastId)) {
                toast.remove();
            }
        }, 5000);
    }
    
    // Auto-refresh every 5 minutes if on first page
    if (<?= $page; ?> === 1) {
        let refreshCount = 0;
        setInterval(() => {
            fetch(window.location.href + '&nocache=' + Date.now())
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newCount = doc.querySelector('.stats-card.primary .stats-number')?.textContent;
                    
                    if (newCount && parseInt(newCount) > <?= $total_rows; ?>) {
                        refreshCount++;
                        if (refreshCount >= 2) { // Only refresh after 2 checks (10 minutes)
                            showToast('New feedback received! Refreshing...', 'info');
                            setTimeout(() => {
                                location.reload();
                            }, 2000);
                        } else {
                            showToast('New feedback detected. Will refresh soon...', 'warning');
                        }
                    }
                })
                .catch(error => console.error('Auto-refresh error:', error));
        }, 300000); // 5 minutes
    }
    
    // Search focus
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.querySelector('input[name="search"]');
        if (searchInput && !searchInput.value) {
            searchInput.focus();
        }
    });
</script>
</body>
</html>