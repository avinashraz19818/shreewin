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

// Create table if not exists
$createTableSQL = "
CREATE TABLE IF NOT EXISTS langcurrency (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lang_code VARCHAR(10) NOT NULL UNIQUE,
    lang_name VARCHAR(100) NOT NULL,
    flag_url VARCHAR(255),
    currency_code VARCHAR(10) NOT NULL,
    currency_symbol VARCHAR(10) NOT NULL,
    currency_name VARCHAR(100) NOT NULL,
    is_default TINYINT(1) DEFAULT 0,
    status TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

$conn->query($createTableSQL);

$success = false;
$error = '';

// Add Entry
if (isset($_POST['add'])) {
    $lang_code = mysqli_real_escape_string($conn, $_POST['lang_code']);
    $lang_name = mysqli_real_escape_string($conn, $_POST['lang_name']);
    $flag_url = mysqli_real_escape_string($conn, $_POST['flag_url']);
    $currency_code = mysqli_real_escape_string($conn, $_POST['currency_code']);
    $currency_symbol = mysqli_real_escape_string($conn, $_POST['currency_symbol']);
    $currency_name = mysqli_real_escape_string($conn, $_POST['currency_name']);
    $is_default = isset($_POST['is_default']) ? 1 : 0;
    $status = isset($_POST['status']) ? 1 : 0;

    // Validate required fields
    if (empty($lang_code) || empty($lang_name) || empty($currency_code) || empty($currency_symbol) || empty($currency_name)) {
        $error = "Please fill all required fields";
    } else {
        if ($is_default == 1) {
            mysqli_query($conn, "UPDATE langcurrency SET is_default = 0");
        }

        $sql = "INSERT INTO langcurrency 
                (lang_code, lang_name, flag_url, currency_code, currency_symbol, currency_name, is_default, status)
                VALUES 
                ('$lang_code', '$lang_name', '$flag_url', '$currency_code', '$currency_symbol', '$currency_name', $is_default, $status)";
        
        if (mysqli_query($conn, $sql)) {
            $success = true;
        } else {
            $error = "Insert failed: " . mysqli_error($conn);
        }
    }
}

// Update Entry
if (isset($_POST['update'])) {
    $id = (int)$_POST['id'];
    $lang_code = mysqli_real_escape_string($conn, $_POST['lang_code']);
    $lang_name = mysqli_real_escape_string($conn, $_POST['lang_name']);
    $flag_url = mysqli_real_escape_string($conn, $_POST['flag_url']);
    $currency_code = mysqli_real_escape_string($conn, $_POST['currency_code']);
    $currency_symbol = mysqli_real_escape_string($conn, $_POST['currency_symbol']);
    $currency_name = mysqli_real_escape_string($conn, $_POST['currency_name']);
    $is_default = isset($_POST['is_default']) ? 1 : 0;
    $status = isset($_POST['status']) ? 1 : 0;

    if (empty($lang_code) || empty($lang_name) || empty($currency_code) || empty($currency_symbol) || empty($currency_name)) {
        $error = "Please fill all required fields";
    } else {
        if ($is_default == 1) {
            mysqli_query($conn, "UPDATE langcurrency SET is_default = 0");
        }

        $sql = "UPDATE langcurrency SET 
                lang_code = '$lang_code',
                lang_name = '$lang_name', 
                flag_url = '$flag_url',
                currency_code = '$currency_code',
                currency_symbol = '$currency_symbol',
                currency_name = '$currency_name',
                is_default = $is_default,
                status = $status
                WHERE id = $id";
        
        if (mysqli_query($conn, $sql)) {
            $success = true;
        } else {
            $error = "Update failed: " . mysqli_error($conn);
        }
    }
}

// Delete Entry
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM langcurrency WHERE id = $id");
    $success = true;
}

// Set Default
if (isset($_GET['set_default'])) {
    $id = (int)$_GET['set_default'];
    mysqli_query($conn, "UPDATE langcurrency SET is_default = 0");
    mysqli_query($conn, "UPDATE langcurrency SET is_default = 1 WHERE id = $id");
    $success = true;
}

// Toggle Status
if (isset($_GET['toggle_status'])) {
    $id = (int)$_GET['toggle_status'];
    mysqli_query($conn, "UPDATE langcurrency SET status = IF(status=1,0,1) WHERE id = $id");
    $success = true;
}

// Fetch all entries
$entries = mysqli_query($conn, "SELECT * FROM langcurrency ORDER BY is_default DESC, lang_name ASC");

