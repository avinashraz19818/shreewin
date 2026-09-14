<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (empty($_SESSION['unohs'])) {
    header("location: api/login.php?msg=unauthorized");
}
include("api/conn.php");

// Handle AJAX request for spin history
if(isset($_POST['ajax_user_id'])) {
    $user_id = mysqli_real_escape_string($conn, $_POST['ajax_user_id']);
    
    $query = "SELECT * FROM `shonu_turntable_spins` WHERE `user_id` = '$user_id' ORDER BY `spin_time` DESC";
    $result = mysqli_query($conn, $query);
    
    $i = 0;
    if(mysqli_num_rows($result) > 0) {
        while($row = mysqli_fetch_assoc($result)) {
            $i++;
            echo '<tr>';
            echo '<td>' . $i . '</td>';
            echo '<td>' . htmlspecialchars($row['user_name']) . '</td>';
            echo '<td>₹' . number_format($row['prize_amount'], 2) . '</td>';
            echo '<td>' . date('d-m-Y H:i:s', strtotime($row['spin_time'])) . '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="4" class="text-center">No spin history found</td></tr>';
    }
    exit;
}
?>

<!doctype html>
<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr" 
    data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template" data-style="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Invite Wheel Withdraw Apply</title>
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
    
    <!-- Explicitly include DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
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
                                <h4 class="m-5">Invite Wheel Withdraw Apply</h4>
                                <form action="" method="get" class="d-flex gap-3 align-items-center">
                                    <input type="date" class="form-control" id="date" name="date">
                                    <!--<input type="text" class="form-control" id="user_id" name="user_id" placeholder="Search by User ID" value="<?= isset($_GET['user_id']) ? htmlspecialchars($_GET['user_id']) : '' ?>">-->
                                    <button type="submit" class="btn btn-primary">Search</button>
                                </form>
                            </div>
                            <div class="table-responsive text-nowrap">
                                <table class="table table-bordered" id="withdrawTable">
                                    <thead>
                                        <tr>
                                            <th>Sr. No</th>
                                            <th>User ID</th>
                                            <th>Amount</th>
                                            <th>Payment Type</th>
                                            <th>Status</th>
                                            <th>Request Date</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="table-border-bottom-0">
                                    <?php
                                        // Build query based on filters
                                        $whereConditions = ["`sthithi` = '1'"];
                                        
                                        if(isset($_GET['date']) && !empty($_GET['date'])) {
                                            $dateFilter = date('Y-m-d', strtotime($_GET['date']));
                                            $whereConditions[] = "DATE(`created_at`) = '$dateFilter'";
                                        }
                                        
                                        if(isset($_GET['user_id']) && !empty($_GET['user_id'])) {
                                            $user_id = mysqli_real_escape_string($conn, $_GET['user_id']);
                                            $whereConditions[] = "`user_id` = '$user_id'";
                                        }
                                        
                                        $whereClause = implode(" AND ", $whereConditions);
                                        $Query = mysqli_query($conn, "SELECT * FROM `mrcoder_withdrawals` WHERE $whereClause ORDER BY `created_at` DESC");
                                    
                                        $i = 0;
                                        $total = 0;
                                        
                                        if(mysqli_num_rows($Query) > 0) {
                                            while ($row = mysqli_fetch_array($Query)) {
                                                $i++;
                                                $total += $row['amount'];
                                        
                                                // Status - always pending since sthithi=0
                                                $statusBadge = '<span class="badge bg-success">Approved</span>';

                                                // Withdrawal type - since only turntable
                                                $withdrawalType = '<span class="badge bg-info">Turntable</span>';
                                                ?>
                                                <tr>
                                                    <td><?= $i; ?></td>
                                                    <td><?= htmlspecialchars($row['user_id']); ?></td>
                                                    <td>₹<?= number_format($row['amount'], 2); ?></td>
                                                    <td><?= $withdrawalType; ?></td>
                                                    <td><?= $statusBadge; ?></td>
                                                    <td><?= date('d-m-Y H:i:s', strtotime($row['created_at'])); ?></td>
                                                    <td>
                                                        <button class="btn btn-sm btn-primary view-details" 
                                                                data-user-id="<?= $row['user_id']; ?>"
                                                                data-amount="<?= $row['amount']; ?>"
                                                                data-request-date="<?= date('d-m-Y H:i:s', strtotime($row['created_at'])); ?>">
                                                            <i class="ri-eye-fill me-1"></i> View
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                        <?php } else { ?>
                                            <tr>
                                                <td colspan="7" class="text-center py-4">
                                                    <div class="text-muted">
                                                        <?php 
                                                        if(isset($_GET['date']) || isset($_GET['user_id'])) {
                                                            echo "No pending withdrawal requests found for selected filters.";
                                                        } else {
                                                            echo "No pending withdrawal requests found.";
                                                        }
                                                        ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
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

    <!-- Details Modal -->
    <div class="modal fade" id="detailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Withdrawal Details - User ID: <span id="modalTitleUserId"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Basic Details -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6>Basic Information</h6>
                            <table class="table table-bordered">
                                <tr>
                                    <th width="40%">User ID:</th>
                                    <td id="modalUserId"></td>
                                </tr>
                                <tr>
                                    <th>Amount:</th>
                                    <td id="modalAmount"></td>
                                </tr>
                                <tr>
                                    <th>Request Date:</th>
                                    <td id="modalRequestDate"></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Turntable Spins History -->
                    <div class="row">
                        <div class="col-12">
                            <h6>Turntable Spin History</h6>
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="10%">Sr. No</th>
                                            <th width="30%">User Name</th>
                                            <th width="25%">Prize Amount</th>
                                            <th width="35%">Spin Time</th>
                                        </tr>
                                    </thead>
                                    <tbody id="spinHistoryBody">
                                        <!-- Spin history will be loaded here via AJAX -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
    
    <!-- Explicitly include DataTables JS from CDN -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    
    <script src="assets/vendor/libs/select2/select2.js"></script>
    <script src="assets/vendor/libs/@form-validation/popular.js"></script>
    <script src="assets/vendor/libs/@form-validation/bootstrap5.js"></script>
    <script src="assets/vendor/libs/@form-validation/auto-focus.js"></script>
    <script src="assets/vendor/libs/cleavejs/cleave.js"></script>
    <script src="assets/vendor/libs/cleavejs/cleave-phone.js"></script>
    <script src="assets/js/main.js"></script>
    
    <script>
        $(document).ready(function() {
            console.log('Document ready - Initializing DataTable');
            
            // Initialize DataTable with pagination
            var table = $('#withdrawTable').DataTable({
                "paging": true,
                "pageLength": 10,
                "lengthChange": true,
                "searching": true,
                "ordering": true,
                "info": true,
                "autoWidth": false,
                "responsive": true,
                "lengthMenu": [10, 25, 50, 100],
                "language": {
                    "search": "Search:",
                    "lengthMenu": "Show _MENU_ entries",
                    "paginate": {
                        "next": "Next",
                        "previous": "Previous"
                    },
                    "info": "Showing _START_ to _END_ of _TOTAL_ entries"
                }
            });

            console.log('DataTable initialized:', table);

            // View Details Button Click - FIXED
            $(document).on('click', '.view-details', function() {
                var userId = $(this).data('user-id');
                var amount = $(this).data('amount');
                var requestDate = $(this).data('request-date');

                console.log('View button clicked for User ID:', userId);

                // Set basic details
                $('#modalTitleUserId').text(userId);
                $('#modalUserId').text(userId);
                $('#modalAmount').text('₹' + parseFloat(amount).toFixed(2));
                $('#modalRequestDate').text(requestDate);

                // Load spin history via AJAX
                loadSpinHistory(userId);

                // Show modal
                $('#detailsModal').modal('show');
            });

            // Function to load spin history
            function loadSpinHistory(userId) {
                console.log('Loading spin history for user:', userId);
                $('#spinHistoryBody').html('<tr><td colspan="4" class="text-center py-3">Loading spin history...</td></tr>');
                
                $.ajax({
                    url: window.location.href,
                    type: 'POST',
                    data: { 
                        ajax_user_id: userId 
                    },
                    success: function(response) {
                        console.log('Spin history loaded successfully');
                        $('#spinHistoryBody').html(response);
                    },
                    error: function(xhr, status, error) {
                        console.log('AJAX Error:', error);
                        $('#spinHistoryBody').html('<tr><td colspan="4" class="text-center text-danger py-3">Error loading spin history</td></tr>');
                    }
                });
            }

            // Reset modal when closed
            $('#detailsModal').on('hidden.bs.modal', function() {
                $('#spinHistoryBody').html('');
            });
        });
    </script>
</body>
</html>