<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (empty($_SESSION['unohs'])) {
    header("location: api/login.php?msg=unauthorized");
}
include("api/conn.php");

$message = "";

// Delete deduction record - MUST BE AT TOP BEFORE ANY OUTPUT
if (isset($_POST['delete_id'])) {
    $deleteId = intval($_POST['delete_id']);
    $userId = intval($_POST['user_id']);
    $amount = floatval($_POST['amount']);

    // Restore balance to user
    $restoreQuery = $conn->prepare("UPDATE shonu_kaichila SET motta = motta + ? WHERE balakedara = ?");
    $restoreQuery->bind_param("di", $amount, $userId);
    $restoreQuery->execute();

    // Delete record from deduction table using id column
    $deleteQuery = $conn->prepare("DELETE FROM balance_detuct_table WHERE id = ?");
    $deleteQuery->bind_param("i", $deleteId);
    $deleteSuccess = $deleteQuery->execute();

    if ($deleteSuccess) {
        echo json_encode(['success' => true, 'message' => 'Deduction record deleted and balance restored!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error deleting record!']);
    }
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['delete_id'])) {
    $userId = intval($_POST['user_id']);
    $amountToDeduct = floatval($_POST['amount']);
    $remark = trim($_POST['remark']);

    // Fetch current balance
    $balanceQuery = $conn->prepare("SELECT motta FROM shonu_kaichila WHERE balakedara = ?");
    $balanceQuery->bind_param("i", $userId);
    $balanceQuery->execute();
    $balanceResult = $balanceQuery->get_result();
    $userBalance = $balanceResult->fetch_assoc()['motta'] ?? 0;

    if ($amountToDeduct > 0 && $userBalance >= $amountToDeduct) {
        // Deduct balance
        $deductQuery = $conn->prepare("UPDATE shonu_kaichila SET motta = motta - ? WHERE balakedara = ?");
        $deductQuery->bind_param("di", $amountToDeduct, $userId);
        $deductQuery->execute();

        // Insert log with processed = 1 (Succeed)
        $serial = "Daman Pro Admin";
        $processed = 1;
        $insertQuery = $conn->prepare("INSERT INTO balance_detuct_table (userkani, price, serial, shonu, remark, processed) VALUES (?, ?, ?, NOW(), ?, ?)");
        $insertQuery->bind_param("idssi", $userId, $amountToDeduct, $serial, $remark, $processed);
        $insertQuery->execute();

        $message = "<div class='alert alert-success alert-dismissible' role='alert'>
            ✅ Balance Deducted Successfully!
            <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
        </div>";

        // PREVENT FORM RESUBMISSION - Redirect to same page
        header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
        exit;
    } else {
        $message = "<div class='alert alert-danger alert-dismissible' role='alert'>
            ❌ Insufficient Balance or Invalid Amount!
            <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
        </div>";
    }
}

// Show success message from redirect
if (isset($_GET['success'])) {
    $message = "<div class='alert alert-success alert-dismissible' role='alert'>
        ✅ Balance Deducted Successfully!
        <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
    </div>";
}
?>

