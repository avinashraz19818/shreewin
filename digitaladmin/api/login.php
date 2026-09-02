<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../login.php');
    exit;
}

// A login request only needs the existing admin account table. Skipping the
// optional runtime migrations here keeps authentication fast and prevents an
// unrelated schema permission issue from turning the login page into HTTP 500.
define('ADMIN_LOGIN_REQUEST', true);
define('ADMIN_SKIP_RUNTIME_SCHEMA', true);
require_once __DIR__ . '/conn.php';

$username = trim((string)($_POST['username'] ?? ''));
$password = (string)($_POST['password'] ?? '');
if ($username === '' || $password === '') {
    header('Location: ../login.php?err=true');
    exit;
}

$passwordHash = md5($password); // Preserve the existing admin credential format.
try {
    $stmt = $conn->prepare("SELECT unohs,nirvahaka_hesaru FROM nirvahaka_shonu WHERE nirvahaka_hesaru=? AND guptapada=? AND sthiti='1' LIMIT 1");
    if (!$stmt) {
        throw new RuntimeException('Unable to prepare admin login query');
    }
    if (!$stmt->bind_param('ss', $username, $passwordHash) || !$stmt->execute()) {
        $stmt->close();
        throw new RuntimeException('Unable to execute admin login query');
    }

    // mysqlnd is not installed on every shared/cPanel host, so get_result()
    // cannot be used here. bind_result()/fetch() works with standard mysqli.
    $adminId = null;
    $adminName = null;
    $stmt->store_result();
    $stmt->bind_result($adminId, $adminName);
    $authenticated = ($stmt->num_rows === 1 && $stmt->fetch());
    $stmt->close();

    if ($authenticated) {
        session_regenerate_id(true);
        $_SESSION['unohs'] = $adminId;
        $_SESSION['nirvahaka_hesaru'] = $adminName;
        header('Location: ../index.php');
        exit;
    }
} catch (Throwable $e) {
    error_log('[admin login] ' . $e->getMessage());
    header('Location: ../login.php?server=true');
    exit;
}

header('Location: ../login.php?err=true');
exit;
