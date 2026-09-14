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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $level1 = $_POST['level1'];
    $level2 = $_POST['level2'];
    $level3 = $_POST['level3'];
    $level4 = $_POST['level4'];
    $level5 = $_POST['level5'];
    $level6 = $_POST['level6'];

    $updateQuery = "UPDATE web_commission SET 
        level1 = ?, 
        level2 = ?, 
        level3 = ?, 
        level4 = ?, 
        level5 = ?, 
        level6 = ? 
        WHERE id = 1";

    $stmt = mysqli_prepare($conn, $updateQuery);
    mysqli_stmt_bind_param($stmt, "dddddd", $level1, $level2, $level3, $level4, $level5, $level6);
    
    if (mysqli_stmt_execute($stmt)) {
        $msg = "✅ Commission levels updated successfully!";
        $message_type = "success";
    } else {
        $msg = "❌ Error updating commission levels: " . mysqli_error($conn);
        $message_type = "error";
    }
}

// Fetch current settings
$query = "SELECT * FROM web_commission LIMIT 1";
$result = mysqli_query($conn, $query);
$settings = mysqli_fetch_assoc($result);

// Set default values if no settings found
if (!$settings) {
    $settings = [
        'level1' => 0,
        'level2' => 0,
        'level3' => 0,
        'level4' => 0,
        'level5' => 0,
        'level6' => 0
    ];
}
?>

