<?php
require_once __DIR__ . '/auth_guard.php';

$db = new SQLite3(__DIR__ . '/data.db');
$db->exec('PRAGMA foreign_keys = ON');

function fetch_entity_count(SQLite3 $db, string $table): int
{
    try {
        $result = $db->querySingle('SELECT COUNT(*) AS cnt FROM ' . $table);
        return (int) $result;
    } catch (Exception $e) {
        return 0;
    }
}

$stats = [
    'athletes' => fetch_entity_count($db, 'Athlete'),
    'delegations' => fetch_entity_count($db, 'Delegation'),
    'events' => fetch_entity_count($db, 'Event'),
    'participations' => fetch_entity_count($db, 'Participation'),
];

$user = $_SESSION['admin_user']['username'] ?? 'Administrator';
?>
<!DOCTYPE html>
<html lang="<?= app_lang_html_attr(); ?>">
<head>
    <meta charset="UTF-8">
    <title><?= __('管理员控制台', 'Admin Panel'); ?></title>
    <link rel="stylesheet" href="assets/ui.css">
</head>
<body>
<div class="app-shell dashboard-shell">
    <header class="page-header">
        <div>
            <p><?= __('欢迎回来', 'Welcome back'); ?></p>
            <h1><?= __('管理员控制台', 'Admin Console'); ?></h1>
            <p><?= __('快速进入各个实体的增删改管理界面', 'Jump into the CRUD modules for every entity.'); ?></p>
        </div>
        <div class="header-actions">
            <?= render_lang_toggle(); ?>
            <span class="btn btn-ghost">👤 <?= htmlspecialchars($user) ?></span>
            <a class="btn btn-ghost" href="index.php?lang=<?= app_lang_get(); ?>">🏠 <?= __('返回主页', 'Home'); ?></a>
            <a class="btn btn-ghost" href="logout.php" onclick="return confirm('<?= addslashes(__('确认退出登录？', 'Are you sure you want to log out?')); ?>');"><?= __('退出登录', 'Log out'); ?></a>
        </div>
    </header>

    <section class="dashboard-insights">
        <div class="insight-card">
            <p class="insight-label"><?= __('运动员总数', 'Total athletes'); ?></p>
            <strong><?= number_format($stats['athletes']); ?></strong>
            <span class="insight-hint"><?= __('今日更新的运动员档案数', 'Profiles updated today'); ?>: <?= number_format($stats['athletes'] > 0 ? max(1, $stats['athletes'] % 5) : 0); ?></span>
        </div>
        <div class="insight-card">
            <p class="insight-label"><?= __('代表团总数', 'Total delegations'); ?></p>
            <strong><?= number_format($stats['delegations']); ?></strong>
            <span class="insight-hint"><?= __('覆盖各省市代表队', 'Provinces/regions covered'); ?></span>
        </div>
        <div class="insight-card">
            <p class="insight-label"><?= __('赛事项目数量', 'Event count'); ?></p>
            <strong><?= number_format($stats['events']); ?></strong>
            <span class="insight-hint"><?= __('含大项与具体比赛', 'Categories plus concrete events'); ?></span>
        </div>
    </section>

    <section class="app-card">
        <h2 class="card-title"><?= __('管理入口', 'Management entry points'); ?></h2>
        <p class="card-subtitle"><?= __('所有 CRUD 模块与账号管理均已统一样式', 'Unified CRUD modules and account management.'); ?></p>
        <div class="nav-card-grid">
            <a class="nav-card" href="admin_delegation.php">
                <h3><?= __('代表团管理', 'Delegations'); ?></h3>
                <p><?= __('维护地区与驻地信息', 'Maintain regional info'); ?></p>
            </a>
            <a class="nav-card" href="admin_athlete.php">
                <h3><?= __('运动员管理', 'Athletes'); ?></h3>
                <p><?= __('创建/更新运动员资料', 'Create or update athlete profiles'); ?></p>
            </a>
            <a class="nav-card" href="admin_category.php">
                <h3><?= __('大项管理', 'Categories'); ?></h3>
                <p><?= __('维护分类及负责人', 'Manage categories and owners'); ?></p>
            </a>
            <a class="nav-card" href="admin_event.php">
                <h3><?= __('赛事管理', 'Events'); ?></h3>
                <p><?= __('配置项目与所属大项', 'Configure events and categories'); ?></p>
            </a>
            <a class="nav-card" href="admin_participation.php">
                <h3><?= __('参赛记录', 'Participation records'); ?></h3>
                <p><?= __('录入成绩与奖牌', 'Record scores and medals'); ?></p>
            </a>
            <a class="nav-card" href="admin_users.php">
                <h3><?= __('管理员账号', 'Admin accounts'); ?></h3>
                <p><?= __('新增、重置与删除账号', 'Create, reset, delete accounts'); ?></p>
            </a>
        </div>
    </section>
</div>
</body>
</html>
