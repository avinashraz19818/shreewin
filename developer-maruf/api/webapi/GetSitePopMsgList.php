<?php
require_once __DIR__ . '/_shreewin_brand.php';

header('Content-Type: application/json; charset=utf-8');
header('Strict-Transport-Security: max-age=31536000');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Origin: ' . shreewin_public_origin());
header('Vary: Origin');

date_default_timezone_set('Asia/Kolkata');
$now = date('Y-m-d H:i:s');
$officialUrl = htmlspecialchars(shreewin_public_origin() . '/', ENT_QUOTES, 'UTF-8');
$brandImage = htmlspecialchars(SHREEWIN_BRAND_IMAGE_URL, ENT_QUOTES, 'UTF-8');

$data = array(
    array(
        'title' => 'SHREE WIN RECHARGE BONUS 3%',
        'siteMessage' => '<div style="text-align:center"><img src="' . $brandImage . '" alt="Shree Win" style="display:block;max-width:260px;width:70%;height:auto;margin:0 auto 14px"><h3 style="margin:0 0 8px;color:#ff8a00">SHREE WIN RECHARGE BONUS 3%</h3><p style="margin:0">Recharge through the available payment methods and check the current bonus details before submitting.</p></div>',
        'sort' => 60,
        'addtime' => $now,
    ),
    array(
        'title' => 'Official Shree Win Website Notice',
        'siteMessage' => '<div style="text-align:center"><img src="' . $brandImage . '" alt="Shree Win" style="display:block;max-width:240px;width:65%;height:auto;margin:0 auto 14px"><h3 style="color:#e53935">Warning Notice</h3><p>For your safety, use only the official Shree Win website. Never register, deposit, or share personal information on an unknown copy website.</p><p><strong>Never share your password or OTP with anyone, including anyone claiming to be customer support.</strong></p><p><a href="' . $officialUrl . '" target="_blank" rel="noopener noreferrer">Open Official Shree Win Website</a></p></div>',
        'sort' => 50,
        'addtime' => $now,
    ),
    array(
        'title' => 'UPI Withdrawal Reminder',
        'siteMessage' => '<div style="text-align:center"><img src="' . $brandImage . '" alt="Shree Win" style="display:block;max-width:220px;width:60%;height:auto;margin:0 auto 14px"><p>📣 UPI Withdrawal Reminder</p><ol style="text-align:left"><li>Enter the correct and complete UPI ID.</li><li>Do not use a mobile number in place of a valid UPI ID.</li><li>Never share your password or OTP.</li><li>Copy and paste the UPI ID from your payment app to avoid typing errors.</li></ol></div>',
        'sort' => 40,
        'addtime' => $now,
    ),
    array(
        'title' => 'Welcome To Shree Win',
        'siteMessage' => '<div style="text-align:center"><img src="' . $brandImage . '" alt="Shree Win" style="display:block;max-width:260px;width:70%;height:auto;margin:0 auto 14px"><p><strong>Welcome to Shree Win!</strong></p><p>Great to have you here. Check announcements regularly for the latest platform and event updates.</p><p><a href="' . $officialUrl . '" target="_blank" rel="noopener noreferrer">Official Shree Win Website</a></p><p>Warm regards,<br>The Shree Win Team</p></div>',
        'sort' => 30,
        'addtime' => $now,
    ),
);

echo json_encode(
    array(
        'data' => $data,
        'code' => 0,
        'msg' => 'Succeed',
        'msgCode' => 0,
        'traceId' => '',
        'serviceNowTime' => $now,
    ),
    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
);

