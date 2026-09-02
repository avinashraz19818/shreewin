<?php
session_start();
if (empty($_SESSION['unohs'])) {
    header("location: api/login.php?msg=unauthorized");
}
if (!isset($_GET['userid'])) {
    header("location: agent-user.php");
    exit();
}
include("api/conn.php");
$date = isset($_GET['date']) ? date('Y-m-d', strtotime($_GET['date'])) : date('Y-m-d');
$level=isset($_GET['level'])?$_GET['level']:0;
$userid=$_GET['userid'];
$sql = "SELECT owncode 
        FROM shonu_subjects
        WHERE id = '$userid'";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    $snum = $result->fetch_assoc();
    $ownCode = $snum['owncode'];
}else{
    header("location: agent-user.php");
    exit();
}
?>
<!doctype html>
<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr"
    data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template" data-style="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Agent Total Recharge</title>
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
                                <h5 class="mx-5 mt-5">Agent ID: <?=$userid?></h5>
                                <h4 class="mx-5">Total Recharge Level <?=$level?></h4>
                                <table class="table" id="example1">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Mobile</th>
                                            <th>Cust ID</th>
                                            <th>Recharge Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody class="table-border-bottom-0">
                                        <?php
                                        $sql = "SELECT 
                                            ss.id AS user_id,
                                            ss.mobile,
                                            SUM(t.motta) AS total_deposit_amount
                                        FROM thevani t
                                        JOIN shonu_subjects ss ON t.balakedara = ss.id
                                        WHERE ss.code".($level-1==0?'':$level-1)." = '$ownCode'
                                        AND DATE(t.dinankavannuracisi) = '$date'
                                        AND t.sthiti = 1
                                        GROUP BY ss.id, ss.mobile;  -- Ensuring unique users
                                                ";  // Ensure only active agents are shown
                                        $result = mysqli_query($conn, $sql);
                                            // output data of each row
                                            $i=0;
                                            while ($row = mysqli_fetch_assoc($result)) {$i++;
                                                ?>
                                                <tr>
                                                    <td><?=$i?></td>
                                                    <td>
                                                        <?= $row["mobile"]; ?>
                                                    </td>
                                                    <td>
                                                        <?= $row["user_id"]; ?>
                                                    </td>
                                                    <td><?= number_format($row["total_deposit_amount"]); ?></td>
                                                </tr>
                                                <?php
                                            }
                                        ?>
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
        $(function () {
            var table = $('#example1').DataTable({
                "paging": true,
                "lengthChange": true,
                "searching": true,
                "ordering": false,
                "info": true,
                "autoWidth": true,
                "pageLength": 10,
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