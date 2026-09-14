<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


session_start();
if (empty($_SESSION['unohs'])) {
    header("location: api/login.php?msg=unauthorized");
}
include("api/conn.php");
$date = isset($_GET['date']) ? date('Y-m-d', strtotime($_GET['date'])) : date('Y-m-d');
?>
<!doctype html>
<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr"
    data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template" data-style="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Agent Users</title>
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
                                <div class="d-flex justify-content-between align-items-center px-4">
                                    <div class="d-flex align-items-center">
                                        <h4 class="m-5">User First Deposit Bonus</h4>
                                        <input type="number" class="form-control" id="uid"
                                        placeholder="Enter UID" autofocus />
                                    </div>
                                    <form action="" method="get"
                                        class="d-flex gap-3 align-items-center">
                                        <input type="date" class="form-control" id="date" name="date"
                                            value="<?= $date; ?>" max="<?= date('Y-m-d'); ?>">
                                        <button type="submit" class="btn btn-primary">Search</button>
                                    </form>
                                </div>
                            <table class="table" id="example1">
    <thead>
        <tr>
            <th>User ID</th>
            <th>First Deposit</th>
            <th>Balance</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody class="table-border-bottom-0">
        <?php
$dateFilter = isset($_GET['date']) ? date('Y-m-d', strtotime($_GET['date'])) : date('Y-m-d');

$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

$sql = "SELECT 
            t.balakedara, 
            MAX(s.motta) AS balance, 
            t.firstbonus,
            (SELECT motta FROM thevani WHERE balakedara = t.balakedara AND sthiti = 1 ORDER BY shonu ASC LIMIT 1) AS first_deposit
        FROM thevani t
        JOIN shonu_kaichila s ON t.balakedara = s.balakedara
        WHERE t.sthiti = 1 
        AND DATE(t.dinankavannuracisi) = '$date'
        GROUP BY t.balakedara";
        
$result = mysqli_query($conn, $sql);
        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $statusColor = $row["firstbonus"] == 0 ? 'danger' : 'success';
                $statusText = $row["firstbonus"] == 0 ? 'Unpaid' : 'Paid';
                ?>
                <tr>
                    <td><?= $row["balakedara"]; ?></td>
                    <td>₹ <?= number_format($row["first_deposit"], 2); ?></td>
                    <td>₹ <?= number_format($row["balance"], 2); ?></td>
                    <td>
                        <button class="btn btn-<?= $statusColor ?>" onclick="updateStatus(<?= $row['balakedara']; ?>, this)">
                            <?= $statusText ?>
                        </button>
                    </td>
                </tr>
                <?php
            }
        } else {
            echo "<tr><td colspan='4'>No results found</td></tr>";
        }
        ?>
    </tbody>
</table>
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
function updateStatus(userId, button) {
    if (confirm("Are you sure you want to paid this?")) {
        $.ajax({
            url: "update_bonus_status.php",
            type: "POST",
            data: { userId: userId },
            success: function(response) {
                if (response == "success") {
                    button.classList.remove("btn-danger");
                    button.classList.add("btn-success");
                    button.innerText = "Paid";
                } else {
                    alert("Error updating status.");
                }
            }
        });
    }
}
</script>
  
  
  
  
    <script>
        $(function () {
            var table = $('#example1').DataTable({
                "paging": true,
                "lengthChange": true,
                "searching": true,
                "ordering": false,
                "info": true,
                "autoWidth": true,
                "pageLength": 50,
                "dom": '<"row"' + '<"col-md-12"<"d-flex align-items-center justify-content-md-end justify-content-center"<"px-4">>>' + '>t' + '<"row p-5"' + '<"col-sm-12 col-md-6"i>' + '<"col-sm-12 col-md-6 d-flex align-items-center justify-content-end gap-5"lp>' + '>',
                "language": {
                    sLengthMenu: 'Show _MENU_',
                    search: '',
                    searchPlaceholder: 'Search User',
                    paginate: {
                        next: '<i class="ri-arrow-right-s-line"></i>',
                        previous: '<i class="ri-arrow-left-s-line"></i>'
                    }
                }
            });
            $('#uid').on('keyup change', function () {
                var val = $(this).val();
                table.columns(1).search(val).draw(); // Change index as needed
            });
        });
    </script>
</body>

</html>