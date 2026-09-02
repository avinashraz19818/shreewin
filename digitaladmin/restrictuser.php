<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (empty($_SESSION['unohs'])) {
    header("location: api/login.php?msg=unauthorized");
    exit();
}

date_default_timezone_set("Asia/Kolkata");
include("api/conn.php");

// AJAX toggle handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle') {
    header('Content-Type: application/json; charset=utf-8');
    $identifier = isset($_POST['update_user_id']) ? trim($_POST['update_user_id']) : '';
    $state = isset($_POST['state']) && $_POST['state'] === '1' ? 1 : 0;

    if ($identifier === '') {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'msg' => 'Invalid user identifier.']);
        exit;
    }

    $db_err = '';
    $ok = false;
    // numeric -> update by id column
    if (ctype_digit((string)$identifier)) {
        $uid = (int)$identifier;
        $sql = "UPDATE shonu_subjects SET is_betrestrict = ?, updated_at = NOW() WHERE id = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('ii', $state, $uid);
            $ok = $stmt->execute();
            if (!$ok) $db_err = $stmt->error;
            $stmt->close();
        } else {
            // fallback query
            $q = "UPDATE shonu_subjects SET is_betrestrict = '".(int)$state."' WHERE id = '".mysqli_real_escape_string($conn, $uid)."' LIMIT 1";
            $ok = $conn->query($q);
            if (!$ok) $db_err = $conn->error;
        }
    } else {
        // update by username (codechorkamukala)
        $username = $identifier;
        $sql = "UPDATE shonu_subjects SET is_betrestrict = ? WHERE codechorkamukala = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('is', $state, $username);
            $ok = $stmt->execute();
            if (!$ok) $db_err = $stmt->error;
            $stmt->close();
        } else {
            $q = "UPDATE shonu_subjects SET is_betrestrict = '".(int)$state."' WHERE codechorkamukala = '".mysqli_real_escape_string($conn, $username)."' LIMIT 1";
            $ok = $conn->query($q);
            if (!$ok) $db_err = $conn->error;
        }
    }

    if ($ok) {
        echo json_encode(['status' => 'ok', 'msg' => 'Updated successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'msg' => 'DB update failed', 'db_error' => $db_err]);
    }
    exit;
}

// Handle manual restrict form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['userid']) && !empty($_POST['userid'])) {
    $userid = mysqli_real_escape_string($conn, $_POST['userid']);

    // Update is_betrestrict to 1 for the given userid
    $updateQuery = "UPDATE shonu_subjects SET is_betrestrict = 1, updated_at = NOW() WHERE id = '$userid'";
    $updateResult = mysqli_query($conn, $updateQuery);

    if ($updateResult) {
        $message = "User bet restricted successfully!";
        $message_type = "success";
    } else {
        $message = "Failed to restrict user bet.";
        $message_type = "error";
    }
}

// Handle delete (remove restriction)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user_id']) && !empty($_POST['delete_user_id'])) {
    $userid = mysqli_real_escape_string($conn, $_POST['delete_user_id']);

    // Set is_betrestrict = 0
    $updateQuery = "UPDATE shonu_subjects SET is_betrestrict = 0, updated_at = NOW() WHERE id = '$userid'";
    if (mysqli_query($conn, $updateQuery)) {
        $message = "User bet restriction removed successfully!";
        $message_type = "success";
    } else {
        $message = "Failed to remove bet restriction.";
        $message_type = "error";
    }
}

// Search functionality
$message = '';
$message_type = 'info';
$search_input = '';
$user = null;

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['q'])) {
    $search_input = trim($_GET['q']);
    if ($search_input !== '') {
        $user = fetch_user($conn, $search_input);
        if (!$user) {
            $message = "User not found for '{$search_input}'";
            $message_type = 'warning';
        }
    } else {
        $message = "Please enter an ID or username to search.";
        $message_type = 'warning';
    }
}

// Helper function to fetch user
function fetch_user($conn, $identifier) {
    if ($identifier === '') return null;
    if (ctype_digit((string)$identifier)) {
        $id = (int)$identifier;
        $sql = "SELECT * FROM shonu_subjects WHERE id = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = ($res && $res->num_rows) ? $res->fetch_assoc() : null;
            $stmt->close();
            return $row;
        } else {
            $res = $conn->query("SELECT * FROM shonu_subjects WHERE id = '".mysqli_real_escape_string($conn, $id)."' LIMIT 1");
            return ($res && $res->num_rows) ? $res->fetch_assoc() : null;
        }
    } else {
        $username = $identifier;
        $sql = "SELECT * FROM shonu_subjects WHERE codechorkamukala = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = ($res && $res->num_rows) ? $res->fetch_assoc() : null;
            $stmt->close();
            return $row;
        } else {
            $res = $conn->query("SELECT * FROM shonu_subjects WHERE codechorkamukala = '".mysqli_real_escape_string($conn, $username)."' LIMIT 1");
            return ($res && $res->num_rows) ? $res->fetch_assoc() : null;
        }
    }
}

