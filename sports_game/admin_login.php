<?php
session_start();

require_once __DIR__ . '/lang.php';
require_once __DIR__ . '/admin_security.php';

$db = open_app_db();
ensure_admin_table($db);
seed_default_admin($db);

$error = '';
$username = '';

function sanitize_redirect($target)
{
    if (!$target) {
        return 'admin_dashboard.php';
    }

    $decoded = urldecode($target);
    $target = trim($decoded);

    if ($target === '' || stripos($target, 'http://') === 0 || stripos($target, 'https://') === 0) {
        return 'admin_dashboard.php';
    }

    if (strpos($target, 'admin_login.php') === 0) {
        return 'admin_dashboard.php';
    }

    return $target;
}

if (!empty($_SESSION['admin_user'])) {
    $redirect = sanitize_redirect($_GET['redirect'] ?? 'admin_dashboard.php');
    header('Location: ' . $redirect);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
    $error = __('请输入用户名和密码。', 'Please enter both username and password.');
    } else {
        $stmt = $db->prepare('SELECT id, username, password_hash FROM AdminUsers WHERE username = :username');
        $stmt->bindValue(':username', $username, SQLITE3_TEXT);
        $result = $stmt->execute();
        $row = $result ? $result->fetchArray(SQLITE3_ASSOC) : null;

        if ($row && password_verify($password, $row['password_hash'])) {
            $_SESSION['admin_user'] = [
                'id' => $row['id'],
                'username' => $row['username']
            ];
            update_admin_timestamp($db, (int) $row['id']);
            $redirect = sanitize_redirect($_GET['redirect'] ?? 'admin_dashboard.php');
            header('Location: ' . $redirect);
            exit;
        }

    $error = __('用户名或密码错误。', 'Invalid username or password.');
    }
}
?>
<!DOCTYPE html>
<html lang="<?= app_lang_html_attr(); ?>">
<head>
    <meta charset="UTF-8">
    <title><?= __('管理员登录', 'Admin Login'); ?></title>
    <link rel="stylesheet" href="assets/ui.css">
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-shell">
        <section class="auth-hero">
            <div class="brand-pill"><?= __('管理员控制台', 'Admin Console'); ?></div>
            <h1><?= __('管理员登录', 'Admin Login'); ?></h1>
            <p><?= __('请输入管理员账户以进入后台系统。', 'Enter your admin credentials to access the console.'); ?></p>
            <ul class="auth-highlights">
                <li><?= __('统一入口覆盖所有模块', 'Single entry point for every module'); ?></li>
                <li><?= __('更大的可视区域，适配不同分辨率', 'Expanded viewport that adapts to any resolution'); ?></li>
                <li><?= __('多语言提示，保护操作安全', 'Bilingual guidance keeps operations safe'); ?></li>
            </ul>
            <div class="helper-text">
                <p><?= __('首个管理员账号已自动创建：', 'The default administrator account is pre-created:'); ?></p>
                <p><code>admin / 12345678</code> <?= __('（密码需为 8 位纯数字，可在“管理员账号”页面修改）。', '(Password must be 8 digits and can be changed via “Admin users”.'); ?></p>
            </div>
            <a class="btn btn-ghost btn-wide" href="index.php?lang=<?= app_lang_get(); ?>">← <?= __('返回首页', 'Back to home'); ?></a>
        </section>

        <section class="auth-card auth-form-panel">
            <div class="auth-form-header">
                <div>
                    <p class="eyebrow"><?= __('安全登录', 'Secure login'); ?></p>
                    <h2><?= __('立即进入后台', 'Access the console now'); ?></h2>
                    <p><?= __('请在下方输入管理员登录信息。', 'Enter your admin credentials below.'); ?></p>
                </div>
                <div class="auth-form-lang">
                    <?= render_lang_toggle(); ?>
                </div>
            </div>

            <?php if ($error !== ''): ?>
                <div class="error-box stacked"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="post" class="auth-form-grid">
                <div class="form-group">
                    <label for="username"><?= __('用户名', 'Username'); ?></label>
                    <input type="text" id="username" name="username" value="<?= htmlspecialchars($username) ?>" autocomplete="username" required>
                </div>
                <div class="form-group">
                    <label for="password"><?= __('密码', 'Password'); ?></label>
                    <input type="password" id="password" name="password" autocomplete="current-password" required>
                </div>
                <button class="btn btn-primary btn-wide" type="submit">
                    <?= __('登录后台', 'Sign in to admin'); ?>
                </button>
            </form>
        </section>
    </div>
</div>
</body>
</html>
