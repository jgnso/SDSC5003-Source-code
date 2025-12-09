<?php
require_once __DIR__ . '/lang.php';
?>
<!DOCTYPE html>
<html lang="<?= app_lang_html_attr(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('第十五届全运会信息系统', 'The 15th National Games Information System'); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Microsoft YaHei', 'Segoe UI', sans-serif; }
        body {
            background: linear-gradient(135deg, #1a2980, #26d0ce);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: clamp(24px, 6vw, 90px);
            color: #fff;
        }
        .container {
            width: min(1500px, 96vw);
            position: relative;
            background: rgba(0,0,0,0.18);
            border-radius: 40px;
            padding: clamp(28px, 4vw, 60px);
            box-shadow: 0 40px 90px rgba(0,0,0,0.35);
            display: flex;
            flex-direction: column;
            gap: clamp(24px, 3vw, 48px);
        }
        .lang-toggle-wrapper { position: absolute; top: clamp(18px, 3vw, 32px); right: clamp(18px, 3vw, 32px); }
        .lang-switch {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.35);
        }
        .lang-switch .lang-btn {
            color: #fff;
            text-decoration: none;
            font-weight: 600;
            opacity: 0.6;
        }
        .lang-switch .lang-btn.active {
            opacity: 1;
        }
        .lang-switch .divider { color: rgba(255, 255, 255, 0.6); }
        .header { text-align: center; color: white; margin-bottom: clamp(20px, 2vw, 40px); }
        .logo { font-size: clamp(4rem, 3vw + 2rem, 6rem); }
        .header h1 { font-size: clamp(2rem, 2vw + 1.6rem, 3rem); margin-top: 12px; }
        .header p { font-size: clamp(1rem, 0.8vw + 0.9rem, 1.4rem); opacity: 0.9; }
        .role-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: clamp(20px, 3vw, 40px);
        }
        .role-card {
            background: rgba(255,255,255,0.96);
            border-radius: 28px;
            padding: clamp(24px, 2.5vw, 42px);
            text-align: center;
            box-shadow: 0 25px 60px rgba(0,0,0,0.25);
            transition: .3s;
            color: #2c3e50;
            min-height: clamp(280px, 30vh, 360px);
        }
        .role-card:hover { transform: translateY(-8px); }
        .card-title { font-size: clamp(1.6rem, 0.8vw + 1.4rem, 2.1rem); margin: 10px 0 20px; color: #2c3e50; }
    .feature-list { text-align: left; margin: 20px 0; display: flex; flex-direction: column; gap: 8px; }
    .feature { padding: 5px 0; color: #555; font-size: 1rem; display: flex; align-items: center; gap: 6px; }
        a.enter-btn {
            display: inline-flex;
            margin-top: 24px;
            padding: 16px 32px;
            border-radius: 999px;
            text-decoration: none;
            color: white;
            font-weight: bold;
            justify-content: center;
            width: 100%;
            font-size: 1.05rem;
        }
        .user-btn { background: linear-gradient(120deg, #3498db, #4aa8ff); }
        .admin-btn { background: linear-gradient(120deg, #e74c3c, #f39c12); }
        @media (max-width: 720px) {
            .lang-toggle-wrapper { position: static; text-align: right; margin-bottom: 12px; }
            .container { border-radius: 24px; padding: 24px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="lang-toggle-wrapper">
            <?= render_lang_toggle(); ?>
        </div>
        <div class="header">
            <div class="logo">🏆</div>
            <h1><?= __('第十五届全运会信息系统', 'The 15th National Games Information System'); ?></h1>
            <p><?= __('请选择您的访问身份', 'Please select your usage identity'); ?></p>
        </div>

        <div class="role-cards">
            <div class="role-card">
                <h3 class="card-title"><?= __('公众查询系统', 'Public System'); ?></h3>
                <div class="feature-list">
                    <div class="feature">✓ <?= __('运动员信息查询', 'Athlete inquiry'); ?></div>
                    <div class="feature">✓ <?= __('代表团信息查询', 'Delegation inquiry'); ?></div>
                    <div class="feature">✓ <?= __('大项分类浏览', 'Category browsing'); ?></div>
                    <div class="feature">✓ <?= __('比赛项目查询', 'Event lookup'); ?></div>
                    <div class="feature">✓ <?= __('参赛成绩 / 奖牌查询', 'Results & medal lookup'); ?></div>
                </div>
                <a class="enter-btn user-btn" href="public.php?lang=<?= app_lang_get(); ?>"><?= __('进入系统 →', 'Enter →'); ?></a>
            </div>

            <div class="role-card">
                <h3 class="card-title"><?= __('管理员后台', 'Admin System'); ?></h3>
                <div class="feature-list">
                    <div class="feature">🔧 <?= __('运动员管理', 'Athlete management'); ?></div>
                    <div class="feature">🔧 <?= __('代表团管理', 'Delegation management'); ?></div>
                    <div class="feature">🔧 <?= __('大项管理', 'Category management'); ?></div>
                    <div class="feature">🔧 <?= __('比赛项目管理', 'Event management'); ?></div>
                    <div class="feature">🔧 <?= __('参赛记录 / 奖牌管理', 'Participation & medals'); ?></div>
                </div>
                <a class="enter-btn admin-btn" href="admin_login.php?lang=<?= app_lang_get(); ?>"><?= __('进入后台 →', 'Enter →'); ?></a>
            </div>
        </div>
    </div>
</body>
</html>
