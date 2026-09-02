<?php
session_start();
if (empty($_SESSION['unohs'])) {
    header("location: api/login.php?msg=unauthorized");
    exit;
}

include("api/conn.php");

$success_message = "";

// Show success message
if (isset($_GET['success'])) {
    $success_message = $_GET['success'];
}

// Update spin_prizevalue only
if (isset($_POST['update_spin_prize'])) {
    $spin_prizevalue = mysqli_real_escape_string($conn, $_POST['spin_prizevalue']);
    mysqli_query($conn, "UPDATE web_setting SET spin_prizevalue='$spin_prizevalue' WHERE id=1");
    header("Location: ?success=Spin Prize Value Updated");
    exit;
}

// Handle Add/Edit/Delete for rewards
if (isset($_POST['add_or_edit_reward'])) {
    $id = mysqli_real_escape_string($conn, $_POST['id']);
    $target_amount = mysqli_real_escape_string($conn, $_POST['target_amount']);
    $rotate_num = mysqli_real_escape_string($conn, $_POST['rotate_num']);
    $reward_type = mysqli_real_escape_string($conn, $_POST['reward_type']);
    $reward_setting = mysqli_real_escape_string($conn, $_POST['reward_setting']);
    $prize_picture_url = mysqli_real_escape_string($conn, $_POST['prize_picture_url']);

    if ($id == 0) {
        mysqli_query($conn, "INSERT INTO recharge_spin_rewards (target_amount, rotate_num, reward_type, reward_setting, prize_picture_url) VALUES ('$target_amount','$rotate_num','$reward_type','$reward_setting','$prize_picture_url')");
    } else {
        mysqli_query($conn, "UPDATE recharge_spin_rewards SET target_amount='$target_amount', rotate_num='$rotate_num', reward_type='$reward_type', reward_setting='$reward_setting', prize_picture_url='$prize_picture_url' WHERE id='$id'");
    }
    header("Location: ?success=Reward Saved");
    exit;
}

if (isset($_GET['delete'])) {
    $id = mysqli_real_escape_string($conn, $_GET['delete']);
    mysqli_query($conn, "DELETE FROM recharge_spin_rewards WHERE id='$id'");
    header("Location: ?success=Reward Deleted");
    exit;
}

$webSetting = mysqli_fetch_assoc(mysqli_query($conn, "SELECT spin_prizevalue FROM web_setting LIMIT 1"));
$rewardResult = mysqli_query($conn, "SELECT * FROM recharge_spin_rewards ORDER BY id DESC");
$editReward = ["id"=>0, "target_amount"=>"", "rotate_num"=>"", "reward_type"=>"", "reward_setting"=>"", "prize_picture_url"=>""];

if (isset($_GET['edit'])) {
    $editId = mysqli_real_escape_string($conn, $_GET['edit']);
    $editReward = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM recharge_spin_rewards WHERE id='$editId'"));
}
?>

<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr"
    data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template" data-style="light">

<head>
<meta charset="utf-8" />
<meta name="viewport"
    content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    
