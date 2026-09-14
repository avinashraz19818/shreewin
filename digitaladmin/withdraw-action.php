<?php
session_start();
if (!isset($_SESSION['unohs'])) {
    header("location: api/login.php?msg=unauthorized");
    exit;
}
include("api/conn.php");
require_once __DIR__ . '/api/manual-withdraw-helper.php';

// AJAX actions from the Processing queue use this same file.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $withdrawId = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $action = strtolower(trim((string)($_POST['type'] ?? '')));
    $remark = trim((string)($_POST['remark'] ?? ''));
    [$ok, $message] = admin_manual_withdraw_action($conn, $withdrawId, $action, $remark);
    if (!$ok) {
        echo '0';
    } elseif ($action === 'accept') {
        echo '1';
    } elseif ($action === 'reject') {
        echo '2';
    } elseif ($action === 'processing') {
        echo '3';
    } else {
        echo '0';
    }
    exit;
}

// Fetch withdrawal details
$decryptedId = null;
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $decryptedId = encryptor('decrypt', $_GET['id']);
    if (!$decryptedId) {
        header("location: withdrawapply.php?msg=invalid_id");
        exit;
    }
    $Query = mysqli_query($conn, "SELECT shonu_subjects.mobile, shonu_subjects.email, shonu_subjects.owncode, 
        khate.phalanubhavi, khate.kod, khate.khatehesaru, khate.khatesankhye, 
        hintegedukolli.shonu, hintegedukolli.motta, hintegedukolli.khateshonu, 
        hintegedukolli.sthiti, hintegedukolli.dinankavannuracisi 
        FROM hintegedukolli 
        INNER JOIN shonu_subjects ON shonu_subjects.id = hintegedukolli.balakedara 
        INNER JOIN khate ON khate.shonu = hintegedukolli.khateshonu 
        WHERE hintegedukolli.shonu = '$decryptedId'");
    $Result = mysqli_fetch_array($Query);
    if (!$Result) {
        header("location: withdrawapply.php?msg=no_record");
        exit;
    }
} else {
    header("location: withdrawapply.php?msg=missing_id");
    exit;
}
?>

<!doctype html>
<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr"
    data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template" data-style="light">
