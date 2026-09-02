<?php
session_start();
if (empty($_SESSION['unohs'])) {
	header("location: api/login.php?msg=unauthorized");
}

include("api/conn.php");

function generateRandomSerial($length = 32)
{
	return strtoupper(bin2hex(random_bytes(16)));
}



// Handling form submission to generate codes
// Handling form submission to generate codes
if (isset($_POST['maxserials']) && isset($_POST['maxusers']) && isset($_POST['price'])) {
	$maxserials = mysqli_real_escape_string($conn, $_POST['maxserials']);
	$maxusers = mysqli_real_escape_string($conn, $_POST['maxusers']);
	$price = mysqli_real_escape_string($conn, $_POST['price']);
	$remark = mysqli_real_escape_string($conn, $_POST['remark']);

	// Check if maxserials is greater than 50
	if ($maxserials > 50) {
		echo '<script>alert("You can only generate a maximum of 50 serial numbers.");</script>';
		exit;  // Stop further execution if the limit is exceeded
	}

	$totalusers = 0;
	$createdate = date("Y-m-d H:i");
	$status = 1;
	$generatedSerials = [];

	// Generate the requested number of random serials
	for ($i = 0; $i < $maxserials; $i++) {
		$newSerial = generateRandomSerial(); // Generate a new random serial
		$generatedSerials[] = $newSerial;

		// Insert the generated serial into the database
		$insertRandomSerial = "INSERT INTO hodike_nirvahaka (enserie, utilisateurmax, prix, nombredutilisateurs, creerunrendezvous, shonu, remark) 
                               VALUES ('" . $newSerial . "', '" . $maxusers . "', '" . $price . "', '" . $totalusers . "', '" . $createdate . "', '" . $status . "', '" . $remark . "')";
		mysqli_query($conn, $insertRandomSerial);
	}

	// Convert the generated serials to JSON format to pass to JavaScript
	$generatedSerialsJson = json_encode($generatedSerials);

}

// Pagination Setup
$limit = 10; // Number of records per page
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Fetch history of generated codes from the database
$historyQuery = "SELECT * FROM hodike_nirvahaka ORDER BY creerunrendezvous DESC LIMIT $limit OFFSET $offset";
$historyResult = mysqli_query($conn, $historyQuery);

// Count total records for pagination
$totalQuery = "SELECT COUNT(*) AS total FROM hodike_nirvahaka";
$totalResult = mysqli_query($conn, $totalQuery);
$totalRow = mysqli_fetch_assoc($totalResult);
$totalRecords = $totalRow['total'];
$totalPages = ceil($totalRecords / $limit);

// hodike_nirvahaka 	
// Handle deletion of serials
if (isset($_POST['delete_serial'])) {
	$claims = (int)$_POST['claims'];
	$price = $_POST['price'];
	$serialToDelete = mysqli_real_escape_string($conn, $_POST['delete_serial']);
	if ($claims > 0) {
		$deductMotta = "UPDATE shonu_kaichila 
			JOIN hodike_balakedara ON shonu_kaichila.balakedara = hodike_balakedara.userkani 
			SET shonu_kaichila.motta = IF(shonu_kaichila.motta >= $price, shonu_kaichila.motta - $price, 0) 
			WHERE hodike_balakedara.serial = '$serialToDelete'";
		mysqli_query($conn, $deductMotta);

		$bonusDeduction = "INSERT INTO bonus_deduction (userkani, price, shonu, remark) 
			SELECT userkani, '$price', NOW() ,'Manual Deduction'
			FROM hodike_balakedara 
			WHERE serial = '$serialToDelete'";
		mysqli_query($conn, $bonusDeduction);
	}
	$deleteQuery = "DELETE FROM hodike_nirvahaka WHERE enserie = '$serialToDelete'";
	mysqli_query($conn, $deleteQuery);
	header("Location: " . $_SERVER['PHP_SELF']); // Refresh the page after deletion
}

?>

<!doctype html>

<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr"
	data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template" data-style="light">

