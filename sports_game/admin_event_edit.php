<?php
require_once __DIR__ . '/auth_guard.php';

$db = new SQLite3(__DIR__ . '/data.db');

function clean($value): string
{
    return trim((string) $value);
}

$id = clean($_GET['id'] ?? '');
if ($id === '') {
    header('Location: admin_event.php');
    exit;
}

$stmt = $db->prepare('SELECT * FROM Event WHERE EventID = :id');
$stmt->bindValue(':id', $id, SQLITE3_TEXT);
$row = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

if (!$row) {
    header('Location: admin_event.php');
    exit;
}

$categories = [];
$categoryStmt = $db->query('SELECT Category_id, Category_name FROM Category ORDER BY Category_name');
while ($category = $categoryStmt->fetchArray(SQLITE3_ASSOC)) {
    $categories[] = $category;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>编辑赛事</title>
    <link rel="stylesheet" href="assets/ui.css">
</head>
<body>
<div class="app-shell">
    <header class="page-header">
        <div>
            <p>Admin Console</p>
            <h1>编辑赛事</h1>
            <p><?= htmlspecialchars($row['EventID']) ?> · <?= htmlspecialchars($row['EventName']) ?></p>
        </div>
        <div class="header-actions">
            <a class="btn btn-ghost" href="index.php?lang=<?= app_lang_get(); ?>">🏠 返回主页</a>
            <a class="btn btn-ghost" href="admin_event.php">← 返回赛事列表</a>
        </div>
    </header>

    <section class="app-card">
        <form method="POST" action="admin_event.php" class="form-grid">
            <input type="hidden" name="update" value="1">
            <input type="hidden" name="EventID" value="<?= htmlspecialchars($row['EventID']) ?>">

            <div class="form-group">
                <label for="CategoryID">所属大项</label>
                <select id="CategoryID" name="CategoryID" required>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= htmlspecialchars($category['Category_id']) ?>" <?= ($category['Category_id'] === $row['CategoryID']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($category['Category_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="EventName">赛事名称</label>
                <input type="text" id="EventName" name="EventName" value="<?= htmlspecialchars($row['EventName']) ?>" required>
            </div>

            <div class="form-group">
                <label for="Level">级别</label>
                <input type="text" id="Level" name="Level" value="<?= htmlspecialchars($row['Level']) ?>" placeholder="如：预赛 / 决赛">
            </div>

            <div class="form-actions">
                <button class="btn btn-primary" type="submit">保存修改</button>
                <a class="btn btn-ghost" href="admin_event.php">取消</a>
            </div>
        </form>
    </section>
</div>
</body>
</html>
