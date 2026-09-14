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
$createTableSQL = "CREATE TABLE IF NOT EXISTS banks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bankID VARCHAR(50) NOT NULL,
    bankName VARCHAR(255) NOT NULL,
    bankLogo VARCHAR(500),
    reserved VARCHAR(100) NOT NULL,
    status VARCHAR(20) DEFAULT 'Inactive',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if (!$conn->query($createTableSQL)) {
    die("Table creation failed: " . $conn->error);
}

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? null;

    if ($action === 'add') {
        $bankID   = trim($_POST['bankID'] ?? '');
        $bankName = trim($_POST['bankName'] ?? '');
        $bankLogo = trim($_POST['bankLogo'] ?? '');
        $reserved = trim($_POST['reserved'] ?? '');
        $status   = trim($_POST['status'] ?? 'Inactive');

        // basic validation
        if ($bankID === '' || $bankName === '' || $reserved === '') {
            $feedback = 'Please provide Bank ID, Bank Name and Reserved.';
            $feedback_type = 'error';
        } else {
            $stmt = $conn->prepare("INSERT INTO banks (bankID, bankName, bankLogo, reserved, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("issss", $bankID, $bankName, $bankLogo, $reserved, $status);
            $ok = $stmt->execute();
            $stmt->close();
            if ($ok) {
                $feedback = 'Bank added successfully.';
                $feedback_type = 'success';
            } else {
                $feedback = 'Insert failed: ' . htmlspecialchars($conn->error);
                $feedback_type = 'error';
            }
        }
    } elseif ($action === 'update') {
        $id       = intval($_POST['id'] ?? 0);
        $bankID   = trim($_POST['bankID'] ?? '');
        $bankName = trim($_POST['bankName'] ?? '');
        $bankLogo = trim($_POST['bankLogo'] ?? '');
        $reserved = trim($_POST['reserved'] ?? '');
        $status   = trim($_POST['status'] ?? 'Inactive');

        if ($id <= 0 || $bankID === '' || $bankName === '') {
            $feedback = 'Missing required fields for update.';
            $feedback_type = 'error';
        } else {
            $stmt = $conn->prepare("UPDATE banks SET bankID = ?, bankName = ?, bankLogo = ?, reserved = ?, status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("issssi", $bankID, $bankName, $bankLogo, $reserved, $status, $id);
            $ok = $stmt->execute();
            $stmt->close();
            if ($ok) {
                $feedback = 'Bank updated successfully.';
                $feedback_type = 'success';
            } else {
                $feedback = 'Update failed: ' . htmlspecialchars($conn->error);
                $feedback_type = 'error';
            }
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $conn->prepare("DELETE FROM banks WHERE id = ?");
            $stmt->bind_param("i", $id);
            $ok = $stmt->execute();
            $stmt->close();
            if ($ok) {
                $feedback = 'Bank deleted successfully.';
                $feedback_type = 'success';
            } else {
                $feedback = 'Delete failed: ' . htmlspecialchars($conn->error);
                $feedback_type = 'error';
            }
        } else {
            $feedback = 'Invalid bank id for delete.';
            $feedback_type = 'error';
        }
    }

    // After handle POST normally redirect to avoid form resubmission
    $_SESSION['bank_feedback'] = $feedback;
    $_SESSION['bank_feedback_type'] = $feedback_type;
    $redirect = strtok($_SERVER["REQUEST_URI"], '?');
    header("Location: $redirect");
    exit;
}

// Read any flash feedback
if (!empty($_SESSION['bank_feedback'])) {
    $feedback = $_SESSION['bank_feedback'];
    $feedback_type = $_SESSION['bank_feedback_type'] ?? 'info';
    unset($_SESSION['bank_feedback'], $_SESSION['bank_feedback_type']);
}

// Get edit data if editing
$editData = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $editRes = mysqli_query($conn, "SELECT * FROM banks WHERE id = $editId");
    if ($editRes && mysqli_num_rows($editRes) > 0) {
        $editData = mysqli_fetch_assoc($editRes);
    }
}

// ---------- Fetch banks ----------
$banks = [];
$qr = $conn->query("SELECT id, bankID, bankName, bankLogo, reserved, status FROM banks ORDER BY id ASC");
if ($qr) {
    while ($r = $qr->fetch_assoc()) $banks[] = $r;
}

// Get statistics
$total_banks = count($banks);
$active_banks = 0;
$inactive_banks = 0;

