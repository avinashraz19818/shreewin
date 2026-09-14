<?php
session_start();
if (empty($_SESSION['unohs'])) {
    header("location: api/login.php?msg=unauthorized");
    exit;
}

include("api/conn.php");

$success_message = "";

// Update record
if (isset($_POST['update'])) {
    $id = mysqli_real_escape_string($conn, $_POST['id']);
    $day = mysqli_real_escape_string($conn, $_POST['day']);
    $amount = mysqli_real_escape_string($conn, $_POST['amount']);
    $bonus = mysqli_real_escape_string($conn, $_POST['bonus']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    mysqli_query($conn, "UPDATE signin_recharge_rewards SET day='$day', amount='$amount', bonus='$bonus', status='$status' WHERE id='$id'");
    $success_message = "Daily Signin Bonus Updated Successfully!";
}

// Fetch all records
$rewardsResult = mysqli_query($conn, "SELECT * FROM signin_recharge_rewards ORDER BY day ASC");
?>

<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr"
    data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template" data-style="light">

<head>
<meta charset="utf-8" />
<meta name="viewport"
    content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    
<title>Manage Daily Signin Bonus</title>
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
.rewards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
    margin-top: 20px;
}
.reward-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    padding: 20px;
    position: relative;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border: 1px solid #eaeaea;
}
.reward-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
}
.day-header {
    display: flex;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 12px;
    border-bottom: 1px solid #f0f0f0;
}
.day-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #6a82fb, #fc5c7d);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 12px;
    color: white;
    font-weight: bold;
    font-size: 16px;
}
.day-title {
    font-size: 18px;
    font-weight: 600;
    color: #333;
}
.reward-details {
    margin-bottom: 15px;
}
.reward-item {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
    padding: 8px 0;
}
.reward-icon {
    width: 24px;
    height: 24px;
    margin-right: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8f9fa;
    border-radius: 4px;
    color: #6c757d;
}
.reward-label {
    color: #666;
    font-size: 14px;
    font-weight: 500;
    flex: 1;
}
.reward-value {
    color: #333;
    font-weight: 600;
    font-size: 15px;
}
.status-badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}
.status-active {
    background: #e8f5e9;
    color: #2e7d32;
}
.status-inactive {
    background: #ffebee;
    color: #c62828;
}
.card-actions {
    display: flex;
    justify-content: flex-end;
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #f0f0f0;
}
.edit-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 9999;
    align-items: center;
    justify-content: center;
}
.edit-modal-content {
    background: white;
    border-radius: 12px;
    width: 90%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
}
.edit-modal-header {
    padding: 20px 24px 0;
    border-bottom: 1px solid #eaeaea;
}
.edit-modal-body {
    padding: 24px;
}
.edit-modal-footer {
    padding: 16px 24px 24px;
    border-top: 1px solid #eaeaea;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
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

                    <h4 class="mb-4">Manage Daily Signin Bonus</h4>

                    <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($success_message) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Daily Signin Rewards List -->
                    <div class="card">
                        <div class="card-header">
    <h5 class="card-title mb-0">
        Daily Signin Bonus Rewards
        <span class="badge bg-primary ms-2">
            <?= mysqli_num_rows($rewardsResult) ?> day(s)
        </span>
    </h5>
</div>
                        <div class="card-body">
                            <?php 
                            $rewardsCount = mysqli_num_rows($rewardsResult);
                            if ($rewardsCount > 0): 
                            ?>
                                <div class="rewards-grid">
                                <?php while ($reward = mysqli_fetch_assoc($rewardsResult)): ?>
                                    <div class="reward-card" id="row-<?= $reward['id'] ?>">
                                        <div class="day-header">
                                            <div class="day-icon">
                                                <!-- Replace this with your image path -->
                                                <img src="assets/img/rewards/day-<?= $reward['day'] ?>.png" alt="Day <?= $reward['day'] ?>" 
                                                     onerror="this.style.display='none'" width="24" height="24">
                                                <?php /* Fallback text if image not found */ ?>
                                                <span style="display: none;"><?= $reward['day'] ?></span>
                                            </div>
                                            <div class="day-title">
                                                DAY <?= $reward['day'] ?>
                                            </div>
                                        </div>
                                        
                                        <div class="reward-details">
                                            <div class="reward-item">
                                                <div class="reward-icon">
                                                    <!-- Replace this with your recharge icon -->
                                                    <img src="assets/img/icons/recharge.png" alt="Recharge" 
                                                         onerror="this.innerHTML='₹'" width="16" height="16">
                                                </div>
                                                <span class="reward-label">Min Recharge:</span>
                                                <span class="reward-value">₹<?= number_format($reward['amount'], 2) ?></span>
                                            </div>
                                            <div class="reward-item">
                                                <div class="reward-icon">
                                                    <!-- Replace this with your bonus icon -->
                                                    <img src="assets/img/icons/bonus.png" alt="Bonus" 
                                                         onerror="this.innerHTML='🎁'" width="16" height="16">
                                                </div>
                                                <span class="reward-label">Bonus:</span>
                                                <span class="reward-value">₹<?= number_format($reward['bonus'], 2) ?></span>
                                            </div>
                                        </div>
                                        
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <span class="status-badge <?= $reward['status'] == '1' ? 'status-active' : 'status-inactive' ?>">
                                                <?= $reward['status'] == '1' ? 'ACTIVE' : 'INACTIVE' ?>
                                            </span>
                                            <small class="text-muted">ID: <?= $reward['id'] ?></small>
                                        </div>
                                        
                                        <div class="card-actions">
                                            <button type="button" class="btn btn-sm btn-warning" 
                                                    onclick="openEditModal(<?= $reward['id'] ?>)">
                                                <i class="ri-edit-line me-1"></i> Edit
                                            </button>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <p class="text-muted">No daily signin rewards found.</p>
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

    <!-- Edit Modal -->
    <div class="edit-modal" id="editModal">
        <div class="edit-modal-content">
            <div class="edit-modal-header">
                <h5 class="modal-title">Edit Daily Bonus</h5>
                <button type="button" class="btn-close" onclick="closeEditModal()" style="margin-top: -10px;"></button>
            </div>
            <form method="post" action="" id="editForm">
                <div class="edit-modal-body">
                    <input type="hidden" name="id" id="editId">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
    <label class="form-label">Day</label>
    <input type="number" class="form-control" name="day" id="editDay" 
           min="1" max="31" readonly disabled style="background-color: #f8f9fa; cursor: not-allowed;">
</div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="editStatus" required>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Amount (₹)</label>
                            <input type="number" class="form-control" name="amount" id="editAmount" 
                                   step="0.01" min="0" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Bonus (₹)</label>
                            <input type="number" class="form-control" name="bonus" id="editBonus" 
                                   step="0.01" min="0" required>
                        </div>
                    </div>
                </div>
                <div class="edit-modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" name="update" class="btn btn-success">Update Bonus</button>
                </div>
            </form>
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
    // Store rewards data for modal population
    const rewardsData = {
        <?php 
        mysqli_data_seek($rewardsResult, 0);
        while ($reward = mysqli_fetch_assoc($rewardsResult)): 
        ?>
            <?= $reward['id'] ?>: {
                day: <?= $reward['day'] ?>,
                amount: <?= $reward['amount'] ?>,
                bonus: <?= $reward['bonus'] ?>,
                status: '<?= $reward['status'] ?>'
            },
        <?php endwhile; ?>
    };

    function openEditModal(id) {
        const reward = rewardsData[id];
        if (reward) {
            document.getElementById('editId').value = id;
            document.getElementById('editDay').value = reward.day;
            document.getElementById('editAmount').value = reward.amount;
            document.getElementById('editBonus').value = reward.bonus;
            document.getElementById('editStatus').value = reward.status;
            
            document.getElementById('editModal').style.display = 'flex';
        }
    }

    function closeEditModal() {
        document.getElementById('editModal').style.display = 'none';
    }

    // Close modal when clicking outside
    document.getElementById('editModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeEditModal();
        }
    });

    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeEditModal();
        }
    });
    </script>

</body>
</html>