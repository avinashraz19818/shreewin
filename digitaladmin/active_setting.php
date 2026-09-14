<?php
session_start();
if (empty($_SESSION['unohs'])) {
    header("location: api/login.php?msg=unauthorized");
}

include("api/conn.php");

// // ==================== DATABASE CONNECTION ====================
// class Database {
//     private static $connection = null;
    
//     public static function connect($config) {
//         if (self::$connection === null) {
//             try {
//                 $dsn = "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4";
//                 self::$connection = new PDO($dsn, $config['db_user'], $config['db_pass'], [
//                     PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
//                     PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
//                 ]);
//             } catch (PDOException $e) {
//                 die("Database connection failed: " . $e->getMessage());
//             }
//         }
//         return self::$connection;
//     }
// }

// ==================== SETTINGS MODEL ====================
class SettingsModel {
    private $db;
    private $table;
    
    public function __construct($db, $table) {
        $this->db = $db;
        $this->table = $table;
    }
    
    // Get all settings
    public function getAll() {
        $stmt = $this->db->query("
            SELECT * FROM {$this->table} 
            ORDER BY category, sort_order, setting_key
        ");
        return $stmt->fetchAll();
    }
    
    // Get setting by key
    public function getByKey($key) {
        $stmt = $this->db->prepare("
            SELECT * FROM {$this->table} 
            WHERE setting_key = :key
        ");
        $stmt->execute([':key' => $key]);
        return $stmt->fetch();
    }
    
    // Update setting
    public function update($key, $value) {
        $stmt = $this->db->prepare("
            UPDATE {$this->table} 
            SET setting_value = :value, updated_at = NOW() 
            WHERE setting_key = :key
        ");
        return $stmt->execute([
            ':value' => $value,
            ':key' => $key
        ]);
    }
    
    // Batch update settings
    public function batchUpdate($settings) {
        $this->db->beginTransaction();
        
        try {
            foreach ($settings as $key => $value) {
                $stmt = $this->db->prepare("
                    UPDATE {$this->table} 
                    SET setting_value = :value, updated_at = NOW() 
                    WHERE setting_key = :key
                ");
                $stmt->execute([
                    ':value' => $value,
                    ':key' => $key
                ]);
            }
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
    
    // Reset to defaults
    public function resetToDefaults() {
        $defaults = [
            'isTaskState' => '1',
            'isOpenJackpotReward' => '1',
            'isOpenWashCode' => '1',
            'unJackpotCount' => '0',
            'isOpenActivityAward' => '1',
            'unWeeklyAwardCount' => '0',
            'isFinishUserGuidelines' => 'true',
            'isFirstUserDayRequest' => 'false',
            'isOpenChampion' => '1',
            'newbieGiftPackCount' => '0',
            'newMemberGiftPackageSwitch' => '1'
        ];
        
        return $this->batchUpdate($defaults);
    }
    
    // Get stats
    public function getStats() {
        $total = $this->db->query("SELECT COUNT(*) as total FROM {$this->table}")->fetch()['total'];
        
        $active = $this->db->query("
            SELECT COUNT(*) as active FROM {$this->table} 
            WHERE (setting_type = 'boolean' AND setting_value IN ('true', '1')) 
               OR (setting_type = 'string' AND setting_value = '1')
        ")->fetch()['active'];
        
        $lastUpdate = $this->db->query("
            SELECT MAX(updated_at) as last_update FROM {$this->table}
        ")->fetch()['last_update'];
        
        return [
            'total' => $total,
            'active' => $active,
            'last_update' => $lastUpdate
        ];
    }
}

// ==================== API HANDLER ====================
function handleApiRequest() {
    global $config;
    
    $db = Database::connect($config);
    $model = new SettingsModel($db, $config['table_name']);
    
    $action = $_GET['action'] ?? '';
    $response = [];
    
    switch ($action) {
        case 'get_all':
            $settings = $model->getAll();
            $formatted = [];
            foreach ($settings as $s) {
                $formatted[$s['setting_key']] = [
                    'value' => $s['setting_value'],
                    'type' => $s['setting_type'],
                    'display_name' => $s['display_name'],
                    'category' => $s['category'],
                    'description' => $s['description']
                ];
            }
            $response = ['success' => true, 'data' => $formatted];
            break;
            
        case 'update':
            $data = json_decode(file_get_contents('php://input'), true);
            if ($model->batchUpdate($data)) {
                $response = ['success' => true, 'message' => 'Settings updated successfully'];
            } else {
                $response = ['success' => false, 'message' => 'Update failed'];
            }
            break;
            
        case 'reset':
            if ($model->resetToDefaults()) {
                $response = ['success' => true, 'message' => 'Reset to defaults successful'];
            } else {
                $response = ['success' => false, 'message' => 'Reset failed'];
            }
            break;
            
        case 'stats':
            $response = ['success' => true, 'data' => $model->getStats()];
            break;
            
        default:
            $response = ['success' => false, 'message' => 'Invalid action'];
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// ==================== INITIALIZE DATABASE ====================
function initializeDatabase($config) {
    $db = Database::connect($config);
    
    // Check if table exists
    $tableExists = $db->query("SHOW TABLES LIKE '{$config['table_name']}'")->rowCount() > 0;
    
    if (!$tableExists) {
        // Create table
        $db->exec("
            CREATE TABLE {$config['table_name']} (
                id INT PRIMARY KEY AUTO_INCREMENT,
                setting_key VARCHAR(100) UNIQUE NOT NULL,
                setting_value TEXT,
                setting_type ENUM('boolean', 'integer', 'string', 'float') DEFAULT 'string',
                display_name VARCHAR(200),
                category VARCHAR(50) DEFAULT 'general',
                description TEXT,
                is_active BOOLEAN DEFAULT TRUE,
                sort_order INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_category (category),
                INDEX idx_is_active (is_active)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
        
        // Insert default data
        $defaultData = [
            ['isTaskState', '1', 'string', 'Task State', 'task', 'Enable/Disable task state functionality', 1],
            ['isOpenJackpotReward', '1', 'string', 'Open Jackpot Reward', 'jackpot', 'Enable jackpot reward system', 2],
            ['isOpenWashCode', '1', 'string', 'Open Wash Code', 'wash_code', 'Enable wash code feature', 3],
            ['unJackpotCount', '0', 'integer', 'Unclaimed Jackpot Count', 'jackpot', 'Number of unclaimed jackpots', 4],
            ['isOpenActivityAward', '1', 'string', 'Open Activity Award', 'activity', 'Enable activity awards', 5],
            ['unWeeklyAwardCount', '0', 'integer', 'Unclaimed Weekly Awards', 'activity', 'Number of unclaimed weekly awards', 6],
            ['isFinishUserGuidelines', 'true', 'boolean', 'Finish User Guidelines', 'task', 'Check if user has completed guidelines', 7],
            ['isFirstUserDayRequest', 'false', 'boolean', 'First User Day Request', 'user', 'Check if first day request', 8],
            ['isOpenChampion', '1', 'string', 'Open Champion', 'user', 'Enable champion feature', 9],
            ['newbieGiftPackCount', '0', 'integer', 'Newbie Gift Pack Count', 'gift', 'Number of newbie gift packs', 10],
            ['newMemberGiftPackageSwitch', '1', 'string', 'New Member Gift Package', 'gift', 'Enable new member gift package', 11]
        ];
        
        $stmt = $db->prepare("
            INSERT INTO {$config['table_name']} 
            (setting_key, setting_value, setting_type, display_name, category, description, sort_order) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($defaultData as $data) {
            $stmt->execute($data);
        }
    }
}

// ==================== MAIN REQUEST HANDLER ====================
// Check if API request
if (isset($_GET['api']) && $_GET['api'] == '1') {
    handleApiRequest();
}

// Initialize database
initializeDatabase($config);

// ==================== HTML UI ====================
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($config['app_name']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --secondary: #7c3aed;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --info: #3b82f6;
            --light: #f8fafc;
            --dark: #1e293b;
            --gray: #64748b;
            --gray-light: #e2e8f0;
            --border-radius: 10px;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            color: var(--dark);
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        /* Header */
        .header {
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            color: white;
            padding: 25px 30px;
            text-align: center;
            position: relative;
        }

        .header h1 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }

        .header p {
            opacity: 0.9;
            font-size: 16px;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Stats Bar */
        .stats-bar {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            background: var(--light);
            padding: 25px 30px;
            border-bottom: 1px solid var(--gray-light);
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            text-align: center;
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
        }

        .stat-value {
            font-size: 32px;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 14px;
            color: var(--gray);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Tabs */
        .tabs-container {
            background: var(--light);
            border-bottom: 1px solid var(--gray-light);
            padding: 0 30px;
        }

        .tabs {
            display: flex;
            overflow-x: auto;
            gap: 5px;
        }

        .tab {
            padding: 18px 30px;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: var(--transition);
            font-weight: 600;
            color: var(--gray);
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .tab:hover {
            background: rgba(255, 255, 255, 0.8);
            color: var(--primary);
        }

        .tab.active {
            border-bottom-color: var(--primary);
            color: var(--primary);
            background: white;
        }

        /* Settings Container */
        .settings-container {
            padding: 30px;
            min-height: 500px;
        }

        .category-section {
            margin-bottom: 50px;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .category-title {
            font-size: 24px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--gray-light);
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .category-title i {
            color: var(--primary);
            font-size: 22px;
        }

        .settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 25px;
        }

        /* Setting Card */
        .setting-card {
            background: white;
            border: 1px solid var(--gray-light);
            border-radius: var(--border-radius);
            padding: 25px;
            transition: var(--transition);
            position: relative;
        }

        .setting-card:hover {
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            transform: translateY(-3px);
            border-color: var(--primary);
        }

        .setting-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .setting-name {
            font-weight: 700;
            color: var(--dark);
            font-size: 18px;
            flex: 1;
        }

        .setting-key {
            font-family: 'Courier New', monospace;
            background: var(--light);
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 13px;
            color: var(--gray);
            font-weight: 600;
        }

        .setting-description {
            color: var(--gray);
            font-size: 15px;
            margin-bottom: 25px;
            line-height: 1.6;
            min-height: 50px;
        }

        .setting-control {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        /* Toggle Switch */
        .toggle-switch {
            position: relative;
            width: 70px;
            height: 34px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: var(--gray-light);
            transition: .4s;
            border-radius: 34px;
        }

        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        input:checked + .toggle-slider {
            background-color: var(--success);
        }

        input:checked + .toggle-slider:before {
            transform: translateX(36px);
        }

        /* Input Controls */
        .number-input, .text-input, .select-input {
            width: 120px;
            padding: 10px 15px;
            border: 2px solid var(--gray-light);
            border-radius: 8px;
            font-size: 15px;
            transition: var(--transition);
        }

        .number-input:focus, .text-input:focus, .select-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        .value-display {
            font-family: 'Courier New', monospace;
            padding: 10px 15px;
            background: var(--light);
            border-radius: 8px;
            font-size: 15px;
            color: var(--dark);
            min-width: 100px;
            text-align: center;
            font-weight: 600;
            border: 2px solid transparent;
        }

        .value-display.true {
            background: #d1fae5;
            color: #065f46;
            border-color: #a7f3d0;
        }

        .value-display.false {
            background: #fee2e2;
            color: #991b1b;
            border-color: #fecaca;
        }

        /* Action Buttons */
        .actions-bar {
            position: fixed;
            bottom: 40px;
            right: 40px;
            display: flex;
            gap: 20px;
            z-index: 1000;
        }

        .btn {
            padding: 15px 35px;
            border: none;
            border-radius: 50px;
            font-weight: 700;
            font-size: 16px;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .btn-save {
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            color: white;
        }

        .btn-save:hover:not(:disabled) {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(79, 70, 229, 0.4);
        }

        .btn-reset {
            background: white;
            color: var(--dark);
            border: 2px solid var(--gray-light);
        }

        .btn-reset:hover {
            background: var(--light);
            border-color: var(--primary);
            color: var(--primary);
        }

        /* Notification */
        .notification {
            position: fixed;
            top: 40px;
            right: 40px;
            padding: 20px 30px;
            background: var(--success);
            color: white;
            border-radius: var(--border-radius);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            display: none;
            align-items: center;
            gap: 15px;
            z-index: 1000;
            animation: slideIn 0.3s ease;
            max-width: 400px;
        }

        .notification.error {
            background: var(--danger);
        }

        .notification.warning {
            background: var(--warning);
        }

        .notification.info {
            background: var(--info);
        }

        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        /* Loader */
        .loader {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: var(--gray);
        }

        .empty-state i {
            font-size: 60px;
            margin-bottom: 20px;
            color: var(--gray-light);
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .settings-grid {
                grid-template-columns: 1fr;
            }
            
            .actions-bar {
                position: static;
                margin-top: 40px;
                justify-content: center;
            }
        }

        @media (max-width: 768px) {
            .container {
                border-radius: 0;
            }
            
            .header, .settings-container {
                padding: 20px;
            }
            
            .stats-bar {
                grid-template-columns: 1fr;
            }
            
            .btn {
                padding: 12px 25px;
                font-size: 14px;
            }
        }

        /* Category Colors */
        .category-task { border-left: 5px solid #4f46e5; }
        .category-jackpot { border-left: 5px solid #10b981; }
        .category-wash_code { border-left: 5px solid #f59e0b; }
        .category-activity { border-left: 5px solid #3b82f6; }
        .category-user { border-left: 5px solid #8b5cf6; }
        .category-gift { border-left: 5px solid #ec4899; }
        .category-general { border-left: 5px solid #64748b; }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1><i class="fas fa-sliders-h"></i> Active Settings Management</h1>
            <p>Toggle features on/off and manage application settings in real-time</p>
        </div>
        
        <!-- Stats Bar -->
        <div class="stats-bar" id="statsBar">
            <div class="stat-card">
                <div class="stat-value" id="activeCount">0</div>
                <div class="stat-label">Active Features</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" id="totalCount">0</div>
                <div class="stat-label">Total Settings</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" id="lastUpdate">-</div>
                <div class="stat-label">Last Updated</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" id="changedCount">0</div>
                <div class="stat-label">Pending Changes</div>
            </div>
        </div>
        
        <!-- Tabs -->
        <div class="tabs-container">
            <div class="tabs" id="categoryTabs">
                <!-- Tabs will be generated by JavaScript -->
            </div>
        </div>
        
        <!-- Settings Container -->
        <div class="settings-container" id="settingsContainer">
            <div class="empty-state" id="loadingState">
                <i class="fas fa-spinner fa-spin"></i>
                <h3>Loading settings...</h3>
            </div>
            <!-- Settings will be loaded here -->
        </div>
    </div>
    
    <!-- Action Buttons -->
    <div class="actions-bar">
        <button class="btn btn-reset" id="resetBtn" onclick="resetSettings()">
            <i class="fas fa-undo"></i> Reset All
        </button>
        <button class="btn btn-save" id="saveBtn" onclick="saveSettings()" disabled>
            <i class="fas fa-save"></i> Save Changes
        </button>
    </div>
    
    <!-- Notification -->
    <div class="notification" id="notification">
        <i class="fas fa-check-circle"></i>
        <span id="notificationText">Settings saved successfully!</span>
    </div>
    
    <script>
        // Global state
        let settingsData = {};
        let originalSettings = {};
        let changedSettings = {};
        let activeCategory = 'all';
        
        // Category configuration
        const categories = {
            'task': { name: 'Task & State', icon: 'fas fa-tasks', color: '#4f46e5' },
            'jackpot': { name: 'Jackpot', icon: 'fas fa-trophy', color: '#10b981' },
            'wash_code': { name: 'Wash Code', icon: 'fas fa-recycle', color: '#f59e0b' },
            'activity': { name: 'Activity', icon: 'fas fa-calendar-alt', color: '#3b82f6' },
            'user': { name: 'User Settings', icon: 'fas fa-users', color: '#8b5cf6' },
            'gift': { name: 'Gift Packs', icon: 'fas fa-gift', color: '#ec4899' }
        };
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            loadSettings();
            setupEventListeners();
        });
        
        // Load settings from server
        async function loadSettings() {
            try {
                showLoading(true);
                
                const response = await fetch('?api=1&action=get_all');
                const result = await response.json();
                
                if (result.success) {
                    settingsData = result.data;
                    originalSettings = JSON.parse(JSON.stringify(settingsData));
                    
                    // Initialize changed settings
                    changedSettings = {};
                    
                    generateTabs();
                    renderSettings();
                    updateStats();
                    checkForChanges();
                    
                    showLoading(false);
                } else {
                    showNotification('Failed to load settings', 'error');
                }
            } catch (error) {
                showNotification('Network error: ' + error.message, 'error');
            }
        }
        
        // Generate category tabs
        function generateTabs() {
            const tabsContainer = document.getElementById('categoryTabs');
            tabsContainer.innerHTML = '';
            
            // All tab
            const allTab = createTab('all', 'fas fa-layer-group', 'All Settings', '#64748b');
            tabsContainer.appendChild(allTab);
            
            // Category tabs
            const uniqueCategories = [...new Set(Object.values(settingsData).map(s => s.category))];
            uniqueCategories.forEach(category => {
                const catConfig = categories[category] || { name: category, icon: 'fas fa-cog', color: '#64748b' };
                const tab = createTab(category, catConfig.icon, catConfig.name, catConfig.color);
                tabsContainer.appendChild(tab);
            });
            
            // Set active tab
            document.querySelectorAll('.tab').forEach(tab => {
                if (tab.dataset.category === activeCategory) {
                    tab.classList.add('active');
                }
            });
        }
        
        // Create tab element
        function createTab(category, icon, name, color) {
            const tab = document.createElement('div');
            tab.className = 'tab';
            tab.dataset.category = category;
            tab.innerHTML = `<i class="${icon}"></i> ${name}`;
            tab.style.borderBottomColor = color;
            
            tab.onclick = function() {
                // Update active tab
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                
                // Switch category
                activeCategory = category;
                renderSettings();
            };
            
            return tab;
        }
        
        // Render settings based on active category
        function renderSettings() {
            const container = document.getElementById('settingsContainer');
            
            if (Object.keys(settingsData).length === 0) {
                container.innerHTML = '<div class="empty-state"><i class="fas fa-cogs"></i><h3>No settings found</h3></div>';
                return;
            }
            
            let filteredSettings = {};
            
            if (activeCategory === 'all') {
                filteredSettings = settingsData;
            } else {
                Object.keys(settingsData).forEach(key => {
                    if (settingsData[key].category === activeCategory) {
                        filteredSettings[key] = settingsData[key];
                    }
                });
            }
            
            if (Object.keys(filteredSettings).length === 0) {
                container.innerHTML = `<div class="empty-state"><i class="fas fa-filter"></i><h3>No settings in ${categories[activeCategory]?.name || activeCategory} category</h3></div>`;
                return;
            }
            
            // Group by category for 'all' view
            if (activeCategory === 'all') {
                const groupedSettings = {};
                
                Object.keys(filteredSettings).forEach(key => {
                    const setting = filteredSettings[key];
                    if (!groupedSettings[setting.category]) {
                        groupedSettings[setting.category] = {};
                    }
                    groupedSettings[setting.category][key] = setting;
                });
                
                let html = '';
                Object.keys(groupedSettings).forEach(category => {
                    html += createCategorySection(category, groupedSettings[category]);
                });
                container.innerHTML = html;
            } else {
                container.innerHTML = createCategorySection(activeCategory, filteredSettings);
            }
            
            // Attach event listeners to controls
            attachEventListeners();
        }
        
        // Create category section
        function createCategorySection(category, settings) {
            const catConfig = categories[category] || { name: category, icon: 'fas fa-cog' };
            
            let html = `
                <div class="category-section">
                    <div class="category-title">
                        <i class="${catConfig.icon}"></i>
                        ${catConfig.name}
                    </div>
                    <div class="settings-grid">
            `;
            
            Object.keys(settings).forEach(key => {
                html += createSettingCard(key, settings[key]);
            });
            
            html += `
                    </div>
                </div>
            `;
            
            return html;
        }
        
        // Create individual setting card
        function createSettingCard(key, setting) {
            const isChanged = key in changedSettings;
            const valueClass = setting.type === 'boolean' ? (setting.value === 'true' ? 'true' : 'false') : '';
            
            return `
                <div class="setting-card category-${setting.category} ${isChanged ? 'changed' : ''}" 
                     style="${isChanged ? 'border-color: #f59e0b;' : ''}">
                    <div class="setting-header">
                        <div class="setting-name">
                            ${setting.display_name || key}
                            ${isChanged ? '<i class="fas fa-pencil-alt" style="color: #f59e0b; margin-left: 8px;"></i>' : ''}
                        </div>
                        <div class="setting-key">${key}</div>
                    </div>
                    <div class="setting-description">
                        ${setting.description || 'No description available'}
                    </div>
                    <div class="setting-control">
                        ${createControlInput(key, setting)}
                        <div class="value-display ${valueClass}" id="value-display-${key}">
                            ${formatValue(setting.value, setting.type)}
                        </div>
                    </div>
                </div>
            `;
        }
        
        // Create control input based on type
        function createControlInput(key, setting) {
            const value = setting.value;
            const type = setting.type;
            
            if (type === 'boolean') {
                const checked = value === 'true' || value === '1' || value === true;
                return `
                    <label class="toggle-switch">
                        <input type="checkbox" id="input-${key}" 
                               data-key="${key}" ${checked ? 'checked' : ''}>
                        <span class="toggle-slider"></span>
                    </label>
                `;
            } else if (type === 'integer') {
                return `
                    <input type="number" id="input-${key}" 
                           class="number-input" data-key="${key}"
                           value="${value}" min="0" step="1">
                `;
            } else if (type === 'string') {
                // For "1"/"0" strings, show toggle
                if (value === '1' || value === '0') {
                    const checked = value === '1';
                    return `
                        <label class="toggle-switch">
                            <input type="checkbox" id="input-${key}" 
                                   data-key="${key}" ${checked ? 'checked' : ''}>
                            <span class="toggle-slider"></span>
                        </label>
                    `;
                } else {
                    // For other strings, show text input
                    return `
                        <input type="text" id="input-${key}" 
                               class="text-input" data-key="${key}"
                               value="${value}">
                    `;
                }
            }
            
            return `<span>Unsupported type: ${type}</span>`;
        }
        
        // Format value for display
        function formatValue(value, type) {
            if (type === 'boolean') {
                return value === 'true' || value === '1' || value === true ? 'true' : 'false';
            }
            return String(value);
        }
        
        // Attach event listeners to controls
        function attachEventListeners() {
            document.querySelectorAll('input[type="checkbox"]').forEach(input => {
                input.addEventListener('change', handleSettingChange);
            });
            
            document.querySelectorAll('input[type="number"], input[type="text"]').forEach(input => {
                input.addEventListener('input', handleSettingChange);
            });
        }
        
        // Handle setting change
        function handleSettingChange(event) {
            const input = event.target;
            const key = input.dataset.key;
            let newValue;
            
            if (input.type === 'checkbox') {
                newValue = input.checked ? 
                    (settingsData[key].type === 'boolean' ? 'true' : '1') : 
                    (settingsData[key].type === 'boolean' ? 'false' : '0');
            } else if (input.type === 'number') {
                newValue = input.value || '0';
            } else {
                newValue = input.value;
            }
            
            // Update local data
            settingsData[key].value = newValue;
            
            // Update display
            const display = document.getElementById(`value-display-${key}`);
            if (display) {
                const valueClass = settingsData[key].type === 'boolean' ? 
                    (newValue === 'true' ? 'true' : 'false') : '';
                display.className = `value-display ${valueClass}`;
                display.textContent = formatValue(newValue, settingsData[key].type);
            }
            
            // Track changes
            const originalValue = originalSettings[key]?.value;
            if (originalValue !== newValue) {
                changedSettings[key] = newValue;
            } else {
                delete changedSettings[key];
            }
            
            // Update UI
            checkForChanges();
            updateStats();
            
            // Highlight changed card
            const card = input.closest('.setting-card');
            if (card) {
                if (key in changedSettings) {
                    card.style.borderColor = '#f59e0b';
                    card.style.boxShadow = '0 5px 15px rgba(245, 158, 11, 0.2)';
                } else {
                    card.style.borderColor = '';
                    card.style.boxShadow = '';
                }
            }
        }
        
        // Check for changes and update save button
        function checkForChanges() {
            const saveBtn = document.getElementById('saveBtn');
            const hasChanges = Object.keys(changedSettings).length > 0;
            
            saveBtn.disabled = !hasChanges;
            saveBtn.innerHTML = hasChanges ? 
                `<i class="fas fa-save"></i> Save Changes (${Object.keys(changedSettings).length})` :
                `<i class="fas fa-save"></i> Save Changes`;
        }
        
        // Update statistics
        function updateStats() {
            const total = Object.keys(settingsData).length;
            let active = 0;
            
            Object.keys(settingsData).forEach(key => {
                const setting = settingsData[key];
                if (setting.type === 'boolean' || setting.type === 'string') {
                    if (setting.value === 'true' || setting.value === '1') {
                        active++;
                    }
                }
            });
            
            document.getElementById('activeCount').textContent = active;
            document.getElementById('totalCount').textContent = total;
            document.getElementById('changedCount').textContent = Object.keys(changedSettings).length;
            document.getElementById('lastUpdate').textContent = new Date().toLocaleTimeString();
        }
        
        // Save settings
        async function saveSettings() {
            const saveBtn = document.getElementById('saveBtn');
            const originalHtml = saveBtn.innerHTML;
            
            try {
                // Show loading
                saveBtn.innerHTML = '<div class="loader"></div> Saving...';
                saveBtn.disabled = true;
                
                // Prepare data
                const dataToSave = {};
                Object.keys(changedSettings).forEach(key => {
                    dataToSave[key] = settingsData[key].value;
                });
                
                // Send to server
                const response = await fetch('?api=1&action=update', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(dataToSave)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Update original settings
                    Object.keys(changedSettings).forEach(key => {
                        originalSettings[key].value = settingsData[key].value;
                    });
                    
                    // Clear changed settings
                    changedSettings = {};
                    
                    // Update UI
                    checkForChanges();
                    updateStats();
                    
                    showNotification('Settings saved successfully!', 'success');
                    
                    // Remove highlights
                    document.querySelectorAll('.setting-card').forEach(card => {
                        card.style.borderColor = '';
                        card.style.boxShadow = '';
                    });
                } else {
                    showNotification('Failed to save: ' + (result.message || 'Unknown error'), 'error');
                }
            } catch (error) {
                showNotification('Network error: ' + error.message, 'error');
            } finally {
                // Restore button
                saveBtn.innerHTML = originalHtml;
                checkForChanges();
            }
        }
        
        // Reset settings
        async function resetSettings() {
            if (Object.keys(changedSettings).length === 0) {
                showNotification('No changes to reset', 'info');
                return;
            }
            
            if (!confirm(`Reset ${Object.keys(changedSettings).length} changed setting(s) to original values?`)) {
                return;
            }
            
            try {
                // Reset to original values
                Object.keys(changedSettings).forEach(key => {
                    settingsData[key].value = originalSettings[key].value;
                });
                
                // Clear changed settings
                changedSettings = {};
                
                // Update UI
                renderSettings();
                checkForChanges();
                updateStats();
                
                showNotification('All changes reset to original values', 'success');
            } catch (error) {
                showNotification('Reset failed: ' + error.message, 'error');
            }
        }
        
        // Reset all to defaults
        async function resetToDefaults() {
            if (!confirm('Reset ALL settings to default values? This cannot be undone.')) {
                return;
            }
            
            const resetBtn = document.getElementById('resetBtn');
            const originalHtml = resetBtn.innerHTML;
            
            try {
                resetBtn.innerHTML = '<div class="loader"></div> Resetting...';
                resetBtn.disabled = true;
                
                const response = await fetch('?api=1&action=reset');
                const result = await response.json();
                
                if (result.success) {
                    // Reload settings
                    await loadSettings();
                    showNotification('All settings reset to defaults', 'success');
                } else {
                    showNotification('Reset failed: ' + (result.message || 'Unknown error'), 'error');
                }
            } catch (error) {
                showNotification('Network error: ' + error.message, 'error');
            } finally {
                resetBtn.innerHTML = originalHtml;
                resetBtn.disabled = false;
            }
        }
        
        // Setup event listeners
        function setupEventListeners() {
            // Keyboard shortcuts
            document.addEventListener('keydown', function(e) {
                // Ctrl + S to save
                if (e.ctrlKey && e.key === 's') {
                    e.preventDefault();
                    if (!document.getElementById('saveBtn').disabled) {
                        saveSettings();
                    }
                }
                
                // Ctrl + R to reset
                if (e.ctrlKey && e.key === 'r') {
                    e.preventDefault();
                    resetSettings();
                }
            });
            
            // Export/Import (optional features)
            document.addEventListener('keydown', function(e) {
                // Ctrl + E to export
                if (e.ctrlKey && e.key === 'e') {
                    e.preventDefault();
                    exportSettings();
                }
            });
        }
        
        // Show/hide loading
        function showLoading(show) {
            const loadingState = document.getElementById('loadingState');
            const settingsContainer = document.getElementById('settingsContainer');
            
            if (show) {
                loadingState.style.display = 'block';
                settingsContainer.innerHTML = '';
            } else {
                loadingState.style.display = 'none';
            }
        }
        
        // Show notification
        function showNotification(message, type = 'success') {
            const notification = document.getElementById('notification');
            const text = document.getElementById('notificationText');
            
            // Set icon based on type
            let icon = 'fas fa-check-circle';
            if (type === 'error') icon = 'fas fa-exclamation-circle';
            if (type === 'warning') icon = 'fas fa-exclamation-triangle';
            if (type === 'info') icon = 'fas fa-info-circle';
            
            notification.innerHTML = `<i class="${icon}"></i> <span id="notificationText">${message}</span>`;
            notification.className = `notification ${type}`;
            notification.style.display = 'flex';
            
            // Auto hide
            setTimeout(() => {
                notification.style.display = 'none';
            }, 4000);
        }
        
        // Export settings (additional feature)
        function exportSettings() {
            const data = {
                settings: settingsData,
                export_date: new Date().toISOString(),
                total_settings: Object.keys(settingsData).length
            };
            
            const dataStr = JSON.stringify(data, null, 2);
            const dataUri = 'data:application/json;charset=utf-8,' + encodeURIComponent(dataStr);
            
            const exportFileDefaultName = `settings-export-${new Date().toISOString().split('T')[0]}.json`;
            
            const linkElement = document.createElement('a');
            linkElement.setAttribute('href', dataUri);
            linkElement.setAttribute('download', exportFileDefaultName);
            linkElement.click();
            
            showNotification('Settings exported successfully', 'info');
        }
        
        // Import settings (additional feature)
        function importSettings(event) {
            const file = event.target.files[0];
            if (!file) return;
            
            const reader = new FileReader();
            reader.onload = function(e) {
                try {
                    const imported = JSON.parse(e.target.result);
                    
                    if (imported.settings) {
                        // Merge imported settings
                        Object.keys(imported.settings).forEach(key => {
                            if (settingsData[key]) {
                                settingsData[key].value = imported.settings[key].value;
                            }
                        });
                        
                        renderSettings();
                        checkForChanges();
                        updateStats();
                        
                        showNotification('Settings imported successfully', 'success');
                    } else {
                        showNotification('Invalid settings file format', 'error');
                    }
                } catch (error) {
                    showNotification('Error importing settings: ' + error.message, 'error');
                }
            };
            reader.readAsText(file);
        }
    </script>
</body>
</html>