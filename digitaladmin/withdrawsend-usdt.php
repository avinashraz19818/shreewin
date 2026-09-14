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

// Get USDT rate from tbl_pg where status = 1
$usdtRateQuery = mysqli_query($conn, "SELECT `rate` FROM `tbl_pg` WHERE `status` = '1' LIMIT 1");
$usdtRateData = mysqli_fetch_assoc($usdtRateQuery);
$usdtRate = $usdtRateData ? $usdtRateData['rate'] : 93; // Default 93 if not found

// Date filter handling - NO DEFAULT DATE FILTERING
$date = isset($_GET['date']) ? $_GET['date'] : '';
$dateFilter = isset($_GET['date']) && !empty($_GET['date']) ? date('Y-m-d', strtotime($_GET['date'])) : '';
?>

<!doctype html>
<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr"
    data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template" data-style="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Approved USDT Withdrawals</title>
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
        .badge-usdt {
            background-color: #28a745 !important;
        }
        .amount-container {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .amount-inr {
            font-weight: 600;
            color: #566a7f;
        }
        .amount-usdt {
            font-size: 0.85em;
            color: #28a745;
            font-weight: 500;
        }
        .rate-info {
            background-color: #f8f9fa;
            padding: 8px 12px;
            border-radius: 6px;
            margin-bottom: 15px;
            border: 1px solid #dee2e6;
        }
        .rate-info strong {
            color: #28a745;
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
                                <h4 class="m-5">Approved USDT Withdrawals</h4>
                                <div class="d-flex align-items-center gap-3">
                                    <?php if ($usdtRate > 0): ?>
                                    <div class="rate-info">
                                        <i class="ri-money-dollar-circle-line me-1"></i>
                                        Current USDT Rate: <strong>₹<?= number_format($usdtRate, 2); ?></strong>
                                    </div>
                                    <?php endif; ?>
                                    <form action="" method="get" class="d-flex gap-3 align-items-center">
                                        <input type="date" class="form-control" id="date" name="date"
                                            value="<?= $date; ?>" max="<?= date('Y-m-d'); ?>">
                                        <button type="submit" class="btn btn-primary">Search</button>
                                        <?php if (!empty($date)): ?>
                                            <a href="?" class="btn btn-secondary">Clear Filter</a>
                                        <?php endif; ?>
                                    </form>
                                </div>
                            </div>
                            <div class="table-responsive text-nowrap">
                                <?php
                                // Build query based on date filter - ONLY USDT (madari = 3) and Approved (sthiti = 1)
                                $query = "SELECT h.*, 
                                    (SELECT `mobile` FROM `shonu_subjects` WHERE `id` = h.`balakedara`) AS user_mobile, 
                                    h.`balakedara` AS subject_id 
                                    FROM `hintegedukolli` h 
                                    WHERE h.`sthiti` = '1' 
                                    AND h.`madari` = '3'";
                                
                                // Add date filter only if date is selected
                                if (!empty($dateFilter)) {
                                    $query .= " AND DATE(h.`dinankavannuracisi`) = '$dateFilter'";
                                }
                                
                                $query .= " ORDER BY h.`shonu` DESC";
                                
                                $Query = mysqli_query($conn, $query);
                                $totalRecords = mysqli_num_rows($Query);
                                
                                // Calculate totals
                                $totalINR = 0;
                                $totalUSDT = 0;
                                ?>

                                <?php if ($totalRecords > 0): ?>
                                <table class="datatables-withdrawsent table">
                                    <thead>
                                        <tr>
                                            <th>Sr. No</th>
                                            <th>User ID</th>
                                            <th>Mobile</th>
                                            <th>Amount</th>
                                            <th>Order ID</th>
                                            <th>Payment Type</th>
                                            <th>Req. Date</th>
                                            <th>Approved On</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="table-border-bottom-0">
                                        <?php
                                        $i = 0;
                                        while ($row = mysqli_fetch_array($Query)) {
                                            $i++;
                                            $inrAmount = $row['motta'];
                                            $usdtAmount = ($usdtRate > 0) ? ($inrAmount / $usdtRate) : 0;
                                            
                                            $totalINR += $inrAmount;
                                            $totalUSDT += $usdtAmount;
                                            
                                            // Fetch user wallet balance
                                            $userWalletQuery = mysqli_query($conn, "SELECT `motta` FROM `shonu_kaichila` WHERE `balakedara` = '{$row['balakedara']}'");
                                            $walletData = mysqli_fetch_assoc($userWalletQuery);
                                            $walletBalance = $walletData ? $walletData['motta'] : 0;
                                            
                                            // Fetch USDT wallet details
                                            $usdtQuery = mysqli_query($conn, "SELECT `account`, `name`, `wallet_name` FROM `usdt_waddress` WHERE `userid` = '{$row['balakedara']}' LIMIT 1");
                                            $usdtData = mysqli_fetch_assoc($usdtQuery);
                                            
                                            // Format dates
                                            $requestDate = date('d-m-Y H:i:s', strtotime($row['dinankavannuracisi']));
                                            $approvedDate = !empty($row['updated_at']) ? date('d-m-Y H:i:s', strtotime($row['updated_at'])) : 'N/A';
                                            ?>
                                            <tr>
                                                <td><span class="fw-medium"><?= $i; ?></span></td>
                                                <td><?= htmlspecialchars($row['subject_id']); ?></td>
                                                <td><?= htmlspecialchars($row['user_mobile']); ?></td>
                                                <td>
                                                    <div class="amount-container">
                                                        <span class="amount-inr">₹<?= number_format($inrAmount, 2); ?></span>
                                                        <?php if ($usdtRate > 0): ?>
                                                        <span class="amount-usdt">
                                                            ≈ <?= number_format($usdtAmount, 4); ?> USDT
                                                        </span>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td><?= htmlspecialchars($row["dharavahi"]); ?></td>
                                                <td><span class="badge rounded-pill badge-usdt me-1">USDT</span></td>
                                                <td><?= $requestDate; ?></td>
                                                <td><?= $approvedDate; ?></td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <!-- View Button -->
                                                        <button type="button" class="btn btn-info btn-sm view-details" 
                                                            data-userid="<?= $row['subject_id']; ?>"
                                                            data-mobile="<?= htmlspecialchars($row['user_mobile']); ?>"
                                                            data-wallet="<?= number_format($walletBalance, 2); ?>"
                                                            data-address="<?= htmlspecialchars($usdtData['account'] ?? 'N/A'); ?>"
                                                            data-alias="<?= htmlspecialchars($usdtData['name'] ?? 'N/A'); ?>"
                                                            data-wallettype="<?= htmlspecialchars($usdtData['wallet_name'] ?? 'N/A'); ?>"
                                                            data-date="<?= $requestDate; ?>"
                                                            data-approved="<?= $approvedDate; ?>"
                                                            data-amount="<?= number_format($inrAmount, 2); ?>"
                                                            data-usdtamount="<?= number_format($usdtAmount, 4); ?>"
                                                            data-usdtrate="<?= number_format($usdtRate, 2); ?>">
                                                            <i class="ri-eye-line"></i> View
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                            <td>
                                                <div class="amount-container">
                                                    <strong class="amount-inr">₹<?= number_format($totalINR, 2); ?></strong>
                                                    <?php if ($usdtRate > 0): ?>
                                                    <strong class="amount-usdt">
                                                        ≈ <?= number_format($totalUSDT, 4); ?> USDT
                                                    </strong>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td colspan="5"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                                <?php else: ?>
                                    <div class="text-center py-5">
                                        <div class="text-muted">
                                            <i class="ri-inbox-line display-4"></i>
                                            <p class="mt-3 mb-0">No approved USDT withdrawals found</p>
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
                    <h5 class="modal-title">USDT Withdrawal Details</h5>
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
                            <span class="detail-value">
                                ₹<span id="detail-amount"></span>
                                <br>
                                <small class="text-success">≈ <span id="detail-usdtamount"></span> USDT</small>
                            </span>
                        </div>
                    </div>
                    
                    <div class="details-section">
                        <h6>USDT Details</h6>
                        <div class="detail-row">
                            <span class="detail-label">USDT Address:</span>
                            <span class="detail-value" id="detail-address"></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Address Alias:</span>
                            <span class="detail-value" id="detail-alias"></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Wallet Type:</span>
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
                            <span class="detail-label">Approved On:</span>
                            <span class="detail-value" id="detail-approved"></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Payment Type:</span>
                            <span class="detail-value">USDT Transfer</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">USDT Rate:</span>
                            <span class="detail-value">₹<span id="detail-usdtrate"></span></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Status:</span>
                            <span class="badge bg-label-success">Approved</span>
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
            $('#detail-address').text($(this).data('address'));
            $('#detail-alias').text($(this).data('alias'));
            $('#detail-wallettype').text($(this).data('wallettype'));
            $('#detail-date').text($(this).data('date'));
            $('#detail-approved').text($(this).data('approved'));
            $('#detail-amount').text($(this).data('amount'));
            $('#detail-usdtamount').text($(this).data('usdtamount') + ' USDT');
            $('#detail-usdtrate').text($(this).data('usdtrate'));
            
            $('#viewDetailsModal').modal('show');
        });
        
        <?php if ($totalRecords > 0): ?>
        $(function () {
            $('.datatables-withdrawsent').DataTable({
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