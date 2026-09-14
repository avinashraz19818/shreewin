<?php
session_start();
if (empty($_SESSION['unohs'])) {
    header("location: api/login.php?msg=unauthorized");
}
include("api/conn.php");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_telegram'])) {
        $support_link = $_POST['support_link'];
        $agentline_link = $_POST['agentline_link'];
        $promotion_bot = $_POST['promotion_bot'];

        // Check if settings exist
        $check = $conn->query("SELECT id FROM mr_telegram LIMIT 1");

        if ($check->num_rows > 0) {
            // Update existing
            $stmt = $conn->prepare("UPDATE mr_telegram SET support_link = ?, agentline_link = ?, promotion_bot = ?, updated_at = NOW() WHERE id = 1");
            $stmt->bind_param("sss", $support_link, $agentline_link, $promotion_bot);
        } else {
            // Insert new
            $stmt = $conn->prepare("INSERT INTO mr_telegram (support_link, agentline_link, promotion_bot) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $support_link, $agentline_link, $promotion_bot);
        }

        if ($stmt->execute()) {
            $_SESSION['success'] = "Telegram settings updated successfully!";
        } else {
            $_SESSION['error'] = "Error updating settings!";
        }

        header("Location: telegram.php");
        exit();
    }
}

// Fetch current settings
$settings = [];
$result = $conn->query("SELECT * FROM mr_telegram LIMIT 1");
if ($result->num_rows > 0) {
    $settings = $result->fetch_assoc();
}

// Default values if not set
$support_link = $settings['support_link'] ?? 'https://t.me/zayro_o';
$agentline_link = $settings['agentline_link'] ?? 'https://t.me/zayro_o';
$promotion_bot = $settings['promotion_bot'] ?? 'https://t.me/zayro_o';
?>

