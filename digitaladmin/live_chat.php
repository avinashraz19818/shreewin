<?php
ini_set('display_errors', 0);                     
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1);                         
ini_set('error_log', __DIR__ . '/new_error.log'); 
error_reporting(E_ALL);   

session_start();
if (empty($_SESSION['unohs'])) {
    header("location: api/login.php?msg=unauthorized");
}
include("api/conn.php");

// Date filter handling - NO DEFAULT DATE FILTERING
$date = isset($_GET['date']) ? $_GET['date'] : '';
$dateFilter = isset($_GET['date']) && !empty($_GET['date']) ? date('Y-m-d', strtotime($_GET['date'])) : '';

// Search filter
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

// Handle AJAX requests for conversation messages
if (isset($_GET['ajax']) && $_GET['ajax'] == 'get_messages') {
    $conversation_id = mysqli_real_escape_string($conn, $_GET['conversation_id']);
    
    $query = "SELECT * FROM live_chat 
              WHERE conversation_id = '$conversation_id' 
              ORDER BY timestamp ASC";
    
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) > 0) {
        while ($message = mysqli_fetch_assoc($result)) {
            $time = date('d-m-Y H:i', strtotime($message['timestamp']));
            $messageClass = $message['sender'] == 'user' ? 'user-message-box' : 'admin-message-box';
            $senderName = $message['sender'] == 'user' ? 'User' : 'Admin';
            ?>
            <div class="message-container">
                <div class="<?= $messageClass; ?>">
                    <div class="message-sender"><?= $senderName; ?></div>
                    <div class="message-text"><?= htmlspecialchars($message['message']); ?></div>
                    <div class="message-time"><?= $time; ?></div>
                    <div class="message-actions">
                        <button class="btn btn-xs btn-outline-danger" onclick="deleteMessage(<?= $message['id']; ?>)">
                            <i class="ri-delete-bin-line"></i>
                        </button>
                    </div>
                </div>
                <div style="clear: both;"></div>
            </div>
            <?php
        }
    } else {
        echo '<div class="text-center text-muted p-4">No messages found</div>';
    }
    exit();
}

// Get statistics
$statsQuery = "SELECT 
    COUNT(DISTINCT conversation_id) as total_conversations,
    COUNT(*) as total_messages,
    SUM(CASE WHEN sender = 'user' THEN 1 ELSE 0 END) as user_messages,
    SUM(CASE WHEN sender = 'admin' THEN 1 ELSE 0 END) as admin_messages
    FROM `live_chat`";
    
// Add date filter for statistics if date is selected
$statsDateFilter = '';
if (!empty($dateFilter)) {
    $statsDateFilter = " WHERE DATE(timestamp) = '$dateFilter'";
}
$statsQuery .= $statsDateFilter;

$statsResult = mysqli_query($conn, $statsQuery);
$stats = mysqli_fetch_assoc($statsResult);

