<?php
session_start();
if (empty($_SESSION['unohs'])) {
    header("location: api/login.php?msg=unauthorized");
    exit();
}

include("api/conn.php");

$salary_status = '';
if (isset($_POST['start_salary'])) {
    include("/dailysalary.php");
    $_SESSION['salary_status'] = "Salary calculation run successfully based on tb_agent settings.";
    header("Location: agentmanage.php");
    exit;
}

if (isset($_SESSION['salary_status'])) {
    $salary_status = $_SESSION['salary_status'];
    unset($_SESSION['salary_status']);
}

$total_agents = mysqli_num_rows(mysqli_query($conn, "SELECT DISTINCT userid FROM dailysalary"));
$total_succ_rech = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM thevani WHERE sthiti = 1"))['cnt'];
$total_fail_rech = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM thevani WHERE sthiti = 0"))['cnt'];
$lastRun = mysqli_fetch_assoc(mysqli_query($conn, "SELECT MAX(createdate) as lastRun FROM dailysalary"))['lastRun'];

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
$valid_limits = [10, 50, 100, 200];
if (!in_array($limit, $valid_limits)) $limit = 50;

$results = mysqli_query($conn, "SELECT * FROM dailysalary ORDER BY createdate DESC LIMIT $limit");
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
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

	<!-- Page CSS -->

	<!-- Helpers -->
	<script src="assets/vendor/js/helpers.js"></script>
	<!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->
	<!--? Template customizer: To hide customizer set displayCustomizer value false in config.js.  -->
	<script src="assets/vendor/js/template-customizer.js"></script>
	<!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->
	<script src="assets/js/config.js"></script>
	<style>
		.dashboard-title {
			font-size: 24px;
			font-weight: 600;
		}
		.summary-card {
			border: 1px solid #dee2e6;
			border-radius: 8px;
		}
		.card-icon {
			font-size: 36px;
			margin-right: 12px;
		}
		.details-box {
			padding: 10px 15px;
			border-radius: 6px;
			margin-bottom: 10px;
		}
		pre {
			padding: 10px;
			border-radius: 5px;
			font-size: 13px;
		}
	</style>
</head>

