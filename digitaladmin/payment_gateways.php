<?php
session_start();
if(empty($_SESSION['unohs'])){
    header("location: api/login.php?msg=unauthorized");
}
include ("api/conn.php");

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update gateway order
    if (isset($_POST['update_order']) && !empty($_POST['sort_order'])) {
        foreach ($_POST['sort_order'] as $id => $order) {
            if (is_numeric($id) && is_numeric($order)) {
                DB_update("UPDATE payment_channels SET sort_order = ? WHERE id = ?", [$order, $id]);
            }
        }
        $success = "Gateway order updated successfully!";
    }
    
    // Toggle gateway status
    if (isset($_POST['toggle_status']) && !empty($_POST['id'])) {
        $id = (int)$_POST['id'];
        $current = DB_selectOne("SELECT is_active FROM payment_channels WHERE id = ?", [$id]);
        
        if ($current && isset($current['is_active'])) {
            $newStatus = $current['is_active'] ? 0 : 1;
            $result = DB_update("UPDATE payment_channels SET is_active = ? WHERE id = ?", [$newStatus, $id]);
            
            if ($result) {
                $success = "Gateway status updated successfully!";
            } else {
                $error = "Failed to update gateway status";
            }
        }
    }
}

// Get all gateways
$gateways = DB_select("
    SELECT * FROM payment_channels 
    WHERE payID = 2
    ORDER BY sort_order ASC
");

// Debugging - check if gateways are fetched
if (!$gateways) {
    $error = "No gateways found in database. Please check your payment_channels table.";
}
?>

<!doctype html>
<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr"
    data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template" data-style="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

    <title>Payment Gateways Management</title>

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

    <!-- Helpers -->
    <script src="assets/vendor/js/helpers.js"></script>
    <script src="assets/vendor/js/template-customizer.js"></script>
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
                        <h4 class="fw-bold py-3 mb-4">
                            <span class="text-muted fw-light">Payment /</span> Gateways Management
                        </h4>

                        <?php if (isset($success)): ?>
                        <div class="alert alert-success alert-dismissible" role="alert">
                            <?php echo $success; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (isset($error)): ?>
                        <div class="alert alert-danger alert-dismissible" role="alert">
                            <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php endif; ?>

                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Payment Gateways</h5>
                                <a href="add_gateway.php" class="btn btn-primary">
                                    <i class="ri-add-line me-1"></i> Add New Gateway
                                </a>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="table-responsive">
                                        <table class="table datatables-basic">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Order</th>
                                                    <th>Gateway Name</th>
                                                    <th>Min/Max Amount</th>
                                                    <th>Status</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody class="table-border-bottom-0">
                                                <?php if ($gateways && is_array($gateways)): ?>
                                                    <?php foreach ($gateways as $index => $gateway): ?>
                                                        <?php if (!is_array($gateway)) continue; ?>
                                                        <tr class="<?php echo $gateway['is_active'] ? '' : 'disabled-gateway'; ?>">
                                                            <td><?php echo $index + 1; ?></td>
                                                            <td>
                                                                <input type="number" name="sort_order[<?php echo $gateway['id']; ?>]" 
                                                                    value="<?php echo $gateway['sort_order']; ?>" class="form-control form-control-sm" style="width: 80px;">
                                                            </td>
                                                            <td>
                                                                <strong><?php echo htmlspecialchars($gateway['payName']); ?></strong>
                                                            </td>
                                                            <td>
                                                                ₹<?php echo $gateway['miniPrice']; ?> - ₹<?php echo $gateway['maxPrice']; ?>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-<?php echo $gateway['is_active'] ? 'success' : 'danger'; ?> me-1">
                                                                    <?php echo $gateway['is_active'] ? 'Active' : 'Inactive'; ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <div class="d-flex">
                                                                    <form method="POST" class="me-2">
                                                                        <input type="hidden" name="id" value="<?php echo $gateway['id']; ?>">
                                                                        <button type="submit" name="toggle_status" class="btn btn-<?php echo $gateway['is_active'] ? 'danger' : 'success'; ?> btn-sm">
                                                                            <i class="ri-<?php echo $gateway['is_active'] ? 'close-line' : 'check-line'; ?> me-1"></i>
                                                                            <?php echo $gateway['is_active'] ? 'Disable' : 'Enable'; ?>
                                                                        </button>
                                                                    </form>
                                                                    <a href="edit_gateway.php?id=<?php echo $gateway['id']; ?>" class="btn btn-info btn-sm">
                                                                        <i class="ri-edit-line me-1"></i> Edit
                                                                    </a>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="6" class="text-center py-4">No payment gateways found</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="mt-3">
                                        <button type="submit" name="update_order" class="btn btn-primary">
                                            <i class="ri-save-line me-1"></i> Save Order
                                        </button>
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
    </div>
    <!-- / Layout wrapper -->

    <!-- Core JS -->
    <script src="assets/vendor/libs/jquery/jquery.js"></script>
    <script src="assets/vendor/libs/popper/popper.js"></script>
    <script src="assets/vendor/js/bootstrap.js"></script>
    <script src="assets/vendor/libs/node-waves/node-waves.js"></script>
    <script src="assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="assets/vendor/libs/hammer/hammer.js"></script>
    <script src="assets/vendor/libs/i18n/i18n.js"></script>
    <script src="assets/vendor/libs/typeahead-js/typeahead.js"></script>
    <script src="assets/vendor/js/menu.js"></script>

    <!-- Vendors JS -->
    <script src="assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js"></script>

    <!-- Main JS -->
    <script src="assets/js/main.js"></script>

    <script>
        $(function () {
            $('.datatables-basic').DataTable({
                "paging": true,
                "lengthChange": false,
                "searching": true,
                "ordering": false,
                "info": true,
                "autoWidth": false,
                "dom":
                    '<"card-header pb-0 pt-md-0"<"head-label"><"dt-action-buttons text-end"f>>t' +
                    '<"row mx-2"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
                "language": {
                    search: '',
                    searchPlaceholder: 'Search gateways...',
                    paginate: {
                        previous: '<i class="ri-arrow-left-line"></i>',
                        next: '<i class="ri-arrow-right-line"></i>'
                    }
                }
            });
        });
    </script>
</body>
</html>