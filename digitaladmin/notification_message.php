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

// Create table if not exists
$createTableSQL = "
CREATE TABLE IF NOT EXISTS website_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    message_type VARCHAR(50) DEFAULT 'info',
    status TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

$conn->query($createTableSQL);

$success = false;
$error = '';

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $message = mysqli_real_escape_string($conn, $_POST['message']);
    $type = mysqli_real_escape_string($conn, $_POST['type']);
    $status = isset($_POST['status']) ? 1 : 0;

    if (empty($title) || empty($message)) {
        $error = "Please fill all required fields";
    } else {
        if ($id == '') {
            $sql = "INSERT INTO website_messages (title, message, message_type, status) VALUES ('$title', '$message', '$type', $status)";
            if (mysqli_query($conn, $sql)) {
                $success = true;
            } else {
                $error = "Insert failed: " . mysqli_error($conn);
            }
        } else {
            $sql = "UPDATE website_messages SET title='$title', message='$message', message_type='$type', status=$status WHERE id=$id";
            if (mysqli_query($conn, $sql)) {
                $success = true;
            } else {
                $error = "Update failed: " . mysqli_error($conn);
            }
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM website_messages WHERE id=$deleteId");
    $success = true;
}

// Handle status toggle
if (isset($_GET['toggle'])) {
    $toggleId = (int)$_GET['toggle'];
    $current = mysqli_fetch_assoc(mysqli_query($conn, "SELECT status FROM website_messages WHERE id=$toggleId"));
    $newStatus = $current['status'] ? 0 : 1;
    mysqli_query($conn, "UPDATE website_messages SET status=$newStatus WHERE id=$toggleId");
    header("Location: manage_notifications.php");
    exit;
}

// Fetch all messages
$messages = mysqli_query($conn, "SELECT * FROM website_messages ORDER BY created_at DESC");

// Edit data
$editData = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $res = mysqli_query($conn, "SELECT * FROM website_messages WHERE id=$editId");
    $editData = mysqli_fetch_assoc($res);
}

// Store messages in array for display
$messagesArray = [];
if ($messages->num_rows > 0) {
    while ($message = $messages->fetch_assoc()) {
        $messagesArray[] = $message;
    }
}

// Calculate statistics
$totalCount = count($messagesArray);
$activeCount = 0;
$inactiveCount = 0;

foreach ($messagesArray as $m) {
    if ($m['status']) {
        $activeCount++;
    } else {
        $inactiveCount++;
    }
}

