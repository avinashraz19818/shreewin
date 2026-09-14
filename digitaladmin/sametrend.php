<?php
session_start();
if (empty($_SESSION['unohs'])) {
    header("location: api/login.php?msg=unauthorized");
}
?>
<?php
include("api/conn.php");

$msg = "";

// Same-trend is an installation invariant. Repair old/off rows on every admin
// visit and add Moto Racing support for databases created by older builds.
$motorColumn = mysqli_query($conn, "SHOW COLUMNS FROM same_trend_manager LIKE 'motoracing'");
if ($motorColumn && mysqli_num_rows($motorColumn) === 0) {
    mysqli_query($conn, "ALTER TABLE same_trend_manager ADD COLUMN motoracing TINYINT(1) NOT NULL DEFAULT 1");
}
mysqli_query($conn, "UPDATE sametrend SET id=1 WHERE id=0");
$statusColumn = mysqli_query($conn, "SHOW COLUMNS FROM sametrend LIKE 'status'");
if ($statusColumn && mysqli_num_rows($statusColumn) > 0) {
    mysqli_query($conn, "UPDATE sametrend SET status='active'");
}
mysqli_query($conn, "UPDATE same_trend_manager SET wingo=1,k3=1,`5d`=1,trx_wingo=1,motoracing=1");

// Process Same Trends Setting
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['copy_trends'])) {
    $sql = "UPDATE sametrend SET id = 1 WHERE id = 0";

    if (mysqli_query($conn, $sql)) {
        $msg = "Setting updated successfully.";
    } else {
        $msg = "Error updating setting: " . mysqli_error($conn);
    }
}

// Process API Configuration
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['domain'])) {
    $domain = mysqli_real_escape_string($conn, $_POST['domain']);
    $server_ip = mysqli_real_escape_string($conn, $_POST['server_ip']);
    $api_key = mysqli_real_escape_string($conn, $_POST['api_key']);

    $sql = "INSERT INTO same_trend_manager (domain, server_ip, api_key,wingo,k3,`5d`,trx_wingo,motoracing) 
                VALUES ('$domain', '$server_ip', '$api_key',1,1,1,1,1) 
                ON DUPLICATE KEY UPDATE 
                server_ip = '$server_ip', 
                api_key = '$api_key', 
                wingo=1,k3=1,`5d`=1,trx_wingo=1,motoracing=1,
                updated_at = CURRENT_TIMESTAMP";

    if (mysqli_query($conn, $sql)) {
        $msg = "API configuration saved successfully.";
    } else {
        $msg = "Error saving API configuration: " . mysqli_error($conn);
    }
}

// Toggle Platform Status
if (isset($_GET['toggle'])) {
    $msg = "Same Trend is permanently active for every game.";
}

// Retrieve current value for the checkbox
$sql = "SELECT id FROM sametrend WHERE id = 0";
$result = mysqli_query($conn, $sql);
$current = (mysqli_num_rows($result) > 0) ? 0 : 1;

