<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (empty($_SESSION['unohs'])) {
    header("location: api/login.php?msg=unauthorized");
    exit;
}

include("api/conn.php");

$message = '';
$message_type = ''; // success / danger

if (isset($_POST['delete_transaction'])) {
    $transaction_id = mysqli_real_escape_string($conn, $_POST['transaction_id']);

    // Get user ID and amount for this transaction
    $get_user_sql = "SELECT userid, amount, transaction_type FROM user_extra_funds WHERE id = '$transaction_id' LIMIT 1";
    $result_user = mysqli_query($conn, $get_user_sql);
    $row_user = mysqli_fetch_assoc($result_user);

    if ($row_user) {
        $userid = $row_user['userid'];
        $amount = $row_user['amount'];
        $transaction_type = strtolower($row_user['transaction_type']);

        // Delete transaction
        $delete_sql = "DELETE FROM user_extra_funds WHERE id = '$transaction_id'";
        if (mysqli_query($conn, $delete_sql)) {

            // Adjust turnover based on deleted transaction type
            if ($transaction_type === 'credit') {
                $update_turnover = "
                    UPDATE shonu_kaichila 
                    SET turnover = turnover - $amount 
                    WHERE balakedara = '$userid'
                ";
            } elseif ($transaction_type === 'debit') {
                $update_turnover = "
                    UPDATE shonu_kaichila 
                    SET turnover = turnover + $amount 
                    WHERE balakedara = '$userid'
                ";
            }

            mysqli_query($conn, $update_turnover);

            $message = "Transaction deleted and turnover updated successfully!";
            $message_type = "success";
        } else {
            $message = "Failed to delete transaction!";
            $message_type = "danger";
        }
    } else {
        $message = "Transaction not found!";
        $message_type = "danger";
    }
}

if (isset($_POST['submit'])) {
    $userid = mysqli_real_escape_string($conn, $_POST['userid']);
    $amount = mysqli_real_escape_string($conn, $_POST['amount']);
    $transaction_type = mysqli_real_escape_string($conn, $_POST['transaction_type']); // credit or debit

    if (empty($userid) || empty($amount) || empty($transaction_type)) {
        $message = "All fields are required!";
        $message_type = "danger";
    } elseif (!is_numeric($amount) || $amount <= 0) {
        $message = "Please enter a valid amount!";
        $message_type = "danger";
    } else {
        // Insert transaction
        $sql = "INSERT INTO user_extra_funds (userid, amount, transaction_type) 
                VALUES ('$userid', '$amount', '$transaction_type')";

        if (mysqli_query($conn, $sql)) {

            // Update turnover based on transaction type
            if (strtolower($transaction_type) === 'credit') {
                $update_turnover = "
                    UPDATE shonu_kaichila 
                    SET turnover = turnover + $amount 
                    WHERE balakedara = '$userid'
                ";
            } elseif (strtolower($transaction_type) === 'debit') {
                $update_turnover = "
                    UPDATE shonu_kaichila 
                    SET turnover = turnover - $amount 
                    WHERE balakedara = '$userid'
                ";
            }

            mysqli_query($conn, $update_turnover);

            $message = "Transaction completed successfully!";
            $message_type = "success";
        } else {
            $message = "Transaction failed!";
            $message_type = "danger";
        }
    }
}

$transactions = mysqli_query($conn, "SELECT * FROM user_extra_funds ORDER BY created_at DESC");
?>


<!doctype html>
<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr"
    data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template" data-style="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Neet To Bet</title>
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
                            <div class="d-flex justify-content-between align-items-center px-4" style="margin-top: 20px;">
                            <div class="d-flex align-items-center">
                                <h4 class="font-weight-bold text-dark mb-0">Manage Illegal Bet</h4>
                                <?php if (!empty($message)): ?>
                                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                                    <?php echo $message; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-body">
                                        <form action="#" method="post" autocomplete="off">
                                            <div class="form-group" >
                                                <label for="userid">User ID</label>
                                                <input type="number" name="userid" id="userid" class="form-control cool-input" required placeholder="Enter User ID">
                                            </div>
                                            <div class="form-group" style="margin-top: 20px;">
                                                <label for="amount">Amount</label>
                                                <input type="number" step="0.01" name="amount" id="amount" class="form-control cool-input" required placeholder="Enter Amount">
                                            </div>
                                            <div class="form-group" style="margin-top: 20px;">
                                                <label>Transaction Type</label>
                                                <div class="d-flex gap-4 flex-wrap">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="transaction_type" value="credit" id="creditType" checked>
                                                        <label class="form-check-label" for="creditType">Increase Balance</label>
                                                    </div>
                                                    <div class="form-check ml-3">
                                                        <input class="form-check-input" type="radio" name="transaction_type" value="debit" id="debitType">
                                                        <label class="form-check-label" for="debitType">Decrease Balance</label>
                                                    </div>
                                                </div>
                                            </div>
                                            <button type="submit" name="submit" class="btn btn-primary cool-button mt-3">Process Transaction</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-body">
                                        <h4 class="card-title">Transaction History</h4>
                                        <div class="table-responsive">
                                            <table id="transactionTable" class="table table-hover">
                                                <thead class="thead-light">
                                                    <tr>
                                                        <th>ID</th>
                                                        <th>User ID</th>
                                                        <th>Amount</th>
                                                        <th>Type</th>
                                                        <th>Date</th>
                                                        <th>Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php while($row = mysqli_fetch_assoc($transactions)): ?>
                                                    <tr>
                                                        <td><?php echo $row['id']; ?></td>
                                                        <td><?php echo $row['userid']; ?></td>
                                                        <td>₹<?php echo number_format($row['amount'], 2); ?></td>
                                                        <td class="<?php echo $row['transaction_type'] == 'credit' ? 'text-success' : 'text-danger'; ?>">
                                                            <?php echo $row['transaction_type'] == 'credit' ? 'Increase' : 'Decrease'; ?>
                                                        </td>
                                                        <td><?php echo date('Y-m-d H:i:s', strtotime($row['created_at'])); ?></td>
                                                        <td>
                                                            <form method="POST" onsubmit="return confirm('Are you sure you want to delete this transaction?');">
                                                                <input type="hidden" name="transaction_id" value="<?php echo $row['id']; ?>">
                                                                <button type="submit" name="delete_transaction" class="btn btn-sm btn-outline-danger">
                                                                    <i class="mdi mdi-delete"></i> Delete
                                                                </button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                    <?php endwhile; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div></div></div>
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
        $(document).ready(function() {
            $('#transactionTable').DataTable({
                order: [[0, 'desc']],
                pageLength: 10,
                responsive: true,
                columnDefs: [
                    {
                        targets: -1,
                        orderable: false
                    }
                ]
            });
        });
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
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