<?php
declare(strict_types=1);

require_once __DIR__ . '/../api/_bootstrap.php';

// Start Session
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

// Load Authentication controller
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/DashboardController.php';
require_once __DIR__ . '/controllers/UserController.php';
require_once __DIR__ . '/controllers/AgentController.php';
require_once __DIR__ . '/controllers/FinanceController.php';
require_once __DIR__ . '/controllers/GameController.php';
require_once __DIR__ . '/controllers/SupportController.php';
require_once __DIR__ . '/controllers/SettingsController.php';
require_once __DIR__ . '/controllers/ReportController.php';

// Helper function for safe admin queries
if (!function_exists('admin_db_rows')) {
    function admin_db_rows(string $sql, array $args = []): array {
        $pdo = api_pdo();
        if (!$pdo) return [];
        try {
            if (empty($args)) {
                return $pdo->query($sql)->fetchAll() ?: [];
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($args);
            return $stmt->fetchAll() ?: [];
        } catch (Throwable $e) {
            return [];
        }
    }
}

// Initialize CSRF Token
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf'];

// Check Logout
if (($_GET['logout'] ?? '') === '1') {
    AuthController::logout();
    header('Location: /admin/');
    exit;
}

// Check Login Post
$loginError = '';
if (!api_pdo()) {
    $loginError = 'Database not available: ' . ($GLOBALS['db_connection_error'] ?? 'Connection failed');
}

if (!AuthController::checkRememberMe() && empty($_SESSION['admin_logged_in'])) {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && empty($loginError)) {
        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $remember = !empty($_POST['remember']);
        
        $res = AuthController::login($username, $password, $remember);
        if ($res['success']) {
            header('Location: /admin/');
            exit;
        } else {
            $loginError = $res['message'];
        }
    }
}

// Render Login Page if not authenticated
if (!AuthController::checkRememberMe() && empty($_SESSION['admin_logged_in'])):
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Dhani.win Admin - Secure Access</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <link href="assets/css/admin-theme.css" rel="stylesheet">
</head>
<body class="d-flex align-items-center justify-content-center" style="min-height: 100vh;">
  <div class="glass-panel p-5 text-center" style="width: min(440px, 90vw);">
    <div class="brand-logo mx-auto mb-4" style="width: 60px; height: 60px; font-size: 28px; border-radius: 18px;">✦</div>
    <h2 class="fw-bold mb-1" style="background: linear-gradient(to right, #fff, var(--accent-gold)); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Dhani Win Portal</h2>
    <p class="text-secondary mb-4">Enterprise Prediction Gaming Administrator Panel</p>
    
    <?php if ($loginError): ?>
        <div class="alert alert-danger border-0 text-start" style="background: rgba(239, 68, 68, 0.15); color: #fca5a5; border-radius: 12px;">
            <i class="fas fa-exclamation-circle me-2"></i> <?php echo htmlspecialchars($loginError); ?>
        </div>
    <?php endif; ?>

    <form method="post" class="text-start">
      <div class="mb-3">
        <label class="form-label text-secondary small fw-bold">Admin Username</label>
        <div class="input-group">
            <span class="input-group-text border-0" style="background: rgba(7, 9, 19, 0.6); color: var(--text-muted);"><i class="fas fa-user"></i></span>
            <input name="username" class="form-control form-control-premium" placeholder="Enter username" required autocomplete="username">
        </div>
      </div>
      <div class="mb-4">
        <label class="form-label text-secondary small fw-bold">Secret Password</label>
        <div class="input-group">
            <span class="input-group-text border-0" style="background: rgba(7, 9, 19, 0.6); color: var(--text-muted);"><i class="fas fa-lock"></i></span>
            <input name="password" type="password" class="form-control form-control-premium" placeholder="Enter password" required autocomplete="current-password">
        </div>
      </div>
      <div class="mb-4 form-check text-start">
        <input type="checkbox" name="remember" class="form-check-input" id="remember-me">
        <label class="form-check-label text-secondary small" for="remember-me">Remember me for 30 days</label>
      </div>
      <button type="submit" class="btn-premium w-100 py-3 justify-content-center">
        Secure Login <i class="fas fa-shield-alt ms-2"></i>
      </button>
    </form>
  </div>
</body>
</html>
<?php
exit;
endif;

// Perform CSRF checks on standard procedural POST requests if any remain
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST['action'])) {
    $token = (string)($_POST['csrf'] ?? '');
    if (!hash_equals($csrfToken, $token)) {
        http_response_code(403);
        exit('Invalid CSRF token');
    }
}

// Fetch active tab
$tab = (string)($_GET['tab'] ?? 'dashboard');
$validTabs = [
    'dashboard', 'live', 'users', 'recharges', 'withdrawals', 'agents', 'queue', 'support', 'gateways', 'settings', 'audit',
    'wingo_30s', 'wingo_1m', 'wingo_3m', 'wingo_5m',
    'k3_1m', 'k3_3m', 'k3_5m', 'k3_10m',
    'd5_1m', 'd5_3m', 'd5_5m', 'd5_10m',
    'add_upi', 'usdt_rate', 'add_usdt', 'add_upi_image', 'add_usdt_image', 'upi_withdraw', 'withdraw_sent', 'withdraw_reject',
    'support_deposit', 'support_withdraw', 'support_ifsc', 'support_bank', 'support_game',
    'bonus_manage', 'admin_password', 'check_same_ip', 'site_maintenance', 'banned_users', 'daily_salary', 'gift_code', 'add_admin', 'demo_user', 'agent_user'
];
if (!in_array($tab, $validTabs, true)) {
    $tab = 'dashboard';
}

// Permission checking per tab
$requiredPermissions = [
    'dashboard' => 'dashboard',
    'live' => 'game_control',
    'users' => 'user_management',
    'recharges' => 'finance',
    'withdrawals' => 'finance',
    'agents' => 'agent_management',
    'queue' => 'game_control',
    'support' => 'support',
    'gateways' => 'settings',
    'settings' => 'settings',
    'audit' => 'reports',
    // WinGo
    'wingo_30s' => 'game_control',
    'wingo_1m' => 'game_control',
    'wingo_3m' => 'game_control',
    'wingo_5m' => 'game_control',
    // K3
    'k3_1m' => 'game_control',
    'k3_3m' => 'game_control',
    'k3_5m' => 'game_control',
    'k3_10m' => 'game_control',
    // 5D
    'd5_1m' => 'game_control',
    'd5_3m' => 'game_control',
    'd5_5m' => 'game_control',
    'd5_10m' => 'game_control',
    // Finance
    'add_upi' => 'settings',
    'usdt_rate' => 'settings',
    'add_usdt' => 'settings',
    'add_upi_image' => 'settings',
    'add_usdt_image' => 'settings',
    'upi_withdraw' => 'finance',
    'withdraw_sent' => 'finance',
    'withdraw_reject' => 'finance',
    // Support
    'support_deposit' => 'support',
    'support_withdraw' => 'support',
    'support_ifsc' => 'support',
    'support_bank' => 'support',
    'support_game' => 'support',
    // Admin Manage
    'bonus_manage' => 'settings',
    'admin_password' => 'settings',
    'check_same_ip' => 'security',
    'site_maintenance' => 'settings',
    'banned_users' => 'user_management',
    'daily_salary' => 'settings',
    'gift_code' => 'settings',
    'add_admin' => 'settings',
    'demo_user' => 'user_management',
    'agent_user' => 'agent_management'
];

$hasAccess = admin_has_permission($requiredPermissions[$tab]);

// Include Header & Viewport Wrapper
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="<?php echo $csrfToken; ?>">
  <title>Dhani.win Admin - Enterprise Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <link href="assets/css/admin-theme.css?v=<?php echo time(); ?>" rel="stylesheet">
