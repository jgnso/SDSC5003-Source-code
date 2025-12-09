<?php
require_once __DIR__ . '/auth_guard.php';

$db = new SQLite3(__DIR__ . '/data.db');
$db->exec('PRAGMA foreign_keys = ON');

$errors = [];

function sanitize_text($value): string
{
    return trim((string) $value);
}

function validate_age($value): ?int
{
    if ($value === '' || !is_numeric($value)) {
        return null;
    }
    $age = (int) $value;
    return ($age >= 0 && $age <= 120) ? $age : null;
}

if (isset($_GET['delete'])) {
    $id = sanitize_text($_GET['delete'] ?? '');
    if ($id === '') {
        flash_error(__('删除失败：缺少运动员编号。', 'Delete failed: missing athlete ID.'));
    } else {
        $stmt = $db->prepare('DELETE FROM Athlete WHERE Athlete_id = :id');
        $stmt->bindValue(':id', $id, SQLITE3_TEXT);
        $stmt->execute();
        if ($db->changes() > 0) {
            flash_success(__('运动员已删除。', 'Athlete deleted.'));
        } else {
            flash_error(__('未找到对应的运动员。', 'Athlete not found.'));
        }
    }
    header('Location: admin_athlete.php?lang=' . app_lang_get());
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add'])) {
        $id = sanitize_text($_POST['Athlete_id'] ?? '');
        $name = sanitize_text($_POST['Name'] ?? '');
        $age = validate_age($_POST['Age'] ?? '');
        $gender = sanitize_text($_POST['Gender'] ?? '');
        $del = sanitize_text($_POST['DelegationID'] ?? '');

        if ($id === '' || $name === '' || $del === '' || $age === null) {
            $message = __('请完整填写必填项并提供有效年龄。', 'Please complete all fields with valid values.');
            $errors[] = $message;
            flash_error(__('新增运动员失败：请填写所有必填项并确保年龄有效。', 'Failed to add athlete: ensure all required fields are valid.'));
        }

        if (!in_array($gender, ['Male', 'Female'], true)) {
            $message = __('性别必须为 Male 或 Female。', 'Gender must be Male or Female.');
            $errors[] = $message;
            flash_error(__('新增运动员失败：性别必须为 Male 或 Female。', 'Failed to add athlete: gender must be Male or Female.'));
        }

        if (empty($errors)) {
            $insert = $db->prepare('INSERT INTO Athlete (Athlete_id, Name, Age, Gender, DelegationID) VALUES (:id, :name, :age, :gender, :del)');
            $insert->bindValue(':id', $id, SQLITE3_TEXT);
            $insert->bindValue(':name', $name, SQLITE3_TEXT);
            $insert->bindValue(':age', $age, SQLITE3_INTEGER);
            $insert->bindValue(':gender', $gender, SQLITE3_TEXT);
            $insert->bindValue(':del', $del, SQLITE3_TEXT);

            try {
                $insert->execute();
                flash_success(__('新增运动员成功。', 'Athlete added.'));
                header('Location: admin_athlete.php?lang=' . app_lang_get());
                exit;
            } catch (Exception $e) {
                $message = __('运动员编号已存在，请使用其他编号。', 'Athlete ID already exists. Please use a different ID.');
                $errors[] = $message;
                flash_error(__('新增运动员失败：编号已存在。', 'Failed to add athlete: ID already exists.'));
            }
        }
    }

    if (isset($_POST['update'])) {
        $id = sanitize_text($_POST['Athlete_id'] ?? '');
        $name = sanitize_text($_POST['Name'] ?? '');
        $age = validate_age($_POST['Age'] ?? '');
        $gender = sanitize_text($_POST['Gender'] ?? '');
        $del = sanitize_text($_POST['DelegationID'] ?? '');

        if ($id === '' || $name === '' || $del === '' || $age === null) {
            $message = __('请完整填写必填项并提供有效年龄。', 'Please complete all fields with valid values.');
            $errors[] = $message;
            flash_error(__('更新运动员失败：请填写所有必填项并确保年龄有效。', 'Failed to update athlete: ensure required fields are valid.'));
        }

        if (!in_array($gender, ['Male', 'Female'], true)) {
            $message = __('性别必须为 Male 或 Female。', 'Gender must be Male or Female.');
            $errors[] = $message;
            flash_error(__('更新运动员失败：性别必须为 Male 或 Female。', 'Failed to update athlete: gender must be Male or Female.'));
        }

        if (empty($errors)) {
            $update = $db->prepare('UPDATE Athlete SET Name = :name, Age = :age, Gender = :gender, DelegationID = :del WHERE Athlete_id = :id');
            $update->bindValue(':name', $name, SQLITE3_TEXT);
            $update->bindValue(':age', $age, SQLITE3_INTEGER);
            $update->bindValue(':gender', $gender, SQLITE3_TEXT);
            $update->bindValue(':del', $del, SQLITE3_TEXT);
            $update->bindValue(':id', $id, SQLITE3_TEXT);
            $update->execute();
            flash_success(__('运动员信息已更新。', 'Athlete updated.'));
            header('Location: admin_athlete.php?lang=' . app_lang_get());
            exit;
        }
    }
}

