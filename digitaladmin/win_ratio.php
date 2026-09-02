<?php
session_start();
if(empty($_SESSION['unohs'])){
    header("location: api/login.php?msg=unauthorized");
}
include ("api/conn.php");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_ratio'])) {
        $game_name = $_POST['game_name'];
        $profit_ratio = (float)$_POST['profit_ratio'];
        $loss_ratio = (float)$_POST['loss_ratio'];
        
        // Check if game exists
        $check = $conn->prepare("SELECT id FROM win_ratio_setting WHERE game_name = ?");
        $check->bind_param("s", $game_name);
        $check->execute();
        $check_result = $check->get_result();
        
        if ($check_result->num_rows > 0) {
            // Update existing
            $stmt = $conn->prepare("UPDATE win_ratio_setting SET profit_ratio = ?, loss_ratio = ?, updated_at = NOW() WHERE game_name = ?");
            $stmt->bind_param("dds", $profit_ratio, $loss_ratio, $game_name);
        } else {
            // Insert new
            $stmt = $conn->prepare("INSERT INTO win_ratio_setting (game_name, profit_ratio, loss_ratio) VALUES (?, ?, ?)");
            $stmt->bind_param("sdd", $game_name, $profit_ratio, $loss_ratio);
        }
        
        $stmt->execute();
        
        $_SESSION['success'] = "Ratio updated successfully for " . htmlspecialchars($game_name) . "!";
        header("Location: win_ratio.php");
        exit();
    }
}

// Fetch all game ratios
$ratios = [];
$result = $conn->query("SELECT * FROM win_ratio_setting ORDER BY game_name");
while ($row = $result->fetch_assoc()) {
    $ratios[$row['game_name']] = $row;
}

// Default games with default values
$games = [
    'Wingo' => ['profit_ratio' => 70, 'loss_ratio' => 30],
    'K3' => ['profit_ratio' => 70, 'loss_ratio' => 30],
    '5D' => ['profit_ratio' => 70, 'loss_ratio' => 30],
    'Trx Wingo' => ['profit_ratio' => 70, 'loss_ratio' => 30]
];

