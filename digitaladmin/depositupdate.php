<?php
session_start();
if(empty($_SESSION['unohs'])){
    header("location: api/login.php?msg=unauthorized");
}
include ("api/conn.php");

// Default date empty - only show data when date is selected
$date = isset($_GET['date']) && !empty($_GET['date']) ? $_GET['date'] : '';
$dateFilter = isset($_GET['date']) && !empty($_GET['date']) ? $_GET['date'] : '';
?>

<!doctype html>

<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr"
    data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template" data-style="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

    <title>Deposit Requests</title>

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


<div class="d-flex justify-content-between align-items-center px-4">
    <h4 class="m-5">Deposit Apply</h4>
    <form action="" method="get" class="d-flex gap-3 align-items-center">
        <input type="date" class="form-control" id="date" name="date"
            value="<?= $date; ?>">
        <button type="submit" class="btn btn-primary">Search</button>
        <?php if(!empty($date)): ?>
            <a href="depositupdate.php" class="btn btn-secondary">Clear Filter</a>
        <?php endif; ?>
    </form>
</div>

                            <div class="table-responsive text-nowrap">
                                <table class="datatables-depositupdate table">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>User ID</th>
                                            
                                            <th>Gateway Name</th>
                                            <th>Amount</th>
                                            <th>Order ID</th>
                                            <th>UTR No</th>
                                            <th>Date</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="table-border-bottom-0">
                                        <?php
                                        // Build query based on date filter
