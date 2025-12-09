<?php
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/db_schema.php';

$db = new SQLite3(__DIR__ . '/data.db');
$db->exec('PRAGMA foreign_keys = ON');
ensure_participation_constraints($db);

$errors = [];

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

function fetchAthletes(SQLite3 $db): array
{
    $rows = [];
    $result = $db->query('
        SELECT Athlete.Athlete_id, Athlete.Name, Delegation.Region
        FROM Athlete
        LEFT JOIN Delegation ON Athlete.DelegationID = Delegation.Delegation_id
        ORDER BY Athlete.Name
    ');

    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $rows[] = $row;
    }

    return $rows;
}

function fetchEvents(SQLite3 $db): array
{
    $rows = [];
    $result = $db->query('
        SELECT Event.EventID, Event.EventName, Category.Category_name
        FROM Event
        LEFT JOIN Category ON Event.CategoryID = Category.Category_id
        ORDER BY Event.EventName
    ');

    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $rows[] = $row;
    }

    return $rows;
}

function delete_participation_record(SQLite3 $db, string $athleteId, string $eventId, ?string $medal): bool
{
    if ($athleteId === '' || $eventId === '') {
        return false;
    }

    $stmt = $db->prepare("DELETE FROM Participation WHERE AthleteID = :athlete AND EventID = :event AND COALESCE(Medal, 'None') = :medal");
    $stmt->bindValue(':athlete', $athleteId, SQLITE3_TEXT);
    $stmt->bindValue(':event', $eventId, SQLITE3_TEXT);
    $stmt->bindValue(':medal', normalize_medal($medal), SQLITE3_TEXT);
    $stmt->execute();
    return $db->changes() > 0;
}

