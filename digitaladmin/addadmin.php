<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


	session_start();
	if(empty($_SESSION['unohs'])){
		header("location: api/login.php?msg=unauthorized");
	}
?>
<?php
	include ("api/conn.php");
	
	if(isset($_POST['serial']) && isset($_POST['maxusers'])){
$chkserial = mysqli_query($conn, "SELECT * FROM `nirvahaka_shonu` WHERE `nirvahaka_hesaru`='".$_POST['serial']."'");
$chkserialrow = mysqli_num_rows($chkserial);

if($chkserialrow == 0) {
    $serial = mysqli_real_escape_string($conn, $_POST['serial']);
    $maxusers = mysqli_real_escape_string($conn, $_POST['maxusers']);
    
    // Checkbox handling
    $dashboard = isset($_POST['dashboard']) ? 1 : 0;
    $games = isset($_POST['games']) ? 1 : 0;
    $finance = isset($_POST['finance']) ? 1 : 0;
    $manage_gateway = isset($_POST['manage_gateway']) ? 1 : 0;
    $manageusers = isset($_POST['manageusers']) ? 1 : 0;
    $users_pan = isset($_POST['users_pan']) ? 1 : 0;
    $manage_agent = isset($_POST['manage_agent']) ? 1 : 0;
    $assign_bonus = isset($_POST['assign_bonus']) ? 1 : 0;
    $support = isset($_POST['support']) ? 1 : 0;
    $other = isset($_POST['other']) ? 1 : 0;
    $manageteam = isset($_POST['manageteam']) ? 1 : 0;
    
    $status = 1;

    $sql_q = "INSERT INTO nirvahaka_shonu (
        hesaru, 
        nirvahaka_hesaru, 
        guptapada, 
        sthiti, 
        dashboard, 
        games, 
        finance, 
        manage_gateway, 
        manageusers, 
        users_pan, 
        manage_agent, 
        assign_bonus, 
        support, 
        other, 
        manageteam
    ) VALUES (
        '$serial', 
        '$serial', 
        '".md5($maxusers)."', 
        '$status', 
        '$dashboard', 
        '$games', 
        '$finance', 
        '$manage_gateway', 
        '$manageusers', 
        '$users_pan', 
        '$manage_agent', 
        '$assign_bonus', 
        '$support', 
        '$other', 
        '$manageteam'
    )";

    $chk = mysqli_query($conn, $sql_q);
if ($chk) {
    echo '<script type="text/javascript"> alert("Admin Added"); </script>';
} else {
    echo '<script type="text/javascript"> alert("Admin Add Failed: ' . mysqli_error($conn) . '"); </script>';
}
}
		else{
			echo '<script type="text/JavaScript"> alert("Duplicate Username"); </script>';
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

    <title>Add Admin</title>

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
                            <div class="row">
                                <!-- First column-->
                                <div class="col-2 col-lg-2"></div>
                                <div class="col-8 col-lg-8">
                                    <!-- Product Information -->
                                    <div class="card mb-6">
                                        <form class="card-body" action="#" id="redform" method="post" autocomplete="off">
                                            <h4 class="mb-5">Add Admin</h4>
                                            <div class="form-floating form-floating-outline mb-5">
                                                <input type="text" class="form-control" id="ecommerce-product-name"
                                                    placeholder="Enter Username"
                                                    name="serial"
                                                    aria-label="Enter Username" required />
                                                <label for="ecommerce-product-name">Enter Username
                                                    bulk code</label>
                                            </div>
                                            <div class="form-floating form-floating-outline mb-5">
                                                <input type="text" class="form-control" id="ecommerce-product-name"
                                                    placeholder="Enter Password" name="maxusers"
                                                    aria-label="Enter Password" required/>
                                                <label for="ecommerce-product-name">Enter Password</label>
                                            </div>
                                            <div class="form-check mb-3">
                                              <input type="checkbox" class="form-check-input" id="dashboard" name="dashboard"
                                                />
                                              <label class="form-check-label" for="dashboard">Dashboard</label>
                                            </div>
                                            <div class="form-check mb-3">
                                              <input type="checkbox" class="form-check-input" id="games" name="games"
                                                />
                                              <label class="form-check-label" for="games">Games</label>
                                            </div>
                                            <div class="form-check mb-3">
                                              <input type="checkbox" class="form-check-input" id="finance" name="finance"
                                                />
                                              <label class="form-check-label" for="finance">Finance</label>
                                            </div>
                                            <div class="form-check mb-3">
                                              <input type="checkbox" class="form-check-input" id="manage_gateway" name="manage_gateway"
                                                />
                                              <label class="form-check-label" for="manage_gateway">Manage Gateway</label>
                                            </div>
                                            
                                            <div class="form-check mb-3">
                                              <input type="checkbox" class="form-check-input" id="manageusers" name="manageusers"
                                                />
                                              <label class="form-check-label" for="manageusers">Manage Users</label>
                                            </div>
                                            <div class="form-check mb-3">
                                              <input type="checkbox" class="form-check-input" id="users_pan" name="users_pan"
                                                />
                                              <label class="form-check-label" for="users_pan">Users Panalty</label>
                                            </div>
											<div class="form-check mb-3">
                                              <input type="checkbox" class="form-check-input" id="manage_agent" name="manage_agent"
                                                />
                                              <label class="form-check-label" for="manage_agent">Manage Agent</label>
                                            </div>
											<div class="form-check mb-3">
                                              <input type="checkbox" class="form-check-input" id="assign_bonus" name="assign_bonus"
                                                />
                                              <label class="form-check-label" for="assign_bonus">Assign Bonus</label>
                                            </div>
                                            <div class="form-check mb-3">
                                              <input type="checkbox" class="form-check-input" id="support" name="support"
                                                />
                                              <label class="form-check-label" for="support">Support</label>
                                            </div>

											  <div class="form-check mb-3">
                                              <input type="checkbox" class="form-check-input" id="other" name="other"
                                                />
                                              <label class="form-check-label" for="other">Others</label>
                                            </div>
                                            <div class="form-check mb-3">
                                              <input type="checkbox" class="form-check-input" id="manageteam" name="manageteam"
                                                />
                                              <label class="form-check-label" for="manageteam">agent team</label>
                                            </div>

                                            <div class="row mb-5 gx-5">
                                                <div class="col">
                                                    <button class="btn btn-primary btn-lg w-100" type="submit">
                                                        <i class="ri-reset-right-line ri-16px me-2"></i>Generate
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                <div class="col-2 col-lg-2"></div>

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