// Merge with saved values
foreach ($games as $game_name => $defaults) {
    if (isset($ratios[$game_name])) {
        $games[$game_name] = $ratios[$game_name];
    } else {
        $games[$game_name] = array_merge(['game_name' => $game_name, 'id' => null], $defaults);
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
    <title>Win Ratio Management</title>
    <meta name="description" content="" />
    <link rel="icon" type="image/x-icon" href="assets/img/favicon/favicon.ico?v=20260814-admin" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&ampdisplay=swap" rel="stylesheet" />
    <link rel="stylesheet" href="assets/vendor/fonts/remixicon/remixicon.css" />
    <link rel="stylesheet" href="assets/vendor/fonts/flag-icons.css" />
    <link rel="stylesheet" href="assets/vendor/libs/node-waves/node-waves.css" />
    <link rel="stylesheet" href="assets/vendor/css/rtl/core.css" class="template-customizer-core-css" />
    <link rel="stylesheet" href="assets/vendor/css/rtl/theme-default.css" class="template-customizer-theme-css" />
    <link rel="stylesheet" href="assets/css/demo.css" />
    <link rel="stylesheet" href="assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
    <link rel="stylesheet" href="assets/vendor/libs/typeahead-js/typeahead.css" />
    <script src="assets/vendor/js/helpers.js"></script>
    <script src="assets/vendor/js/template-customizer.js"></script>
    <script src="assets/js/config.js"></script>
    <style>
        .ratio-card {
            transition: all 0.3s ease;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        .ratio-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        .ratio-header {
            background: linear-gradient(135deg, #3c3d77 0%, #854a4a 100%);
            color: white;
            padding: 20px;
        }
        .ratio-body {
            padding: 25px;
        }
        .slider-container {
            position: relative;
            margin: 40px 0;
        }
        .slider-track {
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            position: relative;
            overflow: hidden;
        }
        .profit-slider .slider-fill {
            background: linear-gradient(90deg, #28a745, #20c997);
            height: 100%;
            border-radius: 4px 4px 0 0;
        }
        .loss-slider .slider-fill {
            background: linear-gradient(90deg, #dc3545, #fd7e14);
            height: 100%;
            border-radius: 0 0 4px 4px;
            position: absolute;
            bottom: 0;
            left: 0;
        }
        .slider-handle {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 24px;
            height: 24px;
            background: white;
            border: 3px solid #696cff;
            border-radius: 50%;
            cursor: pointer;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            z-index: 2;
        }
        .slider-label {
            position: absolute;
            top: -30px;
            left: 0;
            transform: translateX(-50%);
            background: #696cff;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }
        .slider-label::after {
            content: '';
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            border: 5px solid transparent;
            border-top-color: #696cff;
        }
        .profit-text {
            color: #28a745;
            font-weight: 600;
        }
        .loss-text {
            color: #dc3545;
            font-weight: 600;
        }
        .ratio-display {
            font-size: 18px;
            font-weight: 700;
            color: #566a7f;
        }
        .input-group-slider {
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .game-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            margin: 0 auto 15px;
        }
        .progress-bar-container {
            margin: 30px 0;
        }
        .progress-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        .progress-bar {
            height: 20px;
            border-radius: 10px;
            overflow: hidden;
            background: #e9ecef;
        }
        .progress-fill {
            height: 100%;
            transition: width 0.3s ease;
        }
        .progress-profit {
            background: linear-gradient(90deg, #28a745, #20c997);
        }
        .progress-loss {
            background: linear-gradient(90deg, #dc3545, #fd7e14);
        }
        .total-ratio {
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            margin-top: 20px;
            border: 2px dashed #dee2e6;
        }
    </style>
</head>

<body>
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <?php require_once("layout-menu.php"); ?>
            <div class="layout-page">
                <?php require_once("nav.php"); ?>
                <div class="content-wrapper">
                    <div class="container-xxl flex-grow-1 container-p-y">
                        
                        <!-- Success Message -->
                        <?php if(isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible" role="alert">
                            <?= $_SESSION['success'] ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['success']); endif; ?>

                        <!-- Header -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h4 class="card-title mb-0">
                                            <i class="ri-pie-chart-2-line me-2"></i>
                                            Win Ratio Management
                                        </h4>
                                        <p class="text-muted mb-0">Set Profit/Loss ratios for each game</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Games Grid -->
                        <div class="row">
                            <?php foreach ($games as $game_name => $game_data): ?>
                            <?php 
                                $profit = isset($game_data['profit_ratio']) ? $game_data['profit_ratio'] : 70;
                                $loss = isset($game_data['loss_ratio']) ? $game_data['loss_ratio'] : 30;
                                $total = $profit + $loss;
                            ?>
                            <div class="col-lg-6 col-xl-3 mb-4">
                                <div class="card ratio-card">
                                    <div class="ratio-header text-center">
                                        <div class="game-icon">
                                            <?php 
                                                $icon = 'ri-gamepad-line';
                                                switch($game_name) {
                                                    case 'Wingo': $icon = 'ri-sound-module-line'; break;
                                                    case 'K3': $icon = 'ri-number-3'; break;
                                                    case '5D': $icon = 'ri-number-5'; break;
                                                    case 'Trx Wingo': $icon = 'ri-exchange-dollar-line'; break;
                                                }
                                            ?>
                                            <i class="<?= $icon ?>"></i>
                                        </div>
                                        <h5 class="mb-0 text-white"><?= htmlspecialchars($game_name) ?></h5>
                                    </div>
                                    <div class="ratio-body">
                                        <form method="POST" class="ratio-form" data-game="<?= $game_name ?>">
                                            <input type="hidden" name="game_name" value="<?= $game_name ?>">
                                            
                                            <!-- Ratio Display -->
                                            <div class="total-ratio mb-4">
                                                <div class="ratio-display">
                                                    <span class="profit-text"><?= $profit ?>%</span> : 
                                                    <span class="loss-text"><?= $loss ?>%</span>
                                                </div>
                                                <small class="text-muted">Profit : Loss Ratio</small>
                                            </div>
                                            
                                            <!-- Progress Bar Visualization -->
                                            <div class="progress-bar-container">
                                                <div class="progress-label">
                                                    <span class="profit-text">Profit</span>
                                                    <span class="profit-text"><?= $profit ?>%</span>
                                                </div>
                                                <div class="progress-bar">
                                                    <div class="progress-fill progress-profit" style="width: <?= $profit ?>%"></div>
                                                </div>
                                                
                                                <div class="progress-label mt-3">
                                                    <span class="loss-text">Loss</span>
                                                    <span class="loss-text"><?= $loss ?>%</span>
                                                </div>
                                                <div class="progress-bar">
                                                    <div class="progress-fill progress-loss" style="width: <?= $loss ?>%"></div>
                                                </div>
                                            </div>
                                            
                                            <!-- Profit Slider -->
                                            <div class="input-group-slider">
                                                <label class="form-label d-flex justify-content-between">
                                                    <span class="profit-text">
                                                        <i class="ri-arrow-up-line"></i> Profit Ratio
                                                    </span>
                                                    <span class="profit-text fw-bold" id="profitValue_<?= str_replace(' ', '_', $game_name) ?>">
                                                        <?= $profit ?>%
                                                    </span>
                                                </label>
                                                <div class="slider-container profit-slider">
                                                    <div class="slider-track">
                                                        <div class="slider-fill" style="width: <?= $profit ?>%"></div>
                                                        <div class="slider-handle" style="left: <?= $profit ?>%" id="profitHandle_<?= str_replace(' ', '_', $game_name) ?>">
                                                            <div class="slider-label"><?= $profit ?>%</div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <input type="range" class="form-range visually-hidden" 
                                                    name="profit_ratio" 
                                                    id="profitSlider_<?= str_replace(' ', '_', $game_name) ?>" 
                                                    min="0" max="100" step="1" 
                                                    value="<?= $profit ?>"
                                                    oninput="updateProfitRatio('<?= str_replace(' ', '_', $game_name) ?>', this.value)">
                                            </div>
                                            
                                            <!-- Loss Slider -->
                                            <div class="input-group-slider">
                                                <label class="form-label d-flex justify-content-between">
                                                    <span class="loss-text">
                                                        <i class="ri-arrow-down-line"></i> Loss Ratio
                                                    </span>
                                                    <span class="loss-text fw-bold" id="lossValue_<?= str_replace(' ', '_', $game_name) ?>">
                                                        <?= $loss ?>%
                                                    </span>
                                                </label>
                                                <div class="slider-container loss-slider">
                                                    <div class="slider-track">
                                                        <div class="slider-fill" style="width: <?= $loss ?>%"></div>
                                                        <div class="slider-handle" style="left: <?= $loss ?>%" id="lossHandle_<?= str_replace(' ', '_', $game_name) ?>">
                                                            <div class="slider-label"><?= $loss ?>%</div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <input type="range" class="form-range visually-hidden" 
                                                    name="loss_ratio" 
                                                    id="lossSlider_<?= str_replace(' ', '_', $game_name) ?>" 
                                                    min="0" max="100" step="1" 
                                                    value="<?= $loss ?>"
                                                    oninput="updateLossRatio('<?= str_replace(' ', '_', $game_name) ?>', this.value)">
                                            </div>
                                            
                                            <button type="submit" name="update_ratio" class="btn btn-primary w-100">
                                                <i class="ri-save-line me-2"></i>
                                                Save Ratio for <?= htmlspecialchars($game_name) ?>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Info Card -->
                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-body">
                                        <h6 class="mb-3"><i class="ri-information-line me-2"></i> How it works:</h6>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <ul class="list-unstyled">
                                                    <li class="mb-2"><i class="ri-checkbox-circle-line text-success me-2"></i> <strong>Profit Ratio</strong>: The percentage of time users win</li>
                                                    <li class="mb-2"><i class="ri-arrow-up-line text-success me-2"></i> Slide the green handle to adjust profit percentage</li>
                                                    <li class="mb-2"><i class="ri-settings-3-line me-2"></i> Each game has independent settings</li>
                                                </ul>
                                            </div>
                                            <div class="col-md-6">
                                                <ul class="list-unstyled">
                                                    <li class="mb-2"><i class="ri-close-circle-line text-danger me-2"></i> <strong>Loss Ratio</strong>: The percentage of time users lose</li>
                                                    <li class="mb-2"><i class="ri-arrow-down-line text-danger me-2"></i> Slide the red handle to adjust loss percentage</li>
                                                    <li class="mb-2"><i class="ri-refresh-line me-2"></i> Profit + Loss should equal 100%</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php require_once("footer.php"); ?>
                </div>
            </div>
        </div>
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
    <script src="assets/js/main.js"></script>

    <script>
    // Update profit ratio
    function updateProfitRatio(gameId, value) {
        const profitValue = 100 - parseFloat(value);
        const lossValue = parseFloat(value);
        
        // Update display
        document.getElementById('profitValue_' + gameId).textContent = profitValue + '%';
        document.getElementById('lossValue_' + gameId).textContent = lossValue + '%';
        
        // Update handle position
        document.getElementById('profitHandle_' + gameId).style.left = profitValue + '%';
        document.getElementById('profitHandle_' + gameId).querySelector('.slider-label').textContent = profitValue + '%';
        
        document.getElementById('lossHandle_' + gameId).style.left = lossValue + '%';
        document.getElementById('lossHandle_' + gameId).querySelector('.slider-label').textContent = lossValue + '%';
        
        // Update slider fill
        document.querySelector('#profitHandle_' + gameId).parentElement.querySelector('.slider-fill').style.width = profitValue + '%';
        document.querySelector('#lossHandle_' + gameId).parentElement.querySelector('.slider-fill').style.width = lossValue + '%';
        
        // Update progress bars
        const form = document.querySelector(`form[data-game="${gameId.replace('_', ' ')}"]`);
        form.querySelector('.progress-profit').style.width = profitValue + '%';
        form.querySelector('.progress-loss').style.width = lossValue + '%';
        
        // Update ratio display
        form.querySelector('.ratio-display .profit-text').textContent = profitValue + '%';
        form.querySelector('.ratio-display .loss-text').textContent = lossValue + '%';
        
        // Update hidden input values
        document.getElementById('profitSlider_' + gameId).value = profitValue;
        document.getElementById('lossSlider_' + gameId).value = lossValue;
    }
    
    // Update loss ratio (mirror function)
    function updateLossRatio(gameId, value) {
        const lossValue = parseFloat(value);
        const profitValue = 100 - lossValue;
        
        // Update display
        document.getElementById('profitValue_' + gameId).textContent = profitValue + '%';
        document.getElementById('lossValue_' + gameId).textContent = lossValue + '%';
        
        // Update handle position
        document.getElementById('profitHandle_' + gameId).style.left = profitValue + '%';
        document.getElementById('profitHandle_' + gameId).querySelector('.slider-label').textContent = profitValue + '%';
        
        document.getElementById('lossHandle_' + gameId).style.left = lossValue + '%';
        document.getElementById('lossHandle_' + gameId).querySelector('.slider-label').textContent = lossValue + '%';
        
        // Update slider fill
        document.querySelector('#profitHandle_' + gameId).parentElement.querySelector('.slider-fill').style.width = profitValue + '%';
        document.querySelector('#lossHandle_' + gameId).parentElement.querySelector('.slider-fill').style.width = lossValue + '%';
        
        // Update progress bars
        const form = document.querySelector(`form[data-game="${gameId.replace('_', ' ')}"]`);
        form.querySelector('.progress-profit').style.width = profitValue + '%';
        form.querySelector('.progress-loss').style.width = lossValue + '%';
        
        // Update ratio display
        form.querySelector('.ratio-display .profit-text').textContent = profitValue + '%';
        form.querySelector('.ratio-display .loss-text').textContent = lossValue + '%';
        
        // Update hidden input values
        document.getElementById('profitSlider_' + gameId).value = profitValue;
        document.getElementById('lossSlider_' + gameId).value = lossValue;
    }
    
    // Initialize sliders with drag functionality
    document.addEventListener('DOMContentLoaded', function() {
        // Add drag functionality to slider handles
        document.querySelectorAll('.slider-handle').forEach(handle => {
            handle.addEventListener('mousedown', startDrag);
            handle.addEventListener('touchstart', startDrag);
        });
        
        // Add click to slide functionality
        document.querySelectorAll('.slider-track').forEach(track => {
            track.addEventListener('click', function(e) {
                const rect = this.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const percent = Math.round((x / rect.width) * 100);
                const gameId = this.closest('.ratio-form').dataset.game.replace(/ /g, '_');
                
                if (this.closest('.profit-slider')) {
                    updateProfitRatio(gameId, 100 - percent);
                } else {
                    updateLossRatio(gameId, percent);
                }
            });
        });
    });
    
    // Drag functionality
    let isDragging = false;
    let currentSlider = null;
    let currentGame = null;
    let isProfitSlider = false;
    
    function startDrag(e) {
        e.preventDefault();
        isDragging = true;
        currentSlider = this;
        currentGame = this.closest('.ratio-form').dataset.game.replace(/ /g, '_');
        isProfitSlider = this.closest('.profit-slider') !== null;
        
        document.addEventListener('mousemove', onDrag);
        document.addEventListener('mouseup', stopDrag);
        document.addEventListener('touchmove', onDrag);
        document.addEventListener('touchend', stopDrag);
    }
    
    function onDrag(e) {
        if (!isDragging || !currentSlider) return;
        
        e.preventDefault();
        const track = currentSlider.parentElement;
        const rect = track.getBoundingClientRect();
        let x;
        
        if (e.type.includes('touch')) {
            x = e.touches[0].clientX - rect.left;
        } else {
            x = e.clientX - rect.left;
        }
        
        // Constrain within track bounds
        x = Math.max(0, Math.min(x, rect.width));
        const percent = Math.round((x / rect.width) * 100);
        
        if (isProfitSlider) {
            updateProfitRatio(currentGame, 100 - percent);
        } else {
            updateLossRatio(currentGame, percent);
        }
    }
    
    function stopDrag() {
        isDragging = false;
        currentSlider = null;
        currentGame = null;
        document.removeEventListener('mousemove', onDrag);
        document.removeEventListener('mouseup', stopDrag);
        document.removeEventListener('touchmove', onDrag);
        document.removeEventListener('touchend', stopDrag);
    }
    
    // Ensure total is always 100%
    document.querySelectorAll('.ratio-form').forEach(form => {
        const profitInput = form.querySelector('input[name="profit_ratio"]');
        const lossInput = form.querySelector('input[name="loss_ratio"]');
        
        form.addEventListener('submit', function(e) {
            const profit = parseFloat(profitInput.value);
            const loss = parseFloat(lossInput.value);
            
            if (profit + loss !== 100) {
                e.preventDefault();
                alert('Profit + Loss must equal 100%');
                return false;
            }
        });
    });
    </script>
</body>
</html>