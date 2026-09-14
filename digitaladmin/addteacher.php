<?php
session_start();
if (!isset($_SESSION['unohs']) || empty($_SESSION['unohs'])) {
    header("location: api/login.php?msg=unauthorized");
    exit();
}

include("api/conn.php");

$msg = '';

/* ================= TODAY SUM ================= */
function todaySum($conn, $table, $column, $userids)
{
    $date = date('Y-m-d');
    if (empty($userids)) return 0;

    $ids = implode(',', $userids);
    $res = mysqli_query($conn, "SELECT SUM($column) FROM $table WHERE balakedara IN ($ids) AND DATE(dinankavannuracisi) = '$date'");
    $row = mysqli_fetch_row($res);

    return $row[0] ?? 0;
}

/* ================= ADD TEACHER ================= */
if (isset($_POST['add_teacher'])) {

    $user_id = intval($_POST['user_id']);

    $user_check = mysqli_query($conn, "SELECT * FROM shonu_subjects WHERE id='$user_id'");

    if (mysqli_num_rows($user_check) == 0) {

        $msg = "error|User ID not found!";

    } else {

        $teacher_check = mysqli_query($conn, "SELECT * FROM teacher_profile WHERE user_id='$user_id'");

        if (mysqli_num_rows($teacher_check) > 0) {

            $msg = "warning|This user is already a teacher!";

        } else {

            $user_row = mysqli_fetch_assoc($user_check);

            /* ✅ FIXED NAME (IMPORTANT) */
            $name = $user_row['codechorkamukala'];

            /* ACCOUNT / TG */
            $tg = $user_row['mobile'];

            /* BALANCE */
            $balance_q = mysqli_query($conn, "SELECT motta FROM shonu_kaichila WHERE balakedara='$user_id'");
            $balance_row = mysqli_fetch_assoc($balance_q);
            $balance = $balance_row['motta'] ?? 0;

            $createdate = date('Y-m-d H:i:s');

            /* OWN CODE */
            $owncode_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT owncode FROM shonu_subjects WHERE id='$user_id'"));
            $teacher_code = $owncode_row['owncode'];

            /* INSERT (FIXED) */
            $insert = mysqli_query($conn, "INSERT INTO teacher_profile 
            (user_id, name, tg, teacher_code, commission_percent, total_agents, total_balance, created_at)
            VALUES 
            ('$user_id', '$name', '$tg', '$teacher_code', 10.00, 0, '$balance', '$createdate')");

            if ($insert) {
                $msg = "success|User ID $user_id is now a teacher!";
            } else {
                $msg = "error|Insert failed: " . mysqli_error($conn);
            }
        }
    }

    header("Location: addteacher.php?msg=$msg");
    exit;
}

/* ================= DELETE ================= */
if (isset($_GET['delete'])) {

    $id = intval($_GET['delete']);

    mysqli_query($conn, "DELETE FROM teacher_profile WHERE user_id='$id'");

    header("Location: addteacher.php?msg=success|Teacher deleted successfully!");
    exit;
}

/* ================= ALERT SAFE ================= */
$alert = '';

if (isset($_GET['msg'])) {

    $parts = explode('|', $_GET['msg'], 2);

    $type = $parts[0] ?? 'error';
    $text = $parts[1] ?? '';

    $icon = $type == 'success' ? 'ri-checkbox-circle-line' :
            ($type == 'warning' ? 'ri-alert-line' : 'ri-error-warning-line');

    $alert_class = $type == 'success' ? 'success' :
                   ($type == 'warning' ? 'warning' : 'danger');

    $alert = "
        <div class='alert alert-$alert_class alert-dismissible fade show'>
            <i class='$icon me-2'></i>$text
            <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
        </div>
    ";
}

/* ================= FETCH TEACHERS ================= */
$teachers_query = mysqli_query($conn, "
SELECT t.*, s.mobile, s.owncode
FROM teacher_profile t
JOIN shonu_subjects s ON t.user_id = s.id
ORDER BY t.id DESC
");

$total_teachers = mysqli_num_rows($teachers_query);

$total_agents = 0;
$total_balance = 0;
$teacher_data = [];

while ($row = mysqli_fetch_assoc($teachers_query)) {

    $teacher_id = $row['user_id'];

    $agent_ids = [];
    $res_agents = mysqli_query($conn, "SELECT userid FROM tb_agent WHERE teacherid='$teacher_id'");
    while ($agent = mysqli_fetch_assoc($res_agents)) {
        $agent_ids[] = $agent['userid'];
    }

    $downline_userids = [];

    if (!empty($agent_ids)) {

        $ids_str = implode(",", $agent_ids);

        $res_down = mysqli_query($conn, "SELECT id FROM shonu_subjects WHERE id IN ($ids_str)");

        while ($u = mysqli_fetch_assoc($res_down)) {
            $downline_userids[] = $u['id'];
        }
    }

    $today_recharge = todaySum($conn, 'thevani', 'motta', $downline_userids);
    $today_withdrawal = todaySum($conn, 'hintegedukolli', 'motta', $downline_userids);

    $teacher_data[] = [
        'row' => $row,
        'today_recharge' => $today_recharge,
        'today_withdrawal' => $today_withdrawal,
        'total_users' => count($downline_userids)
    ];

    $total_agents += $row['total_agents'];
    $total_balance += $row['total_balance'];
}
?>

<!doctype html>
<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr"
    data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template" data-style="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Teacher Management - Dashboard</title>

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

    <!-- Core CSS -->
    <link rel="stylesheet" href="assets/vendor/libs/node-waves/node-waves.css" />
    <link rel="stylesheet" href="assets/vendor/css/rtl/core.css" class="template-customizer-core-css" />
    <link rel="stylesheet" href="assets/vendor/css/rtl/theme-default.css" class="template-customizer-theme-css" />
    <link rel="stylesheet" href="assets/css/demo.css" />

    <!-- Vendors CSS -->
    <link rel="stylesheet" href="assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
    <link rel="stylesheet" href="assets/vendor/libs/typeahead-js/typeahead.css" />
    <link rel="stylesheet" href="assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css" />
    <link rel="stylesheet" href="assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css" />
    <link rel="stylesheet" href="assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css" />
    <link rel="stylesheet" href="assets/vendor/libs/select2/select2.css" />
    <link rel="stylesheet" href="assets/vendor/libs/@form-validation/form-validation.css" />

    <!-- Page CSS -->
    <style>
        .stat-card {
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card .icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .stat-card .title {
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #ffffff;
            margin-bottom: 0.5rem;
        }

        .stat-card .value {
            font-size: 1.75rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .bg-teacher {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .bg-agent {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }

        .bg-balance {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            justify-content: center;
        }

        .referral-link {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .table-actions {
            min-width: 120px;
        }

        .badge-today {
            background-color: #20c997;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
        }

        .form-add-teacher {
            /*background: white;*/
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .mobile-display {
            font-family: monospace;
            font-weight: 500;
            color: #495057;
        }

        .no-data {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }

        .no-data i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
    </style>

    <!-- Helpers -->
    <script src="assets/vendor/js/helpers.js"></script>
    <script src="assets/vendor/js/template-customizer.js"></script>
    <script src="assets/js/config.js"></script>
</head>

<body>
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <!-- Menu -->
            <?php require_once("layout-menu.php"); ?>
            <!-- / Menu -->

            <!-- Layout container -->
            <div class="layout-page">
                <!-- Navbar -->
                <?php require_once("nav.php"); ?>

                <!-- Content wrapper -->
                <div class="content-wrapper">
                    <!-- Content -->
                    <div class="container-xxl flex-grow-1 container-p-y">

                        <!-- Page Title -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="fw-bold mb-0">
                                <i class="ri-group-line me-2"></i>Teacher Management
                            </h4>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb breadcrumb-style1">
                                    <li class="breadcrumb-item">
                                        <a href="javascript:void(0);">Dashboard</a>
                                    </li>
                                    <li class="breadcrumb-item active">Teachers</li>
                                </ol>
                            </nav>
                        </div>

                        <!-- Alert Messages -->
                        <?php echo $alert; ?>

                        <!-- Stats Cards -->
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="stat-card bg-teacher">
                                    <div class="icon">
                                        <i class="ri-user-star-line"></i>
                                    </div>
                                    <div class="title">Total Teachers</div>
                                    <div class="value"><?= number_format($total_teachers) ?></div>
                                    <div class="subtitle">Active Teachers</div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="stat-card bg-agent">
                                    <div class="icon">
                                        <i class="ri-team-line"></i>
                                    </div>
                                    <div class="title">Total Agents</div>
                                    <div class="value"><?= number_format($total_agents) ?></div>
                                    <div class="subtitle">Under Teachers</div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="stat-card bg-balance">
                                    <div class="icon">
                                        <i class="ri-wallet-line"></i>
                                    </div>
                                    <div class="title">Total Balance</div>
                                    <div class="value">₹<?= number_format($total_balance, 2) ?></div>
                                    <div class="subtitle">Combined Balance</div>
                                </div>
                            </div>
                        </div>

                        <!-- Add Teacher Form -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="ri-user-add-line me-2"></i>Promote User to Teacher
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row justify-content-center">
                                    <div class="col-md-8">
                                        <div class="form-add-teacher">
                                            <form method="POST">
                                                <input type="hidden" name="add_teacher" value="1">
                                                <div class="mb-3">
                                                    <label class="form-label">Enter User ID to Promote</label>
                                                    <div class="input-group input-group-merge">
                                                        <span class="input-group-text">
                                                            <i class="ri-user-search-line"></i>
                                                        </span>
                                                        <input type="number" name="user_id" class="form-control"
                                                            placeholder="Enter existing User ID"
                                                            min="1" required>
                                                    </div>
                                                    <div class="form-text">Enter the ID of an existing user to grant teacher privileges.</div>
                                                </div>
                                                <div class="d-grid">
                                                    <button type="submit" class="btn btn-primary btn-lg">
                                                        <i class="ri-user-star-fill me-2"></i>Promote to Teacher
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Teachers List -->
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">
                                    <i class="ri-list-check-2 me-2"></i>Teachers List
                                </h5>
                                <div>
                                    <span class="badge bg-primary">Total: <?= $total_teachers ?></span>
                                </div>
                            </div>
                            <div class="card-body">
                                <?php if ($total_teachers > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover" id="teachersTable">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Mobile</th>
                                                    <th>Own Code</th>
                                                    <th>Referral</th>
                                                    <th>Balance</th>
                                                    <th>Today Activity</th>
                                                    <th>Downline</th>
                                                    <th class="text-center">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($teacher_data as $item):
                                                    $row = $item['row'];
                                                    $mobile_display = substr($row['mobile'], 2);
                                                    $referral_url = "https://dreambd99.pro.bd/#/register?invitationCode=" . $row['owncode'];
                                                ?>
                                                    <tr>
                                                        <td>
                                                            <span class="fw-bold">#<?= $row['id'] ?></span>
                                                        </td>
                                                        <td>
                                                            <div class="mobile-display">
                                                                <i class="ri-phone-line me-1"></i>
                                                                +880 <?= $mobile_display ?>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <code class="text-primary"><?= $row['owncode'] ?></code>
                                                        </td>
                                                        <td>
                                                            <a href="<?= $referral_url ?>" target="_blank"
                                                                class="btn btn-sm btn-outline-primary"
                                                                title="Copy Referral Link"
                                                                onclick="copyToClipboard('<?= $referral_url ?>')">
                                                                <i class="ri-link me-1"></i>Link
                                                            </a>
                                                        </td>
                                                        <td>
                                                            <span class="fw-bold text-success">₹<?= number_format($row['motta'], 2) ?></span>
                                                        </td>
                                                        <td>
                                                            <div class="d-flex flex-column">
                                                                <small>
                                                                    <i class="ri-money-rupee-circle-line me-1 text-success"></i>
                                                                    <span class="badge-today">₹<?= number_format($item['today_recharge'], 2) ?></span>
                                                                </small>
                                                                <small class="mt-1">
                                                                    <i class="ri-bank-card-line me-1 text-danger"></i>
                                                                    <span class="badge bg-light text-dark">₹<?= number_format($item['today_withdrawal'], 2) ?></span>
                                                                </small>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-info"><?= number_format($item['total_users']) ?> Users</span>
                                                        </td>
                                                        <td class="table-actions text-center">
                                                            <div class="action-buttons">
                                                                <!--<a href="teacher-details.php?id=<?= $row['id'] ?>" -->
                                                                <!--   class="btn btn-sm btn-icon btn-outline-info"-->
                                                                <!--   title="View Details">-->
                                                                <!--    <i class="ri-eye-line"></i>-->
                                                                <!--</a>-->
                                                                <a href="?delete=<?= $row['id'] ?>"
                                                                    class="btn btn-sm btn-icon btn-outline-danger"
                                                                    title="Remove Teacher"
                                                                    onclick="return confirmDelete(<?= $row['id'] ?>)">
                                                                    <i class="ri-delete-bin-line"></i>
                                                                </a>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="no-data">
                                        <i class="ri-user-search-line"></i>
                                        <h5 class="mt-3">No Teachers Found</h5>
                                        <p class="text-muted">Promote users to teachers using the form above.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>
                    <!-- / Content -->

                    <!-- Footer -->
                    <?php include("footer.php"); ?>
                    <div class="content-backdrop fade"></div>
                </div>
                <!-- Content wrapper -->
            </div>
            <!-- / Layout page -->
        </div>

        <!-- Overlay -->
        <div class="layout-overlay layout-menu-toggle"></div>
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
        $(document).ready(function() {
            // Initialize DataTable
            $('#teachersTable').DataTable({
                "paging": true,
                "lengthChange": true,
                "searching": true,
                "ordering": true,
                "info": true,
                "autoWidth": false,
                "pageLength": 10,
                "responsive": true,
                "order": [
                    [0, 'desc']
                ],
                "columnDefs": [{
                        "orderable": false,
                        "targets": [7] // Actions column
                    },
                    {
                        "className": "text-center",
                        "targets": [7]
                    }
                ],
                "language": {
                    "search": "",
                    "searchPlaceholder": "Search teachers...",
                    "lengthMenu": "Show _MENU_ entries",
                    "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                    "paginate": {
                        "next": '<i class="ri-arrow-right-s-line"></i>',
                        "previous": '<i class="ri-arrow-left-s-line"></i>'
                    }
                }
            });

            // Auto-focus user ID input
            $('input[name="user_id"]').focus();

            // Form validation
            $('form').on('submit', function(e) {
                const userId = $('input[name="user_id"]').val();
                if (!userId || userId <= 0) {
                    e.preventDefault();
                    alert('Please enter a valid User ID');
                    return false;
                }
                return true;
            });
        });

        function confirmDelete(userId) {
            return confirm(`Are you sure you want to remove teacher #${userId}?\nThis action cannot be undone.`);
        }

        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                // Show success message
                const originalText = event.target.innerHTML;
                event.target.innerHTML = '<i class="ri-check-line me-1"></i>Copied!';
                event.target.classList.remove('btn-outline-primary');
                event.target.classList.add('btn-success');

                setTimeout(function() {
                    event.target.innerHTML = originalText;
                    event.target.classList.remove('btn-success');
                    event.target.classList.add('btn-outline-primary');
                }, 2000);
            }).catch(function(err) {
                console.error('Failed to copy: ', err);
                alert('Failed to copy link to clipboard');
            });
        }
    </script>
</body>

</html>