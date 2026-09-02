<?php
session_start();
if (empty($_SESSION['unohs'])) {
	header("location: api/login.php?msg=unauthorized");
	exit();
}
include("api/conn.php");
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

	<!-- Custom CSS for Tree -->
	<style>
		.tree-wrapper {
			width: 100%;
			overflow-x: auto;
			padding-bottom: 1rem;
		}

		.zoom-controls {
			display: flex;
			gap: 10px;
			margin-bottom: 1rem;
			align-items: center;
		}

		.zoom-controls button {
			padding: 6px 12px;
			border: none;
			background-color: #6366f1;
			color: white;
			border-radius: 6px;
			cursor: pointer;
			font-weight: bold;
		}

		.zoom-controls button:hover {
			background-color: #4f46e5;
		}

		.zoom-label {
			font-weight: bold;
			color: #111;
		}

		.tree {
			display: flex;
			flex-direction: column;
			align-items: center;
			transform-origin: top center;
		}

		.tree ul {
			display: flex;
			padding-top: 20px;
			position: relative;
			flex-wrap: wrap;
			justify-content: center;
		}

		.tree li {
			list-style-type: none;
			text-align: center;
			position: relative;
			padding: 20px 5px 0 5px;
		}

		.tree li::before,
		.tree li::after {
			content: '';
			position: absolute;
			top: 0;
			border-top: 2px solid #28a745;
			width: 50%;
			height: 20px;
		}

		.tree li::before {
			right: 50%;
			border-right: 2px solid #28a745;
		}

		.tree li::after {
			left: 50%;
			border-left: 2px solid #28a745;
		}

		.tree li:only-child::before,
		.tree li:only-child::after {
			content: none;
		}

		.tree li .box {
			display: inline-block;
			border: 2px solid #28a745;
			padding: 10px 15px;
			border-radius: 10px;
			font-weight: 500;
			min-width: 160px;
		}

		.tree li .main-box {
			background-color: #28a745;
			color: white;
		}

		@media (max-width: 768px) {
			.zoom-controls {
				flex-direction: column;
				align-items: flex-start;
			}
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
						<div class="card p-4 shadow-sm">
							<h4 class="mb-3">Referral Tree</h4>

							<form method="GET" class="row g-3 mb-4">
								<div class="col-md-4">
									<input type="text" name="uid" class="form-control" placeholder="Enter digit UID" required>
								</div>
								<div class="col-md-2">
									<button type="submit" class="btn btn-success">SHOW MY TREE</button>
								</div>
							</form>

							<?php
							if (isset($_GET['uid'])) {
								$uid = intval($_GET['uid']);
								$stmt = $conn->prepare("SELECT owncode FROM shonu_subjects WHERE id = ?");
								$stmt->bind_param("i", $uid);
								$stmt->execute();
								$stmt->bind_result($owncode);
								$stmt->fetch();
								$stmt->close();

								if ($owncode) {
									echo "<div class='tree-wrapper'>";
									echo "<div class='zoom-controls'>
										<button onclick='zoomIn()'>+</button>
										<button onclick='zoomOut()'>−</button>
										<button onclick='resetZoom()'>⟳</button>
										<div class='zoom-label' id='zoomLabel'>100%</div>
									</div>";

									echo "<div id='zoomContainer' class='tree'>";
									echo "<ul><li><div class='box main-box'>UID: $uid</div></li></ul>";

									$levels = ['code', 'code1', 'code2', 'code3', 'code4', 'code5'];
									foreach ($levels as $level) {
										$query = "SELECT id, mobile FROM shonu_subjects WHERE $level = ?";
										$stmt2 = $conn->prepare($query);
										if ($stmt2) {
											$stmt2->bind_param("s", $owncode);
											$stmt2->execute();
											$result = $stmt2->get_result();

											if ($result->num_rows > 0) {
												echo "<ul>";
												while ($row = $result->fetch_assoc()) {
													echo "<li><div class='box'>UID: {$row['id']}<br>Ph: {$row['mobile']}</div></li>";
												}
												echo "</ul>";
											}
											$stmt2->close();
										} else {
											echo "<div class='text-danger'>Query failed: " . $conn->error . "</div>";
										}
									}
									echo "</div></div>"; // zoomContainer + wrapper
								} else {
									echo "<div class='alert alert-danger'>UID not found.</div>";
								}
							}
							?>
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
            
            <!-- JS Scripts -->
        	<script>
        		let zoomLevel = 1;
        
        		function zoomIn() {
        			zoomLevel = Math.min(zoomLevel + 0.1, 2);
        			applyZoom();
        		}
        
        		function zoomOut() {
        			zoomLevel = Math.max(zoomLevel - 0.1, 0.3);
        			applyZoom();
        		}
        
        		function resetZoom() {
        			zoomLevel = 1;
        			applyZoom();
        		}
        
        		function applyZoom() {
        			const container = document.getElementById("zoomContainer");
        			const label = document.getElementById("zoomLabel");
        			container.style.transform = `scale(${zoomLevel})`;
        			label.innerText = Math.round(zoomLevel * 100) + "%";
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