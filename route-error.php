<?php

require_once __DIR__ . '/developer-maruf/error_logger.php';

$status = (int)($_SERVER['REDIRECT_STATUS'] ?? 404);
if ($status < 400 || $status > 599) {
    $status = 404;
}

$originalUri = (string)($_SERVER['REDIRECT_URL'] ?? $_SERVER['REQUEST_URI'] ?? '');
app_log_event('warning', 'HTTP route not found', [
    'status' => $status,
    'original_uri' => app_safe_request_uri($originalUri),
]);

http_response_code($status);
header('Cache-Control: no-store, no-cache, must-revalidate');
header('X-Content-Type-Options: nosniff');

$accept = strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? ''));
$isApi = str_contains($originalUri, '/api/')
    || str_contains($originalUri, '/api-live-v4/')
    || str_contains($originalUri, '/draw-live-v4/')
    || preg_match('#^/(WinGo|TrxWinGo|K3|D5|MotoRace)/#i', $originalUri);

if ($isApi || str_contains($accept, 'application/json')) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'data' => null,
        'code' => 404,
        'msg' => 'Route not found',
        'msgCode' => 404,
        'requestId' => app_request_id(),
        'serviceNowTime' => date('Y-m-d H:i:s'),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

header('Content-Type: text/plain; charset=utf-8');
echo 'Not found';
