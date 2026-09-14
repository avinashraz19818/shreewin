<?php
	session_start();
	if (empty($_SESSION['unohs'])) {
		header("location: api/login.php?msg=unauthorized");
		exit();
	}

	include("api/conn.php");

function debugMessage($message) {
    echo "<pre style='background-color: #222; color: #0f0; padding: 10px; font-size: 16px;'>$message</pre>";
}

if (!$conn) {
    die(" Database Connection Failed: " . mysqli_connect_error());
}

// Handle Form Submission (Secure Method)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $min_recharge = $_POST['min_recharge'];
    $min_withdrawal = $_POST['min_withdrawal'];
    $register_bonus = $_POST['register_bonus'];
    $app_download = $_POST['app_download'];
    $allowbet = isset($_POST['allowbet']) ? (int)$_POST['allowbet'] : 0;

    //  New Fields
    $telegram_active = isset($_POST['telegram_active']) ? (int)$_POST['telegram_active'] : 0;
    $telegram_link = $_POST['telegram_link'];
    $web_name = $_POST['web_name'];

    //  Get existing values for logo paths
    $query = "SELECT * FROM web_setting WHERE id = 1";
    $result = mysqli_query($conn, $query);
    $settings = mysqli_fetch_assoc($result);

    $web_logo = $settings['web_logo'];
    if (isset($_FILES['web_logo']) && $_FILES['web_logo']['error'] == 0) {
        $fileName = basename($_FILES['web_logo']['name']);
        $web_logo = '/assets/' . $fileName;
    
        // Absolute path for move_uploaded_file
        $targetPath = $_SERVER['DOCUMENT_ROOT'] . $web_logo;
    
        if (move_uploaded_file($_FILES['web_logo']['tmp_name'], $targetPath)) {
            // ALSO COPY TO FRONTEND PATH with fixed name
            $frontendPath = $_SERVER['DOCUMENT_ROOT'] . "/assets/png/logo-e926b199.png";
            copy($targetPath, $frontendPath);
        } else {
            debugMessage("Logo upload failed: cannot move to $targetPath");
        }
    }
    
    $favicon_logo = $settings['favicon_logo'];
    if (isset($_FILES['favicon_logo']) && $_FILES['favicon_logo']['error'] == 0) {
        $fileName = basename($_FILES['favicon_logo']['name']);
        $favicon_logo = '/assets/' . $fileName;
    
        // Absolute path for favicon
        $targetPathFavicon = $_SERVER['DOCUMENT_ROOT'] . $favicon_logo;
    
        if (!move_uploaded_file($_FILES['favicon_logo']['tmp_name'], $targetPathFavicon)) {
            debugMessage("Favicon upload failed: cannot move to $targetPathFavicon");
        }
    }
    //  Final Update Query with All Fields
    $updateQuery = "UPDATE web_setting SET 
        min_recharge = ?, 
        min_withdrawal = ?, 
        register_bonus = ?, 
        app_download = ?, 
        allowbet = ?, 
        telegram_active = ?, 
        telegram_link = ?, 
        web_name = ?, 
        web_logo = ?, 
        favicon_logo = ? 
        WHERE id = 1";

    $stmt = mysqli_prepare($conn, $updateQuery);
    mysqli_stmt_bind_param(
        $stmt,
        "iiisisssss",
        $min_recharge,
        $min_withdrawal,
        $register_bonus,
        $app_download,
        $allowbet,
        $telegram_active,
        $telegram_link,
        $web_name,
        $web_logo,
        $favicon_logo
    );

    if (mysqli_stmt_execute($stmt)) {
        $msg = "Settings updated successfully!";
    } else {
        $msg = "Error updating settings: " . mysqli_error($conn);
        debugMessage($msg);
        }
    }
    
    //  Fetch Current Settings
    $query = "SELECT * FROM web_setting WHERE id = 1";
    $result = mysqli_query($conn, $query);
    $settings = mysqli_fetch_assoc($result);
    
    if (!$settings) {
        debugMessage("️ Settings Not Fetch Proper Check DB Connections.");
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
                    <h4 class="font-weight-bold text-dark mb-4">Website Settings</h4>

                    <?php if (isset($msg)) : ?>
                        <div class="alert alert-info"><?php echo $msg; ?></div>
                    <?php endif; ?>

                    <form action="" method="POST" enctype="multipart/form-data">
                        <div class="row">

                            <!-- Left Section -->
                            <div class="col-md-6">
                                <!-- Allow Bet -->
                                <div class="mb-3">
                                    <label class="form-label">Allow Bet:</label><br>
                                    <label>
                                        <input type="radio" name="allowbet" value="1" <?= ($settings['allowbet'] == 1) ? 'checked' : ''; ?>> Bet Allowed
                                    </label>
                                    <label class="ms-3">
                                        <input type="radio" name="allowbet" value="0" <?= ($settings['allowbet'] == 0) ? 'checked' : ''; ?>> Bet Not Allowed
                                    </label>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Minimum Recharge:</label>
                                    <input type="number" name="min_recharge" class="form-control" value="<?= $settings['min_recharge']; ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Minimum Withdrawal:</label>
                                    <input type="number" name="min_withdrawal" class="form-control" value="<?= $settings['min_withdrawal']; ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Register Bonus:</label>
                                    <input type="number" name="register_bonus" class="form-control" value="<?= $settings['register_bonus']; ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">App Download URL:</label>
                                    <input type="url" name="app_download" class="form-control" value="<?= $settings['app_download']; ?>" required>
                                </div>
                            </div>

                            <!-- Right Section -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Telegram Active:</label><br>
                                    <label>
                                        <input type="radio" name="telegram_active" value="1" <?= ($settings['telegram_active'] == 1) ? 'checked' : ''; ?>> On
                                    </label>
                                    <label class="ms-3">
                                        <input type="radio" name="telegram_active" value="0" <?= ($settings['telegram_active'] == 0) ? 'checked' : ''; ?>> Off
                                    </label>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Telegram Link:</label>
                                    <input type="url" name="telegram_link" class="form-control" value="<?= $settings['telegram_link']; ?>">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Website Name:</label>
                                    <input type="text" name="web_name" class="form-control" value="<?= $settings['web_name']; ?>">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Website Logo:</label>
                                    <input type="file" name="web_logo" class="form-control">
                                    <?php if (!empty($settings['web_logo'])): ?>
                                        <div class="mt-2 border rounded p-2" style="width: 100px; height: 100px;">
                                            <img src="<?= $settings['web_logo']; ?>" alt="Logo" style="width: 100%; height: 100%; object-fit: contain;">
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Favicon Logo:</label>
                                    <input type="file" name="favicon_logo" class="form-control">
                                    <?php if (!empty($settings['favicon_logo'])): ?>
                                        <div class="mt-2 border rounded p-2" style="width: 60px; height: 60px;">
                                            <img src="<?= $settings['favicon_logo']; ?>" alt="Favicon" style="width: 100%; height: 100%; object-fit: contain;">
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Submit -->
                        <button type="submit" class="btn btn-primary w-100 mt-3">Update Settings</button>
                    </form>

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