// Fetch API data
$api_data = [];
$api_result = mysqli_query($conn, "SELECT * FROM same_trend_manager ORDER BY id DESC LIMIT 1");
if ($api_result && mysqli_num_rows($api_result) > 0) {
    $api_data = mysqli_fetch_assoc($api_result);
    foreach (array('wingo','k3','5d','trx_wingo','motoracing') as $lockedPlatform) {
        $api_data[$lockedPlatform] = 1;
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

    <title>Same Trend</title>

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
    <style>
        .circle {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            font-size: 1.7em;
            font-weight: bold;
        }

        .red {
            color: rgba(231, 85, 79, 1);
            background-color: rgba(243, 223, 224, 1);
            border: 10px solid rgba(231, 85, 79, 1);
        }

        .green {
            color: green;
            background-color: rgba(226, 238, 230, 1);
            border: 10px solid green;
        }

        .border-violet {
            border: 10px solid purple;
        }

        .api-card {
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            transition: all 0.3s ease;
            cursor: default;
        }

        .api-card:hover {
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .api-card.active {
            border-color: #28a745;
            background-color: #f8fff9;
        }

        .api-card.inactive {
            border-color: #dc3545;
            /*background-color: #fff8f8;*/
        }

        .status-toggle {
            width: 60px;
            height: 30px;
            background: #dc3545;
            border-radius: 15px;
            position: relative;
            cursor: not-allowed;
            transition: all 0.3s ease;
            margin: 0 auto;
        }

        .status-toggle.active {
            background: #28a745;
        }

        .status-toggle::after {
            content: '';
            position: absolute;
            width: 26px;
            height: 26px;
            background: white;
            border-radius: 50%;
            top: 2px;
            left: 2px;
            transition: all 0.3s ease;
        }

        .status-toggle.active::after {
            left: 32px;
        }

        .contact-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .contact-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
            color: white;
        }
    </style>
    <!-- Page CSS -->

    <!-- Helpers -->
    <script src="assets/vendor/js/helpers.js"></script>
    <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->
    <!--? Template customizer: To hide customizer set displayCustomizer value false in config.js.  -->
    <script src="assets/vendor/js/template-customizer.js"></script>
    <!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->
    <script src="assets/js/config.js"></script>
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
                    <!-- Content -->
                    <div class="container-xxl flex-grow-1 container-p-y">

                        <!-- Same Trend API Manager Section -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h4 class="card-title mb-0">AK Softs Same Trend API Manager</h4>
                                        <p class="text-muted mb-0">Manage your Same Trend APIs for different platforms</p>
                                    </div>
                                    <div class="card-body">
                                        <!-- Contact Information -->
                                        <div class="alert alert-info d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong>Need Wingo, K3, 5D and Trx Wingo API?</strong>
                                                <p class="mb-0">Contact us for API integration and support</p>
                                            </div>
                                            <a href="https://t.me/zayro_o" target="_blank" class="contact-btn">
                                                <i class="ri-telegram-line"></i>
                                                Contact Now
                                            </a>
                                        </div>

                                        <!-- API Configuration Form -->
                                        <form method="post" action="">
                                            <div class="row">
                                                <div class="col-md-4 mb-3">
                                                    <label class="form-label">Your Domain</label>
                                                    <input type="text" class="form-control" name="domain"
                                                        placeholder="Enter your domain"
                                                        value="<?= htmlspecialchars($api_data['domain'] ?? '') ?>" required>
                                                </div>
                                                <div class="col-md-3 mb-3">
                                                    <label class="form-label">Your Server IP</label>
                                                    <input type="text" class="form-control" name="server_ip"
                                                        placeholder="Enter server IP"
                                                        value="<?= htmlspecialchars($api_data['server_ip'] ?? '') ?>" required>
                                                </div>
                                                <div class="col-md-3 mb-3">
                                                    <label class="form-label">API Key</label>
                                                    <input type="text" class="form-control" name="api_key"
                                                        placeholder="Enter API key"
                                                        value="<?= htmlspecialchars($api_data['api_key'] ?? '') ?>" required>
                                                </div>
                                                <div class="col-md-2 mb-3 d-flex align-items-end">
                                                    <button type="submit" class="btn btn-success w-100">
                                                        <i class="ri-check-line me-1"></i>
                                                        Active API
                                                    </button>
                                                </div>
                                            </div>
                                        </form>

                                        <!-- API Platforms Grid -->
                                        <div class="row mt-4">
                                            <?php if (!empty($api_data)): ?>
                                                <div class="col-md-3 mb-3">
                                                    <div class="api-card p-3 text-center active">
                                                        <div class="mb-2">
                                                            <i class="ri-gamepad-line display-4 text-primary"></i>
                                                        </div>
                                                        <h5>Wingo API</h5>
                                                        <p class="text-muted small">Wingo platform integration</p>
                                                        <div class="status-toggle active"
                                                            data-platform="wingo" data-id="<?= $api_data['id'] ?>"></div>
                                                        <small class="d-block mt-2 text-success">ACTIVE (LOCKED)</small>
                                                    </div>
                                                </div>
                                                <div class="col-md-3 mb-3">
                                                    <div class="api-card p-3 text-center active">
                                                        <div class="mb-2">
                                                            <i class="ri-codepen-line display-4 text-success"></i>
                                                        </div>
                                                        <h5>K3 API</h5>
                                                        <p class="text-muted small">K3 platform integration</p>
                                                        <div class="status-toggle active"
                                                            data-platform="k3" data-id="<?= $api_data['id'] ?>"></div>
                                                        <small class="d-block mt-2 text-success">ACTIVE (LOCKED)</small>
                                                    </div>
                                                </div>
                                                <div class="col-md-3 mb-3">
                                                    <div class="api-card p-3 text-center active">
                                                        <div class="mb-2">
                                                            <i class="ri-codepen-line display-4 text-success"></i>
                                                        </div>
                                                        <h5>5D API</h5>
                                                        <p class="text-muted small">5D platform integration</p>
                                                        <div class="status-toggle active"
                                                            data-platform="5d" data-id="<?= $api_data['id'] ?>"></div>
                                                        <small class="d-block mt-2 text-success">ACTIVE (LOCKED)</small>
                                                    </div>
                                                </div>
                                                <div class="col-md-3 mb-3">
                                                    <div class="api-card p-3 text-center active">
                                                        <div class="mb-2">
                                                            <i class="ri-exchange-dollar-line display-4 text-info"></i>
                                                        </div>
                                                        <h5>Trx Wingo API</h5>
                                                        <p class="text-muted small">TRX Wingo integration</p>
                                                        <div class="status-toggle active"
                                                            data-platform="trx_wingo" data-id="<?= $api_data['id'] ?>"></div>
                                                        <small class="d-block mt-2 text-success">ACTIVE (LOCKED)</small>
                                                    </div>
                                                </div>
                                                <div class="col-md-3 mb-3">
                                                    <div class="api-card p-3 text-center active">
                                                        <div class="mb-2">
                                                            <i class="ri-motorbike-line display-4 text-warning"></i>
                                                        </div>
                                                        <h5>Moto Racing API</h5>
                                                        <p class="text-muted small">Moto Racing integration</p>
                                                        <div class="status-toggle active" data-platform="motoracing"></div>
                                                        <small class="d-block mt-2 text-success">ACTIVE (LOCKED)</small>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <div class="col-12 text-center py-4">
                                                    <div class="text-muted">
                                                        <i class="ri-information-line display-4"></i>
                                                        <p class="mt-2">Please configure API settings first</p>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Original Same Trends Setting -->
                        <!--<div class="row g-6 mb-6">-->
                        <!--    <div class="col-sm-12 col-xl-3">-->
                        <!--        <div class="card shadow-sm border-0 rounded-3">-->
                        <!--            <div class="card-body">-->
                        <!--                <div class="d-flex justify-content-between align-items-center mb-3">-->
                        <!--                    <h4 class="mb-0">Update Same Trends Setting</h4>-->
                        <!--                </div>-->

                        <!--                <?php if ($msg != "") { ?>-->
                        <!--                    <div class="alert alert-info py-2 px-3 rounded-3">-->
                        <!--                        <?php echo $msg; ?>-->
                        <!--                    </div>-->
                        <!--                <?php } ?>-->

                        <!--                <form action="" method="post" id="gatewayForm" autocomplete="off">-->
                        <!--                    <div class="form-check form-switch mb-3">-->
                        <!--                        <input class="form-check-input" type="checkbox" id="copy_trends" name="copy_trends" value="1" -->
                        <!--                            <?php if ($current == 1) echo "checked"; ?>>-->
                        <!--                        <label class="form-check-label fw-semibold" for="copy_trends">-->
                        <!--                            Enable Same Trends-->
                        <!--                        </label>-->
                        <!--                    </div>-->

                        <!--                    <button type="submit" class="btn btn-primary w-100 rounded-pill">-->
                        <!--                        Save-->
                        <!--                    </button>-->
                        <!--                </form>-->
                        <!--            </div>-->
                        <!--        </div>-->
                        <!--    </div>-->
                        <!--</div>-->
                    </div>
                    <!-- / Content -->

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
    <!-- build:js assets/vendor/js/core.js -->
    <script src="assets/vendor/libs/jquery/jquery.js"></script>
    <script src="assets/vendor/libs/popper/popper.js"></script>
    <script src="assets/vendor/js/bootstrap.js"></script>
    <script src="assets/vendor/libs/node-waves/node-waves.js"></script>
    <script src="assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="assets/vendor/libs/hammer/hammer.js"></script>
    <script src="assets/vendor/libs/i18n/i18n.js"></script>
    <script src="assets/vendor/libs/typeahead-js/typeahead.js"></script>
    <script src="assets/vendor/js/menu.js"></script>

    <!-- endbuild -->

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
        function togglePlatform(platform, id) {
            return false;
        }

        // Auto reload after toggle
        <?php if (isset($_GET['toggle'])): ?>
            setTimeout(() => {
                window.location.href = window.location.pathname;
            }, 1000);
        <?php endif; ?>
    </script>

</body>

</html>
