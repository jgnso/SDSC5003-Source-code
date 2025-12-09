<?php
require_once __DIR__ . '/lang.php';

$db = new SQLite3(__DIR__ . '/data.db');

$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
if ($keyword === '' && isset($_GET['athlete_id'])) {
    $keyword = trim($_GET['athlete_id']);
}

if ($keyword !== '') {
    $stmt = $db->prepare('
        SELECT Athlete.Athlete_id,
               Athlete.Name,
               Athlete.Age,
               Athlete.Gender,
               Delegation.Region AS DelegationRegion
        FROM Athlete
        LEFT JOIN Delegation ON Athlete.DelegationID = Delegation.Delegation_id
        WHERE Athlete.Athlete_id LIKE :term OR Athlete.Name LIKE :term
        ORDER BY Athlete.Name
    ');
    $like = '%' . $keyword . '%';
    $stmt->bindValue(':term', $like, SQLITE3_TEXT);
    $results = $stmt->execute();
    $searchPerformed = true;
} else {
    $results = $db->query('
        SELECT Athlete.Athlete_id,
               Athlete.Name,
               Athlete.Age,
               Athlete.Gender,
               Delegation.Region AS DelegationRegion
        FROM Athlete
        LEFT JOIN Delegation ON Athlete.DelegationID = Delegation.Delegation_id
        ORDER BY Athlete.Name
    ');
    $searchPerformed = false;
}

$rows = [];
while ($results && ($row = $results->fetchArray(SQLITE3_ASSOC))) {
    $rows[] = $row;
}
?>

<!DOCTYPE html>
<html lang="<?= app_lang_html_attr(); ?>">
<head>
    <meta charset="UTF-8">
    <title>运动员查询</title>
    <link rel="stylesheet" href="assets/ui.css">
</head>
<body>
<div class="app-shell">
    <header class="page-header">
        <div>
            <p>Public Explorer</p>
            <h1>运动员查询</h1>
        </div>
        <div class="header-actions">
            <?= render_lang_toggle(); ?>
            <a class="btn btn-ghost" href="public.php?lang=<?= app_lang_get(); ?>">← 返回查询面板</a>
        </div>
    </header>

    <section class="app-card">
        <h2 class="card-title">快速检索</h2>
        <p class="card-subtitle">支持按照运动员编号或姓名模糊匹配</p>
        <form method="get" class="form-grid">
            <div class="form-group">
                <label for="keyword">查询条件</label>
                <input type="text" id="keyword" name="keyword" placeholder="输入ID或姓名，例如 A001" value="<?= htmlspecialchars($keyword) ?>">
            </div>
            <div class="form-actions">
                <button class="btn btn-secondary" type="submit">开始查询</button>
                <a class="btn btn-ghost" href="athlete_list.php">清空筛选</a>
            </div>
        </form>
    </section>

    <section class="app-card">
        <div class="card-title">运动员列表</div>
        <?php if ($searchPerformed && $keyword !== ''): ?>
            <p class="card-subtitle">当前条件：<?= htmlspecialchars($keyword) ?></p>
        <?php endif; ?>

        <div class="table-wrapper">
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>姓名</th>
                    <th>年龄</th>
                    <th>性别</th>
                    <th>代表团地区</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="5" style="text-align:center; color:#c0392b;">未找到匹配的运动员记录</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['Athlete_id']) ?></td>
                            <td><?= htmlspecialchars($row['Name']) ?></td>
                            <td><?= htmlspecialchars($row['Age']) ?></td>
                            <td><?= htmlspecialchars($row['Gender']) ?></td>
                            <td><?= htmlspecialchars($row['DelegationRegion'] ?? '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <p style="margin-top:16px; color: var(--text-secondary);">共 <?= count($rows) ?> 条记录</p>
    </section>
</div>
</body>
</html>