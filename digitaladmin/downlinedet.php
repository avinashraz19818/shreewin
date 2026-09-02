<?php
	session_start();
	if (empty($_SESSION['unohs'])) {
		header("location: api/login.php?msg=unauthorized");
		exit();
	}

	include("api/conn.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $user_id = intval($_POST['user_id']);
    if ($user_id <= 0) {
        die("Invalid User ID");
    }

    // Fetch user details
    $query = "SELECT * FROM shonu_subjects WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($user_result) {
        $today = date('Y-m-d');

    
        $query = "SELECT motta, dinankavannuracisi FROM thevani WHERE balakedara = ? ORDER BY dinankavannuracisi ASC";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $deposit_result = $stmt->get_result();

        $deposits = [];
        $total_deposit = $today_deposit = 0;
        while ($row = $deposit_result->fetch_assoc()) {
            $deposits[] = $row['motta'];
            $total_deposit += $row['motta'];
            if (date('Y-m-d', strtotime($row['dinankavannuracisi'])) === $today) {
                $today_deposit += $row['motta'];
            }
        }
        $stmt->close();

      
        list($first_deposit, $second_deposit, $third_deposit) = array_pad($deposits, 3, 'N/A');

    
        $query = "SELECT motta FROM hintegedukolli WHERE balakedara = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $withdrawal_result = $stmt->get_result();

        $withdrawals = [];
        $withdrawals_today = 0;
        while ($row = $withdrawal_result->fetch_assoc()) {
            $withdrawals[] = $row['motta'];
            if (date('Y-m-d', strtotime($row['dinankavannuracisi'])) === $today) {
                $withdrawals_today += $row['motta'];
            }
        }
        $stmt->close();

 
        list($first_withdrawal, $second_withdrawal, $third_withdrawal) = array_pad($withdrawals, 3, 'N/A');


        $total_downline_deposit = $total_downline_deposit_today = 0;
        $total_downline_withdrawal_today = 0;
        $downline_data = [];

        $codes = [
            'Code1' => $user_result['code1'],
            'Code2' => $user_result['code2'],
            'Code3' => $user_result['code3'],
            'Code4' => $user_result['code4'],
            'Code5' => $user_result['code5']
        ];

        foreach ($codes as $position => $own_code) {
            if ($own_code) {
                $query = "SELECT id FROM shonu_subjects WHERE owncode = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("s", $own_code);
                $stmt->execute();
                $downline_result = $stmt->get_result();

                while ($row = $downline_result->fetch_assoc()) {
                    $downline_id = $row['id'];

   
                    $query = "SELECT motta, dinankavannuracisi FROM thevani WHERE balakedara = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("i", $downline_id);
                    $stmt->execute();
                    $deposit_result = $stmt->get_result();

                    $down_deposits = [];
                    $down_total = $down_today = 0;

                    while ($row = $deposit_result->fetch_assoc()) {
                        $down_deposits[] = $row['motta'];
                        $down_total += $row['motta'];
                        if (date('Y-m-d', strtotime($row['dinankavannuracisi'])) === $today) {
                            $down_today += $row['motta'];
                        }
                    }
                    $stmt->close();

                    list($down_first, $down_second, $down_third) = array_pad($down_deposits, 3, 'N/A');

             
                    $query = "SELECT motta FROM hintegedukolli WHERE balakedara = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("i", $downline_id);
                    $stmt->execute();
                    $withdrawal_result = $stmt->get_result();

                    $down_withdrawals = [];
                    $down_withdrawals_today = 0;

                    while ($row = $withdrawal_result->fetch_assoc()) {
                        $down_withdrawals[] = $row['motta'];
                        $down_withdrawals_today += $row['motta'];
                    }
                    $stmt->close();

                    list($down_first_withdrawal, $down_second_withdrawal, $down_third_withdrawal) = array_pad($down_withdrawals, 3, 'N/A');

                    $total_downline_deposit += $down_total;
                    $total_downline_deposit_today += $down_today;
                    $total_downline_withdrawal_today += $down_withdrawals_today;

            
                    $downline_data[] = [
                        'position' => $position,
                        'id' => $downline_id,
                        'first_deposit' => $down_first,
                        'second_deposit' => $down_second,
                        'third_deposit' => $down_third,
                        'total_deposit' => $down_total,
                        'today_deposit' => $down_today,
                        'first_withdrawal' => $down_first_withdrawal,
                        'second_withdrawal' => $down_second_withdrawal,
                        'third_withdrawal' => $down_third_withdrawal,
                        'today_withdrawal' => $down_withdrawals_today
                    ];
                }
            }
        }
    }
}
?>
<!doctype html>
<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr"
	data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template" data-style="light">

