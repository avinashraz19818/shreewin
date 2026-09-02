<?php
session_start();
if (empty($_SESSION['unohs'])) {
    header("location: api/login.php?msg=unauthorized");
    exit();
}

include("api/conn.php");

$statusMessage = ""; // To store success/error messages

// Handle banner upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['banner'])) {
    if (!$conn) {
        $statusMessage = "❌ Database connection failed: " . mysqli_connect_error();
    } else {
        $upload_dir = "uploads/banner/";
        $domain = "/adminab/";

        $timestamp = time();
        $random_str = substr(md5(rand()), 0, 7);
        $file_name = $timestamp . "_Banner_" . date("YmdHis") . $random_str . "." . pathinfo($_FILES["banner"]["name"], PATHINFO_EXTENSION);
        $target_path = $upload_dir . $file_name;
        $full_url = $domain . $target_path;

        if (move_uploaded_file($_FILES["banner"]["tmp_name"], $target_path)) {
            $stmt = mysqli_prepare($conn, "INSERT INTO banners (banner_url, is_active) VALUES (?, 1)");
            mysqli_stmt_bind_param($stmt, "s", $full_url);

            if (mysqli_stmt_execute($stmt)) {
                $statusMessage = "✅ Banner uploaded and saved successfully!";
            } else {
                $statusMessage = "⚠️ File uploaded, but failed to save to database.";
            }

            mysqli_stmt_close($stmt);
        } else {
            $statusMessage = "❌ Failed to upload the banner.";
        }
    }
}

// Update redirect URL logic
if (isset($_POST['updateLink'])) {
    $id = mysqli_real_escape_string($conn, $_POST['id']);
    $redirectUrl = mysqli_real_escape_string($conn, $_POST['redirectUrl']);

    $update_url = "UPDATE banners SET redirect_url = '$redirectUrl' WHERE id = '$id'";
    if (mysqli_query($conn, $update_url)) {
        $statusMessage = "✅ Redirect URL updated successfully!";
    } else {
        $statusMessage = "❌ Failed to update URL.";
    }
}

