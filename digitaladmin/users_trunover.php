<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['unohs']) || empty($_SESSION['unohs'])) {
    header("location: api/login.php?msg=unauthorized");
    exit();
}

include("api/conn.php");

mysqli_query($conn, "SET NAMES 'utf8mb4'");
mysqli_query($conn, "SET CHARACTER SET utf8mb4");
mysqli_query($conn, "SET SESSION collation_connection = 'utf8mb4_unicode_ci'");

$uid = isset($_GET['uid']) ? intval($_GET['uid']) : 0;
$message = '';
$message_type = '';

/* ------------------ Handle Form Submission ------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adjust_uid'])) {
    $adjust_uid = intval($_POST['adjust_uid']);
    $wallet_balance = round((float)$_POST['wallet_balance'], 2);
    $turnover = round((float)$_POST['turnover'], 2);

    $checkWallet = mysqli_query($conn, "SELECT balakedara FROM shonu_kaichila WHERE balakedara = '{$adjust_uid}'");
    
    if (mysqli_num_rows($checkWallet) > 0) {
        $sql = "UPDATE shonu_kaichila SET motta = '{$wallet_balance}', mottta = '{$turnover}' WHERE balakedara = '{$adjust_uid}'";
    } else {
        $sql = "INSERT INTO shonu_kaichila (balakedara, motta, mottta) VALUES ('{$adjust_uid}', '{$wallet_balance}', '{$turnover}')";
    }

    if (mysqli_query($conn, $sql)) {
        $message = 'User wallet and turnover data updated successfully!';
        $message_type = 'success';
    } else {
        $message = 'Update failed: ' . mysqli_error($conn);
        $message_type = 'danger';
    }

    $uid = $adjust_uid;
}

/* ------------------ Fetch User Data ------------------ */
$userData = null;
$walletBalance = 0.00;
$firstDeposit = 0.00;
$totalDeposit = 0.00;
$totalBet = 0.00;
$totalWithdrawal = 0.00;
$userTurnover = 0.00;

if ($uid > 0) {
    // ১. প্রথমে চেক করছি মূল টেবিলে ইউজার আছে কিনা (এটি ডিলিট হয়নি)
    $userCheck = mysqli_query($conn, "SELECT id, mobile FROM shonu_subjects WHERE id = '{$uid}'");
    if (mysqli_num_rows($userCheck) > 0) {
        $userData = mysqli_fetch_assoc($userCheck);
        
        // ২. ব্যালেন্স টেবিল থেকে ডাটা চেক করছি
        $walletQuery = mysqli_query($conn, "SELECT motta, mottta FROM shonu_kaichila WHERE balakedara = '{$uid}'");
        if ($walletQuery && mysqli_num_rows($walletQuery) > 0) {
            $walletData = mysqli_fetch_assoc($walletQuery);
            $walletBalance = round((float)($walletData['motta'] ?? 0), 2);
            $userTurnover = round((float)($walletData['mottta'] ?? 0), 2);
        } else {
            // যদি নতুন টেবিলে এই ইউজারের ডাটা না থাকে, তবে স্বয়ংক্রিয়ভাবে একটা রো তৈরি করে নেবে
            mysqli_query($conn, "INSERT IGNORE INTO shonu_kaichila (balakedara, motta, mottta) VALUES ('{$uid}', 0.00, 0.00)");
            $walletBalance = 0.00;
            $userTurnover = 0.00;
        }

        // Get first deposit
        $firstDepositQuery = mysqli_query($conn, "SELECT MIN(motta) as first_deposit FROM thevani WHERE sthiti = '1' AND balakedara = '{$uid}'");
        if ($firstDepositQuery && mysqli_num_rows($firstDepositQuery) > 0) {
            $depositData = mysqli_fetch_assoc($firstDepositQuery);
            $firstDeposit = round((float)($depositData['first_deposit'] ?? 0), 2);
        }

        // Total Deposit
        $totalDepositQuery = mysqli_query($conn, "SELECT SUM(motta) as total_deposit FROM thevani WHERE sthiti = '1' AND balakedara = '{$uid}'");
        if ($totalDepositQuery && mysqli_num_rows($totalDepositQuery) > 0) {
            $depositData = mysqli_fetch_assoc($totalDepositQuery);
            $totalDeposit = round((float)($depositData['total_deposit'] ?? 0), 2);
        }

        // Total Bet
        $betTables = [
            'bajikattuttate', 'bajikattuttate_drei', 'bajikattuttate_funf', 'bajikattuttate_zehn',
            'bajikattuttate_kemuru', 'bajikattuttate_kemuru_drei', 'bajikattuttate_kemuru_funf', 'bajikattuttate_kemuru_zehn',
            'bajikattuttate_aidudi', 'bajikattuttate_aidudi_drei', 'bajikattuttate_aidudi_funf', 'bajikattuttate_aidudi_zehn'
        ];

        foreach ($betTables as $table) {
            $betQuery = mysqli_query($conn, "SELECT SUM(ketebida) as total_bet FROM {$table} WHERE byabaharkarta = '{$uid}'");
            if ($betQuery && mysqli_num_rows($betQuery) > 0) {
                $betData = mysqli_fetch_assoc($betQuery);
                $totalBet += round((float)($betData['total_bet'] ?? 0), 2);
            }
        }

        // Get total withdrawal
        $withdrawalQuery = mysqli_query($conn, "SELECT SUM(motta) as total_withdrawal FROM hintegedukolli WHERE sthiti = '1' AND balakedara = '{$uid}'");
        if ($withdrawalQuery && mysqli_num_rows($withdrawalQuery) > 0) {
            $withdrawalData = mysqli_fetch_assoc($withdrawalQuery);
            $totalWithdrawal = round((float)($withdrawalData['total_withdrawal'] ?? 0), 2);
        }

    } else {
        $message = 'User not found in main database!';
        $message_type = 'danger';
    }
}
?>

