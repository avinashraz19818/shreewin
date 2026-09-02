<?php
session_start();
if (empty($_SESSION['unohs'])) {
    header("location: api/login.php?msg=unauthorized");
}
?>
<?php
include("api/conn.php");

$munde = mysqli_query($conn, "SELECT sankhye FROM `hastacalita_phalitansa_aidudi` WHERE `sthiti`='1'");
if (mysqli_num_rows($munde) > 0) {
    $uhisi = mysqli_fetch_array($munde);
    $uhisisankhye = $uhisi['sankhye'];
} else {
    $uhisisankhye = "Not set";
}
?>

<?php
// Subordinate Data PHP Logic

// Helper function for subordinate stats with level filtering
function fetchStats($conn, $subordinateIds, $selectedDate = null, $selectedLevel = 'all', $userId = null) {
    $stats = [
        'commission' => 0,
        'betters' => 0,
        'betAmount' => 0,
        'deposits' => 0,
        'depositAmount' => 0,
        'firstDepositors' => 0,
        'firstDepositAmount' => 0
    ];
    
    if (empty($subordinateIds)) return $stats;
    $ids = implode(',', array_map('intval', $subordinateIds));

    $dateCondition = '';
    $dateConditionThevani = '';
    if ($selectedDate) {
        $d = date('Y-m-d', strtotime($selectedDate));
        $esc = $conn->real_escape_string($d);
        $dateCondition = " AND DATE(tiarikala) = DATE('$esc')";
        $dateConditionThevani = " AND DATE(dinankavannuracisi) = DATE('$esc')";
    }

    // Commission query with level filtering
    $levelCondition = '';
    if ($selectedLevel !== 'all') {
        $lvl = intval($selectedLevel);
        $levelCondition = " AND prakara = 'LVLCOMM$lvl'";
    } else {
        // For "all" levels, include all commission types
        $levelCondition = " AND (prakara = 'LVLCOMM1' OR prakara = 'LVLCOMM2' OR prakara = 'LVLCOMM3' OR prakara = 'LVLCOMM4' OR prakara = 'LVLCOMM5' OR prakara = 'LVLCOMM6')";
    }

    $sql = "SELECT COALESCE(SUM(ayoga),0) commission, COALESCE(COUNT(DISTINCT koduvavanu),0) betters, COALESCE(SUM(ketebida),0) betAmount
            FROM vyavahara WHERE balakedara IN ($ids) $dateCondition $levelCondition";

    // Deposits query
    $sqlDep = "SELECT COUNT(*) deposits, COALESCE(SUM(motta),0) depositAmount FROM thevani WHERE balakedara IN ($ids) AND sthiti = 1 $dateConditionThevani";
    
    // First deposits query
    $sqlFirstDep = "SELECT COUNT(DISTINCT balakedara) firstDepositors, COALESCE(SUM(motta),0) firstDepositAmount 
                   FROM thevani WHERE balakedara IN ($ids) AND sthiti = 1 $dateConditionThevani";

    $res = $conn->query($sql);
    if ($res) {
        $row = $res->fetch_assoc();
        $stats['commission'] = (float)$row['commission'];
        $stats['betters'] = (int)$row['betters'];
        $stats['betAmount'] = (float)$row['betAmount'];
    }
    
    $resp = $conn->query($sqlDep);
    if ($resp) {
        $row = $resp->fetch_assoc();
        $stats['deposits'] = (int)$row['deposits'];
        $stats['depositAmount'] = (float)$row['depositAmount'];
    }
    
    $resf = $conn->query($sqlFirstDep);
    if ($resf) {
        $row = $resf->fetch_assoc();
        $stats['firstDepositors'] = (int)$row['firstDepositors'];
        $stats['firstDepositAmount'] = (float)$row['firstDepositAmount'];
    }

    return $stats;
}

// Manage form states
$searchedId = $_SESSION['searchedUserId'] ?? null;
$selectedDate = $_SESSION['selectedDate'] ?? null;
$selectedLevel = $_SESSION['selectedLevel'] ?? 'all';

// Default stats containers
$subStats = $teamStats = [
    'commission' => 0,
    'betters' => 0,
    'betAmount' => 0,
    'deposits' => 0,
    'depositAmount' => 0,
    'firstDepositors' => 0,
    'firstDepositAmount' => 0
];