<head>
	<meta charset="utf-8" />
	<meta name="viewport"
		content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
	<title>Web Settings</title>

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

	<!-- Custom Responsive CSS for setting-card -->
<style>
    /* Main card styling */
    .setting-card {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1.2rem 1.5rem;
        margin: 1.5rem 0;
        border-radius: 12px;
        background-color: #fff;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        flex-wrap: wrap;
        transition: all 0.3s ease-in-out;
    }

    .card-left {
        display: flex;
        align-items: center;
        gap: 1rem;
        flex: 1 1 300px;
    }

    .card-left .icon {
        font-size: 36px;
        color: #6366f1;
    }

    .card-left .title {
        font-weight: 600;
        font-size: 1.1rem;
        color: #111827;
    }

    .card-left .desc {
        font-size: 0.9rem;
        color: #6b7280;
        margin-top: 0.25rem;
    }

    .card-right {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-top: 1rem;
        flex: 0 0 auto;
    }

    /* Toggle Switch */
    .switch {
        position: relative;
        display: inline-block;
        width: 48px;
        height: 24px;
    }

    .switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .slider {
        position: absolute;
        cursor: pointer;
        inset: 0;
        background-color: #d1d5db;
        transition: 0.4s;
        border-radius: 24px;
    }

    .slider:before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: 0.4s;
        border-radius: 50%;
    }

    input:checked + .slider {
        background-color: #6366f1;
    }

    input:checked + .slider:before {
        transform: translateX(24px);
    }

    .status {
        font-weight: 600;
        font-size: 0.95rem;
        color: <?= $wingo30status === 'active' ? '#10b981' : '#ef4444' ?>;
    }

    /* Form Styling */
    form input[type="number"] {
        padding: 0.6rem 0.8rem;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 0.95rem;
        width: 220px;
        margin-right: 1rem;
    }

    form button[type="submit"] {
        padding: 0.6rem 1.2rem;
        background-color: #6366f1;
        color: #fff;
        border: none;
        border-radius: 8px;
        font-weight: 500;
        font-size: 0.95rem;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    form button[type="submit"]:hover {
        background-color: #4f46e5;
    }

    /* Box / Container Styling */
    .container {
        display: flex;
        flex-wrap: wrap;
        gap: 1.5rem;
        margin-top: 2rem;
    }

    .box {
        flex: 1 1 45%;
        padding: 1.5rem;
        border-radius: 10px;
        box-shadow: 0 0 10px rgb(0 0 0 / 25%);
    }

    /* Table Styling */
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 1rem;
        font-size: 0.95rem;
    }

    table th, table td {
        padding: 0.75rem;
        text-align: center;
    }

    table th {
        font-weight: 600;
        color: #fff;
    }

    table tr:nth-child(even) {
        background-color: #f9fafb;
    }

    table tr:hover {
        background-color: #f3f4f6;
    }

    /* Responsive Design */
    @media (max-width: 992px) {
        .setting-card {
            flex-direction: column;
            align-items: flex-start;
        }

        .card-right {
            margin-top: 1rem;
        }

        .container {
            flex-direction: column;
        }

        .box {
            width: 100%;
        }

        form input[type="number"], form button[type="submit"] {
            width: 100%;
            margin: 0.5rem 0;
        }
    }
