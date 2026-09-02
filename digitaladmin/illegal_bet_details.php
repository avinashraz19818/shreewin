<?php
include("api/conn.php");
error_reporting(E_ALL);
ini_set('display_errors', 1);
if (!isset($_GET['userid']) || !isset($_GET['date'])) {
    die("Invalid request");
}

$userid = mysqli_real_escape_string($conn, $_GET['userid']);
$date = mysqli_real_escape_string($conn, $_GET['date']);

$tables = [
    "bajikattuttate" => "Wingo 1 Min",
    "bajikattuttate_zehn" => "Wingo 30 Sec",
    "bajikattuttate_drei" => "Wingo 3 Min",
    "bajikattuttate_funf" => "Wingo 5 Min"
];

$bigSmallBets = [];
$redGreenBets = [];

foreach ($tables as $table => $gameType) {
    $query = "
    SELECT 
        b.kalaparichaya, 
        b.ojana, 
        b.ketebida, 
        b.phalaphala, 
        b.tiarikala, 
        '$gameType' AS game_type
    FROM $table b
    WHERE 
        b.byabaharkarta = '$userid' 
        AND DATE(b.tiarikala) = '$date'
        AND b.ojana IN (10, 11, 13, 14)
    GROUP BY b.kalaparichaya
    HAVING 
        (SUM(b.ojana = 10) > 0 AND SUM(b.ojana = 11) > 0) -- Red/Green पेयर
        OR 
        (SUM(b.ojana = 13) > 0 AND SUM(b.ojana = 14) > 0) -- Big/Small पेयर
";

    $result = mysqli_query($conn, $query);
    while ($row = mysqli_fetch_assoc($result)) {
        $period = $row['kalaparichaya'];
        
        // पीरियड के सभी बेट्स फ़ेच करें (game_type सहित)
        $detailsQuery = "SELECT *, '$gameType' AS game_type FROM $table 
                        WHERE kalaparichaya = '$period' 
                        AND ojana IN (10, 11, 13, 14)";
        $detailsResult = mysqli_query($conn, $detailsQuery);
        
        $bets = [];
        while ($betRow = mysqli_fetch_assoc($detailsResult)) {
            $bets[] = $betRow;
        }
        
        // कैटेगरी के हिसाब से अलग करें
        $hasRedGreen = false;
        $hasBigSmall = false;
        
        foreach ($bets as $bet) {
            if (in_array($bet['ojana'], [10, 11])) $hasRedGreen = true;
            if (in_array($bet['ojana'], [13, 14])) $hasBigSmall = true;
        }
        
        if ($hasRedGreen) $redGreenBets[$period] = $bets;
        if ($hasBigSmall) $bigSmallBets[$period] = $bets;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Illegal Bet Details</title>
    <link rel="stylesheet" href="assets/vendor/css/core.css">
    <link rel="stylesheet" href="assets/vendor/css/theme-default.css">
    <link rel="stylesheet" href="assets/css/demo.css">
    <style>
        .highlight { background: #ffebee; }
        .table-title { color: #dc3545; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container mt-4">
        <!-- Big/Small टेबल -->
        <h4 class="table-title">Illegal Big/Small Bets</h4>
        <table class="table">
            <thead>
                <tr>
                    <th>Period</th>
                    <th>Bet Type</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Date & Time</th>
                    <th>Game Type</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($bigSmallBets)): ?>
                    <?php foreach ($bigSmallBets as $period => $bets): ?>
                        <?php foreach ($bets as $bet): ?>
                            <tr class="highlight">
                                <td><?= $bet['kalaparichaya'] ?></td>
                                <td><?= ($bet['ojana'] == 13) ? 'Big' : 'Small' ?></td>
                                <td>₹ <?= number_format($bet['ketebida'], 2) ?></td>
                                <td><?= ($bet['phalaphala'] == 'gagner') ? 'Win' : 'Loss' ?></td>
                                <td><?= $bet['tiarikala'] ?></td>
                                <td><?= $bet['game_type'] ?></td> <!-- Fixed Here -->
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6">No Big/Small Illegal Bets Found</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Red/Green टेबल -->
        <h4 class="table-title mt-5">Illegal Red/Green Bets</h4>
        <table class="table">
            <thead>
                <tr>
                    <th>Period</th>
                    <th>Bet Type</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Date & Time</th>
                    <th>Game Type</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($redGreenBets)): ?>
                    <?php foreach ($redGreenBets as $period => $bets): ?>
                        <?php foreach ($bets as $bet): ?>
                            <tr class="highlight">
                                <td><?= $bet['kalaparichaya'] ?></td>
                                <td><?= ($bet['ojana'] == 10) ? 'Red' : 'Green' ?></td>
                                <td>₹ <?= number_format($bet['ketebida'], 2) ?></td>
                                <td><?= ($bet['phalaphala'] == 'gagner') ? 'Win' : 'Loss' ?></td>
                                <td><?= $bet['tiarikala'] ?></td>
                                <td><?= $bet['game_type'] ?></td> <!-- Fixed Here -->
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6">No Red/Green Illegal Bets Found</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>