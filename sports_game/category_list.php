<?php
require_once __DIR__ . '/lang.php';

$db = new SQLite3(__DIR__ . '/data.db');

// 获取用户输入的查询条件
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';

// 构建查询语句
if (!empty($search_term)) {
    $stmt = $db->prepare("SELECT * FROM Category 
                         WHERE Category_id LIKE :term 
                         OR Category_name LIKE :term 
                         OR Manager LIKE :term");
    $like_term = '%' . $search_term . '%';
    $stmt->bindValue(':term', $like_term, SQLITE3_TEXT);
    $results = $stmt->execute();
    $search_performed = true;
} else {
    $results = $db->query("SELECT * FROM Category");
    $search_performed = false;
}
?>

<!DOCTYPE html>
<html lang="<?= app_lang_html_attr(); ?>">
<head>
    <meta charset="UTF-8">
    <title>大项分类查询</title>
    <link rel="stylesheet" href="assets/ui.css">
</head>
<body>
<div class="app-shell">
    <header class="page-header">
        <div>
            <p>Public Explorer</p>
            <h1>比赛大项查询</h1>
        </div>
        <div class="header-actions">
            <?= render_lang_toggle(); ?>
            <a class="btn btn-ghost" href="public.php?lang=<?= app_lang_get(); ?>">← 返回查询面板</a>
        </div>
    </header>

    <section class="app-card">
        <h2 class="card-title">搜索大项</h2>
        <p class="card-subtitle">按大项编号、名称或负责人查询，支持模糊匹配</p>
        <form method="get" class="form-grid">
            <div class="form-group">
                <label for="search">搜索条件</label>
                <input type="text" id="search" name="search" placeholder="如：球类 或 CAT01" value="<?= htmlspecialchars($search_term) ?>">
            </div>
            <div class="form-actions">
                <button class="btn btn-secondary" type="submit">开始搜索</button>
                <a class="btn btn-ghost" href="category_list.php">清空筛选</a>
            </div>
        </form>
    </section>

    <section class="app-card">
        <div class="card-title">大项列表</div>
        <?php if ($search_performed && $search_term !== ''): ?>
            <p class="card-subtitle">当前关键字：<?= htmlspecialchars($search_term) ?></p>
        <?php endif; ?>

        <div class="table-wrapper">
            <table>
                <thead>
                <tr>
                    <th>Category ID</th>
                    <th>Category Name</th>
                    <th>Manager</th>
                </tr>
                </thead>
                <tbody>
                <?php
                $row_count = 0;
                while ($row = $results->fetchArray(SQLITE3_ASSOC)) {
                    $row_count++;
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($row['Category_id']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['Category_name']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['Manager']) . '</td>';
                    echo '</tr>';
                }

                if ($row_count === 0 && $search_performed) {
                    echo "<tr><td colspan='3' style=\"text-align:center; color:#c0392b;\">未找到匹配的记录</td></tr>";
                }
                ?>
                </tbody>
            </table>
        </div>
        <p style="margin-top:16px; color: var(--text-secondary);">共 <?= $row_count ?> 条记录</p>
    </section>
</div>
</body>
</html>