<?php
declare(strict_types=1);
$currentTab = (string)($_GET['tab'] ?? 'dashboard');

$wingoTabs = ['wingo_30s', 'wingo_1m', 'wingo_3m', 'wingo_5m'];
$k3Tabs = ['k3_1m', 'k3_3m', 'k3_5m', 'k3_10m'];
$d5Tabs = ['d5_1m', 'd5_3m', 'd5_5m', 'd5_10m'];
$financeTabs = ['add_upi', 'usdt_rate', 'add_usdt', 'add_upi_image', 'add_usdt_image', 'recharges', 'withdrawals', 'upi_withdraw', 'withdraw_sent', 'withdraw_reject'];
$supportTabs = ['support_deposit', 'support_withdraw', 'support_ifsc', 'support_bank', 'support_game'];
$adminManageTabs = ['bonus_manage', 'admin_password', 'check_same_ip', 'site_maintenance', 'banned_users', 'users', 'daily_salary', 'gift_code', 'add_admin', 'demo_user', 'agent_user'];
?>

<style>
.sidebar-submenu {
    padding-left: 28px;
    display: flex;
    flex-direction: column;
    gap: 2px;
    margin-top: 2px;
    margin-bottom: 6px;
}
.sidebar-sub-link {
    display: flex;
    align-items: center;
    padding: 8px 14px;
    color: var(--text-secondary);
    text-decoration: none;
    border-radius: 10px;
    font-size: 13px;
    transition: var(--transition-smooth);
}
.sidebar-sub-link:hover {
    background: rgba(255, 255, 255, 0.04);
    color: var(--text-primary);
    transform: translateX(2px);
}
.sidebar-sub-link.active {
    color: var(--accent-gold);
    font-weight: 600;
    background: rgba(255, 255, 255, 0.03);
    border-left: 2px solid var(--accent-gold);
    border-radius: 0 10px 10px 0;
}
.sidebar-dropdown-toggle {
    cursor: pointer;
}
.sidebar-dropdown-toggle::after {
    content: "\f107";
    font-family: "Font Awesome 6 Free";
    font-weight: 900;
    margin-left: auto;
    font-size: 14px;
    transition: transform 0.25s ease;
}
.sidebar-dropdown-toggle[aria-expanded="true"]::after {
    transform: rotate(180deg);
}
</style>

