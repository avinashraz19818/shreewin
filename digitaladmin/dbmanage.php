<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['unohs'])) {
    header("location: api/login.php?msg=unauthorized");
    exit;
}

include("api/conn.php");

// Tables list
$tables = [
    "bajikattuttate",
    "bajikattuttate_drei",
    "bajikattuttate_funf",
    "bajikattuttate_zehn",
    "bajikattuttate_kemuru",
    "bajikattuttate_kemuru_drei",
    "bajikattuttate_kemuru_funf",
    "bajikattuttate_kemuru_zehn",
    "bajikattuttate_aidudi",
    "bajikattuttate_aidudi_drei",
    "bajikattuttate_aidudi_funf",
    "bajikattuttate_aidudi_zehn",
    "thevani",
    "hintegedukolli",
    "shonu_subjects",
    "shonu_kaichila"
];

$message = "";

// Clear Table
if (isset($_POST['action']) && $_POST['action'] === "clear" && isset($_POST['table_name'])) {
    $selected_table = $_POST['table_name'];

    // Special case: Clear Pending/Failed recharge in thevani
    if ($selected_table === "thevani_pending_failed") {
        $actual_table = "thevani";

        // Backup before deleting
        $backup_table = "backup_" . $actual_table;
        $conn->query("DROP TABLE IF EXISTS `$backup_table`");
        $conn->query("CREATE TABLE `$backup_table` LIKE `$actual_table`");
        $conn->query("INSERT INTO `$backup_table` SELECT * FROM `$actual_table`");

        // Delete where sthiti is 0 or 2
        if ($conn->query("DELETE FROM `$actual_table` WHERE sthiti IN (0, 2)") === TRUE) {
            $message = "✅ Deleted all Pending/Failed recharge records from <b>$actual_table</b>. Backup created.";
        } else {
            $message = "❌ Error deleting Pending/Failed: " . $conn->error;
        }
    }

    // Special case: Clear Pending/Failed/Cancelled in hintegedukolli
    elseif ($selected_table === "hintegedukolli_pending_failed") {
        $actual_table = "hintegedukolli";

        // Backup before deleting
        $backup_table = "backup_" . $actual_table;
        $conn->query("DROP TABLE IF EXISTS `$backup_table`");
        $conn->query("CREATE TABLE `$backup_table` LIKE `$actual_table`");
        $conn->query("INSERT INTO `$backup_table` SELECT * FROM `$actual_table`");

        // Delete where sthiti is 0, 2, or 3
        if ($conn->query("DELETE FROM `$actual_table` WHERE sthiti IN (0, 2, 3)") === TRUE) {
            $message = "✅ Deleted all Pending/Failed/Cancelled records from <b>$actual_table</b>. Backup created.";
        } else {
            $message = "❌ Error deleting Pending/Failed/Cancelled: " . $conn->error;
        }
    }

    elseif (in_array($selected_table, $tables)) {
        // Backup table before clearing
        $backup_table = "backup_" . $selected_table;
        $conn->query("DROP TABLE IF EXISTS `$backup_table`");
        $conn->query("CREATE TABLE `$backup_table` LIKE `$selected_table`");
        $conn->query("INSERT INTO `$backup_table` SELECT * FROM `$selected_table`");

        if ($selected_table === "shonu_subjects") {
            // Auto detect the first ID to keep in shonu_subjects
            $id_to_keep = null;
            $res = $conn->query("SELECT id FROM `$selected_table` ORDER BY id ASC LIMIT 1");
            if ($res && $row = $res->fetch_assoc()) {
                $id_to_keep = $row['id'];
            }
        
            if ($id_to_keep !== null) {
                $sql = "DELETE FROM `$selected_table` WHERE id != ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $id_to_keep);
                if ($stmt->execute()) {
                    $message = "✅ Cleared all except ID $id_to_keep from <b>$selected_table</b>. Backup created.";
                } else {
                    $message = "❌ Error clearing: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $message = "⚠️ No data in <b>$selected_table</b>, nothing to keep.";
            }
        
        } elseif ($selected_table === "shonu_kaichila") {
            // Get balakedara from shonu_subjects
            $balakedara_to_keep = null;
            $res = $conn->query("SELECT balakedara FROM shonu_subjects LIMIT 1");
            if ($res && $row = $res->fetch_assoc()) {
                $balakedara_to_keep = $row['balakedara'];
            }
        
            if ($balakedara_to_keep !== null) {
                // Keep only the first matching row
                $res = $conn->query("SELECT id FROM `$selected_table` WHERE balakedara = '{$conn->real_escape_string($balakedara_to_keep)}' ORDER BY id ASC LIMIT 1");
                if ($res && $row = $res->fetch_assoc()) {
                    $id_to_keep = $row['id'];
                    $sql = "DELETE FROM `$selected_table` WHERE id != ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $id_to_keep);
                    if ($stmt->execute()) {
                        $message = "✅ Cleared all from <b>$selected_table</b> except ID $id_to_keep (matching balakedara from shonu_subjects). Backup created.";
                    } else {
                        $message = "❌ Error clearing: " . $stmt->error;
                    }
                    $stmt->close();
                } else {
                    // No matching balakedara found, clear all
                    if ($conn->query("TRUNCATE TABLE `$selected_table`") === TRUE) {
                        $message = "✅ Cleared all from <b>$selected_table</b> (no matching balakedara found). Backup created.";
                    } else {
                        $message = "❌ Error clearing: " . $conn->error;
                    }
                }
            } else {
                // No balakedara found in shonu_subjects, clear all
                if ($conn->query("TRUNCATE TABLE `$selected_table`") === TRUE) {
                    $message = "✅ Cleared all from <b>$selected_table</b> (shonu_subjects empty). Backup created.";
                } else {
                    $message = "❌ Error clearing: " . $conn->error;
                }
            }
        
        } else {
            // Normal truncate for other tables
            if ($conn->query("TRUNCATE TABLE `$selected_table`") === TRUE) {
                $message = "✅ Table <b>$selected_table</b> cleared. Backup created.";
            } else {
                $message = "❌ Error clearing: " . $conn->error;
            }
        }
    } else {
        $message = "❌ Invalid table!";
    }
}

// Restore Table
if (isset($_POST['action']) && $_POST['action'] === "restore" && isset($_POST['table_name'])) {
    $selected_table = $_POST['table_name'];

    // If restoring Pending/Failed, map to real table name
    if ($selected_table === "thevani_pending_failed") {
        $selected_table = "thevani";
    } elseif ($selected_table === "hintegedukolli_pending_failed") {
        $selected_table = "hintegedukolli";
    }

    $backup_table = "backup_" . $selected_table;

    if (in_array($selected_table, $tables)) {
        $check_backup = $conn->query("SHOW TABLES LIKE '$backup_table'");
        if ($check_backup && $check_backup->num_rows > 0) {
            $conn->query("TRUNCATE TABLE `$selected_table`");
            if ($conn->query("INSERT INTO `$selected_table` SELECT * FROM `$backup_table`")) {
                $message = "✅ Table <b>$selected_table</b> restored from backup.";
            } else {
                $message = "❌ Restore failed: " . $conn->error;
            }
        } else {
            $message = "⚠️ No backup found for <b>$selected_table</b>.";
        }
    } else {
        $message = "❌ Invalid table!";
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

    <title>DB Management</title>

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
    <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->
    <!--? Template customizer: To hide customizer set displayCustomizer value false in config.js.  -->
    <script src="assets/vendor/js/template-customizer.js"></script>
    <!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->
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
                    <div class="card p-4">
                        <?php if (isset($_SESSION['msg'])): ?>
                            <div class="alert alert-success"><?= $_SESSION['msg']; unset($_SESSION['msg']); ?></div>
                        <?php endif; ?>
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
                        <?php endif; ?>
                         <div class="card mt-4">
                            <div class="card-body">
                                <h5 class="card-title mb-3">Clear / Restore Database Table</h5>
                                <?= $message ?>
                        
                                <!-- Existing Clear/Restore Table Form -->
                                <form method="POST" class="row g-3">
                                    <div class="col-md-6">
                                        <select name="table_name" class="form-select" required>
                                            <option value="">-- Select Table --</option>
                                            <?php foreach ($tables as $table): ?>
                                                <option value="<?= $table ?>"><?= $table ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <button type="submit" name="action" value="clear" class="btn btn-danger w-100"
                                                onclick="return confirm('Are you sure you want to clear this table? Backup will be created?')">
                                            Clear Table
                                        </button>
                                    </div>
                                    <div class="col-md-3">
                                        <button type="submit" name="action" value="restore" class="btn btn-success w-100"
                                                onclick="return confirm('Are you sure you want to restore this table from backup?')" style="color: #000;">
                                            Restore Table
                                        </button>
                                    </div>
                                </form>
                                
                                <!-- New Section for Pending/Failed Recharge -->
                                <div class="card mt-4">
                                    <div class="card-body">
                                        <h5 class="card-title mb-3">Clear Pending/Failed Recharge</h5>
                                        <form method="POST" class="row g-3">
                                            <input type="hidden" name="table_name" value="thevani_pending_failed">
                                            <div class="col-md-12">
                                                <button type="submit" name="action" value="clear" class="btn btn-warning w-100"
                                                        onclick="return confirm('Are you sure you want to delete all Pending/Failed recharge records? Backup will be created.')" style="color: #000;">
                                                    Clear Pending/Failed Recharge
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                
                                <!-- New Section for Pending/Failed Withdrawal -->
                                <div class="card mt-4">
                                    <div class="card-body">
                                        <h5 class="card-title mb-3">Clear Pending/Failed Withdrawal</h5>
                                        <form method="POST" class="row g-3">
                                            <input type="hidden" name="table_name" value="hintegedukolli_pending_failed">
                                            <div class="col-md-12">
                                                <button type="submit" name="action" value="clear" class="btn btn-warning w-100"
                                                        onclick="return confirm('Are you sure you want to delete all Pending/Failed withdrawal records? Backup will be created.')" style="color: #000;">
                                                    Clear Pending/Failed Withdrawal
                                                </button>
                                            </div>
                                        </form>
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
	if ( window.history.replaceState ) {
        window.history.replaceState( null, null, window.location.href );
    }
</script>

</body>

</html>