<!doctype html>
<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr"
    data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template" data-style="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Commission Settings</title>
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
        .commission-card {
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            transition: all 0.3s ease;
        }
        .commission-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        .commission-level {
            font-size: 14px;
            opacity: 0.9;
        }
        .commission-value {
            font-size: 24px;
            font-weight: bold;
            margin: 10px 0;
        }
        .level-badge {
            background: rgba(255,255,255,0.2);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .input-group-percent .input-group-text {
            /*background: #f8f9fa;*/
            border: 1px solid #ced4da;
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
                        <?php if (isset($msg)): ?>
                            <div class="alert alert-<?= $message_type === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
                                <?= htmlspecialchars($msg) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <div class="row">
                            <!-- Commission Settings Form -->
                            <div class="col-md-8">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h4 class="card-title mb-0">Commission Levels Settings</h4>
                                        <p class="text-muted mb-0">Set commission percentages for different levels</p>
                                    </div>
                                    <div class="card-body">
                                        <form action="" method="post">
                                            <div class="row">
                                                <!-- Level 1 -->
                                                <div class="col-md-6 mb-4">
                                                    <div class="commission-card">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <span class="commission-level">Level 1 Commission</span>
                                                            <span class="level-badge">Direct Referral</span>
                                                        </div>
                                                        <div class="commission-value">
                                                            <div class="input-group input-group-percent">
                                                                <input type="number" class="form-control" name="level1" 
                                                                       value="<?= htmlspecialchars($settings['level1']) ?>" 
                                                                       step="0.01" min="0" max="100" required>
                                                                <span class="input-group-text">%</span>
                                                            </div>
                                                        </div>
                                                        <small>Commission from direct referrals</small>
                                                    </div>
                                                </div>

                                                <!-- Level 2 -->
                                                <div class="col-md-6 mb-4">
                                                    <div class="commission-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <span class="commission-level">Level 2 Commission</span>
                                                            <span class="level-badge">Level 2 Referral</span>
                                                        </div>
                                                        <div class="commission-value">
                                                            <div class="input-group input-group-percent">
                                                                <input type="number" class="form-control" name="level2" 
                                                                       value="<?= htmlspecialchars($settings['level2']) ?>" 
                                                                       step="0.01" min="0" max="100" required>
                                                                <span class="input-group-text">%</span>
                                                            </div>
                                                        </div>
                                                        <small>Commission from level 2 referrals</small>
                                                    </div>
                                                </div>

                                                <!-- Level 3 -->
                                                <div class="col-md-6 mb-4">
                                                    <div class="commission-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <span class="commission-level">Level 3 Commission</span>
                                                            <span class="level-badge">Level 3 Referral</span>
                                                        </div>
                                                        <div class="commission-value">
                                                            <div class="input-group input-group-percent">
                                                                <input type="number" class="form-control" name="level3" 
                                                                       value="<?= htmlspecialchars($settings['level3']) ?>" 
                                                                       step="0.01" min="0" max="100" required>
                                                                <span class="input-group-text">%</span>
                                                            </div>
                                                        </div>
                                                        <small>Commission from level 3 referrals</small>
                                                    </div>
                                                </div>

                                                <!-- Level 4 -->
                                                <div class="col-md-6 mb-4">
                                                    <div class="commission-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <span class="commission-level">Level 4 Commission</span>
                                                            <span class="level-badge">Level 4 Referral</span>
                                                        </div>
                                                        <div class="commission-value">
                                                            <div class="input-group input-group-percent">
                                                                <input type="number" class="form-control" name="level4" 
                                                                       value="<?= htmlspecialchars($settings['level4']) ?>" 
                                                                       step="0.01" min="0" max="100" required>
                                                                <span class="input-group-text">%</span>
                                                            </div>
                                                        </div>
                                                        <small>Commission from level 4 referrals</small>
                                                    </div>
                                                </div>

                                                <!-- Level 5 -->
                                                <div class="col-md-6 mb-4">
                                                    <div class="commission-card" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <span class="commission-level">Level 5 Commission</span>
                                                            <span class="level-badge">Level 5 Referral</span>
                                                        </div>
                                                        <div class="commission-value">
                                                            <div class="input-group input-group-percent">
                                                                <input type="number" class="form-control" name="level5" 
                                                                       value="<?= htmlspecialchars($settings['level5']) ?>" 
                                                                       step="0.01" min="0" max="100" required>
                                                                <span class="input-group-text">%</span>
                                                            </div>
                                                        </div>
                                                        <small>Commission from level 5 referrals</small>
                                                    </div>
                                                </div>

                                                <!-- Level 6 -->
                                                <div class="col-md-6 mb-4">
                                                    <div class="commission-card" style="background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); color: #333;">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <span class="commission-level">Level 6 Commission</span>
                                                            <span class="level-badge" style="background: rgba(0,0,0,0.1);">Level 6 Referral</span>
                                                        </div>
                                                        <div class="commission-value">
                                                            <div class="input-group input-group-percent">
                                                                <input type="number" class="form-control" name="level6" 
                                                                       value="<?= htmlspecialchars($settings['level6']) ?>" 
                                                                       step="0.01" min="0" max="100" required>
                                                                <span class="input-group-text">%</span>
                                                            </div>
                                                        </div>
                                                        <small style="color: #666;">Commission from level 6 referrals</small>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Submit Button -->
                                            <div class="row mt-4">
                                                <div class="col-12">
                                                    <button type="submit" class="btn btn-primary btn-lg w-100">
                                                        <i class="ri-save-line ri-16px me-2"></i>Update Commission Settings
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Statistics & Info -->
                            <div class="col-md-4">
                                <!-- Total Commission -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Commission Summary</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="stats-card mb-3">
                                            <div class="stats-value">
                                                <?= number_format($settings['level1'] + $settings['level2'] + $settings['level3'] + $settings['level4'] + $settings['level5'] + $settings['level6'], 2) ?>%
                                            </div>
                                            <div class="stats-label">Total Commission Percentage</div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-6 mb-3">
                                                <div class="stats-card">
                                                    <div class="stats-value" style="color: #667eea;">
                                                        <?= number_format($settings['level1'], 2) ?>%
                                                    </div>
                                                    <div class="stats-label">Level 1</div>
                                                </div>
                                            </div>
                                            <div class="col-6 mb-3">
                                                <div class="stats-card">
                                                    <div class="stats-value" style="color: #f5576c;">
                                                        <?= number_format($settings['level2'], 2) ?>%
                                                    </div>
                                                    <div class="stats-label">Level 2</div>
                                                </div>
                                            </div>
                                            <div class="col-6 mb-3">
                                                <div class="stats-card">
                                                    <div class="stats-value" style="color: #4facfe;">
                                                        <?= number_format($settings['level3'], 2) ?>%
                                                    </div>
                                                    <div class="stats-label">Level 3</div>
                                                </div>
                                            </div>
                                            <div class="col-6 mb-3">
                                                <div class="stats-card">
                                                    <div class="stats-value" style="color: #43e97b;">
                                                        <?= number_format($settings['level4'], 2) ?>%
                                                    </div>
                                                    <div class="stats-label">Level 4</div>
                                                </div>
                                            </div>
                                            <div class="col-6 mb-3">
                                                <div class="stats-card">
                                                    <div class="stats-value" style="color: #fa709a;">
                                                        <?= number_format($settings['level5'], 2) ?>%
                                                    </div>
                                                    <div class="stats-label">Level 5</div>
                                                </div>
                                            </div>
                                            <div class="col-6 mb-3">
                                                <div class="stats-card">
                                                    <div class="stats-value" style="color: #a8edea;">
                                                        <?= number_format($settings['level6'], 2) ?>%
                                                    </div>
                                                    <div class="stats-label">Level 6</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Commission Info -->
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Commission Information</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="alert alert-info">
                                            <h6 class="alert-heading">How Commission Works:</h6>
                                            <ul class="mb-0 ps-3">
                                                <li><strong>Level 1:</strong> Direct referrals</li>
                                                <li><strong>Level 2:</strong> Referrals of your referrals</li>
                                                <li><strong>Level 3-6:</strong> Extended network levels</li>
                                            </ul>
                                        </div>
                                        <div class="alert alert-warning">
                                            <h6 class="alert-heading">Important Notes:</h6>
                                            <ul class="mb-0 ps-3">
                                                <li>Commission is calculated as percentage of referral's activity</li>
                                                <li>Values should be between 0% and 100%</li>
                                                <li>Higher levels typically have lower percentages</li>
                                            </ul>
                                        </div>
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
        // Real-time total calculation
        function updateTotal() {
            let total = 0;
            const inputs = document.querySelectorAll('input[type="number"]');
            
            inputs.forEach(input => {
                const value = parseFloat(input.value) || 0;
                total += value;
            });
            
            document.querySelector('.stats-value').textContent = total.toFixed(2) + '%';
        }

        // Attach event listeners to all commission inputs
        document.querySelectorAll('input[type="number"]').forEach(input => {
            input.addEventListener('input', updateTotal);
            input.addEventListener('change', updateTotal);
        });

        // Input validation
        document.querySelectorAll('input[type="number"]').forEach(input => {
            input.addEventListener('blur', function() {
                let value = parseFloat(this.value);
                if (isNaN(value)) {
                    this.value = 0;
                } else if (value < 0) {
                    this.value = 0;
                } else if (value > 100) {
                    this.value = 100;
                }
                updateTotal();
            });
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 50000);
    </script>
</body>
</html>