<head>
	<meta charset="utf-8" />
	<meta name="viewport"
		content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

	<title>Gift Code</title>

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
									<h4 class="mb-1">Generate Gift Code</h4>
								</div>
							</div>

							<div class="row">
								<!-- First column-->
								<div class="col-12 col-lg-12">
									<!-- Product Information -->
									<div class="card mb-6">
										<!-- <div class="card-header">
											<h5 class="card-tile mb-0">USDT Rate Details</h5>
										</div> -->
										<form class="card-body" action="#" id="redform" method="post"
											autocomplete="off">
											<div class="form-floating form-floating-outline mb-5">
												<input type="number" class="form-control" id="ecommerce-product-name"
													placeholder="Enter number of number to generate bulk code"
													name="maxserials"
													aria-label="Enter number of number to generate bulk code"
													required />
												<label for="ecommerce-product-name">Enter number of number to generate
													bulk code</label>
											</div>
											<div class="form-floating form-floating-outline mb-5">
												<input type="number" class="form-control" id="ecommerce-product-name"
													placeholder="Enter maximum users" name="maxusers"
													aria-label="Enter maximum users" required />
												<label for="ecommerce-product-name">Enter maximum users</label>
											</div>
											<div class="form-floating form-floating-outline mb-5">
												<input type="text" class="form-control" id="ecommerce-product-name"
													placeholder="Add remark" name="remark" aria-label="Add remark"
													 />
												<label for="ecommerce-product-name">Add remark</label>
											</div>
											<div class="form-floating form-floating-outline mb-5">
												<input type="number" class="form-control" id="ecommerce-product-name"
													placeholder="Enter price" name="price" aria-label="Enter price"
													required />
												<label for="ecommerce-product-name">Enter price</label>
											</div>

											<div class="row mb-5 gx-5">
												<div class="col">
													<button class="btn btn-primary btn-lg w-100" type="submit">
														<i class="ri-reset-right-line ri-16px me-2"></i>Generate
													</button>
												</div>
											</div>
										</form>
									</div>
									<div class="card">
										<div class="table-responsive text-nowrap">
											<table class="table datatables-giftcode">
												<thead>
													<tr>
														<th>Serial Code</th>
														<th>Max Users</th>
														<th>Price</th>
														<th>Created At</th>
														<th>NO .of Claims</th>
														<th>Action</th>
													</tr>
												</thead>
												<tbody class="table-border-bottom-0">
													<?php while ($row = mysqli_fetch_assoc($historyResult)): ?>
														<tr>
															<td>
																<span
																	class="fw-medium"><?php echo $row['enserie']; ?></span>
															</td>
															<td><?php echo $row['utilisateurmax']; ?></td>
															<td><?php echo $row['prix']; ?></td>
															<td><?php echo $row['creerunrendezvous']; ?></td>
															<td>
																<?php echo $row['nombredutilisateurs']; ?>
																<a href="giftcode-details.php?code=<?= $row['enserie']; ?>"
																	class="update-person"
																	style="color:#0E0E44; font-size:16px;"
																	data-toggle="tooltip" title="User Deatil"><i
																		class="ri-information-2-line ri-22px text-primary"></i></a>
															</td>
															<td>
																<form method="post" action=""
																	onsubmit="return confirmDelete(event);">
																	<input type="hidden" name="claims"
																		value="<?= $row['nombredutilisateurs']; ?>" />
																	<input type="hidden" name="price"
																		value="<?= $row['prix']; ?>" />
																	<input type="hidden" name="delete_serial"
																		value="<?php echo $row['enserie']; ?>" />
																	<button class="btn btn-danger" type="submit"><i
																			class="ri-delete-bin-7-line me-1"></i>Delete</button>
																</form>
															</td>
														</tr>
													<?php endwhile; ?>
												</tbody>
											</table>
											<div class="px-4 d-flex justify-content-end">
												<ul class="pagination">
													<?php for ($i = 1; $i <= $totalPages; $i++): ?>
														<li
															class="paginate_button page-item <?= ($i == $page) ? 'active' : ''; ?>">
															<a href="?page=<?= $i; ?>" aria-controls="DataTables_Table_0"
																role="link" aria-current="page" data-dt-idx="0" tabindex="0"
																class="page-link"><?= $i; ?></a>
														</li>
													<?php endfor; ?>
												</ul>
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
						<div class="modal fade" id="generatedSerialsModal" tabindex="-1"
							aria-labelledby="generatedSerialsModalLabel" aria-hidden="true">
							<div class="modal-dialog">
								<div class="modal-content">
									<div class="modal-header">
										<h5 class="modal-title" id="generatedSerialsModalLabel">Generated Gift Code</h5>
										<button type="button" class="btn-close" data-bs-dismiss="modal"
											aria-label="Close"></button>
									</div>
									<div class="modal-body">
										<p>The following gift code generated:</p>
										<ul id="generatedSerialList"></ul>
										<button id="copyButton" class="btn btn-success mt-3">Copy All to
											Clipboard</button>
									</div>
									<div class="modal-footer">
										<button type="button" class="btn btn-secondary"
											data-bs-dismiss="modal">Close</button>
									</div>
								</div>
							</div>
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
				<script>
					$(function () {
						$('.datatables-giftcode').DataTable({
							"paging": false,
							"searching": true,
							"ordering": false,
							"info": true,
							"autoWidth": true,
							"dom":
								'<"row"' +
								'<"col-md-12"<"d-flex align-items-center justify-content-md-end justify-content-center"<l><"me-4"f>>>' +
								'>t',
							"language": {
								sLengthMenu: 'Show _MENU_',
								search: '',
								searchPlaceholder: 'Search User',
							},
						});
					});
				</script>
				<script>
					<?php
					// If we have generated serials, show the modal with the list of serials
					if (isset($generatedSerialsJson)) {
						echo "let generatedSerials = " . $generatedSerialsJson . ";";
						echo "let serialList = document.getElementById('generatedSerialList');";
						echo "generatedSerials.forEach(function(serial) {
                                let listItem = document.createElement('li');
                                listItem.textContent = serial;
                                serialList.appendChild(listItem);
                              });
                              var myModal = new bootstrap.Modal(document.getElementById('generatedSerialsModal'));
                              myModal.show();";
					}
					?>

					// Copy All Serial Numbers to Clipboard
					document.getElementById("copyButton").addEventListener("click", function () {
						let serialsText = '';
						// Get all the serials in the list and combine them into a string
						document.querySelectorAll('#generatedSerialList li').forEach(function (li) {
							serialsText += li.textContent + '\n';  // Add each serial with a new line
						});

						// Use the Clipboard API to copy to the clipboard
						navigator.clipboard.writeText(serialsText).then(() => {
							alert("Text copied successfully!");
						}).catch(err => {
							console.error("Copy failed:", err);
						});
					});
					function confirmDelete(event) {
						if (!confirm("Are you sure you want to delete this?")) {
							event.preventDefault(); // Prevent form submission if user cancels
							return false;
						}
						return true;
					}
				</script>

</body>

</html>