<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (empty($_SESSION['unohs'])) {
    header("location: api/login.php?msg=unauthorized");
    exit();
}
include("api/conn.php");

// Handle form submission for API credentials
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $api_username = mysqli_real_escape_string($conn, $_POST['api_username']);
    $api_password = mysqli_real_escape_string($conn, $_POST['api_password']);
    
    // Check if settings exist
    $check = mysqli_query($conn, "SELECT id FROM api_panel_settings LIMIT 1");
    
    if (mysqli_num_rows($check) > 0) {
        $query = "UPDATE api_panel_settings SET 
                  api_username = '$api_username',
                  api_password = '$api_password',
                  updated_at = NOW()";
    } else {
        $query = "INSERT INTO api_panel_settings 
                  (api_username, api_password) 
                  VALUES ('$api_username', '$api_password')";
    }
    
    if (mysqli_query($conn, $query)) {
        $_SESSION['success'] = "API credentials saved successfully!";
    } else {
        $_SESSION['error'] = "Error: " . mysqli_error($conn);
    }
    
    header("Location: api_panel.php");
    exit();
}

// Get current settings
$settings = [];
$result = mysqli_query($conn, "SELECT * FROM api_panel_settings LIMIT 1");
if (mysqli_num_rows($result) > 0) {
    $settings = mysqli_fetch_assoc($result);
} else {
    $settings = [
        'api_username' => '',
        'api_password' => ''
    ];
}
?>