<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Withdraw Action</title>
    <meta name="description" content="" />
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/img/favicon/favicon.ico?v=20260814-admin" />
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap"
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
    <!-- Helpers -->
    <script src="assets/vendor/libs/jquery/jquery.js"></script>
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
                    <div class="container-xxl flex-grow-1 container-p-y row">
                        <div class="col-3"></div>
                        <div class="px-5 col-6">
                            <div class="text-center mb-6">
                                <h4 class="mb-2">Withdraw Action</h4>
                                <p>Reject or Accept the Withdrawal Request</p>
                            </div>
                            <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom py-4 mb-4">
                                <h6 class="m-0 mb-2 mb-sm-0 me-12">Name</h6>
                                <div class="d-flex flex-wrap gap-4">
                                    <?= htmlspecialchars($Result['phalanubhavi']); ?>
                                </div>
                            </div>
                            <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom py-4 mb-4">
                                <h6 class="m-0 mb-2 mb-sm-0 me-12">Mobile</h6>
                                <div class="d-flex flex-wrap gap-4">
                                    <?= htmlspecialchars($Result['mobile']); ?>
                                </div>
                            </div>
                            <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom py-4 mb-4">
                                <h6 class="m-0 mb-2 mb-sm-0 me-12">Date</h6>
                                <div class="d-flex flex-wrap gap-4">
                                    <?= date('d-m-Y H:i:s', strtotime($Result['dinankavannuracisi'])); ?>
                                </div>
                            </div>
                            <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom py-4 mb-4">
                                <h6 class="m-0 mb-2 mb-sm-0 me-12">Amount</h6>
                                <div class="d-flex flex-wrap gap-4">
                                    <?= number_format($Result['motta'], 2); ?>
                                </div>
                            </div>
                            <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom py-4 mb-4">
                                <h6 class="m-0 mb-2 mb-sm-0 me-12">Bank Name</h6>
                                <div class="d-flex flex-wrap gap-4">
                                    <?= htmlspecialchars($Result['khatehesaru']); ?>
                                </div>
                            </div>
                            <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom py-4 mb-4">
                                <h6 class="m-0 mb-2 mb-sm-0 me-12">IFSC Code</h6>
                                <div class="d-flex flex-wrap gap-4">
                                    <?= htmlspecialchars($Result['kod']); ?>
                                </div>
                            </div>
                            <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between py-4 mb-4">
                                <h6 class="m-0 mb-2 mb-sm-0 me-12">Account Number</h6>
                                <div class="d-flex flex-wrap gap-4">
                                    <?= htmlspecialchars($Result['khatesankhye']); ?>
                                </div>
                            </div>
                            <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between py-4 mb-4 gap-3">
                                <button type="button" class="btn btn-outline-secondary w-100" 
                                    data-bs-toggle="modal" 
                                    onclick="addLabel('<?= htmlspecialchars($decryptedId); ?>','Reject')" 
                                    data-bs-target="#withdraw-action-modal">
                                    <span class="align-middle">Reject</span>
                                </button>
                                <button type="button" class="btn btn-warning w-100"
                                    data-bs-toggle="modal"
                                    onclick="addLabel('<?= htmlspecialchars($decryptedId); ?>','Processing')"
                                    data-bs-target="#withdraw-action-modal">
                                    <span class="align-middle">Processing</span>
                                    <i class="ri-loader-4-line ms-1"></i>
                                </button>
                                <button type="button" class="btn btn-primary w-100" 
                                    data-bs-toggle="modal" 
                                    onclick="addLabel('<?= htmlspecialchars($decryptedId); ?>','Accept')" 
                                    data-bs-target="#withdraw-action-modal">
                                    <span class="align-middle">Accept</span>
                                    <i class="ri-check-line ms-1"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-3"></div>
                        <!-- Modal -->
                        <div class="modal fade" id="withdraw-action-modal" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h4 class="modal-title" id="exampleModalLabel1"></h4>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="row">
                                            <div class="col mb-6 mt-2">
                                                <div class="form-floating form-floating-outline">
                                                    <input type="hidden" id="id" value="<?= htmlspecialchars($decryptedId); ?>"/>
                                                    <input type="hidden" id="type"/>
                                                    <input type="text" id="remark" name="remark" class="form-control" placeholder="Enter Remark" />
                                                    <label for="remark">Remark</label>
                                                </div>
                                            </div>
                                            <div class="col mb-6 mt-2" id="api-toggle" style="display: none;">
                                                <div class="form-floating form-floating-outline">
                                                    <select id="apiChoice" name="apiChoice" class="form-select">
                                                        <option value="current">Rupee Rush</option>
                                                        <option value="rupeerush">Silk Pay</option>
                                                    </select>
                                                    <label for="apiChoice">Select Payout API</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                                        <button type="button" class="btn btn-primary" id="modal-button" onclick="handleAction()"></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Footer -->
                    <?php require_once("footer.php"); ?>
                    <!-- / Footer -->
                    <div class="content-backdrop fade"></div>
                </div>
            </div>
        </div>
        <div class="layout-overlay layout-menu-toggle"></div>
        <div class="drag-target"></div>
    </div>
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
        function addLabel(id, type) {
            $('#id').val(id);
            document.getElementById('exampleModalLabel1').innerText = type;
            document.getElementById('modal-button').innerText = type;
            $('#type').val(type.toLowerCase());
            // Show API toggle only for Accept action
            if (type.toLowerCase() === 'accept') {
                $('#api-toggle').show();
            } else {
                $('#api-toggle').hide();
            }
        }

        function handleAction() {
            var Id = $('#id').val();
            var Type = $('#type').val();
            var Remark = $('#remark').val();
            var ApiChoice = $('#apiChoice').val();
            
            if (!Id) {
                alert('Error: Withdrawal ID is missing');
                return;
            }

            // Determine URL based on action and API choice
            var url = 'api/withdraw-action.php';
            if (Type === 'accept' && ApiChoice === 'current') {
                url = 'payout-process.php';
            }

            $.ajax({
                type: "POST",
                url: url,
                data: { id: Id, type: Type, remark: Remark, apiChoice: ApiChoice },
                dataType: "json",
                success: function (response) {
                    if (response.status == 1) {
                        window.location.href = 'withdrawsent.php';
                    } else if (response.status == 2) {
                        window.location.href = 'withdrawreject.php';
                    } else if (response.status == 3) {
                        window.location.href = 'withdrawapply.php';
                    } else {
                        alert(response.message || 'An error occurred. Please try again.');
                    }
                },
                error: function (e) {
                    alert('AJAX error: ' + e.statusText);
                }
            });
        }
    </script>
</body>
</html>