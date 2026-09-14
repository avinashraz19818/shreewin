<?php
	session_start();
	if(empty($_SESSION['unohs'])){
		header("location: api/login.php?msg=unauthorized");
	}
	include ("api/conn.php");
?>

<!doctype html>

<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr"
    data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template" data-style="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

    <title>Withdraw Apply</title>

    <meta name="description" content="" />

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/img/favicon/favicon.ico?v=20260814-admin" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&ampdisplay=swap"
        rel="stylesheet" />

    <!-- Icons -->
    <link rel="stylesheet" href="assets/vendor/fonts/remixicon/remixicon.css" />
    <link rel="stylesheet" href="assets/vendor/fonts/flag-icons.css" />

    <!-- Menu waves for no-customizer fix -->
    <link rel="stylesheet" href="assets/vendor/libs/node-waves/node-waves.css" />

    <!-- Core CSS -->
    <link rel="stylesheet" href="assets/vendor/css/rtl/core.css" class="template-customizer-core-css" />
    <link rel="stylesheet" href="assets/vendor/css/rtl/theme-default.css" class="template-customizer-theme-css" />
    <link rel="stylesheet" href="assets/css/demo.css" />

    <!-- Vendors CSS -->
    <link rel="stylesheet" href="assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
    <link rel="stylesheet" href="assets/vendor/libs/typeahead-js/typeahead.css" />
    <link rel="stylesheet" href="assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css" />
    <link rel="stylesheet" href="assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css" />
    <link rel="stylesheet" href="assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css" />
    <link rel="stylesheet" href="assets/vendor/libs/datatables-checkboxes-jquery/datatables.checkboxes.css" />
    <link rel="stylesheet" href="assets/vendor/libs/select2/select2.css" />
    <link rel="stylesheet" href="assets/vendor/libs/@form-validation/form-validation.css" />

    <!-- Page CSS -->

    <!-- Helpers -->
    <script src="assets/vendor/js/helpers.js"></script>
    <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->
    <!--? Template customizer: To hide customizer set displayCustomizer value false in config.js.  -->
    <script src="assets/vendor/js/template-customizer.js"></script>
    <!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->
    <script src="assets/js/config.js"></script>
</head>

<body>
    <!-- Layout wrapper -->
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <!-- Menu -->

            <?php require_once("layout-menu.php"); ?>
            <!-- / Menu -->

            <!-- Layout container -->
            <div class="layout-page">
                <!-- Navbar -->

                <?php require_once("nav.php"); ?>

                <!-- / Navbar -->

                <!-- Content wrapper -->
                <div class="content-wrapper">
                    <!-- Content -->

                    <div class="container-xxl flex-grow-1 container-p-y">

                        <!-- Users List Table -->
                        <div class="card">
                            <div class="table-responsive text-nowrap">
                                <table class="datatables-withdrawapply table">
                                    <thead>
                                        <tr>
                                            <th>Sr. No</th>
                                            <th>Mobile</th>
                                            <th>Amount</th>
                                            <th>Payment Type</th>
                                            <th>Order ID</th>
                                            <th>Req. Date</th>
                                            <th>Action</th>
                                            
                                        </tr>
                                    </thead>
                                    <tbody class="table-border-bottom-0">
                                        <?php
                                        $Query=mysqli_query($conn,"select *,(select `mobile` from `shonu_subjects` where `id`=`hintegedukolli`.`balakedara`)as user 
							from `hintegedukolli` where `sthiti`='0' AND tike='Processing' order by shonu desc");
                                        $i = 0;
                                        $total = 0;
                                        while ($row = mysqli_fetch_array($Query)) {
                                            $i++;
                                            $total += $row['motta'];
                                            ?>
                                            <tr>
                                                <td>
                                                    <span class="fw-medium"><?= $i; ?></span>
                                                </td>
                                                <td><?= $row['user']; ?></td>
                                                <td><?= number_format($row['motta'],2); ?></td>
                                                <td><span class="badge rounded-pill bg-label-primary me-1"><?= 'Bank' ?></span></td>
                                                <td><?=  $row["dharavahi"];?></td>
                                                <td><?= date('d-m-Y', strtotime($row['dinankavannuracisi']));?></td>
                                                <td>
                                                    <a class="dropdown-item" href="withdraw-action.php?id=<?= encryptor('encrypt', $row['shonu']); ?>"><i
                                                            class="ri-eye-fill text-primary me-1"></i>Accept/Reject</a>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <!-- / Content -->

                    <!-- Footer -->
                    <?php require_once("footer.php"); ?>
                    <!-- / Footer -->

                    <div class="content-backdrop fade"></div>
                </div>
                <!-- Content wrapper -->
            </div>
            <!-- / Layout page -->
        </div>

        <!-- Overlay -->
        <div class="layout-overlay layout-menu-toggle"></div>

        <!-- Drag Target Area To SlideIn Menu On Small Screens -->
        <div class="drag-target"></div>
    </div>
    <!-- / Layout wrapper -->

    <!-- Core JS -->
    <!-- build:js assets/vendor/js/core.js -->
    <script src="assets/vendor/libs/jquery/jquery.js"></script>
    <script src="assets/vendor/libs/popper/popper.js"></script>
    <script src="assets/vendor/js/bootstrap.js"></script>
    <script src="assets/vendor/libs/node-waves/node-waves.js"></script>
    <script src="assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="assets/vendor/libs/hammer/hammer.js"></script>
    <script src="assets/vendor/libs/i18n/i18n.js"></script>
    <script src="assets/vendor/libs/typeahead-js/typeahead.js"></script>
    <script src="assets/vendor/js/menu.js"></script>

    <!-- endbuild -->

    <!-- Vendors JS -->
    <script src="assets/vendor/libs/moment/moment.js"></script>
    <script src="assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js"></script>
    <script src="assets/vendor/libs/select2/select2.js"></script>
    <script src="assets/vendor/libs/@form-validation/popular.js"></script>
    <script src="assets/vendor/libs/@form-validation/bootstrap5.js"></script>
    <script src="assets/vendor/libs/@form-validation/auto-focus.js"></script>
    <script src="assets/vendor/libs/cleavejs/cleave.js"></script>
    <script src="assets/vendor/libs/cleavejs/cleave-phone.js"></script>

    <!-- Main JS -->
    <script src="assets/js/main.js"></script>

    <!-- Page JS -->
      <script>
	$(function () {
		$('.datatables-withdrawapply').DataTable({
		  "paging": true,
		  "lengthChange": true,
		  "searching": true,
		  "ordering": false,
		  "info": true,
		  "autoWidth": true,
		  "pageLength": 100,
		  "dom":
            '<"row"' +
            '<"col-md-12"<"d-flex align-items-center justify-content-md-end justify-content-center"<l><"me-4"f>>>' +
            '>t' +
            '<"row p-5"' +
            '<"col-sm-12 col-md-6"i>' +
            '<"col-sm-12 col-md-6"p>' +
            '>',
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
	});
  </script>
</body>

</html>