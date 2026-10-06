<?php
/**
 * ============================================================================
 *  maanwin — LOCAL BACKEND BRIDGE
 * ============================================================================
 *  YE FILE api_proxy.php ki jagah hai.
 *
 *  KAAM:
 *    1) Agar request ka path LOCAL backend (api/_router.php) me maujood hai
 *       -> jawab aapke hi MySQL se (users table, bcrypt password)
 *    2) Warna -> https://maanwin16.com par proxy (purana behaviour)
 *
 *  SETTING (niche line badlo):
 *      AR_LOCAL_BACKEND = true   -> local DB pehle  (PURANI SITE JAISA)  <= DEFAULT
 *      AR_LOCAL_BACKEND = false  -> sirf upstream   (naye build jaisa)
 * ============================================================================
 */

if (!defined('AR_LOCAL_BACKEND')) {
    define('AR_LOCAL_BACKEND', true);
}

// /api/_core/ kabhi bhi serve na ho
$AR_URI_PATH = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
if (stripos(trim($AR_URI_PATH, '/'), 'api/_core') !== false) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['code' => -1, 'msg' => 'Forbidden', 'msgCode' => -1, 'data' => null]);
    exit;
}

if (AR_LOCAL_BACKEND) {
    $AR_PATH = trim($AR_URI_PATH, '/');
    if (stripos($AR_PATH, 'api/') === 0) {
        $AR_PATH = substr($AR_PATH, 4);
    }
    $AR_PATH = trim($AR_PATH, '/');

    // Paths jo LOCAL backend handle karta hai (api/_router.php se auto-generated)
    $AR_LOCAL_PATHS = [
    'Activity/AddChampion' => true,
    'Activity/CardPlanReceiveReward' => true,
    'Activity/ClaimLuckyDoubleTaskReward' => true,
    'Activity/CompleteJoinTelegramTask' => true,
    'Activity/DayWeekAccumulateReceive' => true,
    'Activity/GetActivityGuideConfig' => true,
    'Activity/GetActivityInformationDetail' => true,
    'Activity/GetActivityInformationList' => true,
    'Activity/GetAgentRankRecord' => true,
    'Activity/GetAgentRankRewardList' => true,
    'Activity/GetBigJackpotConfigList' => true,
    'Activity/GetCardPlanRechargeCategory' => true,
    'Activity/GetCashRainRules' => true,
    'Activity/GetChampionInfo' => true,
    'Activity/GetChampionPageList' => true,
    'Activity/GetCodewashingDescription' => true,
    'Activity/GetCodewashingInfo' => true,
    'Activity/GetCodewashingPageList' => true,
    'Activity/GetCodewashingPageListFlow' => true,
    'Activity/GetDayWeekTaskRule' => true,
    'Activity/GetHomeBigJackpotRecordPageList' => true,
    'Activity/GetLatestCashRainClaimRecords' => true,
    'Activity/GetListRechargeWheelRewardHistory' => true,
    'Activity/GetLuckyDoubleRechargeTaskConfigs' => true,
    'Activity/GetLuckyDoubleTaskDetails' => true,
    'Activity/GetNextCashRainStatus' => true,
    'Activity/GetPageListInvitedWheelWithdrawRecord' => true,
    'Activity/GetPageListRechargeWheelRewardRecord' => true,
    'Activity/GetPageListRechargeWheelSpinRecord' => true,
    'Activity/GetShareCopy' => true,
    'Activity/GetUserBigJackpotRecordPageList' => true,
    'Activity/GetUserCheckInActivityData' => true,
    'Activity/GetUserDayWeekInfo' => true,
    'Activity/GetUserGiftPackList' => true,
    'Activity/GetUserInvitedWheelInfo' => true,
    'Activity/GetUserLossReliefActivityList' => true,
    'Activity/GetUserRankRecord' => true,
    'Activity/GetUserRankRewardList' => true,
    'Activity/GetUserRechargeGiftPackList' => true,
    'Activity/GetUserRechargeWheelInfo' => true,
    'Activity/GetUserRedEnvelopeRecordPageList' => true,
    'Activity/OneClickCodeWashing' => true,
    'Activity/ReceiveDailyCheckInReward' => true,
    'Activity/ReceiveDayWeekTaskReward' => true,
    'Activity/ReceiveGiftPack' => true,
    'Activity/ReceiveOpenPushGuideReward' => true,
    'Activity/ReceiveRedEnvelope' => true,
    'Activity/ReceiveSpecialBonus' => true,
    'Activity/ReceiveUserLossReliefReward' => true,
    'Activity/ReceivedCashRainReward' => true,
    'Activity/ReceivedLuckyDoubleReward' => true,
    'Activity/ReceivedPromotionShareReward' => true,
    'Activity/RechargeCardPlanToPay' => true,
    'Activity/RechargeGiftToPay' => true,
    'Activity/ReportActivityGuideProcess' => true,
    'Activity/SpinInvitedWheel' => true,
    'Activity/SpinRechargeWheel' => true,
    'Activity/SumitInvitedWheelWithdraw' => true,
    'Activity/UpdateGiftPackCompleted' => true,
    'Activity/UpdateGiftPackSelection' => true,
    'Activity/UploadImage' => true,
    'Activity/UserReceiveAllBigJackpotAward' => true,
    'Activity/UserReceiveBigJackpotAward' => true,
    'Admin/GetResultHistory' => true,
    'AgentL3/GetListCommissionRecord' => true,
    'AgentL3/GetMyInvitationInfo' => true,
    'AgentL3/GetMySubDataSummry' => true,
    'AgentL3/GetMyTeamInfo' => true,
    'AgentL3/GetPageListCommissionDetailRecordByBet' => true,
    'AgentL3/GetPageListCommissionDetailRecordByRecharge' => true,
    'AgentL3/GetPageListInviteRecord' => true,
    'AgentL3/GetPageListInviteTaskRecord' => true,
    'AgentL3/GetPageListSubData' => true,
    'AgentL3/ReceiveNotSendCommissionAmount' => true,
    'AgentRebate/GetCommissionDetail' => true,
    'AgentRebate/GetPageListNewSub' => true,
    'AgentRebate/GetPageListSubList' => true,
    'AgentRebate/GetPageListSubordinateUserInfo' => true,
    'AgentRebate/GetPageListTeamDayReport' => true,
    'AgentRebate/GetPageListTeamDayReportRechargeWithdrawDiff' => true,
    'AgentRebate/GetPromotionData' => true,
    'AgentRebate/GetRebateLevelList' => true,
    'AgentRebate/GetRebateLevelRateList' => true,
    'File/Upload' => true,
    'Game/GetGameDrawTimeList' => true,
    'Game/GetGameListByName' => true,
    'Game/GetHotGameList' => true,
    'Game/GetSubGamePageList' => true,
    'Game/GetVendorList' => true,
    'Home/AppLaunch' => true,
    'Home/AutoLogin' => true,
    'Home/Captcha' => true,
    'Home/CheckCanBet' => true,
    'Home/EmailAutoLogin' => true,
    'Home/GetCommonMessage' => true,
    'Home/GetCommonPopup' => true,
    'Home/GetGiftInfo' => true,
    'Home/GetHomeAllGameList' => true,
    'Home/GetSpreadMaterial' => true,
    'Home/HomeBasic' => true,
    'Home/Login' => true,
    'Home/LoginOff' => true,
    'Home/MobileAutoLogin' => true,
    'Home/RefreshToken' => true,
    'Home/Register' => true,
    'Home/TenantFrontStyle' => true,
    'Home/UploadFile' => true,
    'Lottery/D5Bet' => true,
    'Lottery/GameBetting' => true,
    'Lottery/GameListPage' => true,
    'Lottery/GetBalance' => true,
    'Lottery/GetBalanceInfo' => true,
    'Lottery/GetBetLimit' => true,
    'Lottery/GetCurrentIssue' => true,
    'Lottery/GetDragonList' => true,
    'Lottery/GetGameInfo' => true,
    'Lottery/GetGameIntroduce' => true,
    'Lottery/GetGameIssue' => true,
    'Lottery/GetGameList' => true,
    'Lottery/GetGameListPage' => true,
    'Lottery/GetHistoryIssuePage' => true,
    'Lottery/GetIssue' => true,
    'Lottery/GetLotteryResultHistory' => true,
    'Lottery/GetMyGameRecord' => true,
    'Lottery/GetMyGameRecordPageList' => true,
    'Lottery/GetRecordPage' => true,
    'Lottery/GetTrendStatistics' => true,
    'Lottery/GetUserInfo' => true,
    'Lottery/GetWinLossResult' => true,
    'Lottery/GetWingoLiveUrl' => true,
    'Lottery/GetmyEmeralds' => true,
    'Lottery/GetmyIssusPage' => true,
    'Lottery/K3Bet' => true,
    'Lottery/MotoRaceBet' => true,
    'Lottery/TrxWinGoBet' => true,
    'Lottery/VideoWinGoBet' => true,
    'Lottery/WinGoBet' => true,
    'Recharge/ArBuriedPage' => true,
    'Recharge/ArUpiCancelRechargeOrder' => true,
    'Recharge/ArUpiGetBankListToken' => true,
    'Recharge/ArUpiSubmitUtr' => true,
    'Recharge/CancelLocalRecharge' => true,
    'Recharge/CreateRechargeOrderAppeal' => true,
    'Recharge/DepositRecharge' => true,
    'Recharge/GetArUpiOnGoingOrder' => true,
    'Recharge/GetLocalRechargeOrderDetail' => true,
    'Recharge/GetRechargeBasicInfo' => true,
    'Recharge/GetRechargeCategoryList' => true,
    'Recharge/GetRechargeRecord' => true,
    'Recharge/GoodsDepositRecharge' => true,
    'Recharge/SubmitCertificate' => true,
    'Site/GetSettings' => true,
    'ThirdGame/GetARGameAndPlatWallets' => true,
    'ThirdGame/GetARGameBalance' => true,
    'ThirdGame/GetGameUrl' => true,
    'ThirdGame/NotifyARGameRecover' => true,
    'ThirdGame/RecoverSaasBalance' => true,
    'ThirdGame/Transfer' => true,
    'Upload/UploadFile' => true,
    'Upload/UploadImage' => true,
    'User/GetUserCouponDetail' => true,
    'User/GetUserCouponList' => true,
    'User/GetUserFinancialList' => true,
    'User/GetUserInfo' => true,
    'User/GetUserOrderList' => true,
    'User/GetUserRechargeCouponList' => true,
    'User/SetWithdrawPassword' => true,
    'User/UpdateUserLoginSysLanguage' => true,
    'User/UpdateUserNickName' => true,
    'User/UpdateUserPhoto' => true,
    'User/UseUserCoupon' => true,
    'VipLevel/GetUserVipInfo' => true,
    'VipLevel/GetUserVipRewardList' => true,
    'VipLevel/GetVipLevelConfig' => true,
    'VipLevel/PickVipReward' => true,
    'Withdraw/ActivityArbWallet' => true,
    'Withdraw/AddUserWithdrawWallet' => true,
    'Withdraw/GetArbWalletInfo' => true,
    'Withdraw/GetUserWithdrawWallet' => true,
    'Withdraw/GetWalletCodeList' => true,
    'Withdraw/GetWithdrawBasicInfo' => true,
    'Withdraw/GetWithdrawHistory' => true,
    'Withdraw/WithdrawApply' => true,
    'WorkOrder/AddComment' => true,
    'WorkOrder/AddWorkOrder' => true,
    'WorkOrder/CreateWorkOrder' => true,
    'WorkOrder/DataCheckByOrderNo' => true,
    'WorkOrder/GetCaptcha' => true,
    'WorkOrder/GetCommentList' => true,
    'WorkOrder/GetDetail' => true,
    'WorkOrder/GetFAQDetail' => true,
    'WorkOrder/GetFAQList' => true,
    'WorkOrder/GetFaqDetail' => true,
    'WorkOrder/GetFaqList' => true,
    'WorkOrder/GetFormFieldList' => true,
    'WorkOrder/GetFormList' => true,
    'WorkOrder/GetFormTutorialInfo' => true,
    'WorkOrder/GetHomePageConfigs' => true,
    'WorkOrder/GetKycBankList' => true,
    'WorkOrder/GetOutLinkList' => true,
    'WorkOrder/GetPageList' => true,
    'WorkOrder/GetProgressQuery' => true,
    'WorkOrder/GetQuestionList' => true,
    'WorkOrder/GetSettings' => true,
    'WorkOrder/GetTutorial' => true,
    'WorkOrder/GetWorkOrderCommentList' => true,
    'WorkOrder/GetWorkOrderDetail' => true,
    'WorkOrder/GetWorkOrderPageList' => true,
    'WorkOrder/ReVerifyKycOtpCode' => true,
    'WorkOrder/SendKycOtpCode' => true,
    'WorkOrder/SendReminder' => true,
    'WorkOrder/Submit' => true,
    'WorkOrder/SubmitComment' => true,
    'WorkOrder/SubmitWorkOrder' => true,
    'WorkOrder/UploadToOss' => true,
    'WorkOrder/VerifyKycOtpCode' => true,
    'api/GetFormFieldList' => true,
    ];

    $AR_ROUTER = __DIR__ . '/api/_router.php';

    if ($AR_PATH !== '' && isset($AR_LOCAL_PATHS[$AR_PATH]) && is_file($AR_ROUTER)) {
        $GLOBALS['API_PATH'] = $AR_PATH;
        require $AR_ROUTER;
        exit;
    }
}

