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

$updateSuccess = false;
$updateError = false;

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $taskAmounts = $_POST['taskAmount'];
    $rechargeAmounts = $_POST['rechargeAmount'];
    $taskPeoples = $_POST['taskPeople'];
    $ids = $_POST['id'];  // IDs to identify which row to update
    
    for ($i = 0; $i < count($ids); $i++) {
        $taskAmount = $taskAmounts[$i];
        $rechargeAmount = $rechargeAmounts[$i];
        $taskPeople = $taskPeoples[$i];
        $id = $ids[$i];

        // Fetch the existing data to check if there's an actual change
        $query = "SELECT taskAmount, rechargeAmount, taskPeople FROM tbl_invitebonus WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row && ($taskAmount != $row['taskAmount'] || $rechargeAmount != $row['rechargeAmount'] || $taskPeople != $row['taskPeople'])) {
            // Only update if values are changed
            $sql = "UPDATE tbl_invitebonus SET taskAmount = ?, rechargeAmount = ?, taskPeople = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("diii", $taskAmount, $rechargeAmount, $taskPeople, $id);

            if ($stmt->execute()) {
                $updateSuccess = true;
            } else {
                $updateError = true;
            }
            $stmt->close();
        }
    }
    
    if ($updateSuccess) {
        $message = "✅ Invite bonus settings updated successfully!";
        $message_type = "success";
    } elseif ($updateError) {
        $message = "❌ Error updating invite bonus settings!";
        $message_type = "error";
    }
}

// Handle add new bonus
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_bonus'])) {
    $newTaskAmount = $_POST['new_taskAmount'];
    $newRechargeAmount = $_POST['new_rechargeAmount'];
    $newTaskPeople = $_POST['new_taskPeople'];
    
    $insertQuery = "INSERT INTO tbl_invitebonus (taskAmount, rechargeAmount, taskPeople) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($insertQuery);
    $stmt->bind_param("dii", $newTaskAmount, $newRechargeAmount, $newTaskPeople);
    
    if ($stmt->execute()) {
        $message = "✅ New invite bonus added successfully!";
        $message_type = "success";
    } else {
        $message = "❌ Error adding new invite bonus!";
        $message_type = "error";
    }
    $stmt->close();
}

// Handle delete bonus
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $deleteQuery = "DELETE FROM tbl_invitebonus WHERE id = ?";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $message = "✅ Invite bonus deleted successfully!";
        $message_type = "success";
    } else {
        $message = "❌ Error deleting invite bonus!";
        $message_type = "error";
    }
    $stmt->close();
}

// Fetch all data to display in the table
$result = $conn->query("SELECT * FROM tbl_invitebonus ORDER BY taskPeople ASC");
$total_bonuses = $result->num_rows;

// Calculate statistics
$total_reward = 0;
$total_invites = 0;
$result->data_seek(0);
while ($row = $result->fetch_assoc()) {
    $total_reward += $row['taskAmount'];
    $total_invites += $row['taskPeople'];
}
$result->data_seek(0);
?>

