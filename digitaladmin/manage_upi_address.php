<?php
session_start();
if (empty($_SESSION['unohs'])) {
    header("location: api/login.php?msg=unauthorized");
    exit;
}

include("api/conn.php");

$user_records = null;
$searched_userid = '';

// Search user records
if(isset($_POST['search_user'])) {
    $userid = mysqli_real_escape_string($conn, $_POST['userid']);
    $searched_userid = $userid;
    if(!empty($userid)) {
        $user_records = mysqli_query($conn, "SELECT * FROM upi_address WHERE byabaharkarta = '$userid'");
        if(mysqli_num_rows($user_records) == 0) {
            echo '<script type="text/JavaScript">alert("No UPI records found for this User ID!");</script>';
        }
    }
}

// Update record
if(isset($_POST['update'])) {
    $id = mysqli_real_escape_string($conn, $_POST['id']);
    $khatesankhye = mysqli_real_escape_string($conn, $_POST['khatesankhye']);
    $phalanubhavi = mysqli_real_escape_string($conn, $_POST['phalanubhavi']);
    
    $update_sql = "UPDATE upi_address 
                   SET khatesankhye = '$khatesankhye', 
                       phalanubhavi = '$phalanubhavi'
                   WHERE id = '$id'";
    
    if(mysqli_query($conn, $update_sql)) {
        echo '<script type="text/JavaScript">alert("UPI details updated successfully!");</script>';
        if(isset($_POST['userid'])){
            $userid = mysqli_real_escape_string($conn, $_POST['userid']);
            $searched_userid = $userid;
            $user_records = mysqli_query($conn, "SELECT * FROM upi_address WHERE byabaharkarta = '$userid'");
        }
    } else {
        echo '<script type="text/JavaScript">alert("Failed to update UPI details!");</script>';
    }
}

// Delete record
if(isset($_POST['delete'])) {
    $id = mysqli_real_escape_string($conn, $_POST['id']);
    $delete_sql = "DELETE FROM upi_address WHERE id = '$id'";
    if(mysqli_query($conn, $delete_sql)) {
        echo '<script type="text/JavaScript">alert("UPI address deleted successfully!");</script>';
        if(isset($_POST['userid'])){
            $userid = mysqli_real_escape_string($conn, $_POST['userid']);
            $searched_userid = $userid;
            $user_records = mysqli_query($conn, "SELECT * FROM upi_address WHERE byabaharkarta = '$userid'");
        }
    } else {
        echo '<script type="text/JavaScript">alert("Failed to delete UPI address!");</script>';
    }
}
?>

<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr"
    data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template" data-style="light">

<head>
<meta charset="utf-8" />
<meta name="viewport"
    content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    
<title>Manage UPI Addresses</title>
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
.upi-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    margin-bottom: 12px;
    /*background: #fff;*/
}
.upi-info {
    flex: 1;
}
.upi-actions {
    display: flex;
    gap: 10px;
}
.upi-type {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
    /*background: #d4edda;*/
    color: #155724;
}
.edit-form {
    display: none;
    margin-top: 15px;
    padding: 15px;
    border: 1px solid #007bff;
    border-radius: 8px;
    /*background: #f8f9fa;*/
}
.upi-address {
    font-family: monospace;
    /*background: #f8f9fa;*/
    padding: 5px 10px;
    border-radius: 4px;
    font-size: 14px;
    margin-top: 5px;
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

                    <h4 class="mb-4">Manage UPI Addresses</h4>

                    <!-- Search User Card -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Search User UPI Addresses</h5>
                        </div>
                        <div class="card-body">
                            <form method="post" action="">
                                <div class="row">
                                    <div class="col-md-8">
                                        <label for="userid" class="form-label">User ID</label>
                                        <input type="text" class="form-control" name="userid" id="userid" 
                                               placeholder="Enter User ID" 
                                               value="<?= htmlspecialchars($searched_userid) ?>" required>
                                    </div>
                                    <div class="col-md-4 d-flex align-items-end">
                                        <button type="submit" name="search_user" class="btn btn-primary w-100">
                                            Search User
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- User UPI Records -->
                    <?php if ($user_records && mysqli_num_rows($user_records) > 0): ?>
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                UPI Addresses for User: <?= htmlspecialchars($searched_userid) ?>
                                <span class="badge bg-primary ms-2">
                                    <?= mysqli_num_rows($user_records) ?> UPI address(es)
                                </span>
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php while ($row = mysqli_fetch_assoc($user_records)): ?>
                                <div class="upi-row" id="row-<?= $row['id'] ?>">
                                    <div class="upi-info">
                                        <div class="d-flex align-items-center mb-2">
                                            <h6 class="mb-0 me-3"><?= htmlspecialchars($row['phalanubhavi']) ?></h6>
                                            <span class="upi-type">
                                                UPI
                                            </span>
                                        </div>
                                        <p class="mb-1">
                                            <strong>UPI ID:</strong> 
                                            <span class="upi-address"><?= htmlspecialchars($row['khatesankhye']) ?></span>
                                        </p>
                                        <p class="mb-0 text-muted small">
                                            <strong>ID:</strong> <?= $row['id'] ?> | 
                                            <strong>Account Type:</strong> <?= $row['khatakrama'] ?>
                                        </p>
                                    </div>
                                    <div class="upi-actions">
                                        <button type="button" class="btn btn-sm btn-warning" 
                                                onclick="toggleEditForm(<?= $row['id'] ?>)">
                                            <i class="ri-edit-line me-1"></i> Edit
                                        </button>
                                        <form method="post" action="" style="display: inline;">
                                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                            <input type="hidden" name="userid" value="<?= $searched_userid ?>">
                                            <button type="submit" name="delete" class="btn btn-sm btn-danger"
                                                    onclick="return confirm('Are you sure you want to delete this UPI address?')">
                                                <i class="ri-delete-bin-line me-1"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>

                                <!-- Edit Form -->
                                <div class="edit-form" id="edit-form-<?= $row['id'] ?>">
                                    <form method="post" action="">
                                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                        <input type="hidden" name="userid" value="<?= $searched_userid ?>">
                                        
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label class="form-label">Account Holder Name</label>
                                                <input type="text" class="form-control" name="phalanubhavi" 
                                                       value="<?= htmlspecialchars($row['phalanubhavi']) ?>" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">UPI ID</label>
                                                <input type="text" class="form-control" name="khatesankhye" 
                                                       value="<?= htmlspecialchars($row['khatesankhye']) ?>" 
                                                       placeholder="e.g., example@upi" required>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-12 d-flex justify-content-end gap-2">
                                                <button type="submit" name="update" class="btn btn-success">
                                                    Update UPI
                                                </button>
                                                <button type="button" class="btn btn-secondary" 
                                                        onclick="toggleEditForm(<?= $row['id'] ?>)">
                                                    Cancel
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                </div>

                <?php include("footer.php"); ?>
                
            </div>
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
    function toggleEditForm(id) {
        const editForm = document.getElementById('edit-form-' + id);
        if (editForm.style.display === 'block') {
            editForm.style.display = 'none';
        } else {
            // Hide all other edit forms
            document.querySelectorAll('.edit-form').forEach(form => {
                form.style.display = 'none';
            });
            editForm.style.display = 'block';
        }
    }

    // Hide edit form when clicking outside
    document.addEventListener('click', function(event) {
        if (!event.target.closest('.upi-row') && !event.target.closest('.edit-form')) {
            document.querySelectorAll('.edit-form').forEach(form => {
                form.style.display = 'none';
            });
        }
    });
    </script>

</body>
</html>