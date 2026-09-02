<?php
session_start();
if (empty($_SESSION['unohs'])) {
    header("location: api/login.php?msg=unauthorized");
    exit;
}
include("api/conn.php");

$alert = '';

// Add new USDT
if (isset($_POST['newupi'])) {
    $upiid = trim(mysqli_real_escape_string($conn, $_POST['newupi'])); // Trim & escape input, preserve case

    // Check if already exists (case-sensitive)
    $chkExist = mysqli_query($conn, "SELECT * FROM deyyamrici WHERE BINARY maulya = '$upiid'");
    if (mysqli_num_rows($chkExist) == 0) {
        $sql_q = "INSERT INTO deyyamrici (maulya, sthiti) VALUES ('$upiid', '0')";
        $chk = mysqli_query($conn, $sql_q);
        $alert = $chk ? "USDT ID Added" : "USDT ID Add Failed";
    } else {
        $alert = "USDT ID already exists";
    }
}

// Delete USDT
if (isset($_POST['delete_usdt'])) {
    $del_id = intval($_POST['delete_usdt']);
    mysqli_query($conn, "DELETE FROM deyyamrici WHERE shonu = '$del_id'");
    $alert = "USDT ID Deleted";
}

// Set Active USDT
if (isset($_POST['upiid'])) {
    $selected_shonu = intval($_POST['upiid']);

    // Reset all to inactive
    mysqli_query($conn, "UPDATE deyyamrici SET sthiti = '0'");

    // Set selected to active
    mysqli_query($conn, "UPDATE deyyamrici SET sthiti = '1' WHERE shonu = '$selected_shonu'");

    // Fetch the selected value to display
    $getActive = mysqli_fetch_assoc(mysqli_query($conn, "SELECT maulya FROM deyyamrici WHERE shonu = '$selected_shonu'"));
    $alert = "USDT Active: " . htmlspecialchars($getActive['maulya']);
}
?>

<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr"
    data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template" data-style="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

    <title>add usdt</title>

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
        .usdt-row {
            border: 1px solid #ccc;
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .usdt-label {
            margin-left: 10px;
            font-weight: 500;
        }

        .usdt-actions form {
            display: inline;
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
                    <h4 class="mb-4">Manage USDT IDs</h4>

                    <?php if (!empty($alert)): ?>
                        <div class="alert alert-info alert-dismissible fade show" role="alert">
                            <?= $alert ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-8">
                            <!-- Add USDT -->
                            <div class="card mb-4">
                                <div class="card-header"><strong>Add New USDT</strong></div>
                                <div class="card-body">
                                    <form method="post" action="">
                                        <div class="form-floating form-floating-outline mb-4">
                                            <input type="text" class="form-control" name="newupi" id="newupi"
                                                   placeholder="Enter USDT ID" required>
                                            <label for="newupi">USDT ID</label>
                                        </div>
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="ri-add-line me-2"></i>Add USDT
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <!-- List USDTs -->
                            <div class="card">
                                <div class="card-header"><strong>Available USDT IDs</strong></div>
                                <div class="card-body">
                                    <form method="post" action="">
                                        <?php
                                        $res = mysqli_query($conn, "SELECT * FROM deyyamrici ORDER BY shonu DESC");
                                        while ($row = mysqli_fetch_assoc($res)) {
                                            $shonu = intval($row['shonu']);
                                            $maulya = htmlspecialchars($row['maulya']);
                                            $checked = $row['sthiti'] == '1' ? 'checked' : '';
                                            ?>
                                            <div class="usdt-row">
                                                <div class="d-flex align-items-center">
                                                    <input class="form-check-input" type="radio" name="upiid"
                                                           id="upi_<?php echo $shonu; ?>"
                                                           value="<?php echo $shonu; ?>" <?php echo $checked; ?>>
                                                    <label class="usdt-label" for="upi_<?php echo $shonu; ?>">
                                                        <?php echo $maulya; ?>
                                                    </label>
                                                </div>
                                                <div class="usdt-actions">
                                                    <button type="submit" name="delete_usdt" value="<?php echo $shonu; ?>"
                                                            class="btn btn-sm btn-danger"
                                                            onclick="return confirm('Delete this USDT ID?');">
                                                        <i class="ri-delete-bin-line"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        <?php } ?>
                                        <button type="submit" class="btn btn-success w-100 mt-3">
                                            <i class="ri-check-line me-2"></i>Set Selected as Active
                                        </button>
                                    </form>
                                </div>
                            </div>
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

</html>