$owncode = null;
$subs = [];
$teamSubs = [];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = (int)($_POST['user_id'] ?? 0);
    if ($userId > 0) {
        $searchedId = $userId;
        $_SESSION['searchedUserId'] = $searchedId;

        $selectedDate = !empty($_POST['date']) ? $_POST['date'] : null;
        $_SESSION['selectedDate'] = $selectedDate;

        $selectedLevel = ($_POST['level'] ?? 'all');
        $_SESSION['selectedLevel'] = $selectedLevel;

        $stmt = $conn->prepare("SELECT owncode FROM shonu_subjects WHERE id=?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $owncode = null;
        if ($row = $result->fetch_assoc()) {
            $owncode = $row['owncode'];
        }
        $stmt->close();

        $subs = [];
        $teamSubs = [];

        if ($owncode) {
            // Direct subs (level 1)
            $stmt = $conn->prepare("SELECT id FROM shonu_subjects WHERE code=?");
            $stmt->bind_param("s", $owncode);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $subs[] = $row['id'];
            }
            $stmt->close();

            // Team subs based on selected level
            if ($selectedLevel === 'all') {
                // All levels
                $stmt = $conn->prepare("SELECT id FROM shonu_subjects WHERE code1=? OR code2=? OR code3=? OR code4=? OR code5=?");
                $stmt->bind_param("sssss", $owncode, $owncode, $owncode, $owncode, $owncode);
                $stmt->execute();
                $res = $stmt->get_result();
                while ($row = $res->fetch_assoc()) {
                    $teamSubs[] = $row['id'];
                }
                $stmt->close();
            } else {
                // Specific level
                $levelField = "code" . ($selectedLevel - 1); // level 2 uses code1, level 3 uses code2, etc.
                if ($selectedLevel == 1) {
                    $levelField = "code";
                }
                
                $stmt = $conn->prepare("SELECT id FROM shonu_subjects WHERE $levelField=?");
                $stmt->bind_param("s", $owncode);
                $stmt->execute();
                $res = $stmt->get_result();
                while ($row = $res->fetch_assoc()) {
                    $teamSubs[] = $row['id'];
                }
                $stmt->close();
            }
        }

        // Get stats with level filtering
        $subStats = fetchStats($conn, $subs, $selectedDate, 'all', $searchedId);
        $teamStats = fetchStats($conn, $teamSubs, $selectedDate, $selectedLevel, $searchedId);
    }
}

// Yesterday stats
$yesterday = date('Y-m-d', strtotime('-1 day'));
$yesterdayCommission = 0;
$promoDirectRegister = 0;
$promoTeamRegister = 0;
$promoDirectDepositNum = 0;
$promoDirectDepositAmt = 0;
$promoTeamDepositNum = 0;
$promoTeamDepositAmt = 0;
$promoDirectFirstDepositNum = 0;
$promoTeamFirstDepositNum = 0;

