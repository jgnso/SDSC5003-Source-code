<?php
require_once __DIR__ . '/auth_guard.php';

$db = new SQLite3(__DIR__ . '/data.db');

function clean($value): string
{
    return trim((string) $value);
}

$id = clean($_GET['id'] ?? '');
if ($id === '') {
    flash_error('缺少代表团编号。');
    header('Location: admin_delegation.php');
    exit;
}

$stmt = $db->prepare('SELECT * FROM Delegation WHERE Delegation_id = :id');
$stmt->bindValue(':id', $id, SQLITE3_TEXT);
$record = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

if (!$record) {
    flash_error('未找到对应的代表团。');
    header('Location: admin_delegation.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>编辑代表团</title>
    <link rel="stylesheet" href="assets/ui.css">
</head>
<body>
<div class="app-shell">
    <?php render_flash_toasts(); ?>
    <header class="page-header">
        <div>
            <p>Admin Console</p>
            <h1>编辑代表团</h1>
            <p><?= htmlspecialchars($record['Delegation_id']) ?> · <?= htmlspecialchars($record['Region']) ?></p>
        </div>
        <div class="header-actions">
            <a class="btn btn-ghost" href="index.php?lang=<?= app_lang_get(); ?>">🏠 返回主页</a>
            <a class="btn btn-ghost" href="admin_delegation.php">← 返回代表团列表</a>
        </div>
    </header>

    <section class="app-card">
        <form method="POST" action="admin_delegation.php" class="form-grid">
            <input type="hidden" name="update" value="1">
            <input type="hidden" name="Delegation_id" value="<?= htmlspecialchars($record['Delegation_id']); ?>">

            <div class="form-group">
                <label>编号</label>
                <input type="text" value="<?= htmlspecialchars($record['Delegation_id']); ?>" disabled>
            </div>

            <div class="form-group">
                <label for="Region">地区</label>
                <input type="text" id="Region" name="Region" value="<?= htmlspecialchars($record['Region']); ?>" required>
            </div>

            <div class="form-group">
                <label for="Address">驻地地址</label>
                <input type="text" id="Address" name="Address" value="<?= htmlspecialchars($record['Address']); ?>">
            </div>

            <div class="form-actions">
                <button class="btn btn-primary" type="submit">保存修改</button>
                <a class="btn btn-ghost" href="admin_delegation.php">取消</a>
            </div>
        </form>
    </section>
</div>
</body>
</html>
