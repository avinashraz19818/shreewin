<?php
session_start();
if (empty($_SESSION['unohs'])) {
    header("location: api/login.php?msg=unauthorized");
}
?>
<?php
include("api/conn.php");
require_once dirname(__DIR__) . '/saas_lottery/admin_override.php';

$uhisisankhye = 'Not set';
try {
    $pendingMoto = sl_admin_override_get_pending($conn, 'MotoRace_1M');
    if ($pendingMoto) $uhisisankhye = (string)$pendingMoto['premium'];
} catch (Throwable $ignored) {}

?>
<!doctype html>

<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr"
    data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template" data-style="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

    <title>Moto Racing 1 Min</title>

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
    <style>
        .circle{
            width: 100px;
            height: 100px;
            border-radius: 50%;
            font-size: 1.7em;
            font-weight: bold;
        }
        .red{
            color: rgba(231, 85, 79, 1);
            background-color: rgba(243, 223, 224, 1);
            border: 10px solid rgba(231, 85, 79, 1);
        }
        .green{
            color: green;
            background-color: rgba(226, 238, 230, 1);
            border: 10px solid green;
        }
        .border-violet{
            border: 10px solid purple;
        }
    </style>
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
                        <div class="row g-6 mb-6">
                            <div class="col-sm-12 col-xl-3">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div class="me-1">
                                                <p class="text-heading mb-1">Time Remaining</p>
                                                <div class="d-flex align-items-center">
                                                    <h4 class="mb-1 me-2 text-success" id="demo">
                                                        Loading..
                                                    </h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-12 col-xl-3">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div class="me-1">
                                                <p class="text-heading mb-1">Total Bet Amount</p>
                                                <div class="d-flex align-items-center">
                                                    <h4 class="mb-1 me-2 text-success" id="tobet">
                                                        ₹ 0
                                                    </h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-12 col-xl-6">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div class="me-1">
                                                <p class="text-heading mb-1">Period ID</p>
                                                <div class="d-flex align-items-center">
                                                    <h4 class="mb-1 me-2 text-success" id="curr-period">
                                                        Loading...
                                                    </h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card mb-5 p-5">
                            <div class="d-flex justify-content-center align-items-center mb-10 gap-5">
                                <h3 class="text-center m-0">Next prediction : <span class="badge bg-label-success rounded-pill" id="prediction"><?= $uhisisankhye; ?></span></h3>
                                <?php if($uhisisankhye!='Not set'){ ?>
                                    <button class="btn btn-danger" onclick="unsetman()" id="unset-btn">Unset</button>
                                <?php } ?>
                            </div>
                            <form class="row g-6 mb-6 justify-content-center" action="api/prediction-set.php" id="pre" method="POST">
                                <div class="col-sm-12 col-xl-12 col-lg-12 col-md-12 d-flex justify-content-center align-items-center">
                                    <input type="text" id="next" name="username" pattern="^(10|[1-9])(,(10|[1-9])){9}$" placeholder="Enter complete rank, e.g. 3,7,1,10,5,2,8,4,9,6" class="flex-grow-1 cool-input p-3" style="height: 40px;" required>
                                    <input type="hidden" name="type" value="motoracing">
                                    <input type="hidden" name="issue_number" id="issue-number">
                                </div>
                                <div class="col-sm-12 col-xl-12 col-lg-12 col-md-12 d-flex justify-content-center align-items-center">
                                    <button type="submit" class="btn btn-success w-100">Set prediction</button>
                                </div>
                            </form>
                        </div>
                        
                        <div class="card mt-5">
                            <h4 class="m-5">Live BET</h4>
                            <div class="table-responsive text-nowrap">
                                <table class="datatables-depositaccepted table" id="example2">
                                    <thead>
                                        <tr>
                                            <th>User ID</th>
                                            <th>Value</th>
                                            <th>Amount</th>
                                            <th>Mobile</th>
                                            <th>Balance</th>
                                        </tr>
                                    </thead>
                                    <tbody class="table-border-bottom-0">
                                        <tr>
                                            <td></td>
                                            <td></td>
                                            <td class="text-center">Loading...</td>
                                            <td></td>
                                            <td></td>
                                        </tr>
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
		$('.datatables-depositaccepted').not('#example2').DataTable({
		  "paging": false,
		  "lengthChange": false,
		  "searching": false,
		  "ordering": false,
		  "info": false,
		  "autoWidth": true,
		  "pageLength": 15
		});
	});
	$(document).ready(function () {
		var xyz = setInterval(function() { 
    		wingoonetotal();
		}, 2000);
	});
	function wingoonetotal()
	{
	    let periodid=document.getElementById('curr-period').innerHTML;
		$.ajax({
		type: "Post",
		url: "api/total-bet-amount.php?periodid=" + periodid+"&type=motoracing",
		success: function (html) {
		 document.getElementById("tobet").innerHTML = '₹ '+html;		 
		  return false;
		  },
		  error: function (e) {}
		  });
	}
	let liveBetTable = null;
	function refreshLiveBets() {
	    const periodid = document.getElementById('curr-period').textContent.trim();
	    if (!/^\d{17}$/.test(periodid)) return;
	    const endpoint = "api/live-game-bets.php?type=motoracing&periodid=" + encodeURIComponent(periodid);
	    if (liveBetTable) {
	        liveBetTable.ajax.url(endpoint).load(null, false);
	        return;
	    }
	    liveBetTable = $('#example2').DataTable({
	        processing: true, serverSide: true, ajax: endpoint, paging: true,
	        lengthChange: false, searching: false, ordering: false, info: true,
	        autoWidth: false, pageLength: 50, dom: 't<"row p-5"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>'
	    });
	}
	setTimeout(refreshLiveBets, 700);
	setInterval(refreshLiveBets, 5000);
	function resetman() {
        $.ajax({
            method: "Post",
            data: { stat: 1, type: "motoracing" },
            url: "api/prediction-unset.php",
            success: function (html) {
                console.log(html);
                return false;
            },
            error: function (e) { }
        });
    }
    function unsetman() {
        resetman();
        location.reload();
    }

	let timer = null; // Global reference to ensure only one timer runs
	let serverClockOffset = 0;

