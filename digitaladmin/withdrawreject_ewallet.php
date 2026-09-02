<?php
ini_set('display_errors', 0);                     
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1);                         
ini_set('error_log', __DIR__ . '/new_error.log'); 
error_reporting(E_ALL);   

session_start();
if (empty($_SESSION['unohs'])) {
    header("location: api/login.php?msg=unauthorized");
}
include("api/conn.php");

// Date filter handling - NO DEFAULT DATE FILTERING
$date = isset($_GET['date']) ? $_GET['date'] : '';
$dateFilter = isset($_GET['date']) && !empty($_GET['date']) ? date('Y-m-d', strtotime($_GET['date'])) : '';

// Get wallet type names
$walletTypes = [
    '23' => 'bkash',
    '24' => 'nagad',
    '9' => 'Nagad',
    '10' => 'BiKash'
];
?>

<!doctype html>
<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr"
    data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template" data-style="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Rejected E-Wallet Withdrawals</title>
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
        .badge-easy {
            background-color: #6f42c1 !important;
        }
        .badge-jazz {
            background-color: #fd7e14 !important;
        }
        .badge-nagad {
            background-color: #20c997 !important;
        }
        .badge-bikash {
            background-color: #e83e8c !important;
        }
        .badge-ewallet {
            background-color: #17a2b8 !important;
        }
        .badge-rejected {
            background-color: #dc3545 !important;
        }
        .text-rejected {
            color: #dc3545;
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
                                <h4 class="m-5">Rejected E-Wallet Withdrawals</h4>
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
                                // Build query based on date filter - ONLY E-WALLET (madari = 4) and Rejected (sthiti = 2)
                                $query = "SELECT h.*, 
                                    (SELECT `mobile` FROM `shonu_subjects` WHERE `id` = h.`balakedara`) AS user_mobile, 
                                    h.`balakedara` AS subject_id 
                                    FROM `hintegedukolli` h 
                                    WHERE h.`sthiti` = '2' 
                                    AND h.`madari` = '4'";
                                
                                // Add date filter only if date is selected
                                if (!empty($dateFilter)) {
                                    $query .= " AND DATE(h.`dinankavannuracisi`) = '$dateFilter'";
                                }
                                
                                $query .= " ORDER BY h.`shonu` DESC";
                                
                                $Query = mysqli_query($conn, $query);
                                $totalRecords = mysqli_num_rows($Query);
                                ?>

                                <?php if ($totalRecords > 0): ?>
                                <table class="datatables-withdrawreject table">
                                    <thead>
                                        <tr>
                                            <th>Sr. No</th>
                                            <th>User ID</th>
                                            <th>Mobile</th>
                                            <th>Amount</th>
                                            <th>Order ID</th>
                                            <th>Payment Type</th>
                                            <th>Req. Date</th>
                                            <th>Rejected On</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="table-border-bottom-0">
                                        <?php
                                        $i = 0;
                                        $total = 0;
                                        while ($row = mysqli_fetch_array($Query)) {
                                            $i++;
                                            $total += $row['motta'];
                                            
                                            // Fetch user wallet balance
                                            $userWalletQuery = mysqli_query($conn, "SELECT `motta` FROM `shonu_kaichila` WHERE `balakedara` = '{$row['balakedara']}'");
                                            $walletData = mysqli_fetch_assoc($userWalletQuery);
                                            $walletBalance = $walletData ? $walletData['motta'] : 0;
                                            
                                            // Fetch E-Wallet details
                                            $ewalletQuery = mysqli_query($conn, "SELECT `account`, `name`, `type` FROM `bankcard` WHERE `userid` = '{$row['balakedara']}' LIMIT 1");
                                            $ewalletData = mysqli_fetch_assoc($ewalletQuery);
                                            
                                            // Determine wallet type badge
                                            $walletType = $ewalletData['type'] ?? '';
                                            $walletName = $walletTypes[$walletType] ?? 'E-Wallet';
                                            $badgeClass = '';
                                            
                                            switch($walletType) {
                                                case '23': $badgeClass = 'badge-easy'; break;
                                                case '24': $badgeClass = 'badge-jazz'; break;
                                                case '9': $badgeClass = 'badge-nagad'; break;
                                                case '10': $badgeClass = 'badge-bikash'; break;
                                                default: $badgeClass = 'badge-ewallet';
                                            }
                                            
                                            // Format dates
                                            $requestDate = date('d-m-Y H:i:s', strtotime($row['dinankavannuracisi']));
                                            $rejectedDate = !empty($row['updated_at']) ? date('d-m-Y H:i:s', strtotime($row['updated_at'])) : 'N/A';
                                            
                                            // Get rejection reason if available
                                            $rejectionReason = !empty($row['remarks']) ? $row['remarks'] : 'Not specified';
                                            ?>
                                            <tr>
                                                <td><span class="fw-medium"><?= $i; ?></span></td>
                                                <td><?= htmlspecialchars($row['subject_id']); ?></td>
                                                <td><?= htmlspecialchars($row['user_mobile']); ?></td>
                                                <td>₹<?= number_format($row['motta'], 2); ?></td>
                                                <td><?= htmlspecialchars($row["dharavahi"]); ?></td>
                                                <td>
                                                    <span class="badge rounded-pill <?= $badgeClass; ?> me-1">
                                                        <?= $walletName; ?>
                                                    </span>
                                                </td>
                                                <td><?= $requestDate; ?></td>
                                                <td><?= $rejectedDate; ?></td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <!-- View Button -->
                                                        <button type="button" class="btn btn-info btn-sm view-details" 
                                                            data-userid="<?= $row['subject_id']; ?>"
                                                            data-mobile="<?= htmlspecialchars($row['user_mobile']); ?>"
                                                            data-wallet="<?= number_format($walletBalance, 2); ?>"
                                                            data-name="<?= htmlspecialchars($ewalletData['name'] ?? 'N/A'); ?>"
                                                            data-account="<?= htmlspecialchars($ewalletData['account'] ?? 'N/A'); ?>"
                                                            data-wallettype="<?= $walletName; ?>"
                                                            data-date="<?= $requestDate; ?>"
                                                            data-rejected="<?= $rejectedDate; ?>"
                                                            data-amount="<?= number_format($row['motta'], 2); ?>"
                                                            data-reason="<?= htmlspecialchars($rejectionReason); ?>">
                                                            <i class="ri-eye-line"></i> View
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="3" class="text-end"><strong>Total Rejected Amount:</strong></td>
                                            <td><strong class="text-rejected">₹<?= number_format($total, 2); ?></strong></td>
                                            <td colspan="5"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                                <?php else: ?>
                                    <div class="text-center py-5">
                                        <div class="text-muted">
                                            <i class="ri-inbox-line display-4"></i>
                                            <p class="mt-3 mb-0">No rejected E-Wallet withdrawals found</p>
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
                    <h5 class="modal-title">Rejected E-Wallet Withdrawal Details</h5>
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
                        <h6>E-Wallet Details</h6>
                        <div class="detail-row">
                            <span class="detail-label">Name:</span>
                            <span class="detail-value" id="detail-name"></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Account No:</span>
                            <span class="detail-value" id="detail-account"></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Wallet Name:</span>
                            <span class="detail-value" id="detail-wallettype"></span>
                        </div>
                    </div>
                    
                    <div class="details-section">
                        <h6>Transaction Details</h6>
                        <div class="detail-row">
                            <span class="detail-label">Request Date:</span>
                            <span class="detail-value" id="detail-date"></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Rejected On:</span>
                            <span class="detail-value" id="detail-rejected"></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Payment Type:</span>
                            <span class="detail-value">E-Wallet Transfer</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Status:</span>
                            <span class="badge badge-rejected">Rejected</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Rejection Reason:</span>
                            <span class="detail-value text-rejected" id="detail-reason"></span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
                </div>
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
    <script src="assets/js/main.js"></script>
    <script>
        // View Details Modal
        $(document).on('click', '.view-details', function() {
            $('#detail-userid').text($(this).data('userid'));
            $('#detail-mobile').text($(this).data('mobile'));
            $('#detail-wallet').text($(this).data('wallet'));
            $('#detail-name').text($(this).data('name'));
            $('#detail-account').text($(this).data('account'));
            $('#detail-wallettype').text($(this).data('wallettype'));
            $('#detail-date').text($(this).data('date'));
            $('#detail-rejected').text($(this).data('rejected'));
            $('#detail-amount').text($(this).data('amount'));
            $('#detail-reason').text($(this).data('reason'));
            
            $('#viewDetailsModal').modal('show');
        });
        
        <?php if ($totalRecords > 0): ?>
        $(function () {
            $('.datatables-withdrawreject').DataTable({
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
                    searchPlaceholder: 'Search by Mobile, User ID...',
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