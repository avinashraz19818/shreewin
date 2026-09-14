<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$adminId = isset($_SESSION['unohs']) ? (int)$_SESSION['unohs'] : 0;
if ($adminId < 1) {
    header('Location: api/login.php?msg=unauthorized');
    exit;
}

$permissionKeys = [
    'dashboard','manageteam','games','wingomanager','k3manager','5dmanager','setting','finance',
    'admins','manageusers','manage_game','manage_agent','assign_bonus','support','users_pan','other'
];
$salu = array_fill_keys($permissionKeys, 0);
try {
    // $adminId is already an integer; a normal mysqli result keeps the menu
    // compatible with shared hosts where mysqli_stmt::get_result is missing.
    $adminResult = $conn->query('SELECT * FROM nirvahaka_shonu WHERE unohs=' . $adminId . ' LIMIT 1');
    $adminRow = $adminResult ? $adminResult->fetch_assoc() : null;
    if (is_array($adminRow)) {
        $salu = array_merge($salu, $adminRow);
    }
} catch (Throwable $e) {
    error_log('[layout-menu admin lookup] ' . $e->getMessage());
}

function daman_admin_count(mysqli $conn, string $sql): int {
    try {
        $result = $conn->query($sql);
        if ($result && ($row = $result->fetch_assoc())) {
            return (int)($row['total'] ?? 0);
        }
    } catch (Throwable $e) {
        error_log('[layout-menu count] ' . $e->getMessage());
    }
    return 0;
}

$pending_deposits_count = daman_admin_count($conn, "SELECT COUNT(*) AS total FROM thevani WHERE sthiti=0");
$pending_withdrawals_bank_count = daman_admin_count($conn, "SELECT COUNT(*) AS total FROM hintegedukolli WHERE sthiti=0 AND madari=1");
$pending_withdrawals_upi_count = daman_admin_count($conn, "SELECT COUNT(*) AS total FROM hintegedukolli WHERE sthiti=0 AND madari=2");
$pending_withdrawals_usdt_count = daman_admin_count($conn, "SELECT COUNT(*) AS total FROM hintegedukolli WHERE sthiti=0 AND madari=3");
$pending_withdrawals_ewallet_count = daman_admin_count($conn, "SELECT COUNT(*) AS total FROM hintegedukolli WHERE sthiti=0 AND madari=4");
$total_pending_withdrawals = $pending_withdrawals_bank_count + $pending_withdrawals_upi_count + $pending_withdrawals_usdt_count + $pending_withdrawals_ewallet_count;
$pending_wheel_withdrawals_count = daman_admin_count($conn, "SELECT COUNT(*) AS total FROM mrcoder_withdrawals WHERE sthithi=0");
?>

