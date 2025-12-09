<?php
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/db_schema.php';

$db = new SQLite3(__DIR__ . '/data.db');
$db->exec('PRAGMA foreign_keys = ON');
ensure_participation_constraints($db);

function clean($value): string
{
    return trim((string) $value);
}

function normalize_medal(?string $value): string
{
    $value = trim((string) $value);
    if ($value === '') {
        return 'None';
    }
    $normalized = ucfirst(strtolower($value));
    $allowed = ['Gold', 'Silver', 'Bronze', 'None'];
    return in_array($normalized, $allowed, true) ? $normalized : 'None';
}

$key = $_GET['key'] ?? '';
$parts = explode('|', $key);
if (count($parts) !== 3) {
    flash_error('编辑参数不完整。');
    header('Location: admin_participation.php');
    exit;
}

$athleteId = clean($parts[0]);
$eventId = clean($parts[1]);
$medal = normalize_medal($parts[2]);

$stmt = $db->prepare('
    SELECT Participation.*, Athlete.Name AS AthleteName, Delegation.Region, Event.EventName, Category.Category_name
    FROM Participation
    LEFT JOIN Athlete ON Participation.AthleteID = Athlete.Athlete_id
    LEFT JOIN Delegation ON Athlete.DelegationID = Delegation.Delegation_id
    LEFT JOIN Event ON Participation.EventID = Event.EventID
    LEFT JOIN Category ON Event.CategoryID = Category.Category_id
    WHERE Participation.AthleteID = :athlete AND Participation.EventID = :event AND COALESCE(NULLIF(Participation.Medal, \'\'), \'None\') = :medal
');
$stmt->bindValue(':athlete', $athleteId, SQLITE3_TEXT);
$stmt->bindValue(':event', $eventId, SQLITE3_TEXT);
$stmt->bindValue(':medal', $medal, SQLITE3_TEXT);
$record = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

if (!$record) {
    flash_error('未找到需要编辑的参赛记录。');
    header('Location: admin_participation.php');
    exit;
}

$athleteOptions = [];
$athletes = $db->query('
    SELECT Athlete.Athlete_id, Athlete.Name, Delegation.Region
    FROM Athlete
    LEFT JOIN Delegation ON Athlete.DelegationID = Delegation.Delegation_id
    ORDER BY Athlete.Name
');
while ($row = $athletes->fetchArray(SQLITE3_ASSOC)) {
    $athleteOptions[] = $row;
}

$eventOptions = [];
$events = $db->query('
    SELECT Event.EventID, Event.EventName, Category.Category_name
    FROM Event
    LEFT JOIN Category ON Event.CategoryID = Category.Category_id
    ORDER BY Event.EventName
');
while ($row = $events->fetchArray(SQLITE3_ASSOC)) {
    $eventOptions[] = $row;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>编辑参赛记录</title>
    <link rel="stylesheet" href="assets/ui.css">
</head>
<body>
<div class="app-shell">
    <?php render_flash_toasts(); ?>
    <header class="page-header">
        <div>
            <p>Admin Console</p>
            <h1>编辑参赛记录</h1>
            <p><?= htmlspecialchars($record['AthleteName'] ?? $athleteId) ?> · <?= htmlspecialchars($record['EventName'] ?? $eventId) ?></p>
        </div>
        <div class="header-actions">
            <a class="btn btn-ghost" href="index.php?lang=<?= app_lang_get(); ?>">🏠 返回主页</a>
            <a class="btn btn-ghost" href="admin_participation.php">← 返回参赛记录列表</a>
        </div>
    </header>

    <section class="app-card">
        <form method="post" action="admin_participation.php" class="form-grid">
            <input type="hidden" name="update" value="1">
            <input type="hidden" name="original_athlete_id" value="<?= htmlspecialchars($athleteId); ?>">
            <input type="hidden" name="original_event_id" value="<?= htmlspecialchars($eventId); ?>">
            <input type="hidden" name="original_medal" value="<?= htmlspecialchars($medal); ?>">

            <div class="form-group">
                <label for="AthleteID">运动员</label>
                <select id="AthleteID" name="AthleteID" required>
                    <?php foreach ($athleteOptions as $athlete): ?>
                        <option value="<?= htmlspecialchars($athlete['Athlete_id']); ?>" <?= $athlete['Athlete_id'] === $record['AthleteID'] ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($athlete['Name']); ?><?= $athlete['Region'] ? ' · ' . htmlspecialchars($athlete['Region']) : ''; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="EventID">赛事</label>
                <select id="EventID" name="EventID" required>
                    <?php foreach ($eventOptions as $event): ?>
                        <option value="<?= htmlspecialchars($event['EventID']); ?>" <?= $event['EventID'] === $record['EventID'] ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($event['EventName']); ?><?= $event['Category_name'] ? ' · ' . htmlspecialchars($event['Category_name']) : ''; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="Time">成绩 / 用时</label>
                <input type="text" id="Time" name="Time" value="<?= htmlspecialchars($record['Time']); ?>" required>
            </div>

            <div class="form-group">
                <label for="Medal">奖牌</label>
                <select id="Medal" name="Medal">
                    <option value="None" <?= $medal === 'None' ? 'selected' : ''; ?>>无奖牌</option>
                    <option value="Gold" <?= $medal === 'Gold' ? 'selected' : ''; ?>>Gold</option>
                    <option value="Silver" <?= $medal === 'Silver' ? 'selected' : ''; ?>>Silver</option>
                    <option value="Bronze" <?= $medal === 'Bronze' ? 'selected' : ''; ?>>Bronze</option>
                </select>
            </div>

            <div class="form-actions">
                <button class="btn btn-primary" type="submit">保存修改</button>
                <a class="btn btn-ghost" href="admin_participation.php">取消</a>
            </div>
        </form>
    </section>
</div>
</body>
</html>
