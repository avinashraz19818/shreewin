<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (empty($_SESSION['unohs'])) {
    header("location: api/login.php?msg=unauthorized");
    exit();
}

include("api/conn.php");

mysqli_query($conn, "SET NAMES 'utf8mb4'");
mysqli_query($conn, "SET CHARACTER SET utf8mb4");
mysqli_query($conn, "SET SESSION collation_connection = 'utf8mb4_unicode_ci'");
?>

<!DOCTYPE html>
<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr"
    data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template" data-style="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>User Subtree Viewer</title>
    <meta name="description" content="" />
    <link rel="icon" type="image/x-icon" href="assets/img/favicon/favicon.ico?v=20260814-admin" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&amp;display=swap"
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
    
    <style>
        .subtree-container {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .search-section {
            /*background: white;*/
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .scroll-container {
            width: 100%;
            height: 600px;
            overflow: auto;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            /*background: white;*/
            position: relative;
        }
        .zoom-wrapper {
            padding: 20px;
            transform-origin: 0 0;
            transition: transform 0.3s ease;
        }
        .zoom-controls {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 1000;
            /*background: white;*/
            padding: 10px;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            gap: 5px;
            align-items: center;
        }
        .zoom-controls button {
            width: 30px;
            height: 30px;
            border: 1px solid #dee2e6;
            /*background: white;*/
            border-radius: 3px;
            cursor: pointer;
            font-weight: bold;
        }
        .zoom-controls button:hover {
            background: #f8f9fa;
        }
        .zoom-label {
            margin-left: 10px;
            font-size: 12px;
            color: #6c757d;
            min-width: 40px;
        }
        
        /* Tree Styles */
        .tree {
            min-width: max-content;
        }
        .tree ul {
            padding-top: 20px;
            position: relative;
            transition: all 0.5s;
            -webkit-transition: all 0.5s;
            -moz-transition: all 0.5s;
        }
        .tree li {
            float: left;
            text-align: center;
            list-style-type: none;
            position: relative;
            padding: 20px 5px 0 5px;
            transition: all 0.5s;
            -webkit-transition: all 0.5s;
            -moz-transition: all 0.5s;
        }
        .tree li::before, .tree li::after {
            content: '';
            position: absolute;
            top: 0;
            right: 50%;
            border-top: 1px solid #ccc;
            width: 50%;
            height: 20px;
        }
        .tree li::after {
            right: auto;
            left: 50%;
            border-left: 1px solid #ccc;
        }
        .tree li:only-child::after, .tree li:only-child::before {
            display: none;
        }
        .tree li:only-child {
            padding-top: 0;
        }
        .tree li:first-child::before, .tree li:last-child::after {
            border: 0 none;
        }
        .tree li:last-child::before {
            border-right: 1px solid #ccc;
            border-radius: 0 5px 0 0;
            -webkit-border-radius: 0 5px 0 0;
            -moz-border-radius: 0 5px 0 0;
        }
        .tree li:first-child::after {
            border-radius: 5px 0 0 0;
            -webkit-border-radius: 5px 0 0 0;
            -moz-border-radius: 5px 0 0 0;
        }
        .tree ul ul::before {
            content: '';
            position: absolute;
            top: 0;
            left: 50%;
            border-left: 1px solid #ccc;
            width: 0;
            height: 20px;
        }
        .tree li .box {
            border: 1px solid #ccc;
            padding: 10px 15px;
            text-decoration: none;
            color: #666;
            font-family: arial, verdana, tahoma;
            font-size: 12px;
            display: inline-block;
            border-radius: 5px;
            -webkit-border-radius: 5px;
            -moz-border-radius: 5px;
            transition: all 0.5s;
            -webkit-transition: all 0.5s;
            -moz-transition: all 0.5s;
            /*background: white;*/
            min-width: 120px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .tree li .box:hover, .tree li .box:hover+ul li .box {
            background: #e8f4fd;
            color: #000;
            border: 1px solid #94a0b4;
        }
        .tree li .box:hover+ul li::after, 
        .tree li .box:hover+ul li::before, 
        .tree li .box:hover+ul::before, 
        .tree li .box:hover+ul ul::before {
            border-color: #94a0b4;
        }
        .main-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            color: white !important;
            border-color: #5a6fd8 !important;
            font-weight: bold;
        }
        .level-1 .box { background: #e3f2fd !important; }
        .level-2 .box { background: #f3e5f5 !important; }
        .level-3 .box { background: #e8f5e8 !important; }
        .level-4 .box { background: #fff3e0 !important; }
        .level-5 .box { background: #fbe9e7 !important; }
        
        .stats-card {
            /*background: white;*/
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stats-value {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
            display: block;
        }
        .stats-label {
            font-size: 12px;
            color: #6c757d;
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
                        
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h4 class="card-title mb-0">
                                            <i class="ri-group-line ri-16px me-2"></i>
                                            User Subtree Viewer
                                        </h4>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Search Section -->
                        <div class="row">
                            <div class="col-md-8">
                                <div class="search-section">
                                    <h5 class="mb-3"><i class="ri-search-line ri-16px me-2"></i>Search User</h5>
                                    <form method="GET" class="row">
                                        <div class="col-md-8 mb-3">
                                            <label for="uid" class="form-label">Enter User ID</label>
                                            <input type="number" class="form-control" id="uid" name="uid" 
                                                   value="<?= htmlspecialchars($_GET['uid'] ?? '') ?>" 
                                                   placeholder="Enter user ID to view subtree" required>
                                        </div>
                                        <div class="col-md-4 mb-3 d-flex align-items-end">
                                            <button type="submit" class="btn btn-primary w-100">
                                                <i class="ri-eye-line ri-16px me-2"></i>View Subtree
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="stats-card">
                                    <span class="stats-value" id="totalMembers">0</span>
                                    <span class="stats-label">Total Members</span>
                                </div>
                                <div class="stats-card">
                                    <span class="stats-value" id="treeLevels">0</span>
                                    <span class="stats-label">Tree Levels</span>
                                </div>
                            </div>
                        </div>

                        <!-- Subtree Display -->
                        <?php if (isset($_GET['uid'])): ?>
                        <div class="row">
                            <div class="col-12">
                                <div class="subtree-container">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="mb-0">
                                            <i class="ri-node-tree ri-16px me-2"></i>
                                            Subtree for User ID: <?= htmlspecialchars($_GET['uid']) ?>
                                        </h5>
                                        <div class="btn-group">
                                            <button class="btn btn-outline-secondary btn-sm" onclick="downloadTree()">
                                                <i class="ri-download-line ri-14px me-1"></i>Export
                                            </button>
                                            <button class="btn btn-outline-secondary btn-sm" onclick="printTree()">
                                                <i class="ri-printer-line ri-14px me-1"></i>Print
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <?php
                                    $uid = intval($_GET['uid']);
                                    $stmt = $conn->prepare("SELECT owncode FROM shonu_subjects WHERE id = ?");
                                    $stmt->bind_param("i", $uid);
                                    $stmt->execute();
                                    $stmt->bind_result($owncode);
                                    $stmt->fetch();
                                    $stmt->close();

                                    if ($owncode) {
                                        $totalMembers = 1; // Count the main user
                                        $treeLevels = 0;
                                        
                                        echo "<div class='scroll-container'>";
                                        echo "<div class='zoom-wrapper'>";

                                        // Zoom controls
                                        echo "
                                        <div class='zoom-controls'>
                                            <button onclick='zoomIn()' title='Zoom In'>+</button>
                                            <button onclick='zoomOut()' title='Zoom Out'>−</button>
                                            <button onclick='resetZoom()' title='Reset Zoom'>⟳</button>
                                            <div class='zoom-label' id='zoomLabel'>100%</div>
                                        </div>";

                                        // Tree start
                                        echo "<div id='zoomContainer'><div class='tree'>";
                                        echo "<ul><li><div class='box main-box'><strong>UID: $uid</strong><br>Code: $owncode</div>";

                                        $levels = ['code', 'code1', 'code2', 'code3', 'code4', 'code5'];
                                        $levelCount = 0;
                                        
                                        foreach ($levels as $level) {
                                            $query = "SELECT id, mobile, owncode FROM shonu_subjects WHERE $level = ?";
                                            $stmt2 = $conn->prepare($query);
                                            if ($stmt2) {
                                                $stmt2->bind_param("s", $owncode);
                                                $stmt2->execute();
                                                $result = $stmt2->get_result();

                                                if ($result->num_rows > 0) {
                                                    $levelCount++;
                                                    echo "<ul class='level-$levelCount'>";
                                                    while ($row = $result->fetch_assoc()) {
                                                        $totalMembers++;
                                                        echo "<li><div class='box'>UID: {$row['id']}<br>Ph: {$row['mobile']}<br>Code: {$row['owncode']}</div></li>";
                                                    }
                                                    echo "</ul>";
                                                }
                                                $stmt2->close();
                                            }
                                        }
                                        
                                        $treeLevels = $levelCount;

                                        echo "</li></ul></div></div></div></div>";
                                        
                                        // Update statistics
                                        echo "<script>
                                            document.getElementById('totalMembers').textContent = '$totalMembers';
                                            document.getElementById('treeLevels').textContent = '$treeLevels';
                                        </script>";
                                        
                                    } else {
                                        echo "<div class='alert alert-danger'>User ID not found.</div>";
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-body text-center py-5">
                                        <i class="ri-group-line ri-4x text-muted mb-3"></i>
                                        <h5 class="text-muted">Enter a User ID to view their subtree</h5>
                                        <p class="text-muted">The tree will show all users in the referral hierarchy</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- User Statistics -->
                        <?php if (isset($_GET['uid']) && isset($owncode)): ?>
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <i class="ri-bar-chart-line ri-16px me-2"></i>
                                            Subtree Statistics
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-3 text-center">
                                                <div class="stats-card">
                                                    <span class="stats-value"><?= $totalMembers ?></span>
                                                    <span class="stats-label">Total Members</span>
                                                </div>
                                            </div>
                                            <div class="col-md-3 text-center">
                                                <div class="stats-card">
                                                    <span class="stats-value"><?= $treeLevels ?></span>
                                                    <span class="stats-label">Tree Levels</span>
                                                </div>
                                            </div>
                                            <div class="col-md-3 text-center">
                                                <div class="stats-card">
                                                    <span class="stats-value"><?= $uid ?></span>
                                                    <span class="stats-label">Root User ID</span>
                                                </div>
                                            </div>
                                            <div class="col-md-3 text-center">
                                                <div class="stats-card">
                                                    <span class="stats-value"><?= $owncode ?></span>
                                                    <span class="stats-label">Referral Code</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                    </div>
                    <?php require_once("footer.php"); ?>
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
    <script src="assets/vendor/libs/moment/moment.js"></script>
    <script src="assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js"></script>
    <script src="assets/vendor/libs/select2/select2.js"></script>
    <script src="assets/vendor/libs/@form-validation/popular.js"></script>
    <script src="assets/vendor/libs/@form-validation/bootstrap5.js"></script>
    <script src="assets/vendor/libs/@form-validation/auto-focus.js"></script>
    <script src="assets/vendor/libs/cleavejs/cleave.js"></script>
    <script src="assets/vendor/libs/cleavejs/cleave-phone.js"></script>
    <script src="assets/js/main.js"></script>

    <script>
        let currentZoom = 1;
        const zoomStep = 0.1;
        const minZoom = 0.3;
        const maxZoom = 3;

        function zoomIn() {
            if (currentZoom < maxZoom) {
                currentZoom += zoomStep;
                applyZoom();
            }
        }

        function zoomOut() {
            if (currentZoom > minZoom) {
                currentZoom -= zoomStep;
                applyZoom();
            }
        }

        function resetZoom() {
            currentZoom = 1;
            applyZoom();
        }

        function applyZoom() {
            const zoomWrapper = document.querySelector('.zoom-wrapper');
            const zoomLabel = document.getElementById('zoomLabel');
            
            zoomWrapper.style.transform = `scale(${currentZoom})`;
            zoomLabel.textContent = Math.round(currentZoom * 100) + '%';
        }

        function downloadTree() {
            alert('Tree export feature would be implemented here. This could generate a PDF or image of the tree structure.');
        }

        function printTree() {
            window.print();
        }

        // Auto-focus search input
        document.addEventListener('DOMContentLoaded', function() {
            const uidInput = document.getElementById('uid');
            if (uidInput && !uidInput.value) {
                uidInput.focus();
            }
            
            // Add keyboard shortcuts
            document.addEventListener('keydown', function(e) {
                if (e.ctrlKey && e.key === '+') {
                    e.preventDefault();
                    zoomIn();
                } else if (e.ctrlKey && e.key === '-') {
                    e.preventDefault();
                    zoomOut();
                } else if (e.ctrlKey && e.key === '0') {
                    e.preventDefault();
                    resetZoom();
                }
            });
        });
    </script>
</body>
</html>