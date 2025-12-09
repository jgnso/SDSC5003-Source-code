<?php
require_once __DIR__ . '/lang.php';

$db = new SQLite3(__DIR__ . '/data.db');

// 获取用户输入的查询条件
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
$medal_filter = isset($_GET['medal']) ? $_GET['medal'] : '';

// 构建基础查询语句
$sql = "
SELECT 
    Participation.AthleteID,
    Athlete.Name AS AthleteName,
    Event.EventName,
    Participation.Time,
    COALESCE(NULLIF(Participation.Medal, ''), 'None') AS Medal
FROM Participation
LEFT JOIN Athlete ON Participation.AthleteID = Athlete.Athlete_id
LEFT JOIN Event ON Participation.EventID = Event.EventID
";

// 添加WHERE条件
$conditions = [];
if (!empty($search_term)) {
    $conditions[] = "(Athlete.Name LIKE :search OR Event.EventName LIKE :search)";
}
if (!empty($medal_filter) && $medal_filter !== 'all') {
    if ($medal_filter === 'None') {
        $conditions[] = "COALESCE(NULLIF(Participation.Medal, ''), 'None') = 'None'";
    } else {
        $conditions[] = "COALESCE(NULLIF(Participation.Medal, ''), 'None') = :medal";
    }
}

// 组合WHERE条件
if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$sql .= " ORDER BY AthleteName";

// 准备和执行查询
if (!empty($search_term) || (!empty($medal_filter) && $medal_filter !== 'all')) {
    $stmt = $db->prepare($sql);
    
    if (!empty($search_term)) {
        $like_term = '%' . $search_term . '%';
        $stmt->bindValue(':search', $like_term, SQLITE3_TEXT);
    }
    
    if (!empty($medal_filter) && $medal_filter !== 'all' && $medal_filter !== 'None') {
        $stmt->bindValue(':medal', $medal_filter, SQLITE3_TEXT);
    }
    
    $results = $stmt->execute();
    $search_performed = true;
} else {
    $results = $db->query($sql);
    $search_performed = false;
}
?>

<!DOCTYPE html>
<html lang="<?= app_lang_html_attr(); ?>">
<head>
    <meta charset="UTF-8">
    <title>参赛记录与奖牌查询</title>
    <link rel="stylesheet" href="assets/ui.css">
</head>
<body>
<div class="app-shell">
    <header class="page-header">
        <div>
            <p>Public Explorer</p>
            <h1>参赛记录 / 奖牌查询</h1>
        </div>
        <div class="header-actions">
            <?= render_lang_toggle(); ?>
            <a class="btn btn-ghost" href="public.php?lang=<?= app_lang_get(); ?>">← 返回查询面板</a>
        </div>
    </header>

    <section class="app-card">
        <h2 class="card-title">筛选条件</h2>
        <p class="card-subtitle">支持运动员姓名、赛事名称以及奖牌类型的组合过滤</p>
        <form method="get" class="form-grid">
            <div class="form-group">
                <label for="search">关键字</label>
                <input type="text" id="search" name="search" placeholder="输入运动员姓名或赛事名称" value="<?= htmlspecialchars($search_term) ?>">
            </div>
            <div class="form-group">
                <label for="medal">奖牌筛选</label>
                <select id="medal" name="medal">
                    <option value="all" <?= $medal_filter === 'all' || $medal_filter === '' ? 'selected' : '' ?>>所有类型</option>
                    <option value="Gold" <?= $medal_filter === 'Gold' ? 'selected' : '' ?>>金牌</option>
                    <option value="Silver" <?= $medal_filter === 'Silver' ? 'selected' : '' ?>>银牌</option>
                    <option value="Bronze" <?= $medal_filter === 'Bronze' ? 'selected' : '' ?>>铜牌</option>
                    <option value="None" <?= $medal_filter === 'None' ? 'selected' : '' ?>>无奖牌</option>
                </select>
            </div>
            <div class="form-actions">
                <button class="btn btn-secondary" type="submit">应用筛选</button>
                <a class="btn btn-ghost" href="participation_list.php">查看全部</a>
            </div>
        </form>
    </section>

    <section class="app-card">
        <div class="card-title">参赛记录</div>
        <?php if ($search_performed): ?>
            <p class="card-subtitle">
                <?php
                $chips = [];
                if ($search_term !== '') {
                    $chips[] = '关键词：' . htmlspecialchars($search_term);
                }
                if ($medal_filter !== '' && $medal_filter !== 'all') {
                    $chips[] = '奖牌：' . htmlspecialchars($medal_filter === 'None' ? '无奖牌' : $medal_filter);
                }
                echo $chips ? implode('，', $chips) : '已应用筛选条件。';
                ?>
            </p>
        <?php endif; ?>

        <div class="table-wrapper">
            <table>
                <thead>
                <tr>
                    <th>Athlete Name</th>
                    <th>Event</th>
                    <th>Time</th>
                    <th>Medal</th>
                </tr>
                </thead>
                <tbody>
                <?php
                $row_count = 0;
                while ($row = $results->fetchArray(SQLITE3_ASSOC)) {
                    $row_count++;
                    $medal = $row['Medal'] ?? 'None';
                    $medalClass = 'status-badge badge-none';
                    $medalLabel = '无奖牌';

                    if ($medal === 'Gold') {
                        $medalClass = 'status-badge badge-gold';
                        $medalLabel = 'Gold';
                    } elseif ($medal === 'Silver') {
                        $medalClass = 'status-badge badge-silver';
                        $medalLabel = 'Silver';
                    } elseif ($medal === 'Bronze') {
                        $medalClass = 'status-badge badge-bronze';
                        $medalLabel = 'Bronze';
                    }

                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($row['AthleteName']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['EventName']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['Time']) . '</td>';
                    echo '<td><span class="' . $medalClass . '">' . htmlspecialchars($medalLabel) . '</span></td>';
                    echo '</tr>';
                }

                if ($row_count === 0 && $search_performed) {
                    echo "<tr><td colspan='4' style=\"text-align:center; color:#c0392b; padding:18px;\">未找到匹配的参与记录</td></tr>";
                }
                ?>
                </tbody>
            </table>
        </div>
        <p style="margin-top:16px; color: var(--text-secondary);">共 <?= $row_count ?> 条记录</p>

        <?php if (isset($_GET['debug'])): ?>
            <div style="margin-top:18px; font-size:0.85rem; background:rgba(0,0,0,0.04); padding:14px; border-radius:16px;">
                <strong>调试信息：</strong><br>
                搜索条件：<?= var_export($search_term, true) ?><br>
                奖牌筛选：<?= var_export($medal_filter, true) ?><br>
                SQL语句：<pre style="white-space:pre-wrap;"><?= htmlspecialchars($sql) ?></pre>
            </div>
        <?php endif; ?>
    </section>
</div>
</body>
</html>