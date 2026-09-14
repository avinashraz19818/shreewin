<?php
session_start();
if (empty($_SESSION['unohs'])) {
    header("location: api/login.php?msg=unauthorized");
    exit;
}

include("api/conn.php"); // update if path is different

$alert = "";

// Add UPI
if (isset($_POST['newupi'])) {
    $upiid = trim($_POST['newupi']);
    
    if (empty($upiid)) {
        $alert = "Please enter UPI ID";
    } else {
        $upiid = mysqli_real_escape_string($conn, $upiid);
        $chk = mysqli_query($conn, "SELECT * FROM autoupi WHERE maulya = '$upiid'");
        
        if (mysqli_num_rows($chk) > 0) {
            $alert = "UPI ID already added.";
        } else {
            $sql_q = "INSERT INTO autoupi (maulya, sthiti) VALUES ('$upiid', '0')";
            $insert = mysqli_query($conn, $sql_q);
            
            if ($insert) {
                $alert = "UPI ID Added Successfully!";
            } else {
                $alert = "UPI ID Add Failed: " . mysqli_error($conn);
            }
        }
    }
}

// Set selected UPIs active
if (isset($_POST['upiid']) && is_array($_POST['upiid'])) {
    // First reset all to inactive
    mysqli_query($conn, "UPDATE autoupi SET sthiti='0'");
    
    // Set selected ones to active
    foreach ($_POST['upiid'] as $upi_maulya) {
        $safe_upi = mysqli_real_escape_string($conn, $upi_maulya);
        mysqli_query($conn, "UPDATE autoupi SET sthiti='1' WHERE maulya = '$safe_upi'");
    }
    $alert = "Selected UPI IDs are now active!";
}

// Delete UPI
if (isset($_POST['delete_upi'])) {
    $del_id = intval($_POST['delete_upi']);
    $delete = mysqli_query($conn, "DELETE FROM autoupi WHERE shonu = '$del_id'");
    $alert = $delete ? "UPI ID deleted successfully!" : "Failed to delete UPI ID";
}
?>

<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr"
    data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template" data-style="light">

<head>
<meta charset="utf-8" />
<meta name="viewport"
    content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    
<title>Add Auto UPI</title>

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
<style>
        .upi-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            margin-bottom: 8px;
        }
        .upi-actions button {
            margin-left: 10px;
        }
    </style>
</head>

<body>
    <div class="layout-wrapper layout-content-navbar">
    <div class="layout-container">
        <?php include("layout-menu.php"); ?>
        <div class="layout-page">
            <?php include("nav.php"); ?>
            <div class="content-wrapper">
                <div class="container-xxl flex-grow-1 container-p-y">

                    <h4 class="mb-4">Manage UPI IDs</h4>

                    <?php if (!empty($alert)): ?>
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <?= $alert ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header"><strong>Add New UPI</strong></div>
    <div class="card-body">
        <form method="post" action="">
            <div class="mb-3">
                <label for="new-upi-id" class="form-label">UPI ID</label>
                <input type="text" class="form-control" name="newupi" id="new-upi-id" placeholder="Enter UPI ID" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Add UPI</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><strong>Available UPI IDs</strong></div>
    <div class="card-body">
        <form method="post" action="">
            <?php
            $upis = mysqli_query($conn, "SELECT * FROM autoupi ORDER BY shonu DESC");
            while ($row = mysqli_fetch_assoc($upis)) {
                $shonu = $row['shonu'];
                $maulya = htmlspecialchars($row['maulya']);
                $checked = $row['sthiti'] == '1' ? 'checked' : '';
                ?>
                <div class="upi-row">
                    <div>
                        <input type="checkbox" class="form-check-input me-2" name="upiid[]" value="<?= $maulya ?>" <?= $checked ?> id="upi_<?= $shonu ?>">
                        <label for="upi_<?= $shonu ?>"><?= $maulya ?></label>
                    </div>
                    <div class="upi-actions">
                        <button type="submit" name="delete_upi" value="<?= $shonu ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this UPI ID?');">
                            Delete
                        </button>
                    </div>
                </div>
            <?php } ?>
            <button type="submit" class="btn btn-success w-100 mt-3">Set Selected as Active</button>
        </form>
    </div>
</div>
</div>
<?php include("footer.php"); ?>
</div>
</div>
</div>
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

<!-- Page JS -->
<script src="assets/js/app-user-list.js"></script>
</body>

</html> src="assets/vendor/libs/@form-validation/bootstrap5.js"></script>
<script src="assets/vendor/libs/@form-validation/auto-focus.js"></script>
<script src="assets/vendor/libs/cleavejs/cleave.js"></script>
<script src="assets/vendor/libs/cleavejs/cleave-phone.js"></script>

<!-- Main JS -->
<script src="assets/js/main.js"></script>

<!-- Page JS -->
<script src="assets/js/app-user-list.js"></script>
</body>

</html>