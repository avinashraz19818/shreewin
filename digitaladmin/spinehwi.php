<?php
session_start();
if (empty($_SESSION['unohs'])) {
    header("location: api/login.php?msg=unauthorized");
}
?>
<?php
include("api/conn.php");

date_default_timezone_set('Asia/Kolkata');
$now = date("Y-m-d H:i:s");

// // ================= AUTH =================
// $authorization = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
// $bearer = explode(" ", $authorization);
// $token = $bearer[1] ?? null;

// if (!$token) {
//     die("Unauthorized: Missing token");
// }

// $jwtData = json_decode(is_jwt_valid($token), true);
// if (!isset($jwtData['status']) || $jwtData['status'] !== 'Success') {
// //     die("Unauthorized: Invalid token");
// }

// ================= ACTION HANDLER =================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['orderNo'], $_POST['action'])) {
    $orderNo = $_POST['orderNo'];
    $action = $_POST['action'];
    $reason = $_POST['reason'] ?? '';

    $stmt = $conn->prepare("SELECT id, user_id, withdraw_amount, audit_state FROM invite_wheel_withdraw_log WHERE order_no = ?");
    $stmt->bind_param("s", $orderNo);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();

    if ($row && $row['audit_state'] == 0) {
        $userId = (int)$row['user_id'];
        $amount = floatval($row['withdraw_amount']);

        $conn->begin_transaction();
        try {
            if ($action === 'approve') {
                $stmt = $conn->prepare("UPDATE shonu_kaichila SET motta  = motta  + ? WHERE balakedara = ?");
                $stmt->bind_param("di", $amount, $userId);
                $stmt->execute();
                $stmt->close();

                $stmt = $conn->prepare("UPDATE invite_wheel_withdraw_log SET audit_state = 2, reason = '', updated_at = ? WHERE id = ?");
                $stmt->bind_param("si", $now, $row['id']);
                $stmt->execute();
                $stmt->close();

                $msg = "Withdrawal approved successfully!";
            } elseif ($action === 'reject') {
                $stmt = $conn->prepare("UPDATE user_invite_wheel SET total_winning = total_winning + ? WHERE user_id = ?");
                $stmt->bind_param("di", $amount, $userId);
                $stmt->execute();
                $stmt->close();

                $stmt = $conn->prepare("UPDATE invite_wheel_withdraw_log SET audit_state = 3, reason = ?, updated_at = ? WHERE id = ?");
                $stmt->bind_param("ssi", $reason, $now, $row['id']);
                $stmt->execute();
                $stmt->close();

                $msg = "Withdrawal rejected and amount returned!";
            }

            $conn->commit();
        } catch (Exception $e) {
            $conn->rollback();
            $msg = "Error: ".$e->getMessage();
        }
    } else {
        $msg = "Invalid or already processed request!";
    }
}

// ================= FETCH PENDING REQUESTS =================
$pending = $conn->query("SELECT order_no, user_id, withdraw_amount, create_time FROM invite_wheel_withdraw_log WHERE audit_state = 0 ORDER BY create_time ASC");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Withdraw Requests</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-4">
    <h1 class="mb-4">Pending Withdrawals</h1>

    <?php if (!empty($msg)): ?>
        <div class="alert alert-info"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <table class="table table-hover table-bordered align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Order No</th>
                        <th>User ID</th>
                        <th>Amount</th>
                        <th>Requested At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php while($req = $pending->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($req['order_no']) ?></td>
                        <td><?= htmlspecialchars($req['user_id']) ?></td>
                        <td>₹<?= number_format($req['withdraw_amount'], 2) ?></td>
                        <td><?= htmlspecialchars($req['create_time']) ?></td>
                        <td>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="orderNo" value="<?= htmlspecialchars($req['order_no']) ?>">
                                <input type="hidden" name="action" value="approve">
                                <button type="submit" class="btn btn-success btn-sm">Approve</button>
                            </form>

                            <button class="btn btn-danger btn-sm" 
                                data-bs-toggle="modal" 
                                data-bs-target="#rejectModal" 
                                data-order="<?= htmlspecialchars($req['order_no']) ?>">
                                Reject
                            </button>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title" id="rejectModalLabel">Reject Withdrawal</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <input type="hidden" name="orderNo" id="rejectOrderNo">
            <input type="hidden" name="action" value="reject">
            <div class="mb-3">
                <label class="form-label">Reason</label>
                <textarea name="reason" class="form-control" required></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-danger">Reject</button>
        </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('rejectModal').addEventListener('show.bs.modal', function (event) {
    var button = event.relatedTarget;
    var orderNo = button.getAttribute('data-order');
    document.getElementById('rejectOrderNo').value = orderNo;
});
</script>

</body>
</html>
