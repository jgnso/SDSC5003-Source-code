<?php
require_once __DIR__ . '/auth_guard.php';

$db = new SQLite3(__DIR__ . '/data.db');

function clean($value): string
{
    return trim((string) $value);
}

$id = clean($_GET['id'] ?? '');
if ($id === '') {
    flash_error('缺少大项编号。');
    header('Location: admin_category.php');
    exit;
}

$stmt = $db->prepare('SELECT * FROM Category WHERE Category_id = :id');
$stmt->bindValue(':id', $id, SQLITE3_TEXT);
$record = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

if (!$record) {
    flash_error('未找到对应的大项。');
    header('Location: admin_category.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>编辑大项</title>
    <link rel="stylesheet" href="assets/ui.css">
</head>
<body>
<div class="app-shell">
    <?php render_flash_toasts(); ?>
    <header class="page-header">
        <div>
            <p>Admin Console</p>
            <h1>编辑大项</h1>
            <p><?= htmlspecialchars($record['Category_id']) ?> · <?= htmlspecialchars($record['Category_name']) ?></p>
        </div>
        <div class="header-actions">
            <a class="btn btn-ghost" href="index.php?lang=<?= app_lang_get(); ?>">🏠 返回主页</a>
            <a class="btn btn-ghost" href="admin_category.php">← 返回大项列表</a>
        </div>
    </header>

    <section class="app-card">
        <form method="POST" action="admin_category.php" class="form-grid">
            <input type="hidden" name="update" value="1">
            <input type="hidden" name="Category_id" value="<?= htmlspecialchars($record['Category_id']); ?>">

            <div class="form-group">
                <label>大项编号</label>
                <input type="text" value="<?= htmlspecialchars($record['Category_id']); ?>" disabled>
            </div>

            <div class="form-group">
                <label for="Category_name">大项名称</label>
                <input type="text" id="Category_name" name="Category_name" value="<?= htmlspecialchars($record['Category_name']); ?>" required>
            </div>

            <div class="form-group">
                <label for="Manager">负责人</label>
                <input type="text" id="Manager" name="Manager" value="<?= htmlspecialchars($record['Manager']); ?>">
            </div>

            <div class="form-actions">
                <button class="btn btn-primary" type="submit">保存修改</button>
                <a class="btn btn-ghost" href="admin_category.php">取消</a>
            </div>
        </form>
    </section>
</div>
</body>
</html>
