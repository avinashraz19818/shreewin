<?php
	session_start();
	if (empty($_SESSION['unohs'])) {
		header("location: api/login.php?msg=unauthorized");
		exit();
	}

	include("api/conn.php");

$fileUsageMap = [
    'style.css' => 'Login Page',
    'dashboard.css' => 'Dashboard',
    'theme.css' => 'Main Theme',
];

$cssDir = dirname(__DIR__) . '/assets/css/';
$cssFiles = glob($cssDir . '*.css');
$totalChangedFiles = 0;
$allColorsUsed = [];
$totalRGB = 0;

// Pagination
$perPage = 5;
$totalFiles = count($cssFiles);
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$start = ($page - 1) * $perPage;
$pagedFiles = array_slice($cssFiles, $start, $perPage);
$totalPages = ceil($totalFiles / $perPage);

// Handle RESET ALL
if (isset($_GET['reset_all']) && $_GET['reset_all'] == 1) {
    foreach ($cssFiles as $file) {
        if (file_exists($file . '.bak')) {
            copy($file . '.bak', $file);
            $totalChangedFiles++;
        }
    }
    $msg = "✅ All files reverted from backup.";
}

// Handle per-file RESET
if (isset($_GET['reset']) && $_GET['reset'] != '') {
    $target = basename($_GET['reset']);
    $filePath = $cssDir . $target;
    if (file_exists($filePath . '.bak')) {
        copy($filePath . '.bak', $filePath);
        $msg = "✅ <b>$target</b> reverted from backup.";
    }
}

// Handle SAVE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['color_map']) && isset($_POST['file'])) {
    $file = $_POST['file'];
    $filePath = realpath($file);
    if (strpos($filePath, realpath($cssDir)) !== 0) die("Invalid access");

    if (!file_exists($filePath . '.bak')) {
        copy($filePath, $filePath . '.bak');
    }

    $contents = file_get_contents($filePath);
    $changed = false;
    foreach ($_POST['color_map'] as $old => $new) {
        if (trim($old) !== trim($new)) {
            $contents = str_ireplace($old, $new, $contents);
            $changed = true;
        }
    }

    if ($changed) {
        file_put_contents($filePath, $contents);
        $msg = "✅ <b>" . basename($file) . "</b> updated successfully!";
        $totalChangedFiles++;
    }
}

function extractColors($content, &$rgbCount = 0) {
    $colors = [];
    preg_match_all('/#(?:[0-9a-fA-F]{3}){1,2}\b|rgba?\([^)]+\)/i', $content, $matches);
    foreach ($matches[0] as $color) {
        $colors[strtolower($color)] = true;
        if (stripos($color, 'rgb') === 0) {
            $rgbCount++;
        }
    }
    return array_keys($colors);
}

function convertToHex($color) {
    $color = trim(strtolower($color));
    if (preg_match('/^#([a-f0-9]{3,6})$/i', $color)) return $color;
    if (preg_match('/rgba?\((\d+),\s*(\d+),\s*(\d+)/', $color, $m)) {
        return sprintf("#%02x%02x%02x", $m[1], $m[2], $m[3]);
    }
    return '#000000';
}
?>

<!doctype html>
<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr"
	data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template" data-style="light">