<!doctype html>
<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr" 
    data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template" data-style="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>API Panel</title>
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
    <link rel="stylesheet" href("assets/vendor/css/rtl/theme-default.css" class="template-customizer-theme-css" />
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
        .api-container { border: 1px solid #e3e6f0; border-radius: 0.35rem; padding: 1.5rem; margin-bottom: 1.5rem; }
        .frame-container { 
            width: 100%; 
            height: calc(100vh - 250px); 
            min-height: 600px; 
            border: 1px solid #e3e6f0; 
            border-radius: 0.35rem; 
            overflow: hidden; 
            margin-top: 1rem;
            position: relative;
        }
        .frame-toolbar { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 0.75rem 1rem; 
            border-bottom: 1px solid rgba(255,255,255,0.1); 
            display: flex; 
            align-items: center; 
            gap: 0.5rem; 
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
            height: 50px;
        }
        .frame-wrapper { 
            width: 100%; 
            height: 100%; 
            padding-top: 50px;
            box-sizing: border-box;
        }
        iframe { 
            width: 100%; 
            height: 100%; 
            border: none; 
            background: white;
        }
        .copy-btn { 
            transition: all 0.2s; 
            border-radius: 0.25rem;
        }
        .copy-btn:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .copy-btn.copied { 
            background: #28a745 !important; 
            border-color: #28a745 !important; 
            color: white;
        }
        .nav-btn {
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }
        .nav-btn:hover {
            background: rgba(255,255,255,0.3);
            transform: scale(1.05);
        }
        .nav-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        .form-control:focus { 
            border-color: #7367f0; 
            box-shadow: 0 0 0 0.2rem rgba(115, 103, 240, 0.25); 
        }
        .input-group .form-control {
            border-right: 0;
        }
        .input-group .btn {
            border-left: 0;
        }
        .loading-overlay {
            position: absolute;
            top: 50px;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255,255,255,0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 50;
            display: none;
        }
        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid #7367f0;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .proxy-notice {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 0.25rem;
            padding: 0.75rem;
            margin-top: 1rem;
            font-size: 0.875rem;
        }
        .address-bar {
            flex-grow: 1;
            margin: 0 10px;
            display: flex;
            align-items: center;
        }
        .address-input {
            width: 100%;
            padding: 6px 12px;
            border-radius: 20px;
            border: 1px solid rgba(255,255,255,0.3);
            background: rgba(255,255,255,0.1);
            color: white;
            outline: none;
            font-size: 14px;
        }
        .address-input::placeholder {
            color: rgba(255,255,255,0.7);
        }
        .go-btn {
            background: rgba(255,255,255,0.3);
            border: 1px solid rgba(255,255,255,0.4);
            color: white;
            padding: 6px 15px;
            border-radius: 20px;
            margin-left: 5px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .go-btn:hover {
            background: rgba(255,255,255,0.4);
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
                        
                        <?php if (isset($_SESSION['success'])): ?>
                            <div class="alert alert-success alert-dismissible" role="alert">
                                <?= $_SESSION['success']; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                            <?php unset($_SESSION['success']); ?>
                        <?php endif; ?>

                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger alert-dismissible" role="alert">
                                <?= $_SESSION['error']; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                            <?php unset($_SESSION['error']); ?>
                        <?php endif; ?>

                        <!-- Header -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="mb-0">
                                <i class="ri-api-line me-2"></i>
                                API Panel Browser
                            </h4>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-primary" onclick="refreshIframe()">
                                    <i class="ri-refresh-line me-1"></i> Refresh
                                </button>
                            </div>
                        </div>

                        <!-- API Credentials Form -->
                        <div class="api-container">
                            <h5 class="text-primary mb-3">
                                <i class="ri-key-2-line me-2"></i>
                                API Panel Credentials
                            </h5>
                            <form method="POST" id="apiForm">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">API Username</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" name="api_username" 
                                                   id="api_username" value="<?= htmlspecialchars($settings['api_username']); ?>"
                                                   placeholder="Enter API username">
                                            <button type="button" class="btn btn-outline-secondary copy-btn" onclick="copyToClipboard('api_username')">
                                                <i class="ri-file-copy-line"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">API Password</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" name="api_password" 
                                                   id="api_password" value="<?= htmlspecialchars($settings['api_password']); ?>"
                                                   placeholder="Enter API password">
                                            <button type="button" class="btn btn-outline-secondary copy-btn" onclick="copyToClipboard('api_password')">
                                                <i class="ri-file-copy-line"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary" onclick="togglePassword()">
                                                <i class="ri-eye-line" id="eyeIcon"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="text-end mt-3">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="ri-save-line me-2"></i>Save Credentials
                                    </button>
                                    <button type="button" class="btn btn-success" onclick="autoLogin()">
                                        <i class="ri-login-circle-line me-2"></i>Auto Login
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- API Panel Browser -->
                        <div class="api-container">
                            <div class="frame-container">
                                <div class="frame-toolbar">
                                    <button type="button" class="nav-btn" onclick="goBack()" id="backBtn" title="Back" disabled>
                                        <i class="ri-arrow-left-s-line"></i>
                                    </button>
                                    <button type="button" class="nav-btn" onclick="goForward()" id="forwardBtn" title="Forward" disabled>
                                        <i class="ri-arrow-right-s-line"></i>
                                    </button>
                                    
                                    <div class="address-bar">
                                        <input type="text" class="address-input" id="addressBar" 
                                               value="https://apisrental.apioperations.xyz/panel/" 
                                               placeholder="Enter URL">
                                        <button class="go-btn" onclick="goToURL()">Go</button>
                                    </div>
                                    
                                    <div class="loading-overlay" id="loadingOverlay">
                                        <div class="spinner"></div>
                                    </div>
                                </div>
                                
                                <div class="frame-wrapper">
                                    <iframe src="https://apisrental.apioperations.xyz/panel/" 
                                            id="apiFrame" 
                                            title="API Panel Browser"
                                            allow="fullscreen"
                                            sandbox="allow-same-origin allow-scripts allow-forms allow-popups allow-modals allow-top-navigation-by-user-activation">
                                    </iframe>
                                </div>
                            </div>
                            
                            <div class="proxy-notice mt-3">
                                <i class="ri-information-line me-2"></i>
                                <strong>Browser Features:</strong> Full navigation support with back/forward buttons and address bar. 
                                Use "Auto Login" button to automatically login with saved credentials.
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
    <script src("assets/vendor/libs/moment/moment.js"></script>
    <script src="assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js"></script>
    <script src="assets/vendor/libs/select2/select2.js"></script>
    <script src="assets/vendor/libs/@form-validation/popular.js"></script>
    <script src="assets/vendor/libs/@form-validation/bootstrap5.js"></script>
    <script src="assets/vendor/libs/@form-validation/auto-focus.js"></script>
    <script src="assets/vendor/libs/cleavejs/cleave.js"></script>
    <script src="assets/vendor/libs/cleavejs/cleave-phone.js"></script>
    <script src="assets/js/main.js"></script>
<script>
    // Global variables
    let currentIframeURL = "https://apisrental.apioperations.xyz/panel/";
    
    // Copy to clipboard function
    function copyToClipboard(elementId) {
        const element = document.getElementById(elementId);
        let textToCopy = '';
        
        if (element.tagName === 'INPUT') {
            textToCopy = element.value;
        } else {
            textToCopy = element.textContent || element.innerText;
        }
        
        navigator.clipboard.writeText(textToCopy).then(function() {
            const btn = event.target.closest('.copy-btn');
            if (btn) {
                const originalHTML = btn.innerHTML;
                btn.innerHTML = '<i class="ri-check-line"></i>';
                btn.classList.add('copied');
                
                setTimeout(() => {
                    btn.innerHTML = originalHTML;
                    btn.classList.remove('copied');
                }, 2000);
            }
            
            showToast('Copied to clipboard!', 'success');
        }, function() {
            const textArea = document.createElement('textarea');
            textArea.value = textToCopy;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            
            showToast('Copied to clipboard!', 'success');
        });
    }
    
    // Toggle password visibility
    function togglePassword() {
        const passwordInput = document.getElementById('api_password');
        const eyeIcon = document.getElementById('eyeIcon');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            eyeIcon.className = 'ri-eye-off-line';
        } else {
            passwordInput.type = 'password';
            eyeIcon.className = 'ri-eye-line';
        }
    }
    
    // Refresh iframe
    function refreshIframe() {
        const iframe = document.getElementById('apiFrame');
        if (iframe) {
            iframe.src = iframe.src;
            showToast('Page refreshed!', 'info');
        }
    }
    
    // Go to URL from address bar
    function goToURL() {
        const urlInput = document.getElementById('addressBar');
        let url = urlInput.value.trim();
        
        if (!url) {
            showToast('Please enter a URL', 'warning');
            return;
        }
        
        // Add protocol if missing
        if (!url.startsWith('http://') && !url.startsWith('https://')) {
            url = 'https://' + url;
        }
        
        showLoading();
        loadURL(url);
    }
    
    // Load URL in iframe
    function loadURL(url) {
        const iframe = document.getElementById('apiFrame');
        if (iframe) {
            iframe.src = url;
            currentIframeURL = url;
            document.getElementById('addressBar').value = url;
        }
    }
    
    // Auto login function
    function autoLogin() {
        const username = document.getElementById('api_username').value.trim();
        const password = document.getElementById('api_password').value.trim();
        
        if (!username || !password) {
            showToast('Please enter API credentials first', 'warning');
            return;
        }
        
        showLoading();
        
        // Create a form inside the iframe
        const iframe = document.getElementById('apiFrame');
        if (iframe) {
            try {
                const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
                
                // Navigate to login page first
                iframe.src = 'https://apisrental.apioperations.xyz/panel/login.php';
                
                // Wait for iframe to load
                iframe.onload = function() {
                    try {
                        const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
                        
                        // Fill login form
                        const usernameField = iframeDoc.querySelector('input[name="username"]') || 
                                            iframeDoc.querySelector('input[type="text"]') ||
                                            iframeDoc.querySelector('input[id*="user"]') ||
                                            iframeDoc.querySelector('input[name*="user"]');
                        
                        const passwordField = iframeDoc.querySelector('input[name="password"]') || 
                                            iframeDoc.querySelector('input[type="password"]') ||
                                            iframeDoc.querySelector('input[id*="pass"]') ||
                                            iframeDoc.querySelector('input[name*="pass"]');
                        
                        const submitButton = iframeDoc.querySelector('button[type="submit"]') || 
                                           iframeDoc.querySelector('input[type="submit"]') ||
                                           iframeDoc.querySelector('button') ||
                                           iframeDoc.querySelector('input[value*="Login"]');
                        
                        if (usernameField && passwordField && submitButton) {
                            // Fill form
                            usernameField.value = username;
                            passwordField.value = password;
                            
                            // Trigger change events
                            usernameField.dispatchEvent(new Event('change', { bubbles: true }));
                            passwordField.dispatchEvent(new Event('change', { bubbles: true }));
                            
                            // Submit form
                            setTimeout(() => {
                                submitButton.click();
                                
                                // Wait for redirect and update address bar
                                setTimeout(() => {
                                    updateAddressBar();
                                    hideLoading();
                                    showToast('Auto login successful!', 'success');
                                }, 2000);
                            }, 500);
                        } else {
                            // Form not found, use manual login
                            manualLogin(username, password);
                        }
                    } catch (e) {
                        // Cross-origin error, use manual login
                        manualLogin(username, password);
                    }
                };
            } catch (e) {
                // Cross-origin error, use manual login
                manualLogin(username, password);
            }
        }
    }
    
    // Manual login using POST request
    function manualLogin(username, password) {
        // Create a hidden form to submit login
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'https://apisrental.apioperations.xyz/panel/login.php';
        form.target = 'apiFrame';
        form.style.display = 'none';
        
        const usernameInput = document.createElement('input');
        usernameInput.type = 'hidden';
        usernameInput.name = 'username';
        usernameInput.value = username;
        
        const passwordInput = document.createElement('input');
        passwordInput.type = 'hidden';
        passwordInput.name = 'password';
        passwordInput.value = password;
        
        form.appendChild(usernameInput);
        form.appendChild(passwordInput);
        document.body.appendChild(form);
        
        // Submit form
        form.submit();
        
        // Update iframe target
        const iframe = document.getElementById('apiFrame');
        iframe.name = 'apiFrame';
        
        // Wait and check if login was successful
        setTimeout(() => {
            updateAddressBar();
            hideLoading();
            showToast('Login submitted. Please wait for redirect...', 'info');
            
            // Clean up
            setTimeout(() => {
                document.body.removeChild(form);
            }, 3000);
        }, 1000);
    }
    
    // Iframe navigation
    function goBack() {
        const iframe = document.getElementById('apiFrame');
        if (iframe) {
            try {
                iframe.contentWindow.history.back();
                setTimeout(updateAddressBar, 100);
            } catch (e) {
                showToast('Cannot navigate back (cross-origin restriction)', 'warning');
            }
        }
    }
    
    function goForward() {
        const iframe = document.getElementById('apiFrame');
        if (iframe) {
            try {
                iframe.contentWindow.history.forward();
                setTimeout(updateAddressBar, 100);
            } catch (e) {
                showToast('Cannot navigate forward (cross-origin restriction)', 'warning');
            }
        }
    }
    
    // Update address bar with current iframe URL
    function updateAddressBar() {
        const iframe = document.getElementById('apiFrame');
        if (iframe) {
            try {
                const currentURL = iframe.contentWindow.location.href;
                document.getElementById('addressBar').value = currentURL;
                currentIframeURL = currentURL;
                
                // Update nav buttons state
                updateNavButtons();
            } catch (e) {
                // Cross-origin restriction, keep last known URL
            }
        }
    }
    
    // Update navigation buttons state
    function updateNavButtons() {
        const iframe = document.getElementById('apiFrame');
        if (!iframe) return;
        
        try {
            const iframeWindow = iframe.contentWindow;
            document.getElementById('backBtn').disabled = !iframeWindow.history.length || iframeWindow.history.length <= 1;
            
            // Note: forward state is hard to detect due to security restrictions
            document.getElementById('forwardBtn').disabled = false;
        } catch (e) {
            // Cross-origin restriction
            document.getElementById('backBtn').disabled = true;
            document.getElementById('forwardBtn').disabled = true;
        }
    }
    
    // Show loading spinner
    function showLoading() {
        document.getElementById('loadingOverlay').style.display = 'flex';
    }
    
    // Hide loading spinner
    function hideLoading() {
        document.getElementById('loadingOverlay').style.display = 'none';
    }
    
    // Show toast notification
    function showToast(message, type = 'info') {
        let toastContainer = document.getElementById('toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'toast-container';
            toastContainer.style.position = 'fixed';
            toastContainer.style.top = '20px';
            toastContainer.style.right = '20px';
            toastContainer.style.zIndex = '9999';
            document.body.appendChild(toastContainer);
        }
        
        const toastId = 'toast-' + Date.now();
        const toast = document.createElement('div');
        toast.id = toastId;
        toast.className = `alert alert-${type} alert-dismissible fade show`;
        toast.style.minWidth = '300px';
        toast.style.marginBottom = '10px';
        toast.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        toastContainer.appendChild(toast);
        
        setTimeout(() => {
            if (document.getElementById(toastId)) {
                toast.remove();
            }
        }, 5000);
    }
    
    // Form submission
    document.getElementById('apiForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const username = document.getElementById('api_username').value.trim();
        const password = document.getElementById('api_password').value.trim();
        
        if (!username || !password) {
            showToast('Please fill both username and password', 'warning');
            return false;
        }
        
        // Submit form via AJAX
        const form = this;
        const formData = new FormData(form);
        
        fetch(form.action, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            showToast('Credentials saved successfully!', 'success');
        })
        .catch(error => {
            showToast('Error saving credentials', 'error');
        });
        
        return false;
    });
    
    // Initialize
    document.addEventListener('DOMContentLoaded', function() {
        const usernameInput = document.getElementById('api_username');
        if (usernameInput && !usernameInput.value) {
            usernameInput.focus();
        }
        
        // Setup iframe events
        const iframe = document.getElementById('apiFrame');
        if (iframe) {
            // Update address bar when iframe loads
            iframe.onload = function() {
                hideLoading();
                updateAddressBar();
                updateNavButtons();
            };
            
            // Show loading when iframe starts loading
            iframe.onloadstart = function() {
                showLoading();
            };
            
            // Handle iframe errors
            iframe.onerror = function() {
                hideLoading();
                showToast('Error loading page', 'error');
            };
            
            // Initial update
            updateAddressBar();
            updateNavButtons();
        }
        
        // Address bar keyboard support
        document.getElementById('addressBar').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                goToURL();
            }
        });
        
        // Periodically update address bar (for redirects)
        setInterval(updateAddressBar, 1000);
    });
    
    // Global function to handle iframe navigation
    window.navigateIframe = function(url) {
        loadURL(url);
    };
</script>
</body>
</html>