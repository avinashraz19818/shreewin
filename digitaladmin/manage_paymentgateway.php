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

// ✅ Handle AJAX Requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ✅ Fetch Data from Database
    if ($action === 'fetch') {
        $data = [];
        $sql = "SELECT * FROM payment_methods";
        if ($result = $conn->query($sql)) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            $result->free();
        }
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();

    // ✅ Update single row
    } elseif ($action === 'update') {
        // 📎 Securely Fetching Input Data
        $id            = intval($_POST['id'] ?? 0);
        $payName       = $conn->real_escape_string($_POST['payName'] ?? '');
        $miniPrice     = floatval($_POST['miniPrice'] ?? 0);
        $maxPrice      = floatval($_POST['maxPrice'] ?? 0);
        $scope         = $conn->real_escape_string($_POST['scope'] ?? '');
        $paySendUrl    = $conn->real_escape_string($_POST['paySendUrl'] ?? '');
        $startTime     = $conn->real_escape_string($_POST['startTime'] ?? '');
        $endTime       = $conn->real_escape_string($_POST['endTime'] ?? '');
        $rechargeRifts = floatval($_POST['rechargeRifts'] ?? 0);
        $status        = $conn->real_escape_string($_POST['status'] ?? 'inactive');

        $sql = "UPDATE payment_methods SET 
                    payName = ?, miniPrice = ?, maxPrice = ?, scope = ?, paySendUrl = ?, 
                    startTime = ?, endTime = ?, rechargeRifts = ?, status = ? 
                WHERE id = ?";

        $stmt = $conn->prepare($sql);
        header('Content-Type: application/json');
        if ($stmt) {
            $stmt->bind_param(
                "sddssssdsd",
                $payName, $miniPrice, $maxPrice, $scope, $paySendUrl,
                $startTime, $endTime, $rechargeRifts, $status, $id
            );
            if ($stmt->execute()) {
                echo json_encode(["success" => true]);
            } else {
                echo json_encode(["success" => false, "error" => "Database update failed: " . $stmt->error]);
            }
            $stmt->close();
        } else {
            echo json_encode(["success" => false, "error" => "Database statement preparation failed: " . $conn->error]);
        }
        exit();

    // ✅ Global update (Apply to All)
    } elseif ($action === 'update_all') {
        header('Content-Type: application/json');

        // Raw inputs
        $paySendUrl_in    = $_POST['paySendUrl'] ?? null;          // string or ''
        $rechargeRifts_in = $_POST['rechargeRifts'] ?? null;       // string may be '', keep raw
        $miniPrice_in     = $_POST['miniPrice'] ?? null;           // string may be '', keep raw

        // Build dynamic SET only for provided values
        $sets  = [];
        $types = '';
        $vals  = [];

        // paySendUrl: only if non-empty string provided
        if ($paySendUrl_in !== null && $paySendUrl_in !== '') {
            $sets[] = 'paySendUrl = ?';
            $types .= 's';
            $vals[] = $paySendUrl_in;
        }
        // rechargeRifts: allow 0; skip only if empty string
        if ($rechargeRifts_in !== null && $rechargeRifts_in !== '') {
            $sets[] = 'rechargeRifts = ?';
            $types .= 'd';
            $vals[] = floatval($rechargeRifts_in);
        }
        // miniPrice: allow 0; skip only if empty string
        if ($miniPrice_in !== null && $miniPrice_in !== '') {
            $sets[] = 'miniPrice = ?';
            $types .= 'd';
            $vals[] = floatval($miniPrice_in);
        }

        if (empty($sets)) {
            echo json_encode(["success" => false, "error" => "No fields provided. Nothing to update."]);
            exit();
        }

        $sql = "UPDATE payment_methods SET " . implode(', ', $sets);
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            echo json_encode(["success" => false, "error" => "Prepare failed: " . $conn->error]);
            exit();
        }

        // bind dynamically
        $bind_params = array_merge([$types], $vals);
        $ref = [];
        foreach ($bind_params as $k => $v) { $ref[$k] = &$bind_params[$k]; }
        call_user_func_array([$stmt, 'bind_param'], $ref);

        if ($stmt->execute()) {
            echo json_encode([
                "success" => true,
                "updated_rows" => $stmt->affected_rows
            ]);
        } else {
            echo json_encode(["success" => false, "error" => "Execute failed: " . $stmt->error]);
        }
        $stmt->close();
        exit();

    // ✅ Add new payment method
    } elseif ($action === 'add') {
        $payName       = $conn->real_escape_string($_POST['payName'] ?? '');
        $miniPrice     = floatval($_POST['miniPrice'] ?? 0);
        $maxPrice      = floatval($_POST['maxPrice'] ?? 0);
        $scope         = $conn->real_escape_string($_POST['scope'] ?? '');
        $paySendUrl    = $conn->real_escape_string($_POST['paySendUrl'] ?? '');
        $startTime     = $conn->real_escape_string($_POST['startTime'] ?? '');
        $endTime       = $conn->real_escape_string($_POST['endTime'] ?? '');
        $rechargeRifts = floatval($_POST['rechargeRifts'] ?? 0);
        $status        = $conn->real_escape_string($_POST['status'] ?? 'inactive');

        $sql = "INSERT INTO payment_methods (payName, miniPrice, maxPrice, scope, paySendUrl, startTime, endTime, rechargeRifts, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        header('Content-Type: application/json');
        if ($stmt) {
            $stmt->bind_param(
                "sddssssds",
                $payName, $miniPrice, $maxPrice, $scope, $paySendUrl,
                $startTime, $endTime, $rechargeRifts, $status
            );
            if ($stmt->execute()) {
                echo json_encode(["success" => true, "id" => $conn->insert_id]);
            } else {
                echo json_encode(["success" => false, "error" => "Database insert failed: " . $stmt->error]);
            }
            $stmt->close();
        } else {
            echo json_encode(["success" => false, "error" => "Database statement preparation failed: " . $conn->error]);
        }
        exit();

    // ✅ Delete payment method
    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        
        $sql = "DELETE FROM payment_methods WHERE id = ?";
        $stmt = $conn->prepare($sql);
        header('Content-Type: application/json');
        if ($stmt) {
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                echo json_encode(["success" => true]);
            } else {
                echo json_encode(["success" => false, "error" => "Database delete failed: " . $stmt->error]);
            }
            $stmt->close();
        } else {
            echo json_encode(["success" => false, "error" => "Database statement preparation failed: " . $conn->error]);
        }
        exit();
    }
}