<!doctype html>
<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr"
    data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template" data-style="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Invite Bonus Settings</title>
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
        .bonus-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            /*background: #fff;*/
            transition: all 0.3s ease;
        }
        .bonus-card:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .stats-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            /*background: #fff;*/
            text-align: center;
        }
        .stats-value {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
        }
        .stats-label {
            font-size: 14px;
            color: #6c757d;
        }
        .bonus-level {
            font-size: 12px;
            color: #6c757d;
            margin-top: 5px;
        }
        .input-group-sm .form-control {
            font-size: 14px;
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
                        <?php if (isset($message)): ?>
                            <div class="alert alert-<?= $message_type === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
                                <?= htmlspecialchars($message) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <div class="row">
                            <!-- Add New Bonus -->
                            <div class="col-md-4">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Add New Invite Bonus</h5>
                                    </div>
                                    <div class="card-body">
                                        <form action="" method="post">
                                            <div class="mb-3">
                                                <label for="new_taskPeople" class="form-label">Required Invites *</label>
                                                <input type="number" class="form-control" id="new_taskPeople" name="new_taskPeople" 
                                                       min="1" required placeholder="Number of invites required">
                                            </div>

                                            <div class="mb-3">
                                                <label for="new_rechargeAmount" class="form-label">Recharge Amount (₹) *</label>
                                                <input type="number" class="form-control" id="new_rechargeAmount" name="new_rechargeAmount" 
                                                       step="0.01" min="0" required placeholder="Recharge amount required">
                                            </div>

                                            <div class="mb-3">
                                                <label for="new_taskAmount" class="form-label">Bonus Reward (₹) *</label>
                                                <input type="number" class="form-control" id="new_taskAmount" name="new_taskAmount" 
                                                       step="0.01" min="0" required placeholder="Bonus reward amount">
                                            </div>

                                            <button type="submit" name="add_bonus" class="btn btn-primary w-100">
                                                <i class="ri-add-line ri-16px me-2"></i>Add Bonus Level
                                            </button>
                                        </form>
                                    </div>
                                </div>

                                <!-- Statistics -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Bonus Statistics</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="stats-card mb-3">
                                            <div class="stats-value"><?= $total_bonuses ?></div>
                                            <div class="stats-label">Total Bonus Levels</div>
                                        </div>
                                        <div class="stats-card mb-3">
                                            <div class="stats-value" style="color: #28a745;"><?= $total_invites ?></div>
                                            <div class="stats-label">Total Required Invites</div>
                                        </div>
                                        <div class="stats-card">
                                            <div class="stats-value" style="color: #ffc107;">₹<?= number_format($total_reward, 2) ?></div>
                                            <div class="stats-label">Total Reward Amount</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Information -->
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">How Invite Bonus Works</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="alert alert-info">
                                            <h6 class="alert-heading">Bonus System:</h6>
                                            <ul class="mb-0 ps-3">
                                                <li><strong>Required Invites:</strong> Number of friends user needs to invite</li>
                                                <li><strong>Recharge Amount:</strong> Minimum recharge required by referred friends</li>
                                                <li><strong>Bonus Reward:</strong> Reward amount user receives</li>
                                            </ul>
                                        </div>
                                        <div class="alert alert-warning">
                                            <h6 class="alert-heading">Example:</h6>
                                            <p class="mb-0">
                                                Invite <strong>5 friends</strong> who recharge <strong>₹100+</strong> each → Get <strong>₹50 bonus</strong>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Existing Bonuses -->
                            <div class="col-md-8">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Invite Bonus Levels (<?= $total_bonuses ?>)</h5>
                                    </div>
                                    <div class="card-body">
                                        <form action="" method="post">
                                            <?php if ($result->num_rows > 0): ?>
                                                <div class="row">
                                                    <?php while ($row = $result->fetch_assoc()): ?>
                                                        <div class="col-md-6 mb-4">
                                                            <div class="bonus-card">
                                                                <input type="hidden" name="id[]" value="<?= $row['id'] ?>">
                                                                
                                                                <!-- Bonus Header -->
                                                                <div class="d-flex justify-content-between align-items-start mb-3">
                                                                    <div>
                                                                        <h6 class="mb-1">Level #<?= $row['id'] ?></h6>
                                                                        <span class="badge bg-primary">
                                                                            <?= $row['taskPeople'] ?> Invites
                                                                        </span>
                                                                    </div>
                                                                    <div class="dropdown">
                                                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                                                                type="button" data-bs-toggle="dropdown">
                                                                            <i class="ri-more-2-line"></i>
                                                                        </button>
                                                                        <ul class="dropdown-menu">
                                                                            <li>
                                                                                <a class="dropdown-item text-danger" 
                                                                                   href="?delete=<?= $row['id'] ?>" 
                                                                                   onclick="return confirm('Are you sure you want to delete this bonus level?')">
                                                                                    <i class="ri-delete-bin-line me-2"></i>Delete
                                                                                </a>
                                                                            </li>
                                                                        </ul>
                                                                    </div>
                                                                </div>

                                                                <!-- Required Invites -->
                                                                <div class="mb-3">
                                                                    <label class="form-label small">Required Invites</label>
                                                                    <input type="number" class="form-control" name="taskPeople[]" 
                                                                           value="<?= htmlspecialchars($row['taskPeople']) ?>" 
                                                                           min="1" required>
                                                                    <div class="bonus-level">
                                                                        Number of friends to invite
                                                                    </div>
                                                                </div>

                                                                <!-- Recharge Amount -->
                                                                <div class="mb-3">
                                                                    <label class="form-label small">Recharge Amount (₹)</label>
                                                                    <input type="number" class="form-control" name="rechargeAmount[]" 
                                                                           value="<?= htmlspecialchars($row['rechargeAmount']) ?>" 
                                                                           step="0.01" min="0" required>
                                                                    <div class="bonus-level">
                                                                        Each friend must recharge this amount
                                                                    </div>
                                                                </div>

                                                                <!-- Bonus Reward -->
                                                                <div class="mb-3">
                                                                    <label class="form-label small">Bonus Reward (₹)</label>
                                                                    <input type="number" class="form-control" name="taskAmount[]" 
                                                                           value="<?= htmlspecialchars($row['taskAmount']) ?>" 
                                                                           step="0.01" min="0" required>
                                                                    <div class="bonus-level">
                                                                        Reward amount user receives
                                                                    </div>
                                                                </div>

                                                                <!-- Bonus Summary -->
                                                                <div class="alert alert-light mt-3">
                                                                    <small class="text-muted">
                                                                        <strong>Summary:</strong> Invite <?= $row['taskPeople'] ?> friends who recharge ₹<?= number_format($row['rechargeAmount'], 2) ?>+ each → Get ₹<?= number_format($row['taskAmount'], 2) ?> bonus
                                                                    </small>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php endwhile; ?>
                                                </div>

                                                <!-- Update Button -->
                                                <div class="row mt-4">
                                                    <div class="col-12">
                                                        <button type="submit" class="btn btn-primary btn-lg w-100">
                                                            <i class="ri-save-line ri-16px me-2"></i>Update All Bonus Levels
                                                        </button>
                                                    </div>
                                                </div>

                                            <?php else: ?>
                                                <div class="text-center py-5">
                                                    <div class="avatar avatar-lg mb-3">
                                                        <span class="avatar-initial rounded bg-label-secondary">
                                                            <i class="ri-user-shared-line ri-24px"></i>
                                                        </span>
                                                    </div>
                                                    <h5 class="text-muted">No Bonus Levels Found</h5>
                                                    <p class="text-muted">Add your first invite bonus level using the form on the left.</p>
                                                </div>
                                            <?php endif; ?>
                                        </form>
                                    </div>
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
        // Auto-update bonus summary
        document.addEventListener('input', function(e) {
            if (e.target.name === 'taskPeople[]' || e.target.name === 'rechargeAmount[]' || e.target.name === 'taskAmount[]') {
                const card = e.target.closest('.bonus-card');
                const taskPeople = card.querySelector('input[name="taskPeople[]"]');
                const rechargeAmount = card.querySelector('input[name="rechargeAmount[]"]');
                const taskAmount = card.querySelector('input[name="taskAmount[]"]');
                const summary = card.querySelector('.alert-light small');
                
                if (summary) {
                    const people = taskPeople.value || 0;
                    const recharge = rechargeAmount.value ? parseFloat(rechargeAmount.value).toFixed(2) : '0.00';
                    const bonus = taskAmount.value ? parseFloat(taskAmount.value).toFixed(2) : '0.00';
                    
                    summary.innerHTML = `<strong>Summary:</strong> Invite ${people} friends who recharge ₹${recharge}+ each → Get ₹${bonus} bonus`;
                }
            }
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                if (alert.classList.contains('alert-dismissible')) {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }
            });
        }, 50000);
    </script>
</body>
</html>