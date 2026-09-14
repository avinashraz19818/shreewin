<?php
/**
 * Home / bootstrap settings for the VeerGame client.
 *
 * VeerGame hotfix note
 * --------------------
 * This file used to be a frozen capture of the live API response and it
 * carried "isOpenArLottery": false, "isSwitchSaasBalance": false.
 *
 * The client uses those two flags to decide how a lottery tile behaves:
 *   isOpenArLottery    false -> the tile is treated as an EXTERNAL AR vendor
 *                               game and the app tries to hand the user over to
 *                               api.<domain>/draw.<domain> with a third party
 *                               token. Those hosts do not exist on this server,
 *                               so WinGo/K3/D5/TrxWinGo/MotoRace opened to a
 *                               blank or stuck screen.
 *   isSwitchSaasBalance false -> the wallet kept showing the old (empty) saas
 *                               ledger instead of the real balance.
 *
 * This deployment runs the lottery itself (api-live-v4 + draw-live-v4), so the
 * flags are now forced on here, and the response is served as fresh JSON.
 *
 * Keep the rest of the values in the array below - they drive branding, the
 * recharge page, registration rules and the activity modules.
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$settings = json_decode(<<<'JSON'
{
        "version": "2026-04-20 001",
        "arUpiInputUtrSwitch": false,
        "arbApiUrl": [
                "https://apiweb.asjoby.com"
        ],
        "isShowAppDownloadUp": false,
        "isShowAppDownloadDown": true,
        "isShowRewardCenter": true,
        "isShowLotteryDragon": true,
        "isSplitLocalEWallet": true,
        "jackportMaxReswadAmount": 355.0,
        "projectName": "veergame",
        "projectLogo": "https://ossimg.veergamepay.com/veergame/other/h5setting_202608291515262umj.png",
        "languages": "en|hd|ta|te",
        "webIco": "https://ossimg.veergamepay.com/veergame/other/h5setting_20260829151538gix9.png",
        "headLogo": "https://ossimg.veergamepay.com/veergame/other/h5setting_20260829151532fqt9.png",
        "dollarSign": "₹",
        "upperOrLower": "0",
        "defaultCurrentLanguage": "en",
        "registerMobile": "1",
        "registerEmail": "0",
        "areaPhoneLenList": [
                {
                        "area": "+91",
                        "len": "10"
                }
        ],
        "registerSms": "0",
        "isOpenLoginChangeLanguage": "1",
        "rewardValidityTime": 1,
        "electronicWinRateExternalLink": "",
        "electronicWinRateImgUrl": "",
        "isShowElectronicWinRateExternalLink": false,
        "isShowAppHandCodeWashingSwitch": true,
        "isShowHotGameWinOdds": true,
        "ossUrl": "https://ossimg.veergamepay.com",
        "bigTurntableLink": "",
        "bigTurntableImgUrl": "",
        "telegramExternalLink": "https://t.me/veergame",
        "telegramImgUrl": "",
        "isOpenActivityAward": true,
        "isOpenTurntable": true,
        "isPartnerReward": true,
        "isSelfCustomerService": true,
        "webSiteUrl": "https://www.veergame44.com",
        "isOpenFacebookEvent": false,
        "firstDepositRewardCodeAmount": "1",
        "isOpenRegisterPhoneFirstZeroSwitch": false,
        "eventRegionConfigList": null,
        "isOpenAdjustEvent": false,
        "firebaseConfig": null,
        "isOpenArLottery": false,
        "isSwitchSaasBalance": false,
        "isV3Mode": true,
        "isOpenInvitedWheel": true,
        "invitedWheelTotalPrizeAmount": 500.0,
        "invitedWheelImgUrl": "https://ossimg.veergamepay.com/veergame/tab/wheel.png",
        "isOpenDownAppRewardSwitch": false,
        "isShowDownAppBonusAmountSwitch": false,
        "downAppBonusAmount": 0.0,
        "downAppRechargeAmount": 0.0,
        "needKycValidIsOpen": false,
        "needFastKycValidIsOpen": false,
        "lotteryDragonIcon": null,
        "bonusCenterImgUrl": "",
        "isShowBonusCenterExternalLink": true,
        "isOpenBrowserConsoleDebug": true,
        "homeBigTurntableSwitch": true,
        "homeBigTurntableLink": "",
        "homeBigTurntableImgUrl": "https://ossimg.veergamepay.com/veergame/vendorlogo/vendorlogo_20260904152718kiy3.png",
        "jgConfig": {
                "webConfig": null,
                "appConfig": null
        },
        "lastestAppVersionInfo": null
}
JSON, true);

if (!is_array($settings)) {
    $settings = array();
}

// Lottery engine flags - must stay on for the built-in WinGo/K3/D5/Trx pages.
$settings['isOpenArLottery'] = true;      // open the internal saas lottery pages
$settings['isSwitchSaasBalance'] = true;  // wallet amount comes from the API
$settings['isV3Mode'] = false;            // v3 mode needs files this build does not ship
$settings['isShowLotteryDragon'] = isset($settings['isShowLotteryDragon']) ? $settings['isShowLotteryDragon'] : true;

// Cache-busting version so browsers pick the corrected settings up immediately.
$settings['version'] = '2026-09-14 veergame-lottery-fix';

echo json_encode(array(
    'data' => $settings,
    'code' => 0,
    'msg' => 'Succeed',
    'msgCode' => 0,
    'traceId' => '',
    'serviceNowTime' => date('Y-m-d H:i:s'),
), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