// Delete banner logic
if (isset($_POST['deleteBanner'])) {

    $banner_id = intval($_POST['id']);

    $fetch_query = "SELECT banner_url FROM banners WHERE id = $banner_id";
    $result = mysqli_query($conn, $fetch_query);
    $row = mysqli_fetch_assoc($result);

    if ($row) {
        $banner_url = $row['banner_url'];
        $parsed_url = parse_url($banner_url, PHP_URL_PATH);
        $file_path = $_SERVER['DOCUMENT_ROOT'] . $parsed_url;

        if (file_exists($file_path)) {
            unlink($file_path);
        }

        $delete_query = "DELETE FROM banners WHERE id = $banner_id";
        if (mysqli_query($conn, $delete_query)) {
            $statusMessage = "✅ Banner deleted successfully.";
        } else {
            $statusMessage = "❌ Failed to delete the banner from database.";
        }
    } else {
        $statusMessage = "⚠️ Banner not found.";
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
	<title>Web Settings</title>

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

	<!-- Custom Responsive CSS for setting-card -->
	<style>
		.setting-card {
			display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.2rem 1.5rem;
            margin: 1rem 0;
            /* border: 1px solid #ddd; */
            border-radius: 12px;
            /* background-color: #fff; */
            /* box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04); */
            flex-wrap: wrap;
		}

		.card-left {
			display: flex;
			align-items: center;
			gap: 1rem;
			flex: 1 1 300px;
		}

		.card-left .icon {
			font-size: 36px;
			color: #6366f1;
		}

		.card-left .title {
			font-weight: 600;
			font-size: 1.1rem;
		}

		.card-left .desc {
			font-size: 0.9rem;
			color: #6b7280;
			margin-top: 4px;
		}

		.card-right {
			display: flex;
			align-items: center;
			gap: 1rem;
			flex: 0 0 auto;
			margin-top: 1rem;
		}

		.switch {
			position: relative;
			display: inline-block;
			width: 48px;
			height: 24px;
		}

		.switch input {
			opacity: 0;
			width: 0;
			height: 0;
		}

		.slider {
			position: absolute;
			cursor: pointer;
			inset: 0;
			background-color: #ccc;
			transition: 0.4s;
			border-radius: 24px;
		}

		.slider:before {
			position: absolute;
			content: "";
			height: 18px;
			width: 18px;
			left: 3px;
			bottom: 3px;
			background-color: white;
			transition: 0.4s;
			border-radius: 50%;
		}
		.bgab {
		    border-radius: 10px;
            --bs-bg-opacity: 1;
            background-color: #31334e !important;
            padding: 1rem !important;
            box-shadow: 0 0.25rem 0.875rem 0 rgb(16 17 33 / 77%) !important;
        }

		input:checked + .slider {
			background-color: #6366f1;
		}

		input:checked + .slider:before {
			transform: translateX(24px);
		}

		.status {
			font-weight: bold;
			font-size: 0.95rem;
			color: <?= $wingo30status === 'active' ? '#10b981' : '#ef4444' ?>;
		}
		.alert-box {
            border-left: 5px solid #007bff;
            padding: 10px 15px;
            margin: 20px 0;
            border-radius: 4px;
            font-family: Arial, sans-serif;
            color: #fff;
        }

		@media (max-width: 768px) {
			.setting-card {
				flex-direction: column;
				align-items: flex-start;
			}

			.card-right {
				margin-top: 1rem;
			}
		}
	</style>
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
    
          <div class="content-wrapper">
            <div class="container-xxl flex-grow-1 container-p-y">
    
              <div class="card">
                <div class="table-responsive text-nowrap">
    
                   <div class="card shadow-sm p-4">
                     <h2 class="mb-4 text-primary">Upload New Banner</h2>
                         <?php if (!empty($statusMessage)): ?>
                            <div class="alert-box">
                                <?= htmlspecialchars($statusMessage) ?>
                            </div>
                        <?php endif; ?>
                        <form method="POST" enctype="multipart/form-data" class="bgab">
                            <div class="mb-3">
                                <input type="file" name="banner" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Upload</button>
                        </form>
                
                        <!-- ✅ Update Only URL -->
                        <h3 class="mt-5 text-success">Update Banner URL</h3>
                        <form method="POST" class="bgab">
                            <div class="mb-3">
                                <select name="id" class="form-select" required>
                                    <option value="">Select Banner</option>
                                    <?php
                                    $sel_banners = "SELECT id, banner_url FROM banners WHERE is_active='0' OR is_active='1' ORDER BY id DESC";
                                    $banner_r = mysqli_query($conn, $sel_banners);
                                    while ($row = mysqli_fetch_assoc($banner_r)) {
                                        echo '<option value="' . $row['id'] . '">' . $row['banner_url'] . '</option>';
                                    }
                                    ?>    
                                </div>
                            <div class="mb-3">
                                <input type="text" name="redirectUrl" class="form-control" placeholder="Enter Redirect URL" required>
                            </div>
                            <button type="submit" name="updateLink" class="btn btn-success">Update</button>
                        </form>
                
                        <!-- ✅ Display All Banners with Delete Option -->
                        <h3 class="mt-5 text-info">All Banners</h3>
                        <div class="table-responsive bgab">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Banner</th>
                                        <th>Redirect URL</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php
                                        $sel_banners = "SELECT * FROM banners WHERE is_active='0' OR is_active='1' ORDER BY id DESC";
                                        $banner_r = mysqli_query($conn, $sel_banners);
                                        while ($row = mysqli_fetch_array($banner_r)) {
                                        ?>    
                                    <tr>
                                            <td><img src="<?php echo $row['banner_url']; ?>" alt="Banner" width="120"></td>
                                            <td><a href="<?php echo $row['redirect_url']; ?>" target="_blank">
                                                <?php echo $row['redirect_url']; ?>
                                            </a></td>
                                            <td>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                                    <button type="submit" name="deleteBanner" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Delete</button>
                                                </form>
                                            </td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
                </div>
              </div>
    
            </div>
    
            <?php include("footer.php"); ?>
            <div class="content-backdrop fade"></div>
          </div>
        </div>
      </div>
    
      <div class="layout-overlay layout-menu-toggle"></div>
      <div class="drag-target"></div>
    </div>


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