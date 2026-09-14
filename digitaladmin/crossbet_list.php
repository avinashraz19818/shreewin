<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (empty($_SESSION['unohs'])) {
    header("location: api/login.php?msg=unauthorized");
    exit();
}
include("api/conn.php");

$date = isset($_GET['date']) ? $_GET['date'] : '';

// Modified SQL query to find cross bets by IP - NO DATE FILTER BY DEFAULT
$sql = "
    SELECT 
        GROUP_CONCAT(DISTINCT byabaharkarta) AS userids,
        ip,
        DATE(tiarikala) AS bet_date
    FROM (
        SELECT byabaharkarta, ip, tiarikala FROM bajikattuttate
        UNION ALL
        SELECT byabaharkarta, ip, tiarikala FROM bajikattuttate_zehn
        UNION ALL
        SELECT byabaharkarta, ip, tiarikala FROM bajikattuttate_drei
        UNION ALL
        SELECT byabaharkarta, ip, tiarikala FROM bajikattuttate_funf
    ) AS combined
    " . (!empty($date) ? "WHERE DATE(tiarikala) = '$date'" : "") . "
    GROUP BY ip, DATE(tiarikala)
    HAVING COUNT(DISTINCT byabaharkarta) > 1
    ORDER BY bet_date DESC, ip
";

$result = mysqli_query($conn, $sql);
$hasData = ($result && mysqli_num_rows($result) > 0);

