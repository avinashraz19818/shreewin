<?php

session_start();
if (empty($_SESSION['unohs'])) {
    header("location: api/login.php?msg=unauthorized");
    exit();
}
include("api/conn.php");

$date = isset($_GET['date']) ? date('Y-m-d', strtotime($_GET['date'])) : '';

// Query for illegal_bet_banned table
if (isset($_GET['date']) && !empty($_GET['date'])) {
    // If date is selected, filter by date
    $dateFilter = date('Y-m-d', strtotime($_GET['date']));
    $sql = "SELECT * FROM illegal_bet_banned 
            WHERE DATE(datetime) = '$dateFilter' 
            ORDER BY datetime DESC";
} else {
    // Default: Show all data
    $sql = "SELECT * FROM illegal_bet_banned 
            ORDER BY datetime DESC";
}

$result = mysqli_query($conn, $sql);

// Check if we have data
$hasData = mysqli_num_rows($result) > 0;

?>
<!doctype html>
<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr"
    data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template" data-style="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Illegal Bet Banned</title>
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
                            <div class="table-responsive text-nowrap">
                                <div class="d-flex justify-content-between align-items-center px-4 pt-4">
                                    <div class="d-flex align-items-center">
                                        <h4 class="me-3">Illegal Bet Banned List</h4>
                                        <input type="number" class="form-control" id="uid" placeholder="Enter UID" style="width: 150px;" />
                                    </div>
                                    <form action="" method="get" class="d-flex gap-3 align-items-center">
                                        <input type="date" class="form-control" id="date" name="date"
                                            value="<?= $date; ?>" max="<?= date('Y-m-d'); ?>">
                                        <button type="submit" class="btn btn-primary">Search</button>
                                        <?php if (isset($_GET['date']) && !empty($_GET['date'])): ?>
                                            <a href="?" class="btn btn-secondary">Show All</a>
                                        <?php endif; ?>
                                    </form>
                                </div>
                                
                                <?php if ($hasData): ?>
                                <div class="p-4">
                                    <table class="table table-striped" id="example1">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>User ID</th> 
                                                <th>Period</th>
                                                <th>Game</th>
                                                <th>Date & Time</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            while ($row = mysqli_fetch_assoc($result)) {
                                                ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($row["id"]); ?></td>
                                                    <td><?= htmlspecialchars($row["user_id"]); ?></td>
                                                    <td><?= htmlspecialchars($row["period"]); ?></td>
                                                    <td><?= htmlspecialchars($row["game"]); ?></td>
                                                    <td><?= htmlspecialchars($row["datetime"]); ?></td>
                                                    <td>
                                                        <button type="button" class="btn btn-primary btn-sm view-details" 
                                                                data-userid="<?= $row['user_id']; ?>"
                                                                data-datetime="<?= $row['datetime']; ?>"
                                                                data-period="<?= $row['period']; ?>"
                                                                data-game="<?= $row['game']; ?>">
                                                            👁️ View Details
                                                        </button>
                                                    </td>
                                                </tr>
                                                <?php
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php else: ?>
                                    <div class="text-center p-4">
                                        <p class="text-muted">
                                            <?php if (isset($_GET['date']) && !empty($_GET['date'])): ?>
                                                No banned users found for selected date: <?= $date; ?>
                                            <?php else: ?>
                                                No banned users found
                                            <?php endif; ?>
                                        </p>
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

    <!-- Details Modal -->
    <div class="modal fade" id="detailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">User Bet Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>User ID:</strong> <span id="modalUserId"></span></p>
                            <p><strong>Period:</strong> <span id="modalPeriod"></span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Game:</strong> <span id="modalGame"></span></p>
                            <p><strong>Date & Time:</strong> <span id="modalDateTime"></span></p>
                        </div>
                    </div>
                    <hr>
                    <div id="betDetails">
                        <p class="text-center text-muted">Loading bet details...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

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
    <script src="assets/vendor/libs/datatables/jquery.dataTables.js"></script>
    <script src="assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js"></script>
    <script src="assets/vendor/libs/select2/select2.js"></script>
    <script src="assets/vendor/libs/@form-validation/popular.js"></script>
    <script src="assets/vendor/libs/@form-validation/bootstrap5.js"></script>
    <script src="assets/vendor/libs/@form-validation/auto-focus.js"></script>
    <script src="assets/vendor/libs/cleavejs/cleave.js"></script>
    <script src="assets/vendor/libs/cleavejs/cleave-phone.js"></script>
    <script src="assets/js/main.js"></script>
  
    <?php if ($hasData): ?>
    <script>
        $(document).ready(function () {
            var table = $('#example1').DataTable({
                "paging": true,
                "lengthChange": true,
                "searching": true,
                "ordering": true,
                "info": true,
                "autoWidth": false,
                "responsive": true,
                "pageLength": 50,
                "dom": '<"row"' + 
                       '<"col-md-6"l>' +
                       '<"col-md-6"f>' + 
                       '>t' + 
                       '<"row"' + 
                       '<"col-md-6"i>' + 
                       '<"col-md-6"p>' + 
                       '>',
                "language": {
                    "lengthMenu": "Show _MENU_ entries",
                    "search": "",
                    "searchPlaceholder": "Search...",
                    "paginate": {
                        "next": '<i class="ri-arrow-right-s-line"></i>',
                        "previous": '<i class="ri-arrow-left-s-line"></i>'
                    }
                }
            });
            
            // UID search functionality
            $('#uid').on('keyup', function () {
                var val = $(this).val();
                table.column(1).search(val).draw();
            });

            // View Details Modal
            $('.view-details').on('click', function() {
                var userId = $(this).data('userid');
                var datetime = $(this).data('datetime');
                var period = $(this).data('period');
                var game = $(this).data('game');
                
                // Set basic info in modal
                $('#modalUserId').text(userId);
                $('#modalPeriod').text(period);
                $('#modalGame').text(game);
                $('#modalDateTime').text(datetime);
                
                // Load bet details via AJAX
                loadBetDetails(userId, datetime);
                
                // Show modal
                $('#detailsModal').modal('show');
            });

            function loadBetDetails(userId, datetime) {
                $('#betDetails').html('<p class="text-center text-muted">Loading bet details...</p>');
                
                $.ajax({
                    url: 'api/get_bet_details.php',
                    type: 'GET',
                    data: {
                        userid: userId,
                        date: datetime
                    },
                    success: function(response) {
                        $('#betDetails').html(response);
                    },
                    error: function() {
                        $('#betDetails').html('<p class="text-center text-danger">Error loading details</p>');
                    }
                });
            }
        });
    </script>
    <?php endif; ?>
</body>

</html>