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

$isEdit = false;
$editData = null;

// Handle DELETE request
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM table_activity WHERE id = $id");
    header("Location: update_activitybanner.php?msg=deleted");
    exit;
}

// Handle EDIT request
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $get = $conn->query("SELECT * FROM table_activity WHERE id = $id");
    $editData = $get->fetch_assoc();
    $isEdit = true;
}

// Handle active/inactive toggle
if (isset($_POST['toggle_active'])) {
    $id = intval($_POST['id']);
    $current_status = intval($_POST['current_status']);
    $new_status = $current_status == 1 ? 0 : 1;
    
    $update_query = "UPDATE table_activity SET active = $new_status WHERE id = $id";
    if (mysqli_query($conn, $update_query)) {
        $toggle_message = "✅ Activity status updated successfully!";
        $message_type = "success";
    } else {
        $toggle_message = "❌ Failed to update activity status.";
        $message_type = "error";
    }
}

// Handle INSERT/UPDATE request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bannerTitle'])) {
    $title = $_POST['bannerTitle'];
    $bannerID = $_POST['bannerID'];
    $url = $_POST['bannerUrl'];
    $jump = $_POST['jumpType'];
    $contents = $_POST['contents'];
    $active = isset($_POST['active']) ? 1 : 0;

    if (isset($_POST['edit_id'])) {
        $editId = $_POST['edit_id'];
        $stmt = $conn->prepare("UPDATE table_activity SET bannerTitle=?, bannerID=?, bannerUrl=?, jumpType=?, contents=?, active=? WHERE id=?");
        $stmt->bind_param("sisisii", $title, $bannerID, $url, $jump, $contents, $active, $editId);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("INSERT INTO table_activity (bannerTitle, bannerID, bannerUrl, jumpType, contents, active) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sisisi", $title, $bannerID, $url, $jump, $contents, $active);
        $stmt->execute();
    }

    header("Location: update_activitybanner.php?msg=saved");
    exit;
}

// Fetch all activities
$activities_query = "SELECT * FROM table_activity ORDER BY id DESC";
$activities_result = mysqli_query($conn, $activities_query);
$total_activities = mysqli_num_rows($activities_result);

