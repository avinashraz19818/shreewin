<?php



ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (empty($_SESSION['unohs'])) {
  header("location: api/login.php?msg=unauthorized");
}
include("api/conn.php");
$bonusTypes = [
3 => "Red envelope",
8 => "Agent red envelope recharge",
10 => "Recharge gift",
13 => "Bonus",
14 => "First full gift",
20 => "Invite bonus",
25 => "Card binding gift",
107 => "Weekly Awards",
124 => "Join channel rewards",
118 => "Daily Awards",
117 => "New members get bonuses by playing games",
115 => "Return Awards",
];

if (isset($_POST['deduct_id'])) {
	$price = $_POST['price'];
	$uid = mysqli_real_escape_string($conn, $_POST['uid']);
	$deduct_id = mysqli_real_escape_string($conn, $_POST['deduct_id']);

$date = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
    $currentTime = $date->format('Y-m-d H:i:s');
    

	$deductMotta = "UPDATE shonu_kaichila SET motta = IF(motta >= $price, motta - $price, 0) WHERE balakedara = '$uid'";
	mysqli_query($conn, $deductMotta);

	$bonusDeduction = "INSERT INTO bonus_deduction (userkani, price, shonu, remark) 
                      VALUES ($uid, $price, '$currentTime', 'Salary Deduction')";	

	mysqli_query($conn, $bonusDeduction);
	
	$deleteRow = "UPDATE `bonus_recharge_table` SET reffer = '1' WHERE kani = '$deduct_id'"; 
	mysqli_query($conn, $deleteRow);
	
	header("Location: " . $_SERVER['PHP_SELF']); // Refresh the page after deletion
}



?>

<!doctype html>

<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr"
  data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template" data-style="light">

  <head>
    <meta charset="utf-8" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

    <title>Manage Salary</title>

    <meta name="description" content="" />
    <link rel="icon" type="image/x-icon" href="assets/img/favicon/favicon.ico?v=20260814-admin" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&ampdisplay=swap"
      rel="stylesheet" />
    <link rel="stylesheet" href="assets/vendor/fonts/remixicon/remixicon.css" />
    <link rel="stylesheet" href="assets/vendor/fonts/flag-icons.css" />
    <link rel="stylesheet" href="assets/vendor/libs/node-waves/node-waves.css" />
    <link rel="stylesheet" href="assets/vendor/css/rtl/core.css" class="template-customizer-core-css" />
    <link rel="stylesheet" href="assets/vendor/css/rtl/theme-default.css" class="template-customizer-theme-css" />
    <link rel="stylesheet" href="assets/css/demo.css" />
    <link rel="stylesheet" href="assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
    <link rel="stylesheet" href="assets/vendor/libs/typeahead-js/typeahead.css" />
    <link rel="stylesheet" href="assets/vendor/libs/dropzone/dropzone.css" />
    <script src="assets/vendor/js/helpers.js"></script>
    <script src="assets/vendor/js/template-customizer.js"></script>
    <script src="assets/js/config.js"></script>
  </head>