$types = array();
foreach ($messagesArray as $m) {
    $type = $m['message_type'];
    if (!isset($types[$type])) {
        $types[$type] = 0;
    }
    $types[$type]++;
}
?>
<!DOCTYPE html>
<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr"
    data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template" data-style="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Notification Management</title>
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
        .notification-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            /*background: #fff;*/
            transition: all 0.3s ease;
        }
        .notification-card.inactive {
            opacity: 0.7;
            background: #f8f9fa;
        }
        .notification-card:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .type-badge {
            font-size: 0.75em;
        }
        .edit-form-container {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            border: 2px solid #0d6efd;
        }
        .message-preview {
            /*background: #f8f9fa;*/
            border-radius: 5px;
            padding: 10px;
            margin-top: 10px;
            border-left: 4px solid #0d6efd;
        }
        .alert {
            margin-bottom: 20px;
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
                                    ✅ Notification saved successfully!
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
                            <!-- Notification Form -->
                            <div class="col-md-6">
                                <?php if (isset($_GET['edit'])): ?>
                                <div class="edit-form-container">
                                    <h4 class="text-primary mb-3">✏️ Editing: <?= htmlspecialchars($editData['title']) ?></h4>
                                <?php endif; ?>
                                
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <?= isset($_GET['edit']) ? 'Edit Notification' : 'Create New Notification' ?>
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <form method="POST">
                                            <input type="hidden" name="id" value="<?= $editData['id'] ?? '' ?>">
                                            
                                            <div class="mb-3">
                                                <label for="title" class="form-label">Notification Title *</label>
                                                <input type="text" class="form-control" id="title" name="title" 
                                                       value="<?= htmlspecialchars($editData['title'] ?? '') ?>" required 
                                                       placeholder="Enter notification title">
                                            </div>

                                            <div class="mb-3">
                                                <label for="type" class="form-label">Message Type</label>
                                                <select class="form-select" id="type" name="type">
                                                    <option value="info" <?= ($editData['message_type'] ?? 'info') === 'info' ? 'selected' : '' ?>>Info</option>
                                                    <option value="success" <?= ($editData['message_type'] ?? '') === 'success' ? 'selected' : '' ?>>Success</option>
                                                    <option value="warning" <?= ($editData['message_type'] ?? '') === 'warning' ? 'selected' : '' ?>>Warning</option>
                                                    <option value="error" <?= ($editData['message_type'] ?? '') === 'error' ? 'selected' : '' ?>>Error</option>
                                                    <option value="maintenance" <?= ($editData['message_type'] ?? '') === 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
                                                    <option value="update" <?= ($editData['message_type'] ?? '') === 'update' ? 'selected' : '' ?>>Update</option>
                                                </select>
                                            </div>

                                            <div class="mb-3">
                                                <label for="message" class="form-label">Message Content *</label>
                                                <textarea class="form-control" id="message" name="message" 
                                                          rows="4" required placeholder="Enter your notification message"><?= htmlspecialchars($editData['message'] ?? '') ?></textarea>
                                                <div class="form-text">This message will be displayed to users.</div>
                                            </div>

                                            <div class="mb-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="status" 
                                                           name="status" value="1" <?= ($editData['status'] ?? 0) ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="status">
                                                        Active Notification
                                                    </label>
                                                </div>
                                            </div>

                                            <div class="mt-4">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="ri-save-line ri-16px me-2"></i>
                                                    <?= isset($_GET['edit']) ? 'Update Notification' : 'Create Notification' ?>
                                                </button>
                                                <?php if (isset($_GET['edit'])): ?>
                                                    <a href="manage_notifications.php" class="btn btn-secondary">
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
                                        <h5 class="card-title mb-0">Message Preview</h5>
                                    </div>
                                    <div class="card-body">
                                        <div id="messagePreview" class="message-preview">
                                            <?php if (isset($editData)): ?>
                                                <strong><?= htmlspecialchars($editData['title']) ?></strong>
                                                <p class="mb-0 mt-2"><?= htmlspecialchars($editData['message']) ?></p>
                                                <small class="text-muted">Type: <?= htmlspecialchars($editData['message_type']) ?></small>
                                            <?php else: ?>
                                                <p class="text-muted mb-0">Preview will appear here when you start typing...</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Existing Notifications -->
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h5 class="card-title mb-0">Existing Notifications</h5>
                                            <span class="badge bg-primary">
                                                <?= $totalCount ?> Total
                                            </span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <?php if ($totalCount > 0): ?>
                                            <div id="notificationsList">
                                                <?php foreach ($messagesArray as $notification): ?>
                                                    <div class="notification-card <?= !$notification['status'] ? 'inactive' : '' ?>" 
                                                         data-id="<?= $notification['id'] ?>">
                                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                                            <div>
                                                                <h6 class="mb-1"><?= htmlspecialchars($notification['title']) ?></h6>
                                                                <span class="badge bg-<?= $notification['status'] ? 'success' : 'secondary' ?>">
                                                                    <?= $notification['status'] ? 'Active' : 'Inactive' ?>
                                                                </span>
                                                                <span class="badge type-badge bg-<?= 
                                                                    $notification['message_type'] === 'success' ? 'success' : 
                                                                    ($notification['message_type'] === 'warning' ? 'warning' : 
                                                                    ($notification['message_type'] === 'error' ? 'danger' : 
                                                                    ($notification['message_type'] === 'maintenance' ? 'info' : 
                                                                    ($notification['message_type'] === 'update' ? 'primary' : 'secondary')))) 
                                                                ?> ms-1">
                                                                    <?= ucfirst($notification['message_type']) ?>
                                                                </span>
                                                            </div>
                                                            <div class="dropdown">
                                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                                                        type="button" data-bs-toggle="dropdown">
                                                                    <i class="ri-more-2-line"></i>
                                                                </button>
                                                                <ul class="dropdown-menu">
                                                                    <li>
                                                                        <a class="dropdown-item" 
                                                                           href="?edit=<?= $notification['id'] ?>">
                                                                            <i class="ri-edit-line me-2"></i>Edit
                                                                        </a>
                                                                    </li>
                                                                    <li>
                                                                        <a class="dropdown-item toggle-status" href="?toggle=<?= $notification['id'] ?>">
                                                                            <i class="ri-toggle-line me-2"></i>
                                                                            <?= $notification['status'] ? 'Deactivate' : 'Activate' ?>
                                                                        </a>
                                                                    </li>
                                                                    <li>
                                                                        <a class="dropdown-item text-danger delete-notification" 
                                                                           href="?delete=<?= $notification['id'] ?>" 
                                                                           onclick="return confirm('Are you sure you want to delete this notification?')">
                                                                            <i class="ri-delete-bin-line me-2"></i>Delete
                                                                        </a>
                                                                    </li>
                                                                </ul>
                                                            </div>
                                                        </div>
                                                        <div class="message-preview small">
                                                            <?= htmlspecialchars($notification['message']) ?>
                                                        </div>
                                                        <div class="small text-muted mt-2">
                                                            Created: <?= date('M j, Y g:i A', strtotime($notification['created_at'])) ?>
                                                            <?php if ($notification['created_at'] != $notification['updated_at']): ?>
                                                                <br>Updated: <?= date('M j, Y g:i A', strtotime($notification['updated_at'])) ?>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="text-center py-4">
                                                <div class="avatar avatar-lg mb-3">
                                                    <span class="avatar-initial rounded bg-label-secondary">
                                                        <i class="ri-notification-line ri-24px"></i>
                                                    </span>
                                                </div>
                                                <h5 class="text-muted">No Notifications</h5>
                                                <p class="text-muted">Create your first notification using the form.</p>
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
                                                <div class="stats-value text-secondary"><?= $inactiveCount ?></div>
                                                <div class="stats-label">Inactive</div>
                                            </div>
                                        </div>
                                        <div class="mt-3">
                                            <small class="text-muted">Types: 
                                                <?php
                                                foreach ($types as $type => $count) {
                                                    echo "<span class='badge bg-secondary ms-1'>" . ucfirst($type) . ": $count</span>";
                                                }
                                                ?>
                                            </small>
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
            const title = document.getElementById('title').value || 'Notification Title';
            const message = document.getElementById('message').value || 'Message content will appear here...';
            const type = document.getElementById('type').value || 'info';
            
            const previewHTML = `
                <strong>${title}</strong>
                <p class="mb-0 mt-2">${message}</p>
                <small class="text-muted">Type: ${type}</small>
            `;
            
            document.getElementById('messagePreview').innerHTML = previewHTML;
        }

        // Add event listeners for live preview
        document.getElementById('title').addEventListener('input', updatePreview);
        document.getElementById('message').addEventListener('input', updatePreview);
        document.getElementById('type').addEventListener('change', updatePreview);

        // Initialize preview
        document.addEventListener('DOMContentLoaded', function() {
            updatePreview();
        });

        // Enhanced delete confirmation
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('delete-notification')) {
                if (!confirm('Are you sure you want to delete this notification? This action cannot be undone.')) {
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