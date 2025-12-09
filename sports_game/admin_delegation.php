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
        flash_error('删除失败：缺少代表团编号。');
    } else {
        $stmt = $db->prepare('DELETE FROM Delegation WHERE Delegation_id = :id');
        $stmt->bindValue(':id', $id, SQLITE3_TEXT);
        $stmt->execute();
        if ($db->changes() > 0) {
            flash_success('代表团已删除。');
        } else {
            flash_error('未找到对应的代表团记录。');
        }
    }
    header('Location: admin_delegation.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add'])) {
        $id = clean($_POST['Delegation_id'] ?? '');
        $region = clean($_POST['Region'] ?? '');
        $address = clean($_POST['Address'] ?? '');

        if ($id === '' || $region === '') {
            $errors[] = 'ID and Region are required.';
            flash_error('新增代表团失败：编号与地区为必填项。');
        }

        if (empty($errors)) {
            $stmt = $db->prepare('INSERT INTO Delegation (Delegation_id, Region, Address) VALUES (:id, :region, :address)');
            $stmt->bindValue(':id', $id, SQLITE3_TEXT);
            $stmt->bindValue(':region', $region, SQLITE3_TEXT);
            $stmt->bindValue(':address', $address, SQLITE3_TEXT);

            try {
                $stmt->execute();
                flash_success('新增代表团成功。');
                header('Location: admin_delegation.php');
                exit;
            } catch (Exception $e) {
                $errors[] = 'Delegation ID already exists.';
                flash_error('新增代表团失败：编号已存在。');
            }
        }
    }

    if (isset($_POST['update'])) {
        $id = clean($_POST['Delegation_id'] ?? '');
        $region = clean($_POST['Region'] ?? '');
        $address = clean($_POST['Address'] ?? '');

        if ($id === '' || $region === '') {
            $errors[] = 'ID and Region are required.';
            flash_error('更新代表团失败：编号与地区为必填项。');
        }

        if (empty($errors)) {
            $stmt = $db->prepare('UPDATE Delegation SET Region = :region, Address = :address WHERE Delegation_id = :id');
            $stmt->bindValue(':region', $region, SQLITE3_TEXT);
            $stmt->bindValue(':address', $address, SQLITE3_TEXT);
            $stmt->bindValue(':id', $id, SQLITE3_TEXT);
            $stmt->execute();
            flash_success('代表团信息已更新。');
            header('Location: admin_delegation.php');
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>代表团管理</title>
    <link rel="stylesheet" href="assets/ui.css">
</head>
<body>
<div class="app-shell">
    <?php render_flash_toasts(); ?>
    <header class="page-header">
        <div>
            <p>Admin Console</p>
            <h1>代表团管理</h1>
            <p>维护各省代表团的编号、地区与驻地地址</p>
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
        <h2 class="card-title">新增代表团</h2>
        <form method="POST" class="form-grid">
            <input type="hidden" name="add" value="1">
            <div class="form-group">
                <label for="Delegation_id">编号</label>
                <input type="text" id="Delegation_id" name="Delegation_id" required>
            </div>
            <div class="form-group">
                <label for="Region">地区</label>
                <input type="text" id="Region" name="Region" required>
            </div>
            <div class="form-group">
                <label for="Address">驻地地址</label>
                <input type="text" id="Address" name="Address">
            </div>
            <div class="form-actions">
                <button class="btn btn-primary" type="submit">保存代表团</button>
            </div>
        </form>
    </section>

    <section class="app-card">
        <h2 class="card-title">已有代表团</h2>
        <div class="table-wrapper">
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Region</th>
                    <th>Address</th>
                    <th>操作</th>
                </tr>
                </thead>
                <tbody>
                <?php
                $results = $db->query('SELECT * FROM Delegation ORDER BY Region');

                while ($row = $results->fetchArray(SQLITE3_ASSOC)) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($row['Delegation_id']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['Region']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['Address']) . '</td>';
                    echo "<td class='table-actions'>
                        <a href='admin_delegation_edit.php?id=" . urlencode($row['Delegation_id']) . "'>编辑</a>
                        <a class='delete' href='admin_delegation.php?delete=" . urlencode($row['Delegation_id']) . "' onclick=\"return confirm('确认删除该代表团？')\">删除</a>
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
