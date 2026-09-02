<?php

require_once __DIR__ . '/_common.php';
api_require_post();
$body = api_input();
api_require_signature($body);
$user = api_user();

if (($body['vendorCode'] ?? '') !== 'ARLottery') {
    api_send(null, 7, 'Param is Invalid', 200, 6);
}

$gameCode = trim((string)($body['gameCode'] ?? ''));
$games = [
    'WinGo_30S' => 'WinGo', 'WinGo_1M' => 'WinGo', 'WinGo_3M' => 'WinGo',
    'WinGo_5M' => 'WinGo', 'WinGo_10M' => 'WinGo',
    'K3_1M' => 'K3', 'K3_3M' => 'K3', 'K3_5M' => 'K3', 'K3_10M' => 'K3',
    'D5_1M' => 'D5', 'D5_3M' => 'D5', 'D5_5M' => 'D5', 'D5_10M' => 'D5',
    'TrxWinGo_1M' => 'TrxWinGo', 'TrxWinGo_3M' => 'TrxWinGo',
    'TrxWinGo_5M' => 'TrxWinGo', 'TrxWinGo_10M' => 'TrxWinGo',
    'MotoRace_1M' => 'MotoRace', 'MotoRace_3M' => 'MotoRace',
    'MotoRace_5M' => 'MotoRace', 'MotoRace_10M' => 'MotoRace',
];
if ($gameCode !== '' && !isset($games[$gameCode])) {
    api_send(null, 7, 'Param is Invalid', 200, 6);
}

$forwardedProto = strtolower(trim(explode(',', (string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''))[0]));
$scheme = ($forwardedProto === 'https' || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')) ? 'https' : 'http';
$host = preg_replace('/[^A-Za-z0-9.:-]/', '', (string)($_SERVER['HTTP_HOST'] ?? 'damansclub.com'));
$origin = $scheme . '://' . ($host !== '' ? $host : 'damansclub.com');
$language = preg_replace('/[^A-Za-z-]/', '', (string)($body['language'] ?? 'en')) ?: 'en';

$url = $origin . '/?Token=' . rawurlencode((string)$user['token'])
    . '&Skin=blackGoldStyle&Lang=' . rawurlencode($language);
if ($gameCode !== '') {
    $lottery = $games[$gameCode];
    $url .= '#/saasLottery/' . rawurlencode($lottery)
        . '?gameCode=' . rawurlencode($gameCode)
        . '&lottery=' . rawurlencode($lottery);
}

api_send(['url' => $url, 'returnType' => 1]);