$sql = "SELECT * FROM `thevani` WHERE `sthiti` = '0'";
if(!empty($dateFilter)) {
    $sql .= " AND DATE(`dinankavannuracisi`) = '$dateFilter'";
}
$sql .= " ORDER BY `shonu` DESC";
$Query = mysqli_query($conn, $sql);
                                        $i = 0;
                                        while ($row = mysqli_fetch_array($Query)) {
                                            $i++;
                                            ?>
                                            <tr>
                                                <td>
                                                    <span class="fw-medium"><?= $i; ?></span>
                                                </td>
                                                <td><?= $row['balakedara']; ?></td>
                                               
                                                <td>
                                                <?= $row['mula']; ?>
                                                <button class="btn btn-sm btn-icon btn-outline-primary copy-btn ms-2" 
                                                        data-clipboard-text="<?= htmlspecialchars($row['mula']) ?>"
                                                        title="Copy to clipboard">
                                                    <i class="ri-file-copy-line"></i>
                                                </button>
                                                 </td>
                                                <td><?= number_format($row['motta'],2); ?></td>
                                               <td>
                                                <?= $row["dharavahi"]; ?>
                                                <button class="btn btn-sm btn-icon btn-outline-primary copy-btn ms-2" 
                                                        data-clipboard-text="<?= htmlspecialchars($row['dharavahi']) ?>"
                                                        title="Copy to clipboard">
                                                    <i class="ri-file-copy-line"></i>
                                                </button>
                                                 </td>
                                                 <td>
                                                <?= $row["ullekha"]; ?>
                                                <button class="btn btn-sm btn-icon btn-outline-primary copy-btn ms-2" 
                                                        data-clipboard-text="<?= htmlspecialchars($row['ullekha']) ?>"
                                                        title="Copy to clipboard">
                                                    <i class="ri-file-copy-line"></i>
                                                </button>
                                                 </td>
                                                <td><?= date('d-m-Y H:i:s', strtotime($row['dinankavannuracisi']));?></td>
                                                <td>
                                                    <div class="d-flex gap-2 align-items-center">
                                                        <form id="<?php echo 'app'.$row['shonu'];?>" class="approval-form mb-0">
                                                            <input type="hidden" name="uid" value="<?php echo $row['balakedara'];?>">
                                                            <input type="hidden" name="amount" value="<?php echo $row['motta'];?>">
                                                            <input type="hidden" name="date" value="<?php echo $row['dinankavannuracisi'];?>">
                                                            <input type="hidden" name="sid" value="<?php echo $row['shonu'];?>">
                                                            <input type="hidden" name="ref_num" value="<?php echo $row['ullekha'];?>">
                                                            <input type="hidden" name="app" value="Approve Payment">
                                                            <button class="btn btn-primary btn-sm" type="submit" name="approve">
                                                                <i class="ri-check-fill text-white me-1"></i>Approve
                                                            </button>
                                                        </form>
                                                
                                                        <form id="<?php echo 'rej'.$row['shonu'];?>" class="reject-form mb-0">
                                                            <input type="hidden" name="uid" value="<?php echo $row['balakedara'];?>">
                                                            <input type="hidden" name="amount" value="<?php echo $row['motta'];?>">
                                                            <input type="hidden" name="date" value="<?php echo $row['dinankavannuracisi'];?>">
                                                            <input type="hidden" name="sid" value="<?php echo $row['shonu'];?>">
                                                            <input type="hidden" name="ref_num" value="<?php echo $row['ullekha'];?>">
                                                            <input type="hidden" name="rej" value="Reject Payment">
                                                            <button class="btn btn-danger btn-sm" type="submit" name="reject">
                                                                <i class="ri-close-fill text-white me-1"></i>Reject
                                                            </button>
                                                        </form>
                                                    </div>
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
    <script>
	$(function () {
		$('.datatables-depositupdate').DataTable({
		  "paging": true,
		  "lengthChange": true,
		  "searching": true,
		  "ordering": false,
		  "info": true,
		  "autoWidth": true,
		  "pageLength": 10,
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
	document.querySelectorAll('.approval-form').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(e.target);
            $.ajax({
                type: "Post",
                data: "uid="+formData.get('uid')+"&amount="+formData.get('amount')+"&date="+formData.get('date')+"&sid="+formData.get('sid')+"&ref_num="+formData.get('ref_num')+"&app="+formData.get('app')+"&approve=true",
                url: "api/deposit-action.php",
                success: function (html) {
                    html=html.split('~')[0];
                    if (html == 1) {
                        window.location = 'depositaccepted.php';
                    } else {
                        window.location = 'depositupdate.php';
                    }                
                    return false;
                },
                error: function (e) {
                }
            });
        });
    });
    document.querySelectorAll('.reject-form').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(e.target);
            $.ajax({
                type: "Post",
                data: "uid="+formData.get('uid')+"&amount="+formData.get('amount')+"&date="+formData.get('date')+"&sid="+formData.get('sid')+"&ref_num="+formData.get('ref_num')+"&rej="+formData.get('rej')+"&reject=true",
                url: "api/deposit-action.php",
                success: function (html) {
                    html=html.split('~')[0];
                    if (html == 1) {
                        window.location = 'depositrejected.php';
                    } else {
                        window.location = 'depositupdate.php';
                    }                
                    return false;
                },
                error: function (e) {
                }
            });
        });
    });

  </script>
  
  <script src="https://cdn.jsdelivr.net/npm/clipboard@2.0.8/dist/clipboard.min.js"></script>
<script>
    // Initialize clipboard.js
    document.addEventListener('DOMContentLoaded', function() {
        var clipboard = new ClipboardJS('.copy-btn');
        
        clipboard.on('success', function(e) {
            // Show tooltip or alert on successful copy
            e.trigger.innerHTML = '<i class="ri-check-line"></i>';
            e.trigger.setAttribute('title', 'Copied!');
            
            // Reset after 2 seconds
            setTimeout(function() {
                e.trigger.innerHTML = '<i class="ri-file-copy-line"></i>';
                e.trigger.setAttribute('title', 'Copy to clipboard');
            }, 2000);
            
            e.clearSelection();
        });
        
        clipboard.on('error', function(e) {
            console.error('Failed to copy text:', e.action);
        });
    });
</script>
  
  
  
</body>

</html>