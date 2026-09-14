<?php
session_start();
if (!isset($_SESSION['unohs'])) {
    header("location: api/login.php?msg=unauthorized");
    exit();
}

include("api/conn.php");

// Fetch existing values from landingpage table
$query = "SELECT * FROM landingpage LIMIT 1";
$result = mysqli_query($conn, $query);

if (!$result) {
    die("Error fetching data: " . mysqli_error($conn)); // Fetch Error Handling
}

$row = mysqli_fetch_assoc($result) ?? [];

// Default values
$giftcode = $row['giftcode'] ?? "";
$registerlink = $row['registerlink'] ?? "";
$refferalcode = $row['refferalcode'] ?? "";
$telegram = $row['telegram'] ?? "";
$gamename = $row['gamename'] ?? "";
$mindep = $row['mindep'] ?? "";

// Update or Insert Logic
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fields = [];

    if (!empty($_POST['giftcode'])) {
        $giftcode = substr(mysqli_real_escape_string($conn, $_POST['giftcode']), 0, 255);
        $fields[] = "giftcode='$giftcode'";
    }

    if (!empty($_POST['registerlink'])) {
        $registerlink = substr(mysqli_real_escape_string($conn, $_POST['registerlink']), 0, 255);
        $fields[] = "registerlink='$registerlink'";
    }

    if (!empty($_POST['refferalcode'])) {
        $refferalcode = substr(mysqli_real_escape_string($conn, $_POST['refferalcode']), 0, 255);
        $fields[] = "refferalcode='$refferalcode'";
    }

    if (!empty($_POST['telegram'])) {
        $telegram = substr(mysqli_real_escape_string($conn, $_POST['telegram']), 0, 255);
        $fields[] = "telegram='$telegram'";
    }
    
    if (!empty($_POST['gamename'])) {
        $gamename = substr(mysqli_real_escape_string($conn, $_POST['gamename']), 0, 255);
        $fields[] = "gamename='$gamename'";
    }
    
    if (!empty($_POST['mindep'])) {
        $mindep = substr(mysqli_real_escape_string($conn, $_POST['mindep']), 0, 255);
        $fields[] = "mindep='$mindep'";
    }

    if (!empty($fields)) {
        if (!empty($row)) {
            // Update existing record
            $updateQuery = "UPDATE landingpage SET " . implode(", ", $fields);
            if (mysqli_query($conn, $updateQuery)) {
                echo '<script>alert("Landing Page details updated successfully");</script>';
            } else {
                echo '<script>alert("Update Failed: ' . mysqli_error($conn) . '");</script>';
            }
        } else {
            // Insert new record if no data exists
            $insertQuery = "INSERT INTO landingpage (giftcode, registerlink, refferalcode, telegram, gamename, mindep) VALUES ('$giftcode', '$registerlink', '$refferalcode', '$telegram', '$gamename', '$mindep')";
            if (mysqli_query($conn, $insertQuery)) {
                echo '<script>alert("Landing Page details added successfully");</script>';
            } else {
                echo '<script>alert("Insert Failed: ' . mysqli_error($conn) . '");</script>';
            }
        }
        header("Refresh:0");
    } else {
        echo '<script>alert("No changes made! Please enter at least one value.");</script>';
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

    <title>Update Landing Page</title>

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
                        <div class="app-ecommerce">
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-6 row-gap-4">
                                <div class="d-flex flex-column justify-content-center">
                                    <h4 class="mb-1">Update Landing Page</h4>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12 col-lg-8">
                                    <div class="card mb-6">
                                        <div class="card-header">
                                            <h5 class="card-title mb-0">Landing Page Details</h5>
                                        </div>
                                       <form class="card-body" action="#" method="post" autocomplete="off">
                                            <div class="form-floating form-floating-outline mb-5">
                                                <input type="text" class="form-control" name="giftcode" placeholder="Gift Code" value="<?= htmlspecialchars($row['giftcode'] ?? ''); ?>" />
                                                <label for="giftcode">Gift Code</label>
                                            </div>
                                            <div class="form-floating form-floating-outline mb-5">
                                                <input type="text" class="form-control" name="registerlink" placeholder="Register Link" value="<?= htmlspecialchars($row['registerlink'] ?? ''); ?>" />
                                                <label for="registerlink">Register Link</label>
                                            </div>
                                            <div class="form-floating form-floating-outline mb-5">
                                                <input type="text" class="form-control" name="refferalcode" placeholder="Referral Code" value="<?= htmlspecialchars($row['refferalcode'] ?? ''); ?>" />
                                                <label for="refferalcode">Referral Code</label>
                                            </div>
                                            <div class="form-floating form-floating-outline mb-5">
                                                <input type="text" class="form-control" name="telegram" placeholder="Telegram Link" value="<?= htmlspecialchars($row['telegram'] ?? ''); ?>" />
                                                <label for="telegram">Telegram</label>
                                            </div>
                                             <div class="form-floating form-floating-outline mb-5">
                                                <input type="text" class="form-control" name="gamename" placeholder="Game name" value="<?= htmlspecialchars($row['gamename'] ?? ''); ?>" />
                                                <label for="gamename">Game Name</label>
                                            </div>
                                             <div class="form-floating form-floating-outline mb-5">
                                                <input type="text" class="form-control" name="mindep" placeholder="Minimum Deposit Amount" value="<?= htmlspecialchars($row['mindep'] ?? ''); ?>" />
                                                <label for="mindep">Min Deposit Amount</label>
                                            </div>
                                            <div class="row mb-5 gx-5">
                                                <div class="col">
                                                    <button class="btn btn-primary btn-lg w-100" name="updateLanding">
                                                        <i class="ri-reset-right-line ri-16px me-2"></i>Update
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php require_once("footer.php"); ?>
                </div>

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