if ($searchedId && $owncode) {
    // Yesterday commission
    $stmt = $conn->prepare("SELECT price as sumayoga FROM aks_comission_history_table WHERE userkani = ? AND DATE(shonu) = CURDATE() order by id desc limit 1");
    $stmt->bind_param("s", $searchedId);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $yesterdayCommission = number_format($row['sumayoga'], 2);
    }
    $stmt->close();

    // Yesterday direct registrations
    $stmt = $conn->prepare("SELECT COUNT(*) FROM shonu_subjects WHERE DATE(createdate)=? AND code=?");
    $stmt->bind_param("ss", $yesterday, $owncode);
    $stmt->execute();
    $stmt->bind_result($promoDirectRegister);
    $stmt->fetch();
    $stmt->close();

    // Yesterday team registrations
    $stmt = $conn->prepare("SELECT COUNT(*) FROM shonu_subjects WHERE DATE(createdate)=? AND (code1=? OR code2=? OR code3=? OR code4=? OR code5=?)");
    $stmt->bind_param("ssssss", $yesterday, $owncode, $owncode, $owncode, $owncode, $owncode);
    $stmt->execute();
    $stmt->bind_result($promoTeamRegister);
    $stmt->fetch();
    $stmt->close();

    // Yesterday direct deposits & sum
    $stmt = $conn->prepare("SELECT COUNT(*), COALESCE(SUM(motta),0) FROM thevani WHERE sthiti=1 AND DATE(dinankavannuracisi)=? AND balakedara IN (SELECT id FROM shonu_subjects WHERE code=?)");
    $stmt->bind_param("ss", $yesterday, $owncode);
    $stmt->execute();
    $stmt->bind_result($promoDirectDepositNum, $promoDirectDepositAmt);
    $stmt->fetch();
    $stmt->close();

    // Yesterday team deposits & sum
    $stmt = $conn->prepare("SELECT COUNT(*), COALESCE(SUM(motta),0) FROM thevani WHERE sthiti=1 AND DATE(dinankavannuracisi)=? AND balakedara IN (SELECT id FROM shonu_subjects WHERE code1=? OR code2=? OR code3=? OR code4=? OR code5=?)");
    $stmt->bind_param("ssssss", $yesterday, $owncode, $owncode, $owncode, $owncode, $owncode);
    $stmt->execute();
    $stmt->bind_result($promoTeamDepositNum, $promoTeamDepositAmt);
    $stmt->fetch();
    $stmt->close();

    // Yesterday direct first depositors
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT balakedara) FROM thevani WHERE sthiti=1 AND DATE(dinankavannuracisi)=? AND balakedara IN (SELECT id FROM shonu_subjects WHERE code=?)");
    $stmt->bind_param("ss", $yesterday, $owncode);
    $stmt->execute();
    $stmt->bind_result($promoDirectFirstDepositNum);
    $stmt->fetch();
    $stmt->close();

    // Yesterday team first depositors
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT balakedara) FROM thevani WHERE sthiti=1 AND DATE(dinankavannuracisi)=? AND balakedara IN (SELECT id FROM shonu_subjects WHERE code1=? OR code2=? OR code3=? OR code4=? OR code5=?)");
    $stmt->bind_param("ssssss", $yesterday, $owncode, $owncode, $owncode, $owncode, $owncode);
    $stmt->execute();
    $stmt->bind_result($promoTeamFirstDepositNum);
    $stmt->fetch();
    $stmt->close();
}
?>

<!doctype html>

<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr"
    data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template" data-style="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

    <title>Sub Data</title>

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
    <style>
        .circle{
            width: 100px;
            height: 100px;
            border-radius: 50%;
            font-size: 1.7em;
            font-weight: bold;
        }
        .red{
            color: rgba(231, 85, 79, 1);
            background-color: rgba(243, 223, 224, 1);
            border: 10px solid rgba(231, 85, 79, 1);
        }
        .green{
            color: green;
            background-color: rgba(226, 238, 230, 1);
            border: 10px solid green;
        }
        .border-violet{
            border: 10px solid purple;
        }
    </style>
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
                    <!-- Content -->
                <div class="content-wrapper">
    <!-- Content -->
