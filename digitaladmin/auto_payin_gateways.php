<?php
session_start();
if(empty($_SESSION['unohs'])){
    header("location: api/login.php?msg=unauthorized");
}
include ("api/conn.php");

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_gateway'])) {
        $id = $_POST['gateway_id'];
        $api_url = $_POST['api_url'];
        $merchant_id = $_POST['merchant_id'];
        $secret_key = $_POST['secret_key'];
        $channel_code = $_POST['channel_code'];
        $display_name = $_POST['display_name'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        $stmt = $conn->prepare("UPDATE auto_payin_gateways SET api_url = ?, merchant_id = ?, secret_key = ?, channel_code = ?, display_name = ?, is_active = ? WHERE id = ?");
        $stmt->bind_param("sssssii", $api_url, $merchant_id, $secret_key, $channel_code, $display_name, $is_active, $id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Gateway updated successfully!";
        } else {
            $_SESSION['error'] = "Error updating gateway!";
        }
    }
    
    if (isset($_POST['toggle_status'])) {
        $id = $_POST['gateway_id'];
        $current_status = $_POST['current_status'];
        $new_status = $current_status ? 0 : 1;
        
        $stmt = $conn->prepare("UPDATE auto_payin_gateways SET is_active = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_status, $id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Gateway status updated successfully!";
        } else {
            $_SESSION['error'] = "Error updating gateway status!";
        }
    }
    
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}

// Get all gateways
$gateways = $conn->query("SELECT * FROM auto_payin_gateways ORDER BY display_name")->fetch_all(MYSQLI_ASSOC);
?>

