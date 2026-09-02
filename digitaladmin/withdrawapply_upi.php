<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

session_start();
if (empty($_SESSION['unohs'])) {
    header("location: api/login.php?msg=unauthorized");
}
include("api/conn.php");
require_once __DIR__ . "/api/manual-withdraw-helper.php";

// Date filter handling - no default date filtering
$date = isset($_GET['date']) ? $_GET['date'] : '';
$dateFilter = isset($_GET['date']) && !empty($_GET['date']) ? date('Y-m-d', strtotime($_GET['date'])) : '';

// Handle Accept/Reject atomically. Reject refunds the held amount exactly once.
if (isset($_POST['action'], $_POST['withdraw_id'])) {
    $withdraw_id = (int)encryptor('decrypt', (string)$_POST['withdraw_id']);
    $action = strtolower((string)$_POST['action']);
    $remark = trim((string)($_POST['remark'] ?? ''));
    [$ok, $message] = admin_manual_withdraw_action($conn, $withdraw_id, $action, $remark);
    $safeMessage = json_encode($message, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    if ($ok) {
        echo "<script>alert(" . $safeMessage . "); window.location.href = window.location.pathname;</script>";
    } else {
        echo "<script>alert(" . $safeMessage . ");</script>";
    }
}
?>

<!doctype html>
<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr" 
    data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template" data-style="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Withdraw Apply UPI</title>
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
    <link rel="stylesheet" href="assets/vendor/libs/sweetalert2/sweetalert2.css" />
    <script src="assets/vendor/js/helpers.js"></script>
    <script src="assets/vendor/js/template-customizer.js"></script>
    <script src="assets/js/config.js"></script>
    <style>
        .status-badge {
            font-size: 0.85em;
            padding: 0.35em 0.65em;
        }
        .action-buttons {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }
        .details-modal .modal-body {
            padding: 1.5rem;
        }
        .details-section {
            margin-bottom: 1.5rem;
            padding: 1rem;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
        }
        .details-section h6 {
            margin-bottom: 0.75rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #dee2e6;
        }
        .detail-row {
            display: flex;
            margin-bottom: 0.5rem;
        }
        .detail-label {
            font-weight: 600;
            min-width: 120px;
            color: #697a8d;
        }
        .detail-value {
            color: #566a7f;
        }
        .badge-upi {
            background-color: #6f42c1 !important;
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
                        <div class="card">
                            <div class="d-flex justify-content-between align-items-center px-4">
                                <h4 class="m-5">Withdraw Apply UPI</h4>
                                <form action="" method="get" class="d-flex gap-3 align-items-center">
                                    <input type="date" class="form-control" id="date" name="date" 
                                        value="<?= $date; ?>" max="<?= date('Y-m-d'); ?>">
                                    <button type="submit" class="btn btn-primary">Search</button>
                                    <?php if (!empty($date)): ?>
                                        <a href="?" class="btn btn-secondary">Clear Filter</a>
                                    <?php endif; ?>
                                </form>
                            </div>
                            <div class="table-responsive text-nowrap">
                                <?php
                                // Build query based on date filter - Only UPI (madari = 2) and Pending (sthiti = 0)
                                $query = "SELECT h.*,  
                                    s.`mobile` AS user_mobile,
                                    h.`balakedara` AS user_id
                                    FROM `hintegedukolli` h 
                                    LEFT JOIN `shonu_subjects` s ON s.`id` = h.`balakedara`
                                    WHERE h.`sthiti` = '0' 
                                    AND h.`madari` = '2'";
                                
                                // Add date filter only if date is selected
                                if (!empty($dateFilter)) {
                                    $query .= " AND DATE(h.`dinankavannuracisi`) = '$dateFilter'";
                                }
                                
                                $query .= " ORDER BY h.`shonu` DESC";
                                
                                $Query = mysqli_query($conn, $query);
                                $totalRecords = mysqli_num_rows($Query);
                                ?>

                                <?php if ($totalRecords > 0): ?>
                                <table class="datatables-withdrawapply-upi table">
                                    <thead>
                                        <tr>
                                            <th>Sr. No</th>
                                            <th>User ID</th>
                                            <th>Mobile</th>
                                            <th>Amount</th>
                                            <th>Payment Type</th>
                                            <th>Order ID</th>
                                            <th>Request Date</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                   <tbody class="table-border-bottom-0">
                                    <?php
                                        $i = 0;
                                        $total = 0;
                                        while ($row = mysqli_fetch_array($Query)) {
                                            $i++;
                                            $total += $row['motta'];
                                            $encrypted_id = encryptor('encrypt', $row['shonu']);
                                    
                                            // Fetch user wallet balance from shonu_kaichila
                                            $userWalletQuery = mysqli_query($conn, "SELECT `motta` FROM `shonu_kaichila` WHERE `balakedara` = '{$row['balakedara']}'");
                                            $walletData = mysqli_fetch_assoc($userWalletQuery);
                                            $walletBalance = $walletData ? $walletData['motta'] : 0;
                                            
                                            // Fetch UPI details from upi_address table
                                            $upiQuery = mysqli_query($conn, "SELECT `phalanubhavi`, `khatesankhye` FROM `upi_address` WHERE `byabaharkarta` = '{$row['balakedara']}' LIMIT 1");
                                            $upiData = mysqli_fetch_assoc($upiQuery);
                                            
                                            // Status labels
                                            $statusLabels = [
                                                '0' => ['label' => 'Pending', 'class' => 'bg-label-warning'],
                                                '1' => ['label' => 'Accepted', 'class' => 'bg-label-success'],
                                                '2' => ['label' => 'Rejected', 'class' => 'bg-label-danger']
                                            ];
                                            
                                            $currentStatus = $statusLabels[$row['sthiti']];
                                            ?>
                                            <tr>
                                                <td><span class="fw-medium"><?= $i; ?></span></td>
                                                <td><?= htmlspecialchars($row['user_id'] ?? 'N/A'); ?></td>
                                                <td><?= htmlspecialchars($row['user_mobile']); ?></td>
                                                <td>₹<?= number_format($row['motta'], 2); ?></td>
                                                <td><span class="badge rounded-pill badge-upi me-1">UPI</span></td>
                                                <td><?= htmlspecialchars($row["dharavahi"]); ?></td>
                                                <td><?= date('d-m-Y H:i:s', strtotime($row['dinankavannuracisi'])); ?></td>
                                                <td>
                                                    <span class="badge rounded-pill status-badge <?= $currentStatus['class']; ?>">
                                                        <?= $currentStatus['label']; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <!-- View Button -->
                                                        <button type="button" class="btn btn-info btn-sm view-details" 
                                                            data-id="<?= $encrypted_id; ?>"
                                                            data-userid="<?= $row['user_id']; ?>"
                                                            data-mobile="<?= htmlspecialchars($row['user_mobile']); ?>"
                                                            data-wallet="<?= number_format($walletBalance, 2); ?>"
                                                            data-name="<?= htmlspecialchars($upiData['phalanubhavi'] ?? 'N/A'); ?>"
                                                            data-upi="<?= htmlspecialchars($upiData['khatesankhye'] ?? 'N/A'); ?>"
                                                            data-date="<?= date('d-m-Y H:i:s', strtotime($row['dinankavannuracisi'])); ?>"
                                                            data-amount="<?= number_format($row['motta'], 2); ?>">
                                                            <i class="ri-eye-line"></i> View
                                                        </button>
                                                        
                                                        <?php if ($row['sthiti'] == '0'): ?>
                                                            <!-- Accept Button -->
                                                            <button type="button" class="btn btn-success btn-sm accept-btn" 
                                                                data-id="<?= $encrypted_id; ?>"
                                                                data-amount="<?= $row['motta']; ?>">
                                                                <i class="ri-check-line"></i> Accept
                                                            </button>
                                                            
                                                            <!-- Reject Button -->
                                                            <button type="button" class="btn btn-danger btn-sm reject-btn" 
                                                                data-id="<?= $encrypted_id; ?>"
                                                                data-amount="<?= $row['motta']; ?>">
                                                                <i class="ri-close-line"></i> Reject
                                                            </button>
                                                        <?php else: ?>
                                                            <span class="text-muted">Action Completed</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                            <td><strong>₹<?= number_format($total, 2); ?></strong></td>
                                            <td colspan="5"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                                <?php else: ?>
                                    <div class="text-center py-5">
                                        <div class="text-muted">
                                            <i class="ri-inbox-line display-4"></i>
                                            <p class="mt-3 mb-0">No UPI withdrawal requests found</p>
                                            <?php if (!empty($dateFilter)): ?>
                                                <p>for the selected date: <?= date('d-m-Y', strtotime($dateFilter)); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
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
    
    <!-- View Details Modal -->
    <div class="modal fade details-modal" id="viewDetailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">UPI Withdrawal Request Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="details-section">
                        <h6>Withdrawal User Details</h6>
                        <div class="detail-row">
                            <span class="detail-label">User ID:</span>
                            <span class="detail-value" id="detail-userid"></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Mobile:</span>
                            <span class="detail-value" id="detail-mobile"></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Wallet Balance:</span>
                            <span class="detail-value">₹<span id="detail-wallet"></span></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Withdrawal Amount:</span>
                            <span class="detail-value">₹<span id="detail-amount"></span></span>
                        </div>
                    </div>
                    
                    <div class="details-section">
                        <h6>UPI Details</h6>
                        <div class="detail-row">
                            <span class="detail-label">Name:</span>
                            <span class="detail-value" id="detail-name"></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">UPI Address:</span>
                            <span class="detail-value" id="detail-upi"></span>
                        </div>
                    </div>
                    
                    <div class="details-section">
                        <h6>Transaction Details</h6>
                        <div class="detail-row">
                            <span class="detail-label">Request Date:</span>
                            <span class="detail-value" id="detail-date"></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Payment Type:</span>
                            <span class="detail-value">UPI Transfer</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Status:</span>
                            <span class="badge bg-label-warning">Pending</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Reject Modal -->
    <div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="rejectForm" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Reject UPI Withdrawal Request</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="withdraw_id" id="reject_withdraw_id">
                        <input type="hidden" name="action" value="reject">
                        <div class="mb-3">
                            <p>Are you sure you want to reject this UPI withdrawal request of <strong>₹<span id="reject_amount"></span></strong>?</p>
                        </div>
                        <div class="mb-3">
                            <label for="remark" class="form-label">Remark (Optional)</label>
                            <textarea class="form-control" id="remark" name="remark" rows="3" 
                                      placeholder="Enter reason for rejection"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Reject Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Accept Modal -->
    <div class="modal fade" id="acceptModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="acceptForm" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Accept UPI Withdrawal Request</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="withdraw_id" id="accept_withdraw_id">
                        <input type="hidden" name="action" value="accept">
                        <p>Are you sure you want to accept this UPI withdrawal request of <strong>₹<span id="accept_amount"></span></strong>?</p>
                        <p class="text-muted">This action will mark the request as approved and process the payment.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Accept Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
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
    <script src="assets/vendor/libs/sweetalert2/sweetalert2.js"></script>
    <script src="assets/js/main.js"></script>
    
    <script>
        // View Details Modal
        $(document).on('click', '.view-details', function() {
            $('#detail-userid').text($(this).data('userid'));
            $('#detail-mobile').text($(this).data('mobile'));
            $('#detail-wallet').text($(this).data('wallet'));
            $('#detail-name').text($(this).data('name'));
            $('#detail-upi').text($(this).data('upi'));
            $('#detail-date').text($(this).data('date'));
            $('#detail-amount').text($(this).data('amount'));
            
            $('#viewDetailsModal').modal('show');
        });
        
        // Accept Button Click
        $(document).on('click', '.accept-btn', function() {
            const withdrawId = $(this).data('id');
            const amount = $(this).data('amount');
            
            $('#accept_withdraw_id').val(withdrawId);
            $('#accept_amount').text(amount);
            $('#acceptModal').modal('show');
        });
        
        // Reject Button Click
        $(document).on('click', '.reject-btn', function() {
            const withdrawId = $(this).data('id');
            const amount = $(this).data('amount');
            
            $('#reject_withdraw_id').val(withdrawId);
            $('#reject_amount').text(amount);
            $('#rejectModal').modal('show');
        });
        
        // Form submission handling
        $('#acceptForm, #rejectForm').on('submit', function(e) {
            const form = $(this);
            const action = form.find('input[name="action"]').val();
            const amount = form.find('span').text();
            const confirmMessage = action === 'accept' 
                ? `Are you sure you want to accept this UPI withdrawal request of ₹${amount}?`
                : `Are you sure you want to reject this UPI withdrawal request of ₹${amount}?`;
            
            if (!confirm(confirmMessage)) {
                e.preventDefault();
                return false;
            }
            return true;
        });
        
        <?php if ($totalRecords > 0): ?>
        $(function () {
            $('.datatables-withdrawapply-upi').DataTable({
                "paging": true,
                "lengthChange": true,
                "searching": true,
                "ordering": true,
                "info": true,
                "autoWidth": true,
                "pageLength": 50,
                "dom": '<"row"' +
                    '<"col-md-12"<"d-flex align-items-center justify-content-md-end justify-content-center"<l><"me-4"f>>>' +
                    '>t' +
                    '<"row p-5"' +
                    '<"col-sm-12 col-md-6"i>' +
                    '<"col-sm-12 col-md-6"p>' +
                    '>',
                "language": {
                    sLengthMenu: 'Show _MENU_',
                    search: '',
                    searchPlaceholder: 'Search by Mobile, Order ID...',
                    paginate: {
                        next: '<i class="ri-arrow-right-s-line"></i>',
                        previous: '<i class="ri-arrow-left-s-line"></i>'
                    }
                },
                "order": [[0, "asc"]],
                "columnDefs": [
                    { "orderable": false, "targets": [8] } // Make Actions column non-orderable
                ]
            });
        });
        <?php endif; ?>
    </script>
</body>
</html>