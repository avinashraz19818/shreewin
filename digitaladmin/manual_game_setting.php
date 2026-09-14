<?php
session_start();
if(empty($_SESSION['unohs'])){
    header("location: api/login.php?msg=unauthorized");
}
include ("api/conn.php");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_game'])) {
        $game_id = $_POST['game_id'];
        $state = isset($_POST['state']) ? 1 : 0;
        $categoryImg = $_POST['categoryImg'];
        
        $stmt = $conn->prepare("UPDATE lottery_games_setting SET state = ?, categoryImg = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("isi", $state, $categoryImg, $game_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Game updated successfully!";
        } else {
            $_SESSION['error'] = "Error updating game!";
        }
        
        header("Location: manual_game_setting.php");
        exit();
    }
}

// Fetch all games
$games = $conn->query("SELECT * FROM lottery_games_setting ORDER BY sort ASC")->fetch_all(MYSQLI_ASSOC);

// If table is empty, insert default games
if (empty($games)) {
    $defaultGames = [
        [
            'categoryCode' => 'Win Go',
            'categoryName' => 'WinGo彩票',
            'state' => 1,
            'sort' => 55,
            'categoryImg' => 'https://ossimg.yuk87k786d.com/sikkim/lotterycategory/lotterycategory_20250226152252c2tp.png',
            'gameCode' => 'WinGo_30S'
        ],
        [
            'categoryCode' => 'MotoRace',
            'categoryName' => '摩托赛车',
            'state' => 1,
            'sort' => 47,
            'categoryImg' => 'https://ossimg.yuk87k786d.com/sikkim/lotterycategory/lotterycategory_20250518151408ii4i.png',
            'gameCode' => 'MotoRace_1M'
        ],
        [
            'categoryCode' => 'VideoWinGo',
            'categoryName' => '视频WinGo',
            'state' => 1,
            'sort' => 45,
            'categoryImg' => 'https://ossimg.yuk87k786d.com/sikkim/lotterycategory/lotterycategory_20250623155304yu9m.png',
            'gameCode' => 'VideoWinGo_3M'
        ],
        [
            'categoryCode' => 'K3',
            'categoryName' => 'K3彩票',
            'state' => 1,
            'sort' => 44,
            'categoryImg' => 'https://ossimg.yuk87k786d.com/sikkim/lotterycategory/lotterycategory_20250226152226vf4r.png',
            'gameCode' => 'K3_1M'
        ],
        [
            'categoryCode' => '5D',
            'categoryName' => '5D彩票',
            'state' => 1,
            'sort' => 33,
            'categoryImg' => 'https://ossimg.yuk87k786d.com/sikkim/lotterycategory/lotterycategory_20250226152155ds9j.png',
            'gameCode' => 'D5_1M'
        ],
        [
            'categoryCode' => 'Trx Win Go',
            'categoryName' => 'TrxWinGo彩票',
            'state' => 1,
            'sort' => 22,
            'categoryImg' => 'https://ossimg.yuk87k786d.com/sikkim/lotterycategory/lotterycategory_20250226152131ed84.png',
            'gameCode' => 'TrxWinGo_1M'
        ]
    ];
    
    foreach ($defaultGames as $game) {
        $stmt = $conn->prepare("INSERT INTO lottery_games_setting (categoryCode, categoryName, state, sort, categoryImg, gameCode) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssiiss", 
            $game['categoryCode'],
            $game['categoryName'],
            $game['state'],
            $game['sort'],
            $game['categoryImg'],
            $game['gameCode']
        );
        $stmt->execute();
    }
    
    // Fetch again after insertion
    $games = $conn->query("SELECT * FROM lottery_games_setting ORDER BY sort ASC")->fetch_all(MYSQLI_ASSOC);
}
?>