$delegationOptions = [];
$delegations = $db->query('SELECT Delegation_id, Region FROM Delegation ORDER BY Region');
while ($d = $delegations->fetchArray(SQLITE3_ASSOC)) {
    $delegationOptions[] = $d;
}
?>

<!DOCTYPE html>
<html lang="<?= app_lang_html_attr(); ?>">
<head>
    <meta charset="UTF-8">
    <title><?= __('运动员管理', 'Athlete Management'); ?></title>
    <link rel="stylesheet" href="assets/ui.css">
</head>
<body>
<div class="app-shell">
    <?php render_flash_toasts(); ?>
    <header class="page-header">
        <div>
            <p><?= __('管理员后台', 'Admin Console'); ?></p>
            <h1><?= __('运动员管理', 'Athlete Management'); ?></h1>
            <p><?= __('维护运动员基本信息、所属代表团与性别', 'Maintain athlete profiles, delegations, and gender.'); ?></p>
        </div>
        <div class="header-actions">
            <?= render_lang_toggle(); ?>
            <a class="btn btn-ghost" href="index.php?lang=<?= app_lang_get(); ?>">🏠 <?= __('返回主页', 'Home'); ?></a>
            <a class="btn btn-ghost" href="admin_dashboard.php?lang=<?= app_lang_get(); ?>">← <?= __('返回仪表盘', 'Back to dashboard'); ?></a>
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
        <h2 class="card-title"><?= __('新增运动员', 'Add athlete'); ?></h2>
        <form method="POST" class="form-grid">
            <input type="hidden" name="add" value="1">
            <div class="form-group">
                <label for="Athlete_id"><?= __('编号', 'ID'); ?></label>
                <input type="text" id="Athlete_id" name="Athlete_id" value="<?= htmlspecialchars($_POST['Athlete_id'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label for="Name"><?= __('姓名', 'Name'); ?></label>
                <input type="text" id="Name" name="Name" value="<?= htmlspecialchars($_POST['Name'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label for="Age"><?= __('年龄', 'Age'); ?></label>
                <input type="number" id="Age" name="Age" min="0" max="120" value="<?= htmlspecialchars($_POST['Age'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label for="Gender"><?= __('性别', 'Gender'); ?></label>
                <select id="Gender" name="Gender">
                    <option value="Male" <?= (($_POST['Gender'] ?? '') === 'Female') ? '' : 'selected' ?>><?= __('男 Male', 'Male'); ?></option>
                    <option value="Female" <?= (($_POST['Gender'] ?? '') === 'Female') ? 'selected' : '' ?>><?= __('女 Female', 'Female'); ?></option>
                </select>
            </div>
            <div class="form-group">
                <label for="DelegationID"><?= __('所属代表团', 'Delegation'); ?></label>
                <select id="DelegationID" name="DelegationID" required>
                    <option value=""><?= __('请选择', 'Select'); ?></option>
                    <?php foreach ($delegationOptions as $del): ?>
                        <option value="<?= htmlspecialchars($del['Delegation_id']) ?>" <?= (($_POST['DelegationID'] ?? '') === $del['Delegation_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($del['Region']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-actions">
                <button class="btn btn-primary" type="submit"><?= __('保存运动员', 'Save athlete'); ?></button>
            </div>
        </form>
    </section>


    <section class="app-card">
        <h2 class="card-title"><?= __('已有运动员', 'Existing athletes'); ?></h2>
        <div class="table-wrapper">
            <table>
                <thead>
                <tr>
                    <th><?= __('编号', 'ID'); ?></th>
                    <th><?= __('姓名', 'Name'); ?></th>
                    <th><?= __('年龄', 'Age'); ?></th>
                    <th><?= __('性别', 'Gender'); ?></th>
                    <th><?= __('代表团', 'Delegation'); ?></th>
                    <th><?= __('操作', 'Actions'); ?></th>
                </tr>
                </thead>
                <tbody>
                <?php
                $results = $db->query('
    SELECT Athlete.*, Delegation.Region
    FROM Athlete
    LEFT JOIN Delegation ON Athlete.DelegationID = Delegation.Delegation_id
    ORDER BY Name
');

                while ($row = $results->fetchArray(SQLITE3_ASSOC)) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($row['Athlete_id']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['Name']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['Age']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['Gender']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['Region']) . '</td>';
                    $editUrl = 'admin_athlete_edit.php?id=' . urlencode($row['Athlete_id']) . '&lang=' . app_lang_get();
                    $deleteUrl = 'admin_athlete.php?delete=' . urlencode($row['Athlete_id']) . '&lang=' . app_lang_get();
                    $confirm = addslashes(__('确认删除该运动员？', 'Delete this athlete?'));
                    echo "<td class='table-actions'>
                        <a href='{$editUrl}'>" . __('编辑', 'Edit') . "</a>
                        <a class='delete' href='{$deleteUrl}' onclick=\"return confirm('{$confirm}')\">" . __('删除', 'Delete') . "</a>
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