// Handle messages
if (isset($_GET['msg'])) {
    $msg = $_GET['msg'];
    if ($msg == 'saved') {
        $message = "✅ Activity saved successfully!";
        $message_type = "success";
    } elseif ($msg == 'deleted') {
        $message = "✅ Activity deleted successfully!";
        $message_type = "success";
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
    <title>Activity Banners Management</title>
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
        .activity-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            /*background: #fff;*/
            transition: all 0.3s ease;
        }
        .activity-card.inactive {
            opacity: 0.6;
            /*background: #f8f9fa;*/
        }
        .activity-card:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .url-input {
            font-size: 12px;
            font-family: monospace;
        }
        .content-preview {
            max-height: 100px;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
        }
        .jump-badge {
            font-size: 11px;
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
        .banner-preview {
            max-width: 100%;
            max-height: 120px;
            object-fit: contain;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 5px;
            background: #f9f9f9;
            margin-bottom: 10px;
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
                        <?php if (isset($message)): ?>
                            <div class="alert alert-<?= $message_type === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
                                <?= htmlspecialchars($message) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($toggle_message)): ?>
                            <div class="alert alert-<?= $message_type === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
                                <?= htmlspecialchars($toggle_message) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <div class="row">
                            <!-- Add/Edit Activity Section -->
                            <div class="col-md-4">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <?= $isEdit ? 'Edit Activity' : 'Add New Activity' ?>
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <form action="" method="post">
                                            <?php if ($isEdit): ?>
                                                <input type="hidden" name="edit_id" value="<?= $editData['id'] ?>">
                                            <?php endif; ?>
                                            
                                            <!-- Banner Title -->
                                            <div class="mb-3">
                                                <label for="bannerTitle" class="form-label">Banner Title *</label>
                                                <input type="text" class="form-control" id="bannerTitle" name="bannerTitle" 
                                                       value="<?= $isEdit ? htmlspecialchars($editData['bannerTitle']) : '' ?>" 
                                                       required placeholder="Enter banner title">
                                            </div>

                                            <!-- Banner ID -->
                                            <div class="mb-3">
                                                <label for="bannerID" class="form-label">Banner ID *</label>
                                                <input type="number" class="form-control" id="bannerID" name="bannerID" 
                                                       value="<?= $isEdit ? htmlspecialchars($editData['bannerID']) : '' ?>" 
                                                       required placeholder="Enter banner ID">
                                            </div>

                                            <!-- Banner URL -->
                                            <div class="mb-3">
                                                <label for="bannerUrl" class="form-label">Banner URL</label>
                                                <input type="url" class="form-control" id="bannerUrl" name="bannerUrl" 
                                                       value="<?= $isEdit ? htmlspecialchars($editData['bannerUrl']) : '' ?>" 
                                                       placeholder="https://example.com">
                                            </div>

                                            <!-- Active Status -->
                                            <div class="mb-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="active" name="active" value="1"
                                                           <?= ($isEdit && $editData['active'] == 1) ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="active">
                                                        Active Activity
                                                    </label>
                                                </div>
                                            </div>

                                            <!-- Jump Type -->
                                            <div class="mb-3">
                                                <label for="jumpType" class="form-label">Jump Type *</label>
                                                <select class="form-select" id="jumpType" name="jumpType" required>
                                                    <option value="">Select Jump Type</option>
                                                    <option value="1" <?= (isset($editData['jumpType']) && $editData['jumpType'] == 1) ? "selected" : "" ?>>Internal</option>
                                                        <option value="2" <?= (isset($editData['jumpType']) && $editData['jumpType'] == 2) ? "selected" : "" ?>>External</option>
                                                </select>
                                            </div>

                                            <!-- Contents -->
                                            <div class="mb-3">
                                                <label for="contents" class="form-label">Contents</label>
                                                <textarea class="form-control" id="contents" name="contents" 
                                                          rows="4" placeholder="Enter activity contents"><?= $isEdit ? htmlspecialchars($editData['contents']) : '' ?></textarea>
                                            </div>

                                            <div class="d-grid gap-2">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="ri-save-line ri-16px me-2"></i>
                                                    <?= $isEdit ? 'Update Activity' : 'Add Activity' ?>
                                                </button>
                                                <?php if ($isEdit): ?>
                                                    <a href="update_activitybanner.php" class="btn btn-secondary">
                                                        <i class="ri-close-line ri-16px me-2"></i>Cancel Edit
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                                <!-- Statistics -->
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Activity Statistics</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <span>Total Activities:</span>
                                            <span class="badge bg-primary"><?= $total_activities ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <span>Active Activities:</span>
                                            <span class="badge bg-success">
                                                <?php 
                                                $active_query = "SELECT COUNT(*) as active FROM table_activity WHERE active = 1";
                                                $active_result = mysqli_query($conn, $active_query);
                                                $active_count = mysqli_fetch_assoc($active_result)['active'];
                                                echo $active_count;
                                                ?>
                                            </span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <span>Inactive Activities:</span>
                                            <span class="badge bg-secondary">
                                                <?php 
                                                $inactive_query = "SELECT COUNT(*) as inactive FROM table_activity WHERE active = 0";
                                                $inactive_result = mysqli_query($conn, $inactive_query);
                                                $inactive_count = mysqli_fetch_assoc($inactive_result)['inactive'];
                                                echo $inactive_count;
                                                ?>
                                            </span>
                                        </div>
                                        <?php
                                        // Count by jump type
                                        $jump_types = ['internal', 'external'];
                                        foreach ($jump_types as $type) {
                                            $count_query = "SELECT COUNT(*) as count FROM table_activity WHERE jumpType = '$type'";
                                            $count_result = mysqli_query($conn, $count_query);
                                            $count = mysqli_fetch_assoc($count_result)['count'];
                                            ?>
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span><?= ucfirst($type) ?>:</span>
                                                <span class="badge bg-info"><?= $count ?></span>
                                            </div>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>

                            <!-- All Activities Section -->
                            <div class="col-md-8">
                                <div class="card">
                                    <div class="card-header">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h5 class="card-title mb-0">All Activities (<?= $total_activities ?>)</h5>
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-primary active" onclick="filterActivities('all')">All</button>
                                                <button type="button" class="btn btn-outline-success" onclick="filterActivities('active')">Active</button>
                                                <button type="button" class="btn btn-outline-secondary" onclick="filterActivities('inactive')">Inactive</button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <?php if ($total_activities > 0): ?>
                                            <div class="row" id="activitiesContainer">
                                                <?php while ($activity = mysqli_fetch_assoc($activities_result)): ?>
                                                    <div class="col-md-6 mb-4 activity-item" data-status="<?= $activity['active'] ? 'active' : 'inactive' ?>">
                                                        <div class="activity-card <?= $activity['active'] ? '' : 'inactive' ?>">
                                                            <!-- Activity Header -->
                                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                                <div>
                                                                    <h6 class="mb-1"><?= htmlspecialchars($activity['bannerTitle']) ?></h6>
                                                                    <span class="badge bg-<?= $activity['jumpType'] == 'internal' ? 'primary' : 'warning' ?> jump-badge">
                                                                        <?= ucfirst($activity['jumpType']) ?>
                                                                    </span>
                                                                    <span class="badge bg-secondary jump-badge">
                                                                        ID: <?= $activity['bannerID'] ?>
                                                                    </span>
                                                                </div>
                                                                <div class="dropdown">
                                                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                                                            type="button" data-bs-toggle="dropdown">
                                                                        <i class="ri-more-2-line"></i>
                                                                    </button>
                                                                    <ul class="dropdown-menu">
                                                                        <li>
                                                                            <a class="dropdown-item" href="?edit=<?= $activity['id'] ?>">
                                                                                <i class="ri-edit-line me-2"></i>Edit
                                                                            </a>
                                                                        </li>
                                                                        <li>
                                                                            <a class="dropdown-item text-danger" 
                                                                               href="?delete=<?= $activity['id'] ?>" 
                                                                               onclick="return confirm('Are you sure you want to delete this activity?')">
                                                                                <i class="ri-delete-bin-line me-2"></i>Delete
                                                                            </a>
                                                                        </li>
                                                                    </ul>
                                                                </div>
                                                            </div>

                                                            <!-- Banner Preview -->
                                                            <?php if (!empty($activity['bannerUrl'])): ?>
                                                                <div class="mb-2">
                                                                    <img src="<?= htmlspecialchars($activity['bannerUrl']) ?>" 
                                                                         alt="Banner <?= $activity['id'] ?>" 
                                                                         class="banner-preview"
                                                                         onerror="this.style.display='none'">
                                                                </div>
                                                            <?php endif; ?>

                                                            <!-- Banner URL -->
                                                            <?php if (!empty($activity['bannerUrl'])): ?>
                                                                <div class="mb-2">
                                                                    <small class="text-muted">URL:</small>
                                                                    <div class="url-input bg-light p-2 rounded small">
                                                                        <?= htmlspecialchars($activity['bannerUrl']) ?>
                                                                    </div>
                                                                </div>
                                                            <?php endif; ?>

                                                            <!-- Contents Preview -->
                                                            <?php if (!empty($activity['contents'])): ?>
                                                                <div class="mb-3">
                                                                    <small class="text-muted">Contents:</small>
                                                                    <div class="content-preview bg-light p-2 rounded small">
                                                                        <?= htmlspecialchars($activity['contents']) ?>
                                                                    </div>
                                                                </div>
                                                            <?php endif; ?>

                                                            <!-- Activity Footer -->
                                                            <div class="d-flex justify-content-between align-items-center">
                                                                <div>
                                                                    <form action="" method="post" class="d-inline">
                                                                        <input type="hidden" name="id" value="<?= $activity['id'] ?>">
                                                                        <input type="hidden" name="current_status" value="<?= $activity['active'] ?>">
                                                                        <input type="hidden" name="toggle_active" value="1">
                                                                        <label class="toggle-switch status-toggle">
                                                                            <input type="checkbox" <?= $activity['active'] ? 'checked' : '' ?> 
                                                                                   onchange="this.form.submit()">
                                                                            <span class="slider"></span>
                                                                        </label>
                                                                    </form>
                                                                    <small class="text-muted ms-2">
                                                                        <?= $activity['active'] ? 'Active' : 'Inactive' ?>
                                                                    </small>
                                                                </div>
                                                                <div>
                                                                    <a href="?edit=<?= $activity['id'] ?>" 
                                                                       class="btn btn-sm btn-outline-primary me-1">
                                                                        <i class="ri-edit-line"></i>
                                                                    </a>
                                                                    <a href="?delete=<?= $activity['id'] ?>" 
                                                                       class="btn btn-sm btn-outline-danger"
                                                                       onclick="return confirm('Are you sure you want to delete this activity?')">
                                                                        <i class="ri-delete-bin-line"></i>
                                                                    </a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endwhile; ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="text-center py-5">
                                                <div class="avatar avatar-lg mb-3">
                                                    <span class="avatar-initial rounded bg-label-secondary">
                                                        <i class="ri-calendar-event-line ri-24px"></i>
                                                    </span>
                                                </div>
                                                <h5 class="text-muted">No Activities Found</h5>
                                                <p class="text-muted">Add your first activity using the form on the left.</p>
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
        // Filter activities
        function filterActivities(status) {
            const activities = document.querySelectorAll('.activity-item');
            const filterButtons = document.querySelectorAll('.btn-group .btn');
            
            // Update active button
            filterButtons.forEach(btn => {
                btn.classList.remove('active');
                if (btn.textContent.toLowerCase() === status) {
                    btn.classList.add('active');
                }
            });
            
            // Show/hide activities based on filter
            activities.forEach(activity => {
                if (status === 'all' || activity.getAttribute('data-status') === status) {
                    activity.style.display = 'block';
                } else {
                    activity.style.display = 'none';
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

        // Scroll to form when editing
        <?php if ($isEdit): ?>
            document.addEventListener('DOMContentLoaded', function() {
                document.querySelector('.card-header h5').scrollIntoView({
                    behavior: 'smooth'
                });
            });
        <?php endif; ?>
    </script>
</body>
</html>