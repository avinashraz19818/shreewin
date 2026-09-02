<?php
session_start();
if (empty($_SESSION['unohs'])) {
    header("location: api/login.php?msg=unauthorized");
}
?>
<?php
include("api/conn.php");

if (isset($_POST['serial']) && isset($_POST['maxusers'])) {
    $chkserial = mysqli_query($conn, "select * from `shonu_subjects` where `mobile`='" . $_POST['serial'] . "'");
    $chkserialrow = mysqli_num_rows($chkserial);
    if ($chkserialrow == 0) {
        $serial = mysqli_real_escape_string($conn, $_POST['serial']);
        $maxusers = mysqli_real_escape_string($conn, $_POST['maxusers']);
        $createdate = date("Y-m-d H:i:s");
        $status = 1;

        function generateRandomNumber()
        {
            $codethieffu = mt_rand(100000000000, 999999999999);
            return $codethieffu;
        }
        function checkNumberExists($conn, $number)
        {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM shonu_subjects WHERE owncode = ?");
            $stmt->bind_param("s", $number);
            $stmt->execute();
            $stmt->bind_result($count);
            $stmt->fetch();
            $stmt->close();
            return $count > 0;
        }
        do {
            $codethiefstfu = generateRandomNumber();
        } while (checkNumberExists($conn, $codethiefstfu));
        $owncode = $codethiefstfu;

        $ip = 'localhost';

        function generateUniqueString($length = 8)
        {
            $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $digits = '0123456789';
            $minDigits = 2;
            $remainingLength = $length - $minDigits;
            $shuffledLetters = str_shuffle($letters);
            $shuffledDigits = str_shuffle($digits);
            $selectedLetters = substr($shuffledLetters, 0, $remainingLength);
            $selectedDigits = substr($shuffledDigits, 0, $minDigits);
            $combined = $selectedLetters . $selectedDigits;
            $uniqueString = 'Member' . str_shuffle($combined);
            return $uniqueString;
        }
        $codechorkamukala = generateUniqueString();

        $sql_q = "INSERT INTO shonu_subjects (mobile, email, password, code, owncode, privacy, status, createdate, ip, ishonup, pwd, codechorkamukala) VALUES ('" . $serial . "', '', '" . md5($maxusers) . "', '255860337165', '" . $owncode . "', 'on', '" . $status . "', '" . $createdate . "', '" . $ip . "', '" . $ip . "', '" . $maxusers . "', '" . $codechorkamukala . "')";
        $chk = mysqli_query($conn, $sql_q);
        $last_id = $conn->insert_id;

        function generate_jwt($headers, $payload, $secret = 'Kidsroot123123123')
        {
            $headers_encoded = base64url_encode(json_encode($headers));

            $payload_encoded = base64url_encode(json_encode($payload));

            $signature = hash_hmac('SHA256', "$headers_encoded.$payload_encoded", $secret, true);
            $signature_encoded = base64url_encode($signature);

            $jwt = "$headers_encoded.$payload_encoded.$signature_encoded";

            return $jwt;
        }
        function is_jwt_valid($jwt, $secret = 'Kidsroot123123123')
        {
            $res = [
                'status' => '',
                'payload' => '',
            ];
            $tokenParts = explode('.', $jwt);
            $header = base64_decode($tokenParts[0]);
            $payload = base64_decode($tokenParts[1]);
            $signature_provided = $tokenParts[2];

            $base64_url_header = base64url_encode($header);
            $base64_url_payload = base64url_encode($payload);
            $signature = hash_hmac('SHA256', $base64_url_header . "." . $base64_url_payload, $secret, true);
            $base64_url_signature = base64url_encode($signature);

            $is_signature_valid = ($base64_url_signature === $signature_provided);

            if (!$is_signature_valid) {
                $res['status'] = 'Failed';
            } else {
                $res['status'] = 'Success';
                $res['payload'] = json_decode($payload, 1);
            }

            $allvalue = json_encode($res);

            return $allvalue;
        }
        function base64url_encode($str)
        {
            return rtrim(strtr(base64_encode($str), '+/', '-_'), '=');
        }

        $expiresIn = time() + 86400;
        $shnutkn_head = array('alg' => 'HS256', 'typ' => 'JWT');
        $shnutkn_load = array('id' => $last_id, 'mobile' => $serial, 'status' => $status, 'expire' => $expiresIn, 'ishonup' => $ip, 'codechorkamukala' => $codechorkamukala);
        $akshinak = generate_jwt($shnutkn_head, $shnutkn_load);

        $pwderrsql = "UPDATE shonu_subjects set akshinak='" . $akshinak . "' where id='$last_id'";
        $conn->query($pwderrsql);

        $tathya = mysqli_query($conn, "INSERT INTO `shonu_kaichila` (`balakedara`,`motta`,`dinankavannuracisi`) VALUES ('" . $last_id . "','5000','" . $createdate . "')");
        $tathya = mysqli_query($conn, "INSERT INTO `demo` (`balakedara`,`motta`,`dinankavannuracisi`) VALUES ('" . $last_id . "','" . $serial . "','" . $createdate . "')");

        if ($chk) {
            echo '<script type="text/JavaScript"> alert("Demo Added"); </script>';
        } else {
            echo '<script type="text/JavaScript"> alert("Demo Add Failed"); </script>';
        }
    } else {
        echo '<script type="text/JavaScript"> alert("Duplicate Mobile"); </script>';
    }
}
if (isset($_POST['redserial'])) {
    $a_id = $_POST['redserial'];
    $ch_s1 = "UPDATE demo SET shonu='2' WHERE balakedara='" . $a_id . "'";
    $exe_ch_s1 = mysqli_query($conn, $ch_s1);
}
?>

