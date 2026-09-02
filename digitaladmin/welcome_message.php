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



// HTML structure template
$baseTemplate = '
<div style="padding: 20px; font-family: Arial, sans-serif; font-size: 16px; background: {background_color}; border-radius: 10px; box-shadow: 0 0 15px rgba(0,0,0,0.1); max-width: 600px; margin: auto;">
  <h3 style="color: {heading_color}; text-align: center;">{popup_heading}</h3>
  <p style="text-align: center; font-weight: bold; color: {announcement_color};">{announcement}</p>
  <p style="text-align: center; color: {text_color};">{body_text}</p>

  {image_block}

  <p style="text-align: center; font-weight: bold; color: {reward_color};">{reward_text}</p>
  <div style="text-align: center; margin-top: 15px;">
    {button_block}
  </div>
</div>
';

$success = false;
$error = '';

// Fetch all welcome messages
$allMessages = $conn->query("SELECT * FROM welcome_messages ORDER BY created_at DESC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save_message') {
        $id           = intval($_POST['id'] ?? 0);
        $title        = trim($_POST['title'] ?? '');
        $popup_heading = trim($_POST['popup_heading'] ?? '');
        $announcement = trim($_POST['announcement'] ?? '');
        $body_text    = trim($_POST['body_text'] ?? '');
        $reward_text  = trim($_POST['reward_text'] ?? '');
        $vip_link     = trim($_POST['vip_link'] ?? '');
        $button_text  = trim($_POST['button_text'] ?? '');
        $image_url    = trim($_POST['image_url'] ?? '');
        $is_active    = isset($_POST['is_active']) ? 1 : 0;
        $button_enabled = isset($_POST['button_enabled']) ? 1 : 0;
        $heading_color = trim($_POST['heading_color'] ?? '#0d6efd');
        $text_color    = trim($_POST['text_color'] ?? '#ffffff');
        $announcement_color = trim($_POST['announcement_color'] ?? '#ffffff');
        $reward_color  = trim($_POST['reward_color'] ?? '#ffffff');
        $background_color = trim($_POST['background_color'] ?? '#000e22');

        // Validate required fields
        if (empty($title) || empty($popup_heading) || empty($announcement) || empty($reward_text)) {
            $error = "Please fill all required fields";
        } else {
            // image block (optional)
            $image_block = '';
            if (!empty($image_url)) {
                $safe_img = htmlspecialchars($image_url, ENT_QUOTES, 'UTF-8');
                $image_block = '<div style="text-align:center; margin: 12px 0;">
                    <img src="'.$safe_img.'" alt="promo" style="max-width:100%; max-height:100%; border-radius:8px;">
                </div>';
            }

            // button block (only if enabled)
            $button_block = '';
            if ($button_enabled && !empty($button_text) && !empty($vip_link)) {
                $button_block = '<a href="'.$vip_link.'" style="display: inline-block; padding: 10px 25px; background: #0d6efd; color:#ffffff; border-radius: 6px; text-decoration: none;">'.$button_text.'</a>';
            }

            $finalHTML = str_replace(
                [
                    '{popup_heading}', 
                    '{announcement}', 
                    '{body_text}', 
                    '{image_block}', 
                    '{reward_text}', 
                    '{button_block}',
                    '{heading_color}',
                    '{text_color}',
                    '{announcement_color}',
                    '{reward_color}',
                    '{background_color}'
                ],
                [
                    $popup_heading, 
                    $announcement, 
                    $body_text, 
                    $image_block, 
                    $reward_text, 
                    $button_block,
                    $heading_color,
                    $text_color,
                    $announcement_color,
                    $reward_color,
                    $background_color
                ],
                $baseTemplate
            );

            if ($id > 0) {
                // Update existing message
                $sql = "UPDATE welcome_messages SET title = ?, message_html = ?, is_active = ?, button_enabled = ?, heading_color = ?, text_color = ?, announcement_color = ?, reward_color = ?, background_color = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                
                if ($stmt) {
                    $stmt->bind_param("ssiisssssi", $title, $finalHTML, $is_active, $button_enabled, $heading_color, $text_color, $announcement_color, $reward_color, $background_color, $id);
                    if ($stmt->execute()) {
                        $success = true;
                    } else {
                        $error = "Execute failed: " . $stmt->error;
                    }
                    $stmt->close();
                } else {
                    $error = "Prepare failed: " . $conn->error;
                }
            } else {
                // Create new message
                $sql = "INSERT INTO welcome_messages (title, message_html, is_active, button_enabled, heading_color, text_color, announcement_color, reward_color, background_color) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                
                if ($stmt) {
                    $stmt->bind_param("ssiisssss", $title, $finalHTML, $is_active, $button_enabled, $heading_color, $text_color, $announcement_color, $reward_color, $background_color);
                    
                    if ($stmt->execute()) {
                        $success = true;
                    } else {
                        $error = "Execute failed: " . $stmt->error;
                    }
                    $stmt->close();
                } else {
                    $error = "Prepare failed: " . $conn->error;
                }
            }
        }
        
    } elseif ($action === 'delete_message') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $conn->prepare("DELETE FROM welcome_messages WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $id);
                if ($stmt->execute()) {
                    $success = true;
                    // Return JSON response for delete
                    header('Content-Type: application/json');
                    echo json_encode(["success" => true]);
                    exit();
                } else {
                    $error = "Delete failed: " . $stmt->error;
                }
                $stmt->close();
            }
        }
    } elseif ($action === 'toggle_active') {
        $id = intval($_POST['id'] ?? 0);
        $is_active = intval($_POST['is_active'] ?? 0);
        
        $stmt = $conn->prepare("UPDATE welcome_messages SET is_active = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("ii", $is_active, $id);
            if ($stmt->execute()) {
                header('Content-Type: application/json');
                echo json_encode(["success" => true]);
                exit();
            }
            $stmt->close();
        }
    }
}

