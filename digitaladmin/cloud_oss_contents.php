<?php
session_start();
if (empty($_SESSION['unohs'])) {
    header("location: api/login.php?msg=unauthorized");
    exit;
}

include("api/conn.php");

$site_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http')
    . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/';
$success_message = "";
$error_message = "";

// Handle Create Folder
if (isset($_POST['create_folder'])) {
    $folder_name = trim($_POST['folder_name']);
    if (!empty($folder_name)) {
        // Sanitize folder name
        $folder_name = preg_replace('/[^a-zA-Z0-9_-]/', '', $folder_name);
        $folderPath = '../uploads/' . $folder_name;
        if (!is_dir($folderPath)) {
            if (mkdir($folderPath, 0777, true)) {
                $conn->query("INSERT INTO ossimages (folder_name, image_url) VALUES ('$folder_name', '')");
                $success_message = "Folder created successfully!";
            } else {
                $error_message = "Failed to create folder.";
            }
        } else {
            $error_message = "Folder already exists.";
        }
    } else {
        $error_message = "Please enter folder name.";
    }
}

// Handle Rename Folder
if (isset($_POST['rename_folder'])) {
    $old_name = trim($_POST['old_name']);
    $new_name = trim($_POST['new_name']);
    if (!empty($old_name) && !empty($new_name)) {
        $new_name = preg_replace('/[^a-zA-Z0-9_-]/', '', $new_name);
        $old_path = '../uploads/' . $old_name;
        $new_path = '../uploads/' . $new_name;
        if (is_dir($old_path)) {
            if (rename($old_path, $new_path)) {
                $conn->query("UPDATE ossimages SET folder_name='$new_name', image_url = REPLACE(image_url, 'uploads/$old_name/', 'uploads/$new_name/') WHERE folder_name='$old_name'");
                $current_folder = $new_name;
                $success_message = "Folder renamed successfully!";
            } else {
                $error_message = "Failed to rename folder.";
            }
        } else {
            $error_message = "Folder not found.";
        }
    }
}

// Handle Delete Folder
if (isset($_GET['delete_folder'])) {
    $folder = trim($_GET['delete_folder']);
    $folderPath = '../uploads/' . $folder;
    if (is_dir($folderPath)) {
        // Delete all files in folder
        $files = glob($folderPath . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        if (rmdir($folderPath)) {
            $conn->query("DELETE FROM ossimages WHERE folder_name='$folder'");
            $success_message = "Folder deleted successfully!";
            $current_folder = '';
        } else {
            $error_message = "Failed to delete folder.";
        }
    }
}

// Handle Image Upload with security checks - FIXED
if (isset($_FILES['file']) && isset($_POST['current_folder'])) {
    $folder_name = $_POST['current_folder'];
    $targetDir = "../uploads/" . $folder_name . "/";

    // Create folder if not exists
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $fileName = time() . '_' . basename($_FILES["file"]["name"]);
    $targetFilePath = $targetDir . $fileName;
    $savePath = "uploads/" . $folder_name . "/" . $fileName;

    // Security checks - FIXED mime_content_type issue
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $maxFileSize = 3 * 1024 * 1024; // 3MB
    $fileSize = $_FILES['file']['size'];

    // Check if mime_content_type function exists, if not use alternative
    if (function_exists('mime_content_type')) {
        $fileType = mime_content_type($_FILES["file"]["tmp_name"]);
    } else {
        // Alternative method: check file extension and use getimagesize
        $fileName = $_FILES["file"]["name"];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        // Map extensions to mime types
        $extensionMimeMap = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp'
        ];

        $fileType = isset($extensionMimeMap[$fileExtension]) ? $extensionMimeMap[$fileExtension] : '';

        // Additional check using getimagesize
        if (!getimagesize($_FILES["file"]["tmp_name"])) {
            $fileType = 'invalid';
        }
    }

    if (!in_array($fileType, $allowedTypes)) {
        $error_message = "Only JPG, JPEG, PNG, GIF & WEBP files are allowed.";
    } elseif ($fileSize > $maxFileSize) {
        $error_message = "File size must be less than 3MB.";
    } elseif (move_uploaded_file($_FILES["file"]["tmp_name"], $targetFilePath)) {
        $conn->query("INSERT INTO ossimages (folder_name, image_url, file_size) VALUES ('$folder_name', '$savePath', '$fileSize')");
        $success_message = "Image uploaded successfully!";
    } else {
        $error_message = "File upload failed.";
    }
}

