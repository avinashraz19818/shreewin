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
CREATE TABLE IF NOT EXISTS country_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    country_code VARCHAR(10) NOT NULL,
    number_length VARCHAR(20) NOT NULL,
    status VARCHAR(10) DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (!$conn->query($createTableSQL)) {
    die("Table creation failed: " . $conn->error);
}

$success = false;
$error = '';

// Handle Add Country Code
if (isset($_POST['add'])) {
    $country_code = mysqli_real_escape_string($conn, $_POST['country_code']);
    $number_length = mysqli_real_escape_string($conn, $_POST['number_length']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    if (empty($country_code) || empty($number_length)) {
        $error = "Please fill all required fields";
    } else {
        $sql = "INSERT INTO country_codes (country_code, number_length, status) 
                VALUES ('$country_code', '$number_length', '$status')";
        
        if (mysqli_query($conn, $sql)) {
            $_SESSION['status_msg'] = "Country code added successfully!";
            header("Location: manage_country_codes.php");
            exit;
        } else {
            $error = "Insert failed: " . mysqli_error($conn);
        }
    }
}

// Handle Update Country Code
if (isset($_POST['update'])) {
    $id = (int)$_POST['id'];
    $country_code = mysqli_real_escape_string($conn, $_POST['country_code']);
    $number_length = mysqli_real_escape_string($conn, $_POST['number_length']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    if (empty($country_code) || empty($number_length)) {
        $error = "Please fill all required fields";
    } else {
        $sql = "UPDATE country_codes SET 
                country_code = '$country_code',
                number_length = '$number_length',
                status = '$status'
                WHERE id = $id";
        
        if (mysqli_query($conn, $sql)) {
            $_SESSION['status_msg'] = "Country code updated successfully!";
            header("Location: manage_country_codes.php");
            exit;
        } else {
            $error = "Update failed: " . mysqli_error($conn);
        }
    }
}

// Handle Delete Country Code
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if (mysqli_query($conn, "DELETE FROM country_codes WHERE id = $id")) {
        $_SESSION['status_msg'] = "Country code deleted successfully!";
    } else {
        $_SESSION['status_msg'] = "Delete failed: " . mysqli_error($conn);
    }
    header("Location: manage_country_codes.php");
    exit;
}

// Handle Status Change
if (isset($_GET['change_status'])) {
    $id = (int)$_GET['id'];
    $status = mysqli_real_escape_string($conn, $_GET['status']);
    
    if (mysqli_query($conn, "UPDATE country_codes SET status = '$status' WHERE id = $id")) {
        $_SESSION['status_msg'] = "Status updated successfully!";
    } else {
        $_SESSION['status_msg'] = "Status update failed: " . mysqli_error($conn);
    }
    header("Location: manage_country_codes.php");
    exit;
}

// Fetch all country codes
$sql = "SELECT * FROM country_codes ORDER BY id DESC";
$result = mysqli_query($conn, $sql);

// Get edit data if editing
$editData = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $editRes = mysqli_query($conn, "SELECT * FROM country_codes WHERE id = $editId");
    if ($editRes && mysqli_num_rows($editRes) > 0) {
        $editData = mysqli_fetch_assoc($editRes);
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
    <title>Country Code Management</title>
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
    .search-filter-section {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 20px;
}
.sort-btn.active {
    font-weight: bold;
}
        .edit-form-container {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            border: 2px solid #0d6efd;
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
                            <?php if (isset($_SESSION['status_msg'])): ?>
                                <div class="alert alert-info alert-dismissible fade show" role="alert">
                                    <?= $_SESSION['status_msg'] ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                                <?php unset($_SESSION['status_msg']); ?>
                            <?php endif; ?>
                            
                            <?php if (!empty($error)): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    ❌ Error: <?= htmlspecialchars($error) ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="row">
                            <!-- Country Code Form -->
                            <div class="col-12">
                                <?php if (isset($_GET['edit']) && $editData): ?>
                                <div class="edit-form-container">
                                    <h4 class="text-primary mb-3">✏️ Editing: <?= htmlspecialchars($editData['country_code']) ?></h4>
                                <?php endif; ?>
                                
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <?= (isset($_GET['edit']) && $editData) ? 'Edit Country Code' : 'Add New Country Code' ?>
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <form method="POST" class="row">
                                            <?php if (isset($_GET['edit']) && $editData): ?>
                                                <input type="hidden" name="id" value="<?= $editData['id'] ?>">
                                                <input type="hidden" name="update" value="1">
                                            <?php else: ?>
                                                <input type="hidden" name="add" value="1">
                                            <?php endif; ?>
                                            
                                            <div class="col-md-4 mb-3">
                                                <label for="country_code" class="form-label">Country Code *</label>
                                                <input type="text" class="form-control" id="country_code" name="country_code" 
                                                       value="<?= htmlspecialchars($editData['country_code'] ?? '') ?>" required 
                                                       placeholder="e.g., +880, +1, +44">
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="number_length" class="form-label">Number Length *</label>
                                                <input type="text" class="form-control" id="number_length" name="number_length" 
                                                       value="<?= htmlspecialchars($editData['number_length'] ?? '') ?>" required 
                                                       placeholder="e.g., 10, 9-12">
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="status" class="form-label">Status</label>
                                                <select class="form-select" id="status" name="status">
                                                    <option value="Active" <?= ($editData['status'] ?? 'Active') == 'Active' ? 'selected' : '' ?>>Active</option>
                                                    <option value="Inactive" <?= ($editData['status'] ?? '') == 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                                                </select>
                                            </div>

                                            <div class="col-12">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="ri-save-line ri-16px me-2"></i>
                                                    <?= (isset($_GET['edit']) && $editData) ? 'Update Country Code' : 'Add Country Code' ?>
                                                </button>
                                                <?php if (isset($_GET['edit']) && $editData): ?>
                                                    <a href="manage_country_codes.php" class="btn btn-secondary">
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
<div class="card mb-4">
    <div class="card-header">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h5 class="card-title mb-0">🔍 Search & Filter</h5>
            </div>
            <div class="col-md-6 text-end">
                <span class="badge bg-primary">
                    <?= mysqli_num_rows($result) ?> Total
                </span>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="searchInput" class="form-label">Search Country Codes</label>
                <input type="text" class="form-control" id="searchInput" placeholder="Search by country code or length...">
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
                    <button type="button" class="btn btn-outline-primary sort-btn" data-sort="all">
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

                            <!-- Country Codes Table -->
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h5 class="card-title mb-0">📜 Country Code List</h5>
                                            <span class="badge bg-primary">
                                                <?= mysqli_num_rows($result) ?> Total
                                            </span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-bordered">
                                                <thead class="table-dark">
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Country Code</th>
                                                        <th>Length</th>
                                                        <th>Status</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    if (mysqli_num_rows($result) > 0) {
                                                        $count = 1;
                                                        while ($row = mysqli_fetch_assoc($result)) {
                                                            echo "<tr>";
                                                            echo "<td>".$count++."</td>";
                                                            echo "<td>".$row['country_code']."</td>";
                                                            echo "<td>".$row['number_length']."</td>";
                                                            echo "<td><label class='badge badge-".($row['status'] == 'Active' ? 'success' : 'danger')."'>".$row['status']."</label></td>";
                                                            echo "<td>
                                                                <a href='?edit=".$row['id']."' class='btn btn-warning btn-sm'>✏️ Edit</a>
                                                                <a href='?delete=".$row['id']."' onclick=\"return confirm('Are you sure?')\" class='btn btn-danger btn-sm'>🗑️ Delete</a>
                                                                <a href='?change_status&id=".$row['id']."&status=".($row['status'] == 'Active' ? 'Inactive' : 'Active')."' class='btn btn-info btn-sm'>🔁 ".($row['status'] == 'Active' ? 'Inactivate' : 'Activate')."</a>
                                                            </td>";
                                                            echo "</tr>";
                                                        }
                                                    } else {
                                                        echo "<tr><td colspan='5' class='text-center'>No country codes found.</td></tr>";
                                                    }
                                                    ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <!-- Statistics -->
                                <div class="card mt-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Statistics</h5>
                                    </div>
                                    <div class="card-body">
                                        <?php
                                        $total_sql = "SELECT COUNT(*) as total FROM country_codes";
                                        $active_sql = "SELECT COUNT(*) as active FROM country_codes WHERE status = 'Active'";
                                        $inactive_sql = "SELECT COUNT(*) as inactive FROM country_codes WHERE status = 'Inactive'";
                                        
                                        $total_result = mysqli_query($conn, $total_sql);
                                        $active_result = mysqli_query($conn, $active_sql);
                                        $inactive_result = mysqli_query($conn, $inactive_sql);
                                        
                                        $total = mysqli_fetch_assoc($total_result)['total'];
                                        $active = mysqli_fetch_assoc($active_result)['active'];
                                        $inactive = mysqli_fetch_assoc($inactive_result)['inactive'];
                                        ?>
                                        <div class="row text-center">
                                            <div class="col-4">
                                                <div class="stats-value"><?= $total ?></div>
                                                <div class="stats-label">Total</div>
                                            </div>
                                            <div class="col-4">
                                                <div class="stats-value text-success"><?= $active ?></div>
                                                <div class="stats-label">Active</div>
                                            </div>
                                            <div class="col-4">
                                                <div class="stats-value text-secondary"><?= $inactive ?></div>
                                                <div class="stats-label">Inactive</div>
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
        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);

        // Enhanced delete confirmation
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('btn-danger')) {
                if (!confirm('Are you sure you want to delete this country code? This action cannot be undone.')) {
                    e.preventDefault();
                }
            }
        });
        
        // Search and Filter Functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const sortButtons = document.querySelectorAll('.sort-btn');
    const tableBody = document.querySelector('tbody');
    const originalRows = Array.from(tableBody.querySelectorAll('tr'));

    // Search functionality
    searchInput.addEventListener('input', filterTable);
    statusFilter.addEventListener('change', filterTable);

    // Sort buttons functionality
    sortButtons.forEach(button => {
        button.addEventListener('click', function() {
            const sortType = this.getAttribute('data-sort');
            
            // Remove active class from all buttons
            sortButtons.forEach(btn => btn.classList.remove('btn-primary', 'btn-success', 'btn-secondary'));
            
            // Add appropriate class based on sort type
            switch(sortType) {
                case 'all':
                    this.classList.add('btn-primary');
                    statusFilter.value = 'all';
                    break;
                case 'active':
                    this.classList.add('btn-success');
                    statusFilter.value = 'Active';
                    break;
                case 'inactive':
                    this.classList.add('btn-secondary');
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
            const countryCode = cells[1].textContent.toLowerCase();
            const numberLength = cells[2].textContent.toLowerCase();
            const status = cells[3].querySelector('.badge').textContent;
            
            // Search filter
            const matchesSearch = countryCode.includes(searchTerm) || 
                                numberLength.includes(searchTerm);
            
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
            noResultsRow.innerHTML = `<td colspan="5" class="text-center text-muted">No matching country codes found.</td>`;
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
    }, 5000);

    // Enhanced delete confirmation
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('btn-danger')) {
            if (!confirm('Are you sure you want to delete this country code? This action cannot be undone.')) {
                e.preventDefault();
            }
        }
    });
});
    </script>
</body>
</html>