<!doctype html>
<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr"
    data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template" data-style="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Balance Deduction</title>
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

                        <!-- Success/Error Messages -->
                        <?php echo $message; ?>

                        <!-- Balance Deduction Form -->
                        <div class="row mb-6">
                            <div class="col-2"></div>
                            <div class="col-sm-12 mb-6 mb-md-0">
                                <div class="card">
                                    <h5 class="card-header">Balance Deduction</h5>
                                    <div class="card-body">
                                        <form class="browser-default-validation" method="POST" id="deductionForm">
                                            <div class="form-floating form-floating-outline mb-6">
                                                <input type="text" class="form-control" id="user_id" placeholder="User ID"
                                                    name="user_id" required />
                                                <label for="user_id">User ID</label>
                                            </div>
                                            <div class="form-floating form-floating-outline mb-6">
                                                <input type="text" name="amount" id="amount" class="form-control"
                                                    placeholder="Amount to Deduct" required />
                                                <label for="amount">Amount to Deduct</label>
                                            </div>
                                            <div class="form-floating form-floating-outline mb-6">
                                                <textarea class="form-control h-px-75" id="remark" name="remark"
                                                    placeholder="Remarks" rows="3" required></textarea>
                                                <label for="remark">Remarks</label>
                                            </div>
                                            <div class="row">
                                                <div class="col-6">
                                                    <button type="reset" class="btn btn-transparent border-primary w-100">Reset</button>
                                                </div>
                                                <div class="col-6">
                                                    <button type="submit" class="btn btn-danger w-100">Deduct Balance</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <div class="col-2"></div>
                        </div>

                        <!-- Recent Deductions History -->
                        <div class="card">
                            <h5 class="card-header">Recent Deductions</h5>
                            <div class="table-responsive text-nowrap">
                                <table class="table table-hover datatables-deductions">
                                    <thead>
                                        <tr>
                                            <th>Sr.No</th>
                                            <th>User ID</th>
                                            <th>Amount</th>
                                            <th>Remarks</th>
                                            <th>Date & Time</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="table-border-bottom-0">
                                        <?php
                                        // Fetch recent deductions
                                        $historyQuery = "SELECT id, userkani, price, remark, shonu, processed 
                                                        FROM balance_detuct_table 
                                                        ORDER BY shonu DESC 
                                                        LIMIT 50";
                                        $historyResult = mysqli_query($conn, $historyQuery);

                                        // Check if query was successful
                                        if ($historyResult === false) {
                                            echo '<tr><td colspan="7" class="text-center">Error: ' . mysqli_error($conn) . '</td></tr>';
                                        } else {
                                            $i = 0;
                                            if (mysqli_num_rows($historyResult) > 0) {
                                                while ($row = mysqli_fetch_assoc($historyResult)) {
                                                    $i++;
                                                    $status = $row['processed'] == 1 ?
                                                        '<span class="badge bg-label-success">Succeed</span>' :
                                                        '<span class="badge bg-label-warning">Pending</span>';
                                        ?>
                                                    <tr>
                                                        <td><?= $i ?></td>
                                                        <td><?= $row['userkani'] ?></td>
                                                        <td>₹<?= number_format($row['price'], 2) ?></td>
                                                        <td><?= $row['remark'] ?></td>
                                                        <td><?= date('d/m/Y h:i A', strtotime($row['shonu'])) ?></td>
                                                        <td><?= $status ?></td>
                                                        <td>
                                                            <button class="btn btn-danger btn-sm delete-deduction"
                                                                data-id="<?= $row['id'] ?>"
                                                                data-user="<?= $row['userkani'] ?>"
                                                                data-amount="<?= $row['price'] ?>">
                                                                <i class="ri-delete-bin-line"></i> Delete
                                                            </button>
                                                        </td>
                                                    </tr>
                                        <?php
                                                }
                                            } else {
                                                echo '<tr><td colspan="7" class="text-center">No deduction history found.</td></tr>';
                                            }
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

    <!-- Scripts -->
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
            $('.datatables-deductions').DataTable({
                paging: true,
                lengthChange: true,
                searching: true,
                ordering: true,
                info: true,
                autoWidth: true,
                pageLength: 10,
                dom: '<"row"' +
                    '<"col-md-12"<"d-flex align-items-center justify-content-md-end justify-content-center"<l><"me-4"f>>>' +
                    '>t' +
                    '<"row p-5"' +
                    '<"col-sm-12 col-md-6"i>' +
                    '<"col-sm-12 col-md-6"p>' +
                    '>',
                language: {
                    sLengthMenu: 'Show _MENU_',
                    search: '',
                    searchPlaceholder: 'Search User',
                    paginate: {
                        next: '<i class="ri-arrow-right-s-line"></i>',
                        previous: '<i class="ri-arrow-left-s-line"></i>'
                    },
                    emptyTable: "No deduction history found."
                },
                columnDefs: [{
                    targets: 6, // Actions column (7th column)
                    orderable: false,
                    searchable: false
                }]
            });

            // Delete deduction record
            $(document).on('click', '.delete-deduction', function() {
                var deductionId = $(this).data('id');
                var userId = $(this).data('user');
                var amount = $(this).data('amount');

                if (confirm('Are you sure you want to delete this deduction record?\nThis will restore ₹' + amount + ' to User ID: ' + userId)) {
                    $.ajax({
                        type: "POST",
                        url: "<?php echo $_SERVER['PHP_SELF']; ?>",
                        data: {
                            delete_id: deductionId,
                            user_id: userId,
                            amount: amount
                        },
                        success: function(response) {
                            try {
                                var result = JSON.parse(response);
                                if (result.success) {
                                    alert('✅ ' + result.message);
                                    location.reload();
                                } else {
                                    alert('❌ ' + result.message);
                                }
                            } catch (e) {
                                alert('❌ Error parsing response');
                            }
                        },
                        error: function(xhr, status, error) {
                            alert("Error: " + error);
                        }
                    });
                }
            });

            // Form submission handling
            $("#deductionForm").on('submit', function(e) {
                // Let the form submit normally for redirect to work
            });
        });
    </script>
</body>

</html>