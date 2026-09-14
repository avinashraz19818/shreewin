<?php
session_start();

if (empty($_SESSION['unohs'])) {
    header("location: api/login.php?msg=unauthorized");
    exit;
}

include("api/conn.php");

$Result = null;

if (!empty($_GET['id'])) {
    $id = encryptor('decrypt', $_GET['id']);
    if ($id) {
        $safe_id = mysqli_real_escape_string($conn, $id);
        $Query = mysqli_query($conn, "
            SELECT 
                shonu_subjects.id,
                shonu_subjects.email,
                shonu_subjects.owncode,
                khate.phalanubhavi,
                khate.kod,
                khate.khatehesaru,
                khate.khatesankhye,
                hintegedukolli.shonu,
                hintegedukolli.motta,
                hintegedukolli.khateshonu,
                hintegedukolli.sthiti,
                hintegedukolli.dinankavannuracisi
            FROM hintegedukolli
            INNER JOIN shonu_subjects ON shonu_subjects.id = hintegedukolli.balakedara
            INNER JOIN khate ON khate.shonu = hintegedukolli.khateshonu
            WHERE hintegedukolli.shonu = '$safe_id'
        ");

        if ($Query && mysqli_num_rows($Query) > 0) {
            $Result = mysqli_fetch_array($Query);
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
    <title>Withdraw Action USDT</title>

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
                    <div class="container-xxl flex-grow-1 container-p-y row">
                        <div class="col-3"></div>
                        <div class="px-5 col-6">
                            <div class="text-center mb-6">
                                <h4 class="mb-2">Withdraw Action</h4>
                                <p>Reject or Accept the Withdrawal Request</p>
                            </div>

                            <?php if ($Result): ?>
                                <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom py-4 mb-4">
                                    <h6 class="m-0 mb-2 mb-sm-0 me-12">UID</h6>
                                    <div><?= htmlspecialchars($Result['id']) ?></div>
                                </div>
                                <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom py-4 mb-4">
                                    <h6 class="m-0 mb-2 mb-sm-0 me-12">Date</h6>
                                    <div><?= date('d-m-Y H:i:s', strtotime($Result['dinankavannuracisi'])) ?></div>
                                </div>
                                <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom py-4 mb-4">
                                    <h6 class="m-0 mb-2 mb-sm-0 me-12">Amount</h6>
                                    <div><?= '$' . number_format($Result['motta'] / 93, 2) ?></div>
                                </div>
                                <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom py-4 mb-4">
                                    <h6 class="m-0 mb-2 mb-sm-0 me-12">Payment type</h6>
                                    <div><?= htmlspecialchars($Result['khatehesaru']) ?></div>
                                </div>
                                <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between py-4 mb-4">
                                    <h6 class="m-0 mb-2 mb-sm-0 me-12">USDT Address</h6>
                                    <div><?= htmlspecialchars($Result['khatesankhye']) ?></div>
                                </div>
                                <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between py-4 mb-4 gap-3">
                                    <button type="button" class="btn btn-outline-secondary w-100"
                                        data-bs-toggle="modal"
                                        onclick="addLabel('<?= $Result['shonu'] ?>','Reject')"
                                        data-bs-target="#withdraw-action-modal">
                                        <span>Reject</span>
                                    </button>
                                    <button type="button" class="btn btn-primary w-100"
                                        data-bs-toggle="modal"
                                        onclick="addLabel('<?= $Result['shonu'] ?>','Accept')"
                                        data-bs-target="#withdraw-action-modal">
                                        <span>Accept</span><i class="ri-check-line ms-1"></i>
                                    </button>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-danger text-center">Invalid or missing record!</div>
                            <?php endif; ?>
                        </div>
                        <div class="col-3"></div>

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
                                                    <input type="hidden" id="id" />
                                                    <input type="hidden" id="type" />
                                                    <input type="text" id="remark" name="remark" class="form-control" placeholder="Enter Remark" />
                                                    <label for="remark">Remark</label>
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

    <script>
        function addLabel(id, type) {
            $('#id').val(id);
            document.getElementById('exampleModalLabel1').innerText = type;
            document.getElementById('modal-button').innerText = type;
            $('#type').val(type.toLowerCase());
        }

        function handleAction() {
            var Id = $('#id').val();
            var Type = $('#type').val();
            var Remark = $('#remark').val();

            $.ajax({
                type: "POST",
                data: { id: Id, type: Type, remark: Remark },
                url: "api/withdraw-action-usdt.php",
                success: function (html) {
                    if (html == 1) {
                        window.location.href = 'withdrawsend-usdt.php';
                    } else if (html == 2) {
                        window.location.href = 'withdrawreject.php';
                    } else {
                        alert(html);
                    }
                },
                error: function (e) {
                    alert("Something went wrong.");
                }
            });
        }
    </script>
</body>
</html>