// Function to extract values from HTML for editing - FIXED VERSION
function extractWelcomeMessageValues($html) {
    $values = [
        'popup_heading' => '',
        'announcement' => '',
        'body_text' => '',
        'reward_text' => '',
        'vip_link' => '',
        'button_text' => '',
        'image_url' => '',
        'heading_color' => '#0d6efd',
        'text_color' => '#ffffff',
        'announcement_color' => '#ffffff',
        'reward_color' => '#ffffff',
        'background_color' => '#000e22'
    ];
    
    if (empty($html)) return $values;
    
    libxml_use_internal_errors(true);
    $doc = new DOMDocument();
    
    // Fixed encoding without mb_convert_encoding
    $html = '<?xml encoding="UTF-8">' . $html;
    @$doc->loadHTML($html);
    
    // Extract main container for background color
    $divs = $doc->getElementsByTagName('div');
    foreach ($divs as $div) {
        $style = $div->getAttribute('style');
        if (strpos($style, 'background:') !== false && strpos($style, 'border-radius: 10px') !== false) {
            if (preg_match('/background:\s*([^;]+)/i', $style, $match)) {
                $values['background_color'] = trim($match[1]);
            }
        }
    }
    
    // Extract heading
    $h3Tags = $doc->getElementsByTagName('h3');
    if ($h3Tags->length > 0) {
        $values['popup_heading'] = $h3Tags->item(0)->textContent;
        
        // Extract heading color
        $style = $h3Tags->item(0)->getAttribute('style');
        if (preg_match('/color:\s*([^;]+)/i', $style, $match)) {
            $values['heading_color'] = trim($match[1]);
        }
    }
    
    // Extract paragraphs
    $pTags = $doc->getElementsByTagName('p');
    if ($pTags->length > 0) {
        $values['announcement'] = $pTags->item(0)->textContent;
        
        // Extract announcement color
        $style = $pTags->item(0)->getAttribute('style');
        if (preg_match('/color:\s*([^;]+)/i', $style, $match)) {
            $values['announcement_color'] = trim($match[1]);
        }
    }
    
    if ($pTags->length > 1) {
        $values['body_text'] = $pTags->item(1)->textContent;
        
        // Extract body text color
        $style = $pTags->item(1)->getAttribute('style');
        if (preg_match('/color:\s*([^;]+)/i', $style, $match)) {
            $values['text_color'] = trim($match[1]);
        }
    }
    
    if ($pTags->length > 2) {
        $values['reward_text'] = $pTags->item(2)->textContent;
        
        // Extract reward text color
        $style = $pTags->item(2)->getAttribute('style');
        if (preg_match('/color:\s*([^;]+)/i', $style, $match)) {
            $values['reward_color'] = trim($match[1]);
        }
    }
    
    // Extract image
    $imgTags = $doc->getElementsByTagName('img');
    if ($imgTags->length > 0) {
        $values['image_url'] = $imgTags->item(0)->getAttribute('src');
    }
    
    // Extract button
    $aTags = $doc->getElementsByTagName('a');
    if ($aTags->length > 0) {
        $values['vip_link'] = $aTags->item(0)->getAttribute('href');
        $values['button_text'] = $aTags->item(0)->textContent;
    }
    
    return $values;
}

