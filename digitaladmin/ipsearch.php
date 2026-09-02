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

// Pagination and search variables
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search_userid = isset($_GET['search_userid']) ? $conn->real_escape_string($_GET['search_userid']) : '';
$offset = ($page - 1) * $limit;
$valid_limits = [10, 50, 100, 200];
if (!in_array($limit, $valid_limits)) $limit = 50;

// Count Total Same IP Users
$sameIpQuery = "SELECT COUNT(DISTINCT ishonup) AS total FROM shonu_subjects GROUP BY ishonup HAVING COUNT(*) > 1";
$sameIpResult = $conn->query($sameIpQuery);
$totalSameIpUsers = $sameIpResult->num_rows;

// Total Banned Users
$bannedQuery = "SELECT COUNT(*) AS total FROM shonu_subjects WHERE status = 0";
$bannedResult = $conn->query($bannedQuery);
$bannedRow = $bannedResult->fetch_assoc();
$totalBannedUsers = $bannedRow['total'];

// Total Authentic Users
$authenticQuery = "SELECT COUNT(*) AS total FROM shonu_subjects WHERE status = 1";
$authenticResult = $conn->query($authenticQuery);
$authenticRow = $authenticResult->fetch_assoc();
$totalAuthenticUsers = $authenticRow['total'];

// Build search condition
$search_condition = "";
if (!empty($search_userid)) {
    $search_condition = " AND s.id = '$search_userid'";
}

// Count total records for pagination
$count_query = "SELECT COUNT(*) as total FROM (
    SELECT s.ishonup 
    FROM shonu_subjects s 
    WHERE s.ishonup IN (
        SELECT ishonup FROM shonu_subjects GROUP BY ishonup HAVING COUNT(ishonup) > 1
    )
    $search_condition
    GROUP BY s.ishonup
) as temp";
$count_result = $conn->query($count_query);
$total_rows = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

// Fetch duplicate IPs with pagination and search
$duplicateIPsQuery = "SELECT s.ishonup 
FROM shonu_subjects s 
WHERE s.ishonup IN (
    SELECT ishonup FROM shonu_subjects GROUP BY ishonup HAVING COUNT(ishonup) > 1
)
$search_condition
GROUP BY s.ishonup 
LIMIT $limit OFFSET $offset";

$result = $conn->query($duplicateIPsQuery);

// Ban user if Ban button clicked
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ban_id'])) {
    $banId = intval($_POST['ban_id']);

    $banQuery = "UPDATE shonu_subjects SET status = 0 WHERE id = ?";
    $stmtBan = $conn->prepare($banQuery);
    $stmtBan->bind_param("i", $banId);

    if ($stmtBan->execute()) {
        echo "<script>alert('User ID $banId banned successfully.'); window.location.href=window.location.href;</script>";
    } else {
        echo "<script>alert('Failed to ban user.');</script>";
    }

    $stmtBan->close();
}

// Unban user if Unban button clicked
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unban_id'])) {
    $unbanId = intval($_POST['unban_id']);

    $unbanQuery = "UPDATE shonu_subjects SET status = 1 WHERE id = ?";
    $stmtUnban = $conn->prepare($unbanQuery);
    $stmtUnban->bind_param("i", $unbanId);

    if ($stmtUnban->execute()) {
        echo "<script>alert('User ID $unbanId activated successfully.'); window.location.href=window.location.href;</script>";
    } else {
        echo "<script>alert('Failed to activate user.');</script>";
    }

    $stmtUnban->close();
}

