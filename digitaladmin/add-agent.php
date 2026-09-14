<?php
session_start();
if (empty($_SESSION['unohs'])) {
    header("location: api/login.php?msg=unauthorized");
}
?>
<?php
include("api/conn.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['mobile']) && !empty($_POST['mobile'])) {
        $serial = mysqli_real_escape_string($conn, $_POST['mobile']);
        
        $chkserial = mysqli_query($conn, "SELECT * FROM `shonu_subjects` WHERE `mobile`='$serial'");

        if (mysqli_num_rows($chkserial) === 1) {
            $chkserial_ad = mysqli_query($conn, "SELECT 1 FROM `tb_agent` WHERE `mobile`='$serial' AND `status`='1'");

            if (mysqli_num_rows($chkserial_ad) === 0) {
                $stdarr = mysqli_fetch_assoc($chkserial);
                $last_id = $stdarr['id'];
                $createdate = date("Y-m-d H:i:s");
                $status = 1;

                $query = "INSERT INTO `tb_agent` (`userid`, `mobile`, `createdate`, `status`) 
                          VALUES ('$last_id', '$serial', '$createdate', '$status')";

                if (mysqli_query($conn, $query)) {
                    echo '<script>alert("Agent Added");</script>';
                } else {
                    echo '<script>alert("Agent Add Failed");</script>';
                }
            } else {
                echo '<script>alert("Agent already exists");</script>';
            }
        } else {
            echo '<script>alert("Mobile doesn\'t exist");</script>';
        }
    } else if (isset($_POST['userid']) && !empty($_POST['userid'])) {
         $serial = mysqli_real_escape_string($conn, $_POST['userid']);
        
        $chkserial = mysqli_query($conn, "SELECT * FROM `shonu_subjects` WHERE `id`='$serial'");

        if (mysqli_num_rows($chkserial) === 1) {
            $chkserial_ad = mysqli_query($conn, "SELECT 1 FROM `tb_agent` WHERE `userid`='$serial' AND `status`='1'");

            if (mysqli_num_rows($chkserial_ad) === 0) {
                $stdarr = mysqli_fetch_assoc($chkserial);
                $mobile = $stdarr['mobile'];
                $createdate = date("Y-m-d H:i:s");
                $status = 1;

                $query = "INSERT INTO `tb_agent` (`userid`, `mobile`, `createdate`, `status`) 
                          VALUES ('$serial', '$mobile', '$createdate', '$status')";

                if (mysqli_query($conn, $query)) {
                    echo '<script>alert("Agent Added");</script>';
                } else {
                    echo '<script>alert("Agent Add Failed");</script>';
                }
            } else {
                echo '<script>alert("Agent already exists");</script>';
            }
        } else {
            echo '<script>alert("Userid doesn\'t exist");</script>';
        }
    }

    if (isset($_POST['redserial'])) {
        $a_id = mysqli_real_escape_string($conn, $_POST['redserial']);
        $update_query = "UPDATE tb_agent SET status='2' WHERE userid='$a_id'";
        mysqli_query($conn, $update_query);
    }
    // header("Location: " . $_SERVER['PHP_SELF']);
}
?>

<!doctype html>

<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr"
    data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template" data-style="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

    <title>Add Agent Users</title>

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
                                    <h4 class="mb-1">Add Agent User</h4>
                                </div>
                            </div>

                            <div class="row">
                                <!-- First column-->
                                <div class="col-12 col-lg-8">
                                    <!-- Product Information -->
                                    <div class="card mb-6">
                                        <div class="card-header">
                                            <h5 class="card-tile mb-0">User Details</h5>
                                        </div>
                                        <div class="card-body" action="#" id="redform" method="post" autocomplete="off">
                                            <form action="#" id="redform" method="post" autocomplete="off">
                                                <div class="form-floating form-floating-outline mb-5">
                                                    <input type="text" class="form-control" id="ecommerce-product-name"
                                                        placeholder="Enter Mobile No." name="mobile"
                                                        aria-label="Enter Mobile No." />
                                                    <label for="ecommerce-product-name">Enter Mobile No.</label>
                                                </div>
                                                <div class="form-floating form-floating-outline mb-5">
                                                    <input type="text" class="form-control" id="ecommerce-product-name"
                                                        placeholder="Enter UID" name="userid"
                                                        aria-label="Enter UID" />
                                                    <label for="ecommerce-product-name">Enter UID</label>
                                                </div>
                                                <div class="row mb-5 gx-5">
                                                    <div class="col">
                                                        <button class="btn btn-primary btn-lg w-100" type="submit">
                                                            <i class="ri-add-line ri-16px me-2"></i>Add
                                                        </button>
                                                    </div>
                                                </div>
                                            </form>
                                            <form action="#" id="redlist" method="post" autocomplete="off">
                                                <div class="table-responsive text-nowrap">
                                                    <table class="form-check mb-4 table">
                                                        <thead>
                                                            <th>#</th>
                                                            <th>ID</th>
                                                            <th>Mobile</th>
                                                            <th>Date</th>
                                                        </thead>
                                                        </tbody>
                                                        <?php
                                                        $sel_red = "SELECT userid, mobile, createdate FROM tb_agent WHERE status=1";
                                                        $red_r = mysqli_query($conn, $sel_red);
                                                        while ($row = mysqli_fetch_array($red_r)) {
                                                            ?>
                                                            <tr>
                                                                <td>
                                                                    <input class="form-check-input m-0" type="radio"
                                                                        name="redserial" value="<?= $row['userid']; ?>"
                                                                        id="<?= $row['userid']; ?>" />
                                                                </td>
                                                                <td>
                                                                    <label class="form-check-label d-flex flex-column gap-1"
                                                                        for="<?= $row['userid']; ?>">
                                                                        <span
                                                                            class="h6 mb-0"><?= $row['userid']; ?></span>
                                                                    </label>
                                                                </td>
                                                                <td>
                                                                    <label class="form-check-label d-flex flex-column gap-1"
                                                                        for="<?= $row['userid']; ?>">
                                                                        <span class="h6 mb-0"><?= $row['mobile']; ?></span>
                                                                    </label>
                                                                </td>
                                                                <td>
                                                                    <label class="form-check-label d-flex flex-column gap-1"
                                                                        for="<?= $row['userid']; ?>">
                                                                        <span class="h6 mb-0"><?= $row['createdate']; ?></span>
                                                                    </label>
                                                                </td>
                                                            </tr>
                                                        <?php } ?>
                                                        </tbody>
                                                    </table>

                                                    <button class="btn btn-danger btn-lg" type="submit">
                                                        <i class="ri-shut-down-line ri-16px me-2"></i>Deactivate
                                                    </button>
                                                </div>
                                            </form>
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

                <!-- Page JS -->
                <script src="assets/js/app-user-list.js"></script>
</body>

</html>