foreach ($banks as $bank) {
    if ($bank['status'] == 'Active') {
        $active_banks++;
    } else {
        $inactive_banks++;
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
    <title>Bank Management System</title>
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
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .stats-value {
            font-size: 24px;
            font-weight: bold;
        }
        .stats-label {
            font-size: 14px;
            opacity: 0.9;
        }
        .search-filter-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .sort-btn.active {
            font-weight: bold;
            transform: scale(1.05);
        }
        .edit-form-container {
            background: #e3f2fd;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #2196f3;
        }
        .bank-logo-preview {
            max-width: 50px;
            max-height: 30px;
            object-fit: contain;
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
                            <?php if (isset($feedback)): ?>
                                <div class="alert alert-<?= $feedback_type == 'success' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
                                    <?= $feedback ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Statistics Cards -->
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="stats-card">
                                    <div class="stats-value"><?= $total_banks ?></div>
                                    <div class="stats-label">Total Banks</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="stats-card" style="background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);">
                                    <div class="stats-value"><?= $active_banks ?></div>
                                    <div class="stats-label">Active Banks</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="stats-card" style="background: linear-gradient(135deg, #ff9800 0%, #e68900 100%);">
                                    <div class="stats-value"><?= $inactive_banks ?></div>
                                    <div class="stats-label">Inactive Banks</div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Bank Form -->
                            <div class="col-12">
                                <?php if (isset($_GET['edit']) && $editData): ?>
                                <div class="edit-form-container">
                                    <h4 class="text-primary mb-3"><i class="ri-edit-line ri-16px me-2"></i>Editing: <?= htmlspecialchars($editData['bankName']) ?></h4>
                                <?php endif; ?>
                                
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <i class="ri-bank-line ri-16px me-2"></i>
                                            <?= (isset($_GET['edit']) && $editData) ? 'Edit Bank' : 'Add New Bank' ?>
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <form method="POST" class="row">
                                            <?php if (isset($_GET['edit']) && $editData): ?>
                                                <input type="hidden" name="id" value="<?= $editData['id'] ?>">
                                                <input type="hidden" name="action" value="update">
                                            <?php else: ?>
                                                <input type="hidden" name="action" value="add">
                                            <?php endif; ?>
                                            
                                            <div class="col-md-3 mb-3">
                                                <label for="bankID" class="form-label">Bank ID *</label>
                                                <input type="number" class="form-control" id="bankID" name="bankID" 
                                                       value="<?= htmlspecialchars($editData['bankID'] ?? '') ?>" required 
                                                       placeholder="Enter bank ID">
                                            </div>

                                            <div class="col-md-3 mb-3">
                                                <label for="bankName" class="form-label">Bank Name *</label>
                                                <input type="text" class="form-control" id="bankName" name="bankName" 
                                                       value="<?= htmlspecialchars($editData['bankName'] ?? '') ?>" required 
                                                       placeholder="Enter bank name">
                                            </div>

                                            <div class="col-md-3 mb-3">
                                                <label for="bankLogo" class="form-label">Bank Logo URL</label>
                                                <input type="text" class="form-control" id="bankLogo" name="bankLogo" 
                                                       value="<?= htmlspecialchars($editData['bankLogo'] ?? '') ?>" 
                                                       placeholder="Enter logo URL">
                                            </div>

                                            <div class="col-md-3 mb-3">
                                                <label for="reserved" class="form-label">Reserved *</label>
                                                <input type="text" class="form-control" id="reserved" name="reserved" 
                                                       value="<?= htmlspecialchars($editData['reserved'] ?? '') ?>" required 
                                                       placeholder="Enter reserved value">
                                            </div>

                                            <div class="col-md-3 mb-3">
                                                <label for="status" class="form-label">Status</label>
                                                <select class="form-select" id="status" name="status">
                                                    <option value="Active" <?= ($editData['status'] ?? 'Inactive') == 'Active' ? 'selected' : '' ?>>Active</option>
                                                    <option value="Inactive" <?= ($editData['status'] ?? 'Inactive') == 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                                                </select>
                                            </div>

                                            <div class="col-12">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="ri-save-line ri-16px me-2"></i>
                                                    <?= (isset($_GET['edit']) && $editData) ? 'Update Bank' : 'Add Bank' ?>
                                                </button>
                                                <?php if (isset($_GET['edit']) && $editData): ?>
                                                    <a href="manage_banks.php" class="btn btn-secondary">
                                                        <i class="ri-close-line ri-16px me-2"></i>Cancel Edit
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                
                                <?php if (isset($_GET['edit']) && $editData): ?>
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- Search and Filter Section -->
                            <div class="col-12">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h5 class="card-title mb-0"><i class="ri-search-line ri-16px me-2"></i>Search & Filter</h5>
                                            <span class="badge bg-primary">
                                                <?= $total_banks ?> Total Banks
                                            </span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="searchInput" class="form-label">Search Banks</label>
                                                <input type="text" class="form-control" id="searchInput" placeholder="Search by bank name, ID or reserved...">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="statusFilter" class="form-label">Filter by Status</label>
                                                <select class="form-select" id="statusFilter">
                                                    <option value="all">All Status</option>
                                                    <option value="Active">Active Only</option>
                                                    <option value="Inactive">Inactive Only</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-12">
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-outline-primary sort-btn active" data-sort="all">
                                                        <i class="ri-list-check ri-16px me-2"></i>All
                                                    </button>
                                                    <button type="button" class="btn btn-outline-success sort-btn" data-sort="active">
                                                        <i class="ri-checkbox-circle-line ri-16px me-2"></i>Active
                                                    </button>
                                                    <button type="button" class="btn btn-outline-secondary sort-btn" data-sort="inactive">
                                                        <i class="ri-close-circle-line ri-16px me-2"></i>Inactive
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Banks Table -->
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0"><i class="ri-bank-line ri-16px me-2"></i>Bank List</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-bordered">
                                                <thead class="table-dark">
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Bank ID</th>
                                                        <th>Bank Name</th>
                                                        <th>Logo</th>
                                                        <th>Reserved</th>
                                                        <th>Status</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="banksTableBody">
                                                    <?php
                                                    if (count($banks) > 0) {
                                                        $count = 1;
                                                        foreach ($banks as $bank) {
                                                            echo "<tr>";
                                                            echo "<td>".$count++."</td>";
                                                            echo "<td>".$bank['bankID']."</td>";
                                                            echo "<td>".htmlspecialchars($bank['bankName'])."</td>";
                                                            echo "<td>";
                                                            if (!empty($bank['bankLogo'])) {
                                                                echo "<img src='".htmlspecialchars($bank['bankLogo'])."' class='bank-logo-preview' alt='Logo'>";
                                                            } else {
                                                                echo "<span class='text-muted'>No Logo</span>";
                                                            }
                                                            echo "</td>";
                                                            echo "<td>".htmlspecialchars($bank['reserved'])."</td>";
                                                            echo "<td><span class='badge badge-".($bank['status'] == 'Active' ? 'success' : 'secondary')."'>".$bank['status']."</span></td>";
                                                            echo "<td>
                                                                <a href='?edit=".$bank['id']."' class='btn btn-warning btn-sm'><i class='ri-edit-line ri-14px me-1'></i>Edit</a>
                                                                <form method='POST' style='display:inline;' onsubmit='return confirm(\"Are you sure you want to delete this bank?\")'>
                                                                    <input type='hidden' name='action' value='delete'>
                                                                    <input type='hidden' name='id' value='".$bank['id']."'>
                                                                    <button type='submit' class='btn btn-danger btn-sm'><i class='ri-delete-bin-line ri-14px me-1'></i>Delete</button>
                                                                </form>
                                                            </td>";
                                                            echo "</tr>";
                                                        }
                                                    } else {
                                                        echo "<tr><td colspan='7' class='text-center'>No banks found. Add your first bank above.</td></tr>";
                                                    }
                                                    ?>
                                                </tbody>
                                            </table>
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
        // Search and Filter Functionality
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const statusFilter = document.getElementById('statusFilter');
            const sortButtons = document.querySelectorAll('.sort-btn');
            const tableBody = document.getElementById('banksTableBody');
            const originalRows = Array.from(tableBody.querySelectorAll('tr'));

            // Search functionality
            searchInput.addEventListener('input', filterTable);
            statusFilter.addEventListener('change', filterTable);

            // Sort buttons functionality
            sortButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const sortType = this.getAttribute('data-sort');
                    
                    // Remove active class from all buttons
                    sortButtons.forEach(btn => btn.classList.remove('active', 'btn-primary', 'btn-success', 'btn-secondary'));
                    
                    // Add appropriate class based on sort type
                    switch(sortType) {
                        case 'all':
                            this.classList.add('active', 'btn-primary');
                            statusFilter.value = 'all';
                            break;
                        case 'active':
                            this.classList.add('active', 'btn-success');
                            statusFilter.value = 'Active';
                            break;
                        case 'inactive':
                            this.classList.add('active', 'btn-secondary');
                            statusFilter.value = 'Inactive';
                            break;
                    }
                    
                    filterTable();
                });
            });

            function filterTable() {
                const searchTerm = searchInput.value.toLowerCase();
                const statusValue = statusFilter.value;
                
                tableBody.innerHTML = '';
                
                const filteredRows = originalRows.filter(row => {
                    const cells = row.querySelectorAll('td');
                    if (cells.length < 6) return false;
                    
                    const bankID = cells[1].textContent.toLowerCase();
                    const bankName = cells[2].textContent.toLowerCase();
                    const reserved = cells[4].textContent.toLowerCase();
                    const status = cells[5].querySelector('.badge').textContent;
                    
                    // Search filter
                    const matchesSearch = bankID.includes(searchTerm) || 
                                        bankName.includes(searchTerm) ||
                                        reserved.includes(searchTerm);
                    
                    // Status filter
                    const matchesStatus = statusValue === 'all' || status === statusValue;
                    
                    return matchesSearch && matchesStatus;
                });
                
                // Add filtered rows back to table
                filteredRows.forEach(row => {
                    tableBody.appendChild(row);
                });
                
                // Show message if no results
                if (filteredRows.length === 0) {
                    const noResultsRow = document.createElement('tr');
                    noResultsRow.innerHTML = `<td colspan="7" class="text-center text-muted">No matching banks found.</td>`;
                    tableBody.appendChild(noResultsRow);
                }
            }

            // Auto-hide alerts after 5 seconds
            setTimeout(() => {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(alert => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 50000);
        });
    </script>
</body>
</html>