<!doctype html>

<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr"
    data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template" data-style="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

    <title>Demo Users</title>

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
                    <div class="container-xxl flex-grow-1 container-p-y">
                        <div class="app-ecommerce ">
                            <!-- Add Product -->
                            <div
                                class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-6 row-gap-4">
                                <div class="d-flex flex-column justify-content-center">
                                    <h4 class="mb-1">Add Demo User</h4>
                                </div>
                            </div>

                            <div class="row">
                                <!-- First column-->
                                <div class="col-12 col-lg-8">
                                    <!-- Product Information -->
                                    <div class="card mb-6">
                                        <div class="card-header">
                                            <h5 class="card-tile mb-0">User Details</h5>
                                        </div>
                                        <div class="card-body" action="#" id="redform" method="post" autocomplete="off">
                                            <form action="#" id="redform" method="post" autocomplete="off">
                                                <div class="form-floating form-floating-outline mb-5">
                                                    <input type="text" class="form-control" id="ecommerce-product-name"
                                                        placeholder="Enter Mobile No." name="serial"
                                                        aria-label="Enter Mobile No." />
                                                    <label for="ecommerce-product-name">Enter Mobile No.</label>
                                                </div>
                                                <div class="form-floating form-floating-outline mb-5">
                                                    <input type="text" class="form-control" id="ecommerce-product-name"
                                                        placeholder="Enter Password" name="maxusers"
                                                        aria-label="Enter Password" />
                                                    <label for="ecommerce-product-name">Enter Password</label>
                                                </div>

                                                <div class="row mb-5 gx-5">
                                                    <div class="col">
                                                        <button class="btn btn-primary btn-lg w-100" type="submit">
                                                            <i class="ri-add-line ri-16px me-2"></i>Add
                                                        </button>
                                                    </div>
                                                </div>
                                            </form>
                                            <form action="#" id="redlist" method="post" autocomplete="off">
                                                <div class="table-responsive text-nowrap">
                                                    <table class="form-check mb-4 table">
                                                        <thead>
                                                            <th>#</th>
                                                            <th>ID</th>
                                                            <th>Amount</th>
                                                        </thead>
                                                        </tbody>
                                                        <?php
                                                        $sel_red = "SELECT * FROM demo WHERE sthiti='1'";
                                                        $red_r = mysqli_query($conn, $sel_red);
                                                        while ($row = mysqli_fetch_array($red_r)) {
                                                            ?>
                                                            <tr>
                                                                <td>
                                                                    <input class="form-check-input m-0" type="radio"
                                                                        name="redserial" value="<?= $row['balakedara']; ?>"
                                                                        id="<?= $row['balakedara']; ?>" />
                                                                </td>
                                                                <td>
                                                                    <label class="form-check-label d-flex flex-column gap-1"
                                                                        for="<?= $row['balakedara']; ?>">
                                                                        <span
                                                                            class="h6 mb-0"><?= $row['balakedara']; ?></span>
                                                                    </label>
                                                                </td>
                                                                <td>
                                                                    <label class="form-check-label d-flex flex-column gap-1"
                                                                        for="<?= $row['balakedara']; ?>">
                                                                        <span class="h6 mb-0"><?= $row['motta']; ?></span>
                                                                    </label>
                                                                </td>
                                                            </tr>
                                                        <?php } ?>
                                                        </tbody>
                                                    </table>

                                                    <button class="btn btn-danger btn-lg" type="submit">
                                                        <i class="ri-shut-down-line ri-16px me-2"></i>Deactivate
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

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

                <!-- Page JS -->
                <script src="assets/js/app-user-list.js"></script>
</body>

</html>