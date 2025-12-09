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
        flash_error('删除失败：缺少大项编号。');
    } else {
        $stmt = $db->prepare('DELETE FROM Category WHERE Category_id = :id');
        $stmt->bindValue(':id', $id, SQLITE3_TEXT);
        $stmt->execute();
        if ($db->changes() > 0) {
            flash_success('大项已删除。');
        } else {
            flash_error('未找到对应大项。');
        }
    }
    header('Location: admin_category.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add'])) {
        $id = clean($_POST['Category_id'] ?? '');
        $name = clean($_POST['Category_name'] ?? '');
        $manager = clean($_POST['Manager'] ?? '');

        if ($id === '' || $name === '') {
            $errors[] = 'ID and Name are required.';
            flash_error('新增大项失败：编号与名称为必填项。');
        }

        if (empty($errors)) {
            $stmt = $db->prepare('INSERT INTO Category (Category_id, Category_name, Manager) VALUES (:id, :name, :manager)');
            $stmt->bindValue(':id', $id, SQLITE3_TEXT);
            $stmt->bindValue(':name', $name, SQLITE3_TEXT);
            $stmt->bindValue(':manager', $manager, SQLITE3_TEXT);

            try {
                $stmt->execute();
                flash_success('新增大项成功。');
                header('Location: admin_category.php');
                exit;
            } catch (Exception $e) {
                $errors[] = 'Category ID already exists.';
                flash_error('新增大项失败：编号已存在。');
            }
        }
    }

    if (isset($_POST['update'])) {
        $id = clean($_POST['Category_id'] ?? '');
        $name = clean($_POST['Category_name'] ?? '');
        $manager = clean($_POST['Manager'] ?? '');

        if ($id === '' || $name === '') {
            $errors[] = 'ID and Name are required.';
            flash_error('更新大项失败：编号与名称为必填项。');
        }

        if (empty($errors)) {
            $stmt = $db->prepare('UPDATE Category SET Category_name = :name, Manager = :manager WHERE Category_id = :id');
            $stmt->bindValue(':name', $name, SQLITE3_TEXT);
            $stmt->bindValue(':manager', $manager, SQLITE3_TEXT);
            $stmt->bindValue(':id', $id, SQLITE3_TEXT);
            $stmt->execute();
            flash_success('大项信息已更新。');
            header('Location: admin_category.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>大项管理</title>
    <link rel="stylesheet" href="assets/ui.css">
</head>
<body>
<div class="app-shell">
    <?php render_flash_toasts(); ?>
    <header class="page-header">
        <div>
            <p>Admin Console</p>
            <h1>大项管理</h1>
            <p>维护比赛大项与负责人信息</p>
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
        <h2 class="card-title">新增大项</h2>
        <form method="POST" class="form-grid">
            <input type="hidden" name="add" value="1">
            <div class="form-group">
                <label for="Category_id">大项编号</label>
                <input type="text" id="Category_id" name="Category_id" required>
            </div>
            <div class="form-group">
                <label for="Category_name">大项名称</label>
                <input type="text" id="Category_name" name="Category_name" required>
            </div>
            <div class="form-group">
                <label for="Manager">负责人</label>
                <input type="text" id="Manager" name="Manager">
            </div>
            <div class="form-actions">
                <button class="btn btn-primary" type="submit">保存大项</button>
            </div>
        </form>
    </section>


    <section class="app-card">
        <h2 class="card-title">已有大项</h2>
        <div class="table-wrapper">
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>名称</th>
                    <th>负责人</th>
                    <th>操作</th>
                </tr>
                </thead>
                <tbody>
                <?php
                $rs = $db->query('SELECT * FROM Category ORDER BY Category_name');

                while ($row = $rs->fetchArray(SQLITE3_ASSOC)) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($row['Category_id']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['Category_name']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['Manager']) . '</td>';
                    echo "<td class='table-actions'>
                        <a href='admin_category_edit.php?id=" . urlencode($row['Category_id']) . "'>编辑</a>
                        <a class='delete' href='admin_category.php?delete=" . urlencode($row['Category_id']) . "' onclick=\"return confirm('确认删除该大项？')\">删除</a>
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
