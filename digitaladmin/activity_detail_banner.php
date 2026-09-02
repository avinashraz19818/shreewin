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

$publicDomain = 'https://shreewin.club9.eu.cc'; // 

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}
function csrf_field()
{
    $t = htmlspecialchars($_SESSION['csrf_token']);
    return '<input type="hidden" name="csrf_token" value="' . $t . '">';
}
function check_csrf()
{
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        http_response_code(400);
        exit('Bad Request: CSRF failed');
    }
}

function jtLabel($v)
{
    return [1 => 'Internal', 2 => 'External', 3 => 'Image List (JSON)'][$v] ?? $v;
}

function renderContent($imgField, $jumpType)
{
    $html = '';

    if ((int)$jumpType === 3) {
        $decoded = json_decode($imgField, true);
        if (is_array($decoded)) {
            foreach ($decoded as $src) {
                $src = trim(is_string($src) ? $src : ($src['Url'] ?? $src['url'] ?? $src['src'] ?? ''));
                if ($src !== '') {
                    $html .= '<div style="margin:8px 0;"><img src="' . htmlspecialchars($src) . '" style="width:50%;border-radius:8px" onerror="this.style.display=\'none\'"></div>';
                }
            }
            return $html ?: '<div style="color:#8b949e">No images in JSON.</div>';
        }
    }
    if (strlen($imgField) && $imgField[0] === '<') {
        return '<div style="transform: scale(0.5); transform-origin: top left; width: 200%;">' . $imgField . '</div>';
    }

    $parts = preg_split('/[\r\n,]+/', trim($imgField));
    if ($parts && count($parts) > 0) {
        foreach ($parts as $src) {
            $src = trim($src);
            if ($src !== '') {
                $html .= '<div style="margin:8px 0;"><img src="' . htmlspecialchars($src) . '" style="width:50%;border-radius:8px" onerror="this.style.display=\'none\'"></div>';
            }
        }
        return $html ?: '<div style="color:#8b949e">Nothing to preview.</div>';
    }

    return '<div style="color:#8b949e">Nothing to preview.</div>';
}

// Insert/Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bannerId'])) {
    check_csrf();

    $bannerId = intval($_POST['bannerId']);
    $title    = trim($_POST['title']);
    $img      = trim($_POST['img']);
    $coverUrl = trim($_POST['coverUrl']);
    $jumpType = intval($_POST['jumpType']);
    $status   = isset($_POST['status']) ? (int)$_POST['status'] : 1;

    if (isset($_POST['edit_id']) && !empty($_POST['edit_id'])) {
        // Update
        $id = intval($_POST['edit_id']);
        $stmt = $conn->prepare("UPDATE activity_banner_custom_data SET bannerId=?, title=?, img=?, coverUrl=?, jumpType=?, status=? WHERE id=?");
        $stmt->bind_param("isssiii", $bannerId, $title, $img, $coverUrl, $jumpType, $status, $id);
        $stmt->execute();
        $stmt->close();
        $message = "✅ Activity updated successfully!";
        $message_type = "success";
    } else {
        // Insert
        $stmt = $conn->prepare("INSERT INTO activity_banner_custom_data (bannerId, title, img, coverUrl, jumpType, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssii", $bannerId, $title, $img, $coverUrl, $jumpType, $status);
        $stmt->execute();
        $stmt->close();
        $message = "✅ Activity added successfully!";
        $message_type = "success";
    }
}

// Delete 
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM activity_banner_custom_data WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    $message = "✅ Activity deleted successfully!";
    $message_type = "success";
}

// Toggle status 
if (isset($_GET['toggle'])) {
    $id = intval($_GET['toggle']);
    $conn->query("UPDATE activity_banner_custom_data SET status = IF(status=1,0,1) WHERE id={$id}");
    $message = "✅ Status updated successfully!";
    $message_type = "success";
}