// Handle new message submission
if (isset($_POST['send_message'])) {
    $conversation_id = $_POST['conversation_id'];
    $user_id = $_POST['user_id'];
    $mobile = $_POST['mobile'];
    $admin_message = mysqli_real_escape_string($conn, $_POST['admin_message']);
    
    if (!empty($admin_message)) {
        $insertQuery = mysqli_query($conn, "INSERT INTO live_chat (conversation_id, user_id, mobile, message, sender, timestamp) 
                                            VALUES ('$conversation_id', '$user_id', '$mobile', '$admin_message', 'admin', NOW())");
        if ($insertQuery) {
            echo "<script>alert('Message sent successfully!'); window.location.href = window.location.href;</script>";
        } else {
            echo "<script>alert('Error sending message!');</script>";
        }
    }
}

// Handle delete single message
if (isset($_GET['delete_message'])) {
    $message_id = $_GET['delete_message'];
    $deleteQuery = mysqli_query($conn, "DELETE FROM live_chat WHERE id = '$message_id'");
    if ($deleteQuery) {
        echo "<script>alert('Message deleted successfully!'); window.location.href = 'live_chat.php';</script>";
    } else {
        echo "<script>alert('Error deleting message!');</script>";
    }
}

// Handle delete entire conversation
if (isset($_GET['delete_conversation'])) {
    $conversation_id = $_GET['delete_conversation'];
    $deleteQuery = mysqli_query($conn, "DELETE FROM live_chat WHERE conversation_id = '$conversation_id'");
    if ($deleteQuery) {
        echo "<script>alert('Conversation deleted successfully!'); window.location.href = 'live_chat.php';</script>";
    } else {
        echo "<script>alert('Error deleting conversation!');</script>";
    }
}

// Get all unique conversations
$conversationsQuery = "SELECT 
    conversation_id,
    user_id,
    mobile,
    COUNT(*) as message_count,
    MAX(timestamp) as last_message_time,
    MIN(timestamp) as first_message_time,
    SUM(CASE WHEN sender = 'user' THEN 1 ELSE 0 END) as user_messages,
    SUM(CASE WHEN sender = 'admin' THEN 1 ELSE 0 END) as admin_messages
    FROM `live_chat` 
    WHERE 1=1";
    
if (!empty($dateFilter)) {
    $conversationsQuery .= " AND DATE(timestamp) = '$dateFilter'";
}

if (!empty($search)) {
    $conversationsQuery .= " AND (mobile LIKE '%$search%' OR message LIKE '%$search%' OR user_id LIKE '%$search%')";
}

$conversationsQuery .= " GROUP BY conversation_id, user_id, mobile ORDER BY last_message_time DESC";
$conversationsResult = mysqli_query($conn, $conversationsQuery);
$totalConversations = mysqli_num_rows($conversationsResult);
?>

<!doctype html>
<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr"
    data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template" data-style="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Live Chat Management</title>
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
        .action-buttons {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }
        .chat-modal .modal-body {
            padding: 1rem;
            max-height: 500px;
            overflow-y: auto;
        }
        .chat-section {
            margin-bottom: 1rem;
        }
        .message-container {
            margin-bottom: 15px;
            clear: both;
        }
        .user-message-box {
            background-color: #e3f2fd;
            padding: 12px 15px;
            border-radius: 18px 18px 18px 4px;
            max-width: 75%;
            float: left;
            margin-right: 25%;
            border: 1px solid #bbdefb;
            position: relative;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        .admin-message-box {
            background-color: #f1f8e9;
            padding: 12px 15px;
            border-radius: 18px 18px 4px 18px;
            max-width: 75%;
            float: right;
            margin-left: 25%;
            border: 1px solid #dcedc8;
            position: relative;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        .message-text {
            color: #333;
            margin-bottom: 5px;
            word-wrap: break-word;
            white-space: pre-wrap;
            line-height: 1.4;
        }
        .message-time {
            font-size: 0.75em;
            color: #666;
            text-align: right;
            margin-top: 5px;
        }
        .message-sender {
            font-size: 0.8em;
            font-weight: bold;
            margin-bottom: 3px;
        }
        .user-message-box .message-sender {
            color: #1976d2;
        }
        .admin-message-box .message-sender {
            color: #388e3c;
        }
        .message-actions {
            position: absolute;
            top: 5px;
            right: 5px;
            opacity: 0;
            transition: opacity 0.3s;
        }
        .user-message-box:hover .message-actions,
        .admin-message-box:hover .message-actions {
            opacity: 1;
        }
        .message-actions .btn-xs {
            padding: 0.1rem 0.3rem;
            font-size: 0.7rem;
        }
        .chat-input-area {
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
            border: 1px solid #dee2e6;
        }
        .status-badge {
            font-size: 0.85em;
            padding: 0.35em 0.65em;
        }
        .stats-card {
            transition: transform 0.3s;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #007bff;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
        }
        .badge-active {
            background-color: #28a745 !important;
        }
        .badge-inactive {
            background-color: #6c757d !important;
        }
        .conversation-card {
            cursor: pointer;
            transition: all 0.3s;
            border-left: 4px solid transparent;
            padding: 12px 15px;
            border-bottom: 1px solid #dee2e6;
        }
        .conversation-card:hover {
            background-color: #f8f9fa;
            border-left-color: #007bff;
        }
        .conversation-card.active {
            border-left-color: #28a745;
            background-color: #e8f5e9;
        }
        .conversation-message-preview {
            font-size: 0.85em;
            color: #666;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            line-height: 1.3;
            margin-top: 5px;
        }
        .chat-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            border-radius: 10px 10px 0 0;
            margin-bottom: 15px;
        }
        .empty-chat {
            text-align: center;
            padding: 50px 20px;
            color: #6c757d;
        }
        .empty-chat i {
            font-size: 4em;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        #messagesContainer {
            max-height: 400px;
            overflow-y: auto;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 10px;
            border: 1px solid #eee;
        }
        .conversation-list {
            max-height: 500px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            border-radius: 8px;
        }
        .text-truncate-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
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
                        <!-- Statistics Cards -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card stats-card shadow">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h5 class="card-title text-primary mb-2">Conversations</h5>
                                                <h2 class="mb-0"><?= $stats['total_conversations'] ?? 0; ?></h2>
                                            </div>
                                            <div class="avatar">
                                                <div class="avatar-initial bg-primary rounded">
                                                    <i class="ri-chat-3-line fs-4"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <p class="text-muted mb-0 mt-2">Active conversations</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card stats-card shadow">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h5 class="card-title text-info mb-2">Total Messages</h5>
                                                <h2 class="mb-0"><?= $stats['total_messages'] ?? 0; ?></h2>
                                            </div>
                                            <div class="avatar">
                                                <div class="avatar-initial bg-info rounded">
                                                    <i class="ri-message-3-line fs-4"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <p class="text-muted mb-0 mt-2">All chat messages</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card stats-card shadow">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h5 class="card-title text-success mb-2">User Messages</h5>
                                                <h2 class="mb-0"><?= $stats['user_messages'] ?? 0; ?></h2>
                                            </div>
                                            <div class="avatar">
                                                <div class="avatar-initial bg-success rounded">
                                                    <i class="ri-user-line fs-4"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <p class="text-muted mb-0 mt-2">Messages from users</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card stats-card shadow">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h5 class="card-title text-warning mb-2">Admin Messages</h5>
                                                <h2 class="mb-0"><?= $stats['admin_messages'] ?? 0; ?></h2>
                                            </div>
                                            <div class="avatar">
                                                <div class="avatar-initial bg-warning rounded">
                                                    <i class="ri-admin-line fs-4"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <p class="text-muted mb-0 mt-2">Messages from admin</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Main Card -->
                        <div class="card">
                            <div class="d-flex justify-content-between align-items-center px-4 pt-4">
                                <h4 class="m-0">Live Chat Management</h4>
                                <form action="" method="get" class="d-flex gap-3 align-items-center">
                                    <input type="date" class="form-control" id="date" name="date"
                                        value="<?= $date; ?>" max="<?= date('Y-m-d'); ?>">
                                    <input type="text" class="form-control" id="search" name="search" 
                                        value="<?= htmlspecialchars($search); ?>" placeholder="Search by mobile..." style="width: 200px;">
                                    <button type="submit" class="btn btn-primary">Search</button>
                                    <?php if (!empty($date) || !empty($search)): ?>
                                        <a href="live_chat.php" class="btn btn-secondary">Clear Filter</a>
                                    <?php endif; ?>
                                </form>
                            </div>
                            <div class="p-4">
                                <?php if ($totalConversations > 0): ?>
                                <div class="row">
                                    <!-- Conversations List -->
                                    <div class="col-md-4">
                                        <div class="conversation-list">
                                            <?php 
// Reset the result pointer
mysqli_data_seek($conversationsResult, 0);

// Or re-execute the query properly
$conversationsQuery2 = "SELECT 
    conversation_id,
    user_id,
    mobile,
    COUNT(*) as message_count,
    MAX(timestamp) as last_message_time,
    MIN(timestamp) as first_message_time,
    SUM(CASE WHEN sender = 'user' THEN 1 ELSE 0 END) as user_messages,
    SUM(CASE WHEN sender = 'admin' THEN 1 ELSE 0 END) as admin_messages
    FROM `live_chat` 
    WHERE 1=1";
    
if (!empty($dateFilter)) {
    $conversationsQuery2 .= " AND DATE(timestamp) = '$dateFilter'";
}

if (!empty($search)) {
    $conversationsQuery2 .= " AND (mobile LIKE '%$search%' OR message LIKE '%$search%' OR user_id LIKE '%$search%')";
}

$conversationsQuery2 .= " GROUP BY conversation_id, user_id, mobile ORDER BY last_message_time DESC";
$conversationsResult2 = mysqli_query($conn, $conversationsQuery2);

while ($conv = mysqli_fetch_assoc($conversationsResult2)):

                                                // Get last message preview
                                                $lastMsgQuery = mysqli_query($conn, "SELECT message FROM live_chat WHERE conversation_id = '{$conv['conversation_id']}' ORDER BY timestamp DESC LIMIT 1");
                                                $lastMsg = mysqli_fetch_assoc($lastMsgQuery);
                                                $lastMessagePreview = $lastMsg ? substr($lastMsg['message'], 0, 60) . (strlen($lastMsg['message']) > 60 ? '...' : '') : 'No messages';
                                                
                                                $lastTime = date('d M, h:i A', strtotime($conv['last_message_time']));
                                                ?>
                                                <div class="conversation-card" 
                                                     data-convid="<?= $conv['conversation_id']; ?>"
                                                     data-userid="<?= $conv['user_id']; ?>"
                                                     data-mobile="<?= $conv['mobile']; ?>">
                                                    <div class="d-flex w-100 justify-content-between align-items-start">
                                                        <div class="d-flex align-items-center" style="flex: 1;">
                                                            <div class="user-avatar me-3">
                                                                <?= substr($conv['mobile'], -2); ?>
                                                            </div>
                                                            <div style="min-width: 0;">
                                                                <h6 class="mb-1 text-truncate"><?= htmlspecialchars($conv['user_id']); ?></h6>
                                                                <small class="text-muted d-block"><?= htmlspecialchars($conv['mobile']); ?></small>
                                                                <div class="conversation-message-preview">
                                                                    <?= htmlspecialchars($lastMessagePreview); ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="text-end">
                                                            <small class="text-muted d-block"><?= $lastTime; ?></small>
                                                            <span class="badge bg-primary"><?= $conv['message_count']; ?></span>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex justify-content-between align-items-center mt-2">
                                                        <div class="action-buttons">
                                                            <button class="btn btn-outline-primary btn-sm view-conversation"
                                                                    data-convid="<?= $conv['conversation_id']; ?>"
                                                                    data-userid="<?= $conv['user_id']; ?>"
                                                                    data-mobile="<?= $conv['mobile']; ?>">
                                                                <i class="ri-eye-line"></i> View
                                                            </button>
                                                            <a href="live_chat.php?delete_conversation=<?= $conv['conversation_id']; ?>"
                                                               class="btn btn-outline-danger btn-sm"
                                                               onclick="return confirm('Delete entire conversation with <?= htmlspecialchars($conv['mobile']); ?>? All messages will be permanently deleted.')">
                                                                <i class="ri-delete-bin-line"></i>
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endwhile; ?>
                                        </div>
                                    </div>

                                    <!-- Chat Area -->
                                    <div class="col-md-8">
                                        <div class="card h-100">
                                            <div class="card-body d-flex flex-column">
                                                <div class="empty-chat" id="emptyChat">
                                                    <i class="ri-chat-4-line"></i>
                                                    <h4>Select a conversation</h4>
                                                    <p class="text-muted">Choose a conversation from the list to view and reply to messages</p>
                                                </div>
                                                
                                                <div id="chatArea" style="display: none; flex: 1; display: flex; flex-direction: column;">
                                                    <div class="chat-header">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <div class="d-flex align-items-center">
                                                                <div class="user-avatar me-3 bg-white text-primary">
                                                                    <i class="ri-user-line"></i>
                                                                </div>
                                                                <div>
                                                                    <h5 class="mb-0" id="chatUserName"></h5>
                                                                    <small id="chatUserMobile"></small>
                                                                </div>
                                                            </div>
                                                            <div>
                                                                <span class="badge bg-light text-dark" id="chatMessageCount">0 messages</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Messages Container -->
                                                    <div id="messagesContainer" class="flex-grow-1 mb-3">
                                                        <!-- Messages will be loaded here via AJAX -->
                                                    </div>
                                                    
                                                    <!-- Reply Form -->
                                                    <div class="chat-input-area mt-auto">
                                                        <form id="replyForm" method="POST">
                                                            <input type="hidden" name="conversation_id" id="reply_conversation_id">
                                                            <input type="hidden" name="user_id" id="reply_user_id">
                                                            <input type="hidden" name="mobile" id="reply_mobile">
                                                            <input type="hidden" name="send_message" value="1">
                                                            
                                                            <div class="mb-3">
                                                                <label for="admin_message" class="form-label">Your Message</label>
                                                                <textarea class="form-control" id="admin_message" name="admin_message" 
                                                                          rows="2" placeholder="Type your message here..." required 
                                                                          style="resize: none;"></textarea>
                                                            </div>
                                                            <div class="d-flex justify-content-between align-items-center">
                                                                <small class="text-muted">Press Enter to send, Shift+Enter for new line</small>
                                                                <button type="submit" class="btn btn-success">
                                                                    <i class="ri-send-plane-line"></i> Send Message
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php else: ?>
                                    <div class="text-center py-5">
                                        <div class="text-muted">
                                            <i class="ri-chat-4-line display-4"></i>
                                            <p class="mt-3 mb-0">No conversations found</p>
                                            <?php if (!empty($dateFilter) || !empty($search)): ?>
                                                <p>for the selected filters</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
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
    
    <!-- Scripts -->
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
        // Load conversation messages
        function loadConversation(conversationId, userId, mobile) {
            // Show chat area and hide empty state
            $('#emptyChat').hide();
            $('#chatArea').show();
            
            // Set user info
            $('#chatUserName').text(userId);
            $('#chatUserMobile').text('Mobile: ' + mobile);
            
            // Set form values
            $('#reply_conversation_id').val(conversationId);
            $('#reply_user_id').val(userId);
            $('#reply_mobile').val(mobile);
            
            // Load messages via AJAX
            $.ajax({
                url: 'live_chat.php',
                type: 'GET',
                data: { 
                    ajax: 'get_messages',
                    conversation_id: conversationId 
                },
                success: function(response) {
                    $('#messagesContainer').html(response);
                    // Scroll to bottom
                    $('#messagesContainer').scrollTop($('#messagesContainer')[0].scrollHeight);
                    
                    // Update message count
                    const messageCount = $('#messagesContainer .message-container').length;
                    $('#chatMessageCount').text(messageCount + ' messages');
                },
                error: function() {
                    $('#messagesContainer').html('<div class="text-center text-muted p-4">Error loading messages</div>');
                }
            });
            
            // Mark conversation as active
            $('.conversation-card').removeClass('active');
            $(`.conversation-card[data-convid="${conversationId}"]`).addClass('active');
        }
        
        // Conversation click handler
        $(document).on('click', '.conversation-card, .view-conversation', function(e) {
            e.stopPropagation();
            const conversationId = $(this).data('convid');
            const userId = $(this).data('userid');
            const mobile = $(this).data('mobile');
            loadConversation(conversationId, userId, mobile);
        });
        
        // Handle Enter key in message box
        $('#admin_message').on('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                if ($(this).val().trim() !== '') {
                    $('#replyForm').submit();
                }
            }
        });
        
        // Form submission
        $('#replyForm').on('submit', function(e) {
            const message = $('#admin_message').val().trim();
            if (message === '') {
                e.preventDefault();
                alert('Please enter a message before sending.');
                return false;
            }
            return true;
        });
        
        // Auto refresh conversations every 30 seconds
        setInterval(function() {
            const activeConv = $('.conversation-card.active').data('convid');
            if (activeConv) {
                const userId = $('.conversation-card.active').data('userid');
                const mobile = $('.conversation-card.active').data('mobile');
                loadConversation(activeConv, userId, mobile);
            }
        }, 30000);
        
        // Delete message function
        function deleteMessage(messageId) {
            if (confirm('Are you sure you want to delete this message?')) {
                window.location.href = 'live_chat.php?delete_message=' + messageId;
            }
        }
        
        // Load first conversation on page load if any
        $(document).ready(function() {
            const firstConv = $('.conversation-card:first');
            if (firstConv.length) {
                const convId = firstConv.data('convid');
                const userId = firstConv.data('userid');
                const mobile = firstConv.data('mobile');
                loadConversation(convId, userId, mobile);
            }
        });
    </script>
</body>
</html>