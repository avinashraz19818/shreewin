<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (empty($_SESSION['unohs'])) {
    header("location: api/login.php?msg=unauthorized");
}
include("api/conn.php");

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
        $_SESSION['msg'] = "❌ Invalid User ID.";
        header("Location: agents_management.php");
        exit;
    }
    if (!in_array($type, ['day', 'week', 'month'])) {
        $_SESSION['msg'] = "❌ Invalid salary type.";
        header("Location: agents_management.php");
        exit;
    }

    // Check user exists and fetch mobile
    $sql = "SELECT mobile FROM shonu_subjects WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        error_log("Prepare failed (select mobile): " . mysqli_error($conn));
        $_SESSION['msg'] = "❌ Database error. Contact admin.";
        header("Location: agents_management.php");
        exit;
    }
    mysqli_stmt_bind_param($stmt, "i", $userid);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if (!$res || mysqli_num_rows($res) !== 1) {
        $_SESSION['msg'] = "❌ UID not found in subjects table.";
        mysqli_stmt_close($stmt);
        header("Location: agents_management.php");
        exit;
    }
    $row = mysqli_fetch_assoc($res);
    $mobile = $row['mobile'] ?? '';
    mysqli_stmt_close($stmt);

    // Check if active agent already exists
    $sql = "SELECT id FROM tb_agent WHERE userid = ? AND status = 1 LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        error_log("Prepare failed (exists check): " . mysqli_error($conn));
        $_SESSION['msg'] = "❌ Database error. Contact admin.";
        header("Location: agents_management.php");
        exit;
    }
    mysqli_stmt_bind_param($stmt, "i", $userid);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($res && mysqli_num_rows($res) > 0) {
        $_SESSION['msg'] = "⚠️ Agent already exists and is active.";
        mysqli_stmt_close($stmt);
        header("Location: agents_management.php");
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
        $_SESSION['msg'] = "✅ Agent Added Successfully.";
    } else {
        error_log("Insert failed tb_agent: " . mysqli_stmt_error($stmt));
        $_SESSION['msg'] = "❌ Failed to add agent. DB error logged.";
    }
    mysqli_stmt_close($stmt);

    header("Location: agents_management.php");
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
        $_SESSION['msg'] = "❌ Invalid update data.";
        header("Location: agents_management.php");
        exit;
    }

    $sql = "UPDATE tb_agent 
            SET salary=?, salarypercent=?, type=?, minrecharge=?, minbet=?, minreferrals=?
            WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        error_log("Prepare failed (update): " . mysqli_error($conn));
        $_SESSION['msg'] = "❌ Database error. Contact admin.";
        header("Location: agents_management.php");
        exit;
    }
    mysqli_stmt_bind_param($stmt, "ddsiiii", $salary, $percent, $type, $minrecharge, $minbet, $minreferrals, $updateId);
    $ok = mysqli_stmt_execute($stmt);
    if ($ok) {
        $_SESSION['msg'] = "✅ Agent updated successfully.";
    } else {
        error_log("Update failed tb_agent id={$updateId}: " . mysqli_stmt_error($stmt));
        $_SESSION['msg'] = "❌ Failed to update agent. DB error logged.";
    }
    mysqli_stmt_close($stmt);

    header("Location: agents_management.php");
    exit;
}

/*
  -----------------------------
  Delete Agent (GET)
  -----------------------------
*/
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delId = intval($_GET['delete']);
    $sql = "DELETE FROM tb_agent WHERE id = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $delId);
        $ok = mysqli_stmt_execute($stmt);
        if ($ok) {
            $_SESSION['msg'] = "❌ Agent deleted successfully.";
        } else {
            error_log("Delete failed tb_agent id={$delId}: " . mysqli_stmt_error($stmt));
            $_SESSION['msg'] = "❌ Failed to delete agent. DB error logged.";
        }
        mysqli_stmt_close($stmt);
    } else {
        error_log("Prepare failed (delete): " . mysqli_error($conn));
        $_SESSION['msg'] = "❌ Database error. Contact admin.";
    }
    header("Location: agents_management.php");
    exit;
}

/*
  -----------------------------
  Fetch Stats & Agent List
  -----------------------------
*/
$total_agents = 0;
$inactive_agents = 0;
$total_salary = 0;
$all_agents = [];

// Total agents count
$res = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM tb_agent");
if ($res) {
    $total_agents = intval(mysqli_fetch_assoc($res)['cnt'] ?? 0);
}

// Inactive agents count (status != 1)
$res = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM tb_agent WHERE status != 1");
if ($res) {
    $inactive_agents = intval(mysqli_fetch_assoc($res)['cnt'] ?? 0);
}

// Total salary sum
$res = mysqli_query($conn, "SELECT SUM(salary) AS total FROM dailysalary");
if ($res) {
    $total_salary = floatval(mysqli_fetch_assoc($res)['total'] ?? 0);
}

