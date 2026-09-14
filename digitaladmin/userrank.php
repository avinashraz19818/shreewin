<?php
	session_start();
	if (empty($_SESSION['unohs'])) {
		header("location: api/login.php?msg=unauthorized");
		exit();
	}

	include("api/conn.php");

// Step 1: Get total deposits from 'thevani'
$totalDepositsResult = mysqli_query($conn, "SELECT SUM(motta) as totalDeposits FROM thevani");
$totalDepositsRow = mysqli_fetch_assoc($totalDepositsResult);
$totalDeposits = $totalDepositsRow['totalDeposits'] ?? 0;

// Step 2: Get total bets count from all betting tables
$bettingTables = [
    'bajikattuttate', 'bajikattuttate_aidudi', 'bajikattuttate_aidudi_drei', 'bajikattuttate_aidudi_funf', 'bajikattuttate_aidudi_zehn',
    'bajikattuttate_drei', 'bajikattuttate_funf', 'bajikattuttate_kemuru', 'bajikattuttate_kemuru_drei', 'bajikattuttate_kemuru_funf',
    'bajikattuttate_kemuru_zehn', 'bajikattuttate_trx', 'bajikattuttate_trx3', 'bajikattuttate_trx5', 'bajikattuttate_trx10', 'bajikattuttate_zehn',
];

$totalBets = 0;
$allWins = [];

foreach ($bettingTables as $table) {
    // Count total bets
    $countResult = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM $table");
    $countRow = mysqli_fetch_assoc($countResult);
    $totalBets += $countRow['cnt'] ?? 0;

    // Count wins
    $winsResult = mysqli_query($conn, "SELECT byabaharkarta AS user_id FROM $table WHERE phalaphala = 'gagner'");
    while ($row = mysqli_fetch_assoc($winsResult)) {
        $uid = $row['user_id'];
        if (!isset($allWins[$uid])) {
            $allWins[$uid] = 0;
        }
        $allWins[$uid]++;
    }
}
arsort($allWins);
?>

<!doctype html>
<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr"
	data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template" data-style="light">

<head>
	<meta charset="utf-8" />
	<meta name="viewport"
		content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
	<title>🏆 Top Performers</title>

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
	
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
	<style>
        body 
        .stat-card {
            border-radius: 12px;
            padding: 20px;
            color: white;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .stat-deposit { background: linear-gradient(135deg, #00b894, #00cec9); }
        .stat-roi     { background: linear-gradient(135deg, #0984e3, #74b9ff); }
        .stat-bets    { background: linear-gradient(135deg, #e17055, #fd9644); }
        .stat-winrate { background: linear-gradient(135deg, #d63031, #ff7675); }

        .stat-card i {
            font-size: 2rem;
            margin-bottom: 10px;
            display: block;
        }

        .performer-card {
            border-radius: 20px;
            box-shadow: 0 2px 15px rgb(255 255 255 / 10%);
            padding: 20px;
            transition: all 0.3s ease;
        }
        .performer-card:hover {
            transform: translateY(-5px);
        }
        .rank-badge {
            background: #002313;
            padding: 5px 15px;
            border-radius: 30px;
            display: inline-block;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .circle-progress {
            width: 60px;
            height: 60px;
            border: 5px solid #198754;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #198754;
            margin-left: auto;
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
                                    <h2 class="mb-4 fw-bold">🏆 Top Performers</h2>
                
                                    <!-- Top Stats Section -->
                                    <div class="row g-3 mb-5">
                                        <div class="col-md-3">
                                            <div class="stat-card stat-deposit">
                                                <i class="bi bi-wallet2"></i>
                                                <h5>Total Deposits</h5>
                                                <h3>₹<?php echo number_format($totalDeposits, 2); ?></h3>
                                                <p class="small">Across all performers</p>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="stat-card stat-roi">
                                                <i class="bi bi-graph-up-arrow"></i>
                                                <h5>Average ROI</h5>
                                                <h3>0.00%</h3>
                                                <p class="small">Return on investment</p>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="stat-card stat-bets">
                                                <i class="bi bi-cash-coin"></i>
                                                <h5>Total Bets</h5>
                                                <h3><?php echo $totalBets; ?></h3>
                                                <p class="small">Combined activity</p>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="stat-card stat-winrate">
                                                <i class="bi bi-hand-thumbs-up"></i>
                                                <h5>Avg Win Rate</h5>
                                                <h3>0.00%</h3>
                                                <p class="small">Success rate</p>
                                            </div>
                                        </div>
                                    </div>
                                
                                    <!-- Top 3 Performers -->
                                <div class="row g-4">
                                <?php
                                $rank = 1;
                                foreach ($allWins as $user_id => $total_wins) {
                                    if ($rank > 3) break; // Only show top 3 users
                                
                                    // Fetch user info
                                    $uinfo = mysqli_fetch_assoc(mysqli_query($conn, "SELECT codechorkamukala, mobile FROM shonu_subjects WHERE id = '$user_id'"));
                                    $wallet = mysqli_fetch_assoc(mysqli_query($conn, "SELECT motta FROM shonu_kaichila WHERE balakedara = '$user_id'"));
                                
                                    if (!$uinfo) continue;
                                
                                    $fake_percentage = rand(65, 100); // Fake progress % for design
                                ?>
                                    <div class="col-md-4">
                                        <div class="performer-card">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <span class="rank-badge">
                                                    <i class="bi bi-trophy-fill"></i> Rank #<?php echo $rank; ?> Elite
                                                </span>
                                                <div class="circle-progress"><?php echo $fake_percentage; ?>%</div>
                                            </div>
                                
                                            <div class="d-flex align-items-center justify-content-between mt-3">
                                    <div>
                                        <i class="bi bi-person-circle"></i>
                                        <a href="?user=<?= $user_id; ?>" class="text-decoration-none text-dark fw-bold">
                                            <?= htmlspecialchars($uinfo['codechorkamukala']); ?>
                                        </a>
                                        <div class="small text-muted">User ID: <?= $user_id; ?></div>
                                    </div>
                                    <a href="user-details.php?user=<?= $user_id; ?>" class="btn btn-sm btn-outline-primary" title="View Full Details">
                                        <i class="bi bi-info-circle"></i>
                                    </a>
                                </div>
                                 <p class="mb-1">
                                    <i class="bi bi-telephone-fill"></i>
                                    <?php echo $uinfo['mobile']; ?>
                                </p>
                    
                                <p class="mb-1 text-success fw-bold">
                                    <i class="bi bi-dot"></i> Active
                                </p>
                    
                                <hr>
                    
                                <p class="mb-1">
                                    <i class="bi bi-award-fill"></i>
                                    <strong>Total Wins:</strong> <?php echo $total_wins; ?>
                                </p>
                    
                                <p class="mb-1">
                                    <i class="bi bi-wallet2"></i>
                                    <strong>Wallet:</strong> ₹<?php echo number_format($wallet['motta'], 2); ?>
                                </p>
                            </div>
                        </div>
                    <?php $rank++; } ?>
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