// ---------------- LOCAL nahi mila -> original upstream proxy ----------------
/**
 * Same-origin Maanwin API proxy (tenant 6015).
 * Forwards /api/<Controller>/<Action> to https://maanwin16.com so Host/SNI
 * resolves tenant 6015. Body must be forwarded byte-for-byte (client signs it).
 */
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, Accept-Language, X-App-Version');
    http_response_code(204);
    exit;
}

$CHANNEL_ORIGIN = 'https://maanwin16.com';
$UPSTREAM = 'https://maanwin16.com';

$uri = (string) ($_SERVER['REQUEST_URI'] ?? '');
$path = (string) parse_url($uri, PHP_URL_PATH);
$path = '/' . ltrim($path, '/');

if (!preg_match('#^/api/[A-Za-z0-9_]+/[A-Za-z0-9_]+/?$#', $path)) {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['code' => -1, 'msg' => 'Bad API endpoint', 'msgCode' => -1, 'data' => null]);
    exit;
}

$target = $UPSTREAM . $path;
$qs = [];
foreach ($_GET as $k => $v) {
    $qs[$k] = $v;
}
if ($qs) {
    $target .= '?' . http_build_query($qs);
}

$headers = [
    'Origin: ' . $CHANNEL_ORIGIN,
    'Referer: ' . $CHANNEL_ORIGIN . '/',
    'Accept: application/json, text/plain, */*',
];

$contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
if ($contentType !== '') {
    $headers[] = 'Content-Type: ' . $contentType;
} else {
    $headers[] = 'Content-Type: application/json;charset=UTF-8';
}

if (!empty($_SERVER['HTTP_USER_AGENT'])) {
    $headers[] = 'User-Agent: ' . $_SERVER['HTTP_USER_AGENT'];
}
if (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
    $headers[] = 'Accept-Language: ' . $_SERVER['HTTP_ACCEPT_LANGUAGE'];
}

$auth = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
if ($auth === '' && function_exists('apache_request_headers')) {
    foreach (apache_request_headers() as $k => $v) {
        if (strtolower((string) $k) === 'authorization') {
            $auth = (string) $v;
            break;
        }
    }
}
if ($auth === '' && function_exists('getallheaders')) {
    foreach (getallheaders() as $k => $v) {
        if (strtolower((string) $k) === 'authorization') {
            $auth = (string) $v;
            break;
        }
    }
}
$auth = trim((string) $auth);
if ($auth !== '' && $auth !== 'undefined' && $auth !== 'null' && $auth !== 'Bearer') {
    $headers[] = 'Authorization: ' . $auth;
}

foreach (['HTTP_X_APP_VERSION' => 'X-App-Version'] as $srv => $name) {
    if (!empty($_SERVER[$srv])) {
        $headers[] = $name . ': ' . $_SERVER[$srv];
    }
}

