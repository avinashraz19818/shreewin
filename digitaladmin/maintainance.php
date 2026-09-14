<?php
session_start();
if (empty($_SESSION['unohs'])) {
    header("location: api/login.php?msg=unauthorized");
}

include("api/conn.php");

// Fetch maintenance status and message
$query = "SELECT status, message FROM maintenance WHERE id = 1"; 
$result = mysqli_query($conn, $query);
$maintenanceStatus = 'inactive';
$maintenanceMessage = '';

if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    $maintenanceStatus = $row['status'];
    $maintenanceMessage = $row['message'];
}



// Handle maintenance mode toggle
if (isset($_POST['toggleMaintenance'])) {
    $newStatus = ($_POST['toggleMaintenance'] === 'active') ? 'inactive' : 'active';
    $updateMaintenance = "UPDATE maintenance SET status='$newStatus' WHERE id=1";
    mysqli_query($conn, $updateMaintenance);
    header("Refresh:0");
}
?>

<!doctype html>

<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr"
    data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template" data-style="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

    <title>Maintainance</title>

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
                        <div class="app-ecommerce ">
                            <!-- Add Product -->
                            <div
                                class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-6 row-gap-4">
                                <div class="d-flex flex-column justify-content-center">
                                    <h4 class="mb-1">Maintainance Mode</h4>
                                </div>
                            </div>

                            <div class="row">
                                <!-- First column-->
                                <div class="col-12 col-lg-8">
                                    <!-- Product Information -->
                                    <div class="card mb-6">
                                        <form class="card-body" acton="#" method="post" autocomplete="off">
                                            <div class="row mb-5 gx-5">
                                                <div class="col">
                                                    <input type="hidden" name="toggleMaintenance" value="<?php echo htmlspecialchars($maintenanceStatus); ?>">
                                                    <p class="mt-2">Current status: <strong><?php echo ucfirst($maintenanceStatus); ?></strong></p>
                                                    <button type="submit" class="btn btn-<?php echo ($maintenanceStatus === 'active') ? 'danger' : 'success'; ?> btn-lg w-100">
                                                        <i class="ri-reset-right-line ri-16px me-2"></i>
                                                        <?php echo ($maintenanceStatus === 'active') ? 'Deactivate Maintenance' : 'Activate Maintenance'; ?>
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
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