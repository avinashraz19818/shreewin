<?php
session_start();
if(empty($_SESSION['unohs'])){
    header("location: api/login.php?msg=unauthorized");
}
include ("api/conn.php");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    // Get all posted values
    $settings = [
        'isShowAppDownloadUp' => isset($_POST['isShowAppDownloadUp']) ? 1 : 0,
        'isShowAppDownloadDown' => isset($_POST['isShowAppDownloadDown']) ? 1 : 0,
        'isShowLotteryDragon' => isset($_POST['isShowLotteryDragon']) ? 1 : 0,
        'isSplitLocalEWallet' => isset($_POST['isSplitLocalEWallet']) ? 1 : 0,
        'registerMobile' => isset($_POST['registerMobile']) ? 1 : 0,
        'registerEmail' => isset($_POST['registerEmail']) ? 1 : 0,
        'registerSms' => isset($_POST['registerSms']) ? 1 : 0,
        'isOpenLoginChangeLanguage' => isset($_POST['isOpenLoginChangeLanguage']) ? 1 : 0,
        'rewardValidityTime' => (int)$_POST['rewardValidityTime'],
        'electronicWinRateExternalLink' => $_POST['electronicWinRateExternalLink'],
        'electronicWinRateImgUrl' => $_POST['electronicWinRateImgUrl'],
        'isShowElectronicWinRateExternalLink' => isset($_POST['isShowElectronicWinRateExternalLink']) ? 1 : 0,
        'isShowAppHandCodeWashingSwitch' => isset($_POST['isShowAppHandCodeWashingSwitch']) ? 1 : 0,
        'isShowHotGameWinOdds' => isset($_POST['isShowHotGameWinOdds']) ? 1 : 0,
        'ossUrl' => $_POST['ossUrl'],
        'bigTurntableLink' => $_POST['bigTurntableLink'],
        'telegramExternalLink' => $_POST['telegramExternalLink'],
        'isOpenActivityAward' => isset($_POST['isOpenActivityAward']) ? 1 : 0,
        'isOpenTurntable' => isset($_POST['isOpenTurntable']) ? 1 : 0,
        'isPartnerReward' => isset($_POST['isPartnerReward']) ? 1 : 0,
        'isOpenArLottery' => isset($_POST['isOpenArLottery']) ? 1 : 0,
        'isSwitchSaasBalance' => isset($_POST['isSwitchSaasBalance']) ? 1 : 0,
        'isOpenInvitedWheel' => isset($_POST['isOpenInvitedWheel']) ? 1 : 0,
        'invitedWheelTotalPrizeAmount' => (float)$_POST['invitedWheelTotalPrizeAmount'],
        'invitedWheelImgUrl' => $_POST['invitedWheelImgUrl'],
        'isOpenDownAppRewardSwitch' => isset($_POST['isOpenDownAppRewardSwitch']) ? 1 : 0,
        'isShowDownAppBonusAmountSwitch' => isset($_POST['isShowDownAppBonusAmountSwitch']) ? 1 : 0,
        'downAppBonusAmount' => (float)$_POST['downAppBonusAmount'],
        'firstDepositRewardCodeAmount' => $_POST['firstDepositRewardCodeAmount'],
        'isOpenAdjustEvent' => isset($_POST['isOpenAdjustEvent']) ? 1 : 0
    ];
    
    // Check if settings exist
    $check = $conn->query("SELECT id FROM home_setting LIMIT 1");
    
    if ($check->num_rows > 0) {
        // Update existing settings
        $sql = "UPDATE home_setting SET ";
        $params = [];
        $types = '';
        $values = [];
        
        foreach ($settings as $key => $value) {
            $sql .= "$key = ?, ";
            $params[] = &$settings[$key];
            $values[] = $value;
            
            if (is_int($value) || is_float($value)) {
                $types .= 'i';
            } else {
                $types .= 's';
            }
        }
        
        $sql = rtrim($sql, ', ');
        
        $stmt = $conn->prepare($sql);
        
        // Bind parameters dynamically
        $bind_params = array_merge([$types], $params);
        call_user_func_array([$stmt, 'bind_param'], $bind_params);
        
        $stmt->execute();
    } else {
        // Insert new settings
        $keys = implode(', ', array_keys($settings));
        $placeholders = implode(', ', array_fill(0, count($settings), '?'));
        
        $sql = "INSERT INTO home_setting ($keys) VALUES ($placeholders)";
        
        $stmt = $conn->prepare($sql);
        
        // Bind parameters dynamically
        $types = '';
        $bind_params = [];
        foreach ($settings as $key => $value) {
            if (is_int($value) || is_float($value)) {
                $types .= 'i';
            } else {
                $types .= 's';
            }
            $bind_params[] = &$settings[$key];
        }
        
        array_unshift($bind_params, $types);
        call_user_func_array([$stmt, 'bind_param'], $bind_params);
        
        $stmt->execute();
    }
    
    $_SESSION['success'] = "Settings updated successfully!";
    header("Location: home_setting.php");
    exit();
}

