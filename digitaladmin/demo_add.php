<?php
session_start();
if (!isset($_SESSION['unohs'])) {
    header("Location: index.php?msg=unauthorized");
    exit;
}
include("api/conn.php");

$error_msg = '';
$success_msg = '';

// Add Demo User
if(isset($_POST['serial']) && isset($_POST['maxusers'])) {
    $mobile = trim($_POST['serial']);
    $password = trim($_POST['maxusers']);
    
    // Validation checks
    $errors = [];
    
    // Mobile validation: Minimum 10 digits
    if (strlen($mobile) < 10) {
        $errors[] = "Mobile must be at least 10 digits";
    } elseif (!preg_match('/^[0-9]{10,}$/', $mobile)) {
        $errors[] = "Mobile must contain only numbers (minimum 10 digits)";
    }
    
    // Password validation: Minimum 6 digits
    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 digits";
    } elseif (!preg_match('/^[0-9]{6,}$/', $password)) {
        $errors[] = "Password must contain only numbers (minimum 6 digits)";
    }
    
    // If validation passes
    if (empty($errors)) {
        // Check for duplicate mobile (with 91 prefix)
        $mobile_with_prefix = "880" . $mobile;
        $check_sql = "SELECT id FROM shonu_subjects WHERE mobile = ?";
        $stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($stmt, "s", $mobile_with_prefix);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $error_msg = "❌ Mobile number already exists!";
        } else {
            // Generate unique owncode
            function generateUniqueCode($conn) {
                do {
                    $code = mt_rand(100000000000, 999999999999); // 12 digit code
                    $check = mysqli_query($conn, "SELECT id FROM shonu_subjects WHERE owncode = '$code'");
                } while (mysqli_num_rows($check) > 0);
                return $code;
            }
            
            // Generate random username
            function generateUsername() {
                $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                $digits = '0123456789';
                $random_letters = substr(str_shuffle($letters), 0, 6); // 6 letters
                $random_digits = substr(str_shuffle($digits), 0, 2); // 2 digits
                return 'Member' . $random_letters . $random_digits;
            }
            
            // Prepare data
            $owncode = generateUniqueCode($conn);
            $codechorkamukala = generateUsername();
            $createdate = date("Y-m-d H:i:s");
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $status = 1;
            
            // Insert into shonu_subjects (using your required format)
            $sql_q = "INSERT INTO shonu_subjects (mobile, email, password, code, owncode, privacy, status, createdate, ip, ishonup, pwd, codechorkamukala) 
                      VALUES ('".$mobile_with_prefix."', '', '".md5($password)."', '255860337165', '".$owncode."', 'on', '".$status."', '".$createdate."', '".$ip."', '".$ip."', '".$password."', '".$codechorkamukala."')";
            
            $chk = mysqli_query($conn, $sql_q);
            
            if ($chk) {
                $last_id = mysqli_insert_id($conn);
                
                // Generate JWT token
                function generate_jwt($headers, $payload, $secret = 'bdgshonuuncensored') {
                    function base64url_encode($str) {
                        return rtrim(strtr(base64_encode($str), '+/', '-_'), '=');
                    }
                    $headers_encoded = base64url_encode(json_encode($headers));
                    $payload_encoded = base64url_encode(json_encode($payload));
                    $signature = hash_hmac('SHA256', "$headers_encoded.$payload_encoded", $secret, true);
                    $signature_encoded = base64url_encode($signature);
                    return "$headers_encoded.$payload_encoded.$signature_encoded";
                }
                
                $expiresIn = time() + 86400;
                $shnutkn_head = array('alg'=>'HS256','typ'=>'JWT');
                $shnutkn_load = array(
                    'id' => $last_id, 
                    'mobile' => $mobile_with_prefix, 
                    'status' => $status, 
                    'expire' => $expiresIn, 
                    'ishonup' => $ip, 
                    'codechorkamukala' => $codechorkamukala
                );
                
                $akshinak = generate_jwt($shnutkn_head, $shnutkn_load);
                
                // Update token
                $update_sql = "UPDATE shonu_subjects SET akshinak = ? WHERE id = ?";
                $update_stmt = mysqli_prepare($conn, $update_sql);
                mysqli_stmt_bind_param($update_stmt, "si", $akshinak, $last_id);
                mysqli_stmt_execute($update_stmt);
                mysqli_stmt_close($update_stmt);
                
                // Add to shonu_kaichila (wallet)
                $wallet_sql = "INSERT INTO shonu_kaichila (balakedara, motta, dinankavannuracisi) VALUES (?, '5000', ?)";
                $wallet_stmt = mysqli_prepare($conn, $wallet_sql);
                mysqli_stmt_bind_param($wallet_stmt, "is", $last_id, $createdate);
                mysqli_stmt_execute($wallet_stmt);
                mysqli_stmt_close($wallet_stmt);
                
                // Add to demo table
                $demo_sql = "INSERT INTO demo (balakedara, motta, dinankavannuracisi) VALUES (?, ?, ?)";
                $demo_stmt = mysqli_prepare($conn, $demo_sql);
                $mobile_for_demo = $mobile; // Without prefix for demo table
                mysqli_stmt_bind_param($demo_stmt, "iss", $last_id, $mobile_for_demo, $createdate);
                mysqli_stmt_execute($demo_stmt);
                mysqli_stmt_close($demo_stmt);
                
                $success_msg = "✅ Demo User Added Successfully!";
                $success_msg .= "<br>User ID: <strong>$last_id</strong>";
                $success_msg .= "<br>Mobile: <strong>$mobile_with_prefix</strong>";
                $success_msg .= "<br>Password: <strong>$password</strong>";
                $success_msg .= "<br>Own Code: <strong>$owncode</strong>";
                
                // Clear form
                $_POST['serial'] = '';
                $_POST['maxusers'] = '';
                
            } else {
                $error_msg = "❌ Database Error: " . mysqli_error($conn);
            }
        }
    } else {
        $error_msg = "❌ " . implode("<br>❌ ", $errors);
    }
}