<!doctype html>
<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr"
    data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template" data-style="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Manual Game Settings</title>
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
        .game-card {
            transition: all 0.3s ease;
            border-radius: 15px;
            overflow: hidden;
            margin-bottom: 20px;
            height: 100%;
        }
        .game-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .game-card.active {
            border: 2px solid #28a745;
        }
        .game-card.inactive {
            border: 2px solid #dc3545;
            opacity: 0.8;
        }
        .game-header {
            padding: 15px;
            text-align: center;
            border-bottom: 1px solid #eee;
        }
        .game-image {
            width: 120px;
            height: 120px;
            object-fit: contain;
            border-radius: 12px;
            border: 3px solid white;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            margin: 0 auto 10px;
            display: block;
            transition: transform 0.3s ease;
            background: #f8f9fa;
            padding: 10px;
        }
        .game-image:hover {
            transform: scale(1.05);
        }
        .game-status {
            position: absolute;
            top: 10px;
            right: 10px;
        }
        .game-code {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            color: #666;
            background: #f8f9fa;
            padding: 2px 8px;
            border-radius: 10px;
            display: inline-block;
        }
        .image-preview-container {
            position: relative;
            margin-bottom: 15px;
        }
        .image-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
            border-radius: 12px;
        }
        .image-preview-container:hover .image-overlay {
            opacity: 1;
        }
        .image-actions {
            display: flex;
            gap: 10px;
        }
        .sort-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
        }
        .stats-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        /* Toggle Switch Fix - Proper Size */
        .game-toggle {
            width: 60px !important;
            height: 30px !important;
            cursor: pointer !important;
        }
        
        .game-toggle:checked {
            background-color: #28a745 !important;
            border-color: #28a745 !important;
        }
        
        .game-toggle:focus {
            box-shadow: 0 0 0 0.25rem rgba(40, 167, 69, 0.25) !important;
        }
        
        /* Prevent card click to toggle */
        .game-card {
            user-select: none;
        }
        
        /* Only specific elements should be clickable */
        .game-card form,
        .game-card input,
        .game-card button,
        .game-card .image-actions,
        .game-card .form-check,
        .game-card .form-check-label {
            pointer-events: auto;
        }
        
        /* Make label clickable for toggle */
        .toggle-label {
            cursor: pointer;
            user-select: none;
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
                        
                        <!-- Success/Error Messages -->
                        <?php if(isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible" role="alert">
                            <?= $_SESSION['success'] ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['success']); endif; ?>
                        
                        <?php if(isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible" role="alert">
                            <?= $_SESSION['error'] ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['error']); endif; ?>

                        <!-- Header -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <div>
                                            <h4 class="card-title mb-0">
                                                <i class="ri-gamepad-line me-2"></i>
                                                Manual Game Settings
                                            </h4>
                                            <p class="text-muted mb-0">Enable/Disable games and manage their images</p>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn btn-success" onclick="enableAllGames()">
                                                <i class="ri-toggle-line me-2"></i>Enable All
                                            </button>
                                            <button type="button" class="btn btn-danger" onclick="disableAllGames()">
                                                <i class="ri-toggle-fill me-2"></i>Disable All
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="d-flex align-items-center">
                                                    <div class="bg-success rounded-circle p-2 me-3">
                                                        <i class="ri-check-line text-white"></i>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-0" id="activeGamesCount">0</h6>
                                                        <small class="text-muted">Active Games</small>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="d-flex align-items-center">
                                                    <div class="bg-danger rounded-circle p-2 me-3">
                                                        <i class="ri-close-line text-white"></i>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-0" id="inactiveGamesCount">0</h6>
                                                        <small class="text-muted">Inactive Games</small>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="d-flex align-items-center">
                                                    <div class="bg-primary rounded-circle p-2 me-3">
                                                        <i class="ri-image-line text-white"></i>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-0"><?= count($games) ?></h6>
                                                        <small class="text-muted">Total Games</small>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="d-flex align-items-center">
                                                    <div class="bg-warning rounded-circle p-2 me-3">
                                                        <i class="ri-list-settings-line text-white"></i>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-0" id="lastUpdated">Just now</h6>
                                                        <small class="text-muted">Last Updated</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Games Grid -->
                        <div class="row">
                            <?php foreach ($games as $game): ?>
                            <div class="col-lg-4 col-md-6 mb-4">
                                <div class="card game-card <?= $game['state'] == 1 ? 'active' : 'inactive' ?>" id="gameCard_<?= $game['id'] ?>">
                                    <div class="game-header position-relative">
                                        <div class="sort-badge">
                                            <i class="ri-sort-number"></i> <?= $game['sort'] ?>
                                        </div>
                                        
                                        <div class="game-status">
                                            <span class="badge <?= $game['state'] == 1 ? 'bg-success' : 'bg-danger' ?>" id="statusBadge_<?= $game['id'] ?>">
                                                <?= $game['state'] == 1 ? 'Active' : 'Inactive' ?>
                                            </span>
                                        </div>
                                        
                                        <div class="image-preview-container">
                                            <img src="<?= htmlspecialchars($game['categoryImg']) ?>" 
                                                 alt="<?= htmlspecialchars($game['categoryName']) ?>" 
                                                 class="game-image" 
                                                 id="preview_<?= $game['id'] ?>"
                                                 onerror="this.src='https://via.placeholder.com/120x120?text=No+Image'">
                                            <div class="image-overlay">
                                                <div class="image-actions">
                                                    <button type="button" class="btn btn-sm btn-light" 
                                                            onclick="zoomImage('<?= htmlspecialchars($game['categoryImg']) ?>', '<?= htmlspecialchars($game['categoryName']) ?>')">
                                                        <i class="ri-zoom-in-line"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-light" 
                                                            onclick="copyImageUrl('<?= htmlspecialchars($game['categoryImg']) ?>')">
                                                        <i class="ri-file-copy-line"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <h5 class="mb-1"><?= htmlspecialchars($game['categoryName']) ?></h5>
                                        <p class="text-muted mb-1"><?= htmlspecialchars($game['categoryCode']) ?></p>
                                        <span class="game-code"><?= htmlspecialchars($game['gameCode']) ?></span>
                                    </div>
                                    
                                    <div class="card-body">
                                        <form method="POST" action="" class="game-form" data-game-id="<?= $game['id'] ?>" id="gameForm_<?= $game['id'] ?>">
                                            <input type="hidden" name="game_id" value="<?= $game['id'] ?>">
                                            
                                            <!-- Toggle Switch with proper size -->
                                            <div class="mb-3">
                                                <div class="d-flex align-items-center justify-content-between">
                                                    <span class="fw-medium toggle-label" onclick="toggleGame(<?= $game['id'] ?>)">
                                                        Game Status:
                                                    </span>
                                                    <div class="form-check form-switch mb-0">
                                                        <input class="form-check-input game-toggle" type="checkbox" 
                                                               name="state" id="state_<?= $game['id'] ?>" 
                                                               <?= $game['state'] == 1 ? 'checked' : '' ?>
                                                               onchange="updateGameStatus(<?= $game['id'] ?>, this.checked)">
                                                        <label class="form-check-label ms-2 toggle-label" for="state_<?= $game['id'] ?>">
                                                            <span id="statusText_<?= $game['id'] ?>" class="<?= $game['state'] == 1 ? 'text-success' : 'text-danger' ?> fw-medium">
                                                                <?= $game['state'] == 1 ? 'Active' : 'Inactive' ?>
                                                            </span>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Game Image URL</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="ri-image-line"></i></span>
                                                    <input type="url" class="form-control image-url-input" 
                                                           name="categoryImg" 
                                                           value="<?= htmlspecialchars($game['categoryImg']) ?>" 
                                                           placeholder="https://example.com/image.png"
                                                           data-game-id="<?= $game['id'] ?>"
                                                           oninput="updateImagePreview(<?= $game['id'] ?>, this.value)">
                                                </div>
                                                <div class="form-text">Enter direct image URL (PNG, JPG recommended)</div>
                                            </div>
                                            
                                            <div class="d-flex justify-content-between">
                                                <button type="button" class="btn btn-outline-secondary btn-sm" 
                                                        onclick="testImage('<?= htmlspecialchars($game['categoryImg']) ?>')">
                                                    <i class="ri-eye-line me-1"></i>Test Image
                                                </button>
                                                <button type="submit" name="update_game" class="btn btn-primary btn-sm" id="saveBtn_<?= $game['id'] ?>">
                                                    <i class="ri-save-line me-1"></i>Save Changes
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                    
                                    <div class="card-footer bg-transparent border-top d-flex justify-content-between">
                                        <small class="text-muted">
                                            ID: <?= $game['id'] ?>
                                        </small>
                                        <small class="text-muted">
                                            <i class="ri-time-line me-1"></i>
                                            <?= date('M d, Y', strtotime($game['updated_at'] ?? $game['created_at'])) ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php require_once("footer.php"); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Image Zoom Modal -->
    <div class="modal fade" id="imageZoomModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="zoomImageTitle"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="zoomedImage" src="" alt="" class="img-fluid rounded" style="max-height: 400px;">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="downloadImageBtn">
                        <i class="ri-download-line me-1"></i>Download
                    </button>
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
    // Initialize game counts
    function updateGameCounts() {
        const activeGames = document.querySelectorAll('.game-card.active').length;
        const inactiveGames = document.querySelectorAll('.game-card.inactive').length;
        
        document.getElementById('activeGamesCount').textContent = activeGames;
        document.getElementById('inactiveGamesCount').textContent = inactiveGames;
        document.getElementById('lastUpdated').textContent = new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
    }
    
    // Update image preview
    function updateImagePreview(gameId, url) {
        const preview = document.getElementById('preview_' + gameId);
        if (url) {
            preview.src = url;
        } else {
            preview.src = 'https://via.placeholder.com/120x120?text=No+Image';
        }
    }
    
    // Update game status
    function updateGameStatus(gameId, isActive) {
        const card = document.getElementById('gameCard_' + gameId);
        const badge = document.getElementById('statusBadge_' + gameId);
        const statusText = document.getElementById('statusText_' + gameId);
        const checkbox = document.getElementById('state_' + gameId);
        
        if (isActive) {
            card.classList.remove('inactive');
            card.classList.add('active');
            badge.className = 'badge bg-success';
            badge.textContent = 'Active';
            statusText.className = 'text-success fw-medium';
            statusText.textContent = 'Active';
        } else {
            card.classList.remove('active');
            card.classList.add('inactive');
            badge.className = 'badge bg-danger';
            badge.textContent = 'Inactive';
            statusText.className = 'text-danger fw-medium';
            statusText.textContent = 'Inactive';
        }
        
        updateGameCounts();
        
        // Auto-save the form
        const form = document.getElementById('gameForm_' + gameId);
        if (form) {
            const saveBtn = document.getElementById('saveBtn_' + gameId);
            if (saveBtn) {
                saveBtn.click();
            }
        }
    }
    
    // Toggle game manually (for label click)
    function toggleGame(gameId) {
        const checkbox = document.getElementById('state_' + gameId);
        if (checkbox) {
            checkbox.checked = !checkbox.checked;
            updateGameStatus(gameId, checkbox.checked);
        }
    }
    
    // Zoom image
    function zoomImage(url, title) {
        document.getElementById('zoomedImage').src = url;
        document.getElementById('zoomImageTitle').textContent = title;
        
        // Set download button
        document.getElementById('downloadImageBtn').onclick = function() {
            const link = document.createElement('a');
            link.href = url;
            link.download = title.replace(/[^a-z0-9]/gi, '_').toLowerCase() + '.png';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        };
        
        new bootstrap.Modal(document.getElementById('imageZoomModal')).show();
    }
    
    // Copy image URL
    function copyImageUrl(url) {
        navigator.clipboard.writeText(url).then(() => {
            // Show notification
            const originalBtn = event.target;
            const originalHTML = originalBtn.innerHTML;
            originalBtn.innerHTML = '<i class="ri-check-line"></i>';
            originalBtn.classList.add('text-success');
            
            setTimeout(() => {
                originalBtn.innerHTML = originalHTML;
                originalBtn.classList.remove('text-success');
            }, 2000);
        });
    }
    
    // Test image
    function testImage(url) {
        window.open(url, '_blank');
    }
    
    // Enable all games
    function enableAllGames() {
        if (confirm('Are you sure you want to enable all games?')) {
            document.querySelectorAll('.game-toggle').forEach(checkbox => {
                const gameId = checkbox.id.split('_')[1];
                checkbox.checked = true;
                updateGameStatus(gameId, true);
            });
        }
    }
    
    // Disable all games
    function disableAllGames() {
        if (confirm('Are you sure you want to disable all games?')) {
            document.querySelectorAll('.game-toggle').forEach(checkbox => {
                const gameId = checkbox.id.split('_')[1];
                checkbox.checked = false;
                updateGameStatus(gameId, false);
            });
        }
    }
    
    // Prevent card click from toggling
    document.addEventListener('DOMContentLoaded', function() {
        updateGameCounts();
        
        // Add loading state to images
        document.querySelectorAll('.game-image').forEach(img => {
            img.onerror = function() {
                this.src = 'https://via.placeholder.com/120x120?text=Image+Error';
            };
        });
        
        // Prevent card click - REMOVE ANY CARD CLICK EVENT LISTENERS
        document.querySelectorAll('.game-card').forEach(card => {
            // Remove any existing click events
            card.replaceWith(card.cloneNode(true));
            
            // Add proper click prevention
            card.addEventListener('click', function(e) {
                // Only allow clicks on specific elements
                const allowedElements = ['INPUT', 'BUTTON', 'A', 'LABEL', 'IMG'];
                if (!allowedElements.includes(e.target.tagName) && 
                    !e.target.classList.contains('toggle-label') &&
                    !e.target.closest('.image-actions') &&
                    !e.target.closest('.input-group')) {
                    e.stopPropagation();
                    return false;
                }
            }, true);
        });
        
        // Make toggle switches look better
        document.querySelectorAll('.game-toggle').forEach(toggle => {
            toggle.style.width = '60px';
            toggle.style.height = '30px';
            toggle.style.cursor = 'pointer';
        });
    });
    
    // Auto-save on image URL change (optional)
    document.querySelectorAll('.image-url-input').forEach(input => {
        let timeout;
        input.addEventListener('input', function() {
            clearTimeout(timeout);
            const gameId = this.dataset.gameId;
            const url = this.value;
            
            // Update preview immediately
            updateImagePreview(gameId, url);
            
            // Auto-save after 2 seconds of inactivity (optional)
            timeout = setTimeout(() => {
                if (url && url.startsWith('http')) {
                    const form = this.closest('form');
                    if (form) {
                        form.querySelector('button[type="submit"]').click();
                    }
                }
            }, 2000);
        });
    });
    </script>
</body>
</html>