</head>
<body>
  <?php require __DIR__ . '/views/navbar.php'; ?>
  <div class="app-layout">
    <?php require __DIR__ . '/views/sidebar.php'; ?>
    <main class="app-viewport">
      
      <?php if (!$hasAccess): ?>
        <div class="glass-panel p-5 text-center mt-5">
            <div class="display-1 text-danger mb-4"><i class="fas fa-lock"></i></div>
            <h3 class="fw-bold">Access Denied</h3>
            <p class="text-secondary">Your administrator role does not possess the permissions required to access the <strong><?php echo htmlspecialchars($tab); ?></strong> panel.</p>
        </div>
      <?php else: ?>

        <!-- DYNAMIC PANEL ROUTING -->

        <?php
          $flashMessage = trim((string)($_GET['flash'] ?? ''));
          $flashType = (string)($_GET['flash_type'] ?? 'ok');
        ?>
        <?php if ($flashMessage !== ''): ?>
          <div class="alert <?php echo $flashType === 'err' ? 'alert-danger' : 'alert-success'; ?> border-0 d-flex align-items-center gap-2 mb-4" role="alert" style="border-radius: 14px;">
            <i class="fas <?php echo $flashType === 'err' ? 'fa-exclamation-circle' : 'fa-check-circle'; ?>"></i>
            <span class="fw-bold"><?php echo htmlspecialchars($flashMessage); ?></span>
          </div>
          <script>
          setTimeout(function () {
              if (typeof window.showToast === 'function') {
                  window.showToast(<?php echo json_encode($flashMessage); ?>, <?php echo $flashType === 'err' ? "'danger'" : "'success'"; ?>);
              }
          }, 600);
          </script>
        <?php endif; ?>

        <?php if ($tab === 'dashboard'): ?>
          <div id="dashboard-view">
            <h2 class="fw-bold mb-4"><i class="fas fa-chart-line text-gold me-2"></i> Operational Performance</h2>
            
            <!-- Quick Administrative Actions -->
            <h4 class="fw-bold mb-3 text-gold"><i class="fas fa-bolt text-gold me-2"></i> Quick Administrative Actions</h4>
            <div class="row g-4 mb-4">
              <div class="col-xl-3 col-sm-6">
                <a href="/admin/?tab=site_maintenance" class="text-decoration-none">
                  <div class="glass-panel p-3 text-center h-100 border-glow" style="border-color: rgba(239, 68, 68, 0.4) !important; cursor: pointer;">
                    <i class="fas fa-tools text-red fs-3 mb-2"></i>
                    <div class="text-white fw-bold">Site Maintenance</div>
                    <span class="text-muted small">Toggle offline mode</span>
                  </div>
                </a>
              </div>
              <div class="col-xl-3 col-sm-6">
                <a href="/admin/?tab=gift_code" class="text-decoration-none">
                  <div class="glass-panel p-3 text-center h-100 border-glow" style="border-color: rgba(249, 115, 22, 0.4) !important; cursor: pointer;">
                    <i class="fas fa-gift text-orange fs-3 mb-2"></i>
                    <div class="text-white fw-bold">Create Gift Code</div>
                    <span class="text-muted small">Distribute free bonuses</span>
                  </div>
                </a>
              </div>
              <div class="col-xl-3 col-sm-6">
                <a href="/admin/?tab=check_same_ip" class="text-decoration-none">
                  <div class="glass-panel p-3 text-center h-100 border-glow" style="border-color: rgba(59, 130, 246, 0.4) !important; cursor: pointer;">
                    <i class="fas fa-shield-alt text-blue fs-3 mb-2"></i>
                    <div class="text-white fw-bold">Check Same IP</div>
                    <span class="text-muted small">Detect multi-accounts</span>
                  </div>
                </a>
              </div>
              <div class="col-xl-3 col-sm-6">
                <a href="/admin/?tab=daily_salary" class="text-decoration-none">
                  <div class="glass-panel p-3 text-center h-100 border-glow" style="border-color: rgba(16, 185, 129, 0.4) !important; cursor: pointer;">
                    <i class="fas fa-money-bill-wave text-green fs-3 mb-2"></i>
                    <div class="text-white fw-bold">Manage Salary</div>
                    <span class="text-muted small">Config promoter payouts</span>
                  </div>
                </a>
              </div>
            </div>

            <!-- Stats grid -->
            <div class="stats-grid">
              <!-- 1. Total Users (Blue Accent) -->
              <div class="glass-panel stat-card" style="border-left: 4px solid var(--accent-blue);">
                <div class="stat-title text-blue">Total Registered Users</div>
                <div class="stat-value text-white" id="stat-total-users">0</div>
                <div class="stat-change text-blue" id="stat-today-users"><i class="fas fa-user-plus"></i> +0 today</div>
              </div>
              <!-- 2. User Wallet (Purple Accent) -->
              <div class="glass-panel stat-card" style="border-left: 4px solid var(--accent-purple);">
                <div class="stat-title text-purple">Total User Wallets</div>
                <div class="stat-value text-white" id="stat-user-wallet">₹0.00</div>
                <div class="stat-change text-purple"><i class="fas fa-wallet"></i> Database holdings</div>
              </div>
              <!-- 3. Today's Recharge (Green Accent) -->
              <div class="glass-panel stat-card" style="border-left: 4px solid var(--accent-green);">
                <div class="stat-title text-green">Today's Recharges</div>
                <div class="stat-value text-white" id="stat-today-recharges">₹0.00</div>
                <div class="stat-change text-green" id="stat-total-recharges"><i class="fas fa-arrow-circle-down"></i> Total: ₹0.00</div>
              </div>
              <!-- 4. Today's Withdrawal (Red Accent) -->
              <div class="glass-panel stat-card" style="border-left: 4px solid var(--accent-red);">
                <div class="stat-title text-red">Today's Withdrawals</div>
                <div class="stat-value text-white" id="stat-today-withdrawals">₹0.00</div>
                <div class="stat-change text-red" id="stat-total-withdrawals"><i class="fas fa-arrow-circle-up"></i> Total: ₹0.00</div>
              </div>
              <!-- 5. Pending Recharge (Orange Accent) -->
              <div class="glass-panel stat-card" style="border-left: 4px solid var(--accent-orange);">
                <div class="stat-title text-orange">Pending Deposits</div>
                <div class="stat-value text-white" id="stat-pending-recharges">₹0.00</div>
                <div class="stat-change text-orange" id="stat-pending-recharges-count"><i class="fas fa-clock"></i> 0 requests pending</div>
              </div>
              <!-- 6. Today's Profit (Gold Accent) -->
              <div class="glass-panel stat-card" style="border-left: 4px solid var(--accent-gold);">
                <div class="stat-title text-gold">Today's Net Profit</div>
                <div class="stat-value text-white" id="stat-today-profit">₹0.00</div>
                <div class="stat-change text-gold"><i class="fas fa-chart-line"></i> Financial net index</div>
              </div>
            </div>

            <!-- Charts & Action Panel -->
            <div class="row g-4">
              <div class="col-lg-6">
                <div class="glass-panel p-4 h-100">
                  <h4 class="fw-bold mb-3">User Growth Trend</h4>
                  <canvas id="chart-user-growth" style="max-height: 320px;"></canvas>
                </div>
              </div>
              <div class="col-lg-6">
                <div class="glass-panel p-4 h-100">
                  <h4 class="fw-bold mb-3">Deposit vs Withdrawal Payouts</h4>
                  <canvas id="chart-finance" style="max-height: 320px;"></canvas>
                </div>
              </div>
            </div>

            <!-- Fast Global Site Control -->
            <div class="glass-panel p-4 mt-4">
              <h4 class="fw-bold mb-3"><i class="fas fa-sliders-h text-gold me-2"></i> Global Risk Controls</h4>
              <form action="api.php" method="post" id="form-quick-setting" class="row g-3">
                <input type="hidden" name="action" value="save_setting">
                <input type="hidden" name="csrf" value="<?php echo $csrfToken; ?>">
                <div class="col-md-3">
                  <label class="form-label text-secondary small fw-bold">Settlement Mode</label>
                  <select name="setting_value" class="form-control form-control-premium" onchange="this.form.submit()">
                    <?php $mode = api_setting('settlement_mode', 'auto'); ?>
                    <option value="auto" <?php echo $mode === 'auto' ? 'selected' : ''; ?>>Standard Auto Draw</option>
                    <option value="auto_hedge" <?php echo $mode === 'auto_hedge' ? 'selected' : ''; ?>>Auto-Hedging (Min Payout)</option>
                    <option value="force_win" <?php echo $mode === 'force_win' ? 'selected' : ''; ?>>Force Win Mode</option>
                    <option value="force_loss" <?php echo $mode === 'force_loss' ? 'selected' : ''; ?>>Force Loss Mode</option>
                  </select>
                  <input type="hidden" name="setting_key" value="settlement_mode">
                </div>
              </form>
            </div>
          </div>
        <?php endif; ?>

        <?php if ($tab === 'live'): 
          $liveGames = [];
          $pdo = api_pdo();
          if ($pdo) {
              foreach (api_lottery_game_list() as $game) {
                  $code = $game['gameCode'];
                  $issue = api_lottery_issue_data($code);
                  $stats = $pdo->query("
                      SELECT COUNT(*) AS bet_count, 
                             COALESCE(SUM(stake_amount),0) AS stake_total, 
                             COALESCE(SUM(win_amount),0) AS win_total 
                      FROM lottery_bets 
                      WHERE game_code = " . $pdo->quote($code) . " 
                        AND issue_number = " . $pdo->quote($issue['issueNumber'])
                  )->fetch();
                  $liveGames[] = [
                      'game' => $game,
                      'issue' => $issue,
                      'stats' => $stats ?: ['bet_count' => 0, 'stake_total' => 0, 'win_total' => 0],
                  ];
              }
          }
        ?>
          <div id="live-view">
            <h2 class="fw-bold mb-4"><i class="fas fa-play-circle text-gold me-2"></i> Live Game Visualizer</h2>
            <div class="row g-4">
              <?php foreach ($liveGames as $item): ?>
                <div class="col-xl-4 col-md-6">
                  <div class="glass-panel p-4 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                      <div>
                        <h5 class="fw-bold mb-0 text-white"><?php echo htmlspecialchars($item['game']['gameCode']); ?></h5>
                        <span class="text-secondary small"><?php echo htmlspecialchars($item['game']['lotteryCode']); ?> | <?php echo $item['issue']['intervalMinute'] * 60; ?>s interval</span>
                      </div>
                      <span class="pulse-green"></span>
                    </div>
                    <div class="p-3 mb-3 text-center border rounded" style="background: rgba(7,9,19,0.5); border-color: var(--border-light) !important;">
                      <span class="text-secondary small d-block">CURRENT ACTIVE PERIOD</span>
                      <strong class="fs-4 text-gold"><?php echo htmlspecialchars($item['issue']['issueNumber']); ?></strong>
                      <span class="text-muted d-block small mt-1">Countdown: <?php echo $item['issue']['countdown']; ?>s</span>
                    </div>
                    <div class="row g-2 mb-3 text-center">
                      <div class="col-4 border-end" style="border-color: var(--border-light) !important;">
                        <span class="text-muted small">Bets</span>
                        <div class="fw-bold text-white"><?php echo $item['stats']['bet_count']; ?></div>
                      </div>
                      <div class="col-4 border-end" style="border-color: var(--border-light) !important;">
                        <span class="text-muted small">Total Pool</span>
                        <div class="fw-bold text-success">₹<?php echo number_format((float)$item['stats']['stake_total'], 2); ?></div>
                      </div>
                      <div class="col-4">
                        <span class="text-muted small">Payout</span>
                        <div class="fw-bold text-warning">₹<?php echo number_format((float)$item['stats']['win_total'], 2); ?></div>
                      </div>
                    </div>
                    <form action="api.php" method="post">
                      <input type="hidden" name="action" value="add_to_queue">
                      <input type="hidden" name="csrf" value="<?php echo $csrfToken; ?>">
                      <input type="hidden" name="game_code" value="<?php echo htmlspecialchars($item['game']['gameCode']); ?>">
                      <input type="hidden" name="issue_number" value="<?php echo htmlspecialchars($item['issue']['issueNumber']); ?>">
                      <div class="input-group">
                        <input name="premium" class="form-control form-control-premium" placeholder="Force premium outcome">
                        <button type="submit" class="btn btn-premium">Force</button>
                      </div>
                    </form>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>

        <?php if ($tab === 'users'): ?>
          <div id="users-view">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold m-0"><i class="fas fa-users-cog text-gold me-2"></i> Member Directory</h2>
                <button class="btn-premium" data-bs-toggle="modal" data-bs-target="#modal-add-user">
                    <i class="fas fa-user-plus"></i> Create User
                </button>
            </div>
            
            <div class="glass-panel p-4">
                <div class="table-responsive table-responsive-premium">
                    <table class="table w-100" id="users-table">
                        <thead>
                            <tr>
                                <th>User ID</th>
                                <th>Username</th>
                                <th>Nickname</th>
                                <th>Phone</th>
                                <th>Wallet Bal</th>
                                <th>Game Bal</th>
                                <th>Can Bet</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

            <!-- Modals -->
            <!-- Modal: Adjust Balance -->
            <div class="modal fade" id="modal-adjust-balance" tabindex="-1">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content modal-content-premium text-white">
                  <div class="modal-header modal-header-premium">
                    <h5 class="modal-title fw-bold"><i class="fas fa-wallet text-gold me-2"></i> Adjust Balance: <span id="adjust-balance-username"></span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                  </div>
                  <form id="form-adjust-balance">
                    <div class="modal-body p-4">
                      <input type="hidden" name="action" value="adjust_balance">
                      <input type="hidden" name="user_id" id="adjust-balance-user-id">
                      <div class="mb-3">
                        <label class="form-label text-secondary small fw-bold">Balance Type</label>
                        <select name="type" class="form-control form-control-premium">
                            <option value="game">Game Balance</option>
                            <option value="wallet">Main Wallet Balance</option>
                        </select>
                      </div>
                      <div class="mb-3">
                        <label class="form-label text-secondary small fw-bold">Adjustment Amount</label>
                        <input type="number" name="amount" step="0.01" class="form-control form-control-premium" placeholder="e.g. 500 or -200" required>
                      </div>
                      <div class="mb-3">
                        <label class="form-label text-secondary small fw-bold">Reason / Remarks</label>
                        <textarea name="notes" class="form-control form-control-premium" style="min-height: 80px;" placeholder="Reason for adjusting" required></textarea>
                      </div>
                    </div>
                    <div class="modal-footer modal-footer-premium">
                      <button type="button" class="btn-secondary-premium" data-bs-dismiss="modal">Cancel</button>
                      <button type="button" id="btn-submit-adjustment" class="btn-premium">Submit Adjust</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>

            <!-- Modal: Target Control -->
            <div class="modal fade" id="modal-target-control" tabindex="-1">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content modal-content-premium text-white">
                  <div class="modal-header modal-header-premium">
                    <h5 class="modal-title fw-bold"><i class="fas fa-bullseye text-gold me-2"></i> Targeted User Win-Rate Control</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                  </div>
                  <form id="form-target-control">
                    <div class="modal-body p-4">
                      <input type="hidden" name="action" value="set_target_control">
                      <input type="hidden" name="user_id" id="control-user-id">
                      <div class="mb-3">
                        <label class="form-label text-secondary small fw-bold">Win-Rate Percentage</label>
                        <input type="number" name="win_rate_percent" id="control-win-rate" class="form-control form-control-premium" min="0" max="100" placeholder="e.g. 30" required>
                        <span class="text-muted small mt-1 d-block">Target win-rate over time. Lower values force losses.</span>
                      </div>
                      <div class="mb-3">
                        <label class="form-label text-secondary small fw-bold">Targeting Status</label>
                        <select name="status" id="control-status" class="form-control form-control-premium">
                            <option value="1">Enabled (Force Control)</option>
                            <option value="0">Disabled (System Default)</option>
                        </select>
                      </div>
                    </div>
                    <div class="modal-footer modal-footer-premium">
                      <button type="button" class="btn-secondary-premium" data-bs-dismiss="modal">Cancel</button>
                      <button type="button" id="btn-submit-control" class="btn-premium">Save target</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>

            <!-- Modal: Add User -->
            <div class="modal fade" id="modal-add-user" tabindex="-1">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content modal-content-premium text-white">
                  <div class="modal-header modal-header-premium">
                    <h5 class="modal-title fw-bold"><i class="fas fa-user-plus text-gold me-2"></i> Register New User</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                  </div>
                  <form id="form-add-user" action="api.php" method="post">
                    <div class="modal-body p-4">
                      <input type="hidden" name="action" value="save_user">
                      <input type="hidden" name="csrf" value="<?php echo $csrfToken; ?>">
                      <div class="mb-3">
                        <label class="form-label text-secondary small fw-bold">Username</label>
                        <input name="username" class="form-control form-control-premium" placeholder="Enter username/phone" required>
                      </div>
                      <div class="mb-3">
                        <label class="form-label text-secondary small fw-bold">Nickname</label>
                        <input name="nickname" class="form-control form-control-premium" placeholder="Leave empty for auto">
                      </div>
                      <div class="mb-3">
                        <label class="form-label text-secondary small fw-bold">Password</label>
                        <input name="password" type="password" class="form-control form-control-premium" placeholder="Leave empty for admin123">
                      </div>
                      <div class="mb-3">
                        <label class="form-label text-secondary small fw-bold">Contact Phone</label>
                        <input name="phone" class="form-control form-control-premium" placeholder="Enter phone number">
                      </div>
                    </div>
                    <div class="modal-footer modal-footer-premium">
                      <button type="button" class="btn-secondary-premium" data-bs-dismiss="modal">Cancel</button>
                      <button type="submit" class="btn-premium">Create</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          </div>
        <?php endif; ?>

        <?php if ($tab === 'recharges'): ?>
          <div id="recharges-view">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold m-0"><i class="fas fa-arrow-alt-circle-down text-gold me-2"></i> Recharge Deposits</h2>
                <div class="d-flex gap-2">
                    <button id="btn-bulk-approve-recharge" class="btn btn-success"><i class="fas fa-check-double"></i> Bulk Approve</button>
                    <select id="recharge-status-filter" class="form-select form-control-premium" style="width: 180px;">
                        <option value="">All Statuses</option>
                        <option value="Pending" selected>Pending</option>
                        <option value="PendingReview">Pending Review</option>
                        <option value="Approved">Approved</option>
                        <option value="Rejected">Rejected</option>
                    </select>
                </div>
            </div>

            <div class="glass-panel p-4">
                <div class="table-responsive table-responsive-premium">
                    <table class="table w-100" id="recharges-table">
                        <thead>
                            <tr>
                                <th>Select</th>
                                <th>Order No</th>
                                <th>Player ID</th>
                                <th>Username</th>
                                <th>Amount</th>
                                <th>Gateway</th>
                                <th>Status</th>
                                <th>UTR</th>
                                <th>Screenshot</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

            <!-- Modal: View Screenshot -->
            <div class="modal fade" id="modal-screenshot" tabindex="-1">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content modal-content-premium text-white">
                  <div class="modal-header modal-header-premium">
                    <h5 class="modal-title fw-bold">Review Screenshot UTR</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body p-4 text-center">
                    <img id="screenshot-image" class="img-fluid rounded" style="max-height: 500px;" alt="Screenshot review">
                  </div>
                </div>
              </div>
            </div>
          </div>
        <?php endif; ?>

        <?php if ($tab === 'withdrawals'): ?>
          <div id="withdrawals-view">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold m-0"><i class="fas fa-arrow-alt-circle-up text-gold me-2"></i> Withdrawal Payouts</h2>
                <select id="withdrawal-status-filter" class="form-select form-control-premium" style="width: 180px;">
                    <option value="">All Statuses</option>
                    <option value="Pending" selected>Pending</option>
                    <option value="Approved">Approved</option>
                    <option value="Rejected">Rejected</option>
                </select>
            </div>

            <div class="glass-panel p-4">
                <div class="table-responsive table-responsive-premium">
                    <table class="table w-100" id="withdrawals-table">
                        <thead>
                            <tr>
                                <th>Order No</th>
                                <th>Player ID</th>
                                <th>Username</th>
                                <th>Amount</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Account</th>
                                <th>Remarks</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
          </div>
        <?php endif; ?>

        <?php if ($tab === 'agents'): 
          $agentCommissions = AgentController::listCommissions();
        ?>
          <div id="agents-view">
            <h2 class="fw-bold mb-4"><i class="fas fa-project-diagram text-gold me-2"></i> Agent Trees & Commissions</h2>
            
            <div class="row g-4">
              <div class="col-lg-4">
                <div class="glass-panel p-4 h-100">
                  <h4 class="fw-bold mb-3">Inspect Agent Stats</h4>
                  <form method="get" class="mb-3">
                    <input type="hidden" name="tab" value="agents">
                    <div class="input-group">
                        <input name="inspect_id" class="form-control form-control-premium" placeholder="Enter User ID (e.g. 132257)" required>
                        <button type="submit" class="btn btn-premium">Inspect</button>
                    </div>
                  </form>

                  <?php 
                  $inspectId = (int)($_GET['inspect_id'] ?? 0);
                  if ($inspectId > 0): 
                      $stats = AgentController::getAgentSummary($inspectId);
                      if (isset($stats['error'])):
                  ?>
                      <div class="alert alert-danger border-0 small"><?php echo htmlspecialchars($stats['error']); ?></div>
                  <?php else: ?>
                      <div class="p-3 border rounded mb-3 bg-dark border-secondary">
                        <span class="text-secondary small d-block">INSPECTING USER</span>
                        <strong class="fs-5 text-white"><?php echo $stats['user_id']; ?></strong>
                        <div class="row g-2 mt-2 text-center text-secondary small">
                          <div class="col-6 border-end border-secondary">
                            <div>Total team size</div>
                            <strong class="text-white fs-6"><?php echo $stats['total_team']; ?></strong>
                          </div>
                          <div class="col-6">
                            <div>Total Commission</div>
                            <strong class="text-success fs-6">₹<?php echo number_format($stats['total_commission'], 2); ?></strong>
                          </div>
                        </div>
                      </div>
                      <div class="small">
                        <div class="fw-bold mb-2">Team Level Breakdown:</div>
                        <?php foreach ($stats['levels_count'] as $lv => $count): ?>
                            <div class="d-flex justify-content-between border-bottom border-secondary py-1 text-secondary">
                                <span><?php echo $lv; ?> Referrals</span>
                                <span class="text-white"><?php echo $count; ?></span>
                            </div>
                        <?php endforeach; ?>
                      </div>
                  <?php endif; endif; ?>
                </div>
              </div>
              <div class="col-lg-8">
                <div class="glass-panel p-4 h-100">
                  <h4 class="fw-bold mb-3">Recent Agent Commission Log</h4>
                  <div class="table-responsive table-responsive-premium">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Agent</th>
                                <th>From Player</th>
                                <th>Bet Order</th>
                                <th>Level</th>
                                <th>Stake</th>
                                <th>Commission</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($agentCommissions as $comm): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($comm['agent_name'] ?? 'Agent ' . $comm['user_id']); ?></td>
                                    <td><?php echo htmlspecialchars($comm['player_name'] ?? 'Player ' . $comm['from_user_id']); ?></td>
                                    <td><code><?php echo $comm['bet_order_no']; ?></code></td>
                                    <td>L<?php echo $comm['commission_level']; ?></td>
                                    <td>₹<?php echo number_format((float)$comm['bet_amount'], 2); ?></td>
                                    <td class="text-success fw-bold">+₹<?php echo number_format((float)$comm['commission_amount'], 4); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($agentCommissions)): ?>
                                <tr><td colspan="6" class="text-center text-muted">No commissions logged yet</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>
        <?php endif; ?>

        <?php if ($tab === 'queue'): 
          $queue = GameController::listQueue();
        ?>
          <div id="results-view">
            <h2 class="fw-bold mb-4"><i class="fas fa-list-ol text-gold me-2"></i> Result Overrides Queue</h2>
            
            <div class="row g-4">
              <div class="col-md-5">
                <div class="glass-panel p-4">
                  <h4 class="fw-bold mb-3">Schedule Result Override</h4>
                  <form action="api.php" method="post">
                    <input type="hidden" name="action" value="add_to_queue">
                    <input type="hidden" name="csrf" value="<?php echo $csrfToken; ?>">
                    <div class="mb-3">
                        <label class="form-label text-secondary small fw-bold">Select Game</label>
                        <select name="game_code" class="form-control form-control-premium" required>
                            <?php foreach (api_lottery_game_list() as $game): ?>
                                <option value="<?php echo htmlspecialchars($game['gameCode']); ?>"><?php echo htmlspecialchars($game['gameCode']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-secondary small fw-bold">Period/Issue Number</label>
                        <input name="issue_number" class="form-control form-control-premium" placeholder="e.g. 20260603100012345" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label text-secondary small fw-bold">Forced Premium Result</label>
                        <input name="premium" class="form-control form-control-premium" placeholder="e.g. 0-9 for Wingo, 123 for K3, 01234 for 5D" required>
                    </div>
                    <button type="submit" class="btn-premium w-100 justify-content-center">Queue Override</button>
                  </form>
                </div>
              </div>
              <div class="col-md-7">
                <div class="glass-panel p-4">
                  <h4 class="fw-bold mb-3">Active Queued Overrides</h4>
                  <div class="table-responsive table-responsive-premium">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Game</th>
                                <th>Issue No</th>
                                <th>Forced Payout</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($queue as $q): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($q['game_code']); ?></td>
                                    <td><code><?php echo htmlspecialchars($q['issue_number']); ?></code></td>
                                    <td class="text-gold fw-bold"><?php echo htmlspecialchars($q['premium']); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-danger btn-delete-queue" data-id="<?php echo $q['id']; ?>">Remove</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($queue)): ?>
                                <tr><td colspan="4" class="text-center text-muted">No scheduled overrides in queue</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>
        <?php endif; ?>

        <?php if ($tab === 'support'): ?>
          <div id="support-view">
            <h2 class="fw-bold mb-4"><i class="fas fa-ticket-alt text-gold me-2"></i> Support Tickets Queue</h2>
            
            <div class="glass-panel p-4">
                <div class="table-responsive table-responsive-premium">
                    <table class="table w-100" id="support-tickets-table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>User</th>
                                <th>Status</th>
                                <th>Last Update</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

            <?php require __DIR__ . '/views/support-chat-modal.php'; ?>
          </div>
        <?php endif; ?>

        <?php if ($tab === 'gateways'): 
          $upiMethods = SettingsController::getPaymentMethods();
          $usdtMethods = SettingsController::getUsdtMethods();
        ?>
          <div id="gateways-view">
            <h2 class="fw-bold mb-4"><i class="fas fa-credit-card text-gold me-2"></i> Gateway Rotations</h2>
            
            <!-- UPI Gateways -->
            <div class="glass-panel p-4 mb-4">
                <h4 class="fw-bold mb-3 text-gold">UPI Accounts Gateway</h4>
                <div class="table-responsive table-responsive-premium">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Display Name</th>
                                <th>Account/VPA Address</th>
                                <th>Limits (Min/Max)</th>
                                <th>Sort</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($upiMethods as $upi): ?>
                                <tr>
                                    <form action="api.php" method="post">
                                        <input type="hidden" name="action" value="save_payment">
                                        <input type="hidden" name="csrf" value="<?php echo $csrfToken; ?>">
                                        <input type="hidden" name="id" value="<?php echo $upi['id']; ?>">
                                        <td><input name="method_name" class="form-control form-control-premium py-1" value="<?php echo htmlspecialchars($upi['method_name']); ?>"></td>
                                        <td><input name="account_value" class="form-control form-control-premium py-1" value="<?php echo htmlspecialchars($upi['account_value']); ?>"></td>
                                        <td>
                                            <input name="min_amount" type="number" class="form-control form-control-premium py-1 d-inline-block" style="width: 80px;" value="<?php echo (float)$upi['min_amount']; ?>"> - 
                                            <input name="max_amount" type="number" class="form-control form-control-premium py-1 d-inline-block" style="width: 100px;" value="<?php echo (float)$upi['max_amount']; ?>">
                                        </td>
                                        <td><input name="sort_order" type="number" class="form-control form-control-premium py-1" style="width: 60px;" value="<?php echo $upi['sort_order']; ?>"></td>
                                        <td>
                                            <select name="enabled" class="form-control form-control-premium py-1">
                                                <option value="1" <?php echo $upi['enabled'] ? 'selected' : ''; ?>>Active</option>
                                                <option value="0" <?php echo !$upi['enabled'] ? 'selected' : ''; ?>>Paused</option>
                                            </select>
                                        </td>
                                        <td>
                                            <button type="submit" class="btn btn-sm btn-premium py-1">Save</button>
                                            <button type="submit" formaction="api.php" name="action" value="delete_payment" class="btn btn-sm btn-outline-danger py-1">Del</button>
                                        </td>
                                    </form>
                                </tr>
                            <?php endforeach; ?>
                            <!-- Add row -->
                            <tr>
                                <form action="api.php" method="post">
                                    <input type="hidden" name="action" value="save_payment">
                                    <input type="hidden" name="csrf" value="<?php echo $csrfToken; ?>">
                                    <td><input name="method_name" class="form-control form-control-premium py-1" placeholder="PhonePe"></td>
                                    <td><input name="account_value" class="form-control form-control-premium py-1" placeholder="merchant@upi"></td>
                                    <td>
                                        <input name="min_amount" type="number" class="form-control form-control-premium py-1 d-inline-block" style="width: 80px;" value="100"> - 
                                        <input name="max_amount" type="number" class="form-control form-control-premium py-1 d-inline-block" style="width: 100px;" value="50000">
                                    </td>
                                    <td><input name="sort_order" type="number" class="form-control form-control-premium py-1" style="width: 60px;" value="0"></td>
                                    <td>
                                        <select name="enabled" class="form-control form-control-premium py-1">
                                            <option value="1">Active</option>
                                            <option value="0">Paused</option>
                                        </select>
                                    </td>
                                    <td><button type="submit" class="btn btn-sm btn-success py-1">Add Account</button></td>
                                </form>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- USDT Gateways -->
            <div class="glass-panel p-4">
                <h4 class="fw-bold mb-3 text-gold">USDT Crypto Addresses</h4>
                <div class="table-responsive table-responsive-premium">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Wallet Name</th>
                                <th>Deposit Address</th>
                                <th>Network</th>
                                <th>Limits (Min/Max)</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usdtMethods as $usdt): ?>
                                <tr>
                                    <form action="api.php" method="post">
                                        <input type="hidden" name="action" value="save_usdt">
                                        <input type="hidden" name="csrf" value="<?php echo $csrfToken; ?>">
                                        <input type="hidden" name="id" value="<?php echo $usdt['id']; ?>">
                                        <td><input name="wallet_name" class="form-control form-control-premium py-1" value="<?php echo htmlspecialchars($usdt['wallet_name']); ?>"></td>
                                        <td><input name="wallet_address" class="form-control form-control-premium py-1" value="<?php echo htmlspecialchars($usdt['wallet_address']); ?>"></td>
                                        <td><input name="network" class="form-control form-control-premium py-1" style="width: 100px;" value="<?php echo htmlspecialchars($usdt['network']); ?>"></td>
                                        <td>
                                            <input name="min_amount" type="number" class="form-control form-control-premium py-1 d-inline-block" style="width: 70px;" value="<?php echo (float)$usdt['min_amount']; ?>"> - 
                                            <input name="max_amount" type="number" class="form-control form-control-premium py-1 d-inline-block" style="width: 90px;" value="<?php echo (float)$usdt['max_amount']; ?>">
                                        </td>
                                        <td>
                                            <select name="enabled" class="form-control form-control-premium py-1">
                                                <option value="1" <?php echo $usdt['enabled'] ? 'selected' : ''; ?>>Active</option>
                                                <option value="0" <?php echo !$usdt['enabled'] ? 'selected' : ''; ?>>Paused</option>
                                            </select>
                                        </td>
                                        <td>
                                            <button type="submit" class="btn btn-sm btn-premium py-1">Save</button>
                                            <button type="submit" formaction="api.php" name="action" value="delete_usdt" class="btn btn-sm btn-outline-danger py-1">Del</button>
                                        </td>
                                    </form>
                                </tr>
                            <?php endforeach; ?>
                            <!-- Add row -->
                            <tr>
                                <form action="api.php" method="post">
                                    <input type="hidden" name="action" value="save_usdt">
                                    <input type="hidden" name="csrf" value="<?php echo $csrfToken; ?>">
                                    <td><input name="wallet_name" class="form-control form-control-premium py-1" placeholder="Binance USDT"></td>
                                    <td><input name="wallet_address" class="form-control form-control-premium py-1" placeholder="T..."></td>
                                    <td><input name="network" class="form-control form-control-premium py-1" style="width: 100px;" value="TRC20"></td>
                                    <td>
                                        <input name="min_amount" type="number" class="form-control form-control-premium py-1 d-inline-block" style="width: 70px;" value="10"> - 
                                        <input name="max_amount" type="number" class="form-control form-control-premium py-1 d-inline-block" style="width: 90px;" value="10000">
                                    </td>
                                    <td>
                                        <select name="enabled" class="form-control form-control-premium py-1">
                                            <option value="1">Active</option>
                                            <option value="0">Paused</option>
                                        </select>
                                    </td>
                                    <td><button type="submit" class="btn btn-sm btn-success py-1">Add Address</button></td>
                                </form>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
          </div>
        <?php endif; ?>

        <?php if ($tab === 'settings'): 
          $settings = admin_db_rows("SELECT * FROM api_settings ORDER BY setting_key");
          $upstreamUrl = (string)api_setting('lottery_upstream_url', 'https://api.devlopedwithzayro.site/api/webapi');
          $upstreamKey = (string)api_setting('lottery_upstream_key', '');
          $settleMode  = (string)api_setting('settlement_mode', 'auto');
          $betEnabled  = (string)api_setting('bet_enabled', '1');
        ?>
          <div id="settings-view">
            <h2 class="fw-bold mb-4"><i class="fas fa-sliders-h text-gold me-2"></i> System Settings & API Engine</h2>
            
            <!-- 1. Live Result API Engine Card -->
            <div class="glass-panel p-4 mb-4" style="border: 1px solid rgba(245, 158, 11, 0.3);">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h4 class="fw-bold m-0 text-gold"><i class="fas fa-broadcast-tower me-2"></i> Live Result API Engine (Wingo API)</h4>
                    <span class="badge bg-success px-3 py-2"><i class="fas fa-check-circle me-1"></i> Live Sync Enabled</span>
                </div>
                <p class="text-secondary small mb-4">
                    Pulls official live draw numbers & colors directly from the external Lottery Engine into your local database. 
                    <br><strong class="text-white">Note:</strong> User Balance, Wallet, Registration, and Bets remain 100% Local and secure on this website.
                </p>

                <div class="row g-3">
                    <div class="col-md-7">
                        <form action="api.php" method="post" class="glass-panel p-3">
                            <input type="hidden" name="action" value="save_setting">
                            <input type="hidden" name="csrf" value="<?php echo $csrfToken; ?>">
                            <input type="hidden" name="setting_key" value="lottery_upstream_url">
                            <label class="form-label text-light fw-bold">Live API Upstream URL</label>
                            <div class="input-group mb-2">
                                <input type="url" name="setting_value" class="form-control form-control-premium" value="<?php echo htmlspecialchars($upstreamUrl); ?>" placeholder="https://api.devlopedwithzayro.site/api/webapi" required>
                                <button type="submit" class="btn btn-premium px-4">Save URL</button>
                            </div>
                            <small class="text-muted">Target endpoint for live WinGo draw results (e.g. <code>https://api.devlopedwithzayro.site/api/webapi</code>)</small>
                        </form>
                    </div>
                    <div class="col-md-5">
                        <form action="api.php" method="post" class="glass-panel p-3">
                            <input type="hidden" name="action" value="save_setting">
                            <input type="hidden" name="csrf" value="<?php echo $csrfToken; ?>">
                            <input type="hidden" name="setting_key" value="lottery_upstream_key">
                            <label class="form-label text-light fw-bold">API Access Key (Optional)</label>
                            <div class="input-group mb-2">
                                <input type="text" name="setting_value" class="form-control form-control-premium" value="<?php echo htmlspecialchars($upstreamKey); ?>" placeholder="Optional API Key">
                                <button type="submit" class="btn btn-premium px-4">Save Key</button>
                            </div>
                            <small class="text-muted">API key if your external engine requires authentication.</small>
                        </form>
                    </div>
                </div>
            </div>

            <!-- 2. Game Controls Card -->
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="glass-panel p-4 h-100">
                        <h5 class="fw-bold mb-3"><i class="fas fa-dice text-blue me-2"></i> Settlement & Control Mode</h5>
                        <form action="api.php" method="post">
                            <input type="hidden" name="action" value="save_setting">
                            <input type="hidden" name="csrf" value="<?php echo $csrfToken; ?>">
                            <input type="hidden" name="setting_key" value="settlement_mode">
                            <div class="mb-3">
                                <select name="setting_value" class="form-select form-control-premium">
                                    <option value="auto" <?php echo $settleMode === 'auto' ? 'selected' : ''; ?>>Auto (Follow Live External Results)</option>
                                    <option value="auto_hedge" <?php echo $settleMode === 'auto_hedge' ? 'selected' : ''; ?>>Auto Hedge (Maximize House Profit)</option>
                                    <option value="force_win" <?php echo $settleMode === 'force_win' ? 'selected' : ''; ?>>Force Win (All Players Win)</option>
                                    <option value="force_loss" <?php echo $settleMode === 'force_loss' ? 'selected' : ''; ?>>Force Loss (All Players Lose)</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-premium btn-sm w-100">Update Settlement Mode</button>
                        </form>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="glass-panel p-4 h-100">
                        <h5 class="fw-bold mb-3"><i class="fas fa-toggle-on text-green me-2"></i> Betting Operation</h5>
                        <form action="api.php" method="post">
                            <input type="hidden" name="action" value="save_setting">
                            <input type="hidden" name="csrf" value="<?php echo $csrfToken; ?>">
                            <input type="hidden" name="setting_key" value="bet_enabled">
                            <div class="mb-3">
                                <select name="setting_value" class="form-select form-control-premium">
                                    <option value="1" <?php echo $betEnabled === '1' ? 'selected' : ''; ?>>Betting Enabled (Active)</option>
                                    <option value="0" <?php echo $betEnabled === '0' ? 'selected' : ''; ?>>Betting Disabled (Locked)</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-premium btn-sm w-100">Update Betting Status</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- 3. Raw Settings Table -->
            <div class="glass-panel p-4">
                <h5 class="fw-bold mb-3"><i class="fas fa-table text-cyan me-2"></i> All System Variables</h5>
                <div class="table-responsive table-responsive-premium">
                    <table class="table">
                        <thead>
                            <tr>
                                <th style="width: 30%;">Setting Key</th>
                                <th style="width: 55%;">Setting Value</th>
                                <th style="width: 15%;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($settings as $set): ?>
                                <tr>
                                    <form action="api.php" method="post">
                                        <input type="hidden" name="action" value="save_setting">
                                        <input type="hidden" name="csrf" value="<?php echo $csrfToken; ?>">
                                        <input type="hidden" name="setting_key" value="<?php echo htmlspecialchars($set['setting_key']); ?>">
                                        <td>
                                            <code><?php echo htmlspecialchars($set['setting_key']); ?></code>
                                        </td>
                                        <td>
                                            <textarea name="setting_value" class="form-control form-control-premium py-1" style="min-height: 40px; font-size: 13px;"><?php echo htmlspecialchars($set['setting_value']); ?></textarea>
                                        </td>
                                        <td>
                                            <button type="submit" class="btn btn-premium btn-sm">Save</button>
                                        </td>
                                    </form>
                                </tr>
                            <?php endforeach; ?>
                            <tr>
                                <form action="api.php" method="post">
                                    <input type="hidden" name="action" value="save_setting">
                                    <input type="hidden" name="csrf" value="<?php echo $csrfToken; ?>">
                                    <td><input name="setting_key" class="form-control form-control-premium" placeholder="new_setting_key" required></td>
                                    <td><textarea name="setting_value" class="form-control form-control-premium" placeholder="Value..." required></textarea></td>
                                    <td><button type="submit" class="btn btn-success btn-sm">Add Setting</button></td>
                                </form>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
          </div>
        <?php endif; ?>

        <?php if ($tab === 'audit'): 
          $auditLogs = ReportController::listActivityLogs(50);
          $loginHistory = ReportController::listLoginHistory(50);
        ?>
          <div id="audit-view">
            <h2 class="fw-bold mb-4"><i class="fas fa-clipboard-list text-gold me-2"></i> Audit & Login history</h2>
            
            <div class="row g-4">
              <!-- Left: Activity Logs -->
              <div class="col-lg-6">
                <div class="glass-panel p-4">
                  <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="fw-bold m-0 text-gold">Admin Activity Log</h4>
                    <a href="api.php?action=export_report&type=users" class="btn btn-sm btn-outline-info"><i class="fas fa-download"></i> CSV</a>
                  </div>
                  <div class="table-responsive table-responsive-premium" style="max-height: 500px; overflow-y: auto;">
                    <table class="table small">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Admin</th>
                                <th>Action</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($auditLogs as $log): ?>
                                <tr>
                                    <td><?php echo $log['created_at']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($log['username'] ?? 'System'); ?></strong></td>
                                    <td><span class="badge bg-primary"><?php echo htmlspecialchars($log['action']); ?></span></td>
                                    <td><code style="word-break: break-all;"><?php echo htmlspecialchars($log['after_state'] ?? $log['target'] ?? ''); ?></code></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                  </div>
                </div>
              </div>

              <!-- Right: Login History -->
              <div class="col-lg-6">
                <div class="glass-panel p-4">
                  <h4 class="fw-bold mb-3 text-gold">Admin Login Audits</h4>
                  <div class="table-responsive table-responsive-premium" style="max-height: 500px; overflow-y: auto;">
                    <table class="table small">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Admin User</th>
                                <th>IP Address</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($loginHistory as $lh): ?>
                                <tr>
                                    <td><?php echo $lh['created_at']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($lh['username'] ?? 'User ID: ' . $lh['admin_id']); ?></strong></td>
                                    <td><code><?php echo htmlspecialchars($lh['ip_address']); ?></code></td>
                                    <td>
                                        <?php 
                                            $isOk = (strpos(strtolower($lh['status']), 'success') !== false);
                                            $badgeClass = $isOk ? 'bg-success' : 'bg-danger';
                                        ?>
                                        <span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($lh['status']); ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>
        <?php endif; ?>

        <!-- NEW COLLAPSIBLE TABS VIEWPORTS -->

        <!-- 1. Generic Game Manager View -->
        <?php 
          $isGameTab = (strpos($tab, 'wingo_') === 0 || strpos($tab, 'k3_') === 0 || strpos($tab, 'd5_') === 0);
          if ($isGameTab): 
            $parts = explode('_', $tab);
            $base = $parts[0] === 'd5' ? 'D5' : ($parts[0] === 'k3' ? 'K3' : 'WinGo');
            $interval = strtoupper($parts[1]);
            $gameCode = $base . '_' . $interval;
        ?>
          <div id="game-manager-view" data-game-code="<?php echo htmlspecialchars($gameCode); ?>">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold m-0"><i class="fas fa-gamepad text-gold me-2"></i> <?php echo htmlspecialchars(str_replace('_', ' ', $gameCode)); ?> Control Desk</h2>
                <span class="badge bg-danger fs-6 py-2 px-3"><span id="game-timer">00:00</span></span>
            </div>

            <div class="row g-4">
              <!-- Left Column: Current Issue and Override -->
              <div class="col-lg-5">
                <div class="glass-panel p-4 mb-4">
                  <h4 class="fw-bold mb-3 text-gold">Current Issue Details</h4>
                  <div class="p-3 mb-3 border rounded bg-dark border-secondary">
                    <span class="text-secondary small d-block">ACTIVE PERIOD NUMBER</span>
                    <strong class="fs-4 text-white" id="game-active-issue">--</strong>
                    <span class="text-muted d-block small mt-2">Next Forced Outcome: <span id="game-forced-status" class="text-warning fw-bold">None</span></span>
                  </div>

                  <h4 class="fw-bold mb-3 text-gold">Override Result</h4>
                  <form id="form-game-override" class="row g-2">
                    <div class="col-8">
                       <input name="premium" id="override-premium-value" class="form-control form-control-premium" placeholder="e.g. 5, Green, Big" required>
                    </div>
                    <div class="col-4 d-flex gap-2">
                       <button type="submit" class="btn btn-premium w-100 justify-content-center">SET</button>
                       <button type="button" id="btn-unset-override" class="btn btn-secondary-premium">UNSET</button>
                    </div>
                  </form>
                </div>

                <div class="glass-panel p-4">
                  <h4 class="fw-bold mb-3 text-gold">Recently Completed Periods</h4>
                  <div class="table-responsive table-responsive-premium">
                    <table class="table small" id="game-history-table">
                      <thead>
                        <tr>
                          <th>Period</th>
                          <th>Result Value</th>
                          <th>Colors / Sum</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr><td colspan="3" class="text-center text-muted">Loading history...</td></tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>

              <!-- Right Column: Bets pool & Live Bets list -->
              <div class="col-lg-7">
                <div class="glass-panel p-4 mb-4">
                  <div class="d-flex justify-content-between align-items-center">
                    <div>
                      <span class="text-secondary small">ACTIVE STAKES POOL</span>
                      <h3 class="fw-bold text-success m-0" id="game-total-pool">₹0.00</h3>
                    </div>
                    <i class="fas fa-coins text-gold fs-2"></i>
                  </div>
                </div>

                <div class="glass-panel p-4">
                  <h4 class="fw-bold mb-3 text-gold">Stakes Placed in Current Period</h4>
                  <div class="table-responsive table-responsive-premium">
                    <table class="table small" id="game-bets-table">
                      <thead>
                        <tr>
                          <th>Player ID</th>
                          <th>Username</th>
                          <th>Bet Option</th>
                          <th>Amount</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr><td colspan="4" class="text-center text-muted">No active stakes</td></tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>
        <?php endif; ?>

        <!-- 2. Gift Code Manager -->
        <?php if ($tab === 'gift_code'): ?>
          <div id="gift-code-view">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold m-0"><i class="fas fa-gift text-gold me-2"></i> Gift Code Manager</h2>
                <button class="btn-premium" data-bs-toggle="modal" data-bs-target="#modal-add-gift-code">
                    <i class="fas fa-plus"></i> Create Gift Code
                </button>
            </div>
            
            <div class="glass-panel p-4">
                <div class="table-responsive table-responsive-premium">
                    <table class="table w-100" id="gift-codes-table">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Prize Amount</th>
                                <th>Max Redeem</th>
                                <th>Redeemed Count</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Loaded via AJAX -->
                        </tbody>
                    </table>
                </div>
                <div class="text-muted small mt-3">
                    <i class="fas fa-info-circle me-1"></i> Members redeem these codes on the site's <strong>Gift / Reward Redemption Code</strong> page. Every successful claim is credited instantly and appears below.
                </div>
            </div>

            <div class="glass-panel p-4 mt-4">
                <h4 class="fw-bold mb-3 text-gold"><i class="fas fa-history me-2"></i> Redemption Log</h4>
                <div class="table-responsive table-responsive-premium">
                    <table class="table align-middle" id="gift-redemptions-table">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Member</th>
                                <th>Amount</th>
                                <th>Redeemed At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td colspan="4" class="text-center text-muted">Loading redemptions...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Modal: Add Gift Code -->
            <div class="modal fade" id="modal-add-gift-code" tabindex="-1">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content modal-content-premium text-white">
                  <div class="modal-header modal-header-premium">
                    <h5 class="modal-title fw-bold"><i class="fas fa-gift text-gold me-2"></i> Generate Gift Code</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                  </div>
                  <form id="form-add-gift-code">
                    <input type="hidden" name="action" value="save_gift_code">
                    <div class="modal-body p-4">
                      <div class="mb-3">
                        <label class="form-label text-secondary small fw-bold">Gift Code String</label>
                        <input name="code" class="form-control form-control-premium" placeholder="e.g. WELCOME500" required>
                      </div>
                      <div class="mb-3">
                        <label class="form-label text-secondary small fw-bold">Prize Value (₹)</label>
                        <input type="number" name="prize_amount" step="0.01" class="form-control form-control-premium" placeholder="e.g. 50" required>
                      </div>
                      <div class="mb-3">
                        <label class="form-label text-secondary small fw-bold">Max Redeem Limits</label>
                        <input type="number" name="max_redeem" class="form-control form-control-premium" value="1" required>
                      </div>
                    </div>
                    <div class="modal-footer modal-footer-premium">
                      <button type="button" class="btn-secondary-premium" data-bs-dismiss="modal">Cancel</button>
                      <button type="submit" class="btn-premium">Create Code</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          </div>
        <?php endif; ?>

        <!-- 3. Same IP Checker -->
        <?php if ($tab === 'check_same_ip'): ?>
          <div id="check-same-ip-view">
            <h2 class="fw-bold mb-4"><i class="fas fa-shield-alt text-gold me-2"></i> Duplicate IP Account Audits</h2>
            <div class="glass-panel p-4">
                <div class="table-responsive table-responsive-premium">
                    <table class="table w-100" id="same-ip-table">
                        <thead>
                            <tr>
                                <th>IP Address</th>
                                <th>Shared Users Count</th>
                                <th>Associated Accounts</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Loaded via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
          </div>
        <?php endif; ?>

        <!-- 4. Daily Salary View -->
        <?php if ($tab === 'daily_salary'): ?>
          <div id="daily-salary-view">
            <h2 class="fw-bold mb-4"><i class="fas fa-money-bill-wave text-gold me-2"></i> Daily Salary Manager</h2>
            <div class="glass-panel p-4">
                <h4 class="fw-bold mb-3 text-gold">Promoter Daily Salary Settings</h4>
                <form action="api.php" method="post" class="row g-3">
                    <input type="hidden" name="action" value="save_setting">
                    <input type="hidden" name="csrf" value="<?php echo $csrfToken; ?>">
                    
                    <div class="col-md-6">
                        <label class="form-label text-secondary small fw-bold">Active Members Requirement (Recharge >= ₹500)</label>
                        <?php $req = api_setting('salary_req_members', '5'); ?>
                        <input name="setting_value" class="form-control form-control-premium" value="<?php echo htmlspecialchars($req); ?>">
                        <input type="hidden" name="setting_key" value="salary_req_members">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-secondary small fw-bold">Daily Salary Payout (₹)</label>
                        <?php $pay = api_setting('salary_payout_amount', '500'); ?>
                        <input name="setting_value" class="form-control form-control-premium" value="<?php echo htmlspecialchars($pay); ?>">
                        <input type="hidden" name="setting_key" value="salary_payout_amount">
                    </div>
                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-premium">Save Salary Configuration</button>
                    </div>
                </form>
            </div>
          </div>
        <?php endif; ?>

        <!-- 5. Site Maintenance View -->
        <?php if ($tab === 'site_maintenance'): ?>
          <div id="site-maintenance-view">
            <h2 class="fw-bold mb-4"><i class="fas fa-tools text-gold me-2"></i> Site Maintenance Controls</h2>
            <div class="glass-panel p-4 text-center">
                <div class="mb-4">
                    <i class="fas fa-power-off text-danger" style="font-size: 64px;"></i>
                </div>
                <h4 class="fw-bold mb-2">Emergency Under Maintenance Toggle</h4>
                <p class="text-secondary mb-4 mx-auto" style="max-width: 500px;">
                    Activating Site Maintenance will temporarily block all game bets and display a maintenance splash screen to all regular members.
                </p>
                
                <?php $maint = api_setting_bool('site_maintenance', false); ?>
                <button type="button" id="btn-toggle-maintenance" data-enabled="<?php echo $maint ? '1' : '0'; ?>" 
                        class="btn <?php echo $maint ? 'btn-danger' : 'btn-success'; ?> btn-lg px-5 py-3 fw-bold">
                    <?php echo $maint ? 'DISABLE SITE MAINTENANCE (LIVE)' : 'ENABLE SITE MAINTENANCE (OFFLINE)'; ?>
                </button>
            </div>
          </div>
        <?php endif; ?>

        <!-- ADD ADMIN / SUB-ADMIN MANAGEMENT PANEL -->
        <?php if ($tab === 'add_admin'): ?>
          <?php
            $adminsList = AuthController::listAdmins();
            $allPerms = [
              'dashboard' => ['label' => 'Dashboard & Metrics', 'icon' => 'fa-chart-line', 'color' => 'primary', 'desc' => 'View operational stats, live bets & revenue charts'],
              'finance' => ['label' => 'Deposit & Withdrawal (Finance)', 'icon' => 'fa-money-bill-wave', 'color' => 'success', 'desc' => 'Approve/reject recharges, bank/UPI withdrawals & adjust balance'],
              'user_management' => ['label' => 'User Management', 'icon' => 'fa-users', 'color' => 'info', 'desc' => 'View member profiles, ban/unban, search phone numbers & demo users'],
              'game_control' => ['label' => 'Game Control & Live Bets', 'icon' => 'fa-gamepad', 'color' => 'warning', 'desc' => 'Live betting monitor & manual draw predictions (WinGo, K3, 5D)'],
              'support' => ['label' => 'Customer Support Tickets', 'icon' => 'fa-headset', 'color' => 'primary', 'desc' => 'Manage member helpdesk inquiries, deposit issues & replies'],
              'settings' => ['label' => 'Gift Codes & Settings', 'icon' => 'fa-gift', 'color' => 'danger', 'desc' => 'Create redeemable gift codes, manage daily salary & UPI/USDT channels'],
              'agent_management' => ['label' => 'Agent & Affiliate Tree', 'icon' => 'fa-sitemap', 'color' => 'info', 'desc' => 'View promoter hierarchies, invitation links & commission payouts'],
              'security' => ['label' => 'Multi-Account IP Security', 'icon' => 'fa-shield-alt', 'color' => 'secondary', 'desc' => 'Detect duplicate accounts using same IP & block fraud users'],
              'user_control' => ['label' => 'Targeted User Win-Rate Control', 'icon' => 'fa-bullseye', 'color' => 'danger', 'desc' => 'Force specific win/loss percentage on individual high-roller players'],
              'reports' => ['label' => 'Audit Logs & CSV Export', 'icon' => 'fa-file-export', 'color' => 'light', 'desc' => 'Download Excel/CSV reports of users, deposits & view admin activity']
            ];
          ?>
          <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
              <h2 class="fw-bold mb-1"><i class="fas fa-user-shield text-gold me-2"></i> Admin & Sub-Admin Role Management</h2>
              <p class="text-secondary mb-0">Create staff sub-admins and assign specific custom permissions (Deposits, Withdrawals, Game Control, Support, etc.).</p>
            </div>
            <button type="button" class="btn btn-premium" data-bs-toggle="modal" data-bs-target="#createAdminModal">
              <i class="fas fa-user-plus me-2"></i> Create New Sub-Admin
            </button>
          </div>

          <!-- Admins List Panel -->
          <div class="glass-panel p-4 mb-4">
            <h5 class="fw-bold text-white mb-3"><i class="fas fa-users-cog text-gold me-2"></i> Active System Administrators & Staff Accounts</h5>
            <div class="table-responsive">
              <table class="table table-dark table-hover align-middle mb-0">
                <thead>
                  <tr class="text-secondary small text-uppercase">
                    <th>ID</th>
                    <th>Staff / Admin</th>
                    <th>Role Type</th>
                    <th>Assigned Permissions</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th class="text-end">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($adminsList)): ?>
                    <tr>
                      <td colspan="7" class="text-center text-muted py-4">No admin accounts found.</td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($adminsList as $adm): ?>
                      <?php 
                        $isSuper = !empty($adm['is_super_admin']); 
                        $permKeys = [];
                        if (!$isSuper && is_array($adm['permissions'])) {
                          foreach ($adm['permissions'] as $p) {
                            $permKeys[] = is_array($p) ? ($p['permission_key'] ?? '') : (string)$p;
                          }
                        }
                      ?>
                      <tr>
                        <td class="fw-bold text-muted">#<?php echo (int)$adm['id']; ?></td>
                        <td>
                          <div class="d-flex align-items-center gap-2">
                            <div class="brand-logo" style="width: 32px; height: 32px; font-size: 14px; border-radius: 8px;">
                              <i class="fas fa-user-shield"></i>
                            </div>
                            <div>
                              <div class="fw-bold text-white"><?php echo htmlspecialchars($adm['username']); ?></div>
                              <?php if (!empty($adm['email'])): ?>
                                <small class="text-muted"><?php echo htmlspecialchars($adm['email']); ?></small>
                              <?php endif; ?>
                            </div>
                          </div>
                        </td>
                        <td>
                          <?php if ($isSuper): ?>
                            <span class="badge bg-warning text-dark fw-bold px-2 py-1"><i class="fas fa-crown me-1"></i> Super Admin</span>
                          <?php else: ?>
                            <span class="badge bg-primary text-white fw-bold px-2 py-1"><i class="fas fa-id-badge me-1"></i> Sub-Admin / Staff</span>
                          <?php endif; ?>
                        </td>
                        <td>
                          <?php if ($isSuper): ?>
                            <span class="badge bg-success bg-opacity-25 text-success border border-success px-2 py-1">
                              <i class="fas fa-check-double me-1"></i> Full Unrestricted Access (All Permissions)
                            </span>
                          <?php elseif (empty($permKeys)): ?>
                            <span class="badge bg-secondary text-muted px-2 py-1">No permissions assigned</span>
                          <?php else: ?>
                            <div class="d-flex flex-wrap gap-1" style="max-width: 380px;">
                              <?php foreach ($permKeys as $pk): ?>
                                <?php if (isset($allPerms[$pk])): ?>
                                  <span class="badge bg-<?php echo $allPerms[$pk]['color']; ?> bg-opacity-20 text-<?php echo $allPerms[$pk]['color']; ?> border border-<?php echo $allPerms[$pk]['color']; ?> px-2 py-1" style="font-size: 11px;">
                                    <i class="fas <?php echo $allPerms[$pk]['icon']; ?> me-1"></i> <?php echo htmlspecialchars($allPerms[$pk]['label']); ?>
                                  </span>
                                <?php endif; ?>
                              <?php endforeach; ?>
                            </div>
                          <?php endif; ?>
                        </td>
                        <td>
                          <?php if ((int)$adm['status'] === 1): ?>
                            <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i> Active</span>
                          <?php else: ?>
                            <span class="badge bg-danger"><i class="fas fa-ban me-1"></i> Disabled</span>
                          <?php endif; ?>
                        </td>
                        <td class="small text-muted"><?php echo htmlspecialchars(substr((string)$adm['created_at'], 0, 16)); ?></td>
                        <td class="text-end">
                          <div class="btn-group">
                            <?php if (!$isSuper): ?>
                              <button type="button" class="btn btn-sm btn-outline-info btn-edit-perms" 
                                      data-id="<?php echo (int)$adm['id']; ?>" 
                                      data-username="<?php echo htmlspecialchars($adm['username']); ?>"
                                      data-perms='<?php echo json_encode($permKeys); ?>'
                                      title="Edit Permissions">
                                <i class="fas fa-shield-alt"></i> Permissions
                              </button>
                            <?php endif; ?>
                            <button type="button" class="btn btn-sm btn-outline-warning btn-reset-admin-pwd" 
                                    data-id="<?php echo (int)$adm['id']; ?>" 
                                    data-username="<?php echo htmlspecialchars($adm['username']); ?>"
                                    title="Reset Password">
                              <i class="fas fa-key"></i> Password
                            </button>
                            <?php if ((int)$adm['id'] !== 1 && (int)$adm['id'] !== (int)($_SESSION['admin_id'] ?? 0)): ?>
                              <button type="button" class="btn btn-sm <?php echo (int)$adm['status'] === 1 ? 'btn-outline-secondary' : 'btn-outline-success'; ?> btn-toggle-admin-status" 
                                      data-id="<?php echo (int)$adm['id']; ?>" 
                                      title="<?php echo (int)$adm['status'] === 1 ? 'Disable Account' : 'Enable Account'; ?>">
                                <i class="fas <?php echo (int)$adm['status'] === 1 ? 'fa-user-slash' : 'fa-user-check'; ?>"></i>
                              </button>
                              <button type="button" class="btn btn-sm btn-outline-danger btn-delete-admin" 
                                      data-id="<?php echo (int)$adm['id']; ?>" 
                                      data-username="<?php echo htmlspecialchars($adm['username']); ?>"
                                      title="Delete Staff Account">
                                <i class="fas fa-trash-alt"></i>
                              </button>
                            <?php endif; ?>
                          </div>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>

          <!-- CREATE ADMIN MODAL -->
          <div class="modal fade" id="createAdminModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
              <div class="modal-content glass-panel border-0 text-white" style="background: #0f132a;">
                <div class="modal-header border-bottom border-secondary">
                  <h5 class="modal-title fw-bold text-gold"><i class="fas fa-user-plus me-2"></i> Create New Sub-Admin / Staff Account</h5>
                  <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="form-create-admin">
                  <div class="modal-body p-4">
                    <div class="row g-3 mb-4">
                      <div class="col-md-6">
                        <label class="form-label text-secondary small fw-bold">Admin Username *</label>
                        <input type="text" name="username" class="form-control form-control-premium" placeholder="e.g. staff_rahul" required autocomplete="off">
                      </div>
                      <div class="col-md-6">
                        <label class="form-label text-secondary small fw-bold">Login Password *</label>
                        <input type="password" name="password" class="form-control form-control-premium" placeholder="Enter secure password" required autocomplete="new-password">
                      </div>
                      <div class="col-md-6">
                        <label class="form-label text-secondary small fw-bold">Email or Description (Optional)</label>
                        <input type="text" name="email" class="form-control form-control-premium" placeholder="e.g. rahul@staff.dhani.win">
                      </div>
                      <div class="col-md-6">
                        <label class="form-label text-secondary small fw-bold">Account Role Type</label>
                        <select name="role_id" class="form-select" id="create-admin-role-select">
                          <option value="2" selected>Sub-Admin / Staff (Custom Permissions)</option>
                          <option value="1">Super Admin (Full Unrestricted Access)</option>
                        </select>
                      </div>
                    </div>

                    <!-- Custom Permissions Selection -->
                    <div id="create-perms-wrapper">
                      <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 pb-2 border-bottom border-secondary">
                        <div class="fw-bold text-gold"><i class="fas fa-key me-2"></i> Select Sub-Admin Permissions</div>
                        <div class="d-flex gap-2">
                          <button type="button" class="btn btn-sm btn-outline-success btn-preset" data-preset="finance">💰 Finance Only</button>
                          <button type="button" class="btn btn-sm btn-outline-info btn-preset" data-preset="support">💬 Support Only</button>
                          <button type="button" class="btn btn-sm btn-outline-warning btn-preset" data-preset="game">🎮 Games Only</button>
                          <button type="button" class="btn btn-sm btn-outline-light btn-preset" data-preset="all">⚡ Select All</button>
                          <button type="button" class="btn btn-sm btn-outline-secondary btn-preset" data-preset="none">✕ Clear</button>
                        </div>
                      </div>

                      <div class="row g-3">
                        <?php foreach ($allPerms as $pk => $pval): ?>
                          <div class="col-md-6">
                            <label class="glass-panel p-3 d-flex align-items-start gap-3 w-100 mb-0 cursor-pointer" style="border-radius: 12px; cursor: pointer;">
                              <input class="form-check-input perm-checkbox mt-1" type="checkbox" name="permissions[]" value="<?php echo $pk; ?>">
                              <div>
                                <div class="fw-bold text-white"><i class="fas <?php echo $pval['icon']; ?> text-<?php echo $pval['color']; ?> me-2"></i> <?php echo htmlspecialchars($pval['label']); ?></div>
                                <div class="text-secondary small mt-1"><?php echo htmlspecialchars($pval['desc']); ?></div>
                              </div>
                            </label>
                          </div>
                        <?php endforeach; ?>
                      </div>
                    </div>
                  </div>
                  <div class="modal-footer border-top border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-premium px-4">Create Sub-Admin Account <i class="fas fa-check ms-1"></i></button>
                  </div>
                </form>
              </div>
            </div>
          </div>

          <!-- EDIT PERMISSIONS MODAL -->
          <div class="modal fade" id="editPermsModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
              <div class="modal-content glass-panel border-0 text-white" style="background: #0f132a;">
                <div class="modal-header border-bottom border-secondary">
                  <h5 class="modal-title fw-bold text-gold"><i class="fas fa-shield-alt me-2"></i> Edit Permissions: <span id="edit-perms-username" class="text-white"></span></h5>
                  <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="form-edit-perms">
                  <input type="hidden" name="admin_id" id="edit-perms-admin-id">
                  <div class="modal-body p-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 pb-2 border-bottom border-secondary">
                      <div class="text-secondary small">Toggle permissions for this sub-admin staff member:</div>
                      <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-success btn-preset-edit" data-preset="finance">💰 Finance</button>
                        <button type="button" class="btn btn-sm btn-outline-info btn-preset-edit" data-preset="support">💬 Support</button>
                        <button type="button" class="btn btn-sm btn-outline-warning btn-preset-edit" data-preset="game">🎮 Games</button>
                        <button type="button" class="btn btn-sm btn-outline-light btn-preset-edit" data-preset="all">⚡ Select All</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary btn-preset-edit" data-preset="none">✕ Clear</button>
                      </div>
                    </div>

                    <div class="row g-3">
                      <?php foreach ($allPerms as $pk => $pval): ?>
                        <div class="col-md-6">
                          <label class="glass-panel p-3 d-flex align-items-start gap-3 w-100 mb-0 cursor-pointer" style="border-radius: 12px; cursor: pointer;">
                            <input class="form-check-input edit-perm-checkbox mt-1" type="checkbox" name="permissions[]" value="<?php echo $pk; ?>" id="edit-perm-<?php echo $pk; ?>">
                            <div>
                              <div class="fw-bold text-white"><i class="fas <?php echo $pval['icon']; ?> text-<?php echo $pval['color']; ?> me-2"></i> <?php echo htmlspecialchars($pval['label']); ?></div>
                              <div class="text-secondary small mt-1"><?php echo htmlspecialchars($pval['desc']); ?></div>
                            </div>
                          </label>
                        </div>
                      <?php endforeach; ?>
                    </div>
                  </div>
                  <div class="modal-footer border-top border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-premium px-4">Save Permissions <i class="fas fa-save ms-1"></i></button>
                  </div>
                </form>
              </div>
            </div>
          </div>

          <!-- RESET PASSWORD MODAL -->
          <div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
              <div class="modal-content glass-panel border-0 text-white" style="background: #0f132a;">
                <div class="modal-header border-bottom border-secondary">
                  <h5 class="modal-title fw-bold text-gold"><i class="fas fa-key me-2"></i> Reset Password: <span id="reset-pwd-username" class="text-white"></span></h5>
                  <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="form-reset-admin-pwd">
                  <input type="hidden" name="admin_id" id="reset-pwd-admin-id">
                  <div class="modal-body p-4">
                    <div class="mb-3">
                      <label class="form-label text-secondary small fw-bold">New Secret Password *</label>
                      <input type="password" name="new_password" class="form-control form-control-premium" placeholder="Enter new password" required autocomplete="new-password">
                    </div>
                  </div>
                  <div class="modal-footer border-top border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning px-4 fw-bold">Update Password <i class="fas fa-check ms-1"></i></button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        <?php endif; ?>

        <!-- ============================================================= -->
        <!-- DEMO USER MANAGER                                            -->
        <!-- ============================================================= -->
        <?php if ($tab === 'demo_user'): ?>
          <div id="demo-user-view">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
              <div>
                <h2 class="fw-bold mb-1"><i class="fas fa-user-astronaut text-gold me-2"></i> Demo User Manager</h2>
                <p class="text-secondary mb-0">Create test / demo accounts with a demo balance in one click. Demo users can login on the site with the username and password entered below.</p>
              </div>
              <a href="/admin/?tab=users" class="btn btn-secondary-premium"><i class="fas fa-users me-1"></i> All Members</a>
            </div>

            <div class="row g-4">
              <div class="col-xl-5">
                <div class="glass-panel p-4 h-100">
                  <h4 class="fw-bold mb-3 text-gold"><i class="fas fa-user-plus me-2"></i> Create Demo User</h4>
                  <form action="api.php" method="post" class="row g-3">
                    <input type="hidden" name="action" value="create_demo_user">
                    <input type="hidden" name="csrf" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="return_tab" value="demo_user">
                    <div class="col-md-6">
                      <label class="form-label text-secondary small fw-bold">Username / Phone *</label>
                      <input name="username" class="form-control form-control-premium" placeholder="e.g. demo001" required>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label text-secondary small fw-bold">Password</label>
                      <input name="password" class="form-control form-control-premium" placeholder="demo123 (default)">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label text-secondary small fw-bold">Nickname</label>
                      <input name="nickname" class="form-control form-control-premium" placeholder="Demo Account">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label text-secondary small fw-bold">Phone</label>
                      <input name="phone" class="form-control form-control-premium" placeholder="Optional">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label text-secondary small fw-bold">Demo Balance (₹)</label>
                      <input type="number" step="0.01" min="0" name="balance" class="form-control form-control-premium" value="10000">
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                      <button type="submit" class="btn btn-premium w-100 justify-content-center"><i class="fas fa-bolt me-1"></i> Create Demo User</button>
                    </div>
                    <div class="col-12">
                      <div class="text-muted small"><i class="fas fa-info-circle me-1"></i> The demo balance is credited to the wallet and game balance together, so the site shows it everywhere.</div>
                    </div>
                  </form>
                </div>
              </div>

              <div class="col-xl-7">
                <div class="glass-panel p-4 h-100">
                  <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="fw-bold m-0 text-gold"><i class="fas fa-list me-2"></i> Existing Demo Users</h4>
                    <button type="button" class="btn btn-sm btn-secondary-premium" id="btn-refresh-demo-users"><i class="fas fa-sync"></i></button>
                  </div>
                  <div class="table-responsive table-responsive-premium">
                    <table class="table align-middle" id="demo-users-table">
                      <thead>
                        <tr>
                          <th>Username</th>
                          <th>Nickname</th>
                          <th>Phone</th>
                          <th>Balance</th>
                          <th>Status</th>
                          <th class="text-end">Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr><td colspan="6" class="text-center text-muted">Loading demo users...</td></tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>
        <?php endif; ?>

        <!-- ============================================================= -->
        <!-- AGENT USER MANAGER                                           -->
        <!-- ============================================================= -->
        <?php if ($tab === 'agent_user'): ?>
          <div id="agent-user-view">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
              <div>
                <h2 class="fw-bold mb-1"><i class="fas fa-user-tie text-gold me-2"></i> Agent / Promoter Users</h2>
                <p class="text-secondary mb-0">Create promoter accounts and set their commission rate. Agents earn commission when their invited players bet.</p>
              </div>
              <a href="/admin/?tab=agents" class="btn btn-secondary-premium"><i class="fas fa-sitemap me-1"></i> Agent Trees &amp; Commissions</a>
            </div>

            <div class="row g-4">
              <div class="col-xl-5">
                <div class="glass-panel p-4 h-100">
                  <h4 class="fw-bold mb-3 text-gold"><i class="fas fa-user-plus me-2"></i> Create Agent User</h4>
                  <form action="api.php" method="post" class="row g-3">
                    <input type="hidden" name="action" value="create_agent_user">
                    <input type="hidden" name="csrf" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="return_tab" value="agent_user">
                    <div class="col-md-6">
                      <label class="form-label text-secondary small fw-bold">Username / Phone *</label>
                      <input name="username" class="form-control form-control-premium" placeholder="e.g. agent_rahul" required>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label text-secondary small fw-bold">Password</label>
                      <input name="password" class="form-control form-control-premium" placeholder="agent123 (default)">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label text-secondary small fw-bold">Nickname</label>
                      <input name="nickname" class="form-control form-control-premium" placeholder="Agent Account">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label text-secondary small fw-bold">Phone</label>
                      <input name="phone" class="form-control form-control-premium" placeholder="Optional">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label text-secondary small fw-bold">Commission Rate (%)</label>
                      <input type="number" step="0.01" min="0" max="100" name="agent_rate" class="form-control form-control-premium" value="0">
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                      <button type="submit" class="btn btn-premium w-100 justify-content-center"><i class="fas fa-plus me-1"></i> Create Agent</button>
                    </div>
                  </form>
                </div>
              </div>

              <div class="col-xl-7">
                <div class="glass-panel p-4 h-100">
                  <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="fw-bold m-0 text-gold"><i class="fas fa-list me-2"></i> Agent Accounts</h4>
                    <button type="button" class="btn btn-sm btn-secondary-premium" id="btn-refresh-agents"><i class="fas fa-sync"></i></button>
                  </div>
                  <div class="table-responsive table-responsive-premium">
                    <table class="table align-middle" id="agents-table">
                      <thead>
                        <tr>
                          <th>Username</th>
                          <th>Phone</th>
                          <th>Rate</th>
                          <th>Team</th>
                          <th>Commission</th>
                          <th class="text-end">Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr><td colspan="6" class="text-center text-muted">Loading agents...</td></tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>
        <?php endif; ?>

        <!-- ============================================================= -->
        <!-- BONUS MANAGE                                                 -->
        <!-- ============================================================= -->
        <?php if ($tab === 'bonus_manage'): ?>
          <div id="bonus-manage-view">
            <h2 class="fw-bold mb-4"><i class="fas fa-gift text-gold me-2"></i> Bonus Manage</h2>

            <div class="row g-4">
              <div class="col-lg-6">
                <div class="glass-panel p-4 h-100">
                  <h4 class="fw-bold mb-3 text-gold"><i class="fas fa-arrow-alt-circle-down me-2"></i> First Deposit Bonus</h4>
                  <p class="text-secondary small">This bonus is added automatically when the first deposit of a member is approved from the Deposit Update page.</p>
                  <form action="api.php" method="post" class="row g-3">
                    <input type="hidden" name="action" value="save_setting">
                    <input type="hidden" name="csrf" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="return_tab" value="bonus_manage">
                    <input type="hidden" name="setting_key" value="first_recharge_bonus_enabled">
                    <div class="col-12">
                      <label class="form-label text-secondary small fw-bold">Bonus Status</label>
                      <?php $bonusOn = api_setting('first_recharge_bonus_enabled', '1'); ?>
                      <select name="setting_value" class="form-control form-control-premium">
                        <option value="1" <?php echo $bonusOn === '1' ? 'selected' : ''; ?>>Enabled</option>
                        <option value="0" <?php echo $bonusOn !== '1' ? 'selected' : ''; ?>>Disabled</option>
                      </select>
                    </div>
                    <div class="col-12">
                      <button type="submit" class="btn btn-premium btn-sm">Save Bonus Status</button>
                    </div>
                  </form>

                  <hr class="border-secondary">

                  <form action="api.php" method="post" class="row g-3">
                    <input type="hidden" name="action" value="save_setting">
                    <input type="hidden" name="csrf" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="return_tab" value="bonus_manage">
                    <div class="col-md-6">
                      <label class="form-label text-secondary small fw-bold">Bonus Percent (%)</label>
                      <input type="number" step="0.01" min="0" name="setting_value" class="form-control form-control-premium" value="<?php echo htmlspecialchars(api_setting('first_recharge_bonus_percent', '10')); ?>">
                      <input type="hidden" name="setting_key" value="first_recharge_bonus_percent">
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                      <button type="submit" class="btn btn-premium btn-sm w-100">Save Percent</button>
                    </div>
                  </form>

                  <form action="api.php" method="post" class="row g-3 mt-1">
                    <input type="hidden" name="action" value="save_setting">
                    <input type="hidden" name="csrf" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="return_tab" value="bonus_manage">
                    <div class="col-md-6">
                      <label class="form-label text-secondary small fw-bold">Maximum Bonus (₹)</label>
                      <input type="number" step="0.01" min="0" name="setting_value" class="form-control form-control-premium" value="<?php echo htmlspecialchars(api_setting('first_recharge_bonus_max', '500')); ?>">
                      <input type="hidden" name="setting_key" value="first_recharge_bonus_max">
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                      <button type="submit" class="btn btn-premium btn-sm w-100">Save Maximum</button>
                    </div>
                  </form>
                </div>
              </div>

              <div class="col-lg-6">
                <div class="glass-panel p-4 h-100">
                  <h4 class="fw-bold mb-3 text-gold"><i class="fas fa-sitemap me-2"></i> Agent / Invite Commission</h4>
                  <form action="api.php" method="post" class="row g-3">
                    <input type="hidden" name="action" value="save_setting">
                    <input type="hidden" name="csrf" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="return_tab" value="bonus_manage">
                    <input type="hidden" name="setting_key" value="agent_rebate_enabled">
                    <div class="col-12">
                      <label class="form-label text-secondary small fw-bold">Agent Commission Status</label>
                      <?php $agentOn = api_setting('agent_rebate_enabled', '1'); ?>
                      <select name="setting_value" class="form-control form-control-premium">
                        <option value="1" <?php echo $agentOn === '1' ? 'selected' : ''; ?>>Enabled</option>
                        <option value="0" <?php echo $agentOn !== '1' ? 'selected' : ''; ?>>Disabled</option>
                      </select>
                    </div>
                    <div class="col-12">
                      <button type="submit" class="btn btn-premium btn-sm">Save Agent Status</button>
                    </div>
                  </form>

                  <hr class="border-secondary">

                  <h5 class="fw-bold text-white mb-2">First Deposit Bonus Preview</h5>
                  <?php $bonusPercentPreview = (float) api_setting('first_recharge_bonus_percent', '10'); $bonusMaxPreview = (float) api_setting('first_recharge_bonus_max', '500'); ?>
                  <p class="text-secondary small mb-2">See what a member receives on their first deposit:</p>
                  <div id="bonus-preview-box" data-bonus-percent="<?php echo (float)$bonusPercentPreview; ?>" data-bonus-max="<?php echo (float)$bonusMaxPreview; ?>">
                  <div class="input-group mb-2">
                    <span class="input-group-text border-0" style="background: rgba(7,9,19,0.6);">₹</span>
                    <input type="number" id="bonus-preview-amount" class="form-control form-control-premium" value="500">
                  </div>
                  <div class="p-3 rounded" style="background: rgba(7,9,19,0.4); border: 1px solid var(--border-light);">
                    <div class="d-flex justify-content-between"><span class="text-secondary small">Deposit</span><span class="fw-bold text-white" id="bonus-preview-base">₹500.00</span></div>
                    <div class="d-flex justify-content-between"><span class="text-secondary small">Bonus</span><span class="fw-bold text-success" id="bonus-preview-bonus">₹50.00</span></div>
                    <div class="d-flex justify-content-between border-top border-secondary mt-2 pt-2"><span class="text-secondary small">Total Credit</span><span class="fw-bold text-gold" id="bonus-preview-total">₹550.00</span></div>
                  </div>
                  </div>
                  <div class="text-muted small mt-2">Current rule:
                    <strong class="text-white"><?php echo (int) api_setting('first_recharge_bonus_percent', '10'); ?>%</strong> of the deposit, capped at
                    <strong class="text-white">₹<?php echo number_format((float) api_setting('first_recharge_bonus_max', '500'), 2); ?></strong>.
                  </div>
                </div>
              </div>
            </div>
          </div>
        <?php endif; ?>

        <!-- ============================================================= -->
        <!-- ADMIN PASSWORD / OWN PROFILE                                 -->
        <!-- ============================================================= -->
        <?php if ($tab === 'admin_password'): ?>
          <div id="admin-password-view">
            <h2 class="fw-bold mb-4"><i class="fas fa-key text-gold me-2"></i> Admin Password &amp; Profile</h2>

            <div class="row g-4">
              <div class="col-lg-5">
                <div class="glass-panel p-4 h-100">
                  <h4 class="fw-bold mb-3 text-gold"><i class="fas fa-user-circle me-2"></i> My Login Details</h4>
                  <?php $me = admin_db_rows("SELECT u.username, u.email, u.created_at, r.role_label FROM admin_users u LEFT JOIN admin_roles r ON r.id = u.role_id WHERE u.id = ? LIMIT 1", [(int)($_SESSION['admin_id'] ?? 0)]); $me = $me[0] ?? []; ?>
                  <div class="mb-3">
                    <span class="text-secondary small d-block">Username</span>
                    <strong class="text-white"><?php echo htmlspecialchars((string)($me['username'] ?? ($_SESSION['admin_username'] ?? 'admin'))); ?></strong>
                  </div>
                  <div class="mb-3">
                    <span class="text-secondary small d-block">Role</span>
                    <strong class="text-white"><?php echo htmlspecialchars((string)($me['role_label'] ?? 'Super Admin')); ?></strong>
                  </div>
                  <div class="mb-0">
                    <span class="text-secondary small d-block">Account created</span>
                    <strong class="text-white"><?php echo htmlspecialchars(substr((string)($me['created_at'] ?? ''), 0, 16)); ?></strong>
                  </div>
                </div>
              </div>

              <div class="col-lg-7">
                <div class="glass-panel p-4 mb-4">
                  <h4 class="fw-bold mb-3 text-gold"><i class="fas fa-lock me-2"></i> Change My Password</h4>
                  <form action="api.php" method="post" class="row g-3" id="form-own-password">
                    <input type="hidden" name="action" value="change_own_password">
                    <input type="hidden" name="csrf" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="return_tab" value="admin_password">
                    <div class="col-md-4">
                      <label class="form-label text-secondary small fw-bold">Current Password *</label>
                      <input type="password" name="old_password" id="own-old-password" class="form-control form-control-premium" required autocomplete="current-password">
                    </div>
                    <div class="col-md-4">
                      <label class="form-label text-secondary small fw-bold">New Password *</label>
                      <input type="password" name="new_password" id="own-new-password" class="form-control form-control-premium" required minlength="6" autocomplete="new-password">
                    </div>
                    <div class="col-md-4">
                      <label class="form-label text-secondary small fw-bold">Confirm New Password *</label>
                      <input type="password" id="own-confirm-password" class="form-control form-control-premium" required minlength="6" autocomplete="new-password">
                    </div>
                    <div class="col-12">
                      <button type="submit" class="btn btn-premium"><i class="fas fa-save me-1"></i> Update Password</button>
                      <span class="text-muted small ms-2">Minimum 6 characters.</span>
                    </div>
                  </form>
                </div>

                <div class="glass-panel p-4">
                  <h4 class="fw-bold mb-3 text-gold"><i class="fas fa-id-card me-2"></i> Change Username / Email</h4>
                  <form action="api.php" method="post" class="row g-3">
                    <input type="hidden" name="action" value="save_admin_profile">
                    <input type="hidden" name="csrf" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="return_tab" value="admin_password">
                    <div class="col-md-6">
                      <label class="form-label text-secondary small fw-bold">Admin Username *</label>
                      <input name="username" class="form-control form-control-premium" value="<?php echo htmlspecialchars((string)($me['username'] ?? ($_SESSION['admin_username'] ?? 'admin'))); ?>" required>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label text-secondary small fw-bold">Email / Note</label>
                      <input name="email" class="form-control form-control-premium" value="<?php echo htmlspecialchars((string)($me['email'] ?? '')); ?>" placeholder="Optional">
                    </div>
                    <div class="col-12">
                      <button type="submit" class="btn btn-premium"><i class="fas fa-save me-1"></i> Save Profile</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          </div>
        <?php endif; ?>

        <!-- ============================================================= -->
        <!-- BANNED USERS                                                 -->
        <!-- ============================================================= -->
        <?php if ($tab === 'banned_users'): ?>
          <div id="banned-users-view">
            <h2 class="fw-bold mb-4"><i class="fas fa-user-slash text-gold me-2"></i> Banned Users</h2>

            <div class="row g-4">
              <div class="col-xl-4">
                <div class="glass-panel p-4 h-100">
                  <h4 class="fw-bold mb-3 text-gold"><i class="fas fa-ban me-2"></i> Block a User</h4>
                  <form action="api.php" method="post" class="row g-3">
                    <input type="hidden" name="action" value="save_user">
                    <input type="hidden" name="csrf" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="return_tab" value="banned_users">
                    <div class="col-12">
                      <label class="form-label text-secondary small fw-bold">Select User</label>
                      <select name="id" class="form-control form-control-premium" required>
                        <option value="">-- choose member --</option>
                        <?php foreach (admin_db_rows("SELECT id, user_id, username, nickname FROM api_users WHERE can_bet = 1 AND status = 1 ORDER BY id DESC LIMIT 500") as $opt): ?>
                          <option value="<?php echo (int)$opt['id']; ?>"><?php echo htmlspecialchars($opt['username'] . ' (' . $opt['user_id'] . ')'); ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div class="col-12">
                      <label class="form-label text-secondary small fw-bold">Reason</label>
                      <input name="ban_reason" class="form-control form-control-premium" placeholder="e.g. multiple accounts / fraud">
                    </div>
                    <input type="hidden" name="can_bet" value="0">
                    <input type="hidden" name="status" value="0">
                    <div class="col-12">
                      <button type="submit" class="btn btn-danger w-100 fw-bold"><i class="fas fa-user-slash me-1"></i> Ban User</button>
                      <div class="text-muted small mt-2">Banning stops new bets and disables the login of that member.</div>
                    </div>
                  </form>
                </div>
              </div>

              <div class="col-xl-8">
                <div class="glass-panel p-4 h-100">
                  <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="fw-bold m-0 text-gold"><i class="fas fa-list me-2"></i> Blocked Members</h4>
                    <button type="button" class="btn btn-sm btn-secondary-premium" id="btn-refresh-banned"><i class="fas fa-sync"></i></button>
                  </div>
                  <div class="table-responsive table-responsive-premium">
                    <table class="table align-middle" id="banned-users-table">
                      <thead>
                        <tr>
                          <th>Username</th>
                          <th>Player ID</th>
                          <th>Phone</th>
                          <th>Balance</th>
                          <th>Reason</th>
                          <th class="text-end">Action</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr><td colspan="6" class="text-center text-muted">Loading banned users...</td></tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>
        <?php endif; ?>

        <!-- ============================================================= -->
        <!-- ADD UPI                                                      -->
        <!-- ============================================================= -->
        <?php if ($tab === 'add_upi'):
          $upiRows = SettingsController::getPaymentMethods();
        ?>
          <div id="add-upi-view">
            <h2 class="fw-bold mb-2"><i class="fas fa-mobile-alt text-gold me-2"></i> Add UPI Account</h2>
            <p class="text-secondary">UPI accounts listed here are the ones members see on the site's deposit page.</p>

            <div class="glass-panel p-4 mb-4">
              <h4 class="fw-bold mb-3 text-gold"><i class="fas fa-plus me-2"></i> New UPI Account</h4>
              <form action="api.php" method="post" class="row g-3">
                <input type="hidden" name="action" value="save_payment">
                <input type="hidden" name="csrf" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="return_tab" value="add_upi">
                <input type="hidden" name="method_type" value="UPI">
                <div class="col-md-3">
                  <label class="form-label text-secondary small fw-bold">Display Name *</label>
                  <input name="method_name" class="form-control form-control-premium" placeholder="PhonePe" required>
                </div>
                <div class="col-md-3">
                  <label class="form-label text-secondary small fw-bold">Account Holder</label>
                  <input name="account_name" class="form-control form-control-premium" placeholder="Dhani Win">
                </div>
                <div class="col-md-3">
                  <label class="form-label text-secondary small fw-bold">UPI ID (VPA) *</label>
                  <input name="account_value" class="form-control form-control-premium" placeholder="merchant@upi" required>
                </div>
                <div class="col-md-3">
                  <label class="form-label text-secondary small fw-bold">Sort Order</label>
                  <input type="number" name="sort_order" class="form-control form-control-premium" value="10">
                </div>
                <div class="col-md-3">
                  <label class="form-label text-secondary small fw-bold">Min Amount (₹)</label>
                  <input type="number" step="0.01" name="min_amount" class="form-control form-control-premium" value="100">
                </div>
                <div class="col-md-3">
                  <label class="form-label text-secondary small fw-bold">Max Amount (₹)</label>
                  <input type="number" step="0.01" name="max_amount" class="form-control form-control-premium" value="50000">
                </div>
                <div class="col-md-3">
                  <label class="form-label text-secondary small fw-bold">Status</label>
                  <select name="enabled" class="form-control form-control-premium">
                    <option value="1">Active</option>
                    <option value="0">Paused</option>
                  </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                  <button type="submit" class="btn btn-premium w-100 justify-content-center"><i class="fas fa-plus me-1"></i> Add UPI</button>
                </div>
              </form>
            </div>

            <div class="glass-panel p-4">
              <h4 class="fw-bold mb-3 text-gold"><i class="fas fa-list me-2"></i> Existing UPI Accounts</h4>
              <div class="table-responsive table-responsive-premium">
                <table class="table align-middle">
                  <thead>
                    <tr>
                      <th>Display Name</th>
                      <th>Account Holder</th>
                      <th>UPI ID</th>
                      <th>Limits</th>
                      <th>Sort</th>
                      <th>Status</th>
                      <th class="text-end">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (empty($upiRows)): ?>
                      <tr><td colspan="7" class="text-center text-muted">No UPI account added yet.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($upiRows as $upi): ?>
                      <tr>
                        <form action="api.php" method="post">
                          <input type="hidden" name="action" value="save_payment">
                          <input type="hidden" name="csrf" value="<?php echo $csrfToken; ?>">
                          <input type="hidden" name="return_tab" value="add_upi">
                          <input type="hidden" name="id" value="<?php echo (int)$upi['id']; ?>">
                          <input type="hidden" name="method_type" value="UPI">
                          <td><input name="method_name" class="form-control form-control-premium py-1" value="<?php echo htmlspecialchars((string)$upi['method_name']); ?>"></td>
                          <td><input name="account_name" class="form-control form-control-premium py-1" value="<?php echo htmlspecialchars((string)($upi['account_name'] ?? '')); ?>"></td>
                          <td><input name="account_value" class="form-control form-control-premium py-1" value="<?php echo htmlspecialchars((string)$upi['account_value']); ?>"></td>
                          <td>
                            <input type="number" step="0.01" name="min_amount" class="form-control form-control-premium py-1 d-inline-block" style="width: 90px;" value="<?php echo (float)$upi['min_amount']; ?>"> -
                            <input type="number" step="0.01" name="max_amount" class="form-control form-control-premium py-1 d-inline-block" style="width: 100px;" value="<?php echo (float)$upi['max_amount']; ?>">
                          </td>
                          <td><input type="number" name="sort_order" class="form-control form-control-premium py-1" style="width: 70px;" value="<?php echo (int)$upi['sort_order']; ?>"></td>
                          <td>
                            <select name="enabled" class="form-control form-control-premium py-1">
                              <option value="1" <?php echo !empty($upi['enabled']) ? 'selected' : ''; ?>>Active</option>
                              <option value="0" <?php echo empty($upi['enabled']) ? 'selected' : ''; ?>>Paused</option>
                            </select>
                          </td>
                          <td class="text-end">
                            <button type="submit" class="btn btn-sm btn-premium py-1">Save</button>
                            <button type="submit" formaction="api.php" name="action" value="delete_payment" class="btn btn-sm btn-outline-danger py-1" onclick="return confirm('Delete this UPI account?');">Delete</button>
                          </td>
                        </form>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        <?php endif; ?>

        <!-- ============================================================= -->
        <!-- ADD USDT                                                     -->
        <!-- ============================================================= -->
        <?php if ($tab === 'add_usdt'):
          $usdtRows = SettingsController::getUsdtMethods();
        ?>
          <div id="add-usdt-view">
            <h2 class="fw-bold mb-2"><i class="fas fa-coins text-gold me-2"></i> Add USDT Address</h2>
            <p class="text-secondary">USDT wallets added here appear as crypto deposit options for members.</p>

            <div class="glass-panel p-4 mb-4">
              <h4 class="fw-bold mb-3 text-gold"><i class="fas fa-plus me-2"></i> New USDT Wallet</h4>
              <form action="api.php" method="post" class="row g-3">
                <input type="hidden" name="action" value="save_usdt">
                <input type="hidden" name="csrf" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="return_tab" value="add_usdt">
                <div class="col-md-3">
                  <label class="form-label text-secondary small fw-bold">Wallet Name *</label>
                  <input name="wallet_name" class="form-control form-control-premium" placeholder="Binance USDT" required>
                </div>
                <div class="col-md-4">
                  <label class="form-label text-secondary small fw-bold">Wallet Address *</label>
                  <input name="wallet_address" class="form-control form-control-premium" placeholder="T..." required>
                </div>
                <div class="col-md-2">
                  <label class="form-label text-secondary small fw-bold">Network</label>
                  <input name="network" class="form-control form-control-premium" value="TRC20">
                </div>
                <div class="col-md-3">
                  <label class="form-label text-secondary small fw-bold">Sort Order</label>
                  <input type="number" name="sort_order" class="form-control form-control-premium" value="5">
                </div>
                <div class="col-md-3">
                  <label class="form-label text-secondary small fw-bold">Min USDT</label>
                  <input type="number" step="0.01" name="min_amount" class="form-control form-control-premium" value="10">
                </div>
                <div class="col-md-3">
                  <label class="form-label text-secondary small fw-bold">Max USDT</label>
                  <input type="number" step="0.01" name="max_amount" class="form-control form-control-premium" value="10000">
                </div>
                <div class="col-md-3">
                  <label class="form-label text-secondary small fw-bold">Status</label>
                  <select name="enabled" class="form-control form-control-premium">
                    <option value="1">Active</option>
                    <option value="0">Paused</option>
                  </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                  <button type="submit" class="btn btn-premium w-100 justify-content-center"><i class="fas fa-plus me-1"></i> Add Address</button>
                </div>
              </form>
            </div>

            <div class="glass-panel p-4">
              <h4 class="fw-bold mb-3 text-gold"><i class="fas fa-list me-2"></i> Existing USDT Wallets</h4>
              <div class="table-responsive table-responsive-premium">
                <table class="table align-middle">
                  <thead>
                    <tr>
                      <th>Wallet Name</th>
                      <th>Address</th>
                      <th>Network</th>
                      <th>Limits (USDT)</th>
                      <th>Sort</th>
                      <th>Status</th>
                      <th class="text-end">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (empty($usdtRows)): ?>
                      <tr><td colspan="7" class="text-center text-muted">No USDT wallet added yet.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($usdtRows as $usdt): ?>
                      <tr>
                        <form action="api.php" method="post">
                          <input type="hidden" name="action" value="save_usdt">
                          <input type="hidden" name="csrf" value="<?php echo $csrfToken; ?>">
                          <input type="hidden" name="return_tab" value="add_usdt">
                          <input type="hidden" name="id" value="<?php echo (int)$usdt['id']; ?>">
                          <td><input name="wallet_name" class="form-control form-control-premium py-1" value="<?php echo htmlspecialchars((string)$usdt['wallet_name']); ?>"></td>
                          <td><input name="wallet_address" class="form-control form-control-premium py-1" value="<?php echo htmlspecialchars((string)$usdt['wallet_address']); ?>"></td>
                          <td><input name="network" class="form-control form-control-premium py-1" style="width: 110px;" value="<?php echo htmlspecialchars((string)$usdt['network']); ?>"></td>
                          <td>
                            <input type="number" step="0.01" name="min_amount" class="form-control form-control-premium py-1 d-inline-block" style="width: 85px;" value="<?php echo (float)$usdt['min_amount']; ?>"> -
                            <input type="number" step="0.01" name="max_amount" class="form-control form-control-premium py-1 d-inline-block" style="width: 95px;" value="<?php echo (float)$usdt['max_amount']; ?>">
                          </td>
                          <td><input type="number" name="sort_order" class="form-control form-control-premium py-1" style="width: 70px;" value="<?php echo (int)($usdt['sort_order'] ?? 0); ?>"></td>
                          <td>
                            <select name="enabled" class="form-control form-control-premium py-1">
                              <option value="1" <?php echo !empty($usdt['enabled']) ? 'selected' : ''; ?>>Active</option>
                              <option value="0" <?php echo empty($usdt['enabled']) ? 'selected' : ''; ?>>Paused</option>
                            </select>
                          </td>
                          <td class="text-end">
                            <button type="submit" class="btn btn-sm btn-premium py-1">Save</button>
                            <button type="submit" formaction="api.php" name="action" value="delete_usdt" class="btn btn-sm btn-outline-danger py-1" onclick="return confirm('Delete this USDT wallet?');">Delete</button>
                          </td>
                        </form>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        <?php endif; ?>

        <!-- ============================================================= -->
        <!-- USDT RATE                                                    -->
        <!-- ============================================================= -->
        <?php if ($tab === 'usdt_rate'):
          $usdtRate = api_setting_float('usdt_rate', 90.0);
          if ($usdtRate <= 0) { $usdtRate = 90.0; }
          $usdtMethodsForRate = SettingsController::getUsdtMethods();
        ?>
          <div id="usdt-rate-view">
            <h2 class="fw-bold mb-2"><i class="fas fa-rupee-sign text-gold me-2"></i> USDT Rate</h2>
            <p class="text-secondary">Conversion rate used for crypto deposits: how many rupees one USDT is worth.</p>

            <div class="row g-4">
              <div class="col-lg-5">
                <div class="glass-panel p-4 h-100">
                  <h4 class="fw-bold mb-3 text-gold"><i class="fas fa-sliders-h me-2"></i> Rate Settings</h4>
                  <form action="api.php" method="post" class="row g-3 mb-3">
                    <input type="hidden" name="action" value="save_setting">
                    <input type="hidden" name="csrf" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="return_tab" value="usdt_rate">
                    <input type="hidden" name="setting_key" value="usdt_rate">
                    <div class="col-12">
                      <label class="form-label text-secondary small fw-bold">1 USDT = ₹</label>
                      <input type="number" step="0.01" min="0" name="setting_value" class="form-control form-control-premium" value="<?php echo htmlspecialchars((string)$usdtRate); ?>">
                    </div>
                    <div class="col-12"><button type="submit" class="btn btn-premium btn-sm">Save USDT Rate</button></div>
                  </form>

                  <form action="api.php" method="post" class="row g-3">
                    <input type="hidden" name="action" value="save_setting">
                    <input type="hidden" name="csrf" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="return_tab" value="usdt_rate">
                    <div class="col-md-6">
                      <label class="form-label text-secondary small fw-bold">Minimum USDT Deposit</label>
                      <input type="number" step="0.01" min="0" name="setting_value" class="form-control form-control-premium" value="<?php echo htmlspecialchars(api_setting('usdt_min_amount', '10')); ?>">
                      <input type="hidden" name="setting_key" value="usdt_min_amount">
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                      <button type="submit" class="btn btn-premium btn-sm w-100">Save Minimum</button>
                    </div>
                  </form>

                  <form action="api.php" method="post" class="row g-3 mt-1">
                    <input type="hidden" name="action" value="save_setting">
                    <input type="hidden" name="csrf" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="return_tab" value="usdt_rate">
                    <div class="col-md-6">
                      <label class="form-label text-secondary small fw-bold">Maximum USDT Deposit</label>
                      <input type="number" step="0.01" min="0" name="setting_value" class="form-control form-control-premium" value="<?php echo htmlspecialchars(api_setting('usdt_max_amount', '10000')); ?>">
                      <input type="hidden" name="setting_key" value="usdt_max_amount">
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                      <button type="submit" class="btn btn-premium btn-sm w-100">Save Maximum</button>
                    </div>
                  </form>
                </div>
              </div>

              <div class="col-lg-7">
                <div class="glass-panel p-4 h-100">
                  <h4 class="fw-bold mb-3 text-gold"><i class="fas fa-calculator me-2"></i> Live Conversion Converter</h4>
                  <div class="input-group mb-3">
                    <input type="number" step="0.01" id="usdt-convert-input" class="form-control form-control-premium" value="100" data-rate="<?php echo htmlspecialchars((string)$usdtRate); ?>">
                    <span class="input-group-text border-0" style="background: rgba(7,9,19,0.6); color: var(--text-muted);">USDT</span>
                    <span class="input-group-text border-0" style="background: rgba(7,9,19,0.6); color: var(--text-muted);">=</span>
                    <span class="input-group-text border-0 fw-bold text-gold" id="usdt-convert-output" style="background: rgba(7,9,19,0.6);">₹<?php echo number_format(100 * $usdtRate, 2); ?></span>
                  </div>
                  <div class="table-responsive table-responsive-premium">
                    <table class="table align-middle">
                      <thead>
                        <tr><th>USDT Wallet</th><th>Network</th><th>Min</th><th>Max</th><th>Status</th></tr>
                      </thead>
                      <tbody>
                        <?php if (empty($usdtMethodsForRate)): ?>
                          <tr><td colspan="5" class="text-center text-muted">No USDT wallet yet. Add one from <a href="/admin/?tab=add_usdt" class="text-gold">Add USDT</a>.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($usdtMethodsForRate as $row): ?>
                          <tr>
                            <td class="fw-bold text-white"><?php echo htmlspecialchars((string)$row['wallet_name']); ?></td>
                            <td><?php echo htmlspecialchars((string)$row['network']); ?></td>
                            <td><?php echo (float)$row['min_amount']; ?> USDT</td>
                            <td><?php echo (float)$row['max_amount']; ?> USDT</td>
                            <td>
                              <?php if (!empty($row['enabled'])): ?>
                                <span class="badge bg-success">Active</span>
                              <?php else: ?>
                                <span class="badge bg-secondary">Paused</span>
                              <?php endif; ?>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>
        <?php endif; ?>

        <!-- ============================================================= -->
        <!-- GATEWAY IMAGE UPLOAD (UPI / USDT)                            -->
        <!-- ============================================================= -->
        <?php
          $imageViews = [
              'add_upi_image'  => ['table' => 'payment_methods', 'action' => 'save_payment_image', 'title' => 'Add UPI Image', 'icon' => 'fa-qrcode', 'label' => 'UPI Account'],
              'add_usdt_image' => ['table' => 'usdt_methods', 'action' => 'save_usdt_image', 'title' => 'Add USDT Image', 'icon' => 'fa-file-image', 'label' => 'USDT Wallet'],
          ];
        ?>
        <?php if (isset($imageViews[$tab])):
          $imageView = $imageViews[$tab];
          $imageRows = $imageView['table'] === 'payment_methods' ? SettingsController::getPaymentMethods() : SettingsController::getUsdtMethods();
        ?>
          <div id="gateway-image-view">
            <h2 class="fw-bold mb-2"><i class="fas <?php echo $imageView['icon']; ?> text-gold me-2"></i> <?php echo htmlspecialchars($imageView['title']); ?></h2>
            <p class="text-secondary">Upload the QR / logo image of a <?php echo htmlspecialchars($imageView['label']); ?>. The image is shown to members on the deposit page.</p>

            <div class="glass-panel p-4 mb-4">
              <h4 class="fw-bold mb-3 text-gold"><i class="fas fa-upload me-2"></i> Upload Image</h4>
              <form action="api.php" method="post" enctype="multipart/form-data" class="row g-3">
                <input type="hidden" name="action" value="<?php echo $imageView['action']; ?>">
                <input type="hidden" name="csrf" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="return_tab" value="<?php echo $tab; ?>">
                <div class="col-md-4">
                  <label class="form-label text-secondary small fw-bold"><?php echo htmlspecialchars($imageView['label']); ?> *</label>
                  <select name="id" class="form-control form-control-premium" required>
                    <option value="">-- choose --</option>
                    <?php foreach ($imageRows as $row): ?>
                      <?php $rowName = $imageView['table'] === 'payment_methods' ? (string)$row['method_name'] : (string)$row['wallet_name']; ?>
                      <option value="<?php echo (int)$row['id']; ?>"><?php echo htmlspecialchars($rowName); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label text-secondary small fw-bold">Image File</label>
                  <input type="file" name="image" accept="image/png,image/jpeg,image/webp,image/gif" class="form-control form-control-premium">
                  <span class="text-muted small">PNG / JPG / WEBP up to the server upload limit.</span>
                </div>
                <div class="col-md-4">
                  <label class="form-label text-secondary small fw-bold">Or Image URL</label>
                  <input name="image_url" class="form-control form-control-premium" placeholder="https://... / /img/qr.png">
                  <span class="text-muted small">Use this if the file is already hosted.</span>
                </div>
                <div class="col-12">
                  <button type="submit" class="btn btn-premium"><i class="fas fa-save me-1"></i> Save Image</button>
                </div>
              </form>
            </div>

            <div class="glass-panel p-4">
              <h4 class="fw-bold mb-3 text-gold"><i class="fas fa-images me-2"></i> Current Images</h4>
              <div class="row g-3">
                <?php if (empty($imageRows)): ?>
                  <div class="col-12 text-center text-muted">Nothing added yet.</div>
                <?php endif; ?>
                <?php foreach ($imageRows as $row): ?>
                  <?php
                    $rowName = $imageView['table'] === 'payment_methods' ? (string)$row['method_name'] : (string)$row['wallet_name'];
                    $rowImage = trim((string)($row['icon_url'] ?? ''));
                    if ($rowImage === '') { $rowImage = trim((string)($row['qr_image'] ?? '')); }
                  ?>
                  <div class="col-md-4">
                    <div class="glass-panel p-3 h-100 text-center">
                      <?php if ($rowImage !== ''): ?>
                        <img src="<?php echo htmlspecialchars($rowImage); ?>" alt="QR" style="max-width: 140px; max-height: 140px; border-radius: 12px; margin-bottom: 10px; background: #fff; padding: 6px;">
                      <?php else: ?>
                        <div class="text-muted mb-2" style="font-size: 40px;"><i class="fas fa-image"></i></div>
                      <?php endif; ?>
                      <div class="fw-bold text-white"><?php echo htmlspecialchars($rowName); ?></div>
                      <div class="text-muted small"><?php echo htmlspecialchars($imageView['table'] === 'payment_methods' ? (string)$row['account_value'] : (string)$row['wallet_address']); ?></div>
                      <?php if (!empty($row['enabled'])): ?>
                        <span class="badge bg-success mt-2">Active</span>
                      <?php else: ?>
                        <span class="badge bg-secondary mt-2">Paused</span>
                      <?php endif; ?>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        <?php endif; ?>

        <!-- ============================================================= -->
        <!-- WITHDRAW VIEWS: UPI / SENT / REJECTED                        -->
        <!-- ============================================================= -->
        <?php
          $withdrawViews = [
              'upi_withdraw'    => ['title' => 'UPI Withdraw Requests', 'status' => 'Pending', 'type' => 'UPI', 'icon' => 'fa-mobile-alt', 'note' => 'Pending UPI payout requests waiting for approval.'],
              'withdraw_sent'   => ['title' => 'Withdraw Sent', 'status' => 'Approved', 'type' => '', 'icon' => 'fa-paper-plane', 'note' => 'Approved payouts that were sent to members.'],
              'withdraw_reject' => ['title' => 'Withdraw Rejected', 'status' => 'Rejected', 'type' => '', 'icon' => 'fa-ban', 'note' => 'Rejected requests - the amount is returned to the member wallet automatically.'],
          ];
        ?>
        <?php if (isset($withdrawViews[$tab])):
          $wv = $withdrawViews[$tab];
        ?>
          <div id="withdraw-view">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
              <div>
                <h2 class="fw-bold mb-1"><i class="fas <?php echo $wv['icon']; ?> text-gold me-2"></i> <?php echo htmlspecialchars($wv['title']); ?></h2>
                <p class="text-secondary mb-0"><?php echo htmlspecialchars($wv['note']); ?></p>
              </div>
              <div class="d-flex gap-2">
                <select id="payout-status-filter" class="form-select form-control-premium" style="width: 180px;">
                  <option value="">All Statuses</option>
                  <option value="Pending" <?php echo $wv['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                  <option value="Approved" <?php echo $wv['status'] === 'Approved' ? 'selected' : ''; ?>>Approved / Sent</option>
                  <option value="Rejected" <?php echo $wv['status'] === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                </select>
                <a href="/admin/?tab=withdrawals" class="btn btn-secondary-premium"><i class="fas fa-list me-1"></i> All Payouts</a>
              </div>
            </div>

            <div class="glass-panel p-4">
              <div class="table-responsive table-responsive-premium">
                <table class="table align-middle" id="payout-table" data-payout-status="<?php echo htmlspecialchars($wv['status']); ?>" data-payout-type="<?php echo htmlspecialchars($wv['type']); ?>">
                  <thead>
                    <tr>
                      <th>Order No</th>
                      <th>Player ID</th>
                      <th>Username</th>
                      <th>Amount</th>
                      <th>Type</th>
                      <th>Status</th>
                      <th>Account</th>
                      <th>Remarks</th>
                      <th class="text-end">Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr><td colspan="9" class="text-center text-muted">Loading payout requests...</td></tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        <?php endif; ?>

        <!-- ============================================================= -->
        <!-- SUPPORT CATEGORY QUEUES                                      -->
        <!-- ============================================================= -->
        <?php
          $supportViews = [
              'support_deposit'  => ['title' => 'Deposit Problem Tickets', 'icon' => 'fa-arrow-alt-circle-down', 'note' => 'Members reporting deposit not credited / UTR issues.'],
              'support_withdraw' => ['title' => 'Withdrawal Problem Tickets', 'icon' => 'fa-arrow-alt-circle-up', 'note' => 'Members reporting payout delays or failures.'],
              'support_ifsc'     => ['title' => 'IFSC Modification Tickets', 'icon' => 'fa-university', 'note' => 'Bank IFSC correction requests.'],
              'support_bank'     => ['title' => 'Bank Modification Tickets', 'icon' => 'fa-credit-card', 'note' => 'Bank account / card change requests.'],
              'support_game'     => ['title' => 'Game Problem Tickets', 'icon' => 'fa-gamepad', 'note' => 'Bet, result or in-game balance complaints.'],
          ];
        ?>
        <?php if (isset($supportViews[$tab])):
          $sv = $supportViews[$tab];
          $counts = SupportController::categoryCounts();
          $myCount = (int)($counts[$tab] ?? 0);
        ?>
          <div id="support-category-view" data-support-category="<?php echo $tab; ?>">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
              <div>
                <h2 class="fw-bold mb-1"><i class="fas <?php echo $sv['icon']; ?> text-gold me-2"></i> <?php echo htmlspecialchars($sv['title']); ?></h2>
                <p class="text-secondary mb-0"><?php echo htmlspecialchars($sv['note']); ?></p>
              </div>
              <div class="d-flex gap-2 align-items-center">
                <span class="badge bg-danger fs-6 py-2 px-3"><?php echo $myCount; ?> open / total</span>
                <select id="support-status-filter" class="form-select form-control-premium" style="width: 170px;">
                  <option value="">All Statuses</option>
                  <option value="open">Open</option>
                  <option value="replied">Replied</option>
                  <option value="closed">Closed</option>
                </select>
                <a href="/admin/?tab=support" class="btn btn-secondary-premium"><i class="fas fa-inbox me-1"></i> All Tickets</a>
              </div>
            </div>

            <div class="row g-4">
              <div class="col-xl-8">
                <div class="glass-panel p-4 h-100">
                  <h4 class="fw-bold mb-3 text-gold"><i class="fas fa-ticket-alt me-2"></i> Ticket Queue</h4>
                  <div class="table-responsive table-responsive-premium">
                    <table class="table align-middle" id="support-tickets-table">
                      <thead>
                        <tr>
                          <th>Title</th>
                          <th>User</th>
                          <th>Order No</th>
                          <th>Status</th>
                          <th>Last Update</th>
                          <th class="text-end">Action</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr><td colspan="6" class="text-center text-muted">Loading tickets...</td></tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>

              <div class="col-xl-4">
                <div class="glass-panel p-4 h-100">
                  <h4 class="fw-bold mb-3 text-gold"><i class="fas fa-plus-circle me-2"></i> Create Ticket</h4>
                  <p class="text-secondary small">Members can also raise these tickets from the site's Self Service Center - they land in this queue automatically.</p>
                  <form action="api.php" method="post" class="row g-3">
                    <input type="hidden" name="action" value="create_ticket">
                    <input type="hidden" name="csrf" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="return_tab" value="<?php echo $tab; ?>">
                    <input type="hidden" name="category" value="<?php echo $tab; ?>">
                    <div class="col-12">
                      <label class="form-label text-secondary small fw-bold">Member</label>
                      <select name="user_id" class="form-control form-control-premium">
                        <option value="0">-- first member (default) --</option>
                        <?php foreach (admin_db_rows("SELECT id, user_id, username FROM api_users ORDER BY id DESC LIMIT 300") as $opt): ?>
                          <option value="<?php echo (int)$opt['id']; ?>"><?php echo htmlspecialchars($opt['username'] . ' (' . $opt['user_id'] . ')'); ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div class="col-12">
                      <label class="form-label text-secondary small fw-bold">Title</label>
                      <input name="title" class="form-control form-control-premium" placeholder="<?php echo htmlspecialchars($sv['title']); ?>">
                    </div>
                    <div class="col-12">
                      <label class="form-label text-secondary small fw-bold">Order No (optional)</label>
                      <input name="order_no" class="form-control form-control-premium" placeholder="RC... / WD...">
                    </div>
                    <div class="col-12">
                      <label class="form-label text-secondary small fw-bold">Message</label>
                      <textarea name="message" class="form-control form-control-premium" style="min-height: 90px;" placeholder="Problem details..."></textarea>
                    </div>
                    <div class="col-12">
                      <button type="submit" class="btn btn-premium w-100 justify-content-center"><i class="fas fa-plus me-1"></i> Create Ticket</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>

            <?php require __DIR__ . '/views/support-chat-modal.php'; ?>
          </div>
        <?php endif; ?>

      <?php endif; // hasAccess check ?>

    </main>
  </div>
  <?php require __DIR__ . '/views/footer.php'; ?>