<body>
	<div class="layout-wrapper layout-content-navbar">
		<div class="layout-container">
			<?php include("layout-menu.php"); ?>
			<div class="layout-page">
				<?php include("nav.php"); ?>

				<div class="content-wrapper">
					<div class="container-xxl flex-grow-1 container-p-y">

						<div class="card p-4 mb-4 shadow-sm">
							<div class="d-flex justify-content-between flex-wrap">
								<div>
									<div class="dashboard-title">📊 Agent Daily Salary Dashboard</div>
									<div class="text-muted">🕒 <?= date("d M Y H:i") ?></div>
								</div>
							</div>
						</div>

						<div class="card p-4 mb-4 shadow-sm">
							<form method="POST">
								<div class="d-flex justify-content-between flex-wrap">
									<div>
										<h6>⚙️ Daily Salary Control</h6>
										<small class="text-muted">Last Run: <?= $lastRun ? date("d M Y H:i", strtotime($lastRun)) : 'Never' ?></small>
									</div>
									<div>
										<button type="submit" name="start_salary" class="btn btn-primary btn-sm mt-2 mt-md-0">
											▶️ Run Salary
										</button>
									</div>
								</div>
							</form>
							<?php if ($salary_status): ?>
								<div class="alert alert-success mt-3 mb-0">✅ <?= $salary_status ?></div>
							<?php endif; ?>
						</div>

						<div class="row mb-4">
							<div class="col-md-4">
								<div class="card summary-card p-3 shadow-sm d-flex flex-row align-items-center">
									<span class="material-icons text-primary card-icon">group</span>
									<div>
										<h6>Total Agents</h6>
										<h5><?= $total_agents ?></h5>
									</div>
								</div>
							</div>
							<div class="col-md-4">
								<div class="card summary-card p-3 shadow-sm d-flex flex-row align-items-center">
									<span class="material-icons text-success card-icon">check_circle</span>
									<div>
										<h6>Successful Recharges</h6>
										<h5><?= $total_succ_rech ?></h5>
									</div>
								</div>
							</div>
							<div class="col-md-4">
								<div class="card summary-card p-3 shadow-sm d-flex flex-row align-items-center">
									<span class="material-icons text-danger card-icon">highlight_off</span>
									<div>
										<h6>Failed Recharges</h6>
										<h5><?= $total_fail_rech ?></h5>
									</div>
								</div>
							</div>
						</div>

						<div class="card shadow-sm mb-4">
							<div class="card-header bg-primary text-white fw-bold">📝 Salary Eligibility Rules</div>
							<div class="card-body">
								<ul class="list-group list-group-flush">
									<li class="list-group-item">👥 <strong>Min Referrals:</strong> 3 active</li>
									<li class="list-group-item">💰 <strong>Recharge:</strong> 1 referral recharged</li>
									<li class="list-group-item">🎯 <strong>Betting:</strong> ₹300 minimum</li>
									<li class="list-group-item">📆 <strong>Period:</strong> Yesterday</li>
									<li class="list-group-item">📈 <strong>Formula:</strong> (Recharge × %)</li>
									<li class="list-group-item">❌ <strong>No Salary:</strong> if conditions fail</li>
								</ul>
							</div>
						</div>

						<div class="d-flex justify-content-end mb-3">
                        <form method="GET" class="d-inline-flex align-items-center">
                          <label class="me-2">Show logs:</label>
                          <select name="limit" onchange="this.form.submit()" class="form-select form-select-sm w-auto">
                            <?php foreach ([10, 50, 100, 200] as $opt): ?>
                              <option value="<?= $opt ?>" <?= $limit === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                            <?php endforeach; ?>
                          </select>
                        </form>
                      </div>
                    
                      <h5 class="mb-3">📟 Salary Distribution Logs (<?= $limit ?>)</h5>
                      <div class="accordion" id="accordionSalary">
                        <?php $i = 1; while($row = mysqli_fetch_assoc($results)) {
                          $uid = $row['userid'];
                          $salary = number_format($row['salary']);
                          $created = date("d M Y H:i", strtotime($row['createdate']));
                          $tsruser = json_decode($row['tsruser'] ?? '[]');
                          $tfruser = json_decode($row['tfruser'] ?? '[]');
                          $tsbuser = json_decode($row['tsbuser'] ?? '[]');
                          $tfbuser = json_decode($row['tfbuser'] ?? '[]');
                    
                          $succRechAmt = number_format(mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(motta) as total FROM thevani WHERE sthiti = 1 AND balakedara IN (" . implode(',', $tsruser ?: [0]) . ")"))['total'] ?? 0);
                          $failRechAmt = number_format(mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(motta) as total FROM thevani WHERE sthiti = 0 AND balakedara IN (" . implode(',', $tfruser ?: [0]) . ")"))['total'] ?? 0);
                    
                          $successList = "";
                          foreach ($tsruser as $suid) {
                            $mobile = mysqli_fetch_assoc(mysqli_query($conn, "SELECT mobile FROM shonu_subjects WHERE id = '$suid'"))['mobile'] ?? 'N/A';
                            $amount = number_format(mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(motta) as total_rech FROM thevani WHERE balakedara = '$suid' AND sthiti = 1"))['total_rech'] ?? 0);
                            $successList .= "🟢 ID: $suid | 📱 $mobile | ₹$amount\n";
                          }
                    
                          $failList = "";
                          foreach ($tfruser as $fuid) {
                            $mobile = mysqli_fetch_assoc(mysqli_query($conn, "SELECT mobile FROM shonu_subjects WHERE id = '$fuid'"))['mobile'] ?? 'N/A';
                            $amount = number_format(mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(motta) as total_fail FROM thevani WHERE balakedara = '$fuid' AND sthiti = 0"))['total_fail'] ?? 0);
                            $failList .= "❌ ID: $fuid | 📱 $mobile | ₹$amount\n";
                          }
                    
                          $tsb = implode(', ', $tsbuser);
                          $tfb = implode(', ', $tfbuser);
                        ?>
                        <div class="accordion-item mb-2 border shadow-sm">
                          <div class="accordion-header d-flex align-items-center p-3">
                            <span class="material-icons text-secondary me-2">person</span> <strong>Agent ID:</strong> <span class="text-primary ms-1 me-3"> <?= $uid ?> </span>
                            <span class="badge bg-warning text-dark">Salary: ₹<?= $salary ?></span>
                            <span class="text-muted small ms-3">(<?= $created ?>)</span>
                            <button class="btn btn-sm btn-outline-primary ms-auto" data-bs-toggle="modal" data-bs-target="#agentDetailModal"
                              onclick='showAgentModal("<?= $uid ?>", "<?= $salary ?>", "<?= $created ?>", "<?= $succRechAmt ?>", "<?= $failRechAmt ?>", `<?= trim($successList) ?>`, `<?= trim($failList) ?>`, `<?= $tsb ?>`, `<?= $tfb ?>`)'>
                              <span class="material-icons">visibility</span>
                            </button>
                          </div>
                        </div>
                        <?php $i++; } ?>
                      </div>
                    </div>

					<?php include("footer.php"); ?>
					<div class="content-backdrop fade"></div>
				</div>
			</div>
		</div>
	</div>

	<!-- Modal -->
	<div class="modal fade" id="agentDetailModal" tabindex="-1">
		<div class="modal-dialog modal-lg modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">👤 Agent Details</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
				</div>
				<div class="modal-body" id="agentDetailBody"></div>
			</div>
		</div>
	</div>

	<script>
		function showAgentModal(uid, salary, created, successAmt, failAmt, successList, failList, sbids, fbids) {
			const html = `
				<p><strong>Agent ID:</strong> ${uid}</p>
				<p><strong>Salary:</strong> ₹${salary}</p>
				<p><strong>Date:</strong> ${created}</p>
				<div class="details-box"><strong>✅ Recharge:</strong> ₹${successAmt}</div>
				<div class="details-box"><strong>❌ Failed:</strong> ₹${failAmt}</div>
				<div class="details-box"><strong>🟢 Success Details:</strong><pre>${successList}</pre></div>
				<div class="details-box"><strong>🔴 Failed Details:</strong><pre>${failList}</pre></div>
				<div class="details-box"><strong>🧩 Bet Success IDs:</strong><pre>${sbids}</pre></div>
				<div class="details-box"><strong>🚫 Bet Fail IDs:</strong><pre>${fbids}</pre></div>`;
			document.getElementById("agentDetailBody").innerHTML = html;
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