// Fetch current settings
$result = $conn->query("SELECT * FROM home_setting LIMIT 1");
$settings = $result->num_rows > 0 ? $result->fetch_assoc() : [];
?>

<!doctype html>
<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr"
    data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template" data-style="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Home Settings Management</title>
    <meta name="description" content="" />
    <link rel="icon" type="image/x-icon" href="assets/img/favicon/favicon.ico?v=20260814-admin" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&ampdisplay=swap" rel="stylesheet" />
    <link rel="stylesheet" href="assets/vendor/fonts/remixicon/remixicon.css" />
    <link rel="stylesheet" href="assets/vendor/fonts/flag-icons.css" />
    <link rel="stylesheet" href="assets/vendor/libs/node-waves/node-waves.css" />
    <link rel="stylesheet" href="assets/vendor/css/rtl/core.css" class="template-customizer-core-css" />
    <link rel="stylesheet" href="assets/vendor/css/rtl/theme-default.css" class="template-customizer-theme-css" />
    <link rel="stylesheet" href="assets/css/demo.css" />
    <link rel="stylesheet" href="assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
    <link rel="stylesheet" href="assets/vendor/libs/typeahead-js/typeahead.css" />
    <script src="assets/vendor/js/helpers.js"></script>
    <script src="assets/vendor/js/template-customizer.js"></script>
    <script src="assets/js/config.js"></script>
    <style>
        .settings-card {
            transition: all 0.3s ease;
            border-radius: 10px;
        }
        .settings-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .form-check-input:checked {
            background-color: #696cff;
            border-color: #696cff;
        }
        .form-check-input {
            width: 3em;
            height: 1.5em;
        }
        .section-title {
            border-bottom: 2px solid #696cff;
            padding-bottom: 10px;
            margin-bottom: 20px;
            color: #566a7f;
        }
        .settings-group {
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
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
                        
                        <!-- Success Message -->
                        <?php if(isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible" role="alert">
                            <?= $_SESSION['success'] ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['success']); endif; ?>

                        <!-- Header -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card settings-card">
                                    <div class="card-header">
                                        <h4 class="card-title mb-0">
                                            <i class="ri-home-gear-line me-2"></i>
                                            Home Settings Management
                                        </h4>
                                        <p class="text-muted mb-0">Control all frontend settings and features</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Settings Form -->
                        <form method="POST" action="">
                        <div class="row">
                            <!-- Left Column -->
                            <div class="col-lg-6">
                                <!-- App Download Settings -->
                                <div class="card settings-card mb-4">
                                    <div class="card-header">
                                        <h5 class="section-title">📱 App Download Settings</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="settings-group">
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" name="isShowAppDownloadUp" 
                                                    id="isShowAppDownloadUp" <?= (isset($settings['isShowAppDownloadUp']) && $settings['isShowAppDownloadUp'] == 1) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="isShowAppDownloadUp">
                                                    Show App Download (Top)
                                                </label>
                                            </div>
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" name="isShowAppDownloadDown" 
                                                    id="isShowAppDownloadDown" <?= (isset($settings['isShowAppDownloadDown']) && $settings['isShowAppDownloadDown'] == 1) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="isShowAppDownloadDown">
                                                    Show App Download (Bottom)
                                                </label>
                                            </div>
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" name="isOpenDownAppRewardSwitch" 
                                                    id="isOpenDownAppRewardSwitch" <?= (isset($settings['isOpenDownAppRewardSwitch']) && $settings['isOpenDownAppRewardSwitch'] == 1) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="isOpenDownAppRewardSwitch">
                                                    Open Download App Reward
                                                </label>
                                            </div>
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" name="isShowDownAppBonusAmountSwitch" 
                                                    id="isShowDownAppBonusAmountSwitch" <?= (isset($settings['isShowDownAppBonusAmountSwitch']) && $settings['isShowDownAppBonusAmountSwitch'] == 1) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="isShowDownAppBonusAmountSwitch">
                                                    Show Download App Bonus
                                                </label>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label" for="downAppBonusAmount">Download App Bonus Amount (₹)</label>
                                                <input type="number" step="0.01" class="form-control" name="downAppBonusAmount" 
                                                    id="downAppBonusAmount" value="<?= isset($settings['downAppBonusAmount']) ? $settings['downAppBonusAmount'] : '0.0' ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Registration Settings -->
                                <div class="card settings-card mb-4">
                                    <div class="card-header">
                                        <h5 class="section-title">📝 Registration Settings</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="settings-group">
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" name="registerMobile" 
                                                    id="registerMobile" <?= (isset($settings['registerMobile']) && $settings['registerMobile'] == 1) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="registerMobile">
                                                    Require Mobile Number
                                                </label>
                                            </div>
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" name="registerEmail" 
                                                    id="registerEmail" <?= (isset($settings['registerEmail']) && $settings['registerEmail'] == 1) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="registerEmail">
                                                    Require Email
                                                </label>
                                            </div>
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" name="registerSms" 
                                                    id="registerSms" <?= (isset($settings['registerSms']) && $settings['registerSms'] == 1) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="registerSms">
                                                    Enable SMS Registration
                                                </label>
                                            </div>
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" name="isOpenLoginChangeLanguage" 
                                                    id="isOpenLoginChangeLanguage" <?= (isset($settings['isOpenLoginChangeLanguage']) && $settings['isOpenLoginChangeLanguage'] == 1) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="isOpenLoginChangeLanguage">
                                                    Allow Language Change on Login
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Game Features -->
                                <div class="card settings-card mb-4">
                                    <div class="card-header">
                                        <h5 class="section-title">🎮 Game Features</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="settings-group">
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" name="isShowLotteryDragon" 
                                                    id="isShowLotteryDragon" <?= (isset($settings['isShowLotteryDragon']) && $settings['isShowLotteryDragon'] == 1) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="isShowLotteryDragon">
                                                    Show Lottery Dragon
                                                </label>
                                            </div>
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" name="isShowHotGameWinOdds" 
                                                    id="isShowHotGameWinOdds" <?= (isset($settings['isShowHotGameWinOdds']) && $settings['isShowHotGameWinOdds'] == 1) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="isShowHotGameWinOdds">
                                                    Show Hot Game Win Odds
                                                </label>
                                            </div>
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" name="isShowAppHandCodeWashingSwitch" 
                                                    id="isShowAppHandCodeWashingSwitch" <?= (isset($settings['isShowAppHandCodeWashingSwitch']) && $settings['isShowAppHandCodeWashingSwitch'] == 1) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="isShowAppHandCodeWashingSwitch">
                                                    Show Hand Code Washing
                                                </label>
                                            </div>
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" name="isOpenArLottery" 
                                                    id="isOpenArLottery" <?= (isset($settings['isOpenArLottery']) && $settings['isOpenArLottery'] == 1) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="isOpenArLottery">
                                                    Open AR Lottery
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Column -->
                            <div class="col-lg-6">
                                <!-- E-Wallet & Balance -->
                                <div class="card settings-card mb-4">
                                    <div class="card-header">
                                        <h5 class="section-title">💰 E-Wallet & Balance</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="settings-group">
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" name="isSplitLocalEWallet" 
                                                    id="isSplitLocalEWallet" <?= (isset($settings['isSplitLocalEWallet']) && $settings['isSplitLocalEWallet'] == 1) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="isSplitLocalEWallet">
                                                    Split Local E-Wallet
                                                </label>
                                            </div>
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" name="isSwitchSaasBalance" 
                                                    id="isSwitchSaasBalance" <?= (isset($settings['isSwitchSaasBalance']) && $settings['isSwitchSaasBalance'] == 1) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="isSwitchSaasBalance">
                                                    Switch SaaS Balance
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Invite Wheel Settings -->
                                <!--<div class="card settings-card mb-4">-->
                                <!--    <div class="card-header">-->
                                <!--        <h5 class="section-title">🎡 Invite Wheel Settings</h5>-->
                                <!--    </div>-->
                                <!--    <div class="card-body">-->
                                <!--        <div class="settings-group">-->
                                <!--            <div class="form-check form-switch mb-3">-->
                                <!--                <input class="form-check-input" type="checkbox" name="isOpenInvitedWheel" -->
                                <!--                    id="isOpenInvitedWheel" <?= (isset($settings['isOpenInvitedWheel']) && $settings['isOpenInvitedWheel'] == 1) ? 'checked' : '' ?>>-->
                                <!--                <label class="form-check-label" for="isOpenInvitedWheel">-->
                                <!--                    Open Invited Wheel-->
                                <!--                </label>-->
                                <!--            </div>-->
                                <!--            <div class="mb-3">-->
                                <!--                <label class="form-label" for="invitedWheelTotalPrizeAmount">Total Prize Amount (₹)</label>-->
                                <!--                <input type="number" step="0.01" class="form-control" name="invitedWheelTotalPrizeAmount" -->
                                <!--                    id="invitedWheelTotalPrizeAmount" value="<?= isset($settings['invitedWheelTotalPrizeAmount']) ? $settings['invitedWheelTotalPrizeAmount'] : '500.00' ?>">-->
                                <!--            </div>-->
                                <!--            <div class="mb-3">-->
                                <!--                <label class="form-label" for="invitedWheelImgUrl">Wheel Image URL</label>-->
                                <!--                <input type="text" class="form-control" name="invitedWheelImgUrl" -->
                                <!--                    id="invitedWheelImgUrl" value="<?= isset($settings['invitedWheelImgUrl']) ? $settings['invitedWheelImgUrl'] : 'https://ossimg.51game-game.com/51game/tab/wheel.png' ?>">-->
                                <!--            </div>-->
                                <!--        </div>-->
                                <!--    </div>-->
                                <!--</div>-->

                                <!-- Rewards & Activity -->
                                <div class="card settings-card mb-4">
                                    <div class="card-header">
                                        <h5 class="section-title">🎁 Rewards & Activity</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="settings-group">
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" name="isOpenActivityAward" 
                                                    id="isOpenActivityAward" <?= (isset($settings['isOpenActivityAward']) && $settings['isOpenActivityAward'] == 1) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="isOpenActivityAward">
                                                    Open Activity Award
                                                </label>
                                            </div>
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" name="isOpenTurntable" 
                                                    id="isOpenTurntable" <?= (isset($settings['isOpenTurntable']) && $settings['isOpenTurntable'] == 1) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="isOpenTurntable">
                                                    Open Turntable
                                                </label>
                                            </div>
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" name="isPartnerReward" 
                                                    id="isPartnerReward" <?= (isset($settings['isPartnerReward']) && $settings['isPartnerReward'] == 1) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="isPartnerReward">
                                                    Partner Reward
                                                </label>
                                            </div>
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" name="isOpenAdjustEvent" 
                                                    id="isOpenAdjustEvent" <?= (isset($settings['isOpenAdjustEvent']) && $settings['isOpenAdjustEvent'] == 1) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="isOpenAdjustEvent">
                                                    Open Adjust Event
                                                </label>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label" for="firstDepositRewardCodeAmount">First Deposit Reward Code Amount</label>
                                                <input type="text" class="form-control" name="firstDepositRewardCodeAmount" 
                                                    id="firstDepositRewardCodeAmount" value="<?= isset($settings['firstDepositRewardCodeAmount']) ? $settings['firstDepositRewardCodeAmount'] : '1' ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label" for="rewardValidityTime">Reward Validity Time (Days)</label>
                                                <input type="number" class="form-control" name="rewardValidityTime" 
                                                    id="rewardValidityTime" value="<?= isset($settings['rewardValidityTime']) ? $settings['rewardValidityTime'] : '30' ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- URL Settings -->
                                <div class="card settings-card mb-4">
                                    <div class="card-header">
                                        <h5 class="section-title">🔗 URL Settings</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="settings-group">
                                            <div class="mb-3">
                                                <label class="form-label" for="ossUrl">OSS URL</label>
                                                <input type="text" class="form-control" name="ossUrl" 
                                                    id="ossUrl" value="<?= isset($settings['ossUrl']) ? $settings['ossUrl'] : 'https://boom92.online' ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label" for="bigTurntableLink">Big Turntable Link</label>
                                                <input type="text" class="form-control" name="bigTurntableLink" 
                                                    id="bigTurntableLink" value="<?= isset($settings['bigTurntableLink']) ? $settings['bigTurntableLink'] : '' ?>">
                                            </div>
                                            <!--<div class="mb-3">-->
                                            <!--    <label class="form-label" for="telegramExternalLink">Telegram External Link</label>-->
                                            <!--    <input type="text" class="form-control" name="telegramExternalLink" -->
                                            <!--        id="telegramExternalLink" value="<?= isset($settings['telegramExternalLink']) ? $settings['telegramExternalLink'] : '' ?>">-->
                                            <!--</div>-->
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" name="isShowElectronicWinRateExternalLink" 
                                                    id="isShowElectronicWinRateExternalLink" <?= (isset($settings['isShowElectronicWinRateExternalLink']) && $settings['isShowElectronicWinRateExternalLink'] == 1) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="isShowElectronicWinRateExternalLink">
                                                    Show Electronic Win Rate External Link
                                                </label>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label" for="electronicWinRateExternalLink">Electronic Win Rate External Link</label>
                                                <input type="text" class="form-control" name="electronicWinRateExternalLink" 
                                                    id="electronicWinRateExternalLink" value="<?= isset($settings['electronicWinRateExternalLink']) ? $settings['electronicWinRateExternalLink'] : '' ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label" for="electronicWinRateImgUrl">Electronic Win Rate Image URL</label>
                                                <input type="text" class="form-control" name="electronicWinRateImgUrl" 
                                                    id="electronicWinRateImgUrl" value="<?= isset($settings['electronicWinRateImgUrl']) ? $settings['electronicWinRateImgUrl'] : 'https://boom92.online/sikkim' ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="row">
                            <div class="col-12">
                                <div class="card settings-card">
                                    <div class="card-body text-center">
                                        <button type="submit" name="update_settings" class="btn btn-primary btn-lg px-5">
                                            <i class="ri-save-line me-2"></i>
                                            Save All Settings
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        </form>
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
    <script src="assets/js/main.js"></script>

    <script>
    // Toggle bonus amount field based on checkbox
    const bonusSwitch = document.getElementById('isShowDownAppBonusAmountSwitch');
    const bonusAmount = document.getElementById('downAppBonusAmount');
    
    function toggleBonusField() {
        bonusAmount.disabled = !bonusSwitch.checked;
    }
    
    bonusSwitch.addEventListener('change', toggleBonusField);
    toggleBonusField(); // Initial state
    
    // Toggle electronic win rate link field
    const winRateSwitch = document.getElementById('isShowElectronicWinRateExternalLink');
    const winRateLink = document.getElementById('electronicWinRateExternalLink');
    
    function toggleWinRateField() {
        winRateLink.disabled = !winRateSwitch.checked;
    }
    
    winRateSwitch.addEventListener('change', toggleWinRateField);
    toggleWinRateField(); // Initial state
    </script>
</body>
</html>