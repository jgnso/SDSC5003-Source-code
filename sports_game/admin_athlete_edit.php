<?php
require_once __DIR__ . '/auth_guard.php';

$db = new SQLite3(__DIR__ . '/data.db');
$db->exec('PRAGMA foreign_keys = ON');

function sanitize_text($value): string
{
    return trim((string) $value);
}

$athleteId = sanitize_text($_GET['id'] ?? '');
if ($athleteId === '') {
    flash_error(__('缺少运动员编号。', 'Missing athlete ID.'));
    header('Location: admin_athlete.php?lang=' . app_lang_get());
    exit;
}

$stmt = $db->prepare('SELECT * FROM Athlete WHERE Athlete_id = :id');
$stmt->bindValue(':id', $athleteId, SQLITE3_TEXT);
$record = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

if (!$record) {
    flash_error(__('未找到对应的运动员。', 'Athlete not found.'));
    header('Location: admin_athlete.php?lang=' . app_lang_get());
    exit;
}

$delegationOptions = [];
$delegations = $db->query('SELECT Delegation_id, Region FROM Delegation ORDER BY Region');
while ($row = $delegations->fetchArray(SQLITE3_ASSOC)) {
    $delegationOptions[] = $row;
}
?>
<!DOCTYPE html>
<html lang="<?= app_lang_html_attr(); ?>">
<head>
    <meta charset="UTF-8">
    <title><?= __('编辑运动员', 'Edit athlete'); ?></title>
    <link rel="stylesheet" href="assets/ui.css">
</head>
<body>
<div class="app-shell">
    <?php render_flash_toasts(); ?>
    <header class="page-header">
        <div>
            <p><?= __('管理员后台', 'Admin Console'); ?></p>
            <h1><?= __('编辑运动员', 'Edit athlete'); ?></h1>
            <p><?= htmlspecialchars($record['Athlete_id']) ?> · <?= htmlspecialchars($record['Name']) ?></p>
        </div>
        <div class="header-actions">
            <?= render_lang_toggle(); ?>
            <a class="btn btn-ghost" href="index.php?lang=<?= app_lang_get(); ?>">🏠 <?= __('返回主页', 'Home'); ?></a>
            <a class="btn btn-ghost" href="admin_athlete.php?lang=<?= app_lang_get(); ?>">← <?= __('返回运动员列表', 'Back to athlete list'); ?></a>
        </div>
    </header>

    <section class="app-card">
        <form method="POST" action="admin_athlete.php?lang=<?= app_lang_get(); ?>" class="form-grid">
            <input type="hidden" name="update" value="1">
            <input type="hidden" name="Athlete_id" value="<?= htmlspecialchars($record['Athlete_id']); ?>">

            <div class="form-group">
                <label><?= __('编号', 'ID'); ?></label>
                <input type="text" value="<?= htmlspecialchars($record['Athlete_id']); ?>" disabled>
            </div>

            <div class="form-group">
                <label for="Name">&nbsp;<?= __('姓名', 'Name'); ?></label>
                <input type="text" id="Name" name="Name" value="<?= htmlspecialchars($record['Name']); ?>" required>
            </div>

            <div class="form-group">
                <label for="Age"><?= __('年龄', 'Age'); ?></label>
                <input type="number" id="Age" name="Age" min="0" max="120" value="<?= htmlspecialchars($record['Age']); ?>" required>
            </div>

            <div class="form-group">
                <label for="Gender"><?= __('性别', 'Gender'); ?></label>
                <select id="Gender" name="Gender">
                    <option value="Male" <?= $record['Gender'] === 'Male' ? 'selected' : ''; ?>><?= __('男 Male', 'Male'); ?></option>
                    <option value="Female" <?= $record['Gender'] === 'Female' ? 'selected' : ''; ?>><?= __('女 Female', 'Female'); ?></option>
                </select>
            </div>

            <div class="form-group">
                <label for="DelegationID"><?= __('所属代表团', 'Delegation'); ?></label>
                <select id="DelegationID" name="DelegationID" required>
                    <?php foreach ($delegationOptions as $del): ?>
                        <option value="<?= htmlspecialchars($del['Delegation_id']); ?>" <?= $record['DelegationID'] === $del['Delegation_id'] ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($del['Region']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-actions">
                <button class="btn btn-primary" type="submit"><?= __('保存修改', 'Save changes'); ?></button>
                <a class="btn btn-ghost" href="admin_athlete.php?lang=<?= app_lang_get(); ?>"><?= __('取消', 'Cancel'); ?></a>
            </div>
        </form>
    </section>
</div>
</body>
</html>