// Get message for editing (if ID provided)
$editId = $_GET['edit'] ?? 0;
$editingMessage = null;
$formValues = [
    'id' => 0,
    'title' => '',
    'popup_heading' => '',
    'announcement' => '',
    'body_text' => '',
    'reward_text' => '',
    'vip_link' => '',
    'button_text' => '',
    'image_url' => '',
    'is_active' => 0,
    'button_enabled' => 0,
    'heading_color' => '#0d6efd',
    'text_color' => '#ffffff',
    'announcement_color' => '#ffffff',
    'reward_color' => '#ffffff',
    'background_color' => '#000e22'
];

if ($editId > 0) {
    $stmt = $conn->prepare("SELECT * FROM welcome_messages WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $editId);
        $stmt->execute();
        $result = $stmt->get_result();
        $editingMessage = $result->fetch_assoc();
        $stmt->close();
        
        if ($editingMessage) {
            $formValues['id'] = $editingMessage['id'];
            $formValues['title'] = $editingMessage['title'];
            $formValues['is_active'] = $editingMessage['is_active'];
            $formValues['button_enabled'] = $editingMessage['button_enabled'];
            $formValues['heading_color'] = $editingMessage['heading_color'];
            $formValues['text_color'] = $editingMessage['text_color'];
            $formValues['announcement_color'] = $editingMessage['announcement_color'];
            $formValues['reward_color'] = $editingMessage['reward_color'];
            $formValues['background_color'] = $editingMessage['background_color'];
            
            $extractedValues = extractWelcomeMessageValues($editingMessage['message_html']);
            $formValues = array_merge($formValues, $extractedValues);
        }
    }
}