// Edit fetch
$editData = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $get = $conn->prepare("SELECT * FROM activity_banner_custom_data WHERE id=?");
    $get->bind_param("i", $id);
    $get->execute();
    $res = $get->get_result();
    $editData = $res->fetch_assoc();
    $get->close();
}

/* ---- Search + Pagination ---- */
$qp = [
    'q' => isset($_GET['q']) ? trim($_GET['q']) : '',
    'jt' => isset($_GET['jt']) ? (int)$_GET['jt'] : 0,
];
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 20;
$offset = ($page - 1) * $limit;

$where = "1=1";
$params = [];
$types  = '';

if ($qp['q'] !== '') {
    if (ctype_digit($qp['q'])) {
        $where .= " AND (bannerId = ? OR title LIKE CONCAT('%',?,'%'))";
        $types .= "is";
        $params[] = (int)$qp['q'];
        $params[] = $qp['q'];
    } else {
        $where .= " AND (title LIKE CONCAT('%',?,'%'))";
        $types .= "s";
        $params[] = $qp['q'];
    }
}
if ($qp['jt'] > 0) {
    $where .= " AND jumpType = ?";
    $types .= "i";
    $params[] = $qp['jt'];
}

$count_sql = "SELECT COUNT(*) AS c FROM activity_banner_custom_data WHERE $where";
$count_stmt = $conn->prepare($count_sql);
if ($types) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$count_res = $count_stmt->get_result()->fetch_assoc();
$total = (int)$count_res['c'];
$count_stmt->close();

$list_sql = "SELECT * FROM activity_banner_custom_data WHERE $where ORDER BY id DESC LIMIT ? OFFSET ?";
$list_stmt = $conn->prepare($list_sql);
if ($types) {
    $types2 = $types . "ii";
    $params2 = $params;
    $params2[] = $limit;
    $params2[] = $offset;
    $list_stmt->bind_param($types2, ...$params2);
} else {
    $list_stmt->bind_param("ii", $limit, $offset);
}
$list_stmt->execute();
$list_res = $list_stmt->get_result();

$pages = max(1, (int)ceil($total / $limit));

// Statistics
$total_activities = $total;
$active_query = "SELECT COUNT(*) as active FROM activity_banner_custom_data WHERE status = 1";
$active_result = mysqli_query($conn, $active_query);
$active_count = mysqli_fetch_assoc($active_result)['active'];

$inactive_query = "SELECT COUNT(*) as inactive FROM activity_banner_custom_data WHERE status = 0";
$inactive_result = mysqli_query($conn, $inactive_query);
$inactive_count = mysqli_fetch_assoc($inactive_result)['inactive'];
?>

