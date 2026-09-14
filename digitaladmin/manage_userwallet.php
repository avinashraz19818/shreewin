<?php
	session_start();
	if (empty($_SESSION['unohs'])) {
		header("location: api/login.php?msg=unauthorized");
		exit();
	}

	include("api/conn.php");

// Reset single user
if (isset($_POST['reset_single']) && isset($_POST['balakedara'])) {
    $userId = intval($_POST['balakedara']);
    $sql = "UPDATE shonu_kaichila SET motta = 0 WHERE balakedara = $userId";
    if ($conn->query($sql) === TRUE) {
        $msg = "User ID $userId Now 0.00 Balance.";
    } else {
        $msg = "Error: " . $conn->error;
    }
}

// Reset all users
if (isset($_POST['reset_all'])) {
    $sql = "UPDATE shonu_kaichila SET motta = 0";
    if ($conn->query($sql) === TRUE) {
        $msg = "Wallet reset Suceed.";
    } else {
        $msg = "Error resetting all: " . $conn->error;
    }
}

// Fetch user list
$result = $conn->query("SELECT balakedara, motta FROM shonu_kaichila ORDER BY balakedara ASC");
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
                    <h4 class="font-weight-bold text-dark mb-4">Reset User Wallets</h4>

                    <?php if (isset($msg)) : ?>
                        <div class="alert alert-info"><?php echo $msg; ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="row">
                            <!-- Left Section -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Search User by ID:</label>
                                    <input type="search" id="searchInput" onkeyup="filterTable()" class="form-control" placeholder="Type user ID...">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Select User:</label>
                                    <select name="balakedara" id="userSelect" class="form-select" required>
                                        <option value="">-- Select User ID --</option>
                                        <?php
                                        mysqli_data_seek($result, 0);
                                        while ($row = $result->fetch_assoc()) {
                                            echo "<option value='{$row['balakedara']}'>User ID: {$row['balakedara']} | Balance: ₹{$row['motta']}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>

                                <div class="mb-3 d-flex gap-2">
                                    <button type="submit" name="reset_single" class="btn btn-warning w-100">🔁 Reset Selected</button>
                                    <button type="submit" name="reset_all" class="btn btn-danger w-100" onclick="return confirm('Are you sure? This will reset all users to 0.')">⚠️ Reset All</button>
                                </div>
                            </div>

                            <!-- Right Section -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Wallet Overview:</label>
                                    <div class="table-responsive border rounded" style="max-height: 300px; overflow-y: auto;">
                                        <table id="userTable" class="table table-sm table-striped mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>User ID</th>
                                                    <th>Balance (₹)</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                mysqli_data_seek($result, 0);
                                                while ($row = $result->fetch_assoc()) {
                                                    echo "<tr><td>{$row['balakedara']}</td><td>₹{$row['motta']}</td></tr>";
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Optional full-width reset -->
                        <button type="submit" name="reset_all" class="btn btn-outline-danger w-100 mt-3" onclick="return confirm('Really reset all users?')">⚠️ Reset All Users Wallet</button>
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

<script>
// Filter rows by input
function filterTable() {
    let input = document.getElementById("searchInput").value.toLowerCase();
    let rows = document.querySelectorAll("#userTable tbody tr");
    rows.forEach(row => {
        let userId = row.cells[0].textContent.toLowerCase();
        row.style.display = userId.includes(input) ? "" : "none";
    });
}
</script>

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