<div class="container-xxl flex-grow-1 container-p-y">
    <!-- Yesterday Stats Section -->
    <?php if ($owncode): ?>
    <div class="yesterday-stats mb-4">
        <div class="text-center mb-4">
            <h3 class="text-white mb-2">Yesterday's Performance</h3>
            <h2 class="text-white">₹<?= $yesterdayCommission ?></h2>
            <p class="mb-0">Yesterday's Total Commission</p>
        </div>
        
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="stat-item">
                    <h4 class="text-white">Direct Subordinates</h4>
                    <div class="stat-value"><?= $promoDirectRegister ?></div>
                    <div class="stat-label">Registrations</div>
                    <div class="stat-value"><?= $promoDirectDepositNum ?></div>
                    <div class="stat-label">Deposits</div>
                    <div class="stat-value">₹<?= number_format($promoDirectDepositAmt, 2) ?></div>
                    <div class="stat-label">Deposit Amount</div>
                    <div class="stat-value"><?= $promoDirectFirstDepositNum ?></div>
                    <div class="stat-label">First Depositors</div>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="stat-item">
                    <h4 class="text-white">Team Subordinates</h4>
                    <div class="stat-value"><?= $promoTeamRegister ?></div>
                    <div class="stat-label">Registrations</div>
                    <div class="stat-value"><?= $promoTeamDepositNum ?></div>
                    <div class="stat-label">Deposits</div>
                    <div class="stat-value">₹<?= number_format($promoTeamDepositAmt, 2) ?></div>
                    <div class="stat-label">Deposit Amount</div>
                    <div class="stat-value"><?= $promoTeamFirstDepositNum ?></div>
                    <div class="stat-label">First Depositors</div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Search Form Section -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Search Subordinate Data</h5>
        </div>
        <div class="card-body">
            <form method="post">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="user_id" class="form-label">User ID</label>
                        <input type="number" class="form-control" name="user_id" id="user_id"
                            value="<?= htmlspecialchars($searchedId ?? '') ?>" required placeholder="Enter User ID" />
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="level" class="form-label">Level</label>
                        <select name="level" id="level" class="form-select">
                            <option value="all" <?= ($selectedLevel === 'all') ? 'selected' : ''; ?>>All Levels</option>
                            <option value="1" <?= ($selectedLevel == 1) ? 'selected' : ''; ?>>Level 1</option>
                            <option value="2" <?= ($selectedLevel == 2) ? 'selected' : ''; ?>>Level 2</option>
                            <option value="3" <?= ($selectedLevel == 3) ? 'selected' : ''; ?>>Level 3</option>
                            <option value="4" <?= ($selectedLevel == 4) ? 'selected' : ''; ?>>Level 4</option>
                            <option value="5" <?= ($selectedLevel == 5) ? 'selected' : ''; ?>>Level 5</option>
                            <option value="6" <?= ($selectedLevel == 6) ? 'selected' : ''; ?>>Level 6</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="date" class="form-label">Date</label>
                        <input type="date" name="date" id="date" class="form-control"
                            value="<?= htmlspecialchars($selectedDate ?? '') ?>" />
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary w-100">
                            Fetch Data
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php if ($searchedId !== null): ?>
    <!-- Results Section -->
    <div class="row">
        <div class="col-12 text-center mb-4">
            <h5>User ID: <?= htmlspecialchars($searchedId); ?>
                <?php if ($selectedDate): ?>
                | Date: <?= htmlspecialchars($selectedDate) ?>
                <?php endif; ?>
                <?php if ($selectedLevel !== 'all'): ?>
                | <span class="badge bg-primary">Level <?= $selectedLevel ?></span>
                <?php else: ?>
                | <span class="badge bg-primary">All Levels</span>
                <?php endif; ?>
            </h5>
        </div>
        
        <!-- Team Statistics Card -->
        <div class="col-12 mb-4">
            <div class="stats-card">
                <div class="stats-title">
                    Team Statistics 
                    <?php if ($selectedLevel !== 'all'): ?>
                    <span class="level-badge">Level <?= $selectedLevel ?></span>
                    <?php else: ?>
                    <span class="level-badge">All Levels</span>
                    <?php endif; ?>
                </div>
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-value"><?= intval($teamStats['deposits']) ?></div>
                        <div class="stat-label">Deposit number</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?= intval($teamStats['betters']) ?></div>
                        <div class="stat-label">Number of bettors</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?= intval($teamStats['firstDepositors']) ?></div>
                        <div class="stat-label">Number of people making first deposit</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">₹<?= number_format($teamStats['depositAmount'], 0) ?></div>
                        <div class="stat-label">Deposit amount</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">₹<?= number_format($teamStats['betAmount'], 0) ?></div>
                        <div class="stat-label">Total bet</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">₹<?= number_format($teamStats['firstDepositAmount'], 0) ?></div>
                        <div class="stat-label">First deposit amount</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Comparison -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">Direct Subordinates <span class="badge bg-primary">Level 1</span></h6>
                </div>
                <div class="card-body">
                    <div class="info-box">
                        <strong>Commission:</strong> ₹<?= number_format($subStats['commission'], 2); ?>
                    </div>
                    <div class="info-box">
                        <strong>Betters:</strong> <?= intval($subStats['betters']); ?>
                    </div>
                    <div class="info-box">
                        <strong>Total Bet:</strong> ₹<?= number_format($subStats['betAmount'], 2); ?>
                    </div>
                    <div class="info-box">
                        <strong>Deposits:</strong> <?= intval($subStats['deposits']); ?>
                    </div>
                    <div class="info-box">
                        <strong>Total Deposit:</strong> ₹<?= number_format($subStats['depositAmount'], 2); ?>
                    </div>
                    <div class="info-box">
                        <strong>First Depositors:</strong> <?= intval($subStats['firstDepositors']); ?>
                    </div>
                    <div class="info-box">
                        <strong>First Deposit Amount:</strong> ₹<?= number_format($subStats['firstDepositAmount'], 2); ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">Team Subordinates 
                        <?php if ($selectedLevel !== 'all'): ?>
                        <span class="badge bg-primary">Level <?= $selectedLevel ?></span>
                        <?php else: ?>
                        <span class="badge bg-primary">All Levels</span>
                        <?php endif; ?>
                    </h6>
                </div>
                <div class="card-body">
                    <div class="info-box">
                        <strong>Commission:</strong> ₹<?= number_format($teamStats['commission'], 2); ?>
                    </div>
                    <div class="info-box">
                        <strong>Betters:</strong> <?= intval($teamStats['betters']); ?>
                    </div>
                    <div class="info-box">
                        <strong>Total Bet:</strong> ₹<?= number_format($teamStats['betAmount'], 2); ?>
                    </div>
                    <div class="info-box">
                        <strong>Deposits:</strong> <?= intval($teamStats['deposits']); ?>
                    </div>
                    <div class="info-box">
                        <strong>Total Deposit:</strong> ₹<?= number_format($teamStats['depositAmount'], 2); ?>
                    </div>
                    <div class="info-box">
                        <strong>First Depositors:</strong> <?= intval($teamStats['firstDepositors']); ?>
                    </div>
                    <div class="info-box">
                        <strong>First Deposit Amount:</strong> ₹<?= number_format($teamStats['firstDepositAmount'], 2); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