<aside class="app-sidebar" id="app-sidebar">
    <div class="d-flex d-lg-none justify-content-between align-items-center pb-3 mb-3 border-bottom" style="border-color: rgba(255,255,255,0.1) !important;">
        <div class="fw-bold text-gold fs-5"><i class="fas fa-bars me-2"></i> Menu</div>
        <button type="button" class="btn btn-sm text-secondary" id="sidebar-close-btn" onclick="closeMobileSidebar()" style="background: rgba(255,255,255,0.08); border-radius: 8px; width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center;">
            <i class="fas fa-times fs-5 text-white"></i>
        </button>
    </div>

    <a class="sidebar-nav-link <?php echo $currentTab === 'dashboard' ? 'active' : ''; ?>" href="/admin/?tab=dashboard">
        <i class="fas fa-chart-line"></i>
        <span>Dashboard</span>
    </a>

    <!-- WINGO MANAGER -->
    <?php $wingoOpen = in_array($currentTab, $wingoTabs, true); ?>
    <div class="sidebar-dropdown">
        <a class="sidebar-nav-link sidebar-dropdown-toggle <?php echo $wingoOpen ? 'active' : ''; ?>" 
           data-bs-toggle="collapse" href="#wingoCollapse" role="button" aria-expanded="<?php echo $wingoOpen ? 'true' : 'false'; ?>">
            <i class="fas fa-gamepad text-gold"></i>
            <span>WinGo Manager</span>
        </a>
        <div class="collapse <?php echo $wingoOpen ? 'show' : ''; ?>" id="wingoCollapse">
            <div class="sidebar-submenu">
                <a class="sidebar-sub-link <?php echo $currentTab === 'wingo_30s' ? 'active' : ''; ?>" href="/admin/?tab=wingo_30s">WinGo 30 Sec</a>
                <a class="sidebar-sub-link <?php echo $currentTab === 'wingo_1m' ? 'active' : ''; ?>" href="/admin/?tab=wingo_1m">WinGo 1 Min</a>
                <a class="sidebar-sub-link <?php echo $currentTab === 'wingo_3m' ? 'active' : ''; ?>" href="/admin/?tab=wingo_3m">WinGo 3 Min</a>
                <a class="sidebar-sub-link <?php echo $currentTab === 'wingo_5m' ? 'active' : ''; ?>" href="/admin/?tab=wingo_5m">WinGo 5 Min</a>
            </div>
        </div>
    </div>

    <!-- K3 MANAGER -->
    <?php $k3Open = in_array($currentTab, $k3Tabs, true); ?>
    <div class="sidebar-dropdown">
        <a class="sidebar-nav-link sidebar-dropdown-toggle <?php echo $k3Open ? 'active' : ''; ?>" 
           data-bs-toggle="collapse" href="#k3Collapse" role="button" aria-expanded="<?php echo $k3Open ? 'true' : 'false'; ?>">
            <i class="fas fa-dice text-blue"></i>
            <span>K3 Manager</span>
        </a>
        <div class="collapse <?php echo $k3Open ? 'show' : ''; ?>" id="k3Collapse">
            <div class="sidebar-submenu">
                <a class="sidebar-sub-link <?php echo $currentTab === 'k3_1m' ? 'active' : ''; ?>" href="/admin/?tab=k3_1m">K3 1 Min</a>
                <a class="sidebar-sub-link <?php echo $currentTab === 'k3_3m' ? 'active' : ''; ?>" href="/admin/?tab=k3_3m">K3 3 Min</a>
                <a class="sidebar-sub-link <?php echo $currentTab === 'k3_5m' ? 'active' : ''; ?>" href="/admin/?tab=k3_5m">K3 5 Min</a>
                <a class="sidebar-sub-link <?php echo $currentTab === 'k3_10m' ? 'active' : ''; ?>" href="/admin/?tab=k3_10m">K3 10 Min</a>
            </div>
        </div>
    </div>

    <!-- 5D MANAGER -->
    <?php $d5Open = in_array($currentTab, $d5Tabs, true); ?>
    <div class="sidebar-dropdown">
        <a class="sidebar-nav-link sidebar-dropdown-toggle <?php echo $d5Open ? 'active' : ''; ?>" 
           data-bs-toggle="collapse" href="#d5Collapse" role="button" aria-expanded="<?php echo $d5Open ? 'true' : 'false'; ?>">
            <i class="fas fa-gem text-purple"></i>
            <span>5D Manager</span>
        </a>
        <div class="collapse <?php echo $d5Open ? 'show' : ''; ?>" id="d5Collapse">
            <div class="sidebar-submenu">
                <a class="sidebar-sub-link <?php echo $currentTab === 'd5_1m' ? 'active' : ''; ?>" href="/admin/?tab=d5_1m">5D 1 Min</a>
                <a class="sidebar-sub-link <?php echo $currentTab === 'd5_3m' ? 'active' : ''; ?>" href="/admin/?tab=d5_3m">5D 3 Min</a>
                <a class="sidebar-sub-link <?php echo $currentTab === 'd5_5m' ? 'active' : ''; ?>" href="/admin/?tab=d5_5m">5D 5 Min</a>
                <a class="sidebar-sub-link <?php echo $currentTab === 'd5_10m' ? 'active' : ''; ?>" href="/admin/?tab=d5_10m">5D 10 Min</a>
            </div>
        </div>
    </div>

    <!-- FINANCE -->
    <?php $financeOpen = in_array($currentTab, $financeTabs, true); ?>
    <div class="sidebar-dropdown">
        <a class="sidebar-nav-link sidebar-dropdown-toggle <?php echo $financeOpen ? 'active' : ''; ?>" 
           data-bs-toggle="collapse" href="#financeCollapse" role="button" aria-expanded="<?php echo $financeOpen ? 'true' : 'false'; ?>">
            <i class="fas fa-wallet text-green"></i>
            <span>Finance</span>
        </a>
        <div class="collapse <?php echo $financeOpen ? 'show' : ''; ?>" id="financeCollapse">
            <div class="sidebar-submenu">
                <a class="sidebar-sub-link <?php echo $currentTab === 'add_upi' ? 'active' : ''; ?>" href="/admin/?tab=add_upi">Add UPI</a>
                <a class="sidebar-sub-link <?php echo $currentTab === 'usdt_rate' ? 'active' : ''; ?>" href="/admin/?tab=usdt_rate">USDT Rate</a>
                <a class="sidebar-sub-link <?php echo $currentTab === 'add_usdt' ? 'active' : ''; ?>" href="/admin/?tab=add_usdt">Add USDT</a>
                <a class="sidebar-sub-link <?php echo $currentTab === 'add_upi_image' ? 'active' : ''; ?>" href="/admin/?tab=add_upi_image">Add UPI Image</a>
                <a class="sidebar-sub-link <?php echo $currentTab === 'add_usdt_image' ? 'active' : ''; ?>" href="/admin/?tab=add_usdt_image">Add USDT Image</a>
                <a class="sidebar-sub-link <?php echo $currentTab === 'recharges' ? 'active' : ''; ?>" href="/admin/?tab=recharges">Deposit Update</a>
                <a class="sidebar-sub-link <?php echo $currentTab === 'withdrawals' ? 'active' : ''; ?>" href="/admin/?tab=withdrawals">Withdraw Apply</a>
                <a class="sidebar-sub-link <?php echo $currentTab === 'upi_withdraw' ? 'active' : ''; ?>" href="/admin/?tab=upi_withdraw">UPI Withdraw</a>
                <a class="sidebar-sub-link <?php echo $currentTab === 'withdraw_sent' ? 'active' : ''; ?>" href="/admin/?tab=withdraw_sent">Withdraw Sent</a>
                <a class="sidebar-sub-link <?php echo $currentTab === 'withdraw_reject' ? 'active' : ''; ?>" href="/admin/?tab=withdraw_reject">Withdraw Reject</a>
            </div>
        </div>
    </div>

    <!-- SUPPORT -->
    <?php $supportOpen = in_array($currentTab, $supportTabs, true); ?>
    <div class="sidebar-dropdown">
        <a class="sidebar-nav-link sidebar-dropdown-toggle <?php echo $supportOpen ? 'active' : ''; ?>" 
           data-bs-toggle="collapse" href="#supportCollapse" role="button" aria-expanded="<?php echo $supportOpen ? 'true' : 'false'; ?>">
            <i class="fas fa-headset text-orange"></i>
            <span>Support</span>
        </a>
        <div class="collapse <?php echo $supportOpen ? 'show' : ''; ?>" id="supportCollapse">
            <div class="sidebar-submenu">
                <a class="sidebar-sub-link <?php echo $currentTab === 'support_deposit' ? 'active' : ''; ?>" href="/admin/?tab=support_deposit">Deposit Problem</a>
                <a class="sidebar-sub-link <?php echo $currentTab === 'support_withdraw' ? 'active' : ''; ?>" href="/admin/?tab=support_withdraw">Withdrawal Problem</a>
                <a class="sidebar-sub-link <?php echo $currentTab === 'support_ifsc' ? 'active' : ''; ?>" href="/admin/?tab=support_ifsc">IFSC Modification</a>
                <a class="sidebar-sub-link <?php echo $currentTab === 'support_bank' ? 'active' : ''; ?>" href="/admin/?tab=support_bank">Bank Modification</a>
                <a class="sidebar-sub-link <?php echo $currentTab === 'support_game' ? 'active' : ''; ?>" href="/admin/?tab=support_game">Game Problem</a>
            </div>
        </div>
    </div>

    <!-- ADMIN MANAGE -->
    <?php $adminManageOpen = in_array($currentTab, $adminManageTabs, true); ?>
    <div class="sidebar-dropdown">
        <a class="sidebar-nav-link sidebar-dropdown-toggle <?php echo $adminManageOpen ? 'active' : ''; ?>" 
           data-bs-toggle="collapse" href="#adminManageCollapse" role="button" aria-expanded="<?php echo $adminManageOpen ? 'true' : 'false'; ?>">
            <i class="fas fa-user-shield text-red"></i>
            <span>Admin Manage</span>
        </a>
        <div class="collapse <?php echo $adminManageOpen ? 'show' : ''; ?>" id="adminManageCollapse">
            <div class="sidebar-submenu">
                <a class="sidebar-sub-link <?php echo $currentTab === 'settings' ? 'active' : ''; ?>" href="/admin/?tab=settings">System & API Settings</a>
                <a class="sidebar-sub-link <?php echo $currentTab === 'bonus_manage' ? 'active' : ''; ?>" href="/admin/?tab=bonus_manage">Bonus Manage</a>
                <a class="sidebar-sub-link <?php echo $currentTab === 'admin_password' ? 'active' : ''; ?>" href="/admin/?tab=admin_password">Admin Password</a>
                <a class="sidebar-sub-link <?php echo $currentTab === 'check_same_ip' ? 'active' : ''; ?>" href="/admin/?tab=check_same_ip">Check Same IP</a>
                <a class="sidebar-sub-link <?php echo $currentTab === 'site_maintenance' ? 'active' : ''; ?>" href="/admin/?tab=site_maintenance">Site Maintenance</a>
                <a class="sidebar-sub-link <?php echo $currentTab === 'banned_users' ? 'active' : ''; ?>" href="/admin/?tab=banned_users">Banned Users</a>
                <a class="sidebar-sub-link <?php echo $currentTab === 'users' ? 'active' : ''; ?>" href="/admin/?tab=users">Manage Users</a>
                <a class="sidebar-sub-link <?php echo $currentTab === 'daily_salary' ? 'active' : ''; ?>" href="/admin/?tab=daily_salary">Daily Salary</a>
                <a class="sidebar-sub-link <?php echo $currentTab === 'gift_code' ? 'active' : ''; ?>" href="/admin/?tab=gift_code">Gift Code</a>
                <a class="sidebar-sub-link <?php echo $currentTab === 'add_admin' ? 'active' : ''; ?>" href="/admin/?tab=add_admin">Add Admin</a>
                <a class="sidebar-sub-link <?php echo $currentTab === 'demo_user' ? 'active' : ''; ?>" href="/admin/?tab=demo_user">Demo User</a>
                <a class="sidebar-sub-link <?php echo $currentTab === 'agent_user' ? 'active' : ''; ?>" href="/admin/?tab=agent_user">Agent User</a>
            </div>
        </div>
    </div>

    <!-- GO TO WEBSITE -->
    <a class="sidebar-nav-link" href="/" target="_blank">
        <i class="fas fa-external-link-alt text-muted"></i>
        <span>Go To Website</span>
    </a>
</aside>
