<?php
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/admin_security.php';

$db = open_app_db();
ensure_admin_table($db);

$errors = [];

function validate_username(string $username): bool
{
    return preg_match('/^[A-Za-z0-9_]{4,20}$/', $username) === 1;
}

function validate_numeric_password(string $password): bool
{
    return preg_match('/^\d{8}$/', $password) === 1;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_user'])) {
        $newUsername = trim($_POST['new_username'] ?? '');
        $newPassword = trim($_POST['new_password'] ?? '');

        if (!validate_username($newUsername)) {
            $errors[] = 'Username must be 4-20 characters (letters, numbers, underscores).';
        }

        if (!validate_numeric_password($newPassword)) {
            $errors[] = 'Password must be exactly 8 digits (0-9).';
        }

        if (empty($errors)) {
            $checkStmt = $db->prepare('SELECT COUNT(*) AS cnt FROM AdminUsers WHERE username = :username');
            $checkStmt->bindValue(':username', $newUsername, SQLITE3_TEXT);
            $checkResult = $checkStmt->execute();
            $exists = $checkResult ? (int) $checkResult->fetchArray(SQLITE3_ASSOC)['cnt'] : 0;

            if ($exists > 0) {
                $errors[] = 'Username already exists. Choose a different one.';
            } else {
                $hash = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $db->prepare('INSERT INTO AdminUsers (username, password_hash, is_default) VALUES (:username, :hash, 0)');
                $stmt->bindValue(':username', $newUsername, SQLITE3_TEXT);
                $stmt->bindValue(':hash', $hash, SQLITE3_TEXT);
                $stmt->execute();
                flash_success('New administrator created successfully.');
                header('Location: admin_users.php');
                exit;
            }
        }
    }

    if (isset($_POST['update_password'])) {
        $targetId = (int) ($_POST['target_id'] ?? 0);
        $newPassword = trim($_POST['reset_password'] ?? '');

        if ($targetId <= 0) {
            $errors[] = 'Invalid administrator selected.';
        } elseif (!validate_numeric_password($newPassword)) {
            $errors[] = 'Password must be exactly 8 digits (0-9).';
        } else {
            $fetch = $db->prepare('SELECT password_hash FROM AdminUsers WHERE id = :id');
            $fetch->bindValue(':id', $targetId, SQLITE3_INTEGER);
            $result = $fetch->execute();
            $row = $result ? $result->fetchArray(SQLITE3_ASSOC) : null;

            if (!$row) {
                $errors[] = 'Administrator not found.';
            } elseif (password_verify($newPassword, $row['password_hash'])) {
                $errors[] = 'New password must be different from the current password.';
            } else {
                $hash = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $db->prepare('UPDATE AdminUsers SET password_hash = :hash, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
                $stmt->bindValue(':hash', $hash, SQLITE3_TEXT);
                $stmt->bindValue(':id', $targetId, SQLITE3_INTEGER);
                $stmt->execute();
                flash_success('Password updated successfully.');
                header('Location: admin_users.php');
                exit;
            }
        }
    }
}

if (isset($_GET['delete'])) {
    $deleteId = (int) $_GET['delete'];
    $currentId = (int) ($_SESSION['admin_user']['id'] ?? 0);
    $totalUsers = (int) $db->querySingle('SELECT COUNT(*) FROM AdminUsers');

    if ($deleteId === $currentId) {
        $errors[] = 'You cannot delete the account you are currently using.';
        flash_error('Deletion failed: you cannot remove the active session account.');
    } elseif ($totalUsers <= 1) {
        $errors[] = 'At least one administrator must remain in the system.';
        flash_error('Deletion failed: at least one administrator must remain.');
    } else {
        $stmt = $db->prepare('DELETE FROM AdminUsers WHERE id = :id');
        $stmt->bindValue(':id', $deleteId, SQLITE3_INTEGER);
        $stmt->execute();
        flash_success('Administrator removed successfully.');
    }

    header('Location: admin_users.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($errors)) {
    flash_error('Operation failed. Please review the highlighted errors.');
}

$users = [];
$result = $db->query('SELECT id, username, is_default, last_login, created_at FROM AdminUsers ORDER BY username');
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $users[] = $row;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>管理员账号管理</title>
    <link rel="stylesheet" href="assets/ui.css">
</head>
<body>
<div class="app-shell">
    <?php render_flash_toasts(); ?>
    <header class="page-header">
        <div>
            <p>Admin Console</p>
            <h1>管理员账号管理</h1>
            <p>新增、重置或删除后台管理员账号，所有操作都会产生即时提示</p>
        </div>
        <div class="header-actions">
            <a class="btn btn-ghost" href="index.php?lang=<?= app_lang_get(); ?>">🏠 返回主页</a>
            <a class="btn btn-ghost" href="admin_dashboard.php">← 返回仪表盘</a>
        </div>
    </header>

    <?php if (!empty($errors)): ?>
        <div class="error-box">
            <?php foreach ($errors as $msg): ?>
                <div><?= htmlspecialchars($msg) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <section class="app-card">
        <h2 class="card-title">创建管理员</h2>
        <p class="card-subtitle">用户名支持 4-20 位字母/数字/下划线，密码必须为 8 位纯数字。</p>
        <form method="post" class="form-grid">
            <input type="hidden" name="create_user" value="1">
            <div class="form-group">
                <label for="new_username">用户名</label>
                <input type="text" id="new_username" name="new_username" placeholder="4-20 chars, A-Z 0-9 _" required>
            </div>
            <div class="form-group">
                <label for="new_password">密码</label>
                <input type="password" id="new_password" name="new_password" placeholder="8-digit numeric" pattern="\d{8}" required>
            </div>
            <div class="form-actions">
                <button class="btn btn-primary" type="submit">创建管理员</button>
            </div>
        </form>
    </section>

    <section class="app-card">
        <h2 class="card-title">重置管理员密码</h2>
        <p class="card-subtitle">选择目标账号并输入全新的 8 位数字密码。</p>
        <form method="post" class="form-grid">
            <input type="hidden" name="update_password" value="1">
            <div class="form-group">
                <label for="target_id">选择管理员</label>
                <select id="target_id" name="target_id" required>
                    <option value="">请选择账号</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?= (int) $user['id'] ?>"><?= htmlspecialchars($user['username']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="reset_password">新密码</label>
                <input type="password" id="reset_password" name="reset_password" placeholder="8-digit numeric" pattern="\d{8}" required>
            </div>
            <div class="form-actions">
                <button class="btn btn-secondary" type="submit">更新密码</button>
            </div>
        </form>
    </section>

    <section class="app-card">
        <h2 class="card-title">当前管理员</h2>
        <div class="table-wrapper">
            <table>
                <thead>
                <tr>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Last Login</th>
                    <th>Created</th>
                    <th>操作</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= htmlspecialchars($user['username']) ?></td>
                        <td>
                            <?php if ((int) $user['is_default'] === 1): ?>
                                <span class="status-badge badge-default">Default</span>
                            <?php else: ?>
                                <span class="status-badge badge-custom">Custom</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $user['last_login'] ? htmlspecialchars($user['last_login']) : '—' ?></td>
                        <td><?= htmlspecialchars($user['created_at']) ?></td>
                        <td class="table-actions">
                            <?php if ((int) $user['id'] !== (int) ($_SESSION['admin_user']['id'] ?? 0)): ?>
                                <a class="delete" href="admin_users.php?delete=<?= (int) $user['id'] ?>" onclick="return confirm('确认删除该管理员账号？');">删除</a>
                            <?php else: ?>
                                <em>当前登录</em>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
</body>
</html>
