<?php
require_once __DIR__ . '/lang.php';
?>
<!DOCTYPE html>
<html lang="<?= app_lang_html_attr(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('公众查询系统', 'Public Explorer'); ?></title>
    <link rel="stylesheet" href="assets/ui.css">
</head>
<body>
<div class="app-shell">
    <header class="page-header">
        <div>
            <p><?= __('Public Explorer', 'Public Explorer'); ?></p>
            <h1>🔍 <?= __('公众查询系统', 'Public Explorer'); ?></h1>
            <p><?= __('快速浏览运动员、代表团、赛事与奖牌记录', 'Browse athletes, delegations, events, and medals.'); ?></p>
        </div>
        <div class="header-actions">
            <?= render_lang_toggle(); ?>
            <a class="btn btn-ghost" href="index.php?lang=<?= app_lang_get(); ?>">← <?= __('返回首页', 'Back to home'); ?></a>
        </div>
    </header>

    <section class="app-card">
        <h2 class="card-title"><?= __('请选择要查询的内容', 'Select the dataset to explore'); ?></h2>
        <p class="card-subtitle"><?= __('快速浏览运动员、代表团、赛事与奖牌记录', 'Find athletes, delegations, events, and medal stats.'); ?></p>

        <div class="nav-card-grid">
            <a class="nav-card" href="athlete_list.php?lang=<?= app_lang_get(); ?>">
                <h3><?= __('运动员查询', 'Athletes'); ?></h3>
                <p><?= __('查看运动员基本信息与所属代表团', 'View athlete details and delegations.'); ?></p>
            </a>
            <a class="nav-card" href="delegation_list.php?lang=<?= app_lang_get(); ?>">
                <h3><?= __('代表团查询', 'Delegations'); ?></h3>
                <p><?= __('了解各省代表团地区与驻地', 'See provincial delegations and locations.'); ?></p>
            </a>
            <a class="nav-card" href="category_list.php?lang=<?= app_lang_get(); ?>">
                <h3><?= __('大项分类查询', 'Categories'); ?></h3>
                <p><?= __('浏览所有比赛大项与负责人', 'Browse major categories and owners.'); ?></p>
            </a>
            <a class="nav-card" href="event_list.php?lang=<?= app_lang_get(); ?>">
                <h3><?= __('比赛项目查询', 'Events'); ?></h3>
                <p><?= __('查看项目级别和所属大项', 'View events and their categories.'); ?></p>
            </a>
            <a class="nav-card" href="participation_list.php?lang=<?= app_lang_get(); ?>">
                <h3><?= __('参赛/奖牌查询', 'Participation & medals'); ?></h3>
                <p><?= __('检索运动员成绩与奖牌情况', 'Search athlete scores and medals.'); ?></p>
            </a>
        </div>
    </section>
</div>
</body>
</html>