<!DOCTYPE html>
<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr" data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template" data-style="light">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>User Wallet Management</title>
    <link rel="icon" type="image/x-icon" href="assets/img/favicon/favicon.ico?v=20260814-admin" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet" />
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
        .stats-card { background: white; border-radius: 10px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; border-left: 4px solid #667eea; }
        .stats-value { font-size: 24px; font-weight: bold; color: #667eea; display: block; }
        .stats-label { font-size: 14px; color: #6c757d; margin-bottom: 10px; }
        .user-info-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 10px; padding: 20px; margin-bottom: 20px; }
        .amount-input { font-size: 16px; font-weight: 600; text-align: right; }
        .quick-actions { display: flex; gap: 10px; margin-top: 10px; }
        .quick-btn { flex: 1; padding: 8px 12px; border: 1px solid #dee2e6; background: white; border-radius: 5px; cursor: pointer; font-size: 12px; text-align: center; }
        .quick-btn:hover { background: #f8f9fa; border-color: #667eea; }
        .toggle-container { display: flex; align-items: center; gap: 10px; margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #ff6b6b; }
        .toggle-switch { position: relative; display: inline-block; width: 60px; height: 34px; }
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        .toggle-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 34px; }
        .toggle-slider:before { position: absolute; content: ""; height: 26px; width: 26px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .toggle-slider { background-color: #28a745; }
        input:checked + .toggle-slider:before { transform: translateX(26px); }
        .toggle-label { font-weight: 600; color: #495057; }
        .toggle-status { font-size: 14px; color: #6c757d; }
        .manual-fields { transition: all 0.3s ease; }
        .manual-fields.disabled { opacity: 0.6; pointer-events: none; }
        .override-warning { background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px; padding: 10px; margin-bottom: 15px; font-size: 14px; color: #856404; }
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
                        
                        <?php if ($message): ?>
                        <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                            <?= $message ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php endif; ?>

                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h4 class="card-title mb-0">
                                            <i class="ri-wallet-3-line ri-16px me-2"></i>User Wallet Management
                                        </h4>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="card mb-4">
                                    <div class="card-body">
                                        <h5 class="mb-3"><i class="ri-search-line ri-16px me-2"></i>Search User</h5>
                                        <form method="GET" class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="uid" class="form-label">Enter User ID</label>
                                                <input type="number" class="form-control" id="uid" name="uid" value="<?= htmlspecialchars($uid) ?>" placeholder="Enter user ID to manage wallet" required>
                                            </div>
                                            <div class="col-md-6 mb-3 d-flex align-items-end">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="ri-user-search-line ri-16px me-2"></i>Search User
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if ($uid > 0 && $userData): ?>
                        <div class="row">
                            <div class="col-12">
                                <div class="user-info-card">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h5 class="text-white">User Information</h5>
                                            <p class="mb-1"><strong>User ID:</strong> <?= $userData['id'] ?></p>
                                            <p class="mb-0"><strong>Mobile:</strong> <?= htmlspecialchars($userData['mobile']) ?></p>
                                        </div>
                                        <div class="col-md-6 text-end">
                                            <h5 class="text-white">Current Balance</h5>
                                            <h2 class="text-white mb-0">₹<?= number_format($walletBalance, 2) ?></h2>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3">
                                <div class="stats-card">
                                    <span class="stats-label">First Deposit</span>
                                    <span class="stats-value">₹<?= number_format($firstDeposit, 2) ?></span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card">
                                    <span class="stats-label">Total Deposit</span>
                                    <span class="stats-value">₹<?= number_format($totalDeposit, 2) ?></span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card" style="border-left-color: #28a745;">
                                    <span class="stats-label">Total Bet</span>
                                    <span class="stats-value" style="color: #28a745;">₹<?= number_format($totalBet, 2) ?></span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card" style="border-left-color: #ff6b6b;">
                                    <span class="stats-label">Total Withdrawal</span>
                                    <span class="stats-value" style="color: #ff6b6b;">₹<?= number_format($totalWithdrawal, 2) ?></span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card" style="border-left-color: #ffd700;">
                                    <span class="stats-label">Current Turnover</span>
                                    <span class="stats-value" style="color: #ffd700;">₹<?= number_format($userTurnover, 2) ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0"><i class="ri-edit-line ri-16px me-2"></i>Edit User Wallet Data</h5>
                                    </div>
                                    <div class="card-body">
                                        <form method="POST">
                                            <input type="hidden" name="adjust_uid" value="<?= $uid ?>">
                                            
                                            <div class="override-warning" id="overrideWarning" style="display: none;">
                                                <i class="ri-alert-line ri-16px me-2"></i><strong>Manual Override Enabled:</strong> You can now edit historical transaction data. Use with caution!
                                            </div>

                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label for="wallet_balance" class="form-label">Wallet Balance</label>
                                                    <input type="number" step="0.01" class="form-control amount-input" id="wallet_balance" name="wallet_balance" value="<?= $walletBalance ?>" required>
                                                    <div class="quick-actions">
                                                        <button type="button" class="quick-btn" onclick="updateAmount('wallet_balance', 100)">+100</button>
                                                        <button type="button" class="quick-btn" onclick="updateAmount('wallet_balance', 500)">+500</button>
                                                        <button type="button" class="quick-btn" onclick="updateAmount('wallet_balance', 1000)">+1000</button>
                                                        <button type="button" class="quick-btn" onclick="updateAmount('wallet_balance', 0)">Reset</button>
                                                    </div>
                                                </div>

                                                <div class="col-md-6 mb-3">
                                                    <label for="turnover" class="form-label">Turnover (Need to Bet)</label>
                                                    <input type="number" step="0.01" class="form-control amount-input" id="turnover" name="turnover" value="<?= $userTurnover ?>" required>
                                                    <div class="quick-actions">
                                                        <button type="button" class="quick-btn" onclick="updateAmount('turnover', 100)">+100</button>
                                                        <button type="button" class="quick-btn" onclick="updateAmount('turnover', 500)">+500</button>
                                                        <button type="button" class="quick-btn" onclick="updateAmount('turnover', 1000)">+1000</button>
                                                        <button type="button" class="quick-btn" onclick="updateAmount('turnover', 0)">Reset</button>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="toggle-container">
                                                <label class="toggle-switch">
                                                    <input type="checkbox" id="manual_override" name="manual_override" value="1">
                                                    <span class="toggle-slider"></span>
                                                </label>
                                                <div>
                                                    <div class="toggle-label">Manual Override Mode</div>
                                                    <div class="toggle-status" id="toggleStatus">OFF - Historical data fields are view-only</div>
                                                </div>
                                            </div>

                                            <div class="manual-fields" id="manualFields">
                                                <div class="row">
                                                    <div class="col-md-4 mb-3">
                                                        <label for="first_deposit" class="form-label">First Deposit</label>
                                                        <input type="number" step="0.01" class="form-control amount-input" id="first_deposit" name="first_deposit" value="<?= $firstDeposit ?>" readonly>
                                                    </div>
                                                    <div class="col-md-4 mb-3">
                                                        <label for="total_bet" class="form-label">Total Bet</label>
                                                        <input type="number" step="0.01" class="form-control amount-input" id="total_bet" name="total_bet" value="<?= $totalBet ?>" readonly>
                                                    </div>
                                                    <div class="col-md-4 mb-3">
                                                        <label for="total_withdrawal" class="form-label">Total Withdrawal</label>
                                                        <input type="number" step="0.01" class="form-control amount-input" id="total_withdrawal" name="total_withdrawal" value="<?= $totalWithdrawal ?>" readonly>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-12">
                                                    <button type="submit" class="btn btn-success btn-lg"><i class="ri-save-line ri-16px me-2"></i>Save Changes</button>
                                                    <button type="button" class="btn btn-secondary" onclick="resetForm()"><i class="ri-refresh-line ri-16px me-2"></i>Reset Form</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0"><i class="ri-history-line ri-16px me-2"></i>Quick Actions</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-3 text-center"><button class="btn btn-outline-primary w-100 mb-2" onclick="setAmount('wallet_balance', 0)">Set Balance to 0</button></div>
                                            <div class="col-md-3 text-center"><button class="btn btn-outline-success w-100 mb-2" onclick="setAmount('turnover', 0)">Clear Turnover</button></div>
                                            <div class="col-md-3 text-center"><button class="btn btn-outline-info w-100 mb-2" onclick="copyBalanceToTurnover()">Copy Balance to Turnover</button></div>
                                            <div class="col-md-3 text-center"><button class="btn btn-outline-warning w-100 mb-2" onclick="resetAll()">Reset All Fields</button></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php elseif ($uid > 0): ?>
                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-body text-center py-5">
                                        <i class="ri-user-unfollow-line ri-4x text-danger mb-3"></i>
                                        <h5 class="text-danger">User Not Found</h5>
                                        <p class="text-muted">No user found with ID: <?= $uid ?></p>
                                        <a href="?uid=" class="btn btn-primary">Search Another User</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-body text-center py-5">
                                        <i class="ri-wallet-3-line ri-4x text-muted mb-3"></i>
                                        <h5 class="text-muted">Enter a User ID to manage wallet</h5>
                                        <p class="text-muted">Search for a user by their ID to view and edit wallet information</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                    </div>
                    <?php require_once("footer.php"); ?>
                    <div class="content-backdrop fade"></div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/vendor/libs/jquery/jquery.js"></script>
    <script src="assets/vendor/libs/popper/popper.js"></script>
    <script src="assets/vendor/js/bootstrap.js"></script>
    <script src="assets/vendor/libs/node-waves/node-waves.js"></script>
    <script src="assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="assets/vendor/js/menu.js"></script>
    <script src="assets/js/main.js"></script>

    <script>
        const toggleSwitch = document.getElementById('manual_override');
        const toggleStatus = document.getElementById('toggleStatus');
        const overrideWarning = document.getElementById('overrideWarning');
        const manualFields = document.getElementById('manualFields');
        const historicalInputs = document.querySelectorAll('#first_deposit, #total_bet, #total_withdrawal');

        toggleSwitch.addEventListener('change', function() {
            if (this.checked) {
                toggleStatus.textContent = 'ON - Historical data fields are now editable';
                toggleStatus.style.color = '#28a745';
                overrideWarning.style.display = 'block';
                manualFields.classList.remove('disabled');
                historicalInputs.forEach(input => { input.readOnly = false; input.style.backgroundColor = '#fff'; });
            } else {
                toggleStatus.textContent = 'OFF - Historical data fields are view-only';
                toggleStatus.style.color = '#6c757d';
                overrideWarning.style.display = 'none';
                manualFields.classList.add('disabled');
                historicalInputs.forEach(input => { input.readOnly = true; input.style.backgroundColor = '#f8f9fa'; });
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            manualFields.classList.add('disabled');
            historicalInputs.forEach(input => { input.style.backgroundColor = '#f8f9fa'; });
        });

        function updateAmount(fieldId, amount) {
            const field = document.getElementById(fieldId);
            if (amount === 0) {
                field.value = '0.00';
            } else {
                const currentValue = parseFloat(field.value) || 0;
                field.value = (currentValue + amount).toFixed(2);
            }
        }

        function setAmount(fieldId, amount) {
            document.getElementById(fieldId).value = amount.toFixed(2);
        }

        function copyBalanceToTurnover() {
            const balance = document.getElementById('wallet_balance').value;
            document.getElementById('turnover').value = balance;
        }

        function resetForm() {
            if (confirm('Are you sure you want to reset all fields?')) {
                document.querySelector('form').reset();
                toggleSwitch.checked = false;
                toggleSwitch.dispatchEvent(new Event('change'));
            }
        }

        function resetAll() {
            if (confirm('Are you sure you want to reset ALL fields to zero?')) {
                setAmount('wallet_balance', 0);
                setAmount('turnover', 0);
                setAmount('first_deposit', 0);
                setAmount('total_bet', 0);
                setAmount('total_withdrawal', 0);
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const amountInputs = document.querySelectorAll('.amount-input');
            amountInputs.forEach(input => {
                input.addEventListener('blur', function() {
                    const value = parseFloat(this.value);
                    if (!isNaN(value)) { this.value = value.toFixed(2); }
                });
            });
        });
    </script>
</body>
</html>
