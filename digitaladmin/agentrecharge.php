<?php
	session_start();
	if (empty($_SESSION['unohs'])) {
		header("location: api/login.php?msg=unauthorized");
		exit();
	}

	include("api/conn.php");
date_default_timezone_set("Asia/Kolkata");

$downlineData = [];
$agent_id = '';
$totalRechargeAmount = 0;
$totalRechargeUsers = 0;

// If form is submitted, redirect to GET
if (isset($_POST['search'])) {
    $agent_id = intval($_POST['agent_id']);
    header("Location: ".$_SERVER['PHP_SELF']."?agent_id=".$agent_id);
    exit;
}

// If redirected or loaded with GET
if (isset($_GET['agent_id'])) {
    $agent_id = intval($_GET['agent_id']);

    // Get agent's owncode
    $agent = mysqli_fetch_assoc(mysqli_query($conn, "SELECT owncode FROM shonu_subjects WHERE id='$agent_id'"));
    $owncode = $agent['owncode'] ?? '';

    if ($owncode) {
        // Get downline users
        $downlines = mysqli_query($conn, "SELECT id, mobile FROM shonu_subjects WHERE code='$owncode'");
        
        while ($user = mysqli_fetch_assoc($downlines)) {
            $userid = $user['id'];
            $mobile = $user['mobile'];

            // Check today's successful recharge
            $recharge = mysqli_fetch_assoc(mysqli_query($conn, "
                SELECT SUM(motta) AS today_recharge 
                FROM thevani 
                WHERE balakedara='$userid' 
                AND sthiti='1' 
                AND DATE(dinankavannuracisi)=CURDATE()
            "));

            $today_recharge = $recharge['today_recharge'] ?? 0;

            if ($today_recharge > 0) {
                $downlineData[] = [
                    'userid' => $userid,
                    'mobile' => $mobile,
                    'today_recharge' => $today_recharge
                ];
                $totalRechargeAmount += $today_recharge;
                $totalRechargeUsers++;
            }
        }
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
	<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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
                                        <h3 class="mb-4">🔍 Downline Today's Recharge Search</h3>
                    
                        <form method="POST" class="mb-4">
                            <div class="input-group">
                                <input type="number" name="agent_id" class="form-control" placeholder="Enter Agent ID" value="<?= htmlspecialchars($agent_id) ?>" required>
                                <button class="btn btn-primary" type="submit" name="search">Search</button>
                            </div>
                        </form>
                    
                        <?php if (!empty($agent_id)): ?>
                    
                            <!-- Summary Cards -->
                            <div class="row mb-4">
                                <div class="col-md-4 mb-3">
                                    <div class="card shadow-sm border-0 text-center p-3">
                                        <span class="material-icons text-success" style="font-size: 40px;">payments</span>
                                        <h6 class="mt-2">Today's Total Recharge</h6>
                                        <h4 class="text-primary">₹<?= number_format($totalRechargeAmount) ?></h4>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="card shadow-sm border-0 text-center p-3">
                                        <span class="material-icons text-info" style="font-size: 40px;">group</span>
                                        <h6 class="mt-2">Total Users Recharge</h6>
                                        <h4 class="text-primary"><?= $totalRechargeUsers ?></h4>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="card shadow-sm border-0 text-center p-3">
                                        <span class="material-icons text-warning" style="font-size: 40px;">badge</span>
                                        <h6 class="mt-2">Agent ID</h6>
                                        <h4 class="text-primary"><?= htmlspecialchars($agent_id) ?></h4>
                                    </div>
                                </div>
                            </div>
                    
                            <!-- Chart -->
                            <?php if (count($downlineData) > 0): ?>
                                <div class="card shadow-sm p-4 mb-4">
                                    <canvas id="rechargeChart" height="100"></canvas>
                                </div>
                            <?php endif; ?>
                    
                            <!-- Table -->
                            <?php if (count($downlineData) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead class="table table-bordered table-striped">
                                            <tr>
                                                <th>#</th>
                                                <th>User ID</th>
                                                <th>Mobile</th>
                                                <th>Today's Recharge (₹)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $i = 1; foreach ($downlineData as $data): ?>
                                                <tr>
                                                    <td><?= $i++ ?></td>
                                                    <td><?= $data['userid'] ?></td>
                                                    <td><?= $data['mobile'] ?></td>
                                                    <td>₹<?= number_format($data['today_recharge']) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning">No downline recharges found for today.</div>
                            <?php endif; ?>
                    
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

<?php if (count($downlineData) > 0): ?>
<script>
const ctx = document.getElementById('rechargeChart').getContext('2d');
const rechargeChart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($downlineData, 'userid')) ?>,
        datasets: [{
            label: 'Today\'s Recharge ₹',
            data: <?= json_encode(array_column($downlineData, 'today_recharge')) ?>,
            backgroundColor: 'rgba(54, 162, 235, 0.7)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 1
        }]
    },
    options: {
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});
</script>
<?php endif; ?>

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