// Fetch initial data for display
$payment_methods = [];
$sql = "SELECT * FROM payment_methods ORDER BY id DESC";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $payment_methods[] = $row;
    }
    $result->free();
}

// Statistics
$total_methods = count($payment_methods);
$active_methods = 0;
foreach ($payment_methods as $method) {
    if ($method['status'] === 'active') {
        $active_methods++;
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
    <title>Payment Gateway Management</title>
    <meta name="description" content="" />
    <link rel="icon" type="image/x-icon" href="assets/img/favicon/favicon.ico?v=20260814-admin" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&ampdisplay=swap"
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
        .payment-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            /*background: #fff;*/
            transition: all 0.3s ease;
        }
        .payment-card.inactive {
            opacity: 0.7;
            /*background: #f8f9fa;*/
        }
        .payment-card:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .stats-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            /*background: #fff;*/
            text-align: center;
        }
        .stats-value {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
        }
        .stats-label {
            font-size: 14px;
            color: #6c757d;
        }
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 24px;
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        input:checked + .slider {
            background-color: #2196F3;
        }
        input:checked + .slider:before {
            transform: translateX(26px);
        }
        .time-inputs {
            display: flex;
            gap: 10px;
        }
        .time-inputs .form-control {
            flex: 1;
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
                        <div id="messageContainer"></div>

                        <div class="row">
                            <!-- Add New Payment Method -->
                            <div class="col-md-4">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Add New Payment Method</h5>
                                    </div>
                                    <div class="card-body">
                                        <form id="addPaymentForm">
                                            <div class="mb-3">
                                                <label for="new_payName" class="form-label">Payment Name *</label>
                                                <input type="text" class="form-control" id="new_payName" name="payName" required placeholder="e.g., PayPal, Stripe, UPI">
                                            </div>

                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label for="new_miniPrice" class="form-label">Minimum Amount (₹) *</label>
                                                    <input type="number" class="form-control" id="new_miniPrice" name="miniPrice" step="0.01" min="0" required>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label for="new_maxPrice" class="form-label">Maximum Amount (₹) *</label>
                                                    <input type="number" class="form-control" id="new_maxPrice" name="maxPrice" step="0.01" min="0" required>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label for="new_scope" class="form-label">Scope</label>
                                                <input type="text" class="form-control" id="new_scope" name="scope" placeholder="Payment scope/type">
                                            </div>

                                            <div class="mb-3">
                                                <label for="new_paySendUrl" class="form-label">Payment URL</label>
                                                <input type="url" class="form-control" id="new_paySendUrl" name="paySendUrl" placeholder="https://example.com/payment">
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">Active Hours</label>
                                                <div class="time-inputs">
                                                    <input type="time" class="form-control" id="new_startTime" name="startTime" placeholder="Start Time">
                                                    <input type="time" class="form-control" id="new_endTime" name="endTime" placeholder="End Time">
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label for="new_rechargeRifts" class="form-label">Recharge Rifts (%)</label>
                                                <input type="number" class="form-control" id="new_rechargeRifts" name="rechargeRifts" step="0.01" min="0" max="100" placeholder="Commission percentage">
                                            </div>

                                            <div class="mb-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="new_status" name="status" value="active" checked>
                                                    <label class="form-check-label" for="new_status">
                                                        Active
                                                    </label>
                                                </div>
                                            </div>

                                            <button type="submit" class="btn btn-primary w-100">
                                                <i class="ri-add-line ri-16px me-2"></i>Add Payment Method
                                            </button>
                                        </form>
                                    </div>
                                </div>

                                <!-- Statistics -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Payment Statistics</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="stats-card mb-3">
                                            <div class="stats-value"><?= $total_methods ?></div>
                                            <div class="stats-label">Total Methods</div>
                                        </div>
                                        <div class="stats-card mb-3">
                                            <div class="stats-value" style="color: #28a745;"><?= $active_methods ?></div>
                                            <div class="stats-label">Active Methods</div>
                                        </div>
                                        <div class="stats-card">
                                            <div class="stats-value" style="color: #6c757d;"><?= $total_methods - $active_methods ?></div>
                                            <div class="stats-label">Inactive Methods</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Global Update -->
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Apply to All Methods</h5>
                                    </div>
                                    <div class="card-body">
                                        <form id="globalUpdateForm">
                                            <div class="mb-3">
                                                <label for="global_paySendUrl" class="form-label">Payment URL</label>
                                                <input type="url" class="form-control" id="global_paySendUrl" name="paySendUrl" placeholder="Update URL for all methods">
                                            </div>

                                            <div class="mb-3">
                                                <label for="global_rechargeRifts" class="form-label">Recharge Rifts (%)</label>
                                                <input type="number" class="form-control" id="global_rechargeRifts" name="rechargeRifts" step="0.01" min="0" max="100" placeholder="Update commission for all">
                                            </div>

                                            <div class="mb-3">
                                                <label for="global_miniPrice" class="form-label">Minimum Amount (₹)</label>
                                                <input type="number" class="form-control" id="global_miniPrice" name="miniPrice" step="0.01" min="0" placeholder="Update min amount for all">
                                            </div>

                                            <button type="submit" class="btn btn-warning w-100">
                                                <i class="ri-refresh-line ri-16px me-2"></i>Apply to All Methods
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Existing Payment Methods -->
                            <div class="col-md-8">
                                <div class="card">
                                    <div class="card-header">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h5 class="card-title mb-0">Payment Methods (<?= $total_methods ?>)</h5>
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-primary active" onclick="filterMethods('all')">All</button>
                                                <button type="button" class="btn btn-outline-success" onclick="filterMethods('active')">Active</button>
                                                <button type="button" class="btn btn-outline-secondary" onclick="filterMethods('inactive')">Inactive</button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <?php if ($total_methods > 0): ?>
                                            <div class="row" id="paymentMethodsContainer">
                                                <?php foreach ($payment_methods as $method): ?>
                                                    <div class="col-md-6 mb-4 payment-item" data-status="<?= $method['status'] ?>">
                                                        <div class="payment-card <?= $method['status'] === 'inactive' ? 'inactive' : '' ?>">
                                                            <!-- Payment Header -->
                                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                                <div>
                                                                    <h6 class="mb-1"><?= htmlspecialchars($method['payName']) ?></h6>
                                                                    <span class="badge bg-<?= $method['status'] === 'active' ? 'success' : 'secondary' ?>">
                                                                        <?= ucfirst($method['status']) ?>
                                                                    </span>
                                                                    <?php if (!empty($method['scope'])): ?>
                                                                        <span class="badge bg-info ms-1"><?= htmlspecialchars($method['scope']) ?></span>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <div class="dropdown">
                                                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                                                            type="button" data-bs-toggle="dropdown">
                                                                        <i class="ri-more-2-line"></i>
                                                                    </button>
                                                                    <ul class="dropdown-menu">
                                                                        <li>
                                                                            <a class="dropdown-item edit-payment" 
                                                                               href="#" data-id="<?= $method['id'] ?>">
                                                                                <i class="ri-edit-line me-2"></i>Edit
                                                                            </a>
                                                                        </li>
                                                                        <li>
                                                                            <a class="dropdown-item text-danger delete-payment" 
                                                                               href="#" data-id="<?= $method['id'] ?>">
                                                                                <i class="ri-delete-bin-line me-2"></i>Delete
                                                                            </a>
                                                                        </li>
                                                                    </ul>
                                                                </div>
                                                            </div>

                                                            <!-- Amount Range -->
                                                            <div class="row mb-3">
                                                                <div class="col-6">
                                                                    <label class="form-label small">Min Amount (₹)</label>
                                                                    <input type="number" class="form-control form-control-sm" 
                                                                           name="miniPrice" value="<?= htmlspecialchars($method['miniPrice']) ?>" 
                                                                           step="0.01" min="0">
                                                                </div>
                                                                <div class="col-6">
                                                                    <label class="form-label small">Max Amount (₹)</label>
                                                                    <input type="number" class="form-control form-control-sm" 
                                                                           name="maxPrice" value="<?= htmlspecialchars($method['maxPrice']) ?>" 
                                                                           step="0.01" min="0">
                                                                </div>
                                                            </div>

                                                            <!-- Payment URL -->
                                                            <div class="mb-3">
                                                                <label class="form-label small">Payment URL</label>
                                                                <input type="url" class="form-control form-control-sm" 
                                                                       name="paySendUrl" value="<?= htmlspecialchars($method['paySendUrl']) ?>" 
                                                                       placeholder="Payment gateway URL">
                                                            </div>

                                                            <!-- Active Hours -->
                                                            <div class="mb-3">
                                                                <label class="form-label small">Active Hours</label>
                                                                <div class="time-inputs">
                                                                    <input type="time" class="form-control form-control-sm" 
                                                                           name="startTime" value="<?= htmlspecialchars($method['startTime']) ?>">
                                                                    <input type="time" class="form-control form-control-sm" 
                                                                           name="endTime" value="<?= htmlspecialchars($method['endTime']) ?>">
                                                                </div>
                                                            </div>

                                                            <!-- Commission & Status -->
                                                            <div class="d-flex justify-content-between align-items-center">
                                                                <div class="flex-grow-1 me-3">
                                                                    <label class="form-label small">Commission (%)</label>
                                                                    <input type="number" class="form-control form-control-sm" 
                                                                           name="rechargeRifts" value="<?= htmlspecialchars($method['rechargeRifts']) ?>" 
                                                                           step="0.01" min="0" max="100">
                                                                </div>
                                                                <div class="form-check form-switch">
                                                                    <input class="form-check-input" type="checkbox" 
                                                                           name="status" value="active" 
                                                                           <?= $method['status'] === 'active' ? 'checked' : '' ?>>
                                                                    <label class="form-check-label small">Active</label>
                                                                </div>
                                                            </div>

                                                            <!-- Hidden ID -->
                                                            <input type="hidden" name="id" value="<?= $method['id'] ?>">
                                                            <input type="hidden" name="payName" value="<?= htmlspecialchars($method['payName']) ?>">
                                                            <input type="hidden" name="scope" value="<?= htmlspecialchars($method['scope']) ?>">

                                                            <!-- Update Button -->
                                                            <button type="button" class="btn btn-sm btn-primary w-100 mt-3 update-payment" 
                                                                    data-id="<?= $method['id'] ?>">
                                                                <i class="ri-save-line ri-14px me-1"></i>Update
                                                            </button>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="text-center py-5">
                                                <div class="avatar avatar-lg mb-3">
                                                    <span class="avatar-initial rounded bg-label-secondary">
                                                        <i class="ri-bank-card-line ri-24px"></i>
                                                    </span>
                                                </div>
                                                <h5 class="text-muted">No Payment Methods Found</h5>
                                                <p class="text-muted">Add your first payment method using the form on the left.</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
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

    <!-- Edit Payment Method Modal -->
    <div class="modal fade" id="editPaymentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Payment Method</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editPaymentForm">
                        <input type="hidden" id="edit_id" name="id">
                        
                        <div class="mb-3">
                            <label for="edit_payName" class="form-label">Payment Name *</label>
                            <input type="text" class="form-control" id="edit_payName" name="payName" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_miniPrice" class="form-label">Minimum Amount (₹) *</label>
                                <input type="number" class="form-control" id="edit_miniPrice" name="miniPrice" step="0.01" min="0" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_maxPrice" class="form-label">Maximum Amount (₹) *</label>
                                <input type="number" class="form-control" id="edit_maxPrice" name="maxPrice" step="0.01" min="0" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="edit_scope" class="form-label">Scope</label>
                            <input type="text" class="form-control" id="edit_scope" name="scope" placeholder="Payment scope/type">
                        </div>

                        <div class="mb-3">
                            <label for="edit_paySendUrl" class="form-label">Payment URL</label>
                            <input type="url" class="form-control" id="edit_paySendUrl" name="paySendUrl" placeholder="https://example.com/payment">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Active Hours</label>
                            <div class="time-inputs">
                                <input type="time" class="form-control" id="edit_startTime" name="startTime" placeholder="Start Time">
                                <input type="time" class="form-control" id="edit_endTime" name="endTime" placeholder="End Time">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="edit_rechargeRifts" class="form-label">Recharge Rifts (%)</label>
                            <input type="number" class="form-control" id="edit_rechargeRifts" name="rechargeRifts" step="0.01" min="0" max="100" placeholder="Commission percentage">
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="edit_status" name="status" value="active">
                                <label class="form-check-label" for="edit_status">
                                    Active
                                </label>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveEditPayment">
                        <i class="ri-save-line ri-16px me-2"></i>Save Changes
                    </button>
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

    <script>
        // Show message
        function showMessage(message, type = 'success') {
            const messageContainer = document.getElementById('messageContainer');
            const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
            
            messageContainer.innerHTML = `
                <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
            
            setTimeout(() => {
                const bsAlert = new bootstrap.Alert(messageContainer.querySelector('.alert'));
                bsAlert.close();
            }, 5000);
        }

        // Add new payment method
        document.getElementById('addPaymentForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'add');
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('✅ Payment method added successfully!');
                    this.reset();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showMessage('❌ Error: ' + data.error, 'error');
                }
            })
            .catch(error => {
                showMessage('❌ Network error: ' + error, 'error');
            });
        });

        // Update single payment method
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('update-payment')) {
                const card = e.target.closest('.payment-card');
                const formData = new FormData();
                formData.append('action', 'update');
                
                // Collect all form data from the card
                const inputs = card.querySelectorAll('input, select, textarea');
                inputs.forEach(input => {
                    if (input.type === 'checkbox') {
                        formData.append(input.name, input.checked ? 'active' : 'inactive');
                    } else {
                        formData.append(input.name, input.value);
                    }
                });
                
                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showMessage('✅ Payment method updated successfully!');
                    setTimeout(() => location.reload(), 1000);
                    } else {
                        showMessage('❌ Error: ' + data.error, 'error');
                    }
                })
                .catch(error => {
                    showMessage('❌ Network error: ' + error, 'error');
                });
            }
        });

        // Edit payment method - open modal
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('edit-payment')) {
                e.preventDefault();
                const id = e.target.getAttribute('data-id');
                
                // Find the payment method card
                const card = e.target.closest('.payment-card');
                
                // Populate the edit form with current values
                document.getElementById('edit_id').value = id;
                document.getElementById('edit_payName').value = card.querySelector('input[name="payName"]').value;
                document.getElementById('edit_miniPrice').value = card.querySelector('input[name="miniPrice"]').value;
                document.getElementById('edit_maxPrice').value = card.querySelector('input[name="maxPrice"]').value;
                document.getElementById('edit_scope').value = card.querySelector('input[name="scope"]').value;
                document.getElementById('edit_paySendUrl').value = card.querySelector('input[name="paySendUrl"]').value;
                document.getElementById('edit_startTime').value = card.querySelector('input[name="startTime"]').value;
                document.getElementById('edit_endTime').value = card.querySelector('input[name="endTime"]').value;
                document.getElementById('edit_rechargeRifts').value = card.querySelector('input[name="rechargeRifts"]').value;
                
                // Set status checkbox
                const statusCheckbox = card.querySelector('input[name="status"]');
                document.getElementById('edit_status').checked = statusCheckbox.checked;
                
                // Show the modal
                const editModal = new bootstrap.Modal(document.getElementById('editPaymentModal'));
                editModal.show();
            }
        });

        // Save edited payment method
        document.getElementById('saveEditPayment').addEventListener('click', function() {
            const formData = new FormData(document.getElementById('editPaymentForm'));
            formData.append('action', 'update');
            
            // Handle status checkbox
            const statusValue = document.getElementById('edit_status').checked ? 'active' : 'inactive';
            formData.set('status', statusValue);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('✅ Payment method updated successfully!');
                    const editModal = bootstrap.Modal.getInstance(document.getElementById('editPaymentModal'));
                    editModal.hide();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showMessage('❌ Error: ' + data.error, 'error');
                }
            })
            .catch(error => {
                showMessage('❌ Network error: ' + error, 'error');
            });
        });

        // Delete payment method
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('delete-payment')) {
                e.preventDefault();
                const id = e.target.getAttribute('data-id');
                
                if (confirm('Are you sure you want to delete this payment method?')) {
                    const formData = new FormData();
                    formData.append('action', 'delete');
                    formData.append('id', id);
                    
                    fetch('', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showMessage('✅ Payment method deleted successfully!');
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            showMessage('❌ Error: ' + data.error, 'error');
                        }
                    })
                    .catch(error => {
                        showMessage('❌ Network error: ' + error, 'error');
                    });
                }
            }
        });

        // Global update
        document.getElementById('globalUpdateForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (confirm('Apply these settings to ALL payment methods?')) {
                const formData = new FormData(this);
                formData.append('action', 'update_all');
                
                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showMessage(`✅ Settings applied to ${data.updated_rows} payment methods!`);
                        this.reset();
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showMessage('❌ Error: ' + data.error, 'error');
                    }
                })
                .catch(error => {
                    showMessage('❌ Network error: ' + error, 'error');
                });
            }
        });

        // Filter methods
        function filterMethods(status) {
            const methods = document.querySelectorAll('.payment-item');
            const filterButtons = document.querySelectorAll('.btn-group .btn');
            
            // Update active button
            filterButtons.forEach(btn => {
                btn.classList.remove('active');
                if (btn.textContent.toLowerCase() === status) {
                    btn.classList.add('active');
                }
            });
            
            // Show/hide methods based on filter
            methods.forEach(method => {
                if (status === 'all' || method.getAttribute('data-status') === status) {
                    method.style.display = 'block';
                } else {
                    method.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>