<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
    <div class="app-brand demo">
        <a href="index.php" class="app-brand-link">
            <span class="app-brand-logo demo">
                <span style="color: var(--bs-primary)">
                    <svg width="268" height="150" viewBox="0 0 38 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M30.0944 2.22569C29.0511 0.444187 26.7508 -0.172113 24.9566 0.849138C23.1623 1.87039 22.5536 4.14247 23.5969 5.92397L30.5368 17.7743C31.5801 19.5558 33.8804 20.1721 35.6746 19.1509C37.4689 18.1296 38.0776 15.8575 37.0343 14.076L30.0944 2.22569Z"
                            fill="currentColor" />
                        <path
                            d="M30.171 2.22569C29.1277 0.444187 26.8274 -0.172113 25.0332 0.849138C23.2389 1.87039 22.6302 4.14247 23.6735 5.92397L30.6134 17.7743C31.6567 19.5558 33.957 20.1721 35.7512 19.1509C37.5455 18.1296 38.1542 15.8575 37.1109 14.076L30.171 2.22569Z"
                            fill="url(#paint0_linear_2989_100980)" fill-opacity="0.4" />
                        <path
                            d="M22.9676 2.22569C24.0109 0.444187 26.3112 -0.172113 28.1054 0.849138C29.8996 1.87039 30.5084 4.14247 29.4651 5.92397L22.5251 17.7743C21.4818 19.5558 19.1816 20.1721 17.3873 19.1509C15.5931 18.1296 14.9843 15.8575 16.0276 14.076L22.9676 2.22569Z"
                            fill="currentColor" />
                        <path
                            d="M14.9558 2.22569C13.9125 0.444187 11.6122 -0.172113 9.818 0.849138C8.02377 1.87039 7.41502 4.14247 8.45833 5.92397L15.3983 17.7743C16.4416 19.5558 18.7418 20.1721 20.5361 19.1509C22.3303 18.1296 22.9391 15.8575 21.8958 14.076L14.9558 2.22569Z"
                            fill="currentColor" />
                        <path
                            d="M14.9558 2.22569C13.9125 0.444187 11.6122 -0.172113 9.818 0.849138C8.02377 1.87039 7.41502 4.14247 8.45833 5.92397L15.3983 17.7743C16.4416 19.5558 18.7418 20.1721 20.5361 19.1509C22.3303 18.1296 22.9391 15.8575 21.8958 14.076L14.9558 2.22569Z"
                            fill="url(#paint1_linear_2989_100980)" fill-opacity="0.4" />
                        <path
                            d="M7.82901 2.22569C8.87231 0.444187 11.1726 -0.172113 12.9668 0.849138C14.7611 1.87039 15.3698 4.14247 14.3265 5.92397L7.38656 17.7743C6.34325 19.5558 4.04298 20.1721 2.24875 19.1509C0.454514 18.1296 -0.154233 15.8575 0.88907 14.076L7.82901 2.22569Z"
                            fill="currentColor" />
                        <defs>
                            <linearGradient id="paint0_linear_2989_100980" x1="5.36642" y1="0.849138" x2="10.532"
                                y2="24.104" gradientUnits="userSpaceOnUse">
                                <stop offset="0" stop-opacity="1" />
                                <stop offset="1" stop-opacity="0" />
                            </linearGradient>
                            <linearGradient id="paint1_linear_2989_100980" x1="5.19475" y1="0.849139" x2="10.3357"
                                y2="24.1155" gradientUnits="userSpaceOnUse">
                                <stop offset="0" stop-opacity="1" />
                                <stop offset="1" stop-opacity="0" />
                            </linearGradient>
                        </defs>
                    </svg>
                </span>
            </span>
            <span class="app-brand-text demo menu-text fw-semibold ms-2">SHREE WIN PRO ADMIN</span>
        </a>

        <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path
                    d="M8.47365 11.7183C8.11707 12.0749 8.11707 12.6531 8.47365 13.0097L12.071 16.607C12.4615 16.9975 12.4615 17.6305 12.071 18.021C11.6805 18.4115 11.0475 18.4115 10.657 18.021L5.83009 13.1941C5.37164 12.7356 5.37164 11.9924 5.83009 11.5339L10.657 6.707C11.0475 6.31653 11.6805 6.31653 12.071 6.707C12.4615 7.09747 12.4615 7.73053 12.071 8.121L8.47365 11.7183Z"
                    fill-opacity="0.9" />
                <path
                    d="M14.3584 11.8336C14.0654 12.1266 14.0654 12.6014 14.3584 12.8944L18.071 16.607C18.4615 16.9975 18.4615 17.6305 18.071 18.021C17.6805 18.4115 17.0475 18.4115 16.657 18.021L11.6819 13.0459C11.3053 12.6693 11.3053 12.0587 11.6819 11.6821L16.657 6.707C17.0475 6.31653 17.6805 6.31653 18.071 6.707C18.4615 7.09747 18.4615 7.73053 18.071 8.121L14.3584 11.8336Z"
                    fill-opacity="0.4" />
            </svg>
        </a>
    </div>

    <div class="menu-inner-shadow"></div>

    <ul class="menu-inner py-1">
        <!-- Dashboards -->
        <?php if ($salu['dashboard']) { ?>
            <li class="menu-item">
                <a href="index.php" class="menu-link">
                    <i class="menu-icon tf-icons ri-home-smile-line"></i>
                    <div data-i18n="Dashboards">Dashboards</div>
                </a>
            </li>


            <?php if ($salu['manageteam']) { ?>
                <li class="menu-item">
                    <a href="team-dashboard.php" class="menu-link">
                        <i class="ri-team-line"></i>
                        <div data-i18n="Team Dashboards">Team Dashboards</div>
                    </a>
                </li>
            <?php } ?>

            <?php if ($salu['games']) { ?>
                <li class="menu-header mt-5">
                    <span class="menu-header-text" data-i18n="Game Setting">Game Setting</span>
                </li>

                <!-- <li class="menu-item">-->
                <!--    <a href="winratio.php" class="menu-link">-->
                <!--        <i class="ri-line-chart-line"></i>-->
                <!--        <div data-i18n="Win ratio">Win ratio</div>-->
                <!--    </a>-->
                <!--</li>-->
                <li class="menu-item">
                    <a href="win_ratio.php" class="menu-link">
                        <i class="ri-line-chart-line"></i>
                        <div data-i18n="Set Win Ratio">Set Win Ratio</div>
                    </a>
                </li>

                <li class="menu-item">
                    <a href="sametrend.php" class="menu-link">
                        <i class="ri-rfid-line"></i>
                        <div data-i18n="Setup Same trend">Setup Same trend</div>
                    </a>
                </li>
            <?php } ?>


            <?php if ($salu['wingomanager']) { ?>
                <li class="menu-header mt-5">
                    <span class="menu-header-text" data-i18n="Games">Games</span>
                </li>


                <li class="menu-item">
                    <a href="javascript:void(0);" class="menu-link menu-toggle">
                        <i class="menu-icon tf-icons ri-gamepad-line"></i>
                        <div data-i18n="Wingo Manager">Wingo Manager</div>
                    </a>

                    <ul class="menu-sub">
                        <li class="menu-item">
                            <a href="wingo30.php" class="menu-link">
                                <div data-i18n="Wingo 30 sec">Wingo 30s</div>
                            </a>
                        </li>

                        <li class="menu-item">
                            <a href="wingo1.php" class="menu-link">
                                <div data-i18n="Wingo 1 min">Wingo 1 Min</div>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="wingo3.php" class="menu-link">
                                <div data-i18n="Wingo 3 min">Wingo 3 Min</div>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="wingo5.php" class="menu-link">
                                <div data-i18n="Wingo 5 min">Wingo 5 Min</div>
                            </a>
                        </li>
                    </ul>
                </li>
            <?php } ?>


            <?php if ($salu['k3manager']) { ?>
                <li class="menu-item">
                    <a href="javascript:void(0);" class="menu-link menu-toggle">
                        <i class="ri-remote-control-2-line"></i>
                        <div data-i18n="K3 Manager">K3 Manager</div>
                    </a>
                    <ul class="menu-sub">
                        <li class="menu-item">
                            <a href="k31.php" class="menu-link">
                                <div data-i18n="K3 1 min">K3 1 Min</div>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="k33.php" class="menu-link">
                                <div data-i18n="K3 3 min">K3 3 Min</div>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="k35.php" class="menu-link">
                                <div data-i18n="K3 5 min">K3 5 Min</div>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="k310.php" class="menu-link">
                                <div data-i18n="K3 10 min">K3 10 Min</div>
                            </a>
                        </li>
                    </ul>
                </li>
            <?php } ?>

            <?php if ($salu['5dmanager']) { ?>

                <li class="menu-item">
                    <a href="javascript:void(0);" class="menu-link menu-toggle">
                        <i class="ri-remote-control-fill"></i>
                        <div data-i18n="5D Manager">5D Manager</div>
                    </a>
                    <ul class="menu-sub">
                        <li class="menu-item">
                            <a href="5d1.php" class="menu-link">
                                <div data-i18n="5D 1 min">5D 1 Min</div>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="5d3.php" class="menu-link">
                                <div data-i18n="5D 3 min">5D 3 Min</div>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="5d5.php" class="menu-link">
                                <div data-i18n="5D 5 min">5D 5 Min</div>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="5d10.php" class="menu-link">
                                <div data-i18n="5D 10 min">5D 10 Min</div>
                            </a>
                        </li>
                    </ul>
                </li>
            <?php } ?>

            <?php if (!empty($salu['setting'])) { ?>
                <li class="menu-item">
                    <a href="javascript:void(0);" class="menu-link menu-toggle">
                        <i class="ri-exchange-dollar-line"></i>
                        <div data-i18n="TRX Wingo Manager">TRX Wingo Manager</div>
                    </a>
                    <ul class="menu-sub">
                        <li class="menu-item"><a href="ktrx.php" class="menu-link"><div>TRX Wingo 1 Min</div></a></li>
                        <li class="menu-item"><a href="ktrx3.php" class="menu-link"><div>TRX Wingo 3 Min</div></a></li>
                        <li class="menu-item"><a href="ktrx5.php" class="menu-link"><div>TRX Wingo 5 Min</div></a></li>
                        <li class="menu-item"><a href="ktrx10.php" class="menu-link"><div>TRX Wingo 10 Min</div></a></li>
                    </ul>
                </li>
                <li class="menu-item">
                    <a href="motoracing.php" class="menu-link">
                        <i class="ri-motorbike-line"></i>
                        <div data-i18n="Moto Racing Manager">Moto Racing Manager</div>
                    </a>
                </li>
            <?php } ?>

            <?php if ($salu['setting']) { ?>
                <li class="menu-item">
                    <a href="prediction_bot.php" class="menu-link">
                        <i class="ri-robot-2-line"></i>
                        <div data-i18n="Auto Bot Prediction">Auto Bot Prediction</div>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="api_panel.php" class="menu-link">
                        <i class="ri-webhook-line"></i>
                        <div data-i18n="API Games Panel">API Games Panel</div>
                    </a>
                </li>

            <?php } ?>

            <!-- Finances -->
            <?php if ($salu['setting']) { ?>
                <li class="menu-header mt-5">
                    <span class="menu-header-text" data-i18n="Finances">Finances</span>
                </li>

                <!--Payment Gateway Setting-->


                <!--<li class="menu-header mt-5">-->
                <!--    <span class="menu-header-text" data-i18n="Manage Gateway">Manage Gateway</span>-->
                <!--</li>-->
                <li class="menu-item">
                    <a href="finance_bot.php" class="menu-link">
                        <i class="ri-telegram-line"></i>
                        <div data-i18n="Finance TG Bot">Finance TG Bot</div>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="auto_payin_gateways.php" class="menu-link">
                        <i class="ri-download-2-line"></i>
                        <div data-i18n="Auto PayIn Gateways">Auto PayIn Gateways</div>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="auto_payout_gateways.php" class="menu-link">
                        <i class="ri-upload-2-line"></i>
                        <div data-i18n="Auto PayOut Gateways">Auto PayOut Gateways</div>
                    </a>
                </li>
            <?php } ?>

            <?php if ($salu['setting']) { ?>

                <li class="menu-item">
                    <a href="javascript:void(0);" class="menu-link menu-toggle">
                        <i class="ri-settings-5-line"></i>
                        <div data-i18n="Finance Setting">Finance Setting</div>
                    </a>
                    <ul class="menu-sub">
                        <li class="menu-item">
                            <a href="auto_manual.php" class="menu-link">
                                <div data-i18n="Add Auto Upi">Add Auto Upi</div>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="addupi.php" class="menu-link">
                                <div data-i18n="Add Upi">Add Upi</div>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="addupiimage.php" class="menu-link">
                                <div data-i18n="Add UPI Image">Add UPI Image</div>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="updateusdtrate.php" class="menu-link">
                                <div data-i18n="USDT Rate">USDT Rate</div>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="addusdt.php" class="menu-link">
                                <div data-i18n="Add USDT">Add USDT</div>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="addusdtimage.php" class="menu-link">
                                <div data-i18n="Add USDT Image">Add USDT Image</div>
                            </a>
                        </li>
                    </ul>
                </li>
            <?php } ?>

            <?php if ($salu['setting']) { ?>

                <li class="menu-item">
                    <a href="withdrawal_types.php" class="menu-link">
                        <i class="ri-hand-coin-line"></i>
                        <div data-i18n="Withdrawal Setting">Withdrawal Setting</div>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="deposit_types.php" class="menu-link">
                        <i class="ri-hand-coin-line"></i>
                        <div data-i18n="Deposit Setting">Deposit Setting</div>
                    </a>
                </li>

                <!--<li class="menu-item">-->
                <!--    <a href="javascript:void(0);" class="menu-link menu-toggle">-->
                <!--        <i class="menu-icon tf-icons ri-money-dollar-circle-line"></i>-->
                <!--        <div data-i18n="Finance">Deposit</div>-->
                <!--    </a>-->
                <!--    <ul class="menu-sub">-->


                <!--    </ul>-->
                <!--</li>-->
            <?php } ?>

            <?php if ($salu['finance']) { ?>

                <li class="menu-item">
                    <a href="javascript:void(0);" class="menu-link menu-toggle">
                        <i class="ri-arrow-down-circle-line"></i>
                        <div data-i18n="Deposits">Deposits</div>
                        <?php if ($pending_deposits_count > 0) { ?>
                            <span class="badge bg-danger rounded-pill ms-auto"><?php echo $pending_deposits_count; ?></span>
                        <?php } ?>
                    </a>
                    <ul class="menu-sub">
                        <li class="menu-item">
                            <a href="depositupdate.php" class="menu-link">
                                <div data-i18n="Deposit Requests">Deposit Requests</div>
                                <?php if ($pending_deposits_count > 0) { ?>
                                    <span class="badge bg-danger rounded-pill ms-auto"><?php echo $pending_deposits_count; ?></span>
                                <?php } ?>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="depositaccepted.php" class="menu-link">
                                <div data-i18n="Deposit Accepted">Deposit Accepted</div>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="depositrejected.php" class="menu-link">
                                <div data-i18n="Deposit Rejected">Deposit Rejected</div>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="todays-recharge.php" class="menu-link">
                                <div data-i18n="Today Recharge">Today Recharge</div>
                            </a>
                        </li>
                    </ul>
                </li>



                <!--Withdraw BankCard-->

                <li class="menu-item">
                    <a href="javascript:void(0);" class="menu-link menu-toggle">
                        <i class="ri-bank-line"></i>
                        <div data-i18n="Withdraw BankCard">Withdraw BankCard</div>
                        <?php if ($pending_withdrawals_bank_count > 0) { ?>
                            <span class="badge bg-danger rounded-pill ms-auto"><?php echo $pending_withdrawals_bank_count; ?></span>
                        <?php } ?>
                    </a>
                    <ul class="menu-sub">
                        <li class="menu-item">
                            <a href="withdrawapply.php" class="menu-link">
                                <div data-i18n="Withdraw Apply Bank">Withdraw Apply Bank</div>
                                <?php if ($pending_withdrawals_bank_count > 0) { ?>
                                    <span class="badge bg-danger rounded-pill ms-auto"><?php echo $pending_withdrawals_bank_count; ?></span>
                                <?php } ?>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="withdrawsent.php" class="menu-link">
                                <div data-i18n="Withdraw Sent Bank">Withdraw Sent Bank</div>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="withdrawreject.php" class="menu-link">
                                <div data-i18n="Withdraw Reject Bank">Withdraw Reject Bank</div>
                            </a>
                        </li>
                        <!--<li class="menu-item">-->
                        <!--    <a href="withdraw_pro.php" class="menu-link">-->
                        <!--        <div data-i18n="Withdraw Processing Bank">Api Withdraw Bank</div>-->
                        <!--    </a>-->
                        <!--</li>-->
                        <!--<li class="menu-item">-->
                        <!--      <a href="todays-withdraw.php" class="menu-link">-->
                        <!--          <div data-i18n="Today's Withdraw">Today Withdraw</div>-->
                        <!--      </a>-->
                        <!--  </li>-->

                    </ul>
                </li>

                <!--Withdraw USDT-->
                <li class="menu-item">
                    <a href="javascript:void(0);" class="menu-link menu-toggle">
                        <i class="menu-icon tf-icons ri-money-dollar-circle-line"></i>
                        <div data-i18n="Withdraw USDT">Withdraw USDT</div>
                        <?php if ($pending_withdrawals_usdt_count > 0) { ?>
                            <span class="badge bg-danger rounded-pill ms-auto"><?php echo $pending_withdrawals_usdt_count; ?></span>
                        <?php } ?>
                    </a>
                    <ul class="menu-sub">

                        <li class="menu-item">
                            <a href="withdrawapply-usdt.php" class="menu-link">
                                <div data-i18n="Withdraw Apply USDT">Withdraw Apply USDT</div>
                                <?php if ($pending_withdrawals_usdt_count > 0) { ?>
                                    <span class="badge bg-danger rounded-pill ms-auto"><?php echo $pending_withdrawals_usdt_count; ?></span>
                                <?php } ?>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="withdrawsend-usdt.php" class="menu-link">
                                <div data-i18n="Withdraw Sent USDT">Withdraw Sent USDT</div>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="withdrawreject-usdt.php" class="menu-link">
                                <div data-i18n="Withdraw Reject USDT">Withdraw Reject USDT</div>
                            </a>
                        </li>

                    </ul>
                </li>

                <!--WithDraw E-Wallet-->
                <li class="menu-item">
                    <a href="javascript:void(0);" class="menu-link menu-toggle">
                        <i class="ri-wallet-3-line"></i>
                        <div data-i18n="Withdraw E-Wallet">Withdraw E-Wallet</div>
                        <?php if ($pending_withdrawals_ewallet_count > 0) { ?>
                            <span class="badge bg-danger rounded-pill ms-auto"><?php echo $pending_withdrawals_ewallet_count; ?></span>
                        <?php } ?>
                    </a>
                    <ul class="menu-sub">
                        <li class="menu-item">
                            <a href="withdrawapply_ewallet.php" class="menu-link">
                                <div data-i18n="Withdraw Apply E-Wallet">Withdraw Apply E-Wallet</div>
                                <?php if ($pending_withdrawals_ewallet_count > 0) { ?>
                                    <span class="badge bg-danger rounded-pill ms-auto"><?php echo $pending_withdrawals_ewallet_count; ?></span>
                                <?php } ?>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="withdrawsent_ewallet.php" class="menu-link">
                                <div data-i18n="Withdraw Sent E-Wallet">Withdraw Sent E-Wallet</div>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="withdrawreject_ewallet.php" class="menu-link">
                                <div data-i18n="Withdraw Reject E-Wallet">Withdraw Reject E-Wallet</div>
                            </a>
                        </li>
                    </ul>
                </li>

                <!--WithDraw UPI-->
                <li class="menu-item">
                    <a href="javascript:void(0);" class="menu-link menu-toggle">
                        <i class="ri-money-rupee-circle-line"></i>
                        <div data-i18n="Withdraw UPI">Withdraw UPI</div>
                        <?php if ($pending_withdrawals_upi_count > 0) { ?>
                            <span class="badge bg-danger rounded-pill ms-auto"><?php echo $pending_withdrawals_upi_count; ?></span>
                        <?php } ?>
                    </a>
                    <ul class="menu-sub">
                        <li class="menu-item">
                            <a href="withdrawapply_upi.php" class="menu-link">
                                <div data-i18n="Withdraw Apply UPI">Withdraw Apply UPI</div>
                                <?php if ($pending_withdrawals_upi_count > 0) { ?>
                                    <span class="badge bg-danger rounded-pill ms-auto"><?php echo $pending_withdrawals_upi_count; ?></span>
                                <?php } ?>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="withdrawsent_upi.php" class="menu-link">
                                <div data-i18n="Withdraw Sent UPI">Withdraw Sent UPI</div>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="withdrawreject_upi.php" class="menu-link">
                                <div data-i18n="Withdraw Reject UPI">Withdraw Reject UPI</div>
                            </a>
                        </li>
                        <!--  <li class="menu-item">-->
                        <!--      <a href="withdraw_pro.php" class="menu-link">-->
                        <!--          <div data-i18n="Withdraw Processing UPI">Api Withdraw UPI</div>-->
                        <!--      </a>-->
                        <!--  </li>-->
                        <!--<li class="menu-item">-->
                        <!--      <a href="todays-withdraw.php" class="menu-link">-->
                        <!--          <div data-i18n="Todays Total Withdraw">Todays Total Withdraw</div>-->
                        <!--      </a>-->
                        <!--  </li>-->

                    </ul>
                </li>

                <!--WithDraw Invited Wheel-->
                <li class="menu-item">
                    <a href="javascript:void(0);" class="menu-link menu-toggle">
                        <i class="ri-loader-2-line"></i>
                        <div data-i18n="Withdraw Invite Wheel">Withdraw Wheel</div>
                        <?php if ($pending_wheel_withdrawals_count > 0) { ?>
                            <span class="badge bg-danger rounded-pill ms-auto"><?php echo $pending_wheel_withdrawals_count; ?></span>
                        <?php } ?>
                    </a>
                    <ul class="menu-sub">
                        <li class="menu-item">
                            <a href="withdrawapply_wheel.php" class="menu-link">
                                <div data-i18n="Withdraw Apply Wheel">Withdraw Apply Wheel</div>
                                <?php if ($pending_wheel_withdrawals_count > 0) { ?>
                                    <span class="badge bg-danger rounded-pill ms-auto"><?php echo $pending_wheel_withdrawals_count; ?></span>
                                <?php } ?>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="withdrawsent_wheel.php" class="menu-link">
                                <div data-i18n="Withdraw Sent Wheel">Withdraw Sent Wheel</div>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="withdrawreject_wheel.php" class="menu-link">
                                <div data-i18n="Withdraw Reject Wheel">Withdraw Reject Wheel</div>
                            </a>
                        </li>
                    </ul>
                </li>


            <?php } ?>

            <?php if ($salu['admins']) { ?>

                <li class="menu-header mt-5">
                    <span class="menu-header-text" data-i18n="Admin">Admin</span>
                </li>
                <li class="menu-item">
                    <a href="javascript:void(0);" class="menu-link menu-toggle">
                        <i class="ri-admin-line"></i>
                        <div data-i18n="Admin Manager">Admin Manager</div>
                    </a>
                    <ul class="menu-sub">
                        <li class="menu-item">
                            <a href="updateadminpassword.php" class="menu-link">
                                <div data-i18n="Admin Password">Admin Password</div>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="add_admin.php" class="menu-link">
                                <div data-i18n="Add Admin">Add Admin</div>
                            </a>
                        </li>
                        <!--<li class="menu-item">-->
                        <!--    <a href="admin_roles.php" class="menu-link">-->
                        <!--        <div data-i18n="Admin Roles">Admin Roles</div>-->
                        <!--    </a>-->
                        <!--</li>-->

                    </ul>
                </li>

            <?php } ?>




            <!-- Manage Users -->
            <?php if ($salu['manageusers']) { ?>
                <li class="menu-header mt-5">
                    <span class="menu-header-text" data-i18n="Manage Users">Manage Users</span>
                </li>
                <li class="menu-item">
                    <a href="javascript:void(0);" class="menu-link menu-toggle">
                        <i class="ri-group-line"></i>
                        <div data-i18n="Manage Users">Manage Users</div>
                    </a>
                    <ul class="menu-sub">
                        <li class="menu-item">
                            <a href="bonusmanage.php" class="menu-link">
                                <div data-i18n="Bonus Manage">Bonus Manage</div>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="balance_deduction.php" class="menu-link">
                                <div data-i18n="Balance Deduction">Balance Deduction</div>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="full_subcoordinate.php" class="menu-link">
                                <div data-i18n="Sub Co-ordinate Data">Sub Co-ordinate Data</div>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="manage_userwallet.php" class="menu-link">
                                <div data-i18n="Update User Wallet">Reset User Wallet</div>
                            </a>
                        </li>

                        <li class="menu-item">
                            <a href="illegal_bet_list.php" class="menu-link">
                                <i class="menu-icon tf-icons ri-user-3-line"></i>
                                <div data-i18n="Illegal Bet List">Illegal Bet List</div>
                            </a>
                        </li>

                        <li class="menu-item">
                            <a href="crossbet_list.php" class="menu-link">
                                <i class="menu-icon tf-icons ri-user-3-line"></i>
                                <div data-i18n="Cross Bet Listt">Cross Bet List</div>
                            </a>
                        </li>

                        <li class="menu-item">
                            <a href="ipsearch.php" class="menu-link">
                                <i class="menu-icon tf-icons ri-user-star-line"></i>
                                <div data-i18n="Multiple IP">Multiple IP</div>
                            </a>
                        </li>


                        <li class="menu-item">
                            <a href="restrictuser.php" class="menu-link">
                                <i class="menu-icon tf-icons ri-user-star-line"></i>
                                <div data-i18n="Bet restrict">Bet restrict</div>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="users.php" class="menu-link">
                                <i class="menu-icon tf-icons ri-user-star-line"></i>
                                <div data-i18n="Manage Users">Manage Users</div>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="demo_add.php" class="menu-link">
                                <i class="menu-icon tf-icons ri-user-star-line"></i>
                                <div data-i18n="Add Demo User">Add Demo User</div>
                            </a>
                        </li>

                        <!--<li class="menu-item">-->
                        <!--      <a href="restrictlist.php" class="menu-link">-->
                        <!--          <i class="menu-icon tf-icons ri-user-star-line"></i>-->
                        <!--          <div data-i18n="Restricted Users List">Restricted Users List</div>-->
                        <!--      </a>-->
                        <!--  </li>-->

                    </ul>
                </li>
            <?php } ?>
            
            

            <?php if ($salu['setting']) { ?>

                <li class="menu-item">
                    <a href="javascript:void(0);" class="menu-link menu-toggle">
                        <i class="ri-wallet-2-line"></i>
                        <div data-i18n="Modify Wallet Address">Modify Wallet Address</div>
                    </a>
                    <ul class="menu-sub">

                        <li class="menu-item">
                            <a href="manage_bankcard.php" class="menu-link">
                                <div data-i18n="Modify Bank">Modify Bank</div>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="manage_usdt_address.php" class="menu-link">
                                <div data-i18n="Modify USDT">Modify USDT</div>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="manage_ewallet_address.php" class="menu-link">
                                <div data-i18n="Modify E-Wallet">Modify E-Wallet</div>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="manage_upi_address.php" class="menu-link">
                                <div data-i18n="Modify UPI">Modify UPI</div>
                            </a>
                        </li>

                    </ul>
                </li>
            <?php } ?>


            <?php if ($salu['manage_game']) { ?>
                <!-- Web Setting -->
                <li class="menu-header mt-5">
                    <span class="menu-header-text" data-i18n="MANAGE GAME">MANAGE GAME</span>
                </li>

                <li class="menu-item">
                    <a href="javascript:void(0);" class="menu-link menu-toggle">
                        <i class="ri-settings-4-line"></i>
                        <div data-i18n="Website Settings">Website Settings</div>
                    </a>
                    <ul class="menu-sub">
                        <li class="menu-item">
                            <a href="web_setting.php" class="menu-link">
                                <div data-i18n="Web Setting">Web Setting</div>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="update_banner.php" class="menu-link">
                                <div data-i18n="Update Banner">Update Banner</div>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="chnagebann.php" class="menu-link">
                                <div data-i18n="Chnage Banner">Chnage Banner</div>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="update_activitybanner.php" class="menu-link">
                                <div data-i18n="Update Activity Banners">Update Activity Banners</div>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="activity_detail_banner.php" class="menu-link">
                                <div data-i18n="Activity Detail Banners">Activity Detail Banners</div>
                            </a>
                        </li>

                        <li class="menu-item">
                            <a href="welcome_message.php" class="menu-link">
                                <div data-i18n="Welcome Message">Welcome Message</div>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="notification_message.php" class="menu-link">
                                <div data-i18n="Manage Notification">Manage Notification</div>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="manage_lang_currency.php" class="menu-link">
                                <div data-i18n="Currency & Language">Currency & Language</div>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="manage_country_codes.php" class="menu-link">
                                <div data-i18n="Country Codes">Country Codes</div>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="manage_banks.php" class="menu-link">
                                <div data-i18n="Bank Lists">Country Codes</div>
                            </a>
                        </li>
                        <!-- <li class="menu-item">-->
                        <!--    <a href="firstdep.php" class="menu-link">-->
                        <!--        <div data-i18n="First Deposit">First Deposit</div>-->
                        <!--    </a>-->
                        <!--</li>-->

                        <!--<li class="menu-item">-->
                        <!--        <a href="webcolors.php" class="menu-link">-->
                        <!--            <div data-i18n="Chnage Web Colours">Chnage Web Colours</div>-->
                        <!--        </a>-->
                        <!--</li>-->
                        <!--<li class="menu-item">-->
                        <!--        <a href="updateadminpassword.php" class="menu-link">-->
                        <!--            <div data-i18n="Admin Password">Admin Password</div>-->
                        <!--        </a>-->
                        <!--    </li>-->
                        <li class="menu-item">
                            <a href="maintainance.php" class="menu-link">
                                <div data-i18n="Maintainance">Maintainance</div>
                            </a>
                        </li>
                        <!--<li class="menu-item">-->
                        <!--    <a href="telegram.php" class="menu-link">-->
                        <!--        <div data-i18n="Telegram">Telegram</div>-->
                        <!--    </a>-->
                        <!--</li>-->


                    </ul>
                </li>

                <li class="menu-item">
                    <a href="javascript:void(0);" class="menu-link menu-toggle">
                        <i class="ri-settings-4-line"></i>
                        <div data-i18n="Game Settings">Game Settings</div>
                    </a>
                    <ul class="menu-sub">
                        <!--<li class="menu-item">-->
                        <!--    <a href="home_setting.php" class="menu-link">-->
                        <!--        <div data-i18n="Home Setting">Home Setting</div>-->
                        <!--    </a>-->
                        <!--</li>-->
                        <li class="menu-item">
                            <a href="update_comission.php" class="menu-link">
                                <div data-i18n="Comission Setting">Comission Setting</div>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="update_firstdepositbonus.php" class="menu-link">
                                <div data-i18n="First Deposit Bonus">First Deposit Bonus</div>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="partner_reward.php" class="menu-link">
                                <div data-i18n="Partner Reward">Partner Reward</div>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="update_invitebonus.php" class="menu-link">
                                <div data-i18n="Update Invite Bonus">Update Invite Bonus</div>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="manage_paymentgateway.php" class="menu-link">
                                <div data-i18n="Payment Gateway Setting">Payment Gateway Setting</div>
                            </a>
                        </li>
                        <!--<li class="menu-item">-->
                        <!--     <a href="active_setting.php" class="menu-link">-->
                        <!--         <div data-i18n="Active Setting">Active Setting</div>-->
                        <!--     </a>-->
                        <!-- </li>-->
                        <!--  <li class="menu-item">-->
                        <!--     <a href="loaded_setting.php" class="menu-link">-->
                        <!--         <div data-i18n="Loaded Setting">Loaded Setting</div>-->
                        <!--     </a>-->
                        <!-- </li>-->
                        <li class="menu-item">
                            <a href="manual_game_setting.php" class="menu-link">
                                <div data-i18n="Manual Game Setting">Manual Game Setting</div>
                            </a>
                        </li>

                    </ul>

                <li class="menu-item">
                    <a href="adevtanover.php" class="menu-link">
                        <i class="ri-gift-2-line"></i>
                        <div data-i18n="Tanover Add/Revove">Tanover Add/Revove</div>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="apasscheak.php" class="menu-link">
                        <i class="ri-gift-2-line"></i>
                        <div data-i18n="User Accunt cheak">User Accunt cheak</div>
                    </a>
                </li>
                 <li class="menu-item">
                    <a href="agent-summary.php" class="menu-link">
                        <i class="ri-gift-2-line"></i>
                        <div data-i18n="Daily Salary cheak">Daily Salary cheak </div>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="adevrakibdakiextra.php" class="menu-link">
                        <i class="ri-gift-2-line"></i>
                        <div data-i18n="Daily Extra bonus cheak">Daily Extra bonus cheak </div>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="adevrakibweekyeb.php" class="menu-link">
                        <i class="ri-gift-2-line"></i>
                        <div data-i18n="Weelye  bonus cheak">Weelye  bonus cheak </div>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="adevrakib3x.php" class="menu-link">
                        <i class="ri-gift-2-line"></i>
                        <div data-i18n="3xdp  bonus cheak "3xdp  bonus cheak </div>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="adevrakibdawn.php" class="menu-link">
                        <i class="ri-gift-2-line"></i>
                        <div data-i18n="dawnline edit  "dawnline edit </div>
                    </a>
                </li>

<li class="menu-item">
                    <a href="generate_gift_code.php" class="menu-link">
                        <i class="ri-gift-2-line"></i>
                        <div data-i18n="Gift Code">Gift Code</div>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="red_env_rqst.php" class="menu-link">
                        <i class="ri-gift-2-line"></i>
                        <div data-i18n="Red Env. Request">Red Env. Request</div>
                    </a>
                </li>

                <li class="menu-item">
                    <a href="landingpageedit.php" class="menu-link">
                        <i class="ri-file-settings-line"></i>
                        <div data-i18n="Update Landing Page">Update Landing Page</div>
                    </a>
                </li>
            <?php } ?>


            <?php if ($salu['manage_game']) { ?>

                <!--Marketing-->

                <li class="menu-item">
                    <a href="javascript:void(0);" class="menu-link menu-toggle">
                        <i class="ri-megaphone-line"></i>
                        <div data-i18n="Marketing">Marketing</div>
                    </a>
                    <ul class="menu-sub">
                        <li class="menu-item">
                            <a href="user_subtree.php" class="menu-link">
                                <div data-i18n="User Sub-Tree">User Sub-Tree</div>
                            </a>
                        </li>

                        <li class="menu-item">
                            <a href="users_trunover.php" class="menu-link">
                                <div data-i18n="Turn Over">Users Turn Over</div>
                            </a>
                        </li>

                        <li class="menu-item">
                            <a href="manage_turntable.php" class="menu-link">
                                <div data-i18n="Manage TurnTable">Manage TurnTable</div>
                            </a>
                        </li>
                        <!--<li class="menu-item">-->
                        <!--    <a href="manage_invitewheel.php" class="menu-link">-->
                        <!--        <div data-i18n="Manage InviteWheel">Manage InviteWheel</div>-->
                        <!--    </a>-->
                        <!--</li>-->
                        <li class="menu-item">
                            <a href="manage_invite_wheel.php" class="menu-link">
                                <div data-i18n="Manage InviteWheel">Manage InviteWheel</div>
                            </a>
                        </li>

                        <li class="menu-item">
                            <a href="manage_dailysignin.php" class="menu-link">
                                <div data-i18n="Daily Sign In Bonus">Daily Sign In Bonus</div>
                            </a>
                        </li>

                        <!-- <li class="menu-item">-->
                        <!--    <a href="firstdep.php" class="menu-link">-->
                        <!--        <div data-i18n="First Deposit">First Deposit</div>-->
                        <!--    </a>-->
                        <!--</li>-->

                        <li class="menu-item">
                            <a href="cloud_oss_contents.php" class="menu-link">
                                <div data-i18n="Cloud Image Storage">Cloud Image Storage</div>
                            </a>
                        </li>

                        <li class="menu-item">
                            <a href="manage_ranks.php" class="menu-link">
                                <div data-i18n="User Ranks">User Ranks</div>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="users_profit_rank.php" class="menu-link">
                                <div data-i18n="Daily Profit Rank">Daily Profit Rank</div>
                            </a>
                        </li>
                    </ul>
                </li>

            <?php } ?>



            <?php if ($salu['manage_agent']) { ?>

                <!-- Others -->
                <li class="menu-header mt-5">
                    <span class="menu-header-text" data-i18n="Agent Settings">Agent Settings</span>
                </li>

                <li class="menu-item">
                    <a href="javascript:void(0);" class="menu-link menu-toggle">
                        <i class="ri-settings-4-line"></i>
                        <div data-i18n="Agent Settings">Agent Settings</div>
                    </a>
                    <ul class="menu-sub">

                        <!--<li class="menu-item">-->
                        <!--     <a href="agents_management.php" class="menu-link">-->
                        <!--         <div data-i18n="Add Agent">Add Agent</div>-->
                        <!--     </a>-->
                        <!-- </li>-->

                        <li class="menu-item">
                            <a href="manage_agents.php" class="menu-link">
                                <div data-i18n="Agent Management">Agent Management</div>
                            </a>
                        </li>

                        <li class="menu-item">
                            <a href="agent_recharges.php" class="menu-link">
                                <div data-i18n="Agent Recharges">Agent Recharges</div>
                            </a>
                        </li>

                        <li class="menu-item">
                            <a href="downlinedet.php" class="menu-link">
                                <div data-i18n="Downline Details">Downline Details</div>
                            </a>
                        </li>

                        <li class="menu-item">
                            <a href="userturn.php" class="menu-link">
                                <div data-i18n="Refar Tree">Refar Tree</div>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="menu-item">
                    <a href="manage-salary.php" class="menu-link">
                        <i class="ri-mail-add-line"></i>
                        <div data-i18n="Manage Salary">Manage Salary</div>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="addteacher.php" class="menu-link">
                        <div data-i18n="Add Teacher" <i class="ri-presentation-line"></i> >Add Teacher</div>
                    </a>
                </li>

            <?php } ?>



            <!--<?php if ($salu['manage_agent']) { ?>-->

            <!--    <li class="menu-header mt-5">-->
            <!--        <span class="menu-header-text" data-i18n="Manage Agents">Manage Agents</span>-->
            <!--    </li>-->
            <!--    <li class="menu-item">-->
            <!--        <a href="add-agent.php" class="menu-link">-->
            <!--            <i class="menu-icon tf-icons ri-user-add-line"></i>-->
            <!--            <div data-i18n="Add Agent">Add Agent</div>-->
            <!--        </a>-->
            <!--    </li>-->
            <!--    <li class="menu-item">-->
            <!--        <a href="agent-user.php" class="menu-link">-->
            <!--            <i class="menu-icon tf-icons ri-user-star-line"></i>-->
            <!--            <div data-i18n="Agent Data">Agent Data</div>-->
            <!--        </a>-->
            <!--    </li>-->

            <!--    <li class="menu-item">-->
            <!--        <a href="manage-salary.php" class="menu-link">-->
            <!--            <i class="menu-icon tf-icons ri-money-rupee-circle-line"></i>-->
            <!--            <div data-i18n="Manage Salary">Manage Salary</div>-->
            <!--        </a>-->
            <!--    </li>-->
            <!--<?php } ?>-->



            <!--<?php if ($salu['assign_bonus']) { ?>-->
            <!--<li class="menu-header mt-5">-->
            <!--        <span class="menu-header-text" data-i18n="Assign Bonus">Assign Bonus</span>-->
            <!--    </li>-->

            <!-- <li class="menu-item">-->
            <!--    <a href="userfirstdeposit.php" class="menu-link">-->
            <!--        <i class="menu-icon tf-icons ri-user-settings-line"></i>-->
            <!--        <div data-i18n="Member Deposit Bonus">Member Deposit Bonus</div>-->
            <!--    </a>-->
            <!--</li>-->

            <!-- <li class="menu-item">-->
            <!--    <a href="firstrechargebonus.php" class="menu-link">-->
            <!--    <i class="menu-icon tf-icons ri-money-rupee-circle-line"></i>-->
            <!--    <div data-i18n="Agent Refferal Bonus">Agent Refferal Bonus</div>-->
            <!--    </a>-->
            <!--</li>-->

            <!--<li class="menu-item">-->
            <!--   <a href="bonusmanage.php" class="menu-link">-->
            <!-- <i class="menu-icon tf-icons ri-money-rupee-circle-line"></i>-->
            <!--        <div data-i18n="Bonus Manage">Bonus Manage</div>-->
            <!--    </a>-->
            <!--</li>-->

            <!--<li class="menu-item">-->
            <!--    <a href="generategiftcode.php" class="menu-link">-->
            <!--    <i class="ri-gift-line"></i>-->
            <!--        <div data-i18n="Gift Code">Gift Code</div>-->
            <!--    </a>-->
            <!--</li>-->
            <!--<?php } ?>-->


            <?php if ($salu['support']) { ?>

                <li class="menu-header mt-5">
                    <span class="menu-header-text" data-i18n="Support">Support</span>
                </li>

                <li class="menu-item">
                    <a href="javascript:void(0);" class="menu-link menu-toggle">
                        <i class="menu-icon tf-icons ri-customer-service-2-line"></i>
                        <div data-i18n="Customer Service">Customer Service</div>
                    </a>

                    <ul class="menu-sub">
                        <li class="menu-item">
                            <a href="aviatorluckybonus.php" class="menu-link">
                                <div data-i18n="Aviator Lucky Bonus">Aviator Lucky Bonus</div>
                            </a>
                        </li>

                        <li class="menu-item">
                            <a href="bankaccountmodify.php" class="menu-link">
                                <div data-i18n="Bank Account Delete">Bank Account Delete</div>
                            </a>
                        </li>

                        <li class="menu-item">
                            <a href="wingowinstreak.php" class="menu-link">
                                <div data-i18n="Win Streak Bonus">Win Streak Bonus</div>
                            </a>
                        </li>

                        <li class="menu-item">
                            <a href="depositproblem.php" class="menu-link">
                                <div data-i18n="Deposit Problem">Deposit Problem</div>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="withdrawproblem.php" class="menu-link">
                                <div data-i18n="Withdrawal Problem">Withdrawal Problem</div>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="ifscmodification.php" class="menu-link">
                                <div data-i18n="IFSC Modification">IFSC Modification</div>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="bankmodification.php" class="menu-link">
                                <div data-i18n="Bank Modification">Bank Modification</div>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="gameproblem.php" class="menu-link">
                                <div data-i18n="Game Problem">Game Problem</div>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="usdtverification.php" class="menu-link">
                                <div data-i18n="USDT Verification">USDT Verification</div>
                            </a>
                        </li>
                    </ul>
                </li>

                <!--<li class="menu-item">-->
                <!--    <a href="javascript:void(0);" class="menu-link menu-toggle">-->
                <!--        <i class="menu-icon tf-icons ri-customer-service-2-line"></i>-->
                <!--        <div data-i18n="Support">Support</div>-->
                <!--    </a>-->
                <!--    <ul class="menu-sub">-->
                <!--        <li class="menu-item">-->
                <!--            <a href="depositproblem.php" class="menu-link">-->
                <!--                <div data-i18n="Deposit Problem">Deposit Problem</div>-->
                <!--            </a>-->
                <!--        </li>-->
                <!--        <li class="menu-item">-->
                <!--            <a href="withdrawproblem.php" class="menu-link">-->
                <!--                <div data-i18n="Withdrawal Problem">Withdrawal Problem</div>-->
                <!--            </a>-->
                <!--        </li>-->
                <!--        <li class="menu-item">-->
                <!--            <a href="ifscmodification.php" class="menu-link">-->
                <!--                <div data-i18n="IFSC Modification">IFSC Modification</div>-->
                <!--            </a>-->
                <!--        </li>-->
                <!--        <li class="menu-item">-->
                <!--            <a href="bankmodification.php" class="menu-link">-->
                <!--                <div data-i18n="Bank Modification">Bank Modification</div>-->
                <!--            </a>-->
                <!--        </li>-->
                <!--        <li class="menu-item">-->
                <!--            <a href="gameproblem.php" class="menu-link">-->
                <!--                <div data-i18n="Game Problem">Game Problem</div>-->
                <!--            </a>-->
                <!--        </li>-->
                <!--        <li class="menu-item">-->
                <!--            <a href="usdtverification.php" class="menu-link">-->
                <!--                <div data-i18n="USDT Verification">USDT Verification</div>-->
                <!--            </a>-->
                <!--        </li>-->
                <!--    </ul>-->
                <!--</li>-->

                <li class="menu-item">
                    <a href="users_feedback.php" class="menu-link">
                        <i class="ri-feedback-line"></i>
                        <div data-i18n="Users Feedback">Users Feedback</div>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="live_chat.php" class="menu-link">
                        <i class="ri-message-2-line"></i>
                        <div data-i18n="Live Chat">Live Chat</div>
                    </a>
                </li>

                <li class="menu-item">
                    <a href="telegram.php" class="menu-link">
                        <i class="ri-telegram-line"></i>
                        <div data-i18n="Telegram">Telegram</div>
                    </a>
                </li>
            <?php } ?>



            <!--<?php if ($salu['users_pan']) { ?>-->
            <!--  <li class="menu-header mt-5">-->
            <!--        <span class="menu-header-text" data-i18n="Users Penalty">Users Penalty</span>-->
            <!--    </li>-->

            <!--  <li class="menu-item">-->
            <!--        <a href="illegal_bet_list.php" class="menu-link">-->
            <!--            <i class="menu-icon tf-icons ri-user-3-line"></i>-->
            <!--            <div data-i18n="Illegal Bet List">Illegal Bet List</div>-->
            <!--        </a>-->
            <!--    </li>-->

            <!--   <li class="menu-item">-->
            <!--        <a href="crossbet_list.php" class="menu-link">-->
            <!--            <i class="menu-icon tf-icons ri-user-3-line"></i>-->
            <!--            <div data-i18n="Cross Bet Listt">Cross Bet List</div>-->
            <!--        </a>-->
            <!--    </li>-->

            <!--  <li class="menu-item">-->
            <!--        <a href="ipsearch.php" class="menu-link">-->
            <!--            <i class="menu-icon tf-icons ri-user-star-line"></i>-->
            <!--            <div data-i18n="Multiple IP">Multiple IP</div>-->
            <!--        </a>-->
            <!--    </li>-->


            <!--   <li class="menu-item">-->
            <!--        <a href="restrictuser.php" class="menu-link">-->
            <!--            <i class="menu-icon tf-icons ri-user-star-line"></i>-->
            <!--            <div data-i18n="Bet restrict">Bet restrict</div>-->
            <!--        </a>-->
            <!--    </li>-->

            <!--  <li class="menu-item">-->
            <!--        <a href="restrictlist.php" class="menu-link">-->
            <!--            <i class="menu-icon tf-icons ri-user-star-line"></i>-->
            <!--            <div data-i18n="Restricted Users List">Restricted Users List</div>-->
            <!--        </a>-->
            <!--    </li>-->
            <!--<?php } ?>-->

            <!--<?php if ($salu['other']) { ?>-->
            <!-- Others -->
            <!--    <li class="menu-header mt-5">-->
            <!--        <span class="menu-header-text" data-i18n="Others">Others</span>-->
            <!--    </li>-->

            <!--    <li class="menu-item">-->
            <!--        <a href="javascript:void(0);" class="menu-link menu-toggle">-->
            <!--            <i class="menu-icon tf-icons ri-settings-4-line"></i>-->
            <!--            <div data-i18n="Manage Game">Manage Game</div>-->
            <!--        </a>-->
            <!--        <ul class="menu-sub">-->

            <!--            <li class="menu-item">-->
            <!--                <a href="updateadminpassword.php" class="menu-link">-->
            <!--                    <div data-i18n="Admin Password">Admin Password</div>-->
            <!--                </a>-->
            <!--            </li>-->
            <!--            <li class="menu-item">-->
            <!--                <a href="maintainance.php" class="menu-link">-->
            <!--                    <div data-i18n="Maintainance">Maintainance</div>-->
            <!--                </a>-->
            <!--            </li>-->
            <!--            <li class="menu-item">-->
            <!--                <a href="telegram.php" class="menu-link">-->
            <!--                    <div data-i18n="Telegram">Telegram</div>-->
            <!--                </a>-->
            <!--            </li>-->
            <!--            <li class="menu-item">-->
            <!--                <a href="addadmin.php" class="menu-link">-->
            <!--                    <div data-i18n="Add Admin">Add Admin</div>-->
            <!--                </a>-->
            <!--            </li>-->
            <!--        </ul>-->
            <!--    </li>-->

            <!--<?php } ?>  -->


            <!--<li class="menu-item">-->
            <!--    <a href="landingpageedit.php" class="menu-link">-->
            <!--        <i class="menu-icon tf-icons ri-user-smile-line"></i>-->
            <!--        <div data-i18n="Update Landing Page">Update Landing Page</div>-->
            <!--    </a>-->
            <!--</li>-->


            <li class="menu-item">
                <a href="https://dreambd99.pro.bd" class="menu-link">
                    <i class="menu-icon tf-icons ri-home-smile-line"></i>
                    <div data-i18n="Go to Website">Go to Website</div>
                </a>
            </li>
    </ul>

<?php } ?>

</aside>
