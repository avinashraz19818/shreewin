<?php
session_start();
if (!isset($_SESSION['unohs'])) {
    header("HTTP/1.1 401 Unauthorized");
    exit;
}
include("conn.php");

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

if ($user_id <= 0) {
    echo '<div class="alert alert-danger">Invalid User ID</div>';
    exit;
}

// Get user basic info
$user_sql = "SELECT * FROM shonu_turntable WHERE user_id = ?";
$user_stmt = mysqli_prepare($conn, $user_sql);
mysqli_stmt_bind_param($user_stmt, "i", $user_id);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);
$user = mysqli_fetch_assoc($user_result);
mysqli_stmt_close($user_stmt);

if (!$user) {
    echo '<div class="alert alert-danger">User not found</div>';
    exit;
}

// Get spin history count
$count_sql = "SELECT COUNT(*) as total FROM shonu_turntable_spins WHERE user_id = ?";
$count_stmt = mysqli_prepare($conn, $count_sql);
mysqli_stmt_bind_param($count_stmt, "i", $user_id);
mysqli_stmt_execute($count_stmt);
$count_result = mysqli_stmt_get_result($count_stmt);
$total_count = mysqli_fetch_assoc($count_result)['total'] ?? 0;
mysqli_stmt_close($count_stmt);

// Get spin history
$spin_sql = "SELECT * FROM shonu_turntable_spins WHERE user_id = ? ORDER BY spin_time DESC LIMIT $limit OFFSET $offset";
$spin_stmt = mysqli_prepare($conn, $spin_sql);
mysqli_stmt_bind_param($spin_stmt, "i", $user_id);
mysqli_stmt_execute($spin_stmt);
$spin_result = mysqli_stmt_get_result($spin_stmt);

$total_pages = ceil($total_count / $limit);

// Calculate total prize won
$total_prize_sql = "SELECT SUM(prize_amount) as total FROM shonu_turntable_spins WHERE user_id = ?";
$total_prize_stmt = mysqli_prepare($conn, $total_prize_sql);
mysqli_stmt_bind_param($total_prize_stmt, "i", $user_id);
mysqli_stmt_execute($total_prize_stmt);
$total_prize_result = mysqli_stmt_get_result($total_prize_stmt);
$total_prize = mysqli_fetch_assoc($total_prize_result)['total'] ?? 0;
mysqli_stmt_close($total_prize_stmt);

ob_start();
?>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <h6 class="card-title">Total Spins</h6>
                <h3 class="text-primary"><?= number_format($user['total_spins']) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <h6 class="card-title">Invited Wheel Amount</h6>
                <h3 class="text-success">₹<?= number_format($user['invited_wheel_amount'], 2) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <h6 class="card-title">Total Prize Won</h6>
                <h3 class="text-info">₹<?= number_format($total_prize, 2) ?></h3>
            </div>
        </div>
    </div>
</div>

<h6 class="mb-3">Spin History (<?= number_format($total_count) ?> records)</h6>

<div class="table-responsive">
    <table class="table table-sm spin-history-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Prize Amount</th>
                <th>Spin Time</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($spin = mysqli_fetch_assoc($spin_result)): ?>
                <tr>
                    <td><?= $spin['id'] ?></td>
                    <td>
                        <span class="badge bg-label-success">₹<?= number_format($spin['prize_amount'], 2) ?></span>
                    </td>
                    <td>
                        <small class="text-muted">
                            <?= date('d M Y H:i:s', strtotime($spin['spin_time'])) ?>
                        </small>
                    </td>
                </tr>
            <?php endwhile; ?>
            
            <?php if ($total_count === 0): ?>
                <tr>
                    <td colspan="3" class="text-center py-3 text-muted">
                        No spin history found for this user
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if ($total_pages > 1): ?>
    <nav aria-label="Spin history pagination" class="mt-3">
        <ul class="pagination justify-content-center user-spin-pagination">
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="#" data-page="<?= $page - 1 ?>">
                    <i class="ri-arrow-left-s-line"></i>
                </a>
            </li>
            
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                    <a class="page-link" href="#" data-page="<?= $i ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
            
            <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                <a class="page-link" href="#" data-page="<?= $page + 1 ?>">
                    <i class="ri-arrow-right-s-line"></i>
                </a>
            </li>
        </ul>
    </nav>
<?php endif; ?>

<?php
echo ob_get_clean();
mysqli_stmt_close($spin_stmt);
?>