// Handle individual user search
$searched_users = [];
if (isset($_POST['userid'])) {
    $userid = mysqli_real_escape_string($conn, $_POST['userid']);

    // Pehle entered User ID ki IP nikalni
    $ipQuery = "SELECT ishonup FROM shonu_subjects WHERE id = '$userid'";
    $ipResult = mysqli_query($conn, $ipQuery);

    if ($ipRow = mysqli_fetch_assoc($ipResult)) {
        $userIP = $ipRow['ishonup'];

        // Ab us IP wale saare users ki details nikalni
        $sql = "SELECT 
                    s.id AS userid,
                    s.ishonup AS ip_address,
                    s.mobile,
                    COALESCE(k.motta, 0) AS balance,
                    s.status
                FROM shonu_subjects s
                LEFT JOIN shonu_kaichila k ON s.id = k.balakedara
                WHERE s.ishonup = '$userIP'";

        $search_result = mysqli_query($conn, $sql);

        if (mysqli_num_rows($search_result) > 0) {
            while ($row = mysqli_fetch_assoc($search_result)) {
                $searched_users[] = $row;
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
    <title>Multiple IP</title>
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
</head>

<body>
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <?php require_once("layout-menu.php"); ?>
            <div class="layout-page">
                <?php require_once("nav.php"); ?>
                <div class="content-wrapper">
                    <div class="container-xxl flex-grow-1 container-p-y">
                        <!-- Statistics Cards -->
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h6 class="card-title mb-2">Same IP Users</h6>
                                                <h4 class="text-primary"><?= $totalSameIpUsers ?></h4>
                                            </div>
                                            <div class="avatar">
                                                <span class="avatar-initial rounded bg-label-primary">
                                                    <i class="ri-group-line"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h6 class="card-title mb-2">Banned Users</h6>
                                                <h4 class="text-danger"><?= $totalBannedUsers ?></h4>
                                            </div>
                                            <div class="avatar">
                                                <span class="avatar-initial rounded bg-label-danger">
                                                    <i class="ri-user-forbid-line"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h6 class="card-title mb-2">Active Users</h6>
                                                <h4 class="text-success"><?= $totalAuthenticUsers ?></h4>
                                            </div>
                                            <div class="avatar">
                                                <span class="avatar-initial rounded bg-label-success">
                                                    <i class="ri-user-check-line"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="table-responsive text-nowrap">
                                <div class="d-flex justify-content-between align-items-center px-4 pt-4">
                                    <h4 class="mb-0">Same IP Users</h4>
                                    <div class="d-flex gap-3 align-items-center">
                                        <!-- Search Form -->
                                        <form method="POST" class="d-flex align-items-center">
                                            <input type="number" class="form-control me-2" name="userid" placeholder="Enter UID" required />
                                            <button type="submit" class="btn btn-primary">Search User</button>
                                        </form>
                                        
                                        <!-- Global Search Form -->
                                        <form method="GET" class="d-flex align-items-center">
                                            <input type="number" class="form-control me-2" name="search_userid" placeholder="Search by UID" value="<?= htmlspecialchars($search_userid) ?>" />
                                            <button type="submit" class="btn btn-info">Search IP</button>
                                        </form>
                                        
                                        <!-- Limit Selector -->
                                        <form method="GET" class="d-flex align-items-center">
                                            <select name="limit" class="form-select me-2" onchange="this.form.submit()">
                                                <?php foreach($valid_limits as $valid_limit): ?>
                                                    <option value="<?= $valid_limit ?>" <?= $limit == $valid_limit ? 'selected' : '' ?>>
                                                        <?= $valid_limit ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <input type="hidden" name="search_userid" value="<?= htmlspecialchars($search_userid) ?>">
                                        </form>
                                        
                                        <a href="" class="btn btn-success">All IPs</a>
                                    </div>
                                </div>

                                <!-- Searched User Results -->
                                <?php if (!empty($searched_users)): ?>
                                <div class="p-4">
                                    <h5 class="text-primary mb-3">Search Results for User ID: <?= htmlspecialchars($_POST['userid']) ?></h5>
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>User ID</th>
                                                <th>Mobile</th>
                                                <th>IP Address</th>
                                                <th>Balance</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($searched_users as $row): 
                                                $statusText = ($row["status"] == 1) ? "Active" : "Banned";
                                                $statusColor = ($row["status"] == 1) ? "success" : "danger";
                                            ?>
                                                <tr>
                                                    <td><?= $row["userid"]; ?></td>
                                                    <td><?= $row["mobile"]; ?></td>
                                                    <td><?= $row["ip_address"]; ?></td>
                                                    <td>₹ <?= number_format($row["balance"], 2); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?= $statusColor ?>"><?= $statusText ?></span>
                                                    </td>
                                                    <td>
                                                        <?php if($row["status"] == 1): ?>
                                                            <form method="POST" style="display:inline;">
                                                                <input type="hidden" name="ban_id" value="<?= $row['userid'] ?>">
                                                                <button type="submit" class="btn btn-sm btn-danger" 
                                                                        onclick="return confirm('Are you sure you want to ban User ID <?= $row['userid'] ?>?')">
                                                                    Ban
                                                                </button>
                                                            </form>
                                                        <?php else: ?>
                                                            <form method="POST" style="display:inline;">
                                                                <input type="hidden" name="unban_id" value="<?= $row['userid'] ?>">
                                                                <button type="submit" class="btn btn-sm btn-success" 
                                                                        onclick="return confirm('Are you sure you want to activate User ID <?= $row['userid'] ?>?')">
                                                                    Activate
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <hr>
                                <?php endif; ?>

                                <!-- Main Same IP Users Table -->
                                <div class="p-4">
                                    <table class="table table-striped" id="example1">
                                        <thead>
                                            <tr>
                                                <th>IP Address</th>
                                                <th>Users Count</th>
                                                <th>User IDs</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            if ($result && $result->num_rows > 0) {
                                                while ($ip_row = $result->fetch_assoc()) {
                                                    $ip = $ip_row['ishonup'];
                                                    
                                                    // Get all users with this IP
                                                    $usersQuery = "SELECT 
                                                                    s.id, 
                                                                    s.mobile, 
                                                                    s.status,
                                                                    COALESCE(k.motta, 0) AS balance
                                                                FROM shonu_subjects s
                                                                LEFT JOIN shonu_kaichila k ON s.id = k.balakedara
                                                                WHERE s.ishonup = '$ip'";
                                                    $usersResult = $conn->query($usersQuery);
                                                    $userCount = $usersResult->num_rows;
                                                    $userIDs = [];
                                                    $usersData = [];
                                                    
                                                    while ($user = $usersResult->fetch_assoc()) {
                                                        $userIDs[] = $user['id'];
                                                        $usersData[] = $user;
                                                    }
                                                    ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($ip) ?></td>
                                                        <td>
                                                            <span class="badge bg-warning"><?= $userCount ?> Users</span>
                                                        </td>
                                                        <td>
                                                            <div class="user-ids">
                                                                <?php foreach($userIDs as $userID): ?>
                                                                    <span class="badge bg-secondary me-1"><?= $userID ?></span>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <button class="btn btn-sm btn-info view-users" 
                                                                    data-ip="<?= htmlspecialchars($ip) ?>"
                                                                    data-users='<?= json_encode($usersData) ?>'>
                                                                View Details
                                                            </button>
                                                        </td>
                                                    </tr>
                                                    <?php
                                                }
                                            } else {
                                                echo "<tr><td colspan='4' class='text-center'>No duplicate IPs found</td></tr>";
                                            }
                                            ?>
                                        </tbody>
                                    </table>

                                    <!-- Pagination -->
                                    <?php if ($total_pages > 1): ?>
                                    <div class="d-flex justify-content-between align-items-center mt-4">
                                        <div>
                                            <p class="mb-0">Showing <?= $offset + 1 ?> to <?= min($offset + $limit, $total_rows) ?> of <?= $total_rows ?> entries</p>
                                        </div>
                                        <nav>
                                            <ul class="pagination mb-0">
                                                <?php if ($page > 1): ?>
                                                    <li class="page-item">
                                                        <a class="page-link" href="?page=<?= $page - 1 ?>&limit=<?= $limit ?>&search_userid=<?= urlencode($search_userid) ?>">Previous</a>
                                                    </li>
                                                <?php endif; ?>

                                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                                        <a class="page-link" href="?page=<?= $i ?>&limit=<?= $limit ?>&search_userid=<?= urlencode($search_userid) ?>"><?= $i ?></a>
                                                    </li>
                                                <?php endfor; ?>

                                                <?php if ($page < $total_pages): ?>
                                                    <li class="page-item">
                                                        <a class="page-link" href="?page=<?= $page + 1 ?>&limit=<?= $limit ?>&search_userid=<?= urlencode($search_userid) ?>">Next</a>
                                                    </li>
                                                <?php endif; ?>
                                            </ul>
                                        </nav>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php require_once("footer.php"); ?>
                    <div class="content-backdrop fade"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Users Modal -->
    <div class="modal fade" id="usersModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Users with IP: <span id="modalIp"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>User ID</th>
                                    <th>Mobile</th>
                                    <th>Balance</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="modalUsersBody">
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

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
    <script src="assets/vendor/libs/datatables/jquery.dataTables.js"></script>
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
            // View Users Modal
            $('.view-users').on('click', function() {
                var ip = $(this).data('ip');
                var users = $(this).data('users');
                
                $('#modalIp').text(ip);
                $('#modalUsersBody').empty();
                
                users.forEach(function(user) {
                    var statusText = user.status == 1 ? 'Active' : 'Banned';
                    var statusColor = user.status == 1 ? 'success' : 'danger';
                    var actionBtn = user.status == 1 ? 
                        '<form method="POST" style="display:inline;"><input type="hidden" name="ban_id" value="' + user.id + '"><button type="submit" class="btn btn-sm btn-danger" onclick="return confirm(\'Are you sure you want to ban User ID ' + user.id + '?\')">Ban</button></form>' :
                        '<form method="POST" style="display:inline;"><input type="hidden" name="unban_id" value="' + user.id + '"><button type="submit" class="btn btn-sm btn-success" onclick="return confirm(\'Are you sure you want to activate User ID ' + user.id + '?\')">Activate</button></form>';
                    
                    $('#modalUsersBody').append(
                        '<tr>' +
                        '<td>' + user.id + '</td>' +
                        '<td>' + user.mobile + '</td>' +
                        '<td>₹ ' + parseFloat(user.balance).toLocaleString('en-IN', {minimumFractionDigits: 2}) + '</td>' +
                        '<td><span class="badge bg-' + statusColor + '">' + statusText + '</span></td>' +
                        '<td>' + actionBtn + '</td>' +
                        '</tr>'
                    );
                });
                
                $('#usersModal').modal('show');
            });

            // Initialize DataTable
            $('#example1').DataTable({
                "paging": false,
                "searching": true,
                "ordering": true,
                "info": false,
                "responsive": true,
                "language": {
                    search: '',
                    searchPlaceholder: 'Search IP or Users'
                }
            });
        });
    </script>
</body>
</html>