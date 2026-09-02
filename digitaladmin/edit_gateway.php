<?php
session_start();
if(empty($_SESSION['unohs'])){
    header("location: api/login.php?msg=unauthorized");
}
include ("api/conn.php");

error_reporting(E_ALL);
ini_set('display_errors', 1);

$gatewayId = $_GET['id'] ?? 0;
$gateway = DB_selectOne("SELECT * FROM payment_channels WHERE id = ?", [$gatewayId]);
$amounts = DB_select("SELECT * FROM payment_channel_amounts WHERE channel_id = ?", [$gatewayId]);

if (!$gateway) {
    header("Location: payment_gateways.php?error=Gateway+not+found");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        $_POST['payTypeID'],
        $_POST['payName'],
        $_POST['paySysName'],
        $_POST['miniPrice'],
        $_POST['maxPrice'],
        $_POST['scope'],
        $_POST['paySendUrl'],
        isset($_POST['is_active']) ? 1 : 0,
        $gatewayId
    ];
    
    // Update gateway
    DB_update("
        UPDATE payment_channels SET 
        payTypeID = ?,
        payName = ?,
        paySysName = ?,
        miniPrice = ?,
        maxPrice = ?,
        scope = ?,
        paySendUrl = ?,
        is_active = ?
        WHERE id = ?
    ", $data);
    
    // Delete existing amounts
    DB_delete("DELETE FROM payment_channel_amounts WHERE channel_id = ?", [$gatewayId]);
    
    // Insert new amounts
    foreach ($_POST['amounts'] as $amount) {
        if (!empty($amount['rechargeAmount'])) {
            DB_insert("
                INSERT INTO payment_channel_amounts (channel_id, rechargeAmount, giftAmount) 
                VALUES (?, ?, ?)
            ", [
                $gatewayId, 
                $amount['rechargeAmount'], 
                $amount['giftAmount'] ?? 0
            ]);
        }
    }
    
    header("Location: payment_gateways.php?success=Gateway+updated");
    exit;
}
?>