<!doctype html>
<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr"
    data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template" data-style="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Custom Activity Banners Management</title>
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
            transition: all 0.3s ease;
        }

        .activity-card.inactive {
            opacity: 0.6;
        }

        .activity-card:hover {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .url-input {
            font-size: 12px;
            font-family: monospace;
        }

        .content-preview {
            max-height: 150px;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 4;
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

        input:checked+.slider {
            background-color: #2196F3;
        }

        input:checked+.slider:before {
            transform: translateX(26px);
        }

        .image-preview {
            max-width: 50%;
            max-height: 120px;
            object-fit: contain;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 5px;
            background: #f9f9f9;
            margin-bottom: 10px;
        }

        .cover-preview {
            max-width: 50%;
            max-height: 80px;
            object-fit: contain;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 5px;
            background: #f9f9f9;
            margin-bottom: 10px;
        }

        .preview-container {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 10px;
            margin-top: 10px;
            max-height: 300px;
            overflow-y: auto;
        }

        .preview-container img {
            max-width: 50% !important;
            border-radius: 8px;
            margin: 5px 0;
        }

        .html-preview {
            transform: scale(0.5);
            transform-origin: top left;
            width: 200%;
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

                        <div class="row">
                            <!-- Add/Edit Activity Section -->
                            <div class="col-md-4">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <?= isset($editData) ? 'Edit Activity' : 'Add New Activity' ?>
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <form action="" method="post">
                                            <?= csrf_field() ?>
                                            <?php if (isset($editData)): ?>
                                                <input type="hidden" name="edit_id" value="<?= $editData['id'] ?>">
                                            <?php endif; ?>

                                            <!-- Banner ID -->
                                            <div class="mb-3">
                                                <label for="bannerId" class="form-label">Banner ID *</label>
                                                <input type="number" class="form-control" id="bannerId" name="bannerId"
                                                    value="<?= isset($editData) ? htmlspecialchars($editData['bannerId']) : '' ?>"
                                                    required placeholder="Enter banner ID">
                                            </div>

                                            <!-- Title -->
                                            <div class="mb-3">
                                                <label for="title" class="form-label">Title *</label>
                                                <input type="text" class="form-control" id="title" name="title"
                                                    value="<?= isset($editData) ? htmlspecialchars($editData['title']) : '' ?>"
                                                    required placeholder="Enter activity title">
                                            </div>

                                            <!-- Image/Content -->
                                            <div class="mb-3">
                                                <label for="img" class="form-label">Image/Content *</label>
                                                <textarea class="form-control" id="img" name="img"
                                                    rows="4" placeholder="Enter image URL, HTML content, or JSON array"><?= isset($editData) ? htmlspecialchars($editData['img']) : '' ?></textarea>
                                                <div class="form-text">
                                                    Can be: Single image URL, Multiple URLs (newline separated), HTML content, or JSON array for multiple images
                                                </div>
                                            </div>

                                            <!-- Cover URL -->
                                            <div class="mb-3">
                                                <label for="coverUrl" class="form-label">Cover URL</label>
                                                <input type="url" class="form-control" id="coverUrl" name="coverUrl"
                                                    value="<?= isset($editData) ? htmlspecialchars($editData['coverUrl']) : '' ?>"
                                                    placeholder="https://example.com/cover.jpg">
                                                <div class="form-text">
                                                    Cover image will be displayed at 50% size
                                                </div>
                                            </div>

                                            <!-- Jump Type -->
                                            <div class="mb-3">
                                                <label for="jumpType" class="form-label">Jump Type *</label>
                                                <select class="form-select" id="jumpType" name="jumpType" required>
                                                    <option value="">Select Jump Type</option>
                                                    <option value="1" <?= isset($editData) && $editData['jumpType'] == 1 ? 'selected' : '' ?>>Internal</option>
                                                    <option value="2" <?= isset($editData) && $editData['jumpType'] == 2 ? 'selected' : '' ?>>External</option>
                                                    <option value="3" <?= isset($editData) && $editData['jumpType'] == 3 ? 'selected' : '' ?>>Image List (JSON)</option>
                                                </select>
                                            </div>

                                            <!-- Status -->
                                            <div class="mb-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="status" name="status"
                                                        <?= (isset($editData) && $editData['status'] == 1) || !isset($editData) ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="status">
                                                        Active
                                                    </label>
                                                </div>
                                            </div>

                                            <!-- Live Preview -->
                                            <div class="mb-3">
                                                <label class="form-label">Live Preview</label>
                                                <div class="preview-container" id="previewContainer">
                                                    <!-- Content Preview -->
                                                    <div id="contentPreview">
                                                        <?php if (isset($editData)): ?>
                                                            <?= renderContent($editData['img'], $editData['jumpType']) ?>
                                                        <?php else: ?>
                                                            <div style="color:#8b949e">Content preview will appear here</div>
                                                        <?php endif; ?>
                                                    </div>

                                                    <!-- Cover Image Preview -->
                                                    <div id="coverPreview" class="mt-3">
                                                        <?php if (isset($editData) && !empty($editData['coverUrl'])): ?>
                                                            <div>
                                                                <small class="text-muted">Cover Preview:</small>
                                                                <img src="<?= htmlspecialchars($editData['coverUrl']) ?>"
                                                                    class="cover-preview"
                                                                    onerror="this.style.display='none'">
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="d-grid gap-2">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="ri-save-line ri-16px me-2"></i>
                                                    <?= isset($editData) ? 'Update Activity' : 'Add Activity' ?>
                                                </button>
                                                <?php if (isset($editData)): ?>
                                                    <a href="manage_custom_activitybanner.php" class="btn btn-secondary">
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
                                            <span class="badge bg-success"><?= $active_count ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <span>Inactive Activities:</span>
                                            <span class="badge bg-secondary"><?= $inactive_count ?></span>
                                        </div>
                                        <?php
                                        // Count by jump type
                                        $jump_types = [1, 2, 3];
                                        foreach ($jump_types as $type) {
                                            $count_query = "SELECT COUNT(*) as count FROM activity_banner_custom_data WHERE jumpType = $type";
                                            $count_result = mysqli_query($conn, $count_query);
                                            $count = mysqli_fetch_assoc($count_result)['count'];
                                        ?>
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span><?= jtLabel($type) ?>:</span>
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

                                    <!-- Search Form -->
                                    <div class="card-body border-bottom">
                                        <form method="GET" class="row g-3">
                                            <div class="col-md-4">
                                                <input type="text" class="form-control" name="q"
                                                    value="<?= htmlspecialchars($qp['q']) ?>"
                                                    placeholder="Search by ID or Title">
                                            </div>
                                            <div class="col-md-3">
                                                <select class="form-select" name="jt">
                                                    <option value="0">All Jump Types</option>
                                                    <option value="1" <?= $qp['jt'] == 1 ? 'selected' : '' ?>>Internal</option>
                                                    <option value="2" <?= $qp['jt'] == 2 ? 'selected' : '' ?>>External</option>
                                                    <option value="3" <?= $qp['jt'] == 3 ? 'selected' : '' ?>>Image List</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <button type="submit" class="btn btn-primary w-100">Search</button>
                                            </div>
                                            <div class="col-md-2">
                                                <a href="manage_custom_activitybanner.php" class="btn btn-secondary w-100">Reset</a>
                                            </div>
                                        </form>
                                    </div>

                                    <div class="card-body">
                                        <?php if ($total_activities > 0): ?>
                                            <div class="row" id="activitiesContainer">
                                                <?php while ($activity = $list_res->fetch_assoc()): ?>
                                                    <div class="col-md-6 mb-4 activity-item" data-status="<?= $activity['status'] ? 'active' : 'inactive' ?>">
                                                        <div class="activity-card <?= $activity['status'] ? '' : 'inactive' ?>">
                                                            <!-- Activity Header -->
                                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                                <div>
                                                                    <h6 class="mb-1"><?= htmlspecialchars($activity['title']) ?></h6>
                                                                    <span class="badge bg-<?= $activity['jumpType'] == 1 ? 'primary' : ($activity['jumpType'] == 2 ? 'warning' : 'info') ?> jump-badge">
                                                                        <?= jtLabel($activity['jumpType']) ?>
                                                                    </span>
                                                                    <span class="badge bg-secondary jump-badge">
                                                                        ID: <?= $activity['bannerId'] ?>
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

                                                            <!-- Cover Image Preview -->
                                                            <?php if (!empty($activity['coverUrl'])): ?>
                                                                <div class="mb-2">
                                                                    <small class="text-muted">Cover:</small>
                                                                    <img src="<?= htmlspecialchars($activity['coverUrl']) ?>"
                                                                        class="cover-preview"
                                                                        onerror="this.style.display='none'">
                                                                </div>
                                                            <?php endif; ?>

                                                            <!-- Content Preview -->
                                                            <div class="mb-3">
                                                                <?= renderContent($activity['img'], $activity['jumpType']) ?>
                                                            </div>

                                                            <!-- Activity Footer -->
                                                            <div class="d-flex justify-content-between align-items-center">
                                                                <div>
                                                                    <a href="?toggle=<?= $activity['id'] ?>"
                                                                        class="btn btn-sm btn-<?= $activity['status'] ? 'success' : 'secondary' ?>">
                                                                        <?= $activity['status'] ? 'Active' : 'Inactive' ?>
                                                                    </a>
                                                                    <small class="text-muted ms-2">
                                                                        ID: <?= $activity['id'] ?>
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

                                            <!-- Pagination -->
                                            <?php if ($pages > 1): ?>
                                                <nav class="mt-4">
                                                    <ul class="pagination justify-content-center">
                                                        <?php if ($page > 1): ?>
                                                            <li class="page-item">
                                                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">Previous</a>
                                                            </li>
                                                        <?php endif; ?>

                                                        <?php for ($i = 1; $i <= $pages; $i++): ?>
                                                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                                                            </li>
                                                        <?php endfor; ?>

                                                        <?php if ($page < $pages): ?>
                                                            <li class="page-item">
                                                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Next</a>
                                                            </li>
                                                        <?php endif; ?>
                                                    </ul>
                                                </nav>
                                            <?php endif; ?>

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
        // Live preview update
        function updatePreview() {
            const imgContent = document.getElementById('img').value;
            const jumpType = document.getElementById('jumpType').value;
            const coverUrl = document.getElementById('coverUrl').value;

            // Update content preview
            const contentPreview = document.getElementById('contentPreview');

            // Simple preview logic (you can enhance this with AJAX if needed)
            let contentHtml = '';

            if (jumpType === '3') {
                // JSON Image List
                try {
                    const images = JSON.parse(imgContent);
                    if (Array.isArray(images)) {
                        images.forEach(src => {
                            if (typeof src === 'string' && src.trim()) {
                                contentHtml += `<div style="margin:8px 0;"><img src="${src.trim()}" style="width:50%;border-radius:8px" onerror="this.style.display='none'"></div>`;
                            }
                        });
                    }
                } catch (e) {
                    contentHtml = '<div style="color:#8b949e">Invalid JSON</div>';
                }
            } else if (imgContent.trim().startsWith('<')) {
                // HTML Content
                contentHtml = `<div class="html-preview">${imgContent}</div>`;
            } else {
                // Single or Multiple URLs
                const urls = imgContent.split(/[\r\n,]+/).filter(url => url.trim());
                urls.forEach(url => {
                    contentHtml += `<div style="margin:8px 0;"><img src="${url.trim()}" style="width:50%;border-radius:8px" onerror="this.style.display='none'"></div>`;
                });
            }

            if (!contentHtml) {
                contentHtml = '<div style="color:#8b949e">Content preview will appear here</div>';
            }

            contentPreview.innerHTML = contentHtml;

            // Update cover preview
            const coverPreview = document.getElementById('coverPreview');
            if (coverUrl.trim()) {
                coverPreview.innerHTML = `
                    <div>
                        <small class="text-muted">Cover Preview:</small>
                        <img src="${coverUrl.trim()}" class="cover-preview" onerror="this.style.display='none'">
                    </div>
                `;
            } else {
                coverPreview.innerHTML = '';
            }
        }

        // Attach event listeners
        document.getElementById('img').addEventListener('input', updatePreview);
        document.getElementById('jumpType').addEventListener('change', updatePreview);
        document.getElementById('coverUrl').addEventListener('input', updatePreview);

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
        <?php if (isset($editData)): ?>
            document.addEventListener('DOMContentLoaded', function() {
                document.querySelector('.card-header h5').scrollIntoView({
                    behavior: 'smooth'
                });
            });
        <?php endif; ?>
    </script>
</body>

</html>