<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (empty($_SESSION['unohs'])) {
    header("location: api/login.php?msg=unauthorized");
}
include("api/conn.php");

// Date filter handling
$dateFilter = isset($_GET['date']) ? date('Y-m-d', strtotime($_GET['date'])) : date('Y-m-d');

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

// Deduction handling
if (isset($_POST['deduct_id'])) {
    $price = $_POST['price'];
    $uid = mysqli_real_escape_string($conn, $_POST['uid']);
    $deduct_id = mysqli_real_escape_string($conn, $_POST['deduct_id']);
    $type = mysqli_real_escape_string($conn, $_POST['type']);

    // Deduct motta
    $deductMotta = "UPDATE shonu_kaichila SET motta = GREATEST(motta - $price, 0) WHERE balakedara = '$uid'";
    mysqli_query($conn, $deductMotta) or die(mysqli_error($conn));

    // Insert deduction record
    $date = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
    $currentTime = $date->format('Y-m-d H:i:s');
    $bonusDeduction = "INSERT INTO bonus_deduction (userkani, price, shonu, remark) 
                      VALUES ($uid, $price, '$currentTime', 'Salary Deduction')";
    mysqli_query($conn, $bonusDeduction) or die(mysqli_error($conn));

    // Delete from original table
    $tableMap = [
        3 => "hodike_balakedara",
        8 => "agent_red_envelope_recharge_table",
        10 => "recharge_gift_table",
        13 => "bonus_recharge_table",
        14 => "first_full_gift_table",
        20 => "invite_bonus_table",
        25 => "card_binding_gift_table",
        107 => "weekly_awards_table",
        124 => "agent_bonus_table",
        118 => "daily_awards_table",
        117 => "new_members_bonus_table",
        115 => "return_awards_table",
    ];

    if (isset($tableMap[$type])) {
        $table = $tableMap[$type];

        if ($type != 13) {
            $deleteRow = "DELETE FROM `$table` WHERE kani = '$deduct_id'";
        } else {
            $deleteRow = "UPDATE `$table` SET reffer = '1' WHERE kani = '$deduct_id'";
        }

        mysqli_query($conn, $deleteRow) or die(mysqli_error($conn));
    }

    header("Location: " . $_SERVER['PHP_SELF'] . "?date=$dateFilter");
    exit;
}

// Bonus assignment handling
if (isset($_POST['user_id']) && isset($_POST['type']) && isset($_POST['amount'])) {
    $userId = mysqli_real_escape_string($conn, $_POST['user_id']);
    $type = mysqli_real_escape_string($conn, $_POST['type']);
    $amount = floatval($_POST['amount']);
    $remark = mysqli_real_escape_string($conn, $_POST['remark'] ?? '');
    $addToTurnover = isset($_POST['turnover']) ? 1 : 0;

    // Get current balance
    $userQuery = mysqli_query($conn, "SELECT motta FROM shonu_kaichila WHERE balakedara = '$userId'");
    if (mysqli_num_rows($userQuery) > 0) {
        $userData = mysqli_fetch_assoc($userQuery);
        $currentBalance = $userData['motta'];
        $newBalance = $currentBalance + $amount;

        // Update user balance
        $updateBalance = "UPDATE shonu_kaichila SET motta = $newBalance WHERE balakedara = '$userId'";
        mysqli_query($conn, $updateBalance) or die(mysqli_error($conn));

        // Add to turnover if checkbox is checked
        if ($addToTurnover) {
            $updateTurnover = "UPDATE shonu_kaichila SET mottta = mottta + $amount WHERE balakedara = '$userId'";
            mysqli_query($conn, $updateTurnover) or die(mysqli_error($conn));
        }

        // Insert bonus record based on type
        $date = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
        $currentTime = $date->format('Y-m-d H:i:s');

        $tableMap = [
            3 => "hodike_balakedara",
            8 => "agent_red_envelope_recharge_table",
            10 => "recharge_gift_table",
            13 => "bonus_recharge_table",
            14 => "first_full_gift_table",
            20 => "invite_bonus_table",
            25 => "card_binding_gift_table",
            107 => "weekly_awards_table",
            124 => "agent_bonus_table",
            118 => "daily_awards_table",
            117 => "new_members_bonus_table",
            115 => "return_awards_table",
        ];

        if (isset($tableMap[$type])) {
            $table = $tableMap[$type];
            $insertBonus = "INSERT INTO `$table` (userkani, serial, price, shonu, remark) 
               VALUES ('$userId', 'Daman Pro Admin', $amount, '$currentTime', '$remark')";
            mysqli_query($conn, $insertBonus) or die(mysqli_error($conn));
        }

        header("Location: " . $_SERVER['PHP_SELF'] . "?date=$dateFilter&success=1");
        exit;
    } else {
        header("Location: " . $_SERVER['PHP_SELF'] . "?date=$dateFilter&error=User not found");
        exit;
    }
}

