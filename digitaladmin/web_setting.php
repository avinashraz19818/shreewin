<?php
session_start();

// Security headers to prevent path disclosure
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("X-XSS-Protection: 1; mode=block");

// Hide server paths in production - only show in development
$isDevelopment = false; // Set to true only for local debugging
if (!$isDevelopment) {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(E_ALL);
    ini_set('log_errors', 1);
} else {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

if (empty($_SESSION['unohs'])) {
    header("location: api/login.php?msg=unauthorized");
    exit();
}

include("api/conn.php");

// debug helper (prints styled debug messages)
function debugMessage($message) {
    echo "<pre style='background-color: #111; color: #9ff; padding: 10px; font-size: 14px; border-radius:6px;'>$message</pre>";
}

// sanitize filename and make unique
function makeSafeFilename($name) {
    $name = preg_replace('/[^a-zA-Z0-9\-\_\.]/', '-', $name);
    $name = preg_replace('/-+/', '-', $name);
    return $name;
}

// ensure DB connection
if (!$conn) {
    die("Database connection error. Please contact support.");
}

// Fetch current settings (before handling POST)
$query = "SELECT * FROM web_setting WHERE id = 1";
$result = mysqli_query($conn, $query);
$settings = mysqli_fetch_assoc($result);

if (!$settings) {
    debugMessage("⚠️ Could not load settings. Using defaults.");
    // set sensible defaults to avoid undefined index notices later
    $settings = [
        'min_recharge' => 0,
        'min_withdrawal' => 0,
        'register_bonus' => 0,
        'app_download' => '',
        'allowbet' => 0,
        'telegram_active' => 0,
        'telegram_link' => '',
        'web_name' => '',
        'web_logo' => '',
        'favicon_logo' => ''
    ];
}

// Files directories
$logosDirFs = realpath(__DIR__) . '/../logos'; // filesystem absolute path to ../logos
$logosDirWeb = '/logos'; // web path to store in DB (so img src="/logos/xxx.png" works)

// Ensure logos dir exists and is writable
if (!is_dir($logosDirFs)) {
    if (!mkdir($logosDirFs, 0755, true)) {
        debugMessage("❌ Could not create logos directory - check server permissions.");
    } else {
        // created
        // try to set permissions
        @chmod($logosDirFs, 0755);
    }
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // sanitize/validate inputs
    $min_recharge = isset($_POST['min_recharge']) ? (int)$_POST['min_recharge'] : 0;
    $min_withdrawal = isset($_POST['min_withdrawal']) ? (int)$_POST['min_withdrawal'] : 0;
    $register_bonus = isset($_POST['register_bonus']) ? (int)$_POST['register_bonus'] : 0;
    $app_download = isset($_POST['app_download']) ? trim($_POST['app_download']) : '';
    $allowbet = isset($_POST['allowbet']) ? (int)$_POST['allowbet'] : 0;

    $telegram_active = isset($_POST['telegram_active']) ? (int)$_POST['telegram_active'] : 0;
    $telegram_link = isset($_POST['telegram_link']) ? trim($_POST['telegram_link']) : '';
    $web_name = isset($_POST['web_name']) ? trim($_POST['web_name']) : '';

    // Start with current DB values (if no new file uploaded we keep existing values)
    $web_logo = $settings['web_logo'];
    $favicon_logo = $settings['favicon_logo'];

    // --- Handle web_logo upload ---
    if (isset($_FILES['web_logo']) && $_FILES['web_logo']['error'] === UPLOAD_ERR_OK) {
        $tmpPath = $_FILES['web_logo']['tmp_name'];
        $original = $_FILES['web_logo']['name'];
        $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        
        // Only allow PNG, JPG, WEBP
        $allowedExt = ['png', 'jpg', 'jpeg', 'webp'];
        if (!in_array($ext, $allowedExt)) {
            debugMessage("⚠️ Only PNG, JPG, and WEBP files are allowed.");
        } else {
            $safeName = pathinfo($original, PATHINFO_FILENAME);
            $safeName = makeSafeFilename($safeName);
            $finalName = $safeName . '-' . time() . '.' . $ext;
            $destFs = $logosDirFs . '/' . $finalName;
            $destWeb = $logosDirWeb . '/' . $finalName;

            if (is_uploaded_file($tmpPath)) {
                if (move_uploaded_file($tmpPath, $destFs)) {
                    // success: set web path for DB
                    $web_logo = $destWeb;
                    // optional: copy to frontend path with fixed filename (if required)
                    $frontendDir = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . '/assets/png';
                    if (!is_dir($frontendDir)) {
                        @mkdir($frontendDir, 0755, true);
                    }
                    $frontendPath = $frontendDir . '/logo-8e1d7dae.png';
                    @copy($destFs, $frontendPath); // non-fatal if fails
                } else {
                    debugMessage("❌ Failed to upload website logo. Please try again.");
                }
            } else {
                debugMessage("❌ Invalid file upload. Please try again.");
            }
        }
    } elseif (isset($_FILES['web_logo']) && $_FILES['web_logo']['error'] !== UPLOAD_ERR_NO_FILE) {
        debugMessage("⚠️ File upload failed. Please try a different file.");
    }

    // --- Handle favicon_logo upload ---
    if (isset($_FILES['favicon_logo']) && $_FILES['favicon_logo']['error'] === UPLOAD_ERR_OK) {
        $tmpPath = $_FILES['favicon_logo']['tmp_name'];
        $original = $_FILES['favicon_logo']['name'];
        $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        
        // Only allow PNG, JPG, WEBP
        $allowedExt = ['png', 'jpg', 'jpeg', 'webp'];
        if (!in_array($ext, $allowedExt)) {
            debugMessage("⚠️ Only PNG, JPG, and WEBP files are allowed.");
        } else {
            $safeName = pathinfo($original, PATHINFO_FILENAME);
            $safeName = makeSafeFilename($safeName);
            $finalName = $safeName . '-' . time() . '.' . $ext;
            $destFs = $logosDirFs . '/' . $finalName;
            $destWeb = $logosDirWeb . '/' . $finalName;

            if (is_uploaded_file($tmpPath)) {
                if (move_uploaded_file($tmpPath, $destFs)) {
                    $favicon_logo = $destWeb;
                } else {
                    debugMessage("❌ Failed to upload favicon. Please try again.");
                }
            } else {
                debugMessage("❌ Invalid file upload. Please try again.");
            }
        }
    } elseif (isset($_FILES['favicon_logo']) && $_FILES['favicon_logo']['error'] !== UPLOAD_ERR_NO_FILE) {
        debugMessage("⚠️ File upload failed. Please try a different file.");
    }

    // --- DB update: prepare and execute safely
    $updateQuery = "UPDATE web_setting SET 
        min_recharge = ?, 
        min_withdrawal = ?, 
        register_bonus = ?, 
        app_download = ?, 
        allowbet = ?, 
        telegram_active = ?, 
        telegram_link = ?, 
        web_name = ?, 
        web_logo = ?, 
        favicon_logo = ? 
        WHERE id = 1";

    $stmt = mysqli_prepare($conn, $updateQuery);
    if (!$stmt) {
        debugMessage("❌ Database error. Please try again.");
    } else {
        // types: i i i s i i s s s s  => "iiisiissss"
        mysqli_stmt_bind_param(
            $stmt,
            "iiisiissss",
            $min_recharge,
            $min_withdrawal,
            $register_bonus,
            $app_download,
            $allowbet,
            $telegram_active,
            $telegram_link,
            $web_name,
            $web_logo,
            $favicon_logo
        );

        if (mysqli_stmt_execute($stmt)) {
            $msg = "✅ Settings updated successfully!";
        } else {
            $msg = "❌ Error updating settings: " . mysqli_error($conn);
            debugMessage($msg);
        }
        mysqli_stmt_close($stmt);
    }

    // refresh settings array after update for UI display
    $result = mysqli_query($conn, "SELECT * FROM web_setting WHERE id = 1");
    $settings = mysqli_fetch_assoc($result);
}
?>

<!doctype html>
<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr"
    data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template" data-style="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Website Settings</title>
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
        .logo-preview {
            border: 2px dashed #ddd;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            /*background: #f9f9f9;*/
            margin-bottom: 15px;
        }
        .logo-preview img {
            max-width: 200px;
            max-height: 80px;
            object-fit: contain;
        }
        .favicon-preview img {
            max-width: 32px;
            max-height: 32px;
            object-fit: contain;
        }
        .preview-container {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
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
            border-radius: 34px;
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
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
                        <?php if (isset($msg)): ?>
                            <div class="alert alert-<?= strpos($msg, '✅') !== false ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
                                <?= htmlspecialchars($msg) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title mb-0">Website Settings</h4>
                            </div>
                            <div class="card-body">
                                <form action="" method="post" enctype="multipart/form-data" autocomplete="off">
                                    <div class="row">
                                        <!-- Left Column -->
                                        <div class="col-md-6">
                                            <!-- Website Name -->
                                            <div class="mb-4">
                                                <label for="web_name" class="form-label">Website Name</label>
                                                <input type="text" class="form-control" id="web_name" name="web_name" 
                                                       value="<?= htmlspecialchars($settings['web_name']) ?>" required>
                                            </div>

                                            <!-- Minimum Recharge -->
                                            <div class="mb-4">
                                                <label for="min_recharge" class="form-label">Minimum Recharge (₹)</label>
                                                <input type="number" class="form-control" id="min_recharge" name="min_recharge" 
                                                       value="<?= htmlspecialchars($settings['min_recharge']) ?>" min="0" required>
                                            </div>

                                            <!-- Minimum Withdrawal -->
                                            <div class="mb-4">
                                                <label for="min_withdrawal" class="form-label">Minimum Withdrawal (₹)</label>
                                                <input type="number" class="form-control" id="min_withdrawal" name="min_withdrawal" 
                                                       value="<?= htmlspecialchars($settings['min_withdrawal']) ?>" min="0" required>
                                            </div>

                                            <!-- Register Bonus -->
                                            <div class="mb-4">
                                                <label for="register_bonus" class="form-label">Register Bonus (₹)</label>
                                                <input type="number" class="form-control" id="register_bonus" name="register_bonus" 
                                                       value="<?= htmlspecialchars($settings['register_bonus']) ?>" min="0" required>
                                            </div>

                                            <!-- App Download Link -->
                                            <div class="mb-4">
                                                <label for="app_download" class="form-label">App Download Link</label>
                                                <input type="url" class="form-control" id="app_download" name="app_download" 
                                                       value="<?= htmlspecialchars($settings['app_download']) ?>" placeholder="https://...">
                                            </div>
                                        </div>

                                        <!-- Right Column -->
                                        <div class="col-md-6">
                                            <!-- Telegram Settings -->
                                            <div class="mb-4">
                                                <label class="form-label">Telegram Settings</label>
                                                <div class="d-flex gap-3 align-items-center mb-2">
                                                    <span>Telegram Active:</span>
                                                    <label class="toggle-switch">
                                                        <input type="checkbox" name="telegram_active" value="1" 
                                                               <?= $settings['telegram_active'] ? 'checked' : '' ?>>
                                                        <span class="slider"></span>
                                                    </label>
                                                </div>
                                                <input type="url" class="form-control" name="telegram_link" 
                                                       value="<?= htmlspecialchars($settings['telegram_link']) ?>" 
                                                       placeholder="Telegram Group/Channel Link">
                                            </div>
                                            
                                            <!-- Deposit Requirement Card -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h6 class="card-title mb-1">
                    <i class="ri-wallet-line me-2 text-primary"></i>Deposit Requirement
                </h6>
                <p class="card-text text-muted mb-0 small">
                    Control whether users need to deposit before playing
                </p>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" 
                           name="allowbet" 
                           value="1"
                           id="depositSwitch"
                           <?= $settings['allowbet'] == 1 ? 'checked' : '' ?>>
                    <label class="form-check-label visually-hidden" for="depositSwitch">
                        Toggle
                    </label>
                </div>
                <span class="badge rounded-pill bg-<?= $settings['allowbet'] == 1 ? 'success' : 'secondary' ?> px-3">
                    <?= $settings['allowbet'] == 1 ? 'ON' : 'OFF' ?>
                </span>
            </div>
        </div>
        <div class="mt-3">
            <div class="alert alert-<?= $settings['allowbet'] == 1 ? 'warning' : 'info' ?> border-0 py-2 mb-0">
                <i class="ri-<?= $settings['allowbet'] == 1 ? 'alert' : 'information' ?>-line me-2"></i>
                <?= $settings['allowbet'] == 1 
                    ? '<strong>Deposit Required:</strong> Users must make a deposit before accessing games' 
                    : '<strong>Deposit Optional:</strong> Users can play games without any deposit' ?>
            </div>
        </div>
    </div>
</div>

                                            <!-- Betting Settings -->
                                            <!--<div class="mb-4">-->
                                            <!--    <label class="form-label">Betting Settings</label>-->
                                            <!--    <div class="d-flex gap-3 align-items-center">-->
                                            <!--        <span>Allow Betting:</span>-->
                                            <!--        <label class="toggle-switch">-->
                                            <!--            <input type="checkbox" name="allowbet" value="1" -->
                                            <!--                   <?= $settings['allowbet'] ? 'checked' : '' ?>>-->
                                            <!--            <span class="slider"></span>-->
                                            <!--        </label>-->
                                            <!--    </div>-->
                                            <!--</div>-->

                                            <!-- Logo Upload -->
                                            <div class="mb-4">
                                                <label for="web_logo" class="form-label">Website Logo</label>
                                                <small class="text-muted d-block mb-2">(PNG, JPG, or WEBP only)</small>
                                                <input type="file" class="form-control" id="web_logo" name="web_logo" 
                                                       accept="image/png, image/jpeg, image/webp">
                                                
                                                <!-- Logo Preview -->
                                                <?php if (!empty($settings['web_logo'])): ?>
                                                <div class="logo-preview mt-2">
                                                    <p class="mb-2"><strong>Current Logo:</strong></p>
                                                    <img src="<?= htmlspecialchars($settings['web_logo']) ?>" 
                                                         alt="Website Logo" 
                                                         onerror="this.style.display='none'">
                                                    <p class="mt-2 text-muted small"><?= basename($settings['web_logo']) ?></p>
                                                </div>
                                                <?php else: ?>
                                                <div class="logo-preview mt-2">
                                                    <p class="text-muted">No logo uploaded</p>
                                                </div>
                                                <?php endif; ?>
                                            </div>

                                            <!-- Favicon Upload -->
                                            <div class="mb-4">
                                                <label for="favicon_logo" class="form-label">Favicon</label>
                                                <small class="text-muted d-block mb-2">(PNG, JPG, or WEBP only)</small>
                                                <input type="file" class="form-control" id="favicon_logo" name="favicon_logo" 
                                                       accept="image/png, image/jpeg, image/webp">
                                                
                                                <!-- Favicon Preview -->
                                                <?php if (!empty($settings['favicon_logo'])): ?>
                                                <div class="preview-container mt-2">
                                                    <div class="favicon-preview">
                                                        <img src="<?= htmlspecialchars($settings['favicon_logo']) ?>" 
                                                             alt="Favicon" 
                                                             onerror="this.style.display='none'">
                                                    </div>
                                                    <div>
                                                        <p class="mb-1"><strong>Current Favicon:</strong></p>
                                                        <p class="text-muted small mb-0"><?= basename($settings['favicon_logo']) ?></p>
                                                    </div>
                                                </div>
                                                <?php else: ?>
                                                <div class="preview-container mt-2">
                                                    <p class="text-muted">No favicon uploaded</p>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Submit Button -->
                                    <div class="row">
                                        <div class="col-12">
                                            <button type="submit" class="btn btn-primary btn-lg w-100">
                                                <i class="ri-save-line ri-16px me-2"></i>Save Settings
                                            </button>
                                        </div>
                                    </div>
                                </form>
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
        // Live preview for logo upload
        document.getElementById('web_logo').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    // Create or update preview
                    let preview = document.querySelector('.logo-preview');
                    if (!preview) {
                        preview = document.createElement('div');
                        preview.className = 'logo-preview mt-2';
                        e.target.parentNode.appendChild(preview);
                    }
                    preview.innerHTML = `
                        <p class="mb-2"><strong>New Logo Preview:</strong></p>
                        <img src="${e.target.result}" alt="Logo Preview" style="max-width: 200px; max-height: 80px;">
                        <p class="mt-2 text-muted small">${file.name}</p>
                    `;
                }
                reader.readAsDataURL(file);
            }
        });

        // Live preview for favicon upload
        document.getElementById('favicon_logo').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    // Create or update preview
                    let previewContainer = document.querySelector('.preview-container');
                    if (!previewContainer || previewContainer.querySelector('.text-muted')) {
                        previewContainer = document.createElement('div');
                        previewContainer.className = 'preview-container mt-2';
                        e.target.parentNode.appendChild(previewContainer);
                    }
                    previewContainer.innerHTML = `
                        <div class="favicon-preview">
                            <img src="${e.target.result}" alt="Favicon Preview" style="max-width: 32px; max-height: 32px;">
                        </div>
                        <div>
                            <p class="mb-1"><strong>New Favicon Preview:</strong></p>
                            <p class="text-muted small mb-0">${file.name}</p>
                        </div>
                    `;
                }
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>