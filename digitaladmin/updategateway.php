<?php
	session_start();
	if(empty($_SESSION['unohs'])){
		header("location: api/login.php?msg=unauthorized");
	}
?>
<?php
	include ("api/conn.php");		
	
	if(isset($_POST['upiid'])){
		$a_id = $_POST['upiid'];
		$sql_s = "SELECT * FROM tbl_pg WHERE value = '".$a_id."'";
		$run = mysqli_query($conn, $sql_s);
		//Set status to 0
		$sql_d = "SELECT * FROM tbl_pg WHERE status = '1'";		
		$run_d = mysqli_query($conn, $sql_d);
		$rund_f = mysqli_fetch_array($run_d);
		$ch_s0 = "UPDATE tbl_pg SET status='0' WHERE id='".$rund_f['id']."'";
		$exe_ch_s0 = mysqli_query($conn, $ch_s0);
		//Set status to 1
		$run_f = mysqli_fetch_array($run);
		$ch_s1 = "UPDATE tbl_pg SET status='1' WHERE id='".$run_f['id']."'";
		$exe_ch_s1 = mysqli_query($conn, $ch_s1);
	}
?>

<!doctype html>

<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr"
    data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template" data-style="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

    <title>Update Gateway</title>

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
    <link rel="stylesheet" href="assets/vendor/libs/quill/typography.css" />
    <link rel="stylesheet" href="assets/vendor/libs/quill/katex.css" />
    <link rel="stylesheet" href="assets/vendor/libs/quill/editor.css" />
    <link rel="stylesheet" href="assets/vendor/libs/select2/select2.css" />
    <link rel="stylesheet" href="assets/vendor/libs/dropzone/dropzone.css" />
    <link rel="stylesheet" href="assets/vendor/libs/flatpickr/flatpickr.css" />
    <link rel="stylesheet" href="assets/vendor/libs/tagify/tagify.css" />

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
                                    <h4 class="mb-1">Select Withdraw Gateway</h4>
                                </div>
                            </div>

                            <div class="row">
                                <!-- First column-->
                                <div class="col-12 col-lg-8">
                                    <div class="card mb-6">
                                        <form class="card-body" action="#" id="upisave" method="post" autocomplete="off">
                                            <div>
                                                <?php
                                                $sel_upi = "SELECT * FROM tbl_pg WHERE status='0' OR status='1'";
                                				$upi_r = mysqli_query($conn, $sel_upi);
                                                while ($row = mysqli_fetch_array($upi_r)) { ?>
                                                <div class="form-check mb-4">
                                                    <input class="form-check-input" type="radio" name="upiid"
                                                        id="seller" value="<?= $row['value']; ?>" <?php echo $row["status"]==1?"checked":""; ?> />
                                                    <label class="form-check-label d-flex flex-column gap-1"
                                                        for="<?= $row['value']; ?>">
                                                        <span class="h6 mb-0"><?= $row['value']; ?></span>
                                                    </label>
                                                </div>
                                                <?php } ?>
                                            </div>
                                            <div class="row mb-5 gx-5">
                                                <div class="col">
                                                    <button class="btn btn-primary btn-lg w-100">
                                                        <i class="ri-check-line ri-16px me-2"></i>Save
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
                <script src="assets/vendor/libs/quill/katex.js"></script>
                <script src="assets/vendor/libs/quill/quill.js"></script>
                <script src="assets/vendor/libs/select2/select2.js"></script>
                <script src="assets/vendor/libs/dropzone/dropzone.js"></script>
                <script src="assets/vendor/libs/jquery-repeater/jquery-repeater.js"></script>
                <script src="assets/vendor/libs/flatpickr/flatpickr.js"></script>
                <script src="assets/vendor/libs/tagify/tagify.js"></script>

                <!-- Main JS -->
                <script src="assets/js/main.js"></script>

                <!-- Page JS -->
                <script src="assets/js/app-ecommerce-product-add.js"></script>
</body>

</html>