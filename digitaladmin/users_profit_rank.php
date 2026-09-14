<?php
session_start();
if (empty($_SESSION['unohs'])) {
    header("location: api/login.php?msg=unauthorized");
    exit;
}

include("api/conn.php");

// Helper: neat redirect (PRG)
function redirect_self($extra = []) {
    $base = strtok($_SERVER['REQUEST_URI'], '?');
    $qs = array_merge($_GET, $extra);
    header("Location: " . $base . (count($qs) ? '?' . http_build_query($qs) : ''));
    exit;
}

// Flash messages
$flash_err = $_SESSION['flash_err'] ?? '';
$flash_success = isset($_GET['saved']) ? "Record saved successfully!" : '';
unset($_SESSION['flash_err']);

// POST handler — INSERT/UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nick_name'])) {
    $id         = trim($_POST['id'] ?? '');
    $user_photo = trim($_POST['user_photo'] ?? '');
    $nick_name  = trim($_POST['nick_name'] ?? '');
    $price      = isset($_POST['price']) ? floatval($_POST['price']) : 0;
    $time       = trim($_POST['time'] ?? ''); // YYYY-MM-DD

    // basic validation
    if ($user_photo === '' || $nick_name === '' || $time === '' || $price <= 0) {
        $_SESSION['flash_err'] = "Please fill all fields correctly.";
        redirect_self();
    }

    // INSERT
    if ($id === '') {
        $sql = "INSERT INTO `daily_profit_rank`
                (`user_photo`, `nick_name`, `price`, `time`, `type_name`, `win_time`, `created_at`)
                VALUES (?, ?, ?, ?, 'Penarikan', NOW(), NOW())";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $_SESSION['flash_err'] = "Prepare failed: " . $conn->error;
            redirect_self();
        }
        $stmt->bind_param('ssds', $user_photo, $nick_name, $price, $time);
        if (!$stmt->execute()) {
            $_SESSION['flash_err'] = "Insert failed: " . $stmt->error;
            $stmt->close();
            redirect_self();
        }
        $stmt->close();
        redirect_self(['saved' => 1]);
    } else {
        // UPDATE (win_time = NOW() per your requirement)
        $sql = "UPDATE `daily_profit_rank`
                SET `user_photo`=?, `nick_name`=?, `price`=?, `time`=?, `type_name`='Penarikan', `win_time`=NOW()
                WHERE `id`=?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $_SESSION['flash_err'] = "Prepare failed: " . $conn->error;
            redirect_self();
        }
        $stmt->bind_param('ssdsi', $user_photo, $nick_name, $price, $time, $id);
        if (!$stmt->execute()) {
            $_SESSION['flash_err'] = "Update failed: " . $stmt->error;
            $stmt->close();
            redirect_self();
        }
        $stmt->close();
        redirect_self(['saved' => 1]);
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $delete_sql = "DELETE FROM daily_profit_rank WHERE id = ?";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param('i', $delete_id);
    if ($stmt->execute()) {
        $_SESSION['flash_success'] = "Record deleted successfully!";
    } else {
        $_SESSION['flash_err'] = "Failed to delete record!";
    }
    $stmt->close();
    redirect_self();
}

// Fetch all records
$records = $conn->query("SELECT * FROM daily_profit_rank ORDER BY price DESC, win_time DESC");
$edit_record = ['id' => '', 'user_photo' => '', 'nick_name' => '', 'price' => '', 'time' => ''];

// Check if editing
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $edit_result = $conn->query("SELECT * FROM daily_profit_rank WHERE id = $edit_id");
    if ($edit_result && $edit_result->num_rows > 0) {
        $edit_record = $edit_result->fetch_assoc();
    }
}
?>

<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr"
    data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template" data-style="light">

<head>
<meta charset="utf-8" />
<meta name="viewport"
    content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    
