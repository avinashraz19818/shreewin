<?php
// SHREEWIN HOTFIX: bridge legacy/direct endpoint to the full SaaS Lottery v4 controller.
$_GET['action'] = 'GetFollowRecord';
require dirname(__DIR__, 2) . '/api-live-v4/Lottery/index.php';
