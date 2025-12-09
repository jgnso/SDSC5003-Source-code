<?php
require_once __DIR__ . '/lang.php';

$db = new SQLite3(__DIR__ . '/data.db');

// 获取用户输入的查询条件
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';

// 构建查询语句
if (!empty($search_term)) {
    $stmt = $db->prepare("SELECT * FROM Delegation 
                         WHERE Delegation_id LIKE :term 
                         OR Region LIKE :term 
                         OR Address LIKE :term");
    $like_term = '%' . $search_term . '%';
    $stmt->bindValue(':term', $like_term, SQLITE3_TEXT);
    $results = $stmt->execute();
    $search_performed = true;
} else {
    $results = $db->query("SELECT * FROM Delegation");
    $search_performed = false;
}
?>

<!DOCTYPE html>
<html lang="<?= app_lang_html_attr(); ?>">
<head>
    <meta charset="UTF-8">
    <title>代表团查询</title>
    <link rel="stylesheet" href="assets/ui.css">
</head>
<body>
<div class="app-shell">
    <header class="page-header">
        <div>
            <p>Public Explorer</p>
            <h1>代表团查询</h1>
        </div>
        <div class="header-actions">
            <?= render_lang_toggle(); ?>
            <a class="btn btn-ghost" href="public.php?lang=<?= app_lang_get(); ?>">← 返回查询面板</a>
        </div>
    </header>

    <section class="app-card">
        <h2 class="card-title">搜索代表团</h2>
        <p class="card-subtitle">可按代表团编号、地区或驻地地址进行模糊查询</p>
        <form method="get" class="form-grid">
            <div class="form-group">
                <label for="search">搜索条件</label>
                <input type="text" id="search" name="search" placeholder="例如：粤 或 GD001" value="<?= htmlspecialchars($search_term) ?>">
            </div>
            <div class="form-actions">
                <button class="btn btn-secondary" type="submit">开始搜索</button>
                <a class="btn btn-ghost" href="delegation_list.php">清空筛选</a>
            </div>
        </form>
    </section>

    <section class="app-card">
        <div class="card-title">代表团列表</div>
        <?php if ($search_performed && $search_term !== ''): ?>
            <p class="card-subtitle">当前关键字：<?= htmlspecialchars($search_term) ?></p>
        <?php endif; ?>

        <div class="table-wrapper">
            <table>
                <thead>
                <tr>
                    <th>Delegation ID</th>
                    <th>Region</th>
                    <th>Address</th>
                </tr>
                </thead>
                <tbody>
                <?php
                $row_count = 0;
                while ($row = $results->fetchArray(SQLITE3_ASSOC)) {
                    $row_count++;
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($row['Delegation_id']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['Region']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['Address']) . '</td>';
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