<title>Monthly Profit Rank</title>
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
.rank-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    margin-bottom: 12px;
    /*background: #fff;*/
    transition: all 0.3s ease;
}
.rank-row:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.rank-info {
    flex: 1;
}
.rank-actions {
    display: flex;
    gap: 10px;
}
.rank-badge {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    color: white;
    margin-right: 15px;
}
.rank-1 { background: #FFD700; } /* Gold */
.rank-2 { background: #C0C0C0; } /* Silver */
.rank-3 { background: #CD7F32; } /* Bronze */
.rank-other { background: #007bff; } /* Blue */
.amount-badge {
    background: #28a745;
    color: white;
    padding: 6px 12px;
    border-radius: 20px;
    font-weight: 600;
}
.user-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    object-fit: cover;
    margin-right: 15px;
    border: 2px solid #e0e0e0;
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

                    <h4 class="mb-4">Monthly Profit Rank</h4>

                    <!-- Flash Messages -->
                    <?php if (!empty($flash_err)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($flash_err) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($flash_success)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($flash_success) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Add/Edit Form -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <?= empty($edit_record['id']) ? 'Add New Profit Record' : 'Edit Profit Record' ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="post" action="">
                                <input type="hidden" name="id" value="<?= htmlspecialchars($edit_record['id']) ?>">
                                
                                <div class="row">
                                    <div class="col-md-3">
                                        <label class="form-label">User Photo URL</label>
                                        <input type="url" class="form-control" name="user_photo" 
                                               value="<?= htmlspecialchars($edit_record['user_photo']) ?>" 
                                               placeholder="https://example.com/photo.jpg" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Nick Name</label>
                                        <input type="text" class="form-control" name="nick_name" 
                                               value="<?= htmlspecialchars($edit_record['nick_name']) ?>" 
                                               placeholder="Enter nick name" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Profit Amount (₹)</label>
                                        <input type="number" class="form-control" name="price" 
                                               value="<?= htmlspecialchars($edit_record['price']) ?>" 
                                               step="0.01" min="0.01" placeholder="0.00" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Date</label>
                                        <input type="date" class="form-control" name="time" 
                                               value="<?= htmlspecialchars($edit_record['time']) ?>" required>
                                    </div>
                                    <div class="col-md-2 d-flex align-items-end">
                                        <div class="d-flex gap-2 w-100">
                                            <button type="submit" class="btn btn-success w-100">
                                                <?= empty($edit_record['id']) ? 'Add Record' : 'Update Record' ?>
                                            </button>
                                            <?php if (!empty($edit_record['id'])): ?>
                                                <a href="?" class="btn btn-secondary">Cancel</a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Profit Records List -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                Monthly Profit Rankings
                                <span class="badge bg-primary ms-2">
                                    <?= $records->num_rows ?> records
                                </span>
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if ($records->num_rows > 0): ?>
                                <?php 
                                $rank = 1;
                                while ($record = $records->fetch_assoc()): 
                                ?>
                                    <div class="rank-row">
                                        <div class="d-flex align-items-center">
                                            <div class="rank-badge <?= $rank <= 3 ? 'rank-' . $rank : 'rank-other' ?>">
                                                <?= $rank ?>
                                            </div>
                                            <?php if (!empty($record['user_photo'])): ?>
                                                <img src="<?= htmlspecialchars($record['user_photo']) ?>" 
                                                     alt="User" 
                                                     class="user-avatar"
                                                     onerror="this.src='https://via.placeholder.com/50x50?text=User'">
                                            <?php endif; ?>
                                            <div class="rank-info">
                                                <div class="d-flex align-items-center mb-2">
                                                    <h6 class="mb-0 me-3"><?= htmlspecialchars($record['nick_name']) ?></h6>
                                                    <span class="amount-badge">
                                                        ₹<?= number_format($record['price'], 2) ?>
                                                    </span>
                                                </div>
                                                <p class="mb-0 text-muted">
                                                    <i class="ri-calendar-line me-1"></i>
                                                    <?= date('d M Y', strtotime($record['time'])) ?> | 
                                                    <i class="ri-time-line me-1"></i>
                                                    <?= date('h:i A', strtotime($record['win_time'])) ?>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="rank-actions">
                                            <a href="?edit=<?= $record['id'] ?>" class="btn btn-sm btn-warning">
                                                <i class="ri-edit-line me-1"></i> Edit
                                            </a>
                                            <a href="?delete=<?= $record['id'] ?>" class="btn btn-sm btn-danger"
                                               onclick="return confirm('Are you sure you want to delete this record?')">
                                                <i class="ri-delete-bin-line me-1"></i> Delete
                                            </a>
                                        </div>
                                    </div>
                                <?php 
                                $rank++;
                                endwhile; 
                                ?>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="ri-line-chart-line display-4 text-muted"></i>
                                    <p class="text-muted mt-3">No profit records found. Add your first record above.</p>
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

</body>
</html>