</style>

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
    
          <div class="content-wrapper">
            <div class="container-xxl flex-grow-1 container-p-y">
    
              <div class="card">
                <div class="table-responsive text-nowrap">
    
                   <div class="card shadow-sm p-4">
                   <h2>Check User Downline Deposit & Withdrawal Details</h2>
                        <form method="POST" style="width: 100%;">
                            <input type="number" name="user_id" placeholder="Enter User ID" required>
                            <button type="submit" name="submit">Check</button>
                        </form>
                   
                
                    <?php if (isset($user_result)) : ?>
                        <div class="container">
                            <div class="box">
                                <h3>Searched User Details</h3>
                                <p><strong>ID:</strong> <?php echo $user_result['id']; ?></p>
                                <p><strong>Own Code:</strong> <?php echo $user_result['owncode']; ?></p>
                                <h4>Deposit Summary</h4>
                                <p><strong>First Deposit:</strong> <?php echo $first_deposit ?? 'N/A'; ?></p>
                                <p><strong>Second Deposit:</strong> <?php echo $second_deposit ?? 'N/A'; ?></p>
                                <p><strong>Third Deposit:</strong> <?php echo $third_deposit ?? 'N/A'; ?></p>
                                <p><strong>Total Deposits:</strong> <?php echo $total_deposit; ?></p>
                                <p><strong>Today's Deposits:</strong> <?php echo $today_deposit; ?></p>
                                <h4>Withdrawal Summary</h4>
                                <p><strong>First Withdrawal:</strong> <?php echo $first_withdrawal ?? 'N/A'; ?></p>
                                <p><strong>Second Withdrawal:</strong> <?php echo $second_withdrawal ?? 'N/A'; ?></p>
                                <p><strong>Third Withdrawal:</strong> <?php echo $third_withdrawal ?? 'N/A'; ?></p>
                                <p><strong>Total Withdrawals Today:</strong> <?php echo $withdrawals_today; ?></p>
                            </div>
                
                            <div class="box">
                                <h3>Downline Summary</h3>
                                <p><strong>Total Deposits:</strong> <?php echo $total_downline_deposit; ?></p>
                                <p><strong>Total Withdrawals:</strong> <?php echo $withdrawals_today; ?></p>
                                <p><strong>Total Downline Deposits Today:</strong> <?php echo $total_downline_deposit_today; ?></p>
                                <p><strong>Total Downline Withdrawals Today:</strong> <?php echo $total_downline_withdrawal_today; ?></p>
                            </div>
                        </div>
                
                        <div class="container">
                            <div class="box" style="width: 100%">
                                <h3>Downline Deposits & Withdrawals</h3>
                                <table>
                                    <tr>
                                        <th>Position</th>
                                        <th>ID</th>
                                        <th>First Deposit</th>
                                        <th>Second Deposit</th>
                                        <th>Third Deposit</th>
                                        <th>Total Deposits</th>
                                        <th>Today's Deposits</th>
                                        <th>First Withdrawal</th>
                                        <th>Second Withdrawal</th>
                                        <th>Third Withdrawal</th>
                                        <th>Today's Withdrawals</th>
                                    </tr>
                                    <?php foreach ($downline_data as $downline) : ?>
                                        <tr>
                                            <td><?php echo $downline['position']; ?></td>
                                            <td><?php echo $downline['id']; ?></td>
                                            <td><?php echo $downline['first_deposit']; ?></td>
                                            <td><?php echo $downline['second_deposit']; ?></td>
                                            <td><?php echo $downline['third_deposit']; ?></td>
                                            <td><?php echo $downline['total_deposit']; ?></td>
                                            <td><?php echo $downline['today_deposit']; ?></td>
                                            <td><?php echo $downline['first_withdrawal']; ?></td>
                                            <td><?php echo $downline['second_withdrawal']; ?></td>
                                            <td><?php echo $downline['third_withdrawal']; ?></td>
                                            <td><?php echo $downline['today_withdrawal']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
         </div>
    </div>
     <?php include("footer.php"); ?>
    <div class="content-backdrop fade"></div>
  </div>
</div>
</div>

<div class="layout-overlay layout-menu-toggle"></div>
<div class="drag-target"></div>
</div>


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

</body>

</html>