if (isset($_GET['delete'])) {
    $parts = explode('|', $_GET['delete']);
    if (count($parts) === 3) {
        $athleteId = clean($parts[0]);
        $eventId = clean($parts[1]);
        $medal = normalize_medal($parts[2]);
        $deleted = delete_participation_record($db, $athleteId, $eventId, $medal);
        if ($deleted) {
            flash_success('参赛记录已删除。');
        } else {
            flash_error('删除失败：未找到对应的参赛记录。');
        }
    } else {
        flash_error('删除失败：参数不完整。');
    }

    header('Location: admin_participation.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add'])) {
        $athleteId = clean($_POST['AthleteID'] ?? '');
        $eventId = clean($_POST['EventID'] ?? '');
        $time = clean($_POST['Time'] ?? '');
        $medal = normalize_medal($_POST['Medal'] ?? 'None');

        if ($athleteId === '' || $eventId === '') {
            $errors[] = '请选择运动员和赛事。';
        }

        if ($time === '') {
            $errors[] = '成绩 / 用时不能为空（尤其是决赛记录必须填写成绩）。';
        }

        if (empty($errors)) {
            $stmt = $db->prepare('INSERT INTO Participation (AthleteID, EventID, Time, Medal) VALUES (:athlete, :event, :time, :medal)');
            $stmt->bindValue(':athlete', $athleteId, SQLITE3_TEXT);
            $stmt->bindValue(':event', $eventId, SQLITE3_TEXT);
            $stmt->bindValue(':time', $time, SQLITE3_TEXT);
            $stmt->bindValue(':medal', $medal, SQLITE3_TEXT);

            try {
                $stmt->execute();
                flash_success('参赛记录已新增。');
                header('Location: admin_participation.php');
                exit;
            } catch (Exception $e) {
                $message = $e->getMessage();
                if (str_contains($message, 'UNIQUE')) {
                    $errors[] = '同一运动员在同一赛事上的奖牌记录已存在，请直接编辑该记录。';
                    flash_error('新增失败：记录重复。');
                } else {
                    $errors[] = '新增参赛记录失败：' . $message;
                    flash_error('新增参赛记录失败：' . $message);
                }
            }
        } else {
            flash_error('新增参赛记录失败：请根据提示修正表单。');
        }
    }

    if (isset($_POST['update'])) {
        $originalAthlete = clean($_POST['original_athlete_id'] ?? '');
        $originalEvent = clean($_POST['original_event_id'] ?? '');
        $originalMedal = normalize_medal($_POST['original_medal'] ?? 'None');

        $newAthleteId = clean($_POST['AthleteID'] ?? '');
        $newEventId = clean($_POST['EventID'] ?? '');
        $time = clean($_POST['Time'] ?? '');
        $medal = normalize_medal($_POST['Medal'] ?? 'None');

        if ($originalAthlete === '' || $originalEvent === '') {
            $errors[] = '原始记录参数缺失，无法更新。';
        }

        if ($newAthleteId === '' || $newEventId === '') {
            $errors[] = '请选择新的运动员和赛事。';
        }

        if ($time === '') {
            $errors[] = '成绩 / 用时不能为空。';
        }

        if (empty($errors)) {
            $db->exec('BEGIN');
            try {
                $deleted = delete_participation_record($db, $originalAthlete, $originalEvent, $originalMedal);
                if (!$deleted) {
                    throw new RuntimeException('未找到原始记录，无法执行更新。');
                }

                $stmt = $db->prepare('INSERT INTO Participation (AthleteID, EventID, Time, Medal) VALUES (:athlete, :event, :time, :medal)');
                $stmt->bindValue(':athlete', $newAthleteId, SQLITE3_TEXT);
                $stmt->bindValue(':event', $newEventId, SQLITE3_TEXT);
                $stmt->bindValue(':time', $time, SQLITE3_TEXT);
                $stmt->bindValue(':medal', $medal, SQLITE3_TEXT);

                $stmt->execute();
                $db->exec('COMMIT');
                flash_success('参赛记录已更新。');
                header('Location: admin_participation.php');
                exit;
            } catch (Exception $e) {
                $db->exec('ROLLBACK');
                $errors[] = '更新参赛记录失败：' . $e->getMessage();
                flash_error('更新参赛记录失败：' . $e->getMessage());
            }
        } else {
            flash_error('更新参赛记录失败：请根据提示修正表单。');
        }
    }
}

        $athleteOptions = fetchAthletes($db);
        $eventOptions = fetchEvents($db);

        $records = [];
        $recordQuery = $db->query('
            SELECT Participation.AthleteID,
                   Participation.EventID,
                   Participation.Time,
                   COALESCE(NULLIF(Participation.Medal, \'\'), \'None\') AS Medal,
                   Athlete.Name AS AthleteName,
                   Event.EventName,
                   Event.Level,
                   Delegation.Region AS DelegationRegion
            FROM Participation
            LEFT JOIN Athlete ON Participation.AthleteID = Athlete.Athlete_id
            LEFT JOIN Delegation ON Athlete.DelegationID = Delegation.Delegation_id
            LEFT JOIN Event ON Participation.EventID = Event.EventID
            ORDER BY Athlete.Name, Event.EventName
        ');

        while ($row = $recordQuery->fetchArray(SQLITE3_ASSOC)) {
            $records[] = $row;
        }

    $selectedAthlete = isset($_POST['AthleteID']) ? clean($_POST['AthleteID']) : '';
    $selectedEvent = isset($_POST['EventID']) ? clean($_POST['EventID']) : '';
    $inputTime = isset($_POST['Time']) ? clean($_POST['Time']) : '';
    $inputMedal = isset($_POST['Medal']) ? normalize_medal($_POST['Medal']) : 'None';
        ?>

        <!DOCTYPE html>
        <html lang="zh-CN">
        <head>
            <meta charset="UTF-8">
            <title>参赛记录管理</title>
            <link rel="stylesheet" href="assets/ui.css">
        </head>
        <body>
        <div class="app-shell">
            <?php render_flash_toasts(); ?>
            <header class="page-header">
                <div>
                    <p>Admin Console</p>
                    <h1>参赛记录管理</h1>
                    <p>录入或更新运动员在各个项目中的成绩与奖牌情况</p>
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
                <h2 class="card-title">新增参赛记录</h2>
                <p class="card-subtitle">选择运动员与赛事，补充成绩与奖牌信息后提交（成绩/用时为必填字段）。</p>
                <form method="post" class="form-grid">
                    <input type="hidden" name="add" value="1">

                    <div class="form-group">
                        <label for="AthleteID">运动员</label>
                        <select id="AthleteID" name="AthleteID" required data-confirm-change="true" data-confirm-message="切换运动员将重置当前选择，确认继续？">
                            <option value="">请选择运动员</option>
                            <?php foreach ($athleteOptions as $athlete): ?>
                                <option value="<?= htmlspecialchars($athlete['Athlete_id']) ?>" <?= $selectedAthlete === $athlete['Athlete_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($athlete['Name']) ?><?= $athlete['Region'] ? ' · ' . htmlspecialchars($athlete['Region']) : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="EventID">赛事</label>
                        <select id="EventID" name="EventID" required data-confirm-change="true" data-confirm-message="切换赛事将影响对应成绩与奖牌，确认切换？">
                            <option value="">请选择赛事</option>
                            <?php foreach ($eventOptions as $event): ?>
                                <option value="<?= htmlspecialchars($event['EventID']) ?>" <?= $selectedEvent === $event['EventID'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($event['EventName']) ?><?= $event['Category_name'] ? ' · ' . htmlspecialchars($event['Category_name']) : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="Time">成绩 / 用时</label>
                        <input type="text" id="Time" name="Time" placeholder="例如：9.58s" value="<?= htmlspecialchars($inputTime) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="Medal">奖牌</label>
                        <select id="Medal" name="Medal">
                            <option value="None" <?= $inputMedal === '' || $inputMedal === 'None' ? 'selected' : '' ?>>无奖牌</option>
                            <option value="Gold" <?= $inputMedal === 'Gold' ? 'selected' : '' ?>>Gold</option>
                            <option value="Silver" <?= $inputMedal === 'Silver' ? 'selected' : '' ?>>Silver</option>
                            <option value="Bronze" <?= $inputMedal === 'Bronze' ? 'selected' : '' ?>>Bronze</option>
                        </select>
                    </div>

                    <div class="form-actions">
                        <button class="btn btn-primary" type="submit">保存参赛记录</button>
                        <a class="btn btn-ghost" href="admin_participation.php">重置</a>
                    </div>
                </form>
            </section>


            <section class="app-card">
                <h2 class="card-title">参赛记录列表</h2>
                <p class="card-subtitle">当前共有 <?= count($records) ?> 条记录，删除与切换操作都会弹出确认提示。</p>
                <div class="table-wrapper">
                    <table>
                        <thead>
                        <tr>
                            <th>运动员 / 代表团</th>
                            <th>赛事</th>
                            <th>成绩 / 用时</th>
                            <th>奖牌</th>
                            <th>操作</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($records)): ?>
                            <tr>
                                <td colspan="5" style="text-align:center; color:#c0392b;">暂无参赛记录</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($records as $row): ?>
                                <?php
                                $medal = $row['Medal'] ?? 'None';
                                $badgeClass = 'badge-none';
                                if ($medal === 'Gold') {
                                    $badgeClass = 'badge-gold';
                                } elseif ($medal === 'Silver') {
                                    $badgeClass = 'badge-silver';
                                } elseif ($medal === 'Bronze') {
                                    $badgeClass = 'badge-bronze';
                                }
                                $medalLabel = $medal === 'None' ? '暂无' : $medal;
                                $deleteParam = $row['AthleteID'] . '|' . $row['EventID'] . '|' . $medal;
                                ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($row['AthleteName'] ?? '未关联') ?></strong><br>
                                        <span style="color: var(--text-secondary); font-size:0.85rem;">代表团：<?= htmlspecialchars($row['DelegationRegion'] ?? '—') ?></span>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($row['EventName'] ?? '—') ?><br>
                                        <span style="color: var(--text-secondary); font-size:0.8rem;">级别：<?= htmlspecialchars($row['Level'] ?? '—') ?></span>
                                    </td>
                                    <td><?= htmlspecialchars($row['Time'] ?? '—') ?></td>
                                    <td>
                                        <span class="status-badge <?= $badgeClass ?>"><?= htmlspecialchars($medalLabel) ?></span>
                                    </td>
                                    <td class="table-actions">
                                        <?php $editParam = $row['AthleteID'] . '|' . $row['EventID'] . '|' . $medal; ?>
                                        <a href="admin_participation_edit.php?key=<?= urlencode($editParam) ?>">编辑</a>
                                        <a class="delete" href="admin_participation.php?delete=<?= urlencode($deleteParam) ?>" onclick="return confirm('确认删除 <?= htmlspecialchars($row['AthleteName'] ?? '该运动员') ?> 在 <?= htmlspecialchars($row['EventName'] ?? '该赛事') ?> 的参赛记录？');">删除</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const confirmFields = document.querySelectorAll('[data-confirm-change="true"]');
                confirmFields.forEach(function (field) {
                    field.dataset.previous = field.value;
                    field.addEventListener('change', function (event) {
                        const prev = field.dataset.previous || '';
                        if (!prev) {
                            field.dataset.previous = field.value;
                            return;
                        }
                        const message = field.dataset.confirmMessage || '确认继续执行该操作？';
                        if (!window.confirm(message)) {
                            field.value = prev;
                            event.preventDefault();
                            return;
                        }
                        field.dataset.previous = field.value;
                    });
                });
            });
        </script>
        </body>
        </html>