function badgeColor($type) {
    switch ($type) {
        case 'success': return '#16a34a';
        case 'warning': return '#f59e0b';
        case 'error':   return '#ef4444';
        default:        return '#475569';
    }
}

// Get restricted users list
$restricted_users_result = mysqli_query($conn, "SELECT id, codechorkamukala, is_betrestrict, updated_at FROM shonu_subjects WHERE is_betrestrict = 1 ORDER BY updated_at DESC");
$hasRestrictedUsers = ($restricted_users_result && mysqli_num_rows($restricted_users_result) > 0);
?>

<!doctype html>
<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr"
    data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template" data-style="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

    <title>Bet Restriction Management</title>

    <meta name="description" content="" />

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/img/favicon/favicon.ico?v=20260814-admin" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&ampdisplay=swap"
        rel="stylesheet" />

    <!-- Icons -->
    <link rel="stylesheet" href="assets/vendor/fonts/remixicon/remixicon.css" />
    <link rel="stylesheet" href="assets/vendor/fonts/flag-icons.css" />

    <!-- Menu waves for no-customizer fix -->
    <link rel="stylesheet" href="assets/vendor/libs/node-waves/node-waves.css" />

    <!-- Core CSS -->
    <link rel="stylesheet" href="assets/vendor/css/rtl/core.css" class="template-customizer-core-css" />
    <link rel="stylesheet" href="assets/vendor/css/rtl/theme-default.css" class="template-customizer-theme-css" />
    <link rel="stylesheet" href="assets/css/demo.css" />

    <!-- Vendors CSS -->
    <link rel="stylesheet" href="assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
    <link rel="stylesheet" href="assets/vendor/libs/typeahead-js/typeahead.css" />
    <link rel="stylesheet" href="assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css" />
    <link rel="stylesheet" href="assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css" />
    <link rel="stylesheet" href="assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css" />
    <link rel="stylesheet" href="assets/vendor/libs/datatables-checkboxes-jquery/datatables.checkboxes.css" />
    <link rel="stylesheet" href="assets/vendor/libs/select2/select2.css" />
    <link rel="stylesheet" href="assets/vendor/libs/@form-validation/form-validation.css" />

    <!-- Page CSS -->

    <!-- Helpers -->
    <script src="assets/vendor/js/helpers.js"></script>
    <script src="assets/vendor/js/template-customizer.js"></script>
    <script src="assets/js/config.js"></script>
    
    <style>
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        input:checked + .slider {
            background-color: #2196F3;
        }
        input:checked + .slider:before {
            transform: translateX(26px);
        }
        .user-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            background: #f9f9f9;
        }
    </style>
</head>

