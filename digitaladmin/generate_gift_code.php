<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (empty($_SESSION['unohs'])) {
    header("location: api/login.php?msg=unauthorized");
    exit();
}

include("api/conn.php");

mysqli_query($conn, "SET NAMES 'utf8mb4'");
mysqli_query($conn, "SET CHARACTER SET utf8mb4");
mysqli_query($conn, "SET SESSION collation_connection = 'utf8mb4_unicode_ci'");

function generateRandomSerial($length = 32) {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[random_int(0, strlen($characters) - 1)];
    }
    return $randomString;
}
/* ================= ADD COMBO SYSTEM ================= */
if (isset($_POST['add'])) {

    $id = intval($_POST['user_id']);
    $amount = floatval($_POST['amount']);

    if ($id > 0 && $amount > 0) {

        $res = $conn->query("
            SELECT mottta, turnover 
            FROM shonu_kaichila 
            WHERE balakedara='$id'
        ");

        if ($res && $res->num_rows > 0) {

            $row = $res->fetch_assoc();

            $current_mottta = floatval($row['mottta']);
            $current_turnover = floatval($row['turnover']);

            // 🔥 ADD BOTH COMBO
            $new_mottta = $current_mottta + $amount;
            $new_turnover = $current_turnover + $amount;

            $update = $conn->query("
                UPDATE shonu_kaichila 
                SET 
                    mottta='$new_mottta',
                    turnover='$new_turnover'
                WHERE balakedara='$id'
            ");

            if ($update) {
                $_SESSION['message'] = "✔️ Added Successfully: +$amount (Both Combo Updated)";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "❌ Update Failed!";
                $_SESSION['message_type'] = "error";
            }

        } else {
            $_SESSION['message'] = "❌ User Not Found!";
            $_SESSION['message_type'] = "error";
        }

    } else {
        $_SESSION['message'] = "❌ Invalid Input!";
        $_SESSION['message_type'] = "error";
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}



// Normal Gift Code Generator
if (!empty($_POST['maxserials']) && !empty($_POST['maxusers']) && !empty($_POST['price']) && isset($_POST['remark'])) {
    $maxserials = (int)mysqli_real_escape_string($conn, $_POST['maxserials']);
    $maxusers = (int)mysqli_real_escape_string($conn, $_POST['maxusers']);
    $price = (float)mysqli_real_escape_string($conn, $_POST['price']);
    $remark = mysqli_real_escape_string($conn, $_POST['remark']);

    if ($maxserials > 50) {
        $_SESSION['message'] = 'Maximum 50 codes allowed at a time.';
        $_SESSION['message_type'] = 'error';
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    $generatedSerials = [];
    $createdate = date("Y-m-d H:i:s");
    foreach (range(1, $maxserials) as $i) {
        $serial = generateRandomSerial();
        $generatedSerials[] = $serial;
        mysqli_query($conn, "INSERT INTO hodike_nirvahaka (enserie, utilisateurmax, prix, nombredutilisateurs, creerunrendezvous, shonu, remark) 
                             VALUES ('$serial', '$maxusers', '$price', 0, '$createdate', 1, '$remark')");
    }
    $_SESSION['generated_serials'] = $generatedSerials;
    $_SESSION['message'] = 'Gift codes generated successfully!';
    $_SESSION['message_type'] = 'success';
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Internal Gift Code Generator (no INT- prefix)
if (!empty($_POST['generate_internal'])) {
    $rechargeRequired = (float)mysqli_real_escape_string($conn, $_POST['recharge_required']);
    $maxserials = (int)mysqli_real_escape_string($conn, $_POST['internal_maxserials']);
    $maxusers = (int)mysqli_real_escape_string($conn, $_POST['internal_maxusers']);
    $price = (float)mysqli_real_escape_string($conn, $_POST['internal_price']);
    $remark = mysqli_real_escape_string($conn, $_POST['internal_remark']);

    if ($maxserials > 50) {
        $_SESSION['message'] = 'Maximum 50 internal codes allowed at a time.';
        $_SESSION['message_type'] = 'error';
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    $generatedInternalSerials = [];
    $createdate = date("Y-m-d H:i:s");
    foreach (range(1, $maxserials) as $i) {
        $serial = generateRandomSerial();
        $generatedInternalSerials[] = $serial;
        mysqli_query($conn, "INSERT INTO hodike_nirvahaka (enserie, utilisateurmax, prix, nombredutilisateurs, creerunrendezvous, shonu, remark, recharge_required) 
                             VALUES ('$serial', '$maxusers', '$price', 0, '$createdate', 1, '$remark', '$rechargeRequired')");
    }
    $_SESSION['generated_internal_serials'] = $generatedInternalSerials;
    $_SESSION['message'] = 'Internal gift codes generated successfully!';
    $_SESSION['message_type'] = 'success';
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Pagination
$limit = 10;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

$historyResult = mysqli_query($conn, "SELECT * FROM hodike_nirvahaka WHERE recharge_required IS NULL ORDER BY creerunrendezvous DESC LIMIT $limit OFFSET $offset");
$totalResult = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM hodike_nirvahaka WHERE recharge_required IS NULL"));
$totalPages = ceil($totalResult['total'] / $limit);

$internalCodes = mysqli_query($conn, "SELECT * FROM hodike_nirvahaka WHERE recharge_required IS NOT NULL ORDER BY creerunrendezvous DESC");

// Delete
if (!empty($_POST['delete_serial'])) {
    $serialToDelete = mysqli_real_escape_string($conn, $_POST['delete_serial']);
    mysqli_query($conn, "DELETE FROM hodike_nirvahaka WHERE enserie = '$serialToDelete'");
    $_SESSION['message'] = 'Gift code deleted successfully!';
    $_SESSION['message_type'] = 'success';
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Get statistics
$totalNormalCodes = $totalResult['total'];
$totalInternalCodes = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM hodike_nirvahaka WHERE recharge_required IS NOT NULL"))['total'];
$totalUsedCodes = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM hodike_nirvahaka WHERE nombredutilisateurs > 0"))['total'];
?>

<!DOCTYPE html>
<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr"
    data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template" data-style="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Gift Code Management</title>
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
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            text-align: center;
        }
        .stats-value {
            font-size: 24px;
            font-weight: bold;
            display: block;
        }
        .stats-label {
            font-size: 14px;
            opacity: 0.9;
        }
        .code-generator-section {
            /*background: #f8f9fa;*/
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #007bff;
        }
        .internal-generator-section {
            /*background: #e8f5e8;*/
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
        }
        .serial-code {
            font-family: 'Courier New', monospace;
            /*background: #f8f9fa;*/
            padding: 8px 12px;
            border-radius: 5px;
            border: 1px solid #dee2e6;
            margin: 5px 0;
        }
        .copy-btn {
            cursor: pointer;
            transition: all 0.3s;
        }
        .copy-btn:hover {
            transform: scale(1.1);
        }
        .pagination {
            justify-content: center;
            margin-top: 20px;
        }
        .badge-used {
            background-color: #28a745;
        }
        .badge-unused {
            background-color: #6c757d;
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
                        <!-- Display Messages -->
                        <div id="messageContainer">
                            <?php if (isset($_SESSION['message'])): ?>
                                <div class="alert alert-<?= $_SESSION['message_type'] == 'success' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
                                    <?= $_SESSION['message'] ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                                <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
                            <?php endif; ?>
                        </div>

                        <!-- Statistics Cards -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="stats-card">
                                    <span class="stats-value"><?= $totalNormalCodes ?></span>
                                    <span class="stats-label">Normal Gift Codes</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                                    <span class="stats-value"><?= $totalInternalCodes ?></span>
                                    <span class="stats-label">Internal Codes</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card" style="background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);">
                                    <span class="stats-value"><?= $totalUsedCodes ?></span>
                                    <span class="stats-label">Used Codes</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card" style="background: linear-gradient(135deg, #ffd700 0%, #ffa500 100%);">
                                    <span class="stats-value"><?= $totalNormalCodes + $totalInternalCodes - $totalUsedCodes ?></span>
                                    <span class="stats-label">Available Codes</span>
                                </div>
                            </div>
                        </div>

                        <!-- Generated Codes Display -->
                        <?php if (isset($_SESSION['generated_serials'])): ?>
                        <div class="card mb-4">
                            <div class="card-header bg-success text-white">
                                <h5 class="card-title mb-0"><i class="ri-gift-line ri-16px me-2"></i>Generated Normal Gift Codes</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php foreach ($_SESSION['generated_serials'] as $serial): ?>
                                    <div class="col-md-6 mb-2">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <code class="serial-code"><?= $serial ?></code>
                                            <button class="btn btn-sm btn-outline-primary copy-btn" data-code="<?= $serial ?>">
                                                <i class="ri-file-copy-line ri-14px"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <?php unset($_SESSION['generated_serials']); ?>
                        <?php endif; ?>

                        <?php if (isset($_SESSION['generated_internal_serials'])): ?>
                        <div class="card mb-4">
                            <div class="card-header bg-info text-white">
                                <h5 class="card-title mb-0"><i class="ri-shield-keyhole-line ri-16px me-2"></i>Generated Internal Gift Codes</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php foreach ($_SESSION['generated_internal_serials'] as $serial): ?>
                                    <div class="col-md-6 mb-2">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <code class="serial-code"><?= $serial ?></code>
                                            <button class="btn btn-sm btn-outline-primary copy-btn" data-code="<?= $serial ?>">
                                                <i class="ri-file-copy-line ri-14px"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <?php unset($_SESSION['generated_internal_serials']); ?>
                        <?php endif; ?>

                        <div class="row">
                            <!-- Normal Gift Code Generator -->
                            <div class="col-md-6">
                                <div class="code-generator-section">
                                    <h4 class="text-primary mb-4"><i class="ri-gift-line ri-16px me-2"></i>Normal Gift Code Generator</h4>
                                    <form method="POST">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="maxserials" class="form-label">Number of Codes *</label>
                                                <input type="number" class="form-control" id="maxserials" name="maxserials" 
                                                       min="1" max="50" required placeholder="Max 50">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="maxusers" class="form-label">Max Users per Code *</label>
                                                <input type="number" class="form-control" id="maxusers" name="maxusers" 
                                                       min="1" required placeholder="Maximum users">
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="price" class="form-label">Price/Value *</label>
                                                <input type="number" step="0.01" class="form-control" id="price" name="price" 
                                                       required placeholder="0.00">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="remark" class="form-label">Remark</label>
                                                <input type="text" class="form-control" id="remark" name="remark" 
                                                       placeholder="Optional remark">
                                            </div>
                                        </div>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="ri-add-circle-line ri-16px me-2"></i>Generate Gift Codes
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <div class="col-md-6">
<div style="background:#fff;padding:20px;margin:20px;border-radius:10px;box-shadow:0 0 10px #ddd;">

<h2>⚡ Mottta + Turnover Combo Add</h2>

<?php if(isset($_SESSION['message'])){ ?>
    <p style="color:<?= $_SESSION['message_type']=='success'?'green':'red' ?>">
        <?= $_SESSION['message'] ?>
    </p>
<?php unset($_SESSION['message']); } ?>

<form method="POST">

    <input type="number" name="user_id" placeholder="User ID" required>
    <input type="number" step="0.01" name="amount" placeholder="Amount Add" required>

    <button type="submit" name="add"
        style="padding:10px 20px;background:#111;color:#fff;border:none;border-radius:5px;">
        ADD TO BOTH (MOTTA + TURNOVER)
    </button>

</form>

</div>               <!-- Internal Gift Code Generator -->
                            <div class="col-md-6">
                                <div class="internal-generator-section">
                                    <h4 class="text-success mb-4"><i class="ri-shield-keyhole-line ri-16px me-2"></i>Internal Gift Code Generator</h4>
                                    <form method="POST">
                                        <input type="hidden" name="generate_internal" value="1">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="internal_maxserials" class="form-label">Number of Codes *</label>
                                                <input type="number" class="form-control" id="internal_maxserials" name="internal_maxserials" 
                                                       min="1" max="50" required placeholder="Max 50">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="internal_maxusers" class="form-label">Max Users per Code *</label>
                                                <input type="number" class="form-control" id="internal_maxusers" name="internal_maxusers" 
                                                       min="1" required placeholder="Maximum users">
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label for="internal_price" class="form-label">Price/Value *</label>
                                                <input type="number" step="0.01" class="form-control" id="internal_price" name="internal_price" 
                                                       required placeholder="0.00">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="recharge_required" class="form-label">Recharge Required</label>
                                                <input type="number" step="0.01" class="form-control" id="recharge_required" name="recharge_required" 
                                                       placeholder="0.00">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="internal_remark" class="form-label">Remark</label>
                                                <input type="text" class="form-control" id="internal_remark" name="internal_remark" 
                                                       placeholder="Optional remark">
                                            </div>
                                        </div>
                                        <button type="submit" class="btn btn-success">
                                            <i class="ri-key-2-line ri-16px me-2"></i>Generate Internal Codes
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Normal Gift Codes History -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><i class="ri-history-line ri-16px me-2"></i>Normal Gift Codes History</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>#</th>
                                                <th>Gift Code</th>
                                                <th>Max Users</th>
                                                <th>Used Users</th>
                                                <th>Price</th>
                                                <th>Remark</th>
                                                <th>Created Date</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $count = ($page - 1) * $limit + 1;
                                            if (mysqli_num_rows($historyResult) > 0) {
                                                while ($row = mysqli_fetch_assoc($historyResult)) {
                                                    echo "<tr>";
                                                    echo "<td>" . $count++ . "</td>";
                                                    echo "<td><code class='serial-code'>" . $row['enserie'] . "</code></td>";
                                                    echo "<td>" . $row['utilisateurmax'] . "</td>";
                                                    echo "<td>" . $row['nombredutilisateurs'] . "</td>";
                                                    echo "<td>$" . number_format($row['prix'], 2) . "</td>";
                                                    echo "<td>" . htmlspecialchars($row['remark'] ?? 'N/A') . "</td>";
                                                    echo "<td>" . date('M d, Y H:i', strtotime($row['creerunrendezvous'])) . "</td>";
                                                    echo "<td>";
                                                    if ($row['nombredutilisateurs'] >= $row['utilisateurmax']) {
                                                        echo "<span class='badge badge-used'>Used</span>";
                                                    } else {
                                                        echo "<span class='badge badge-unused'>Available</span>";
                                                    }
                                                    echo "</td>";
                                                    echo "<td>
                                                        <form method='POST' style='display:inline;' onsubmit='return confirm(\"Are you sure you want to delete this gift code?\")'>
                                                            <input type='hidden' name='delete_serial' value='" . $row['enserie'] . "'>
                                                            <button type='submit' class='btn btn-danger btn-sm'><i class='ri-delete-bin-line ri-14px me-1'></i>Delete</button>
                                                        </form>
                                                    </td>";
                                                    echo "</tr>";
                                                }
                                            } else {
                                                echo "<tr><td colspan='9' class='text-center'>No gift codes found.</td></tr>";
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Pagination -->
                                <?php if ($totalPages > 1): ?>
                                <nav>
                                    <ul class="pagination">
                                        <?php if ($page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?= $page - 1 ?>">Previous</a>
                                            </li>
                                        <?php endif; ?>

                                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                                <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                            </li>
                                        <?php endfor; ?>

                                        <?php if ($page < $totalPages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?= $page + 1 ?>">Next</a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Internal Gift Codes -->
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <h5 class="card-title mb-0"><i class="ri-shield-keyhole-line ri-16px me-2"></i>Internal Gift Codes</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>#</th>
                                                <th>Gift Code</th>
                                                <th>Max Users</th>
                                                <th>Used Users</th>
                                                <th>Price</th>
                                                <th>Recharge Required</th>
                                                <th>Remark</th>
                                                <th>Created Date</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $internalCount = 1;
                                            if (mysqli_num_rows($internalCodes) > 0) {
                                                while ($row = mysqli_fetch_assoc($internalCodes)) {
                                                    echo "<tr>";
                                                    echo "<td>" . $internalCount++ . "</td>";
                                                    echo "<td><code class='serial-code'>" . $row['enserie'] . "</code></td>";
                                                    echo "<td>" . $row['utilisateurmax'] . "</td>";
                                                    echo "<td>" . $row['nombredutilisateurs'] . "</td>";
                                                    echo "<td>₹" . number_format($row['prix'], 2) . "</td>";
                                                    echo "<td>₹" . number_format($row['recharge_required'], 2) . "</td>";
                                                    echo "<td>" . htmlspecialchars($row['remark'] ?? 'N/A') . "</td>";
                                                    echo "<td>" . date('M d, Y H:i', strtotime($row['creerunrendezvous'])) . "</td>";
                                                    echo "<td>";
                                                    if ($row['nombredutilisateurs'] >= $row['utilisateurmax']) {
                                                        echo "<span class='badge badge-used'>Used</span>";
                                                    } else {
                                                        echo "<span class='badge badge-unused'>Available</span>";
                                                    }
                                                    echo "</td>";
                                                    echo "<td>
                                                        <form method='POST' style='display:inline;' onsubmit='return confirm(\"Are you sure you want to delete this internal gift code?\")'>
                                                            <input type='hidden' name='delete_serial' value='" . $row['enserie'] . "'>
                                                            <button type='submit' class='btn btn-danger btn-sm'><i class='ri-delete-bin-line ri-14px me-1'></i>Delete</button>
                                                        </form>
                                                    </td>";
                                                    echo "</tr>";
                                                }
                                            } else {
                                                echo "<tr><td colspan='10' class='text-center'>No internal gift codes found.</td></tr>";
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php require_once("footer.php"); ?>
                    <div class="content-backdrop fade"></div>
                </div>
            </div>
        </div>
        <div class="layout-overlay layout-menu-toggle"></div>
        <div class="drag-target"></div>
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

    <script>
        // Copy to clipboard functionality
        document.addEventListener('DOMContentLoaded', function() {
            const copyButtons = document.querySelectorAll('.copy-btn');
            
            copyButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const code = this.getAttribute('data-code');
                    navigator.clipboard.writeText(code).then(() => {
                        // Show temporary feedback
                        const originalHTML = this.innerHTML;
                        this.innerHTML = '<i class="ri-check-line ri-14px"></i>';
                        this.classList.remove('btn-outline-primary');
                        this.classList.add('btn-success');
                        
                        setTimeout(() => {
                            this.innerHTML = originalHTML;
                            this.classList.remove('btn-success');
                            this.classList.add('btn-outline-primary');
                        }, 2000);
                    });
                });
            });

            // Auto-hide alerts after 5 seconds
            setTimeout(() => {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(alert => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 5000);
        });
    </script>
</body>
</html>