/* Subordinate Data Styles */
.yesterday-stats {
    background: linear-gradient(135deg, #764ca3 0%, #667de9 100%);
    border-radius: 15px;
    padding: 25px;
    color: white;
    margin-bottom: 25px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.stats-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 15px;
    padding: 25px;
    color: white;
    margin-bottom: 25px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.stats-title {
    font-size: 1.3rem;
    font-weight: 600;
    margin-bottom: 20px;
    text-align: center;
    color: white;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
}

.stat-item {
    background: rgba(255,255,255,0.15);
    padding: 15px;
    border-radius: 10px;
    text-align: center;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.2);
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 5px;
    color: #ffffff;
}

.stat-label {
    font-size: 0.85rem;
    opacity: 0.9;
    line-height: 1.3;
}

.level-badge {
    background: rgba(255,255,255,0.3);
    color: white;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
    margin-left: 10px;
}

.info-box {
    background: #f8f9fa;
    border-left: 4px solid #667eea;
    padding: 12px;
    margin-bottom: 10px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    font-size: 0.9rem;
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .yesterday-stats .row > div {
        margin-bottom: 15px;
    }
    
    .stat-label {
        font-size: 0.8rem;
    }
}
</style>



                   
                    <!-- / Content -->

                    <!-- Footer -->
                    <?php require_once("footer.php"); ?>
                    <!-- / Footer -->

                    <div class="content-backdrop fade"></div>
                </div>
                <!-- Content wrapper -->
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
    
    <script>
        // Additional JavaScript for enhanced functionality
document.addEventListener('DOMContentLoaded', function() {
    // Auto-focus on user ID field
    const userIdInput = document.getElementById('user_id');
    if (userIdInput) {
        userIdInput.focus();
    }
    
    // Form validation
    const searchForm = document.querySelector('form[method="post"]');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            const userId = document.getElementById('user_id').value;
            if (!userId || userId < 1) {
                e.preventDefault();
                alert('Please enter a valid User ID');
                return false;
            }
        });
    }
    
    // Date picker enhancement
    const dateInput = document.getElementById('date');
    if (dateInput) {
        // Set max date to today
        const today = new Date().toISOString().split('T')[0];
        dateInput.setAttribute('max', today);
    }
    
    // Real-time form validation
    const levelSelect = document.getElementById('level');
    if (levelSelect) {
        levelSelect.addEventListener('change', function() {
            // You can add real-time updates here if needed
            console.log('Level changed to:', this.value);
        });
    }
    
    // Loading state for form submission
    const submitButton = searchForm?.querySelector('button[type="submit"]');
    if (submitButton) {
        searchForm.addEventListener('submit', function() {
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Fetching Data...';
            submitButton.disabled = true;
        });
    }
});

// Utility functions for subordinate data
const SubordinateDataUtils = {
    // Format currency
    formatCurrency: function(amount) {
        return new Intl.NumberFormat('en-IN', {
            style: 'currency',
            currency: 'INR',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(amount);
    },
    
    // Format number with commas
    formatNumber: function(number) {
        return new Intl.NumberFormat('en-IN').format(number);
    },
    
    // Calculate percentage
    calculatePercentage: function(part, total) {
        if (total === 0) return 0;
        return ((part / total) * 100).toFixed(2);
    },
    
    // Refresh data (if you want to add AJAX functionality)
    refreshData: function(userId, level, date) {
        // This can be used for AJAX updates in the future
        console.log('Refreshing data for:', { userId, level, date });
    }
};
    </script>

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