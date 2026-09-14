<?php
// Mobile detection
$isMobile = preg_match('/Mobile|Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i', $_SERVER['HTTP_USER_AGENT'] ?? '');

// Clean API base URL - replace with your actual game provider URL
if ($isMobile) {
    $apibaseurl = "https://devtools.asia/api/mobile/";
} else {
    $apibaseurl = "https://devtools.asia/api/";
}

// Alternative: Use your actual game provider URL
// $apibaseurl = "https://your-game-provider.com/api/";
?>