// Edit data
$editData = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $res = mysqli_query($conn, "SELECT * FROM langcurrency WHERE id=$editId");
    $editData = mysqli_fetch_assoc($res);
}

// Store entries in array for display
$entriesArray = [];
if ($entries->num_rows > 0) {
    while ($entry = $entries->fetch_assoc()) {
        $entriesArray[] = $entry;
    }
}

// Calculate statistics
$totalCount = count($entriesArray);
$activeCount = 0;
$defaultCount = 0;

foreach ($entriesArray as $entry) {
    if ($entry['status']) {
        $activeCount++;
    }
    if ($entry['is_default']) {
        $defaultCount++;
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr"
    data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template" data-style="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Language & Currency Management</title>
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
        .entry-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            /*background: #fff;*/
            transition: all 0.3s ease;
        }
        .entry-card.inactive {
            opacity: 0.6;
            /*background: #f8f9fa;*/
        }
        .entry-card.default {
            border: 2px solid #28a745;
            /*background: #f8fff9;*/
        }
        .entry-card:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .edit-form-container {
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            border: 2px solid #0d6efd;
        }
        .flag-preview {
            width: 30px;
            height: 20px;
            border: 1px solid #ddd;
            border-radius: 3px;
            object-fit: cover;
        }
        .stats-value {
            font-size: 24px;
            font-weight: bold;
        }
        .stats-label {
            font-size: 14px;
            color: #6c757d;
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
                            <?php if ($success): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    ✅ Operation completed successfully!
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($error)): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    ❌ Error: <?= htmlspecialchars($error) ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="row">
                            <!-- Language & Currency Form -->
                            <div class="col-md-6">
                                <?php if (isset($_GET['edit'])): ?>
                                <div class="edit-form-container">
                                    <h4 class="text-primary mb-3">✏️ Editing: <?= htmlspecialchars($editData['lang_name']) ?></h4>
                                <?php endif; ?>
                                
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <?= isset($_GET['edit']) ? 'Edit Language & Currency' : 'Add New Language & Currency' ?>
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <form method="POST">
                                            <?php if (isset($_GET['edit'])): ?>
                                                <input type="hidden" name="id" value="<?= $editData['id'] ?>">
                                                <input type="hidden" name="update" value="1">
                                            <?php else: ?>
                                                <input type="hidden" name="add" value="1">
                                            <?php endif; ?>
                                            
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label for="lang_code" class="form-label">Language Code *</label>
                                                    <input type="text" class="form-control" id="lang_code" name="lang_code" 
                                                           value="<?= htmlspecialchars($editData['lang_code'] ?? '') ?>" required 
                                                           placeholder="en, hi, es, etc." maxlength="10">
                                                    <div class="form-text">2-10 character code (e.g., en, hi, es)</div>
                                                </div>
                                                
                                                <div class="col-md-6 mb-3">
                                                    <label for="lang_name" class="form-label">Language Name *</label>
                                                    <input type="text" class="form-control" id="lang_name" name="lang_name" 
                                                           value="<?= htmlspecialchars($editData['lang_name'] ?? '') ?>" required 
                                                           placeholder="English, Hindi, Spanish">
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label for="flag_url" class="form-label">Flag Image URL</label>
                                                <input type="url" class="form-control" id="flag_url" name="flag_url" 
                                                       value="<?= htmlspecialchars($editData['flag_url'] ?? '') ?>" 
                                                       placeholder="https://example.com/flag.png">
                                                <div class="form-text">Optional: URL to flag image</div>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-4 mb-3">
                                                    <label for="currency_code" class="form-label">Currency Code *</label>
                                                    <input type="text" class="form-control" id="currency_code" name="currency_code" 
                                                           value="<?= htmlspecialchars($editData['currency_code'] ?? '') ?>" required 
                                                           placeholder="USD, INR, EUR" maxlength="10">
                                                </div>
                                                
                                                <div class="col-md-4 mb-3">
                                                    <label for="currency_symbol" class="form-label">Currency Symbol *</label>
                                                    <input type="text" class="form-control" id="currency_symbol" name="currency_symbol" 
                                                           value="<?= htmlspecialchars($editData['currency_symbol'] ?? '') ?>" required 
                                                           placeholder="$, ₹, €" maxlength="10">
                                                </div>
                                                
                                                <div class="col-md-4 mb-3">
                                                    <label for="currency_name" class="form-label">Currency Name *</label>
                                                    <input type="text" class="form-control" id="currency_name" name="currency_name" 
                                                           value="<?= htmlspecialchars($editData['currency_name'] ?? '') ?>" required 
                                                           placeholder="US Dollar, Indian Rupee, Euro">
                                                </div>
                                            </div>

                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" id="is_default" 
                                                               name="is_default" value="1" <?= ($editData['is_default'] ?? 0) ? 'checked' : '' ?>>
                                                        <label class="form-check-label" for="is_default">
                                                            Set as Default
                                                        </label>
                                                    </div>
                                                    <div class="form-text">Only one language can be default</div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" id="status" 
                                                               name="status" value="1" <?= ($editData['status'] ?? 1) ? 'checked' : '' ?>>
                                                        <label class="form-check-label" for="status">
                                                            Active
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="mt-4">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="ri-save-line ri-16px me-2"></i>
                                                    <?= isset($_GET['edit']) ? 'Update Entry' : 'Add Entry' ?>
                                                </button>
                                                <?php if (isset($_GET['edit'])): ?>
                                                    <a href="manage_lang_currency.php" class="btn btn-secondary">
                                                        <i class="ri-close-line ri-16px me-2"></i>Cancel Edit
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                
                                <?php if (isset($_GET['edit'])): ?>
                                </div> <!-- Close edit form container -->
                                <?php endif; ?>

                                <!-- Preview Section -->
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Entry Preview</h5>
                                    </div>
                                    <div class="card-body">
                                        <div id="entryPreview" class="d-flex align-items-center p-3 bg-light rounded">
                                            <?php if (isset($editData)): ?>
                                                <?php if (!empty($editData['flag_url'])): ?>
                                                    <img src="<?= htmlspecialchars($editData['flag_url']) ?>" class="flag-preview me-3" alt="Flag">
                                                <?php else: ?>
                                                    <div class="flag-preview bg-secondary me-3 d-flex align-items-center justify-content-center">
                                                        <i class="ri-flag-line text-white"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <strong><?= htmlspecialchars($editData['lang_name']) ?> (<?= htmlspecialchars($editData['lang_code']) ?>)</strong>
                                                    <div class="text-muted small">
                                                        <?= htmlspecialchars($editData['currency_name']) ?> (<?= htmlspecialchars($editData['currency_symbol']) ?><?= htmlspecialchars($editData['currency_code']) ?>)
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <p class="text-muted mb-0">Preview will appear here when you start typing...</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Existing Entries -->
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h5 class="card-title mb-0">Language & Currency Settings</h5>
                                            <span class="badge bg-primary">
                                                <?= $totalCount ?> Total
                                            </span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <?php if ($totalCount > 0): ?>
                                            <div id="entriesList">
                                                <?php foreach ($entriesArray as $entry): ?>
                                                    <div class="entry-card <?= !$entry['status'] ? 'inactive' : '' ?> <?= $entry['is_default'] ? 'default' : '' ?>" 
                                                         data-id="<?= $entry['id'] ?>">
                                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                                            <div class="d-flex align-items-center">
                                                                <?php if (!empty($entry['flag_url'])): ?>
                                                                    <img src="<?= htmlspecialchars($entry['flag_url']) ?>" class="flag-preview me-3" alt="Flag">
                                                                <?php else: ?>
                                                                    <div class="flag-preview bg-secondary me-3 d-flex align-items-center justify-content-center">
                                                                        <i class="ri-flag-line text-white"></i>
                                                                    </div>
                                                                <?php endif; ?>
                                                                <div>
                                                                    <h6 class="mb-1">
                                                                        <?= htmlspecialchars($entry['lang_name']) ?> 
                                                                        <small class="text-muted">(<?= htmlspecialchars($entry['lang_code']) ?>)</small>
                                                                    </h6>
                                                                    <div class="small">
                                                                        <?= htmlspecialchars($entry['currency_name']) ?> 
                                                                        (<?= htmlspecialchars($entry['currency_symbol']) ?><?= htmlspecialchars($entry['currency_code']) ?>)
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="dropdown">
                                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                                                        type="button" data-bs-toggle="dropdown">
                                                                    <i class="ri-more-2-line"></i>
                                                                </button>
                                                                <ul class="dropdown-menu">
                                                                    <li>
                                                                        <a class="dropdown-item" 
                                                                           href="?edit=<?= $entry['id'] ?>">
                                                                            <i class="ri-edit-line me-2"></i>Edit
                                                                        </a>
                                                                    </li>
                                                                    <?php if (!$entry['is_default']): ?>
                                                                        <li>
                                                                            <a class="dropdown-item" href="?set_default=<?= $entry['id'] ?>">
                                                                                <i class="ri-star-line me-2"></i>Set as Default
                                                                            </a>
                                                                        </li>
                                                                    <?php endif; ?>
                                                                    <li>
                                                                        <a class="dropdown-item" href="?toggle_status=<?= $entry['id'] ?>">
                                                                            <i class="ri-toggle-line me-2"></i>
                                                                            <?= $entry['status'] ? 'Deactivate' : 'Activate' ?>
                                                                        </a>
                                                                    </li>
                                                                    <li>
                                                                        <a class="dropdown-item text-danger delete-entry" 
                                                                           href="?delete=<?= $entry['id'] ?>" 
                                                                           onclick="return confirm('Are you sure you want to delete this entry?')">
                                                                            <i class="ri-delete-bin-line me-2"></i>Delete
                                                                        </a>
                                                                    </li>
                                                                </ul>
                                                            </div>
                                                        </div>
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <div>
                                                                <?php if ($entry['is_default']): ?>
                                                                    <span class="badge bg-success me-2">
                                                                        <i class="ri-star-fill me-1"></i>Default
                                                                    </span>
                                                                <?php endif; ?>
                                                                <span class="badge bg-<?= $entry['status'] ? 'success' : 'secondary' ?>">
                                                                    <?= $entry['status'] ? 'Active' : 'Inactive' ?>
                                                                </span>
                                                            </div>
                                                            <div class="small text-muted">
                                                                Created: <?= date('M j, Y', strtotime($entry['created_at'])) ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="text-center py-4">
                                                <div class="avatar avatar-lg mb-3">
                                                    <span class="avatar-initial rounded bg-label-secondary">
                                                        <i class="ri-global-line ri-24px"></i>
                                                    </span>
                                                </div>
                                                <h5 class="text-muted">No Language & Currency Settings</h5>
                                                <p class="text-muted">Add your first entry using the form.</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Statistics -->
                                <div class="card mt-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Statistics</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row text-center">
                                            <div class="col-4">
                                                <div class="stats-value"><?= $totalCount ?></div>
                                                <div class="stats-label">Total</div>
                                            </div>
                                            <div class="col-4">
                                                <div class="stats-value text-success"><?= $activeCount ?></div>
                                                <div class="stats-label">Active</div>
                                            </div>
                                            <div class="col-4">
                                                <div class="stats-value text-warning"><?= $defaultCount ?></div>
                                                <div class="stats-label">Default</div>
                                            </div>
                                        </div>
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
        // Live preview update
        function updatePreview() {
            const langCode = document.getElementById('lang_code').value || 'en';
            const langName = document.getElementById('lang_name').value || 'Language Name';
            const flagUrl = document.getElementById('flag_url').value;
            const currencyCode = document.getElementById('currency_code').value || 'USD';
            const currencySymbol = document.getElementById('currency_symbol').value || '$';
            const currencyName = document.getElementById('currency_name').value || 'Currency Name';
            
            let flagHtml = '';
            if (flagUrl) {
                flagHtml = `<img src="${flagUrl}" class="flag-preview me-3" alt="Flag">`;
            } else {
                flagHtml = `<div class="flag-preview bg-secondary me-3 d-flex align-items-center justify-content-center">
                    <i class="ri-flag-line text-white"></i>
                </div>`;
            }
            
            const previewHTML = `
                ${flagHtml}
                <div>
                    <strong>${langName} (${langCode})</strong>
                    <div class="text-muted small">
                        ${currencyName} (${currencySymbol}${currencyCode})
                    </div>
                </div>
            `;
            
            document.getElementById('entryPreview').innerHTML = previewHTML;
        }

        // Add event listeners for live preview
        document.getElementById('lang_code').addEventListener('input', updatePreview);
        document.getElementById('lang_name').addEventListener('input', updatePreview);
        document.getElementById('flag_url').addEventListener('input', updatePreview);
        document.getElementById('currency_code').addEventListener('input', updatePreview);
        document.getElementById('currency_symbol').addEventListener('input', updatePreview);
        document.getElementById('currency_name').addEventListener('input', updatePreview);

        // Initialize preview
        document.addEventListener('DOMContentLoaded', function() {
            updatePreview();
        });

        // Enhanced delete confirmation
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('delete-entry')) {
                if (!confirm('Are you sure you want to delete this entry? This action cannot be undone.')) {
                    e.preventDefault();
                }
            }
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>