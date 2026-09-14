<?php
	session_start();
	if (empty($_SESSION['unohs'])) {
		header("location: api/login.php?msg=unauthorized");
		exit();
	}

	include("api/conn.php");

$user_records = null;
$show_form = false;

if(isset($_POST['search_user'])) {
    $userid = mysqli_real_escape_string($conn, $_POST['userid']);
    if(!empty($userid)) {
        $user_records = mysqli_query($conn, "SELECT * FROM khate WHERE byabaharkarta = '$userid'");
        if(mysqli_num_rows($user_records) > 0) {
            $show_form = true;
        } else {
            echo '<script type="text/JavaScript"> alert("No records found for this User ID!"); </script>';
        }
    }
}

if(isset($_POST['update'])) {
    $userid = mysqli_real_escape_string($conn, $_POST['userid']);
    $account = mysqli_real_escape_string($conn, $_POST['khatehesaru']);
    $number = mysqli_real_escape_string($conn, $_POST['khatesankhye']);
    $ifsc = mysqli_real_escape_string($conn, $_POST['kod']);
    
    $update_sql = "UPDATE khate SET khatehesaru = '$account', khatesankhye = '$number', kod = '$ifsc' WHERE byabaharkarta = '$userid'";
    
    if(mysqli_query($conn, $update_sql)) {
       $msg = "Bank details updated successfully.";
        $user_records = mysqli_query($conn, "SELECT * FROM khate WHERE byabaharkarta = '$userid'");
        $show_form = true;
    } else {
        echo '<script type="text/JavaScript"> alert("Failed to update bank details!"); </script>';
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
                    <div class="card shadow-sm p-4">
                        <h4 class="font-weight-bold text-dark">Modify Bank Details</h4>
                        <?php if (isset($msg)) : ?>
                            <div class="alert alert-info"><?php echo $msg; ?></div>
                        <?php endif; ?>
                    </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <form action="#" method="post" autocomplete="off">
                                        <div class="form-group">
                                            <label for="userid">Enter User ID</label>
                                            <div class="d-flex gap-2">
                                                <input type="number" name="userid" id="userid" class="form-control cool-input" required placeholder="Enter User ID" value="<?php echo isset($_POST['userid']) ? $_POST['userid'] : ''; ?>">
                                                <button type="submit" name="search_user" class="btn btn-primary cool-button ml-2">Search User</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php if($user_records && mysqli_num_rows($user_records) > 0): ?>
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">Current Bank Details</h4>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Bank Name</th>
                                                    <th>Account Number</th>
                                                    <th>IFSC Code</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while($row = mysqli_fetch_assoc($user_records)): ?>
                                                <tr>
                                                    <td><?php echo $row['khatehesaru']; ?></td>
                                                    <td><?php echo $row['khatesankhye']; ?></td>
                                                    <td><?php echo $row['kod']; ?></td>
                                                    <td>
                                                        <button class="btn btn-outline-primary btn-sm" onclick="showUpdateForm('<?php echo $row['khatehesaru']; ?>', '<?php echo $row['khatesankhye']; ?>', '<?php echo $row['kod']; ?>')">Update</button>
                                                    </td>
                                                </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card update-form" id="updateForm">
                                <div class="card-body">
                                    <h4 class="card-title">Update Bank Details</h4>
                                    <form action="#" method="post" autocomplete="off">
                                        <input type="hidden" name="userid" value="<?php echo isset($_POST['userid']) ? $_POST['userid'] : ''; ?>">
                                        <div class="form-group">
                                            <label for="khatehesaru">Bank Name</label>
                                            <input type="text" name="khatehesaru" id="updateAccount" class="form-control cool-input" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="khatesankhye">Account Number</label>
                                            <input type="text" name="khatesankhye" id="updateNumber" class="form-control cool-input" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="kod">IFSC Code</label>
                                            <input type="text" name="kod" id="updateIFSC" class="form-control cool-input" required>
                                        </div>
                                        <button type="submit" name="update" class="btn btn-primary cool-button">Update Details</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
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