function startTimer(startTime, endTime) {
    let startTimestamp = new Date(startTime).getTime();
    let endTimestamp = new Date(endTime).getTime();

    // Clear any existing interval before starting a new one
    if (timer) {
        clearInterval(timer);
    }

    function updateTimer() {
            let now = Date.now() + serverClockOffset;
            let distance = Math.floor((endTimestamp - now) / 1000); // Remaining seconds
            // Make timer 1 sec forward
            distance = Math.max(0, distance + 1);
            // Ensure countdown doesn't go negative
            if (distance < 0) {
                distance = 0;
            }
    
            let minutes = Math.floor(distance / 60);
            let seconds = ('0' + (distance % 60)).slice(-2);
    
            document.getElementById("demo").innerHTML =
                `<span class='timer'>0${minutes}</span><span>:</span><span class='timer'>${seconds}</span>`;
    
            if (distance < 1) {
                clearInterval(timer);
                fetchServerTime()
                    .then(data => {
                        // console.log("New Server Time Data:", data);
                        startTimer(data.startTime, data.endTime);
                        document.getElementById("curr-period").innerHTML = data.period;
                        document.getElementById("issue-number").value = data.period;
                        if (Number.isFinite(Number(data.serverTime))) serverClockOffset = Number(data.serverTime) - Date.now();
                        document.getElementById("prediction").innerHTML = data.prediction === null ? 'Not set' : data.prediction;
                        let unsetBtn = document.getElementById("unset-btn");
                        if (unsetBtn) {
                            unsetBtn.classList.add('d-none');
                        } 
                    })
                    .catch(error => {
                        console.error("Server Time Fetch Error:", error);
                    });
            }
        }
    
        updateTimer(); // Run immediately to update UI
        timer = setInterval(updateTimer, 1000); // Run every second
    }
    
    // Fetch server time and start the timer
    function fetchServerTime() {
        return fetch('api/servertime.php?type=motoracing')
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Failed to fetch server time. Status: ${response.status}`);
                }
                return response.json();
            })
            .catch(error => {
                console.error('Fetch Server Time Error:', error);
                throw error;
            });
    }
    
    // Start the timer when the page loads
    fetchServerTime()
        .then(data => {
            // console.log("Server Time Data:", data);
            startTimer(data.startTime, data.endTime);
            document.getElementById("curr-period").innerHTML = data.period;
            document.getElementById("issue-number").value = data.period;
            if (Number.isFinite(Number(data.serverTime))) serverClockOffset = Number(data.serverTime) - Date.now();
            document.getElementById("prediction").innerHTML = data.prediction === null ? 'Not set' : data.prediction;
        })
        .catch(error => {
            console.error("Error Fetching Initial Server Time:", error);
        });

  </script>
</body>

</html>