<body>
    <!-- Layout wrapper -->
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <!-- Menu -->
            <?php require_once("layout-menu.php"); ?>
            <!-- / Menu -->

            <!-- Layout container -->
            <div class="layout-page">
                <!-- Navbar -->
                <?php require_once("nav.php"); ?>
                <!-- / Navbar -->

                <!-- Content wrapper -->
                <div class="content-wrapper">
                    <div class="container-xxl flex-grow-1 container-p-y">
                        <!-- Display Messages -->
                        <?php if (!empty($message)): ?>
                            <div class="alert alert-<?= $message_type === 'success' ? 'success' : ($message_type === 'warning' ? 'warning' : 'danger') ?> alert-dismissible fade show" role="alert">
                                <?= htmlspecialchars($message) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <div class="app-ecommerce ">
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-6 row-gap-4">
                                <div class="d-flex flex-column justify-content-center">
                                    <h4 class="mb-1">Bet Restriction Management</h4>
                                </div>
                            </div>

                            <div class="row">
                                <!-- First column-->
                                <div class="col-12 col-lg-6">
                                    <!-- Restrict User -->
                                    <div class="card mb-6">
                                        <div class="card-header">
                                            <h5 class="card-tile mb-0">Restrict User Bet</h5>
                                        </div>
                                        <div class="card-body">
                                            <form action="" method="post" autocomplete="off">
                                                <div class="form-floating form-floating-outline mb-4">
                                                    <input type="text" class="form-control" id="userid" 
                                                        placeholder="Enter User ID" name="userid" required />
                                                    <label for="userid">Enter User ID</label>
                                                </div>
                                                <div class="row">
                                                    <div class="col">
                                                        <button class="btn btn-primary btn-lg w-100" type="submit">
                                                            <i class="ri-shield-keyhole-line ri-16px me-2"></i>Restrict Bet
                                                        </button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>

                                    <!-- Search User -->
                                    <div class="card mb-6">
                                        <div class="card-header">
                                            <h5 class="card-tile mb-0">Search User</h5>
                                        </div>
                                        <div class="card-body">
                                            <form action="" method="get" autocomplete="off">
                                                <div class="form-floating form-floating-outline mb-4">
                                                    <input type="text" class="form-control" id="search" 
                                                        placeholder="Enter User ID or Username" name="q" 
                                                        value="<?= htmlspecialchars($search_input) ?>" />
                                                    <label for="search">Enter User ID or Username</label>
                                                </div>
                                                <div class="row">
                                                    <div class="col">
                                                        <button class="btn btn-info btn-lg w-100" type="submit">
                                                            <i class="ri-search-line ri-16px me-2"></i>Search User
                                                        </button>
                                                    </div>
                                                </div>
                                            </form>

                                            <?php if ($user): ?>
                                            <div class="user-card mt-4">
                                                <h6>User Details:</h6>
                                                <p><strong>ID:</strong> <?= htmlspecialchars($user['id']) ?></p>
                                                <p><strong>Mobile:</strong> <?= htmlspecialchars($user['mobile']) ?></p>
                                                <p><strong>Gamil:</strong> <?= htmlspecialchars($user['email']) ?></p>
                                                <p><strong>Username:</strong> <?= htmlspecialchars($user['codechorkamukala']) ?></p>
                                                <p><strong>Current Status:</strong> 
                                                    <span class="badge bg-<?= $user['is_betrestrict'] ? 'danger' : 'success' ?>">
                                                        <?= $user['is_betrestrict'] ? 'Restricted' : 'Allowed' ?>
                                                    </span>
                                                </p>
                                                
                                                
                                                <div class="mt-3">
                                                    <!--<label class="form-label">Toggle Bet Restriction:</label>-->
                                                    <!--<div class="toggle-switch">-->
                                                    <!--    <input type="checkbox" id="toggle-<?= $user['id'] ?>" -->
                                                    <!--        <?= $user['is_betrestrict'] ? 'checked' : '' ?>-->
                                                    <!--        onchange="toggleRestriction('<?= $user['id'] ?>', this.checked ? 1 : 0)">-->
                                                    <!--    <span class="slider"></span>-->
                                                    <!--</div>-->
                                                    <!--<small class="text-muted d-block mt-1">-->
                                                    <!--    Switch to <?= $user['is_betrestrict'] ? 'allow' : 'restrict' ?> betting-->
                                                    <!--</small>-->
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Second column -->
                                <div class="col-12 col-lg-6">
                                    <!-- Restricted Users List -->
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="card-tile mb-0">Restricted Users List</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table table-striped">
                                                    <thead>
                                                        <tr>
                                                            <th>User Id</th>
                                                            <th>Username</th>
                                                            <th>Status</th>
                                                            <th>Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php
                                                        if ($hasRestrictedUsers) {
                                                            while ($row = mysqli_fetch_assoc($restricted_users_result)) {
                                                                ?>
                                                                <tr>
                                                                    <td><?= htmlspecialchars($row['id']) ?></td>
                                                                    <td><?= htmlspecialchars($row['codechorkamukala']) ?></td>
                                                                    <td>
                                                                        <span class="badge bg-danger">Restricted</span>
                                                                    </td>
                                                                    <td>
                                                                        <form action="" method="post" style="display: inline;">
                                                                            <input type="hidden" name="delete_user_id" value="<?= $row['id'] ?>">
                                                                            <button type="submit" class="btn btn-sm btn-danger" 
                                                                                    onclick="return confirm('Are you sure you want to remove bet restriction for this user?')">
                                                                                <i class="ri-delete-bin-line"></i> Remove
                                                                            </button>
                                                                        </form>
                                                                    </td>
                                                                </tr>
                                                                <?php
                                                            }
                                                        } else {
                                                            echo '<tr><td colspan="4" class="text-center text-muted">No restricted users found</td></tr>';
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
                    </div>

                    <!-- Footer -->
                    <?php require_once("footer.php"); ?>
                    <!-- / Footer -->

                    <div class="content-backdrop fade"></div>
                </div>
                <!-- Content wrapper -->
            </div>
            <!-- / Layout page -->
        </div>

        <!-- Overlay -->
        <div class="layout-overlay layout-menu-toggle"></div>

        <!-- Drag Target Area To SlideIn Menu On Small Screens -->
        <div class="drag-target"></div>
    </div>
    <!-- / Layout wrapper -->

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

    <!-- Vendors JS -->
    <script src="assets/vendor/libs/moment/moment.js"></script>
    <script src="assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js"></script>
    <script src="assets/vendor/libs/select2/select2.js"></script>
    <script src="assets/vendor/libs/@form-validation/popular.js"></script>
    <script src="assets/vendor/libs/@form-validation/bootstrap5.js"></script>
    <script src="assets/vendor/libs/@form-validation/auto-focus.js"></script>
    <script src="assets/vendor/libs/cleavejs/cleave.js"></script>
    <script src="assets/vendor/libs/cleavejs/cleave-phone.js"></script>

    <!-- Main JS -->
    <script src="assets/js/main.js"></script>

    <script>
        function toggleRestriction(userId, state) {
            $.ajax({
                url: '',
                type: 'POST',
                data: {
                    action: 'toggle',
                    update_user_id: userId,
                    state: state
                },
                success: function(response) {
                    const result = JSON.parse(response);
                    if (result.status === 'ok') {
                        alert('Bet restriction updated successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + result.msg);
                        location.reload();
                    }
                },
                error: function() {
                    alert('Error updating bet restriction');
                    location.reload();
                }
            });
        }
    </script>
</body>
</html>