<title>Manage Turn Table</title>
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
.reward-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    margin-bottom: 12px;
    /*background: #fff;*/
}
.reward-info {
    flex: 1;
}
.reward-actions {
    display: flex;
    gap: 10px;
}
.reward-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
}
.badge-cash {
    background: #d4edda;
    color: #155724;
}
.badge-bonus {
    background: #d1ecf1;
    color: #0c5460;
}
.badge-other {
    background: #e2e3e5;
    color: #383d41;
}
.prize-image {
    width: 40px;
    height: 40px;
    object-fit: cover;
    border-radius: 4px;
    margin-right: 10px;
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

                    <h4 class="mb-4">Manage Turn Table</h4>

                    <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($success_message) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Spin Prize Value Setting -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Spin Prize Value Setting</h5>
                        </div>
                        <div class="card-body">
                            <form method="post" action="">
                                <div class="row">
                                    <div class="col-md-8">
                                        <label for="spin_prizevalue" class="form-label">Spin Prize Value</label>
                                        <input type="number" class="form-control" name="spin_prizevalue" id="spin_prizevalue" 
                                               value="<?= htmlspecialchars($webSetting['spin_prizevalue'] ?? '') ?>" 
                                               step="0.01" min="0" required>
                                    </div>
                                    <div class="col-md-4 d-flex align-items-end">
                                        <button type="submit" name="update_spin_prize" class="btn btn-primary w-100">
                                            Update Prize Value
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Add/Edit Reward Form -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <?= $editReward['id'] == 0 ? 'Add New Reward' : 'Edit Reward' ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="post" action="">
                                <input type="hidden" name="id" value="<?= $editReward['id'] ?>">
                                
                                <div class="row">
                                    <div class="col-md-3">
                                        <label class="form-label">Target Amount</label>
                                        <input type="number" class="form-control" name="target_amount" 
                                               value="<?= htmlspecialchars($editReward['target_amount']) ?>" 
                                               step="0.01" min="0" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Rotate Number</label>
                                        <input type="number" class="form-control" name="rotate_num" 
                                               value="<?= htmlspecialchars($editReward['rotate_num']) ?>" 
                                               min="1" max="12" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Reward Type</label>
                                        <select class="form-select" name="reward_type" required>
                                            <option value="cash" <?= $editReward['reward_type'] == 'cash' ? 'selected' : '' ?>>Cash</option>
                                            <option value="bonus" <?= $editReward['reward_type'] == 'bonus' ? 'selected' : '' ?>>Bonus</option>
                                            <option value="other" <?= $editReward['reward_type'] == 'other' ? 'selected' : '' ?>>Other</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Reward Setting</label>
                                        <input type="text" class="form-control" name="reward_setting" 
                                               value="<?= htmlspecialchars($editReward['reward_setting']) ?>" 
                                               placeholder="Reward description" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Prize Image URL</label>
                                        <input type="url" class="form-control" name="prize_picture_url" 
                                               value="<?= htmlspecialchars($editReward['prize_picture_url']) ?>" 
                                               placeholder="Image URL">
                                    </div>
                                </div>
                                
                                <div class="mt-3">
                                    <button type="submit" name="add_or_edit_reward" class="btn btn-success">
                                        <?= $editReward['id'] == 0 ? 'Add Reward' : 'Update Reward' ?>
                                    </button>
                                    <?php if ($editReward['id'] != 0): ?>
                                        <a href="?" class="btn btn-secondary">Cancel Edit</a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Rewards List -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Turn Table Rewards</h5>
                        </div>
                        <div class="card-body">
                            <?php 
                            $rewardsCount = mysqli_num_rows($rewardResult);
                            if ($rewardsCount > 0): 
                            ?>
                                <?php while ($reward = mysqli_fetch_assoc($rewardResult)): ?>
                                    <div class="reward-row">
                                        <div class="reward-info">
                                            <div class="d-flex align-items-center mb-2">
                                                <?php if (!empty($reward['prize_picture_url'])): ?>
                                                    <img src="<?= htmlspecialchars($reward['prize_picture_url']) ?>" 
                                                         alt="Prize" class="prize-image" 
                                                         onerror="this.style.display='none'">
                                                <?php endif; ?>
                                                <h6 class="mb-0 me-3"><?= htmlspecialchars($reward['reward_setting']) ?></h6>
                                                <span class="reward-badge badge-<?= $reward['reward_type'] ?>">
                                                    <?= strtoupper($reward['reward_type']) ?>
                                                </span>
                                            </div>
                                            <p class="mb-1 text-muted">
                                                <strong>Target Amount:</strong> ₹<?= number_format($reward['target_amount'], 2) ?> | 
                                                <strong>Rotate Number:</strong> <?= $reward['rotate_num'] ?>
                                            </p>
                                            <p class="mb-0 text-muted small">
                                                <strong>ID:</strong> <?= $reward['id'] ?>
                                            </p>
                                        </div>
                                        <div class="reward-actions">
                                            <a href="?edit=<?= $reward['id'] ?>" class="btn btn-sm btn-warning">
                                                <i class="ri-edit-line me-1"></i> Edit
                                            </a>
                                            <a href="?delete=<?= $reward['id'] ?>" class="btn btn-sm btn-danger"
                                               onclick="return confirm('Are you sure you want to delete this reward?')">
                                                <i class="ri-delete-bin-line me-1"></i> Delete
                                            </a>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <p class="text-muted">No rewards found. Add your first reward above.</p>
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