// Simple query without UNION ALL - just get data from one main table
$sql = "SELECT kani, userkani, serial, price, shonu, remark FROM hodike_balakedara 
        WHERE serial='Daman Pro Admin' AND DATE(shonu) = '$dateFilter' 
        ORDER BY shonu DESC";

$Query = mysqli_query($conn, $sql);

// DEBUG: Check if query executed successfully
if (!$Query) {
    echo "<div class='alert alert-danger'>Query Error: " . mysqli_error($conn) . "</div>";
    $hasData = false;
} else {
    $hasData = (mysqli_num_rows($Query) > 0);
}
?>

<!doctype html>
<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr"
    data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template" data-style="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Manage Bonus</title>
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
                        <?php if (isset($_GET['success'])): ?>
                            <div class="alert alert-success alert-dismissible" role="alert">
                                Bonus assigned successfully!
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($_GET['error'])): ?>
                            <div class="alert alert-danger alert-dismissible" role="alert">
                                <?= htmlspecialchars($_GET['error']) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Date Filter Section -->
                        <div class="card mb-4">
                            <div class="d-flex justify-content-between align-items-center p-4">
                                <h4 class="mb-0">Bonus Management</h4>
                                <!--<form action="" method="get" class="d-flex gap-3 align-items-center">-->
                                <!--    <input type="date" class="form-control" id="date" name="date" -->
                                <!--           value="<?= $dateFilter ?>" max="<?= date('Y-m-d') ?>">-->
                                <!--    <button type="submit" class="btn btn-primary">Filter Date</button>-->
                                <!--</form>-->
                            </div>
                        </div>

                        <!-- Bonus Assignment Form -->
                        <div class="row mb-6">
                            <div class="col-2"></div>
                            <div class=" col-sm-12 mb-6 mb-md-0">
                                <div class="card">
                                    <div class="d-flex justify-content-between align-items-center py-5">
                                        <h5 class="card-header">Assign Bonus To User</h5>
                                        <div class="mb-4 px-4">
                                            <label for="formFile" class="form-label">Choose excel file</label>
                                            <input class="form-control" type="file" id="excelFile" accept=".xls,.xlsx,.csv" />
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <form class="browser-default-validation" method="POST" id="bonusForm">
                                            <div class="form-floating form-floating-outline mb-6">
                                                <input type="text" class="form-control" id="basic-default-name" placeholder="User ID"
                                                    name="user_id" required />
                                                <label for="basic-default-name">User ID</label>
                                            </div>
                                            <div class="form-floating form-floating-outline mb-6">
                                                <select class="form-select" id="basic-default-country" required name="type">
                                                    <?php foreach ($bonusTypes as $typeId => $typeName): ?>
                                                        <option value="<?= $typeId ?>"><?= htmlspecialchars($typeName) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <label for="basic-default-country">Bonus Type</label>
                                            </div>
                                            <div class="form-floating form-floating-outline mb-6">
                                                <input type="text" name="amount" id="basic-default-email" class="form-control"
                                                    placeholder="Amount" required />
                                                <label for="basic-default-email">Amount</label>
                                            </div>
                                            <div class="form-floating form-floating-outline mb-6">
                                                <textarea class="form-control h-px-75" id="basic-default-bio" name="remark"
                                                    placeholder="Remarks" rows="3"></textarea>
                                                <label for="basic-default-bio">Remarks</label>
                                            </div>
                                            <div class="mb-4">
                                                <div class="form-check">
                                                    <input type="checkbox" class="form-check-input" id="basic-default-checkbox" name="turnover" />
                                                    <label class="form-check-label" for="basic-default-checkbox">Add to turnover</label>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-6">
                                                    <button type="button" class="btn btn-transparent border-primary w-100">Bonus History</button>
                                                </div>
                                                <div class="col-6">
                                                    <button type="submit" class="btn btn-primary w-100">Assign Bonus</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <div class="col-2"></div>
                        </div>

                        <!-- Bonus History Section -->
                        <!-- Bonus History Section -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <h5 class="card-title">Bonus History</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>User ID</th>
                                                <th>Bonus Type</th>
                                                <th>Amount</th>
                                                <th>Is TurnOver</th>
                                                <th>Date & Time</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            // Query to get bonus history from all tables
                                            $historyQuery = "SELECT 
                                    userkani as user_id, 
                                    'Red envelope' as bonus_type, 
                                    price as amount, 
                                    'Yes' as is_turnover, 
                                    shonu as date_time
                                FROM hodike_balakedara 
                                WHERE DATE(shonu) = '$dateFilter'
                                
                                UNION ALL
                                
                                SELECT 
                                    userkani as user_id, 
                                    'Agent red envelope recharge' as bonus_type, 
                                    price as amount, 
                                    'Yes' as is_turnover, 
                                    shonu as date_time
                                FROM agent_red_envelope_recharge_table 
                                WHERE DATE(shonu) = '$dateFilter'
                                
                                UNION ALL
                                
                                SELECT 
                                    userkani as user_id, 
                                    'Recharge gift' as bonus_type, 
                                    price as amount, 
                                    'Yes' as is_turnover, 
                                    shonu as date_time
                                FROM recharge_gift_table 
                                WHERE DATE(shonu) = '$dateFilter'
                                
                                UNION ALL
                                
                                SELECT 
                                    userkani as user_id, 
                                    'Bonus' as bonus_type, 
                                    price as amount, 
                                    'Yes' as is_turnover, 
                                    shonu as date_time
                                FROM bonus_recharge_table 
                                WHERE DATE(shonu) = '$dateFilter'
                                
                                UNION ALL
                                
                                SELECT 
                                    userkani as user_id, 
                                    'First full gift' as bonus_type, 
                                    price as amount, 
                                    'Yes' as is_turnover, 
                                    shonu as date_time
                                FROM first_full_gift_table 
                                WHERE DATE(shonu) = '$dateFilter'
                                
                                UNION ALL
                                
                                SELECT 
                                    userkani as user_id, 
                                    'Invite bonus' as bonus_type, 
                                    price as amount, 
                                    'Yes' as is_turnover, 
                                    shonu as date_time
                                FROM invite_bonus_table 
                                WHERE DATE(shonu) = '$dateFilter'
                                
                                UNION ALL
                                
                                SELECT 
                                    userkani as user_id, 
                                    'Card binding gift' as bonus_type, 
                                    price as amount, 
                                    'Yes' as is_turnover, 
                                    shonu as date_time
                                FROM card_binding_gift_table 
                                WHERE DATE(shonu) = '$dateFilter'
                                
                                
                                
                                UNION ALL
                                
                                SELECT 
                                    userkani as user_id, 
                                    'Join channel rewards' as bonus_type, 
                                    price as amount, 
                                    'Yes' as is_turnover, 
                                    shonu as date_time
                                FROM agent_bonus_table 
                                WHERE DATE(shonu) = '$dateFilter'
                                
                                UNION ALL
                                
                                SELECT 
                                    userkani as user_id, 
                                    'Daily Awards' as bonus_type, 
                                    price as amount, 
                                    'Yes' as is_turnover, 
                                    shonu as date_time
                                FROM daily_awards_table 
                                WHERE DATE(shonu) = '$dateFilter'
                                
                                UNION ALL
                                
                                SELECT 
                                    userkani as user_id, 
                                    'New members get bonuses by playing games' as bonus_type, 
                                    price as amount, 
                                    'Yes' as is_turnover, 
                                    shonu as date_time
                                FROM new_members_bonus_table 
                                WHERE DATE(shonu) = '$dateFilter'
                                
                                UNION ALL
                                
                                SELECT 
                                    userkani as user_id, 
                                    'Return Awards' as bonus_type, 
                                    price as amount, 
                                    'Yes' as is_turnover, 
                                    shonu as date_time
                                FROM return_awards_table 
                                WHERE DATE(shonu) = '$dateFilter'
                                
                                ORDER BY date_time DESC";

                                            $historyResult = mysqli_query($conn, $historyQuery);

                                            if (mysqli_num_rows($historyResult) > 0) {
                                                while ($history = mysqli_fetch_assoc($historyResult)) {
                                                    echo "<tr>
                                <td>{$history['user_id']}</td>
                                <td>{$history['bonus_type']}</td>
                                <td>₹" . number_format($history['amount'], 2) . "</td>
                                <td>{$history['is_turnover']}</td>
                                <td>" . date('d/m/Y h:i A', strtotime($history['date_time'])) . "</td>
                            </tr>";
                                                }
                                            } else {
                                                echo "<tr><td colspan='5' class='text-center'>No bonus history found for selected date.</td></tr>";
                                            }
                                            ?>
                                        </tbody>
                                    </table>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="assets/js/main.js"></script>

    <script>
        $(document).ready(function() {
            $('.datatables-bonusmanage').DataTable({
                paging: true,
                lengthChange: true,
                searching: true,
                ordering: false,
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
                    }
                }
            });

            // Bonus form submission
            $("#bonusForm").on('submit', function(e) {
                e.preventDefault();
                var formData = $(this).serialize();

                $.ajax({
                    type: "POST",
                    url: "<?php echo $_SERVER['PHP_SELF'] . '?date=' . $dateFilter; ?>",
                    data: formData,
                    success: function(response) {
                        window.location.href = "<?php echo $_SERVER['PHP_SELF'] . '?date=' . $dateFilter . '&success=1'; ?>";
                    },
                    error: function(xhr, status, error) {
                        alert("Error: " + error);
                    }
                });
            });

            // Excel file handling

        });
    </script>
    <script>
        $(document).ready(function() {
            $('#excelFile').on('change', function(event) {
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

                reader.onload = function(e) {
                    const data = new Uint8Array(e.target.result);

                    // ✅ 3. Read & Parse Excel/CSV
                    let workbook;
                    if (fileType === "csv") {
                        const csvText = new TextDecoder().decode(data);
                        const rows = csvText.split("\n").map(row => row.split(","));
                        processData(rows);
                    } else {
                        workbook = XLSX.read(data, {
                            type: 'array'
                        });
                        const sheetName = workbook.SheetNames[0];
                        const sheet = workbook.Sheets[sheetName];
                        const jsonData = XLSX.utils.sheet_to_json(sheet, {
                            header: 1
                        });
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
                    url: 'api/AddBonusExcel.php',
                    type: 'POST',
                    data: JSON.stringify({
                        data: jsonData
                    }),
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
                        console.error("❌AJAX Error:", error);
                        alert("❌An error occurred while processing your request.");
                    }
                });
            }
        });
    </script>

</body>

</html>