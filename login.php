<?php
session_start();

// ==========================================
// 🔐 SECURITY SETTINGS (APNA PASSWORD YAHAN BADLEIN)
// ==========================================
$ADMIN_PASSWORD = "bhai_ka_password_123"; 

// Login Logic
if (isset($_POST['login'])) {
    if ($_POST['password'] === $ADMIN_PASSWORD) {
        $_SESSION['logged_in'] = true;
    } else {
        $login_error = "Galat Password Bhai!";
    }
}
// Logout Logic
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ?");
    exit;
}

// Agar login nahi hai toh form dikhao
if (!isset($_SESSION['logged_in'])) {
?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Pro Finder - Login</title>
        <style>
            body { background-color: #0f172a; color: white; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
            .login-box { background: #1e293b; padding: 40px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); text-align: center; width: 300px; }
            input { width: 100%; padding: 12px; margin: 15px 0; border: none; border-radius: 6px; box-sizing: border-box; background: #334155; color: white; }
            button { background: #3b82f6; color: white; border: none; padding: 12px; width: 100%; border-radius: 6px; font-weight: bold; cursor: pointer; transition: 0.3s; }
            button:hover { background: #2563eb; }
        </style>
    </head>
    <body>
        <div class="login-box">
            <h2>🔒 Security Login</h2>
            <?php if(isset($login_error)) echo "<p style='color:#ef4444;'>$login_error</p>"; ?>
            <form method="POST">
                <input type="password" name="password" placeholder="Enter Password..." required>
                <button type="submit" name="login">Login</button>
            </form>
        </div>
    </body>
    </html>
<?php
    exit;
}

// ==========================================
// 🚀 MAIN TOOL LOGIC (SEARCH & REPLACE)
// ==========================================
$results = [];
$search_text = $_POST['search_text'] ?? '';
$replace_text = $_POST['replace_text'] ?? '';
$target_dir = $_POST['target_dir'] ?? __DIR__;
$file_types = explode(',', str_replace(' ', '', $_POST['file_types'] ?? '.js,.html,.css,.php,.txt,.json'));
$action = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $search_text !== '' && ($action === 'find' || $action === 'replace')) {
    
    // Directory check
    if (is_dir($target_dir)) {
        $iterator = new RecursiveDirectoryIterator($target_dir);
        $files = new RecursiveIteratorIterator($iterator);

        foreach ($files as $file) {
            if ($file->isFile()) {
                // Check File Extension
                $ext = '.' . pathinfo($file->getFilename(), PATHINFO_EXTENSION);
                if (in_array(strtolower($ext), array_map('strtolower', $file_types))) {
                    
                    // Khud is file (super_replace.php) ko skip karo taaki apna hi code na badal de!
                    if ($file->getRealPath() === __FILE__) continue;

                    $file_path = $file->getRealPath();
                    $content = file_get_contents($file_path);
                    
                    // Agar Text Mil Gaya
                    if (strpos($content, $search_text) !== false) {
                        $count = substr_count($content, $search_text);
                        
                        if ($action === 'replace') {
                            $new_content = str_replace($search_text, $replace_text, $content);
                            if (file_put_contents($file_path, $new_content) !== false) {
                                $results[] = ['file' => $file_path, 'status' => "<span style='color:#10b981;'>✅ Replaced $count times</span>"];
                            } else {
                                $results[] = ['file' => $file_path, 'status' => "<span style='color:#ef4444;'>❌ Permission Denied</span>"];
                            }
                        } else {
                            $results[] = ['file' => $file_path, 'status' => "<span style='color:#3b82f6;'>🔍 Found $count times</span>"];
                        }
                    }
                }
            }
        }
    } else {
        $error_msg = "Galat Directory Path!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pro Master Replacer</title>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f1f5f9; color: #1e293b; margin: 0; padding: 0; }
        .header { background: #1e293b; color: white; padding: 20px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .header h1 { margin: 0; font-size: 24px; }
        .logout-btn { background: #ef4444; color: white; text-decoration: none; padding: 8px 16px; border-radius: 6px; font-weight: bold; transition: 0.3s; }
        .logout-btn:hover { background: #dc2626; }
        .container { max-width: 1000px; margin: 30px auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 20px; }
        label { display: block; font-weight: bold; margin-bottom: 8px; color: #475569; }
        input[type="text"] { width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; font-size: 15px; }
        input[type="text"]:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2); }
        .btn-group { display: flex; gap: 15px; margin-top: 10px; }
        button { flex: 1; padding: 15px; border: none; border-radius: 6px; font-size: 16px; font-weight: bold; cursor: pointer; color: white; transition: 0.2s; }
        .btn-find { background-color: #3b82f6; } .btn-find:hover { background-color: #2563eb; }
        .btn-replace { background-color: #10b981; } .btn-replace:hover { background-color: #059669; }
        .table-wrap { margin-top: 30px; border-radius: 8px; overflow: hidden; border: 1px solid #e2e8f0; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th, td { padding: 15px; border-bottom: 1px solid #e2e8f0; }
        th { background: #f8fafc; color: #475569; font-weight: bold; }
        tr:hover { background: #f1f5f9; }
        .alert { padding: 15px; background: #fffbeb; color: #b45309; border-left: 4px solid #f59e0b; margin-bottom: 20px; border-radius: 4px; font-weight: 500; }
    </style>
</head>
<body>

    <div class="header">
        <h1>🚀 Pro Master File Replacer</h1>
        <a href="?logout=true" class="logout-btn">Log Out</a>
    </div>

    <div class="container">
        <div class="alert">
            ⚠️ <b>Bhai Ka Warning:</b> "Replace All" dabane se pehle files ka backup zaroor le lena. Ek baar file modify ho gayi toh undo nahi hoga!
        </div>

        <form method="POST">
            <div class="form-group">
                <label>Directory Path (Yahan search karna hai):</label>
                <input type="text" name="target_dir" value="<?php echo htmlspecialchars($target_dir); ?>" required>
            </div>

            <div class="form-group">
                <label>File Types (Kisme dhoondhna hai):</label>
                <input type="text" name="file_types" value="<?php echo htmlspecialchars(implode(',', $file_types)); ?>" required>
            </div>

            <div class="form-group">
                <label>Search Text / URL (Jo dhoondhna hai):</label>
                <input type="text" name="search_text" placeholder="e.g., shreewin.club9.eu.cc/ old-text" value="<?php echo htmlspecialchars($search_text); ?>" required>
            </div>

            <div class="form-group">
                <label>Replace With (Naya Text / URL):</label>
                <input type="text" name="replace_text" placeholder="e.g., shreewin.club9.eu.cc/ new-text" value="<?php echo htmlspecialchars($replace_text); ?>">
            </div>

            <div class="btn-group">
                <button type="submit" name="action" value="find" class="btn-find">🔍 Sirf Dhoondho (Preview)</button>
                <button type="submit" name="action" value="replace" class="btn-replace" onclick="return confirm('Bhai, pakka replace karna hai na? Backup le liya?')">⚙️ Replace All (DANGER)</button>
            </div>
        </form>

        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && $search_text !== ''): ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>File Path</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($results)): ?>
                            <tr><td colspan="2" style="text-align:center; color:#64748b;">Is folder mein aisi koi detail nahi mili.</td></tr>
                        <?php else: ?>
                            <?php foreach ($results as $res): ?>
                                <tr>
                                    <td width="20%"><?php echo $res['status']; ?></td>
                                    <td><code style="background:#f1f5f9; padding:4px 8px; border-radius:4px; font-size:13px;"><?php echo htmlspecialchars($res['file']); ?></code></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

    </div>

</body>
</html>