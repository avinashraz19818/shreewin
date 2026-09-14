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

// Handle banner upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['banner'])) {
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }

    $upload_dir = "uploads/Banners/";
    $domain = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http')
        . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/digitaladmin/';

    // Create uploads directory if it doesn't exist
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    // Generate unique file name
    $timestamp = time();
    $random_str = substr(md5(rand()), 0, 7);
    $file_name = $timestamp . "_Banner_" . date("YmdHis") . $random_str . "." . pathinfo($_FILES["banner"]["name"], PATHINFO_EXTENSION);
    $target_path = $upload_dir . $file_name;
    $full_url = $domain . $target_path;

    // Upload file to server
    if (move_uploaded_file($_FILES["banner"]["tmp_name"], $target_path)) {
        // Insert banner URL into database
        $stmt = mysqli_prepare($conn, "INSERT INTO banners (banner_url, is_active) VALUES (?, 1)");
        mysqli_stmt_bind_param($stmt, "s", $full_url);

        if (mysqli_stmt_execute($stmt)) {
            $upload_message = "✅ Banner uploaded and saved successfully!";
            $message_type = "success";
        } else {
            $upload_message = "❌ File uploaded, but failed to save to database.";
            $message_type = "error";
        }

        mysqli_stmt_close($stmt);
    } else {
        $upload_message = "❌ Failed to upload the banner.";
        $message_type = "error";
    }
}

// Update redirect URL logic
if (isset($_POST['updateLink'])) {
    $id = mysqli_real_escape_string($conn, $_POST['id']);
    $redirectUrl = mysqli_real_escape_string($conn, $_POST['redirectUrl']);

    $update_url = "UPDATE banners SET redirect_url = '$redirectUrl' WHERE id = '$id'";
    if (mysqli_query($conn, $update_url)) {
        $update_message = "✅ Redirect URL updated successfully!";
        $message_type = "success";
    } else {
        $update_message = "❌ Failed to update URL.";
        $message_type = "error";
    }
}

// Toggle active status
if (isset($_POST['toggleStatus'])) {
    $id = mysqli_real_escape_string($conn, $_POST['id']);
    $current_status = mysqli_real_escape_string($conn, $_POST['current_status']);
    $new_status = $current_status == 1 ? 0 : 1;

    $toggle_query = "UPDATE banners SET is_active = '$new_status' WHERE id = '$id'";
    if (mysqli_query($conn, $toggle_query)) {
        $toggle_message = "✅ Banner status updated successfully!";
        $message_type = "success";
    } else {
        $toggle_message = "❌ Failed to update banner status.";
        $message_type = "error";
    }
}

// Delete banner logic
if (isset($_POST['deleteBanner'])) {
    $banner_id = intval($_POST['id']);

    // Step 1: Fetch banner URL from DB
    $fetch_query = "SELECT banner_url FROM banners WHERE id = $banner_id";
    $result = mysqli_query($conn, $fetch_query);
    $row = mysqli_fetch_assoc($result);

    if ($row) {
        $banner_url = $row['banner_url'];

        // Step 2: Extract relative path from URL
        $parsed_url = parse_url($banner_url, PHP_URL_PATH);

        // Step 3: Convert to full path relative to this script
        $file_path = $_SERVER['DOCUMENT_ROOT'] . $parsed_url;

        // Step 4: Delete file from server if it exists
        if (file_exists($file_path)) {
            unlink($file_path);
        }

        // Step 5: Delete DB entry
        $delete_query = "DELETE FROM banners WHERE id = $banner_id";
        if (mysqli_query($conn, $delete_query)) {
            $delete_message = "✅ Banner deleted successfully.";
            $message_type = "success";
        } else {
            $delete_message = "❌ Failed to delete the banner from database.";
            $message_type = "error";
        }
    } else {
        $delete_message = "❌ Banner not found.";
        $message_type = "error";
    }
}

// Fetch all banners
$banners_query = "SELECT * FROM banners ORDER BY id DESC";
$banners_result = mysqli_query($conn, $banners_query);
$total_banners = mysqli_num_rows($banners_result);
?>