<!doctype html>
<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr"
    data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template" data-style="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Auto PayIn Gateways Configuration</title>
    <meta name="description" content="" />
    <link rel="icon" type="image/x-icon" href="assets/img/favicon/favicon.ico?v=20260814-admin" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&ampdisplay=swap" rel="stylesheet" />
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
        .secret-key {
            font-family: 'Courier New', monospace;
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            padding: 8px 12px;
            border-radius: 6px;
            word-break: break-all;
        }
        .gateway-card {
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        .gateway-card:hover {
            border-color: #7367f0;
            transform: translateY(-2px);
        }
        .gateway-card.active {
            border-color: #28c76f;
        }
        .status-badge {
            font-size: 0.75rem;
        }
        .copy-btn {
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .copy-btn:hover {
            transform: scale(1.1);
        }
        .field-value {
            /*background: #f8f9fa;*/
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 8px 12px;
            font-family: 'Courier New', monospace;
            font-size: 0.875rem;
            word-break: break-all;
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
                        
                        <!-- Success/Error Messages -->
                        <?php if(isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible" role="alert">
                            <?= $_SESSION['success'] ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['success']); endif; ?>
                        
                        <?php if(isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible" role="alert">
                            <?= $_SESSION['error'] ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['error']); endif; ?>

                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="mb-0">Auto PayIn Gateways Configuration</h4>
                            <div class="d-flex gap-2">
                                <span class="text-muted">Total Gateways: <?= count($gateways) ?></span>
                                <span class="badge bg-label-success">Active: <?= array_sum(array_column($gateways, 'is_active')) ?></span>
                            </div>
                        </div>
                        
                        <!-- Statistics Cards -->
                        <div class="row mb-4">
                            <div class="col-lg-3 col-sm-6 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="card-title d-flex align-items-start justify-content-between">
                                            <div class="avatar flex-shrink-0">
                                                <div class="bg-label-primary rounded p-2">
                                                    <i class="ri-bank-card-line text-primary"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <span class="fw-semibold d-block mb-1">Total Gateways</span>
                                        <h3 class="card-title mb-2"><?= count($gateways) ?></h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-sm-6 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="card-title d-flex align-items-start justify-content-between">
                                            <div class="avatar flex-shrink-0">
                                                <div class="bg-label-success rounded p-2">
                                                    <i class="ri-checkbox-circle-line text-success"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <span class="fw-semibold d-block mb-1">Active Gateways</span>
                                        <h3 class="card-title mb-2"><?= array_sum(array_column($gateways, 'is_active')) ?></h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-sm-6 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="card-title d-flex align-items-start justify-content-between">
                                            <div class="avatar flex-shrink-0">
                                                <div class="bg-label-warning rounded p-2">
                                                    <i class="ri-pause-circle-line text-warning"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <span class="fw-semibold d-block mb-1">Inactive Gateways</span>
                                        <h3 class="card-title mb-2"><?= count($gateways) - array_sum(array_column($gateways, 'is_active')) ?></h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-sm-6 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="card-title d-flex align-items-start justify-content-between">
                                            <div class="avatar flex-shrink-0">
                                                <div class="bg-label-info rounded p-2">
                                                    <i class="ri-settings-3-line text-info"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <span class="fw-semibold d-block mb-1">Configuration</span>
                                        <h3 class="card-title mb-2">Ready</h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Gateway Cards -->
                        <div class="row">
                            <?php foreach($gateways as $gateway): ?>
                            <div class="col-xl-4 col-lg-6 col-md-6 mb-4">
                                <div class="card gateway-card <?= $gateway['is_active'] ? 'active' : '' ?>">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h5 class="card-title mb-0">
                                            <?= htmlspecialchars($gateway['display_name']) ?>
                                            <small class="text-muted d-block"><?= htmlspecialchars($gateway['gateway_name']) ?></small>
                                        </h5>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge <?= $gateway['is_active'] ? 'bg-label-success' : 'bg-label-secondary' ?> status-badge">
                                                <?= $gateway['is_active'] ? 'Active' : 'Inactive' ?>
                                            </span>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="gateway_id" value="<?= $gateway['id'] ?>">
                                                <input type="hidden" name="current_status" value="<?= $gateway['is_active'] ?>">
                                                <button type="submit" name="toggle_status" class="btn btn-sm <?= $gateway['is_active'] ? 'btn-outline-danger' : 'btn-outline-success' ?>">
                                                    <?= $gateway['is_active'] ? 'Deactivate' : 'Activate' ?>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <!-- API URL -->
                                        <div class="mb-3">
                                            <label class="form-label small text-muted mb-1 d-flex justify-content-between">
                                                <span>API URL</span>
                                                <i class="ri-file-copy-line copy-btn text-primary" data-value="<?= htmlspecialchars($gateway['api_url']) ?>" title="Copy API URL"></i>
                                            </label>
                                            <div class="field-value">
                                                <?= htmlspecialchars($gateway['api_url']) ?>
                                            </div>
                                        </div>
                                        
                                        <!-- Merchant ID -->
                                        <div class="mb-3">
                                            <label class="form-label small text-muted mb-1 d-flex justify-content-between">
                                                <span>Merchant ID</span>
                                                <i class="ri-file-copy-line copy-btn text-primary" data-value="<?= htmlspecialchars($gateway['merchant_id']) ?>" title="Copy Merchant ID"></i>
                                            </label>
                                            <div class="field-value">
                                                <?= htmlspecialchars($gateway['merchant_id']) ?>
                                            </div>
                                        </div>
                                        
                                        <!-- Channel Code -->
                                        <div class="mb-3">
                                            <label class="form-label small text-muted mb-1 d-flex justify-content-between">
                                                <span>Channel Code</span>
                                                <i class="ri-file-copy-line copy-btn text-primary" data-value="<?= htmlspecialchars($gateway['channel_code']) ?>" title="Copy Channel Code"></i>
                                            </label>
                                            <div class="field-value">
                                                <?= htmlspecialchars($gateway['channel_code']) ?>
                                            </div>
                                        </div>
                                        
                                        <!-- Secret Key -->
                                        <div class="mb-3">
                                            <label class="form-label small text-muted mb-1 d-flex justify-content-between">
                                                <span>Secret Key</span>
                                                <i class="ri-file-copy-line copy-btn text-primary" data-value="<?= htmlspecialchars($gateway['secret_key']) ?>" title="Copy Secret Key"></i>
                                            </label>
                                            <div class="field-value">
                                                <?= str_repeat('•', min(20, strlen($gateway['secret_key']))) ?>
                                                <small class="text-muted ms-2">(Click copy icon to copy)</small>
                                            </div>
                                        </div>
                                        
                                        <div class="mt-4">
                                            <button type="button" class="btn btn-primary btn-sm w-100" data-bs-toggle="modal" data-bs-target="#editGatewayModal" 
                                                data-gatewayid="<?= $gateway['id'] ?>"
                                                data-gatewayname="<?= htmlspecialchars($gateway['gateway_name']) ?>"
                                                data-displayname="<?= htmlspecialchars($gateway['display_name']) ?>"
                                                data-apiurl="<?= htmlspecialchars($gateway['api_url']) ?>"
                                                data-merchantid="<?= htmlspecialchars($gateway['merchant_id']) ?>"
                                                data-secretkey="<?= htmlspecialchars($gateway['secret_key']) ?>"
                                                data-channelcode="<?= htmlspecialchars($gateway['channel_code']) ?>"
                                                data-isactive="<?= $gateway['is_active'] ?>">
                                                <i class="ri-edit-line me-1"></i> Edit Configuration
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-footer">
                                        <small class="text-muted">
                                            Last updated: <?= date('M d, Y H:i', strtotime($gateway['updated_at'])) ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <!---->
                        <!---->
                    </div>
                    <?php require_once("footer.php"); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Gateway Modal -->
    <div class="modal fade" id="editGatewayModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Gateway Configuration - <span id="modal_gateway_name"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="gateway_id" id="edit_gateway_id">
                        <input type="hidden" name="update_gateway" value="1">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Gateway Name</label>
                                    <input type="text" class="form-control" id="edit_gateway_name" readonly>
                                    <div class="form-text">System gateway identifier</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Display Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="display_name" id="edit_display_name" required>
                                    <div class="form-text">User-friendly display name</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">API URL <span class="text-danger">*</span></label>
                            <input type="url" class="form-control" name="api_url" id="edit_api_url" required placeholder="https://api.example.com/v1/payment">
                            <div class="form-text">Full API endpoint URL for payment processing</div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Merchant ID <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="merchant_id" id="edit_merchant_id" required placeholder="MERCHANT_123456">
                                    <div class="form-text">Your unique merchant identifier</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Channel Code <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="channel_code" id="edit_channel_code" required placeholder="CHANNEL_001">
                                    <div class="form-text">Payment channel code provided by gateway</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Secret Key <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" class="form-control" name="secret_key" id="edit_secret_key" required placeholder="Enter secret key">
                                <button type="button" class="btn btn-outline-secondary" id="toggleSecretKey">
                                    <i class="ri-eye-line"></i>
                                </button>
                            </div>
                            <div class="form-text">
                                <i class="ri-information-line"></i> Keep this secret key secure and do not share it
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" id="edit_is_active">
                                <label class="form-check-label" for="edit_is_active">
                                    <strong>Activate this gateway</strong>
                                </label>
                            </div>
                            <div class="form-text">When active, this gateway will be available for payments</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="ri-save-line me-1"></i> Update Gateway
                        </button>
                    </div>
                </form>
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
    <script src="assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js"></script>
    <script src="assets/js/main.js"></script>

    <script>
        // Edit Gateway Modal
        $('#editGatewayModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var gatewayId = button.data('gatewayid');
            var gatewayName = button.data('gatewayname');
            var displayName = button.data('displayname');
            var apiUrl = button.data('apiurl');
            var merchantId = button.data('merchantid');
            var secretKey = button.data('secretkey');
            var channelCode = button.data('channelcode');
            var isActive = button.data('isactive');
            
            var modal = $(this);
            modal.find('#edit_gateway_id').val(gatewayId);
            modal.find('#edit_gateway_name').val(gatewayName);
            modal.find('#modal_gateway_name').text(gatewayName);
            modal.find('#edit_display_name').val(displayName);
            modal.find('#edit_api_url').val(apiUrl);
            modal.find('#edit_merchant_id').val(merchantId);
            modal.find('#edit_secret_key').val(secretKey);
            modal.find('#edit_channel_code').val(channelCode);
            modal.find('#edit_is_active').prop('checked', isActive == 1);
        });

        // Toggle secret key visibility
        $('#toggleSecretKey').click(function() {
            var secretKeyInput = $('#edit_secret_key');
            var type = secretKeyInput.attr('type');
            if (type === 'password') {
                secretKeyInput.attr('type', 'text');
                $(this).html('<i class="ri-eye-off-line"></i>');
            } else {
                secretKeyInput.attr('type', 'password');
                $(this).html('<i class="ri-eye-line"></i>');
            }
        });

        // Copy to clipboard functionality
        $('.copy-btn').click(function() {
            var value = $(this).data('value');
            var tempInput = $('<input>');
            $('body').append(tempInput);
            tempInput.val(value).select();
            document.execCommand('copy');
            tempInput.remove();
            
            // Show copied feedback
            var originalIcon = $(this).html();
            $(this).html('<i class="ri-check-line text-success"></i>');
            setTimeout(() => {
                $(this).html(originalIcon);
            }, 2000);
            
            // Show toast notification
            showToast('Copied to clipboard!');
        });

        // Simple toast notification
        function showToast(message) {
            var toast = $('<div class="alert alert-success alert-dismissible position-fixed top-0 end-0 m-3" style="z-index: 9999">' +
                         '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                         message +
                         '</div>');
            $('body').append(toast);
            setTimeout(() => {
                toast.alert('close');
            }, 2000);
        }

        // Form validation
        $('form').on('submit', function() {
            var apiUrl = $('#edit_api_url').val();
            var merchantId = $('#edit_merchant_id').val();
            var secretKey = $('#edit_secret_key').val();
            var channelCode = $('#edit_channel_code').val();
            
            if (!apiUrl || !merchantId || !secretKey || !channelCode) {
                alert('Please fill all required fields');
                return false;
            }
            
            return true;
        });
    </script>
</body>
</html>