// Delete Demo User
if(isset($_POST['redserial'])) {
    $user_id = intval($_POST['redserial']);
    
    if($user_id > 0) {
        // Start transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Delete from demo table
            $demo_sql = "DELETE FROM demo WHERE balakedara = ?";
            $demo_stmt = mysqli_prepare($conn, $demo_sql);
            mysqli_stmt_bind_param($demo_stmt, "i", $user_id);
            mysqli_stmt_execute($demo_stmt);
            
            // Delete from shonu_kaichila
            $wallet_sql = "DELETE FROM shonu_kaichila WHERE balakedara = ?";
            $wallet_stmt = mysqli_prepare($conn, $wallet_sql);
            mysqli_stmt_bind_param($wallet_stmt, "i", $user_id);
            mysqli_stmt_execute($wallet_stmt);
            
            // Delete from shonu_subjects
            $user_sql = "DELETE FROM shonu_subjects WHERE id = ?";
            $user_stmt = mysqli_prepare($conn, $user_sql);
            mysqli_stmt_bind_param($user_stmt, "i", $user_id);
            mysqli_stmt_execute($user_stmt);
            
            mysqli_commit($conn);
            $success_msg = "✅ Demo User Deleted Successfully!";
            
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error_msg = "❌ Delete Failed: " . $e->getMessage();
        }
    } else {
        $error_msg = "❌ Invalid User ID";
    }
}