<!doctype html>
<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr"
    data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template" data-style="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Telegram Settings</title>
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
        .telegram-card {
            transition: all 0.3s ease;
            border-radius: 15px;
            overflow: hidden;
            border: 2px solid transparent;
        }

        .telegram-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border-color: #0088cc;
        }

        .telegram-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #0088cc, #24a2e0);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 36px;
        }

        .telegram-badge {
            background: linear-gradient(135deg, #0088cc, #24a2e0);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 10px;
        }

        .link-preview {
            border-radius: 8px;
            padding: 10px;
            margin-top: 15px;
            border: 1px dashed #dee2e6;
            word-break: break-all;
        }

        .test-btn {
            background: linear-gradient(135deg, #0088cc, #24a2e0);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .test-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(0, 136, 204, 0.3);
        }

        .copy-btn {
            position: absolute;
            right: 10px;
            top: 10px;
            background: #000;
            border: none;
            border-radius: 4px;
            padding: 4px 8px;
            cursor: pointer;
        }

        .qr-container {
            text-align: center;
            padding: 20px;
            background: white;
            border-radius: 10px;
            border: 2px solid #0088cc;
            margin-top: 20px;
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
                        <?php if (isset($_SESSION['success'])): ?>
                            <div class="alert alert-success alert-dismissible" role="alert">
                                <?= $_SESSION['success'] ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php unset($_SESSION['success']);
                        endif; ?>

                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger alert-dismissible" role="alert">
                                <?= $_SESSION['error'] ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php unset($_SESSION['error']);
                        endif; ?>

                        <!-- Header -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <div>
                                            <h4 class="card-title mb-0">
                                                <i class="ri-telegram-line me-2" style="color: #0088cc;"></i>
                                                Telegram Settings
                                            </h4>
                                            <p class="text-muted mb-0">Manage all Telegram links for your platform</p>
                                        </div>
                                        <a href="https://t.me/zayro_o" target="_blank" class="btn btn-outline-primary">
                                            <i class="ri-external-link-line me-2"></i>Telegram Website
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Telegram Links Form -->
                        <form method="POST" action="">
                            <div class="row mb-4">
                                <!-- Support Telegram -->
                                <div class="col-lg-4 col-md-6 mb-4">
                                    <div class="card telegram-card h-100">
                                        <div class="card-body text-center">
                                            <div class="telegram-icon">
                                                <i class="ri-customer-service-2-fill"></i>
                                            </div>
                                            <span class="telegram-badge">Support</span>
                                            <h5 class="mb-3">Support Telegram</h5>
                                            <p class="text-muted mb-4">Official support channel for customer queries and assistance</p>

                                            <div class="mb-3">
                                                <label class="form-label">Support Link</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="ri-link"></i></span>
                                                    <input type="url" class="form-control" name="support_link"
                                                        value="<?= htmlspecialchars($support_link) ?>"
                                                        placeholder="https://t.me/zayro_o" required>
                                                </div>
                                                <div class="form-text">Enter full Telegram channel/group link</div>
                                            </div>

                                            <div class="link-preview position-relative">
                                                <small class="text-muted">Preview:</small>
                                                <div class="mt-1" id="supportPreview">
                                                    <a href="<?= htmlspecialchars($support_link) ?>" target="_blank" class="text-primary">
                                                        <?= htmlspecialchars($support_link) ?>
                                                    </a>
                                                </div>
                                                <button type="button" class="copy-btn" onclick="copyToClipboard('<?= htmlspecialchars($support_link) ?>', 'support')">
                                                    <i class="ri-file-copy-line"></i>
                                                </button>
                                            </div>

                                            <div class="mt-4">
                                                <a href="<?= htmlspecialchars($support_link) ?>" target="_blank" class="test-btn text-decoration-none">
                                                    <i class="ri-test-tube-line me-2"></i>Test Link
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Agentline Telegram -->
                                <div class="col-lg-4 col-md-6 mb-4">
                                    <div class="card telegram-card h-100">
                                        <div class="card-body text-center">
                                            <div class="telegram-icon">
                                                <i class="ri-team-fill"></i>
                                            </div>
                                            <span class="telegram-badge">Agents</span>
                                            <h5 class="mb-3">Agentline Telegram</h5>
                                            <p class="text-muted mb-4">Dedicated channel for agents communication and updates</p>

                                            <div class="mb-3">
                                                <label class="form-label">Agentline Link</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="ri-link"></i></span>
                                                    <input type="url" class="form-control" name="agentline_link"
                                                        value="<?= htmlspecialchars($agentline_link) ?>"
                                                        placeholder="https://t.me/zayro_o" required>
                                                </div>
                                                <div class="form-text">Agent communication channel link</div>
                                            </div>

                                            <div class="link-preview position-relative">
                                                <small class="text-muted">Preview:</small>
                                                <div class="mt-1" id="agentlinePreview">
                                                    <a href="<?= htmlspecialchars($agentline_link) ?>" target="_blank" class="text-primary">
                                                        <?= htmlspecialchars($agentline_link) ?>
                                                    </a>
                                                </div>
                                                <button type="button" class="copy-btn" onclick="copyToClipboard('<?= htmlspecialchars($agentline_link) ?>', 'agentline')">
                                                    <i class="ri-file-copy-line"></i>
                                                </button>
                                            </div>

                                            <div class="mt-4">
                                                <a href="<?= htmlspecialchars($agentline_link) ?>" target="_blank" class="test-btn text-decoration-none">
                                                    <i class="ri-test-tube-line me-2"></i>Test Link
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Promotion Bot -->
                                <div class="col-lg-4 col-md-6 mb-4">
                                    <div class="card telegram-card h-100">
                                        <div class="card-body text-center">
                                            <div class="telegram-icon">
                                                <i class="ri-robot-fill"></i>
                                            </div>
                                            <span class="telegram-badge">Promotion</span>
                                            <h5 class="mb-3">Telegram Promotion Bot</h5>
                                            <p class="text-muted mb-4">Automated bot for promotions, announcements and updates</p>

                                            <div class="mb-3">
                                                <label class="form-label">Bot Link</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="ri-link"></i></span>
                                                    <input type="url" class="form-control" name="promotion_bot"
                                                        value="<?= htmlspecialchars($promotion_bot) ?>"
                                                        placeholder="https://t.me/zayro_o" required>
                                                </div>
                                                <div class="form-text">Telegram bot username/link</div>
                                            </div>

                                            <div class="link-preview position-relative">
                                                <small class="text-muted">Preview:</small>
                                                <div class="mt-1" id="botPreview">
                                                    <a href="<?= htmlspecialchars($promotion_bot) ?>" target="_blank" class="text-primary">
                                                        <?= htmlspecialchars($promotion_bot) ?>
                                                    </a>
                                                </div>
                                                <button type="button" class="copy-btn" onclick="copyToClipboard('<?= htmlspecialchars($promotion_bot) ?>', 'bot')">
                                                    <i class="ri-file-copy-line"></i>
                                                </button>
                                            </div>

                                            <div class="mt-4">
                                                <a href="<?= htmlspecialchars($promotion_bot) ?>" target="_blank" class="test-btn text-decoration-none">
                                                    <i class="ri-test-tube-line me-2"></i>Test Link
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="row">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-body text-center">
                                            <button type="submit" name="update_telegram" class="btn btn-primary btn-lg px-5">
                                                <i class="ri-save-line me-2"></i>
                                                Save All Telegram Settings
                                            </button>
                                            <div class="form-text mt-2">Changes will be reflected immediately in the application</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>

                        <!-- Quick Actions -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">Quick Actions</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row text-center">
                                            <div class="col-md-4 mb-3">
                                                <button type="button" class="btn btn-outline-primary w-100" onclick="openAllLinks()">
                                                    <i class="ri-external-link-line me-2"></i>Open All Links
                                                </button>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <button type="button" class="btn btn-outline-success w-100" onclick="copyAllLinks()">
                                                    <i class="ri-file-copy-line me-2"></i>Copy All Links
                                                </button>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <button type="button" class="btn btn-outline-info w-100" onclick="generateQR()">
                                                    <i class="ri-qr-code-line me-2"></i>Generate QR Codes
                                                </button>
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

    <!-- QR Modal -->
    <div class="modal fade" id="qrModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Telegram QR Codes</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row" id="qrCodesContainer">
                        <!-- QR codes will be generated here -->
                    </div>
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
        // Copy to clipboard function
        function copyToClipboard(text, type) {
            navigator.clipboard.writeText(text).then(() => {
                // Show success notification
                const originalText = event.target.innerHTML;
                event.target.innerHTML = '<i class="ri-check-line"></i> Copied!';
                event.target.classList.add('text-success');

                setTimeout(() => {
                    event.target.innerHTML = originalText;
                    event.target.classList.remove('text-success');
                }, 2000);
            });
        }

        // Copy all links
        function copyAllLinks() {
            const links = [
                document.querySelector('[name="support_link"]').value,
                document.querySelector('[name="agentline_link"]').value,
                document.querySelector('[name="promotion_bot"]').value
            ];

            const text = `Support: ${links[0]}\nAgentline: ${links[1]}\nPromotion Bot: ${links[2]}`;

            navigator.clipboard.writeText(text).then(() => {
                alert('All links copied to clipboard!');
            });
        }

        // Open all links
        function openAllLinks() {
            const links = [
                document.querySelector('[name="support_link"]').value,
                document.querySelector('[name="agentline_link"]').value,
                document.querySelector('[name="promotion_bot"]').value
            ];

            links.forEach(link => {
                if (link.startsWith('http')) {
                    window.open(link, '_blank');
                }
            });
        }

        // Generate QR codes
        function generateQR() {
            const links = {
                'Support': document.querySelector('[name="support_link"]').value,
                'Agentline': document.querySelector('[name="agentline_link"]').value,
                'Promotion Bot': document.querySelector('[name="promotion_bot"]').value
            };

            let qrHTML = '';

            for (const [name, link] of Object.entries(links)) {
                if (link) {
                    const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=${encodeURIComponent(link)}`;

                    qrHTML += `
                <div class="col-md-4 text-center mb-4">
                    <div class="qr-container">
                        <h6 class="mb-3">${name}</h6>
                        <img src="${qrUrl}" alt="${name} QR" class="img-fluid mb-3">
                        <a href="${link}" target="_blank" class="btn btn-sm btn-outline-primary">
                            <i class="ri-external-link-line me-1"></i>Open
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-secondary ms-2" onclick="downloadQR('${qrUrl}', '${name}')">
                            <i class="ri-download-line me-1"></i>Download
                        </button>
                    </div>
                </div>`;
                }
            }

            document.getElementById('qrCodesContainer').innerHTML = qrHTML;
            new bootstrap.Modal(document.getElementById('qrModal')).show();
        }

        // Download QR code
        function downloadQR(url, name) {
            const link = document.createElement('a');
            link.href = url;
            link.download = `telegram_${name.toLowerCase().replace(' ', '_')}_qr.png`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        // Live preview update
        document.querySelectorAll('input[type="url"]').forEach(input => {
            input.addEventListener('input', function() {
                const type = this.name.replace('_link', '').replace('promotion_bot', 'bot');
                const previewId = type + 'Preview';
                const link = this.value;

                const previewDiv = document.getElementById(previewId);
                if (previewDiv) {
                    previewDiv.innerHTML = link ?
                        `<a href="${link}" target="_blank" class="text-primary">${link}</a>` :
                        '<span class="text-muted">No link provided</span>';
                }
            });
        });
    </script>
</body>

</html>