<!doctype html>
<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr"
    data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template" data-style="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Banners Management</title>
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
        .banner-preview {
            max-width: 300px;
            max-height: 150px;
            object-fit: contain;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 5px;
            background: #f9f9f9;
        }

        .banner-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            /*background: #fff;*/
            transition: all 0.3s ease;
        }

        .banner-card.inactive {
            opacity: 0.6;
            /*background: #f8f9fa;*/
        }

        .url-input {
            font-size: 12px;
            font-family: monospace;
        }

        .status-toggle {
            cursor: pointer;
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

        input:checked+.slider {
            background-color: #2196F3;
        }

        input:checked+.slider:before {
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
                        <?php if (isset($upload_message)): ?>
                            <div class="alert alert-<?= $message_type === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
                                <?= htmlspecialchars($upload_message) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($update_message)): ?>
                            <div class="alert alert-<?= $message_type === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
                                <?= htmlspecialchars($update_message) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($toggle_message)): ?>
                            <div class="alert alert-<?= $message_type === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
                                <?= htmlspecialchars($toggle_message) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($delete_message)): ?>
                            <div class="alert alert-<?= $message_type === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
                                <?= htmlspecialchars($delete_message) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <div class="row">
                            <!-- Upload Banner Section -->
                            <div class="col-md-4">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Upload New Banner</h5>
                                    </div>
                                    <div class="card-body">
                                        <form action="" method="post" enctype="multipart/form-data">
                                            <div class="mb-3">
                                                <label for="banner" class="form-label">Select Banner Image</label>
                                                <input type="file" class="form-control" id="banner" name="banner"
                                                    accept="image/png, image/jpeg, image/jpg, image/gif" required>
                                                <div class="form-text">
                                                    Supported formats: PNG, JPG, JPEG, GIF
                                                </div>
                                            </div>
                                            <button type="submit" class="btn btn-primary w-100">
                                                <i class="ri-upload-cloud-line ri-16px me-2"></i>Upload Banner
                                            </button>
                                        </form>
                                    </div>
                                </div>

                                <!-- Statistics -->
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Banners Statistics</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <span>Total Banners:</span>
                                            <span class="badge bg-primary"><?= $total_banners ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <span>Active Banners:</span>
                                            <span class="badge bg-success">
                                                <?php
                                                $active_query = "SELECT COUNT(*) as active FROM banners WHERE is_active = 1";
                                                $active_result = mysqli_query($conn, $active_query);
                                                $active_count = mysqli_fetch_assoc($active_result)['active'];
                                                echo $active_count;
                                                ?>
                                            </span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span>Inactive Banners:</span>
                                            <span class="badge bg-secondary">
                                                <?php
                                                $inactive_query = "SELECT COUNT(*) as inactive FROM banners WHERE is_active = 0";
                                                $inactive_result = mysqli_query($conn, $inactive_query);
                                                $inactive_count = mysqli_fetch_assoc($inactive_result)['inactive'];
                                                echo $inactive_count;
                                                ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- All Banners Section -->
                            <div class="col-md-8">
                                <div class="card">
                                    <div class="card-header">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h5 class="card-title mb-0">All Banners (<?= $total_banners ?>)</h5>
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-primary active" onclick="filterBanners('all')">All</button>
                                                <button type="button" class="btn btn-outline-success" onclick="filterBanners('active')">Active</button>
                                                <button type="button" class="btn btn-outline-secondary" onclick="filterBanners('inactive')">Inactive</button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <?php if ($total_banners > 0): ?>
                                            <div class="row" id="bannersContainer">
                                                <?php while ($banner = mysqli_fetch_assoc($banners_result)): ?>
                                                    <div class="col-md-6 mb-4 banner-item" data-status="<?= $banner['is_active'] ? 'active' : 'inactive' ?>">
                                                        <div class="banner-card <?= $banner['is_active'] ? '' : 'inactive' ?>">
                                                            <!-- Banner Preview -->
                                                            <div class="text-center mb-3">
                                                                <img src="<?= htmlspecialchars($banner['banner_url']) ?>"
                                                                    alt="Banner <?= $banner['id'] ?>"
                                                                    class="banner-preview"
                                                                    onerror="this.style.display='none'; this.nextElementSibling.style.display='block'">
                                                                <div style="display: none; color: #ff6b6b;" class="mt-2">
                                                                    <i class="ri-error-warning-line"></i> Image not available
                                                                </div>
                                                            </div>

                                                            <!-- Status Toggle -->
                                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                                <span class="fw-medium">Status:</span>
                                                                <form action="" method="post" class="d-inline">
                                                                    <input type="hidden" name="id" value="<?= $banner['id'] ?>">
                                                                    <input type="hidden" name="current_status" value="<?= $banner['is_active'] ?>">
                                                                    <input type="hidden" name="toggleStatus" value="1">
                                                                    <label class="toggle-switch status-toggle">
                                                                        <input type="checkbox" <?= $banner['is_active'] ? 'checked' : '' ?>
                                                                            onchange="this.form.submit()">
                                                                        <span class="slider"></span>
                                                                    </label>
                                                                </form>
                                                            </div>

                                                            <!-- Redirect URL Form -->
                                                            <form action="" method="post" class="mb-3">
                                                                <input type="hidden" name="id" value="<?= $banner['id'] ?>">
                                                                <div class="input-group input-group-sm">
                                                                    <input type="url" class="form-control url-input"
                                                                        name="redirectUrl"
                                                                        value="<?= htmlspecialchars($banner['redirect_url'] ?? '') ?>"
                                                                        placeholder="https://example.com"
                                                                        pattern="https?://.+"
                                                                        title="Enter a valid URL starting with http:// or https://">
                                                                    <button type="submit" name="updateLink" class="btn btn-outline-primary">
                                                                        <i class="ri-link ri-14px"></i>
                                                                    </button>
                                                                </div>
                                                                <div class="form-text small">
                                                                    Set redirect URL for this banner
                                                                </div>
                                                            </form>

                                                            <!-- Banner Info & Actions -->
                                                            <div class="d-flex justify-content-between align-items-center">
                                                                <div>
                                                                    <span class="badge bg-<?= $banner['is_active'] ? 'success' : 'secondary' ?>">
                                                                        <?= $banner['is_active'] ? 'Active' : 'Inactive' ?>
                                                                    </span>
                                                                    <small class="text-muted d-block mt-1">
                                                                        ID: <?= $banner['id'] ?>
                                                                    </small>
                                                                </div>

                                                                <!-- Delete Form -->
                                                                <form action="" method="post" onsubmit="return confirm('Are you sure you want to delete this banner?')">
                                                                    <input type="hidden" name="id" value="<?= $banner['id'] ?>">
                                                                    <button type="submit" name="deleteBanner" class="btn btn-sm btn-danger">
                                                                        <i class="ri-delete-bin-line"></i> Delete
                                                                    </button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endwhile; ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="text-center py-5">
                                                <div class="avatar avatar-lg mb-3">
                                                    <span class="avatar-initial rounded bg-label-secondary">
                                                        <i class="ri-image-line ri-24px"></i>
                                                    </span>
                                                </div>
                                                <h5 class="text-muted">No Banners Found</h5>
                                                <p class="text-muted">Upload your first banner using the form on the left.</p>
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
        // Live banner preview
        document.getElementById('banner').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    // Check if preview container exists
                    let previewContainer = document.getElementById('bannerPreview');
                    if (!previewContainer) {
                        previewContainer = document.createElement('div');
                        previewContainer.id = 'bannerPreview';
                        previewContainer.className = 'text-center mt-3';
                        e.target.parentNode.appendChild(previewContainer);
                    }

                    previewContainer.innerHTML = `
                        <p class="mb-2"><strong>Preview:</strong></p>
                        <img src="${e.target.result}" alt="Banner Preview" 
                             style="max-width: 100%; max-height: 150px; border: 2px dashed #ddd; border-radius: 8px; padding: 5px;">
                        <p class="text-muted small mt-2">${file.name}</p>
                    `;
                }
                reader.readAsDataURL(file);
            }
        });

        // Filter banners
        function filterBanners(status) {
            const banners = document.querySelectorAll('.banner-item');
            const filterButtons = document.querySelectorAll('.btn-group .btn');

            // Update active button
            filterButtons.forEach(btn => {
                btn.classList.remove('active');
                if (btn.textContent.toLowerCase() === status) {
                    btn.classList.add('active');
                }
            });

            // Show/hide banners based on filter
            banners.forEach(banner => {
                if (status === 'all' || banner.getAttribute('data-status') === status) {
                    banner.style.display = 'block';
                } else {
                    banner.style.display = 'none';
                }
            });
        }

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>

</html>