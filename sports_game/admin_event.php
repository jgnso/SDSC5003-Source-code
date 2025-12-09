<?php
require_once __DIR__ . '/auth_guard.php';

$db = new SQLite3(__DIR__ . '/data.db');
$errors = [];

function clean($value): string
{
    return trim((string) $value);
}

if (isset($_GET['delete'])) {
    $id = clean($_GET['delete'] ?? '');
    if ($id === '') {
        flash_error('删除失败：缺少赛事编号。');
    } else {
        $stmt = $db->prepare('DELETE FROM Event WHERE EventID = :id');
        $stmt->bindValue(':id', $id, SQLITE3_TEXT);
        $stmt->execute();
        if ($db->changes() > 0) {
            flash_success('赛事已删除。');
        } else {
            flash_error('未找到对应的赛事记录。');
        }
    }
    header('Location: admin_event.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $eventId = clean($_POST['EventID'] ?? '');
    $categoryId = clean($_POST['CategoryID'] ?? '');
    $eventName = clean($_POST['EventName'] ?? '');
    $level = clean($_POST['Level'] ?? '');

    if ($eventId === '' || $eventName === '' || $categoryId === '') {
        $errors[] = 'Event ID, Category, and Name are required.';
        flash_error('保存赛事失败：编号、所属大项与名称为必填项。');
    }

    if (empty($errors)) {
        if (isset($_POST['add'])) {
            $stmt = $db->prepare('INSERT INTO Event (EventID, CategoryID, EventName, Level) VALUES (:id, :category, :name, :level)');
            $stmt->bindValue(':id', $eventId, SQLITE3_TEXT);
            $stmt->bindValue(':category', $categoryId, SQLITE3_TEXT);
            $stmt->bindValue(':name', $eventName, SQLITE3_TEXT);
            $stmt->bindValue(':level', $level, SQLITE3_TEXT);

            try {
                $stmt->execute();
                flash_success('新增赛事成功。');
                header('Location: admin_event.php');
                exit;
            } catch (Exception $e) {
                $errors[] = 'Event ID already exists.';
                flash_error('新增赛事失败：编号已存在。');
            }
        }

        if (isset($_POST['update'])) {
            $stmt = $db->prepare('UPDATE Event SET CategoryID = :category, EventName = :name, Level = :level WHERE EventID = :id');
            $stmt->bindValue(':category', $categoryId, SQLITE3_TEXT);
            $stmt->bindValue(':name', $eventName, SQLITE3_TEXT);
            $stmt->bindValue(':level', $level, SQLITE3_TEXT);
            $stmt->bindValue(':id', $eventId, SQLITE3_TEXT);
            $stmt->execute();
            flash_success('赛事信息已更新。');
            header('Location: admin_event.php');
            exit;
        }
    }
}

$categoryOptions = [];
$categoryStmt = $db->query('SELECT Category_id, Category_name FROM Category ORDER BY Category_name');
while ($row = $categoryStmt->fetchArray(SQLITE3_ASSOC)) {
    $categoryOptions[] = $row;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>赛事管理</title>
    <link rel="stylesheet" href="assets/ui.css">
</head>
<body>
<div class="app-shell">
    <?php render_flash_toasts(); ?>
    <header class="page-header">
        <div>
            <p>Admin Console</p>
            <h1>赛事管理</h1>
            <p>维护赛事编号、所属大项与级别信息</p>
        </div>
        <div class="header-actions">
            <a class="btn btn-ghost" href="index.php?lang=<?= app_lang_get(); ?>">🏠 返回主页</a>
            <a class="btn btn-ghost" href="admin_dashboard.php">← 返回仪表盘</a>
            <a class="btn btn-ghost" href="logout.php" onclick="return confirm('<?= addslashes(__('确认退出登录？', 'Are you sure you want to log out?')); ?>');"><?= __('退出登录', 'Log out'); ?></a>
        </div>
    </header>

    <?php if (!empty($errors)): ?>
        <div class="error-box">
            <?php foreach ($errors as $message): ?>
                <div><?= htmlspecialchars($message) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <section class="app-card">
        <h2 class="card-title">新增赛事</h2>
        <form method="POST" class="form-grid">
            <input type="hidden" name="add" value="1">
            <div class="form-group">
                <label for="EventID">赛事编号</label>
                <input type="text" id="EventID" name="EventID" value="<?= isset($_POST['EventID']) ? htmlspecialchars($_POST['EventID']) : '' ?>" required>
            </div>
            <div class="form-group">
                <label for="CategoryID">所属大项</label>
                <select id="CategoryID" name="CategoryID" required>
                    <option value="">请选择大项</option>
                    <?php foreach ($categoryOptions as $category): ?>
                        <option value="<?= htmlspecialchars($category['Category_id']) ?>" <?= (($_POST['CategoryID'] ?? '') === $category['Category_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($category['Category_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="EventName">赛事名称</label>
                <input type="text" id="EventName" name="EventName" value="<?= isset($_POST['EventName']) ? htmlspecialchars($_POST['EventName']) : '' ?>" required>
            </div>
            <div class="form-group">
                <label for="Level">级别</label>
                <input type="text" id="Level" name="Level" value="<?= isset($_POST['Level']) ? htmlspecialchars($_POST['Level']) : '' ?>" placeholder="例如：决赛、半决赛">
            </div>
            <div class="form-actions">
                <button class="btn btn-primary" type="submit">保存赛事</button>
            </div>
        </form>
    </section>

    <section class="app-card">
        <h2 class="card-title">已有赛事</h2>
        <div class="table-wrapper">
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>名称</th>
                    <th>级别</th>
                    <th>所属大项</th>
                    <th>操作</th>
                </tr>
                </thead>
                <tbody>
                <?php
                $sql = '
SELECT Event.*, Category.Category_name
FROM Event
LEFT JOIN Category ON Event.CategoryID = Category.Category_id
';

                $rs = $db->query($sql);

                while ($row = $rs->fetchArray(SQLITE3_ASSOC)) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($row['EventID']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['EventName']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['Level']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['Category_name']) . '</td>';
                    echo "<td class='table-actions'>
                        <a href='admin_event_edit.php?id=" . urlencode($row['EventID']) . "'>编辑</a>
                        <a class='delete' href='admin_event.php?delete=" . urlencode($row['EventID']) . "' onclick=\"return confirm('确认删除该赛事？')\">删除</a>
                    </td>";
                    echo '</tr>';
                }
                ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
</body>
</html>
