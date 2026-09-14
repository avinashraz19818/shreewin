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

// Fetch existing records
$sql = "SELECT * FROM tbl_firstdepositreward ORDER BY rechargeAmount ASC";
$result = $conn->query($sql);

// Handle form submission for updates
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update'])) {
    foreach ($_POST['id'] as $key => $id) {
        $rewardAmount = $_POST['rewardAmount'][$key];
        $rechargeAmount = $_POST['rechargeAmount'][$key];
        $active = isset($_POST['active'][$key]) ? 1 : 0;
        
        $updateQuery = "UPDATE tbl_firstdepositreward SET rewardAmount = '$rewardAmount', rechargeAmount = '$rechargeAmount', active = '$active' WHERE id = '$id'";
        $conn->query($updateQuery);
    }
    header("Location: update_firstdepositbonus.php?success=1");
    exit();
}

// Handle add new record
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add'])) {
    $newRewardAmount = $_POST['new_rewardAmount'];
    $newRechargeAmount = $_POST['new_rechargeAmount'];
    $newActive = isset($_POST['new_active']) ? 1 : 0;
    
    $insertQuery = "INSERT INTO tbl_firstdepositreward (rewardAmount, rechargeAmount, active) VALUES ('$newRewardAmount', '$newRechargeAmount', '$newActive')";
    if ($conn->query($insertQuery)) {
        header("Location: update_firstdepositbonus.php?success=1");
        exit();
    } else {
        $error = "Error adding new record: " . $conn->error;
    }
}

// Handle delete record
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $deleteQuery = "DELETE FROM tbl_firstdepositreward WHERE id = '$id'";
    if ($conn->query($deleteQuery)) {
        header("Location: update_firstdepositbonus.php?success=1");
        exit();
    } else {
        $error = "Error deleting record: " . $conn->error;
    }
}

// Handle toggle active status
if (isset($_GET['toggle'])) {
    $id = intval($_GET['toggle']);
    $toggleQuery = "UPDATE tbl_firstdepositreward SET active = IF(active=1,0,1) WHERE id = '$id'";
    if ($conn->query($toggleQuery)) {
        header("Location: update_firstdepositbonus.php?success=1");
        exit();
    } else {
        $error = "Error updating status: " . $conn->error;
    }
}

// Success message
if (isset($_GET['success'])) {
    $message = "✅ Settings updated successfully!";
    $message_type = "success";
}
?>