$body = file_get_contents('php://input');
if ($body === false) {
    $body = '';
}

if (!function_exists('curl_init')) {
    http_response_code(502);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['code' => 1, 'msg' => 'curl missing', 'msgCode' => 1, 'data' => null]);
    exit;
}

$ch = curl_init($target);
$opts = [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HEADER => true,
    CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_CUSTOMREQUEST => strtoupper($_SERVER['REQUEST_METHOD'] ?? 'POST'),
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_TIMEOUT => 45,
    CURLOPT_CONNECTTIMEOUT => 15,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
    CURLOPT_ENCODING => '',
];
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
if ($method !== 'GET' && $method !== 'HEAD') {
    $opts[CURLOPT_POSTFIELDS] = $body;
}
curl_setopt_array($ch, $opts);

$raw = curl_exec($ch);
$errno = curl_errno($ch);
$status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
curl_close($ch);

if ($raw === false || $errno) {
    http_response_code(502);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['code' => 1, 'msg' => 'ProxyError', 'msgCode' => 1, 'data' => null]);
    exit;
}

$respHeaders = substr($raw, 0, $headerSize);
$respBody = substr($raw, $headerSize);
http_response_code($status > 0 ? $status : 200);

$sentType = false;
foreach (explode("\r\n", $respHeaders) as $line) {
    if (stripos($line, 'Content-Type:') === 0) {
        header($line, true);
        $sentType = true;
    }
}
if (!$sentType) {
    header('Content-Type: application/json; charset=utf-8');
}

echo $respBody;
