<?php
session_start();
if (!isset($_SESSION['unohs'])) {
    header("location: api/login.php?msg=unauthorized");
    exit;
}

include("api/conn.php");

if (isset($_POST['newupi'])) {

    $newPass = mysqli_real_escape_string($conn, $_POST['newupi']);

    // Update guptapada with MD5 hash
    $sql_q = "UPDATE nirvahaka_shonu 
              SET guptapada = '" . md5($newPass) . "' 
              WHERE unohs = '" . $_SESSION['unohs'] . "'";

    if (mysqli_query($conn, $sql_q)) {

        // Logout user
        session_unset();
        session_destroy();

        echo '<script>
                alert("Password Updated Successfully!");
                window.location.href = "api/login.php";
              </script>';
        exit;

    } else {
        echo '<script>alert("Failed to Update Password");</script>';
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

    <title>Admin Password</title>

    <link rel="icon" type="image/x-icon" href="assets/img/favicon/favicon.ico?v=20260814-admin" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&ampdisplay=swap"
        rel="stylesheet" />

    <link rel="stylesheet" href="assets/vendor/fonts/remixicon/remixicon.css" />
    <link rel="stylesheet" href="assets/vendor/fonts/flag-icons.css" />
    <link rel="stylesheet" href="assets/vendor/libs/node-waves/node-waves.css" />

    <link rel="stylesheet" href="assets/vendor/css/rtl/core.css" />
    <link rel="stylesheet" href="assets/vendor/css/rtl/theme-default.css" />
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
</head>

<body>
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">

            <?php require_once("layout-menu.php"); ?>

            <div class="layout-page">

                <?php require_once("nav.php"); ?>

                <div class="content-wrapper">
                    <div class="container-xxl flex-grow-1 container-p-y">

                        <div class="app-ecommerce">
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start mb-6">
                                <div>
                                    <h4 class="mb-1">Admin Password</h4>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-12 col-lg-8">
                                    <div class="card mb-6">
                                        <form class="card-body" action="" method="post" autocomplete="off">

                                            <div class="form-floating form-floating-outline mb-5">
                                                <input type="text" name="newupi" class="form-control"
                                                    placeholder="New Admin Password" required />
                                                <label>New Admin Password</label>
                                            </div>

                                            <div class="row mb-5 gx-5">
                                                <div class="col">
                                                    <button type="submit" class="btn btn-primary btn-lg w-100">
                                                        <i class="ri-reset-right-line ri-16px me-2"></i> Update
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
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
            </div>

        </div>

    </div>

    <script src="assets/vendor/libs/jquery/jquery.js"></script>
    <script src="assets/vendor/libs/popper/popper.js"></script>
    <script src="assets/vendor/js/bootstrap.js"></script>
    <script src="assets/vendor/libs/node-waves/node-waves.js"></script>
    <script src="assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="assets/vendor/js/menu.js"></script>

    <script src="assets/js/main.js"></script>

    <script>
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>