<!doctype html>
<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr"
    data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template" data-style="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>First Deposit Bonus Settings</title>
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
        .bonus-card.inactive {
            opacity: 0.6;
            background: #f8f9fa;
        }
        .bonus-card:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .bonus-ratio {
            font-size: 12px;
            color: #6c757d;
            margin-top: 5px;
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
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 24px;
        }
        .slider:before {
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
        input:checked + .slider {
            background-color: #2196F3;
        }
        input:checked + .slider:before {
            transform: translateX(26px);
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

                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?= htmlspecialchars($error) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <div class="row">
                            <!-- Add New Bonus -->
                            <div class="col-md-4">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Add New Bonus</h5>
                                    </div>
                                    <div class="card-body">
                                        <form action="" method="post">
                                            <div class="mb-3">
                                                <label for="new_rechargeAmount" class="form-label">Recharge Amount (₹) *</label>
                                                <input type="number" class="form-control" id="new_rechargeAmount" name="new_rechargeAmount" 
                                                       step="0.01" min="0" required placeholder="Enter recharge amount">
                                            </div>

                                            <div class="mb-3">
                                                <label for="new_rewardAmount" class="form-label">Bonus Amount (₹) *</label>
                                                <input type="number" class="form-control" id="new_rewardAmount" name="new_rewardAmount" 
                                                       step="0.01" min="0" required placeholder="Enter bonus amount">
                                            </div>

                                            <div class="mb-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="new_active" name="new_active" checked>
                                                    <label class="form-check-label" for="new_active">
                                                        Active
                                                    </label>
                                                </div>
                                            </div>

                                            <button type="submit" name="add" class="btn btn-primary w-100">
                                                <i class="ri-add-line ri-16px me-2"></i>Add Bonus
                                            </button>
                                        </form>
                                    </div>
                                </div>

                                <!-- Statistics -->
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Bonus Statistics</h5>
                                    </div>
                                    <div class="card-body">
                                        <?php
                                        $total_bonuses = $result->num_rows;
                                        $active_bonuses = 0;
                                        $total_reward = 0;
                                        $result->data_seek(0); // Reset pointer
                                        
                                        while ($row = $result->fetch_assoc()) {
                                            if ($row['active'] == 1) {
                                                $active_bonuses++;
                                                $total_reward += $row['rewardAmount'];
                                            }
                                        }
                                        $result->data_seek(0); // Reset pointer again
                                        ?>
                                        <div class="stats-card mb-3">
                                            <div class="stats-value"><?= $total_bonuses ?></div>
                                            <div class="stats-label">Total Bonuses</div>
                                        </div>
                                        <div class="stats-card mb-3">
                                            <div class="stats-value" style="color: #28a745;"><?= $active_bonuses ?></div>
                                            <div class="stats-label">Active Bonuses</div>
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
                                        <h5 class="card-title mb-0">Information</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="alert alert-info">
                                            <h6 class="alert-heading">How it works:</h6>
                                            <ul class="mb-0 ps-3">
                                                <li>Users get bonus on their first deposit</li>
                                                <li>Bonus amount based on recharge amount</li>
                                                <li>Higher recharge = Higher bonus</li>
                                                <li>Inactive bonuses won't be shown to users</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Existing Bonuses -->
                            <div class="col-md-8">
                                <div class="card">
                                    <div class="card-header">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h5 class="card-title mb-0">First Deposit Bonuses</h5>
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-primary active" onclick="filterBonuses('all')">All</button>
                                                <button type="button" class="btn btn-outline-success" onclick="filterBonuses('active')">Active</button>
                                                <button type="button" class="btn btn-outline-secondary" onclick="filterBonuses('inactive')">Inactive</button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <form action="" method="post">
                                            <input type="hidden" name="update" value="1">
                                            
                                            <?php if ($result->num_rows > 0): ?>
                                                <div class="row" id="bonusesContainer">
                                                    <?php while ($row = $result->fetch_assoc()): ?>
                                                        <div class="col-md-6 mb-4 bonus-item" data-status="<?= $row['active'] ? 'active' : 'inactive' ?>">
                                                            <div class="bonus-card <?= $row['active'] ? '' : 'inactive' ?>">
                                                                <input type="hidden" name="id[]" value="<?= $row['id'] ?>">
                                                                
                                                                <!-- Bonus Header -->
                                                                <div class="d-flex justify-content-between align-items-start mb-3">
                                                                    <div>
                                                                        <h6 class="mb-1">Bonus #<?= $row['id'] ?></h6>
                                                                        <span class="badge bg-<?= $row['active'] ? 'success' : 'secondary' ?>">
                                                                            <?= $row['active'] ? 'Active' : 'Inactive' ?>
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
                                                                                   onclick="return confirm('Are you sure you want to delete this bonus?')">
                                                                                    <i class="ri-delete-bin-line me-2"></i>Delete
                                                                                </a>
                                                                            </li>
                                                                        </ul>
                                                                    </div>
                                                                </div>

                                                                <!-- Recharge Amount -->
                                                                <div class="mb-3">
                                                                    <label class="form-label small">Recharge Amount (₹)</label>
                                                                    <input type="number" class="form-control" name="rechargeAmount[]" 
                                                                           value="<?= htmlspecialchars($row['rechargeAmount']) ?>" 
                                                                           step="0.01" min="0" required>
                                                                </div>

                                                                <!-- Reward Amount -->
                                                                <div class="mb-3">
                                                                    <label class="form-label small">Bonus Amount (₹)</label>
                                                                    <input type="number" class="form-control" name="rewardAmount[]" 
                                                                           value="<?= htmlspecialchars($row['rewardAmount']) ?>" 
                                                                           step="0.01" min="0" required>
                                                                    <?php if ($row['rechargeAmount'] > 0): ?>
                                                                        <div class="bonus-ratio">
                                                                            Ratio: 1:<?= number_format($row['rewardAmount'] / $row['rechargeAmount'], 2) ?>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                </div>

                                                                <!-- Active Status -->
                                                                <div class="d-flex justify-content-between align-items-center">
                                                                    <div class="form-check form-switch">
                                                                        <input class="form-check-input" type="checkbox" name="active[<?= $row['id'] ?>]" 
                                                                               value="1" <?= $row['active'] ? 'checked' : '' ?>>
                                                                        <label class="form-check-label">Active</label>
                                                                    </div>
                                                                    <a href="?toggle=<?= $row['id'] ?>" 
                                                                       class="btn btn-sm btn-<?= $row['active'] ? 'success' : 'secondary' ?>">
                                                                        <?= $row['active'] ? 'Active' : 'Inactive' ?>
                                                                    </a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php endwhile; ?>
                                                </div>

                                                <!-- Update Button -->
                                                <div class="row mt-4">
                                                    <div class="col-12">
                                                        <button type="submit" class="btn btn-primary btn-lg w-100">
                                                            <i class="ri-save-line ri-16px me-2"></i>Update All Bonuses
                                                        </button>
                                                    </div>
                                                </div>

                                            <?php else: ?>
                                                <div class="text-center py-5">
                                                    <div class="avatar avatar-lg mb-3">
                                                        <span class="avatar-initial rounded bg-label-secondary">
                                                            <i class="ri-gift-line ri-24px"></i>
                                                        </span>
                                                    </div>
                                                    <h5 class="text-muted">No Bonuses Found</h5>
                                                    <p class="text-muted">Add your first bonus using the form on the left.</p>
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
        // Filter bonuses
        function filterBonuses(status) {
            const bonuses = document.querySelectorAll('.bonus-item');
            const filterButtons = document.querySelectorAll('.btn-group .btn');
            
            // Update active button
            filterButtons.forEach(btn => {
                btn.classList.remove('active');
                if (btn.textContent.toLowerCase() === status) {
                    btn.classList.add('active');
                }
            });
            
            // Show/hide bonuses based on filter
            bonuses.forEach(bonus => {
                if (status === 'all' || bonus.getAttribute('data-status') === status) {
                    bonus.style.display = 'block';
                } else {
                    bonus.style.display = 'none';
                }
            });
        }

        // Auto-calculate bonus ratio
        document.addEventListener('input', function(e) {
            if (e.target.name === 'rechargeAmount[]' || e.target.name === 'rewardAmount[]') {
                const card = e.target.closest('.bonus-card');
                const rechargeInput = card.querySelector('input[name="rechargeAmount[]"]');
                const rewardInput = card.querySelector('input[name="rewardAmount[]"]');
                const ratioDiv = card.querySelector('.bonus-ratio');
                
                const recharge = parseFloat(rechargeInput.value) || 0;
                const reward = parseFloat(rewardInput.value) || 0;
                
                if (recharge > 0 && ratioDiv) {
                    ratioDiv.textContent = `Ratio: 1:${(reward / recharge).toFixed(2)}`;
                }
            }
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>