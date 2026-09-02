<?php
session_start();
if (empty($_SESSION['unohs'])) {
    header("location: api/login.php?msg=unauthorized");
    exit;
}
include("api/conn.php");

$msg = "";
$msgClass = "";

// === Handle Image Upload ===
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['uploadImage']) && isset($_FILES["image"])) {
    $target_dir = "../images/";
    $filename = basename($_FILES["image"]["name"]);
    $target_file = $target_dir . $filename;
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    $check = getimagesize($_FILES["image"]["tmp_name"]);
    if ($check === false) {
        $msg = "File is not an image.";
        $msgClass = "alert-danger";
        $uploadOk = 0;
    } elseif ($_FILES["image"]["size"] > 500000) {
        $msg = "File is too large.";
        $msgClass = "alert-warning";
        $uploadOk = 0;
    } elseif (!in_array($imageFileType, ["jpg", "jpeg", "png"])) {
        $msg = "Only JPG, JPEG, PNG files are allowed.";
        $msgClass = "alert-danger";
        $uploadOk = 0;
    }

    $check_query = mysqli_query($conn, "SELECT * FROM images WHERE filename = '$filename'");
    if (mysqli_num_rows($check_query) > 0) {
        $msg = "This image already exists in the database.";
        $msgClass = "alert-warning";
        $uploadOk = 0;
    }

    if ($uploadOk && move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
        $insert = "INSERT INTO images (filename, status) VALUES ('$filename', '0')";
        if ($conn->query($insert) === TRUE) {
            $msg = "Image uploaded and saved successfully.";
            $msgClass = "alert-success";
        } else {
            $msg = "Database insert error.";
            $msgClass = "alert-danger";
        }
    } elseif ($uploadOk) {
        $msg = "Error uploading file.";
        $msgClass = "alert-danger";
    }
}

// === Handle UPI Image Activation ===
if (isset($_POST['upiid'])) {
    $filename = $_POST['upiid'];
    mysqli_query($conn, "UPDATE images SET status = '0'");
    $update = "UPDATE images SET status = '1' WHERE filename = '$filename'";
    if (mysqli_query($conn, $update)) {
        $msg = "Active image updated successfully.";
        $msgClass = "alert-success";
    } else {
        $msg = "Failed to update active image.";
        $msgClass = "alert-danger";
    }
}

// === Handle Image Deletion ===
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $get_file = mysqli_fetch_assoc(mysqli_query($conn, "SELECT filename FROM images WHERE id = $id"));
    $file_path = "../images/" . $get_file['filename'];
    if (file_exists($file_path) && unlink($file_path)) {
        mysqli_query($conn, "DELETE FROM images WHERE id = $id");
        $msg = "Image deleted successfully.";
        $msgClass = "alert-success";
    } else {
        $msg = "Failed to delete image file.";
        $msgClass = "alert-danger";
    }
}
?>
<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr"
    data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template" data-style="light">

<head>
<meta charset="utf-8" />
<meta name="viewport"
content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

<title>Add UPI channel Image</title>

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
        .dz-preview img {
            width: 120px;
            height: auto;
            margin-bottom: 10px;
        }
    </style>
</head>

<body>
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">

            <?php require_once("layout-menu.php"); ?>

            <div class="layout-page">

                <?php require_once("nav.php"); ?>

                <div class="content-wrapper">
                    <div class="container-xxl flex-grow-1 container-p-y">

                        <!-- Show message -->
                        <?php if (!empty($msg)) : ?>
                            <div class="alert <?php echo $msgClass; ?> alert-dismissible fade show" role="alert">
                                <?php echo $msg; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"
                                    aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <div class="row">
                            <div class="col-12 col-lg-8">
                                <!-- Upload Image Card -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="mb-0">Upload UPI Image</h5>
                                    </div>
                                    <div class="card-body">
                                        <form action="" method="post" enctype="multipart/form-data">
                                            <label>Select Image to Upload</label>
                                            <input type="file" name="image" class="form-control mb-2" required>
                                            <button type="submit" name="uploadImage"
                                                class="btn btn-primary">Upload</button>
                                        </form>
                                    </div>
                                </div>

                                <!-- Active Image Selection Card -->
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">Choose Active UPI Image</h5>
                                    </div>
                                    <div class="card-body">
                                        <form action="" method="post">
                                            <?php
                                            $sel_upi = "SELECT * FROM images";
                                            $upi_r = mysqli_query($conn, $sel_upi);
                                            while ($row = mysqli_fetch_array($upi_r)) {
                                            ?>
                                                <div class="d-flex align-items-center justify-content-between mt-2">
                                                    <div>
                                                        <input name="upiid" type="radio"
                                                            value="<?php echo $row['filename']; ?>"
                                                            <?php if ($row['status'] == 1) echo "checked"; ?> />
                                                        <label><?php echo $row['filename']; ?></label>
                                                    </div>
                                                    <a href="?delete=<?php echo $row['id']; ?>"
                                                        onclick="return confirm('Are you sure to delete this image?')"
                                                        class="btn btn-sm btn-danger">Delete</a>
                                                </div>
                                            <?php } ?>
                                            <button type="submit" class="btn btn-success mt-3">Make Active</button>
                                        </form>
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