<!doctype html>
<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr"
    data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template" data-style="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

    <title>Edit Payment Gateway</title>

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
    <link rel="stylesheet" href="assets/vendor/libs/select2/select2.css" />

    <!-- Helpers -->
    <script src="assets/vendor/js/helpers.js"></script>
    <script src="assets/vendor/js/template-customizer.js"></script>
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
                    <div class="container-xxl flex-grow-1 container-p-y">
                        <h4 class="fw-bold py-3 mb-4">
                            <span class="text-muted fw-light">Payment Gateways /</span> Edit Gateway
                        </h4>

                        <div class="card mb-4">
                            <div class="card-body">
                                <form method="POST">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Gateway Name</label>
                                                <input type="text" name="payName" class="form-control" 
                                                    value="<?= htmlspecialchars($gateway['payName']) ?>" required>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">System Name</label>
                                                <input type="text" name="paySysName" class="form-control" 
                                                    value="<?= htmlspecialchars($gateway['paySysName']) ?>" required>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Pay Type ID</label>
                                                <input type="number" name="payTypeID" class="form-control" 
                                                    value="<?= htmlspecialchars($gateway['payTypeID']) ?>" required>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Minimum Amount</label>
                                                <input type="number" name="miniPrice" class="form-control" 
                                                    value="<?= htmlspecialchars($gateway['miniPrice']) ?>" required>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Maximum Amount</label>
                                                <input type="number" name="maxPrice" class="form-control" 
                                                    value="<?= htmlspecialchars($gateway['maxPrice']) ?>" required>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Scope (pipe separated)</label>
                                                <input type="text" name="scope" class="form-control" 
                                                    value="<?= htmlspecialchars($gateway['scope']) ?>" required>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Payment Endpoint URL</label>
                                        <input type="text" name="paySendUrl" class="form-control" 
                                            value="<?= htmlspecialchars($gateway['paySendUrl']) ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Quick Amounts</label>
                                        <div id="amounts-container">
                                            <?php foreach ($amounts as $index => $amount): ?>
                                            <div class="row mb-2 amount-row">
                                                <div class="col-md-5">
                                                    <input type="number" name="amounts[<?= $index ?>][rechargeAmount]" class="form-control" 
                                                        value="<?= $amount['rechargeAmount'] ?>" placeholder="Amount" step="0.01" required>
                                                </div>
                                                <div class="col-md-5">
                                                    <input type="number" name="amounts[<?= $index ?>][giftAmount]" class="form-control" 
                                                        value="<?= $amount['giftAmount'] ?>" placeholder="Bonus" step="0.01">
                                                </div>
                                                <div class="col-md-2">
                                                    <button type="button" class="btn btn-danger remove-amount">
                                                        <i class="ri-delete-bin-line"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                            <?php if (empty($amounts)): ?>
                                            <div class="row mb-2 amount-row">
                                                <div class="col-md-5">
                                                    <input type="number" name="amounts[0][rechargeAmount]" class="form-control" placeholder="Amount" step="0.01" required>
                                                </div>
                                                <div class="col-md-5">
                                                    <input type="number" name="amounts[0][giftAmount]" class="form-control" placeholder="Bonus" step="0.01" value="0">
                                                </div>
                                                <div class="col-md-2">
                                                    <button type="button" class="btn btn-danger remove-amount">
                                                        <i class="ri-delete-bin-line"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        <button type="button" id="add-amount" class="btn btn-secondary mt-2">
                                            <i class="ri-add-line me-1"></i> Add Amount
                                        </button>
                                    </div>
                                    
                                    <div class="mb-3 form-check form-switch">
                                        <input type="checkbox" name="is_active" class="form-check-input" id="is_active" 
                                            <?= $gateway['is_active'] ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="is_active">Active</label>
                                    </div>
                                    
                                    <div class="mt-4">
                                        <button type="submit" class="btn btn-primary me-2">
                                            <i class="ri-save-line me-1"></i> Update Gateway
                                        </button>
                                        <a href="payment_gateways.php" class="btn btn-outline-secondary">
                                            <i class="ri-arrow-go-back-line me-1"></i> Cancel
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
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
    </div>
    <!-- / Layout wrapper -->

    <!-- Core JS -->
    <script src="assets/vendor/libs/jquery/jquery.js"></script>
    <script src="assets/vendor/libs/popper/popper.js"></script>
    <script src="assets/vendor/js/bootstrap.js"></script>
    <script src="assets/vendor/libs/node-waves/node-waves.js"></script>
    <script src="assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="assets/vendor/js/menu.js"></script>

    <!-- Main JS -->
    <script src="assets/js/main.js"></script>

    <script>
        let amountCounter = <?= count($amounts) ?: 1 ?>;
        
        document.getElementById('add-amount').addEventListener('click', function() {
            const container = document.getElementById('amounts-container');
            const newRow = document.createElement('div');
            newRow.className = 'row mb-2 amount-row';
            newRow.innerHTML = `
                <div class="col-md-5">
                    <input type="number" name="amounts[${amountCounter}][rechargeAmount]" class="form-control" placeholder="Amount" step="0.01" required>
                </div>
                <div class="col-md-5">
                    <input type="number" name="amounts[${amountCounter}][giftAmount]" class="form-control" placeholder="Bonus" step="0.01" value="0">
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-danger remove-amount">
                        <i class="ri-delete-bin-line"></i>
                    </button>
                </div>
            `;
            container.appendChild(newRow);
            amountCounter++;
        });
        
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-amount') || e.target.closest('.remove-amount')) {
                const btn = e.target.classList.contains('remove-amount') ? e.target : e.target.closest('.remove-amount');
                btn.closest('.amount-row').remove();
            }
        });
    </script>
</body>
</html>