<head>
	<meta charset="utf-8" />
	<meta name="viewport"
		content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
	<title>Web Settings</title>

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
	
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

	<!-- Custom Responsive CSS for setting-card -->
	<style>
        body
        main { padding: 20px; max-width: 100%; width: 100%; box-sizing: border-box; }
        .stats { display: flex; gap: 20px; margin-bottom: 20px; flex-wrap: wrap; justify-content: space-between; }
        .stat-box { padding: 15px 20px; border-radius: 8px; box-shadow: 0 0 5px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 10px; flex: 1; min-width: 220px; }
        .stat-box i { font-size: 28px; color: #007bff; }
        .file-block { border: 1px solid #dddddd1a; padding: 15px; margin-bottom: 25px; border-radius: 8px; box-shadow: 0 0 4px rgba(0,0,0,0.05); display: flex; justify-content: space-between; flex-wrap: wrap; }
        .file-block .left { flex: 1; }
        .file-block .right { flex: 1; max-width: 320px; text-align: right; font-size: 14px; color: #fff; line-height: 1.6; }
        .color-row { display: flex; align-items: center; margin-bottom: 10px; gap: 12px; }
        .color-sample { width: 30px; height: 30px; border-radius: 4px; border: 1px solid #ccc; }
        input[type="color"] { width: 50px; height: 30px; }
        button, .btn-reset { padding: 8px 16px; background: #0000002b; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .btn-reset-all {
            margin: 30px auto 40px auto;
            padding: 16px 0;
            font-size: 18px;
            width: 100%;
            max-width: 1000px;
            display: block;
            text-align: center;
            border-radius: 33px;
        }
        .btn-reset:hover, .btn-reset-all:hover { background: #c82333; }
        h2 { margin-bottom: 10px; }
        .success { padding: 12px; background: #d4edda; color: #155724; border: 1px solid #c3e6cb; border-radius: 5px; margin-bottom: 20px; }
        .pagination { margin-top: 30px; text-align: center; }
        .pagination a { margin: 0 5px; padding: 8px 12px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; }
        .pagination a:hover { background: #0056b3; }
    </style>
	<style>
		.setting-card {
			display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.2rem 1.5rem;
            margin: 1rem 0;
            /* border: 1px solid #ddd; */
            border-radius: 12px;
            /* background-color: #fff; */
            /* box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04); */
            flex-wrap: wrap;
		}

		.card-left {
			display: flex;
			align-items: center;
			gap: 1rem;
			flex: 1 1 300px;
		}

		.card-left .icon {
			font-size: 36px;
			color: #6366f1;
		}

		.card-left .title {
			font-weight: 600;
			font-size: 1.1rem;
		}

		.card-left .desc {
			font-size: 0.9rem;
			color: #6b7280;
			margin-top: 4px;
		}

		.card-right {
			display: flex;
			align-items: center;
			gap: 1rem;
			flex: 0 0 auto;
			margin-top: 1rem;
		}

		.switch {
			position: relative;
			display: inline-block;
			width: 48px;
			height: 24px;
		}

		.switch input {
			opacity: 0;
			width: 0;
			height: 0;
		}

		.slider {
			position: absolute;
			cursor: pointer;
			inset: 0;
			background-color: #ccc;
			transition: 0.4s;
			border-radius: 24px;
		}

		.slider:before {
			position: absolute;
			content: "";
			height: 18px;
			width: 18px;
			left: 3px;
			bottom: 3px;
			background-color: white;
			transition: 0.4s;
			border-radius: 50%;
		}

		input:checked + .slider {
			background-color: #6366f1;
		}

		input:checked + .slider:before {
			transform: translateX(24px);
		}

		.status {
			font-weight: bold;
			font-size: 0.95rem;
			color: <?= $wingo30status === 'active' ? '#10b981' : '#ef4444' ?>;
		}

		@media (max-width: 768px) {
			.setting-card {
				flex-direction: column;
				align-items: flex-start;
			}

			.card-right {
				margin-top: 1rem;
			}
		}
	</style>
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
    
          <div class="content-wrapper">
            <div class="container-xxl flex-grow-1 container-p-y">
    
              <div class="card">
                <div class="table-responsive text-nowrap">
    
                   <div class="card shadow-sm p-4">
                    <h2>🎯 CSS Color Editor - Admin Panel</h2>
                    <?php if (isset($msg)) echo "<div class='success'>$msg</div>"; ?>
                    <div class="stats">
                        <div class="stat-box"><i class="material-icons">layers</i> <div><b>Total Files:</b><br><?= $totalFiles ?></div></div>
                        <div class="stat-box"><i class="material-icons">palette</i> <div><b>Total Colors:</b><br><?php
                            $colorSet = [];
                            foreach ($cssFiles as $file) {
                                $content = file_get_contents($file);
                                $colors = extractColors($content);
                                foreach ($colors as $c) $colorSet[strtolower($c)] = true;
                            }
                            echo count($colorSet);
                        ?></div></div>
                        <div class="stat-box"><i class="material-icons">edit</i> <div><b>Total Changes:</b><br><?= $totalChangedFiles ?></div></div>
                        <div class="stat-box"><i class="material-icons">gradient</i> <div><b>Total RGB:</b><br><?php
                            $rgbCount = 0;
                            foreach ($cssFiles as $file) {
                                $content = file_get_contents($file);
                                extractColors($content, $rgbCount);
                            }
                            echo $rgbCount;
                        ?></div></div>
                    </div>
                    <a class="btn-reset btn-reset-all" href="?reset_all=1" onclick="return confirm('Revert all CSS files from backup?')">♻️ Reset All</a>
                    
                    <?php foreach ($pagedFiles as $file): ?>
                        <?php
                        $content = file_get_contents($file);
                        $colors = extractColors($content, $rgbCount = 0);
                        $lastModified = date("d-M-Y h:i A", filemtime($file));
                        $basename = basename($file);
                        $usage = isset($fileUsageMap[$basename]) ? $fileUsageMap[$basename] : 'Unknown';
                        ?>
                        <div class="file-block">
                            <div class="left">
                                <h3>🗂 <?= $basename ?></h3>
                                <form method="post">
                                    <input type="hidden" name="file" value="<?= htmlspecialchars($file) ?>">
                                    <?php foreach ($colors as $color): ?>
                                        <div class="color-row">
                                            <div class="color-sample" style="background: <?= htmlspecialchars($color) ?>"></div>
                                            <label><?= htmlspecialchars($color) ?></label>
                                            <input type="color" name="color_map[<?= htmlspecialchars($color) ?>]" value="<?= htmlspecialchars(convertToHex($color)) ?>">
                                        </div>
                                    <?php endforeach; ?>
                                    <button type="submit">💾 Save Changes</button>
                                    <a class="btn-reset" href="?reset=<?= urlencode($basename) ?>&page=<?= $page ?>" onclick="return confirm('Reset this file?')">♻️ Revert</a>
                                </form>
                            </div>
                            <div class="right">
                                📝 Usage: <?= $usage ?><br>
                                🎨 Total Colors: <?= count($colors) ?><br>
                                🎯 Unique RGB: <?= $rgbCount ?><br>
                                📅 Last Modified: <?= $lastModified ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="pagination">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="?page=<?= $i ?>"<?= $i === $page ? ' style="font-weight:bold;background:#0056b3;"' : '' ?>><?= $i ?></a>
                        <?php endfor; ?>
                    </div>

                </div>
                </div>
              </div>
    
            </div>
    
            <?php include("footer.php"); ?>
            <div class="content-backdrop fade"></div>
          </div>
        </div>
      </div>
    
      <div class="layout-overlay layout-menu-toggle"></div>
      <div class="drag-target"></div>
    </div>


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

</body>

</html>