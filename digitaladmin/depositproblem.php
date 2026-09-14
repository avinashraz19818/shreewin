<?php
session_start();
if (empty($_SESSION['unohs'])) {
  header("location: api/login.php?msg=unauthorized");
}
include("api/conn.php");
?>

<!doctype html>

<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr"
  data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template" data-style="light">

<head>
  <meta charset="utf-8" />
  <meta name="viewport"
    content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

  <title>Deposit Problems</title>

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
                <table class="datatables-depositproblem table">
                  <thead>
                    <tr>
                      <th>ID</th>
                      <th>User ID</th>
                      <th>Order No.</th>
                      <th>Amount</th>
                      <th>UTR</th>
                      <th>Image 1</th>
                      <th>Image 2</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody class="table-border-bottom-0">
                    <?php
                    $Query = mysqli_query($conn, "SELECT id, userid, deposit_order_no,image_upload,file_upload, order_amount, utr, remarks FROM your_table WHERE prob = 'Deposit Not Receive' AND status = 2");
                    while ($row = mysqli_fetch_array($Query)) {
                    ?>
                      <tr>
                        <td>
                          <span class="fw-medium"><?= $row['id']; ?></span>
                        </td>
                        <td><?= $row['userid']; ?></td>
                        <td><?= $row['deposit_order_no']; ?></td>
                        <td><?= $row['order_amount']; ?></td>
                        <td><?= $row['utr']; ?></td>
                        <td><a href="https://h5workersupports.yaarwin21.site/uploads/<?= $row['file_upload']; ?>"><?= $row['file_upload']; ?></a></td>
                        <td><a href="https://h5workersupports.yaarwin21.site/uploads/<?= $row['image_upload']; ?>"><?= $row['image_upload']; ?></a></td>
                        <td>
                          <button class="btn btn-primary edit-btn" type="submit" name="approve" data-id="<?= $row['id']; ?>" data-remarks="<?= $row['remarks']; ?>" data-bs-target="#deposit-problem-modal" data-bs-toggle="modal"><i
                              class="ri-chat-new-line text-white me-1"></i>Do Response</button>
                        </td>
                      </tr>
                    <?php } ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
          <div class="modal fade" id="deposit-problem-modal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog" role="document">
              <form class="modal-content" id="editForm">
                <div class="modal-header">
                  <h4 class="modal-title" id="exampleModalLabel1">Add Remark</h4>
                  <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Close"></button>
                </div>
                <div class="modal-body">
                  <div class="row">
                    <div class="col mb-6 mt-2">
                      <div class="form-floating form-floating-outline">
                        <input type="hidden" id="id" name='id' />
                        <input type="hidden" id="formid" value="14" name='formid' />
                        <input type="text" id="remarks" name="remarks" class="form-control" placeholder="Enter Remark" />
                        <label for="nameBasic">Remark</label>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    Close
                  </button>
                  <button type="submit" class="btn btn-primary" id="modal-button">Done</button>
                </div>
              </form>
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
    $(function() {
      $('.datatables-depositproblem').DataTable({
        "paging": true,
        "lengthChange": true,
        "searching": true,
        "ordering": false,
        "info": true,
        "autoWidth": true,
        "pageLength": 10,
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
          searchPlaceholder: 'Search User',
          paginate: {
            next: '<i class="ri-arrow-right-s-line"></i>',
            previous: '<i class="ri-arrow-left-s-line"></i>'
          }
        }
      });
      // Add event listener for all buttons with class 'edit-btn'
      $(document).on('click', '.edit-btn', function() {
        // Get data-id and data-remarks attributes
        const dataId = $(this).data('id');
        const dataRemarks = $(this).data('remarks');
        // Set values in the form fields
        $('#id').val(dataId);
        $('#remarks').val(dataRemarks);
      });


      $('#editForm').submit(function(e) {
        e.preventDefault();
        const formData = $(this).serialize();
        $.post('api/updateremarks.php', formData, function(response) {
          alert(response.message);
          if (response.status == "success") {
            location.reload();
          }
        }, 'json');
      });
    });
  </script>
</body>

</html>