// Store all messages in array for display
$messagesArray = [];
if ($allMessages->num_rows > 0) {
    $allMessages->data_seek(0); // Reset pointer
    while ($message = $allMessages->fetch_assoc()) {
        $messagesArray[] = $message;
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr"
    data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template" data-style="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Welcome Message Management</title>
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
        .message-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            /*background: #fff;*/
            transition: all 0.3s ease;
        }
        .message-card.inactive {
            opacity: 0.7;
            background: #f8f9fa;
        }
        .message-card:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .preview-container {
            border: 1px dashed #ccc;
            border-radius: 8px;
            padding: 15px;
            /*background: #f9f9f9;*/
            min-height: 200px;
        }
        .color-preview {
            width: 30px;
            height: 30px;
            border-radius: 4px;
            display: inline-block;
            margin-right: 10px;
            border: 1px solid #ddd;
        }
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 24px;
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        input:checked + .slider {
            background-color: #2196F3;
        }
        input:checked + .slider:before {
            transform: translateX(26px);
        }
        .background-preview {
            width: 100%;
            height: 60px;
            border-radius: 6px;
            margin-top: 5px;
            border: 1px solid #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
        }
        .alert {
            margin-bottom: 20px;
        }
        .edit-form-container {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            border: 2px solid #0d6efd;
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
                        <!-- Display Messages -->
                        <div id="messageContainer">
                            <?php if ($success): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    ✅ Welcome message saved successfully!
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($error)): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    ❌ Error: <?= htmlspecialchars($error) ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="row">
                            <!-- Welcome Message Form -->
                            <div class="col-md-6">
                                <?php if ($editId > 0): ?>
                                <div class="edit-form-container">
                                    <h4 class="text-primary mb-3">✏️ Editing: <?= htmlspecialchars($formValues['title']) ?></h4>
                                <?php endif; ?>
                                
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <?= $editId > 0 ? 'Edit Welcome Message' : 'Create New Welcome Message' ?>
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <form id="welcomeMessageForm" method="POST">
                                            <input type="hidden" name="action" value="save_message">
                                            <input type="hidden" name="id" value="<?= $formValues['id'] ?>">
                                            
                                            <div class="mb-3">
                                                <label for="title" class="form-label">Message Title *</label>
                                                <input type="text" class="form-control" id="title" name="title" 
                                                       value="<?= htmlspecialchars($formValues['title']) ?>" required 
                                                       placeholder="e.g., Welcome Bonus, Daily Reward">
                                            </div>

                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" id="is_active" 
                                                               name="is_active" value="1" <?= $formValues['is_active'] ? 'checked' : '' ?>>
                                                        <label class="form-check-label" for="is_active">
                                                            Active Message
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" id="button_enabled" 
                                                               name="button_enabled" value="1" <?= $formValues['button_enabled'] ? 'checked' : '' ?>>
                                                        <label class="form-check-label" for="button_enabled">
                                                            Enable Button
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label for="popup_heading" class="form-label">Popup Heading *</label>
                                                <input type="text" class="form-control" id="popup_heading" name="popup_heading" 
                                                       value="<?= htmlspecialchars($formValues['popup_heading']) ?>" required 
                                                       placeholder="Welcome heading text">
                                            </div>

                                            <div class="mb-3">
                                                <label for="announcement" class="form-label">Announcement Text *</label>
                                                <textarea class="form-control" id="announcement" name="announcement" 
                                                          rows="2" required placeholder="Main announcement text"><?= htmlspecialchars($formValues['announcement']) ?></textarea>
                                            </div>

                                            <div class="mb-3">
                                                <label for="body_text" class="form-label">Body Text</label>
                                                <textarea class="form-control" id="body_text" name="body_text" 
                                                          rows="3" placeholder="Additional description"><?= htmlspecialchars($formValues['body_text']) ?></textarea>
                                            </div>

                                            <div class="mb-3">
                                                <label for="image_url" class="form-label">Image URL</label>
                                                <input type="url" class="form-control" id="image_url" name="image_url" 
                                                       value="<?= htmlspecialchars($formValues['image_url']) ?>" 
                                                       placeholder="https://example.com/image.jpg">
                                            </div>

                                            <div class="mb-3">
                                                <label for="reward_text" class="form-label">Reward Text *</label>
                                                <textarea class="form-control" id="reward_text" name="reward_text" 
                                                          rows="2" required placeholder="Reward or offer details"><?= htmlspecialchars($formValues['reward_text']) ?></textarea>
                                            </div>

                                            <!-- Button Settings (only show if button enabled) -->
                                            <div id="buttonSettings" style="display: <?= $formValues['button_enabled'] ? 'block' : 'none' ?>;">
                                                <div class="mb-3">
                                                    <label for="vip_link" class="form-label">Button Link</label>
                                                    <input type="url" class="form-control" id="vip_link" name="vip_link" 
                                                           value="<?= htmlspecialchars($formValues['vip_link']) ?>" 
                                                           placeholder="https://example.com/offer">
                                                </div>

                                                <div class="mb-3">
                                                    <label for="button_text" class="form-label">Button Text</label>
                                                    <input type="text" class="form-control" id="button_text" name="button_text" 
                                                           value="<?= htmlspecialchars($formValues['button_text']) ?>" 
                                                           placeholder="Claim Now">
                                                </div>
                                            </div>

                                            <!-- Color Settings -->
                                            <div class="card mt-4">
                                                <div class="card-header">
                                                    <h6 class="card-title mb-0">Color Settings</h6>
                                                </div>
                                                <div class="card-body">
                                                    <!-- Background Color -->
                                                    <div class="row mb-3">
                                                        <div class="col-12">
                                                            <label for="background_color" class="form-label">Background Color</label>
                                                            <div class="input-group">
                                                                <input type="color" class="form-control form-control-color" id="background_color" 
                                                                       name="background_color" value="<?= $formValues['background_color'] ?>" title="Choose background color">
                                                                <span class="input-group-text"><?= $formValues['background_color'] ?></span>
                                                            </div>
                                                            <div class="background-preview" id="backgroundPreview" style="background-color: <?= $formValues['background_color'] ?>;">
                                                                Background Preview
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="row">
                                                        <div class="col-md-6 mb-3">
                                                            <label for="heading_color" class="form-label">Heading Color</label>
                                                            <div class="input-group">
                                                                <input type="color" class="form-control form-control-color" id="heading_color" 
                                                                       name="heading_color" value="<?= $formValues['heading_color'] ?>" title="Choose heading color">
                                                                <span class="input-group-text"><?= $formValues['heading_color'] ?></span>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6 mb-3">
                                                            <label for="announcement_color" class="form-label">Announcement Color</label>
                                                            <div class="input-group">
                                                                <input type="color" class="form-control form-control-color" id="announcement_color" 
                                                                       name="announcement_color" value="<?= $formValues['announcement_color'] ?>" title="Choose announcement color">
                                                                <span class="input-group-text"><?= $formValues['announcement_color'] ?></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-6 mb-3">
                                                            <label for="text_color" class="form-label">Body Text Color</label>
                                                            <div class="input-group">
                                                                <input type="color" class="form-control form-control-color" id="text_color" 
                                                                       name="text_color" value="<?= $formValues['text_color'] ?>" title="Choose text color">
                                                                <span class="input-group-text"><?= $formValues['text_color'] ?></span>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6 mb-3">
                                                            <label for="reward_color" class="form-label">Reward Text Color</label>
                                                            <div class="input-group">
                                                                <input type="color" class="form-control form-control-color" id="reward_color" 
                                                                       name="reward_color" value="<?= $formValues['reward_color'] ?>" title="Choose reward color">
                                                                <span class="input-group-text"><?= $formValues['reward_color'] ?></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="mt-4">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="ri-save-line ri-16px me-2"></i>
                                                    <?= $editId > 0 ? 'Update Message' : 'Create Message' ?>
                                                </button>
                                                <?php if ($editId > 0): ?>
                                                    <a href="welcome_message.php" class="btn btn-secondary">
                                                        <i class="ri-close-line ri-16px me-2"></i>Cancel Edit
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                
                                <?php if ($editId > 0): ?>
                                </div> <!-- Close edit form container -->
                                <?php endif; ?>
                            </div>

                            <!-- Preview & Existing Messages -->
                            <div class="col-md-6">
                                <!-- Preview -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Live Preview</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="preview-container" id="messagePreview">
                                            <!-- Preview will be updated by JavaScript -->
                                        </div>
                                    </div>
                                </div>

                                <!-- Existing Messages -->
                                <div class="card">
                                    <div class="card-header">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h5 class="card-title mb-0">Existing Messages</h5>
                                            <span class="badge bg-primary">
                                                <?= count($messagesArray) ?> Total
                                            </span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <?php if (count($messagesArray) > 0): ?>
                                            <div id="messagesList">
                                                <?php foreach ($messagesArray as $message): ?>
                                                    <div class="message-card <?= !$message['is_active'] ? 'inactive' : '' ?>" 
                                                         data-id="<?= $message['id'] ?>">
                                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                                            <div>
                                                                <h6 class="mb-1"><?= htmlspecialchars($message['title']) ?></h6>
                                                                <span class="badge bg-<?= $message['is_active'] ? 'success' : 'secondary' ?>">
                                                                    <?= $message['is_active'] ? 'Active' : 'Inactive' ?>
                                                                </span>
                                                                <?php if ($message['button_enabled']): ?>
                                                                    <span class="badge bg-info ms-1">Button Enabled</span>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="dropdown">
                                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                                                        type="button" data-bs-toggle="dropdown">
                                                                    <i class="ri-more-2-line"></i>
                                                                </button>
                                                                <ul class="dropdown-menu">
                                                                    <li>
                                                                        <a class="dropdown-item" 
                                                                           href="?edit=<?= $message['id'] ?>">
                                                                            <i class="ri-edit-line me-2"></i>Edit
                                                                        </a>
                                                                    </li>
                                                                    <li>
                                                                        <a class="dropdown-item toggle-active" href="#" 
                                                                           data-id="<?= $message['id'] ?>" 
                                                                           data-active="<?= $message['is_active'] ?>">
                                                                            <i class="ri-toggle-line me-2"></i>
                                                                            <?= $message['is_active'] ? 'Deactivate' : 'Activate' ?>
                                                                        </a>
                                                                    </li>
                                                                    <li>
                                                                        <a class="dropdown-item text-danger delete-message" 
                                                                           href="#" data-id="<?= $message['id'] ?>">
                                                                            <i class="ri-delete-bin-line me-2"></i>Delete
                                                                        </a>
                                                                    </li>
                                                                </ul>
                                                            </div>
                                                        </div>
                                                        <div class="small text-muted">
                                                            Created: <?= date('M j, Y g:i A', strtotime($message['created_at'])) ?>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="text-center py-4">
                                                <div class="avatar avatar-lg mb-3">
                                                    <span class="avatar-initial rounded bg-label-secondary">
                                                        <i class="ri-message-2-line ri-24px"></i>
                                                    </span>
                                                </div>
                                                <h5 class="text-muted">No Welcome Messages</h5>
                                                <p class="text-muted">Create your first welcome message using the form.</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
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
        // Show message
        function showMessage(message, type = 'success') {
            const messageContainer = document.getElementById('messageContainer');
            const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
            
            messageContainer.innerHTML = `
                <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
            
            setTimeout(() => {
                const bsAlert = new bootstrap.Alert(messageContainer.querySelector('.alert'));
                bsAlert.close();
            }, 5000);
        }

        // Update preview
        function updatePreview() {
            const popup_heading = document.getElementById('popup_heading').value || 'Welcome Heading';
            const announcement = document.getElementById('announcement').value || 'Announcement text';
            const body_text = document.getElementById('body_text').value || 'Body text description';
            const reward_text = document.getElementById('reward_text').value || 'Reward details';
            const image_url = document.getElementById('image_url').value;
            const vip_link = document.getElementById('vip_link').value || '#';
            const button_text = document.getElementById('button_text').value || 'Claim Now';
            const button_enabled = document.getElementById('button_enabled').checked;
            
            const heading_color = document.getElementById('heading_color').value;
            const text_color = document.getElementById('text_color').value;
            const announcement_color = document.getElementById('announcement_color').value;
            const reward_color = document.getElementById('reward_color').value;
            const background_color = document.getElementById('background_color').value;

            // Update background preview
            document.getElementById('backgroundPreview').style.backgroundColor = background_color;

            // image block
            let image_block = '';
            if (image_url) {
                image_block = `<div style="text-align:center; margin: 12px 0;">
                    <img src="${image_url}" alt="promo" style="max-width:100%; max-height:100%; border-radius:8px;">
                </div>`;
            }

            // button block
            let button_block = '';
            if (button_enabled && button_text) {
                button_block = `<a href="${vip_link}" style="display: inline-block; padding: 10px 25px; background: #0d6efd; color:#ffffff; border-radius: 6px; text-decoration: none;">${button_text}</a>`;
            }

            const previewHTML = `
                <div style="padding: 20px; font-family: Arial, sans-serif; font-size: 16px; background: ${background_color}; border-radius: 10px; box-shadow: 0 0 15px rgba(0,0,0,0.1); max-width: 600px; margin: auto;">
                    <h3 style="color: ${heading_color}; text-align: center;">${popup_heading}</h3>
                    <p style="text-align: center; font-weight: bold; color: ${announcement_color};">${announcement}</p>
                    <p style="text-align: center; color: ${text_color};">${body_text}</p>
                    ${image_block}
                    <p style="text-align: center; font-weight: bold; color: ${reward_color};">${reward_text}</p>
                    <div style="text-align: center; margin-top: 15px;">
                        ${button_block}
                    </div>
                </div>
            `;

            document.getElementById('messagePreview').innerHTML = previewHTML;
        }

        // Toggle button settings visibility
        document.getElementById('button_enabled').addEventListener('change', function() {
            const buttonSettings = document.getElementById('buttonSettings');
            buttonSettings.style.display = this.checked ? 'block' : 'none';
            updatePreview();
        });

        // Add event listeners for form inputs to update preview
        const previewInputs = [
            'popup_heading', 'announcement', 'body_text', 'reward_text', 
            'image_url', 'vip_link', 'button_text',
            'heading_color', 'text_color', 'announcement_color', 'reward_color', 'background_color'
        ];
        
        previewInputs.forEach(inputId => {
            document.getElementById(inputId).addEventListener('input', updatePreview);
        });

        // Toggle active status
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('toggle-active')) {
                e.preventDefault();
                const id = e.target.getAttribute('data-id');
                const currentActive = parseInt(e.target.getAttribute('data-active'));
                const newActive = currentActive ? 0 : 1;
                
                const formData = new FormData();
                formData.append('action', 'toggle_active');
                formData.append('id', id);
                formData.append('is_active', newActive);
                
                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showMessage('✅ Message status updated successfully!');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showMessage('❌ Error updating message status', 'error');
                    }
                })
                .catch(error => {
                    showMessage('❌ Network error: ' + error, 'error');
                });
            }
        });

        // Delete message - FIXED VERSION
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('delete-message')) {
                e.preventDefault();
                const id = e.target.getAttribute('data-id');
                
                if (confirm('Are you sure you want to delete this welcome message?')) {
                    const formData = new FormData();
                    formData.append('action', 'delete_message');
                    formData.append('id', id);
                    
                    fetch('', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showMessage('✅ Message deleted successfully!');
                            // Remove the message card from UI immediately
                            const messageCard = document.querySelector(`.message-card[data-id="${id}"]`);
                            if (messageCard) {
                                messageCard.remove();
                            }
                            // Update counter
                            const badge = document.querySelector('.badge.bg-primary');
                            if (badge) {
                                const currentCount = parseInt(badge.textContent);
                                badge.textContent = (currentCount - 1) + ' Total';
                            }
                        } else {
                            showMessage('❌ Error deleting message', 'error');
                        }
                    })
                    .catch(error => {
                        showMessage('❌ Network error: ' + error, 'error');
                    });
                }
            }
        });

        // Initialize preview on page load
        document.addEventListener('DOMContentLoaded', function() {
            updatePreview();
        });
    </script>
</body>
</html>