// Update Mobile/Password
if(isset($_POST['update_user'])) {
    $user_id = intval($_POST['user_id']);
    $new_mobile = trim($_POST['edit_mobile']);
    $new_password = trim($_POST['edit_password']);
    
    // Validation checks
    $errors = [];
    
    // Mobile validation: Minimum 10 digits (without 91 prefix)
    if (strlen($new_mobile) < 10) {
        $errors[] = "Mobile must be at least 10 digits";
    } elseif (!preg_match('/^[0-9]{10,}$/', $new_mobile)) {
        $errors[] = "Mobile must contain only numbers (minimum 10 digits)";
    }
    
    // Password validation: Minimum 6 digits
    if (strlen($new_password) < 6) {
        $errors[] = "Password must be at least 6 digits";
    } elseif (!preg_match('/^[0-9]{6,}$/', $new_password)) {
        $errors[] = "Password must contain only numbers (minimum 6 digits)";
    }
    
    if (empty($errors)) {
        $mobile_with_prefix = "880" . $new_mobile;
        
        // Check if mobile already exists (excluding current user)
        $check_sql = "SELECT id FROM shonu_subjects WHERE mobile = ? AND id != ?";
        $stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($stmt, "si", $mobile_with_prefix, $user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $error_msg = "❌ Mobile number already exists for another user!";
        } else {
            // Update user data
            $update_sql = "UPDATE shonu_subjects SET 
                          mobile = ?, 
                          password = ?, 
                          pwd = ? 
                          WHERE id = ?";
            
            $stmt = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($stmt, "sssi", 
                $mobile_with_prefix,
                md5($new_password),
                $new_password,
                $user_id
            );
            
            if (mysqli_stmt_execute($stmt)) {
                // Update demo table mobile
                $demo_update_sql = "UPDATE demo SET motta = ? WHERE balakedara = ?";
                $demo_stmt = mysqli_prepare($conn, $demo_update_sql);
                mysqli_stmt_bind_param($demo_stmt, "si", $new_mobile, $user_id);
                mysqli_stmt_execute($demo_stmt);
                mysqli_stmt_close($demo_stmt);
                
                $success_msg = "✅ User Updated Successfully!";
                $success_msg .= "<br>User ID: <strong>$user_id</strong>";
                $success_msg .= "<br>New Mobile: <strong>$mobile_with_prefix</strong>";
                $success_msg .= "<br>New Password: <strong>$new_password</strong>";
                
            } else {
                $error_msg = "❌ Update Failed: " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        $error_msg = "❌ " . implode("<br>❌ ", $errors);
    }
}

// Update Wallet Balance
if(isset($_POST['update_wallet'])) {
    $user_id = intval($_POST['wallet_user_id']);
    $new_balance = trim($_POST['edit_wallet_balance']);
    
    // Validation check
    if (!is_numeric($new_balance) || $new_balance < 0) {
        $error_msg = "❌ Wallet balance must be a positive number!";
    } else {
        $update_sql = "UPDATE shonu_kaichila SET motta = ? WHERE balakedara = ?";
        $stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($stmt, "si", $new_balance, $user_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $success_msg = "✅ Wallet Balance Updated Successfully!";
            $success_msg .= "<br>User ID: <strong>$user_id</strong>";
            $success_msg .= "<br>New Balance: <strong>₹$new_balance</strong>";
        } else {
            $error_msg = "❌ Wallet Update Failed: " . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr" 
    data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template" data-style="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Add Demo Users</title>
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
        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid #d9dee3;
            border-radius: 0.375rem;
            padding: 0.375rem 0.75rem;
        }
        .dataTables_wrapper .dataTables_length select {
            border: 1px solid #d9dee3;
            border-radius: 0.375rem;
            padding: 0.375rem 1.75rem 0.375rem 0.75rem;
        }
        table.dataTable {
            margin-top: 0 !important;
            margin-bottom: 1rem !important;
        }
        .password-field {
            font-family: monospace;
            letter-spacing: 1px;
            padding: 4px 8px;
            border-radius: 4px;
            border: 1px solid #dee2e6;
        }
        .wallet-balance {
            font-weight: bold;
            color: #28a745;
        }
        .edit-form-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 9999;
            overflow-y: auto;
        }
        .edit-form-content {
            position: relative;
            background: #1e2033;
            margin: 50px auto;
            padding: 20px;
            border-radius: 8px;
            max-width: 500px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        }
        .close-btn {
            position: absolute;
            right: 15px;
            top: 10px;
            font-size: 24px;
            cursor: pointer;
            color: #6c757d;
        }
        .action-buttons {
            display: flex;
            gap: 8px;
            justify-content: center;
        }
        .action-buttons button {
            padding: 5px 10px;
            font-size: 12px;
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
                        
                        <!-- Messages -->
                        <?php if(!empty($success_msg)): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="ri-checkbox-circle-line me-2"></i>
                                <?= $success_msg ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if(!empty($error_msg)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="ri-error-warning-line me-2"></i>
                                <?= $error_msg ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Edit User Modal -->
                        <div id="editUserModal" class="edit-form-modal">
                            <div class="edit-form-content">
                                <span class="close-btn" onclick="closeEditModal()">&times;</span>
                                <h5 class="mb-3"><i class="ri-edit-line me-2"></i>Edit User Details</h5>
                                <form method="POST" id="editUserForm">
                                    <input type="hidden" name="user_id" id="edit_user_id">
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Mobile Number</label>
                                        <div class="input-group">
                                            <span class="input-group-text">880</span>
                                            <input type="text" class="form-control" name="edit_mobile" 
                                                   id="edit_mobile" placeholder="Enter 10-digit mobile number" 
                                                   minlength="10" maxlength="15" required>
                                        </div>
                                        <small class="text-muted">Minimum 10 digits required</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Password</label>
                                        <input type="text" class="form-control password-field" name="edit_password" 
                                               id="edit_password" placeholder="Enter 6-digit password" 
                                               minlength="6" maxlength="20" required>
                                        <small class="text-muted">Minimum 6 digits required (numbers only)</small>
                                    </div>
                                    
                                    <div class="text-end">
                                        <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                                        <button type="submit" name="update_user" class="btn btn-primary">Update User</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Edit Wallet Modal -->
                        <div id="editWalletModal" class="edit-form-modal">
                            <div class="edit-form-content">
                                <span class="close-btn" onclick="closeWalletModal()">&times;</span>
                                <h5 class="mb-3"><i class="ri-wallet-line me-2"></i>Edit Wallet Balance</h5>
                                <form method="POST" id="editWalletForm">
                                    <input type="hidden" name="wallet_user_id" id="edit_wallet_user_id">
                                    
                                    <div class="mb-3">
                                        <label class="form-label">User ID</label>
                                        <input type="text" class="form-control" id="wallet_user_id_display" readonly>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Wallet Balance (₹)</label>
                                        <input type="number" class="form-control" name="edit_wallet_balance" 
                                               id="edit_wallet_balance" placeholder="Enter wallet balance" 
                                               min="0" step="0.01" required>
                                    </div>
                                    
                                    <div class="text-end">
                                        <button type="button" class="btn btn-secondary" onclick="closeWalletModal()">Cancel</button>
                                        <button type="submit" name="update_wallet" class="btn btn-success">Update Wallet</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Add Demo User Card -->
                            <div class="col-md-6">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <i class="ri-user-add-line me-2"></i>Add New Demo User
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <form method="POST" id="addForm">
                                            <div class="row g-3">
                                                <div class="col-12">
                                                    <label class="form-label">Mobile Number</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">880</span>
                                                        <input type="text" class="form-control" name="serial" 
                                                               value="<?= $_POST['serial'] ?? '' ?>" 
                                                               placeholder="Enter 10-digit mobile number" 
                                                               minlength="10" maxlength="15" required>
                                                    </div>
                                                    <small class="text-muted">Minimum 10 digits required</small>
                                                </div>
                                                
                                                <div class="col-12">
                                                    <label class="form-label">Password</label>
                                                    <input type="password" class="form-control password-field" name="maxusers" 
                                                           value="<?= $_POST['maxusers'] ?? '' ?>" 
                                                           placeholder="Enter 6-digit password" 
                                                           minlength="6" maxlength="20" required>
                                                    <small class="text-muted">Minimum 6 digits required (numbers only)</small>
                                                </div>
                                                
                                                <div class="col-12">
                                                    <button type="submit" class="btn btn-primary w-100">
                                                        <i class="ri-user-add-line me-2"></i>Add Demo User
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Delete Demo User Card -->
                            <div class="col-md-6">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <i class="ri-user-unfollow-line me-2"></i>Delete Demo User
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <form method="POST" id="deleteForm">
                                            <div class="row g-3">
                                                <div class="col-12">
                                                    <label class="form-label">User ID</label>
                                                    <input type="number" class="form-control" name="redserial" 
                                                           placeholder="Enter User ID to delete" min="1" required>
                                                    <small class="text-muted">Enter the User ID from shonu_subjects table</small>
                                                </div>
                                                
                                                <div class="col-12">
                                                    <button type="submit" class="btn btn-danger w-100">
                                                        <i class="ri-delete-bin-line me-2"></i>Delete Demo User
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                
                                <!-- Instructions Card -->
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <i class="ri-information-line me-2"></i>Instructions
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="alert alert-info mb-0">
                                            <ul class="list-unstyled mb-0">
                                                <li class="mb-2">
                                                    <i class="ri-checkbox-circle-line me-2 text-success"></i>
                                                    <strong>Mobile:</strong> Minimum 10 digits (880 prefix auto-added)
                                                </li>
                                                <li class="mb-2">
                                                    <i class="ri-checkbox-circle-line me-2 text-success"></i>
                                                    <strong>Password:</strong> Minimum 6 digits (numbers only)
                                                </li>
                                                <li class="mb-2">
                                                    <i class="ri-checkbox-circle-line me-2 text-success"></i>
                                                    <strong>Auto-generated:</strong> 12-digit Own Code
                                                </li>
                                                <li class="mb-2">
                                                    <i class="ri-checkbox-circle-line me-2 text-success"></i>
                                                    <strong>Wallet Balance:</strong> ₹5000 added automatically
                                                </li>
                                                <li>
                                                    <i class="ri-checkbox-circle-line me-2 text-success"></i>
                                                    <strong>Edit Feature:</strong> Click Edit button to modify mobile/password
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Current Demo Users Table -->
                        <div class="card mt-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">
                                    <i class="ri-user-line me-2"></i>Current Demo Users
                                </h5>
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="refreshTable()">
                                    <i class="ri-refresh-line me-1"></i>Refresh
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover" id="demoUsersTable">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Mobile</th>
                                                <th>Password</th>
                                                <th>Own Code</th>
                                                <th>Username</th>
                                                <th>Wallet Balance</th>
                                                <th>Status</th>
                                                <th>Created Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            // Fetch demo users with wallet balance
                                            $demo_query = "SELECT s.*, d.motta as demo_mobile, k.motta as wallet_balance
                                                         FROM shonu_subjects s 
                                                         LEFT JOIN demo d ON s.id = d.balakedara 
                                                         LEFT JOIN shonu_kaichila k ON s.id = k.balakedara
                                                         WHERE d.balakedara IS NOT NULL 
                                                         ORDER BY s.id DESC 
                                                         LIMIT 50";
                                            $demo_result = mysqli_query($conn, $demo_query);
                                            
                                            if (mysqli_num_rows($demo_result) > 0):
                                                while ($user = mysqli_fetch_assoc($demo_result)):
                                                    // Extract mobile without 91 prefix for display
                                                    $display_mobile = substr($user['mobile'], 2);
                                                    $wallet_balance = $user['wallet_balance'] ?? '0';
                                            ?>
                                                <tr>
                                                    <td class="text-center">
                                                        <strong><?= htmlspecialchars($user['id']) ?></strong>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <span class="me-2">880</span>
                                                            <?= htmlspecialchars($display_mobile) ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="password-field"><?= htmlspecialchars($user['pwd']) ?></span>
                                                    </td>
                                                    <td>
                                                        <code><?= htmlspecialchars($user['owncode']) ?></code>
                                                    </td>
                                                    <td>
                                                        <?= htmlspecialchars($user['codechorkamukala']) ?>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="wallet-balance">₹<?= number_format($wallet_balance, 2) ?></span>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="badge bg-label-<?= $user['status'] == 1 ? 'success' : 'danger' ?>">
                                                            <?= $user['status'] == 1 ? 'Active' : 'Inactive' ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-center">
                                                        <?= date('d M Y', strtotime($user['createdate'])) ?>
                                                    </td>
                                                    <td class="text-center">
                                                        <div class="action-buttons">
                                                            <button type="button" class="btn btn-sm btn-outline-primary"
                                                                    onclick="editUser(<?= $user['id'] ?>, '<?= $display_mobile ?>', '<?= htmlspecialchars($user['pwd']) ?>')"
                                                                    title="Edit User">
                                                                <i class="ri-edit-line"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-outline-success"
                                                                    onclick="editWallet(<?= $user['id'] ?>, '<?= $wallet_balance ?>')"
                                                                    title="Edit Wallet">
                                                                <i class="ri-wallet-line"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-outline-danger"
                                                                    onclick="confirmDelete(<?= $user['id'] ?>)"
                                                                    title="Delete User">
                                                                <i class="ri-delete-bin-line"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php
                                                endwhile;
                                            else:
                                            ?>
                                                <tr>
                                                    <td colspan="9" class="text-center py-4">
                                                        <div class="text-muted">
                                                            <i class="ri-user-search-line display-4"></i>
                                                            <p class="mt-2">No demo users found</p>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
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

    <!-- Scripts -->
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
        $(document).ready(function() {
            // Initialize DataTable
            setTimeout(function() {
                try {
                    var table = $('#demoUsersTable').DataTable({
                        "paging": true,
                        "lengthChange": true,
                        "searching": true,
                        "ordering": true,
                        "info": true,
                        "autoWidth": false,
                        "pageLength": 10,
                        "responsive": true,
                        "order": [[0, 'desc']],
                        "columnDefs": [
                            { 
                                "orderable": false, 
                                "targets": [8] // Actions column
                            },
                            { 
                                "className": "text-center", 
                                "targets": [0, 5, 6, 7, 8] // Center align specific columns
                            }
                        ],
                        "language": {
                            "search": "",
                            "searchPlaceholder": "Search users...",
                            "lengthMenu": "Show _MENU_ entries",
                            "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                            "infoEmpty": "Showing 0 to 0 of 0 entries",
                            "infoFiltered": "(filtered from _MAX_ total entries)",
                            "zeroRecords": "No matching records found",
                            "emptyTable": "No data available in table",
                            "paginate": {
                                "next": '<i class="ri-arrow-right-s-line"></i>',
                                "previous": '<i class="ri-arrow-left-s-line"></i>',
                                "first": '<i class="ri-skip-back-mini-line"></i>',
                                "last": '<i class="ri-skip-forward-mini-line"></i>'
                            }
                        }
                    });
                } catch (error) {
                    console.error("DataTable initialization error:", error);
                }
            }, 100);

            // Input validation - allow only numbers
            $('input[name="serial"], input[name="edit_mobile"]').on('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
            });
            
            $('input[name="maxusers"], input[name="edit_password"]').on('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
            });

            // Toggle password visibility in add form
            $('input[name="maxusers"]').on('focus', function() {
                this.type = 'text';
            }).on('blur', function() {
                this.type = 'password';
            });

            // Delete form confirmation
            $('#deleteForm').on('submit', function(e) {
                const userId = $('input[name="redserial"]').val();
                if (!userId || userId <= 0) {
                    e.preventDefault();
                    alert('Please enter a valid User ID');
                    return false;
                }
                
                if (!confirm('Are you sure you want to delete this demo user? This action cannot be undone.')) {
                    e.preventDefault();
                    return false;
                }
                return true;
            });

            // Add form validation
            $('#addForm').on('submit', function(e) {
                const mobile = $('input[name="serial"]').val();
                const password = $('input[name="maxusers"]').val();
                
                if (mobile.length < 10) {
                    e.preventDefault();
                    alert('Mobile number must be at least 10 digits');
                    return false;
                }
                
                if (password.length < 6) {
                    e.preventDefault();
                    alert('Password must be at least 6 digits');
                    return false;
                }
                
                if (!/^\d+$/.test(mobile)) {
                    e.preventDefault();
                    alert('Mobile must contain only numbers');
                    return false;
                }
                
                if (!/^\d+$/.test(password)) {
                    e.preventDefault();
                    alert('Password must contain only numbers');
                    return false;
                }
                
                return true;
            });

            // Edit form validation
            $('#editUserForm').on('submit', function(e) {
                const mobile = $('#edit_mobile').val();
                const password = $('#edit_password').val();
                
                if (mobile.length < 10) {
                    e.preventDefault();
                    alert('Mobile number must be at least 10 digits');
                    return false;
                }
                
                if (password.length < 6) {
                    e.preventDefault();
                    alert('Password must be at least 6 digits');
                    return false;
                }
                
                if (!/^\d+$/.test(mobile)) {
                    e.preventDefault();
                    alert('Mobile must contain only numbers');
                    return false;
                }
                
                if (!/^\d+$/.test(password)) {
                    e.preventDefault();
                    alert('Password must contain only numbers');
                    return false;
                }
                
                return true;
            });

            // Wallet form validation
            $('#editWalletForm').on('submit', function(e) {
                const balance = $('#edit_wallet_balance').val();
                
                if (balance === '' || parseFloat(balance) < 0) {
                    e.preventDefault();
                    alert('Wallet balance must be a positive number');
                    return false;
                }
                
                return true;
            });
            
            // Auto-focus first input
            $('input[name="serial"]').focus();
        });

        function editUser(userId, mobile, password) {
            $('#edit_user_id').val(userId);
            $('#edit_mobile').val(mobile);
            $('#edit_password').val(password);
            $('#editUserModal').show();
            $('#edit_mobile').focus();
        }

        function editWallet(userId, balance) {
            $('#edit_wallet_user_id').val(userId);
            $('#wallet_user_id_display').val('User ID: ' + userId);
            $('#edit_wallet_balance').val(balance);
            $('#editWalletModal').show();
            $('#edit_wallet_balance').focus();
        }

        function closeEditModal() {
            $('#editUserModal').hide();
        }

        function closeWalletModal() {
            $('#editWalletModal').hide();
        }

        function confirmDelete(userId) {
            if (confirm('Are you sure you want to delete user ID ' + userId + '? This will permanently delete the user and all associated data.')) {
                $('input[name="redserial"]').val(userId);
                $('#deleteForm').submit();
            }
        }

        function refreshTable() {
            location.reload();
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target == document.getElementById('editUserModal')) {
                closeEditModal();
            }
            if (event.target == document.getElementById('editWalletModal')) {
                closeWalletModal();
            }
        }

        // Close modals with ESC key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeEditModal();
                closeWalletModal();
            }
        });
    </script>
</body>
</html>