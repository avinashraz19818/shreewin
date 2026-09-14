<?php
/*------------------------------------------------
  Cross Bet Detection System - Version 2.0
------------------------------------------------*/
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

// ❶ Session and Parameter Check
if (!isset($_SESSION['unohs'])) {
    header("Location: api/login.php?msg=session_expired");
    exit;
}

// ❷ Parameter Processing
$raw_user_ids = explode(',', $_GET['userids'] ?? '');
$raw_ip = $_GET['ip'] ?? '';

include("api/conn.php");
if (!$conn) {
    die("<div class='alert alert-danger'>⚠️ Database Connection Failed</div>");
}

// ❸ Data Sanitization
$user_ids = array_unique(array_filter(array_map(function($id) use ($conn) {
    return (int)mysqli_real_escape_string($conn, trim($id));
}, $raw_user_ids)));

$ip = mysqli_real_escape_string($conn, trim($raw_ip));

if(empty($user_ids) || !filter_var($ip, FILTER_VALIDATE_IP)) {
    die("<div class='alert alert-warning'>❌ Invalid Parameters: Check User IDs and IP</div>");
}

/*------------------------------------------------
  Data Processing Logic
------------------------------------------------*/
$tables = [
    'bajikattuttate' => 'Wingo 1 Min',
    'bajikattuttate_zehn' => 'Wingo 30 Sec',
    'bajikattuttate_drei' => 'Wingo 3 Min',
    'bajikattuttate_funf' => 'Wingo 5 Min'
];

$conflicts = [];
$bet_types = [
    13 => ['name' => 'Big', 'class' => 'big-bet'],
    14 => ['name' => 'Small', 'class' => 'small-bet'],
    10 => ['name' => 'Red', 'class' => 'red-bet'],
    11 => ['name' => 'Green', 'class' => 'green-bet']
];

// ❹ Fetch Data from All Tables
foreach ($tables as $table => $game_name) {
    $query = "SELECT 
                byabaharkarta AS user_id,
                kalaparichaya AS period,
                ojana AS bet_type,
                ketebida AS amount,
                tiarikala AS bet_time
              FROM $table
              WHERE 
                ip = '$ip' AND 
                byabaharkarta IN (".implode(',', $user_ids).") AND 
                ojana IN (13,14,10,11)
              ORDER BY bet_time DESC";

    $result = mysqli_query($conn, $query);
    
    if(!$result) continue;

    while($row = mysqli_fetch_assoc($result)) {
        $period_key = $row['period'].'|'.$game_name;
        
        // ❺ Conflict Detection
        if(!isset($conflicts[$period_key])) {
            $conflicts[$period_key] = [
                'game' => $game_name,
                'period' => $row['period'],
                'users' => [],
                'bets' => []
            ];
        }

        // User Tracking
        if(!in_array($row['user_id'], $conflicts[$period_key]['users'])) {
            $conflicts[$period_key]['users'][] = $row['user_id'];
        }

        // Bet Details
        $conflicts[$period_key]['bets'][] = [
            'type' => $bet_types[$row['bet_type']]['name'],
            'class' => $bet_types[$row['bet_type']]['class'],
            'amount' => $row['amount'],
            'time' => date('H:i:s', strtotime($row['bet_time']))
        ];
    }
}

/*------------------------------------------------
  HTML Output
------------------------------------------------*/
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cross Bet Analysis</title>
    <link rel="stylesheet" href="assets/vendor/css/core.css">
    <link rel="stylesheet" href="assets/vendor/css/theme-default.css">
    <style>
        .conflict-card {
            border-left: 4px solid #dc3545;
            margin: 1rem 0;
            background: #fff5f5;
        }
        .bet-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            margin: 0.25rem;
            font-size: 0.9em;
        }
        .big-bet { background: #ffd7d7; color: #cc0000; }
        .small-bet { background: #d7ffd7; color: #00cc00; }
        .red-bet { background: #ffb3b3; color: #990000; }
        .green-bet { background: #b3ffb3; color: #009900; }
        .debug-info {
            font-family: monospace;
            background: #f8f9fa;
            padding: 1rem;
            margin: 1rem 0;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <!-- Header Section -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">🔍 Cross Bet Analysis Report</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5>📌 Parameter Details</h5>
                        <ul class="list-unstyled">
                            <li>🆔 User ID: <?= implode(', ', $user_ids) ?></li>
                            <li>🌐 IP Address: <?= htmlspecialchars($ip) ?></li>
                        </ul>
                    </div>
                    <div class="col-md-6 text-end">
                        <a href="crossbet_list.php" class="btn btn-secondary">
                            ← Go Back
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Report -->
        <?php if(empty($conflicts)): ?>
            <div class="alert alert-success">
                🎉 No suspicious cross bet activity found!
            </div>
        <?php else: ?>
            <?php foreach($conflicts as $key => $data): ?>
                <div class="card conflict-card">
                    <div class="card-body">
                        <div class="row">
                            <!-- Game Details -->
                            <div class="col-md-4 border-end">
                                <h5 class="text-primary">🎮 <?= $data['game'] ?></h5>
                                <div class="mt-3">
                                    <div>⏳ Period: <?= $data['period'] ?></div>
                                    <div>👥 Users: <?= implode(', ', $data['users']) ?></div>
                                </div>
                            </div>
                            
                            <!-- Bet Details -->
                            <div class="col-md-8">
                                <h5 class="mb-3">📊 Bet Details</h5>
                                <?php foreach($data['bets'] as $bet): ?>
                                    <div class="bet-badge <?= $bet['class'] ?>">
                                        <?= $bet['type'] ?> - 
                                        ₹<?= number_format($bet['amount'], 2) ?>
                                        <small class="text-muted">(<?= $bet['time'] ?>)</small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Debug Information -->
        <div class="debug-info">
            <small>
                📝 Debug Data:<br>
                IP: <?= $ip ?><br>
                User IDs: <?= json_encode($user_ids) ?><br>
                Total Conflicts: <?= count($conflicts) ?>
            </small>
        </div>
    </div>
</body>
</html>