<body>
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
            <div class="row">
                <!-- Basic  -->
                <div class="col-12">
                  <div class="card mb-6">
                    <h5 class="card-header">Manage Salary</h5>
                    <div class="card-body">
                        <div class="mb-4 px-4">
                            <label for="formFile" class="form-label">Choose excel file</label>
                            <input class="form-control py-5" type="file" id="excelFile" accept=".xls,.xlsx,.csv" />
                        </div>
                    </div>
                  </div>
                </div>
            </div>
            
            <div class="card">
              <div class="table-responsive text-nowrap">
                <table class="table table-hover datatables-bonusmanage">
                  <thead>
                    <tr>
                      <th>Sr.No</th>
                      <th>User Id</th>
                      <th>Amount</th>
                      <th>New Balance</th>
                      <th>Remarks</th>
                      <th>Date</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody class="table-border-bottom-0">
                      <?php
						$Query = mysqli_query($conn, "
						SELECT * FROM bonus_recharge_table WHERE serial='Salary' AND processed != '1'
						ORDER BY shonu DESC
						");
						$i = 0;
						while ($row = mysqli_fetch_array($Query)) {
							$i++;
					  ?>
                        <tr>
                          <td>
                            <span class="fw-medium"><?= $i; ?></span>
                          </td>
                          <td><?= $row["userkani"]; ?></td>
                          <td><?= number_format($row['price'], 2); ?></td>
                          <td><?= $row["balance"]; ?></td>
                          <td><?= $row["remark"]; ?></td>
                          <td><?= date('d-m-Y h:i:s A', strtotime($row['shonu'])); ?></td>
                          <td>
                              <form  method="post" action="" onsubmit="return confirmDelete(event);">
                                  <input type="hidden" name="deduct_id" value="<?=$row["kani"]?>"/>
                                  <input type="hidden" name="uid" value="<?=$row["userkani"]?>"/>
                                  <input type="hidden" name="price" value="<?=$row["price"]?>"/>
                                  <button type="submit" class="btn btn-danger">Deduct</button>
                              </form>
                          </td>
                        </tr>
                      <?php } ?>
                      <?php if($i===0){ ?>
                            <tr>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td>No data available</td>
                                <td></td>
                                <td></td>
                                <td></td>
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
    <script src="assets/vendor/libs/jquery/jquery.js"></script>
    <script src="assets/vendor/libs/popper/popper.js"></script>
    <script src="assets/vendor/js/bootstrap.js"></script>
    <script src="assets/vendor/libs/node-waves/node-waves.js"></script>
    <script src="assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="assets/vendor/libs/hammer/hammer.js"></script>
    <script src="assets/vendor/libs/i18n/i18n.js"></script>
    <script src="assets/vendor/libs/typeahead-js/typeahead.js"></script>
    <script src="assets/vendor/js/menu.js"></script>
    <script src="assets/vendor/libs/dropzone/dropzone.js"></script>
    <script src="assets/js/main.js"></script>
    <script src="assets/js/forms-file-upload.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
  <script>
    $(document).ready(function () {
        $('.datatables-bonusmanage').DataTable({
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
      $("#type").on('submit', (function (e) {
        e.preventDefault();
        $.ajax({
          type: "POST",
          url: "api/handle_bonus.php",
          data: new FormData(this),
          contentType: false,
          cache: false,
          processData: false,

          success: function (html) {
            if (html == 1) {
              alert("Bonus added successfully...");
              $("#type")[0].reset();
              window.location = '';
            }
            else if (html == 0) {
              alert("Some Technical Error....");
            }
          }
        });
      }));

    });
  </script>
  <script>
    $(document).ready(function () {
        $('#excelFile').on('change', function (event) {
            const file = event.target.files[0];
            
            if (!confirm("Do you want to process this file?")) {
                alert("File processing canceled.");
                return;
            }
            // ✅ 1. Check if file is selected
            if (!file) {
                alert("Please select a file!");
                return;
            }

            // ✅ 2. Validate file type
            const fileType = file.name.split('.').pop().toLowerCase();
            if (!['xls', 'xlsx', 'csv'].includes(fileType)) {
                alert("Invalid file type! Please upload an Excel or CSV file.");
                return;
            }

            const reader = new FileReader();

            reader.onload = function (e) {
                const data = new Uint8Array(e.target.result);

                // ✅ 3. Read & Parse Excel/CSV
                let workbook;
                if (fileType === "csv") {
                    const csvText = new TextDecoder().decode(data);
                    const rows = csvText.split("\n").map(row => row.split(",")); 
                    processData(rows);
                } else {
                    workbook = XLSX.read(data, { type: 'array' });
                    const sheetName = workbook.SheetNames[0]; 
                    const sheet = workbook.Sheets[sheetName];
                    const jsonData = XLSX.utils.sheet_to_json(sheet, { header: 1 });
                    processData(jsonData);
                }
            };

            reader.readAsArrayBuffer(file);
        });

        function processData(jsonData) {
            console.log("Extracted Data:", jsonData);

            // ✅ 4. Check if data is empty
            if (!jsonData || jsonData.length < 2) {
                alert("Invalid or empty file! Please upload a valid Excel/CSV file.");
                return;
            }

            sendToAPI(jsonData);
        }

        function sendToAPI(jsonData) {
            $.ajax({
                url: 'api/AddSalaryExcel.php', 
                type: 'POST',
                data: JSON.stringify({ data: jsonData }), 
                contentType: 'application/json',
                dataType: 'json',
                success: function(response) {
                    if (response.error) {
                        alert(`❌Error: ${response.error}`); // Show error message
                    } else {
                        console.log("Success:", response);
                        alert(`Success✅ \n${response.message}\nSkipped Rows: ${response.skipped_rows}`)
                        location.reload();
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error:", error);
                    alert("❌An error occurred while processing your request.");
                }
            });
        }
    });
    function confirmDelete(event) {
    	if (!confirm("Are you sure you want to delete this?")) {
    		event.preventDefault(); // Prevent form submission if user cancels
    		return false;
    	}
    	return true;
    }
  </script>
</body>

</html>