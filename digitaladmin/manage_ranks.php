<?php
session_start();
if (empty($_SESSION['unohs'])) {
    header("location: api/login.php?msg=unauthorized");
    exit;
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

// Get user details for top winners
$userDetails = [];
$rank = 1;
foreach ($allWins as $userId => $winCount) {
    if ($rank > 100) break; // Limit to top 100
    
    $userQuery = mysqli_query($conn, "SELECT mobile FROM shonu_subjects WHERE id = '$userId'");
    $userData = mysqli_fetch_assoc($userQuery);
    
    if ($userData) {
        $userDetails[] = [
            'rank' => $rank,
            'user_id' => $userId,
            'mobile' => $userData['mobile'],
            'wins' => $winCount
        ];
        $rank++;
    }
}
?>

<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr"
    data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template" data-style="light">

<head>
<meta charset="utf-8" />
<meta name="viewport"
    content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    
<title>User Rankings</title>
<meta name="description" content="" />
<link rel="icon" type="image/x-icon" href="assets/img/favicon/favicon.ico?v=20260814-admin" />
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&amp;display=swap"
    rel="stylesheet" />
<link rel="stylesheet" href="assets/vendor/fonts/remixicon/remixicon.css" />
<link rel="stylesheet" href="assets/vendor/fonts/flag-icons.css" />
<link rel="stylesheet" href="assets/vendor/libs/node-waves/node-waves.css" />
<link rel="stylesheet" href="assets/vendor/css/rtl/core.css" class="template-customizer-core-css" />
<link rel="stylesheet" href="assets/vendor/css/rtl/theme-default.css" class="template-customizer-theme-css" />
<link rel="stylesheet" href="assets/css/demo.css" />
<link rel="stylesheet" href="assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
<link rel="stylesheet" href="assets/vendor/libs/typeahead-js/typeahead.css" />
<link rel="stylesheet" href="assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css" />
<link rel="stylesheet" href="assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css" />
<link rel="stylesheet" href="assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css" />
<link rel="stylesheet" href="assets/vendor/libs/datatables-checkboxes-jquery/datatables.checkboxes.css" />
<link rel="stylesheet" href="assets/vendor/libs/select2/select2.css" />
<link rel="stylesheet" href="assets/vendor/libs/@form-validation/form-validation.css" />
<script src="assets/vendor/js/helpers.js"></script>
<script src="assets/vendor/js/template-customizer.js"></script>
<script src="assets/js/config.js"></script>

<style>
.rank-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    margin-bottom: 12px;
    /*background: #fff;*/
    transition: all 0.3s ease;
}
.rank-row:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.rank-info {
    flex: 1;
}
.rank-badge {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    color: white;
    margin-right: 15px;
}
.rank-1 { background: #FFD700; } /* Gold */
.rank-2 { background: #C0C0C0; } /* Silver */
.rank-3 { background: #CD7F32; } /* Bronze */
.rank-other { background: #007bff; } /* Blue */
.wins-badge {
    background: #28a745;
    color: white;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
}
.stats-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 20px;
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

                    <h4 class="mb-4">User Rankings</h4>

                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="stats-card">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">Total Deposits</h6>
                                        <h4 class="mb-0">₹<?= number_format($totalDeposits, 2) ?></h4>
                                    </div>
                                    <i class="ri-wallet-3-fill display-4 opacity-50"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stats-card">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">Total Bets</h6>
                                        <h4 class="mb-0"><?= number_format($totalBets) ?></h4>
                                    </div>
                                    <i class="ri-line-chart-fill display-4 opacity-50"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stats-card">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">Top Winners</h6>
                                        <h4 class="mb-0"><?= count($userDetails) ?></h4>
                                    </div>
                                    <i class="ri-trophy-fill display-4 opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- User Rankings List -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                Top Winners Ranking
                                <span class="badge bg-primary ms-2">
                                    <?= count($userDetails) ?> users
                                </span>
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($userDetails)): ?>
                                <?php foreach ($userDetails as $user): ?>
                                    <div class="rank-row">
                                        <div class="d-flex align-items-center">
                                            <div class="rank-badge <?= $user['rank'] <= 3 ? 'rank-' . $user['rank'] : 'rank-other' ?>">
                                                <?= $user['rank'] ?>
                                            </div>
                                            <div class="rank-info">
                                                <div class="d-flex align-items-center mb-2">
                                                    <h6 class="mb-0 me-3">User ID: <?= htmlspecialchars($user['user_id']) ?></h6>
                                                    <span class="wins-badge">
                                                        <?= $user['wins'] ?> Wins
                                                    </span>
                                                </div>
                                                <p class="mb-0 text-muted">
                                                    <i class="ri-phone-line me-1"></i>
                                                    <?= htmlspecialchars($user['mobile']) ?>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="rank-actions">
                                            <a href="user-details.php?user=<?= $user['user_id'] ?>" 
                                               class="btn btn-sm btn-primary"
                                               title="View User Details">
                                                <i class="ri-information-line me-1"></i> Details
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="ri-user-search-line display-4 text-muted"></i>
                                    <p class="text-muted mt-3">No user ranking data found.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>

                <?php include("footer.php"); ?>
                
            </div>
        </div>
    </div>
    </div>
    
    <!-- Core JS -->
    <script src="assets/vendor/libs/jquery/jquery.js"></script>
    <script src="assets/vendor/libs/popper/popper.js"></script>
    <script src="assets/vendor/js/bootstrap.js"></script>
    <script src="assets/vendor/libs/node-waves/node-waves.js"></script>
    <script src="assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="assets/vendor/libs/hammer/hammer.js"></script>
    <script src="assets/vendor/libs/i18n/i18n.js"></script>
    <script src="assets/vendor/libs/typeahead-js/typeahead.js"></script>
    <script src="assets/vendor/js/menu.js"></script>
    <script src="assets/vendor/libs/moment/moment.js"></script>
    <script src="assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js"></script>
    <script src="assets/vendor/libs/select2/select2.js"></script>
    <script src="assets/vendor/libs/@form-validation/popular.js"></script>
    <script src="assets/vendor/libs/@form-validation/bootstrap5.js"></script>
    <script src="assets/vendor/libs/@form-validation/auto-focus.js"></script>
    <script src="assets/vendor/libs/cleavejs/cleave.js"></script>
    <script src="assets/vendor/libs/cleavejs/cleave-phone.js"></script>
    <script src="assets/js/main.js"></script>

</body>
</html>