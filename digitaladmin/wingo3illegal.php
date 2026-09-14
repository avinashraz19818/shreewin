<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

date_default_timezone_set('Asia/Kolkata');

define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'club532583_veergame');
define('DB_PASSWORD', 'club532583_veergame');
define('DB_NAME', 'club532583_veergame');

$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn == false) {
    dir('Error: Cannot connect');
    echo "Fail";
}

// Table Info
$tables = [

    "bajikattuttate_drei" => "Wingo 3 Min"

];

$illegalBets = [];
$today = date("Y-m-d");

foreach ($tables as $table => $gameType) {
    $query = "
        SELECT b.byabaharkarta, b.kalaparichaya, b.ojana, b.ketebida, b.phalaphala, b.tiarikala, '$gameType' AS game_type
        FROM $table b
        WHERE b.ojana IN (13, 14, 10, 11) 
    ";

    $result = mysqli_query($conn, $query);
    $bets = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $bets[$row['byabaharkarta']][$row['kalaparichaya']][$row['ojana']][] = $row;
    }

    foreach ($bets as $userId => $periods) {
        foreach ($periods as $period => $ojanas) {
            if ((isset($ojanas[13]) && isset($ojanas[14])) || (isset($ojanas[10]) && isset($ojanas[11]))) {
                foreach ([13, 14, 10, 11] as $type) {
                    if (isset($ojanas[$type])) {
                        foreach ($ojanas[$type] as $bet) {
                            $illegalBets[] = $bet;
                        }
                    }
                }

                // Check if user already got turnover today
                $checkQuery = "SELECT COUNT(*) as count FROM illegal_bet WHERE userid = '$userId' AND DATE(dateandtime) = '$today' AND turnover = 1";
                $checkResult = mysqli_query($conn, $checkQuery);
                $checkRow = mysqli_fetch_assoc($checkResult);

                if ($checkRow['count'] == 0) {
                    // Fetch user balance and turnover from shonu_kaichila
                    $balanceQuery = "SELECT motta, turnover FROM shonu_kaichila WHERE balakedara = '$userId'";
                    $balanceResult = mysqli_query($conn, $balanceQuery);
                    $balanceRow = mysqli_fetch_assoc($balanceResult);

                    if ($balanceRow) {
                        $userBalance = $balanceRow['motta'];
                        $newTurnover = $userBalance * 10;

                        // Update turnover
                        $updateQuery = "UPDATE shonu_kaichila SET turnover = '$newTurnover' WHERE balakedara = '$userId'";
                        mysqli_query($conn, $updateQuery);

                        // Insert into illegal_bet table
                        $insertQuery = "INSERT INTO illegal_bet (userid, dateandtime, turnover) VALUES ('$userId', NOW(), 1)";
                        mysqli_query($conn, $insertQuery);
                    }
                }
            }
        }
    }
}
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Illegal Bet Users</title>
    <link rel="stylesheet" href="assets/vendor/css/core.css" />
    <link rel="stylesheet" href="assets/vendor/css/theme-default.css" />
    <link rel="stylesheet" href="assets/css/demo.css" />
    <link rel="stylesheet" href="assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css" />
</head>

<body>
    <div class="container mt-4">
        <h4 class="mb-4">Illegal Bet Users List</h4>
        <table class="table" id="example1">
            <thead>
                <tr>
                    <th>User ID</th>
                    <th>Period</th>
                    <th>Bet Type</th>
                    <th>Bet Amount</th>
                    <th>Status</th>
                    <th>Date & Time</th>
                    <th>Game Type</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($illegalBets)) {
                    foreach ($illegalBets as $row) { ?>
                        <tr>
                            <td><?= $row["byabaharkarta"]; ?></td>
                            <td><?= $row["kalaparichaya"]; ?></td>
                            <td>
                                <?php
                                if ($row["ojana"] == 13) echo 'Big';
                                elseif ($row["ojana"] == 14) echo 'Small';
                                elseif ($row["ojana"] == 10) echo 'Red';
                                elseif ($row["ojana"] == 11) echo 'green';
                                ?>
                            </td>
                            <td>₹ <?= $row["ketebida"]; ?></td>
                            <td><?= $row["phalaphala"] == 'gagner' ? 'Win' : 'Loss'; ?></td>
                            <td><?= $row["tiarikala"]; ?></td>
                            <td><?= $row["game_type"]; ?></td>
                        </tr>
                <?php }
                } else {
                    echo "<tr><td colspan='7'>No Illegal Bets Found</td></tr>";
                } ?>
            </tbody>
        </table>
    </div>

    <script src="assets/vendor/libs/jquery/jquery.js"></script>
    <script src="assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js"></script>
    <script>
        $(document).ready(function() {
            $('#example1').DataTable({
                "pageLength": 50,
                "ordering": false
            });
        });
    </script>
</body>

</html>