?>
<!doctype html>
<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr"
    data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template" data-style="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Cross Bet Detection</title>
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
                                        <h4 class="me-3">Cross Bet Detection</h4>
                                        <input type="number" class="form-control" id="uid" placeholder="Enter UID" style="width: 150px;" />
                                    </div>
                                    <form action="" method="get" class="d-flex gap-3 align-items-center">
                                        <input type="date" class="form-control" id="date" name="date"
                                            value="<?= htmlspecialchars($date); ?>">
                                        <button type="submit" class="btn btn-primary">Search</button>
                                        <?php if (!empty($date)): ?>
                                            <a href="?" class="btn btn-secondary">Show All</a>
                                        <?php endif; ?>
                                    </form>
                                </div>
                                
                                <?php if ($hasData): ?>
                                <div class="p-4">
                                    <table class="table table-striped" id="example1">
                                        <thead>
                                            <tr>
                                                <th>User IDs</th>
                                                <th>IP Address</th>
                                                <th>Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            while ($row = mysqli_fetch_assoc($result)) {
                                                ?>
                                                <tr>
                                                    <td class="text-danger fw-bold"><?= str_replace(',', ', ', $row["userids"]) ?></td>
                                                    <td><?= htmlspecialchars($row["ip"]) ?></td>
                                                    <td><?= htmlspecialchars($row["bet_date"]) ?></td>
                                                    <td>
                                                        <button class="btn btn-sm btn-warning view-details" 
                                                            data-ip="<?= htmlspecialchars($row['ip']) ?>" 
                                                            data-date="<?= htmlspecialchars($row['bet_date']) ?>"
                                                            data-userids="<?= htmlspecialchars($row['userids']) ?>">
                                                            <i class="ri-alert-line"></i> Investigate
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
                                            <?php if (!empty($date)): ?>
                                                No cross bets detected for date: <?= htmlspecialchars($date); ?>
                                            <?php else: ?>
                                                No cross bets detected
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

    <!-- Investigation Modal -->
    <div class="modal fade" id="investigationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cross Bet Investigation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <p><strong>IP Address:</strong> <span id="modalIp"></span></p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Date:</strong> <span id="modalDate"></span></p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Users Involved:</strong> <span id="modalUsers"></span></p>
                        </div>
                    </div>
                    <hr>
                    <div id="investigationDetails">
                        <p class="text-center text-muted">Loading investigation details...</p>
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
                    "searchPlaceholder": "Search IP or Users",
                    "paginate": {
                        "next": '<i class="ri-arrow-right-s-line"></i>',
                        "previous": '<i class="ri-arrow-left-s-line"></i>'
                    }
                },
                "columnDefs": [
                    { "orderable": false, "targets": [3] }
                ]
            });

            $('#uid').on('keyup', function () {
                var val = $(this).val();
                table.column(0).search(val).draw();
            });

            // Investigation Modal
            $('.view-details').on('click', function() {
                var ip = $(this).data('ip');
                var date = $(this).data('date');
                var userIds = $(this).data('userids');
                
                // Set basic info in modal
                $('#modalIp').text(ip);
                $('#modalDate').text(date);
                $('#modalUsers').text(userIds.replace(/,/g, ', '));
                
                // Load investigation details
                loadInvestigationDetails(ip, date, userIds);
                
                // Show modal
                $('#investigationModal').modal('show');
            });

            function loadInvestigationDetails(ip, date, userIds) {
                $('#investigationDetails').html('<p class="text-center text-muted">Loading investigation details...</p>');
                
                // Create AJAX request to self
                $.ajax({
                    url: '<?php echo $_SERVER['PHP_SELF']; ?>',
                    type: 'GET',
                    data: {
                        action: 'get_investigation_details',
                        ip: ip,
                        date: date,
                        userids: userIds
                    },
                    success: function(response) {
                        $('#investigationDetails').html(response);
                    },
                    error: function() {
                        $('#investigationDetails').html('<p class="text-center text-danger">Error loading investigation details</p>');
                    }
                });
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>

<?php
// Handle AJAX request for investigation details
if (isset($_GET['action']) && $_GET['action'] == 'get_investigation_details') {
    $ip = $_GET['ip'] ?? '';
    $date = $_GET['date'] ?? '';
    $userIds = $_GET['userids'] ?? '';
    
    if (empty($ip) || empty($date)) {
        echo "<p class='text-danger'>Invalid parameters</p>";
        exit();
    }
    
    // Convert comma-separated user IDs to array
    $userArray = explode(',', $userIds);
    $userConditions = implode("','", array_map('trim', $userArray));
    
    // Query to get detailed bet information for all users from this IP on this date
    $detailSql = "
        SELECT 
            table_name,
            byabaharkarta as user_id,
            kalaparichaya as period,
            ojana as bet_type,
            sankhya as number,
            rashi as amount,
            samaya as bet_time,
            ip
        FROM (
            SELECT 
                'bajikattuttate' as table_name,
                byabaharkarta, kalaparichaya, ojana, sankhya, rashi, samaya, ip
            FROM bajikattuttate 
            WHERE ip = '$ip' AND DATE(tiarikala) = '$date' AND byabaharkarta IN ('$userConditions')
            
            UNION ALL
            
            SELECT 
                'bajikattuttate_zehn' as table_name,
                byabaharkarta, kalaparichaya, ojana, sankhya, rashi, samaya, ip
            FROM bajikattuttate_zehn 
            WHERE ip = '$ip' AND DATE(tiarikala) = '$date' AND byabaharkarta IN ('$userConditions')
            
            UNION ALL
            
            SELECT 
                'bajikattuttate_drei' as table_name,
                byabaharkarta, kalaparichaya, ojana, sankhya, rashi, samaya, ip
            FROM bajikattuttate_drei 
            WHERE ip = '$ip' AND DATE(tiarikala) = '$date' AND byabaharkarta IN ('$userConditions')
            
            UNION ALL
            
            SELECT 
                'bajikattuttate_funf' as table_name,
                byabaharkarta, kalaparichaya, ojana, sankhya, rashi, samaya, ip
            FROM bajikattuttate_funf 
            WHERE ip = '$ip' AND DATE(tiarikala) = '$date' AND byabaharkarta IN ('$userConditions')
        ) AS all_bets
        ORDER BY user_id, bet_time DESC
    ";
    
    $detailResult = mysqli_query($conn, $detailSql);
    
    if ($detailResult && mysqli_num_rows($detailResult) > 0) {
        echo "<h6>Detailed Bet Information:</h6>";
        echo "<div class='table-responsive' style='max-height: 400px; overflow-y: auto;'>";
        echo "<table class='table table-sm table-bordered table-striped'>";
        echo "<thead class='table-light'>";
        echo "<tr>
                <th>User ID</th>
                <th>Table</th>
                <th>Period</th>
                <th>Type</th>
                <th>Number</th>
                <th>Amount</th>
                <th>Time</th>
              </tr>";
        echo "</thead>";
        echo "<tbody>";
        
        $totalAmount = 0;
        $userTotals = [];
        
        while ($row = mysqli_fetch_assoc($detailResult)) {
            echo "<tr>";
            echo "<td class='fw-bold'>" . htmlspecialchars($row['user_id']) . "</td>";
            echo "<td><small>" . htmlspecialchars($row['table_name']) . "</small></td>";
            echo "<td>" . htmlspecialchars($row['period']) . "</td>";
            echo "<td>" . htmlspecialchars($row['bet_type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['number']) . "</td>";
            echo "<td>₹" . htmlspecialchars($row['amount']) . "</td>";
            echo "<td><small>" . htmlspecialchars($row['bet_time']) . "</small></td>";
            echo "</tr>";
            
            $totalAmount += floatval($row['amount']);
            
            // Calculate per-user totals
            if (!isset($userTotals[$row['user_id']])) {
                $userTotals[$row['user_id']] = 0;
            }
            $userTotals[$row['user_id']] += floatval($row['amount']);
        }
        
        echo "</tbody>";
        echo "</table></div>";
        
        // Display summary
        echo "<div class='mt-3 p-3 bg-light rounded'>";
        echo "<h6>Summary:</h6>";
        echo "<p><strong>Total Bets:</strong> " . mysqli_num_rows($detailResult) . "</p>";
        echo "<p><strong>Total Amount:</strong> ₹" . number_format($totalAmount, 2) . "</p>";
        echo "<p><strong>Per User Totals:</strong></p>";
        echo "<ul>";
        foreach ($userTotals as $userId => $userTotal) {
            echo "<li>User $userId: ₹" . number_format($userTotal, 2) . "</li>";
        }
        echo "</ul>";
        echo "</div>";
    } else {
        echo "<div class='alert alert-warning'>";
        echo "<p class='mb-0'>No detailed bet information found for IP: <strong>" . htmlspecialchars($ip) . "</strong> on date: <strong>" . htmlspecialchars($date) . "</strong></p>";
        echo "</div>";
    }
    
    exit();
}

// Close connection
mysqli_close($conn);
?>