// All agents list
$res = mysqli_query($conn, "SELECT * FROM tb_agent ORDER BY id DESC");
if ($res) {
    while ($r = mysqli_fetch_assoc($res)) {
        $all_agents[] = $r;
    }
}
?>

<!DOCTYPE html>
<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr" 
    data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template" data-style="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Agent Management</title>
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
                        
                        <!-- Success/Error Messages -->
                        <?php if (isset($_SESSION['msg'])): ?>
                            <div class="alert alert-<?= strpos($_SESSION['msg'], '❌') !== false ? 'danger' : 'success' ?> alert-dismissible fade show" role="alert">
                                <?= $_SESSION['msg'] ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                            <?php unset($_SESSION['msg']); ?>
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
                                                <p class="card-text">Inactive Agents</p>
                                                <div class="d-flex align-items-end mb-2">
                                                    <h4 class="card-title mb-0 me-2"><?= $inactive_agents ?></h4>
                                                </div>
                                            </div>
                                            <div class="card-icon">
                                                <span class="badge bg-label-warning rounded p-2">
                                                    <i class="ri-user-unfollow-line"></i>
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
                                                <p class="card-text">Total Salary</p>
                                                <div class="d-flex align-items-end mb-2">
                                                    <h4 class="card-title mb-0 me-2">₹<?= number_format($total_salary, 2) ?></h4>
                                                </div>
                                            </div>
                                            <div class="card-icon">
                                                <span class="badge bg-label-success rounded p-2">
                                                    <i class="ri-money-rupee-circle-line"></i>
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
                                                <p class="card-text">Active Agents</p>
                                                <div class="d-flex align-items-end mb-2">
                                                    <h4 class="card-title mb-0 me-2"><?= $total_agents - $inactive_agents ?></h4>
                                                </div>
                                            </div>
                                            <div class="card-icon">
                                                <span class="badge bg-label-info rounded p-2">
                                                    <i class="ri-user-follow-line"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">Agent Management</h5>
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAgentModal">
                                    <i class="ri-user-add-line me-1"></i>Add New Agent
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="datatables-agents table table-hover">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>User ID</th>
                                                <th>Mobile</th>
                                                <th>Salary</th>
                                                <th>Salary %</th>
                                                <th>Type</th>
                                                <th>Min Recharge</th>
                                                <th>Min Bet</th>
                                                <th>Min Referrals</th>
                                                <th>Status</th>
                                                <th>Created Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($all_agents as $index => $agent): ?>
                                                <tr>
                                                    <td><?= $index + 1 ?></td>
                                                    <td><?= htmlspecialchars($agent['userid']) ?></td>
                                                    <td><?= htmlspecialchars($agent['mobile']) ?></td>
                                                    <td>₹<?= number_format($agent['salary'], 2) ?></td>
                                                    <td><?= number_format($agent['salarypercent'], 2) ?>%</td>
                                                    <td>
                                                        <span class="badge bg-label-<?= $agent['type'] == 'day' ? 'primary' : ($agent['type'] == 'week' ? 'warning' : 'success') ?>">
                                                            <?= ucfirst($agent['type']) ?>
                                                        </span>
                                                    </td>
                                                    <td>₹<?= number_format($agent['minrecharge']) ?></td>
                                                    <td><?= $agent['minbet'] ?></td>
                                                    <td><?= $agent['minreferrals'] ?></td>
                                                    <td>
                                                        <span class="badge bg-label-<?= $agent['status'] == 1 ? 'success' : 'danger' ?>">
                                                            <?= $agent['status'] == 1 ? 'Active' : 'Inactive' ?>
                                                        </span>
                                                    </td>
                                                    <td><?= date('d-m-Y H:i', strtotime($agent['createdate'])) ?></td>
                                                    <td>
                                                        <div class="d-flex gap-2">
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
                                                            <a href="agents_management.php?delete=<?= $agent['id'] ?>" 
                                                               class="btn btn-sm btn-icon btn-outline-danger"
                                                               onclick="return confirm('Are you sure you want to delete this agent?')">
                                                                <i class="ri-delete-bin-line"></i>
                                                            </a>
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
    <script src="assets/js/main.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('.datatables-agents').DataTable({
                "paging": true,
                "lengthChange": true,
                "searching": true,
                "ordering": true,
                "info": true,
                "autoWidth": true,
                "pageLength": 10,
                "dom": '<"row"' +
                    '<"col-md-12"<"d-flex align-items-center justify-content-md-end justify-content-center"<l><"me-4"f>>>' +
                    '>t' +
                    '<"row p-5"' +
                    '<"col-sm-12 col-md-6"i>' +
                    '<"col-sm-12 col-md-6"p>' +
                    '>',
                "language": {
                    sLengthMenu: 'Show _MENU_',
                    search: '',
                    searchPlaceholder: 'Search agents...',
                    paginate: {
                        next: '<i class="ri-arrow-right-s-line"></i>',
                        previous: '<i class="ri-arrow-left-s-line"></i>'
                    }
                }
            });

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
        });
    </script>
</body>
</html>