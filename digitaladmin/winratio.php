<?php
session_start();
if (empty($_SESSION['unohs'])) {
    header("location: api/login.php?msg=unauthorized");
}
?>
<?php
include("api/conn.php");

$munde = mysqli_query($conn, "SELECT sankhye FROM `hastacalita_phalitansa_aidudi` WHERE `sthiti`='1'");
if (mysqli_num_rows($munde) > 0) {
    $uhisi = mysqli_fetch_array($munde);
    $uhisisankhye = $uhisi['sankhye'];
} else {
    $uhisisankhye = "Not set";
}
?>
<!doctype html>

<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr"
    data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template" data-style="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

    <title>Win ratio</title>

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
        .circle{
            width: 100px;
            height: 100px;
            border-radius: 50%;
            font-size: 1.7em;
            font-weight: bold;
        }
        .red{
            color: rgba(231, 85, 79, 1);
            background-color: rgba(243, 223, 224, 1);
            border: 10px solid rgba(231, 85, 79, 1);
        }
        .green{
            color: green;
            background-color: rgba(226, 238, 230, 1);
            border: 10px solid green;
        }
        .border-violet{
            border: 10px solid purple;
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
                    <div class="row g-6 mb-6">
                        <div class="col-sm-12 col-xl-3">
                            <div class="card shadow-sm border-0 rounded-3">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="card-title mb-0">Game Settings</h5>
                                    </div>
                
                                    <?php
                                    // Fetch current settings from game_win_setting
                                    $sql = "SELECT game, process_type FROM game_win_settings LIMIT 1";
                                    $result = $conn->query($sql);
                
                                    $game_mode = "wingo"; // Default value
                                    $process_type = "highest_bet_wins"; // Default value
                
                                    if ($result->num_rows > 0) {
                                        $row = $result->fetch_assoc();
                                        $game_mode = $row['game'];
                                        $process_type = $row['process_type'];
                                    }
                
                                    // Handle form submission
                                    if ($_SERVER["REQUEST_METHOD"] == "POST") {
                                        $game_mode = $_POST['game_mode'];
                                        $process_type = $_POST['process_type'];
                
                                        // Update game_win_setting in the database
                                        $update_query = "UPDATE game_win_settings SET game = '$game_mode', process_type = '$process_type' WHERE id = 1";
                                        if ($conn->query($update_query) === TRUE) {
                                            echo "<script>alert('Settings updated successfully!');</script>";
                                        } else {
                                            echo "<script>alert('Error updating settings: " . $conn->error . "');</script>";
                                        }
                                    }
                                    ?>
                
                                    <!-- Mode Changer Form -->
                                    <form method="POST" action="">
                                        <div class="mb-3">
                                            <label for="game_mode" class="form-label fw-semibold">Select Game Mode</label>
                                            <select name="game_mode" id="game_mode" class="form-select rounded-pill">
                                                <option value="wingo" <?= ($game_mode == "wingo") ? "selected" : "" ?>>Wingo</option>
                                                <!--<option value="k3" <?= ($game_mode == "k3") ? "selected" : "" ?>>K3</option>-->
                                            </select>
                                        </div>
                
                                        <div class="mb-3">
                                            <label for="process_type" class="form-label fw-semibold">Select Process Type</label>
                                            <select name="process_type" id="process_type" class="form-select rounded-pill">
                                                <option value="highest_bet_wins" <?= ($process_type == "highest_bet_wins") ? "selected" : "" ?>>Higher Bet Wins</option>
                                                <option value="random" <?= ($process_type == "random") ? "selected" : "" ?>>Random</option>
                                                <option value="default" <?= ($process_type == "default") ? "selected" : "" ?>>Higher Bet Lose</option>
                                            </select>
                                        </div>
                
                                        <button type="submit" class="btn btn-primary w-100 rounded-pill">
                                            Save Settings
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
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
   
</body>

</html>