// Handle Delete Image
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $folder_name = $_GET['folder'];
    $img = $conn->query("SELECT * FROM ossimages WHERE id=$delete_id")->fetch_assoc();
    if ($img) {
        $filePath = '../' . $img['image_url'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        $conn->query("DELETE FROM ossimages WHERE id=$delete_id");
        $success_message = "Image deleted successfully!";
    }
}

// Fetch folders for sidebar
$folders = $conn->query("SELECT folder_name, COUNT(*) as total_images FROM ossimages GROUP BY folder_name ORDER BY folder_name ASC");

// Current Folder
$current_folder = isset($_GET['folder']) ? trim($_GET['folder']) : '';
$images = [];
if ($current_folder) {
    $images = $conn->query("SELECT * FROM ossimages WHERE folder_name='$current_folder' ORDER BY id DESC");
}
?>

<!-- BAKI KA SAB CODE APKE JAISA HI RAHEGA -->

<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr"
    data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template" data-style="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

    <title>Cloud Image Storage</title>
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
        .folder-sidebar {
            /*background: #f8f9fa;*/
            border-right: 1px solid #e0e0e0;
            height: calc(100vh - 200px);
            overflow-y: auto;
        }

        .folder-item {
            padding: 10px 15px;
            border-bottom: 1px solid #e0e0e0;
            cursor: pointer;
            transition: background 0.3s;
        }

        .folder-item:hover,
        .folder-item.active {
            background: #007bff;
            color: white;
        }

        .folder-item.active .badge {
            background: white !important;
            color: #007bff !important;
        }

        .image-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            /*background: #fff;*/
        }

        .image-preview {
            max-width: 100%;
            max-height: 200px;
            object-fit: contain;
            border-radius: 4px;
        }

        .url-box {
            /*background: #f8f9fa;*/
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            padding: 8px 12px;
            font-family: monospace;
            font-size: 12px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .upload-area {
            border: 2px dashed #007bff;
            border-radius: 8px;
            padding: 40px 20px;
            text-align: center;
            /*background: #f8f9fa;*/
            cursor: pointer;
            transition: background 0.3s;
        }

        .upload-area:hover {
            background: #e3f2fd;
        }

        .upload-area.dragover {
            background: #bbdefb;
            border-color: #1565c0;
        }
    </style>
</head>

<body>
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <?php include("layout-menu.php"); ?>
            <div class="layout-page">
                <?php include("nav.php"); ?>
                <div class="content-wrapper">
                    <div class="container-xxl flex-grow-1 container-p-y">

                        <h4 class="mb-4">Cloud Image Storage</h4>

                        <?php if (!empty($success_message)): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?= htmlspecialchars($success_message) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($error_message)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?= htmlspecialchars($error_message) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <div class="row">
                            <!-- Sidebar - Folders -->
                            <div class="col-md-3">
                                <div class="card">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h5 class="card-title mb-0">Folders</h5>
                                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createFolderModal">
                                            <i class="ri-add-line me-1"></i> New
                                        </button>
                                    </div>
                                    <div class="folder-sidebar">
                                        <?php while ($folder = $folders->fetch_assoc()): ?>
                                            <div class="folder-item <?= $current_folder == $folder['folder_name'] ? 'active' : '' ?>"
                                                onclick="location.href='?folder=<?= urlencode($folder['folder_name']) ?>'">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span>
                                                        <i class="ri-folder-2-fill me-2"></i>
                                                        <?= htmlspecialchars($folder['folder_name']) ?>
                                                    </span>
                                                    <span class="badge bg-secondary"><?= $folder['total_images'] ?></span>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Main Content -->
                            <div class="col-md-9">
                                <?php if ($current_folder): ?>
                                    <!-- Current Folder Header -->
                                    <div class="card mb-4">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <h5 class="card-title mb-0">
                                                <i class="ri-folder-2-fill me-2"></i>
                                                <?= htmlspecialchars($current_folder) ?>
                                            </h5>
                                            <div class="d-flex gap-2">
                                                <button type="button" class="btn btn-sm btn-warning"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#renameFolderModal"
                                                    data-folder="<?= htmlspecialchars($current_folder) ?>">
                                                    <i class="ri-edit-line me-1"></i> Rename
                                                </button>
                                                <a href="?delete_folder=<?= urlencode($current_folder) ?>"
                                                    class="btn btn-sm btn-danger"
                                                    onclick="return confirm('Delete this folder and all images?')">
                                                    <i class="ri-delete-bin-line me-1"></i> Delete
                                                </a>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Upload Area -->
                                    <div class="card mb-4">
                                        <div class="card-body">
                                            <form id="uploadForm" method="post" enctype="multipart/form-data">
                                                <input type="hidden" name="current_folder" value="<?= htmlspecialchars($current_folder) ?>">
                                                <div class="upload-area" id="uploadArea">
                                                    <i class="ri-upload-cloud-2-line display-4 text-primary mb-3"></i>
                                                    <h5>Drop images here or click to upload</h5>
                                                    <p class="text-muted">PNG, JPG, JPEG, GIF, WEBP (Max 3MB)</p>
                                                    <input type="file" name="file" id="fileInput"
                                                        accept=".jpg,.jpeg,.png,.gif,.webp"
                                                        style="display: none;"
                                                        onchange="document.getElementById('uploadForm').submit()">
                                                </div>
                                            </form>
                                        </div>
                                    </div>

                                    <!-- Images Grid -->
                                    <div class="row">
                                        <?php if ($images && $images->num_rows > 0): ?>
                                            <?php while ($image = $images->fetch_assoc()): ?>
                                                <div class="col-md-6 col-lg-4 mb-4">
                                                    <div class="image-card">
                                                        <img src="../<?= htmlspecialchars($image['image_url']) ?>"
                                                            alt="Image"
                                                            class="image-preview w-100 mb-3"
                                                            onerror="this.src='https://via.placeholder.com/300x200?text=Image+Not+Found'">

                                                        <div class="mb-3">
                                                            <label class="form-label small text-muted">Image URL</label>
                                                            <div class="url-box">
                                                                <?= $site_url . htmlspecialchars($image['image_url']) ?>
                                                            </div>
                                                            <button class="btn btn-sm btn-outline-primary w-100 mt-2"
                                                                onclick="copyToClipboard('<?= $site_url . htmlspecialchars($image['image_url']) ?>')">
                                                                <i class="ri-file-copy-line me-1"></i> Copy URL
                                                            </button>
                                                        </div>

                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <small class="text-muted">
                                                                <?= round($image['file_size'] / 1024, 2) ?> KB
                                                            </small>
                                                            <a href="?delete=<?= $image['id'] ?>&folder=<?= urlencode($current_folder) ?>"
                                                                class="btn btn-sm btn-danger"
                                                                onclick="return confirm('Delete this image?')">
                                                                <i class="ri-delete-bin-line"></i>
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <div class="col-12 text-center py-5">
                                                <i class="ri-image-line display-4 text-muted"></i>
                                                <p class="text-muted mt-3">No images in this folder. Upload some images above.</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-5">
                                        <i class="ri-folder-open-line display-4 text-muted"></i>
                                        <h5 class="text-muted mt-3">Select a folder or create a new one</h5>
                                        <p class="text-muted">Choose a folder from the sidebar to view and manage images.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>

                    <?php include("footer.php"); ?>

                </div>
            </div>
        </div>
    </div>

    <!-- Create Folder Modal -->
    <div class="modal fade" id="createFolderModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Folder</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Folder Name</label>
                            <input type="text" class="form-control" name="folder_name"
                                placeholder="Enter folder name" required
                                pattern="[a-zA-Z0-9_-]+"
                                title="Only letters, numbers, underscore and hyphen allowed">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="create_folder" class="btn btn-primary">Create Folder</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Rename Folder Modal -->
    <div class="modal fade" id="renameFolderModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Rename Folder</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post">
                    <input type="hidden" name="old_name" id="oldFolderName">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">New Folder Name</label>
                            <input type="text" class="form-control" name="new_name"
                                placeholder="Enter new folder name" required
                                pattern="[a-zA-Z0-9_-]+"
                                title="Only letters, numbers, underscore and hyphen allowed">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="rename_folder" class="btn btn-primary">Rename Folder</button>
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
        // Upload area click handler
        document.getElementById('uploadArea').addEventListener('click', function() {
            document.getElementById('fileInput').click();
        });

        // Drag and drop functionality
        const uploadArea = document.getElementById('uploadArea');
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            uploadArea.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, unhighlight, false);
        });

        function highlight() {
            uploadArea.classList.add('dragover');
        }

        function unhighlight() {
            uploadArea.classList.remove('dragover');
        }

        uploadArea.addEventListener('drop', handleDrop, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            document.getElementById('fileInput').files = files;
            document.getElementById('uploadForm').submit();
        }

        // Rename folder modal
        document.getElementById('renameFolderModal').addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const folderName = button.getAttribute('data-folder');
            document.getElementById('oldFolderName').value = folderName;
        });

        // Copy to clipboard
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                alert('URL copied to clipboard!');
            }, function(err) {
                console.error('Could not copy text: ', err);
            });
        }
    </script>

</body>

</html>