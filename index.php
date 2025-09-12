<!DOCTYPE html>
<html lang="zh-HK">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Photography Team | S.K.H. Leung Kwai Yee Secondary School</title>
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', 'Microsoft JhengHei', Arial, sans-serif;
            background: #f7f7f7;
        }
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #2d3e50;
            color: #fff;
            padding: 0 32px;
            height: 64px;
        }
        .nav-left {
            display: flex;
            align-items: center;
        }
        .nav-logo {
            font-size: 1.3em;
            font-weight: bold;
            margin-right: 32px;
        }
        .nav-menu {
            display: flex;
            gap: 24px;
        }
        .nav-menu a {
            color: #fff;
            text-decoration: none;
            font-size: 1em;
            transition: color 0.2s;
            padding: 8px 16px;
            border-radius: 4px;
        }
        .nav-menu a:hover {
            color: #ffd700;
        }
        .nav-menu a.active {
            background: #34495e;
            color: #ffd700;
        }
        .nav-actions {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .nav-action-btn {
            background: #ffd700;
            color: #2d3e50;
            padding: 8px 20px;
            border-radius: 20px;
            text-decoration: none;
            font-weight: bold;
            transition: background 0.2s;
            font-size: 1em;
        }
        .nav-action-btn:hover {
            background: #ffec80;
        }
        .main {
            max-width: 800px;
            margin: 48px auto 0 auto;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            padding: 40px 32px;
        }
        .school-name {
            font-size: 2em;
            font-weight: bold;
            color: #2d3e50;
            margin-bottom: 8px;
        }
        .school-en {
            font-size: 1.2em;
            color: #555;
            margin-bottom: 24px;
        }
        .slogan {
            font-size: 1.3em;
            font-family: 'Segoe Script', cursive;
            color: #0077b6;
            margin-bottom: 32px;
        }
        .team-info {
            font-size: 1.1em;
            color: #333;
            margin-bottom: 8px;
        }
        .team-info span {
            display: inline-block;
            margin-right: 16px;
        }
        @media (max-width: 600px) {
            .main {
                padding: 20px 8px;
            }
            .navbar {
                flex-direction: column;
                height: auto;
                padding: 12px 8px;
            }
            .nav-logo {
                margin-bottom: 8px;
            }
            .nav-lang {
                display: inline-block;
            }
        }
    </style>
</head>
<body>
    <?php
    // 多语言支持
    require_once __DIR__ . '/config/lang.php';
    $default_lang = 'zh-HK';
    if (isset($_GET['lang']) && in_array($_GET['lang'], ['zh-HK', 'en'])) {
        $current_lang = $_GET['lang'];
        setcookie('site_lang', $current_lang, time()+3600*24*30, '/');
    } elseif (isset($_COOKIE['site_lang']) && in_array($_COOKIE['site_lang'], ['zh-HK', 'en'])) {
        $current_lang = $_COOKIE['site_lang'];
    } else {
        $current_lang = $default_lang;
    }
    $t = $langs[$current_lang];
    ?>
    <?php session_start(); ?>
    <nav class="navbar">
        <div class="nav-left">
            <div class="nav-logo"><?php echo $t['team']; ?></div>
            <div class="nav-menu">
                <a href="index.php?lang=<?php echo $current_lang; ?>" class="active"><?php echo $t['home']; ?></a>
                <a href="album.php?lang=<?php echo $current_lang; ?>"><?php echo $t['album']; ?></a>
                <a href="about.php?lang=<?php echo $current_lang; ?>"><?php echo $t['about']; ?></a>
                <a href="help.php?lang=<?php echo $current_lang; ?>"><?php echo $t['help']; ?></a>
            </div>
        </div>
        <div class="nav-actions">
            <?php
            require_once __DIR__ . '/config/config.php';
            $is_admin = false;
            if (isset($_SESSION['user'])) {
                $user_email = $_SESSION['user']['email'];
                $stmt = $pdo->prepare('SELECT 1 FROM admin_users WHERE email = ? LIMIT 1');
                $stmt->execute([$user_email]);
                $is_admin = $stmt->fetch() ? true : false;
            }
            ?>
            <?php if (isset($_SESSION['user'])): ?>
                <a class="nav-action-btn" href="logout.php?lang=<?php echo $current_lang; ?>">登出</a>
                <?php if ($is_admin): ?>
                    <a class="nav-action-btn" href="/admin/dashboard.php?lang=<?php echo $current_lang; ?>"><?php echo $t['admin_dashboard']; ?></a>
                <?php endif; ?>
            <?php else: ?>
                <a class="nav-action-btn" href="login.php?lang=<?php echo $current_lang; ?>"><?php echo $t['login']; ?></a>
            <?php endif; ?>
            <a class="nav-action-btn" href="?lang=<?php echo $current_lang === 'zh-HK' ? 'en' : 'zh-HK'; ?>"> <?php echo $t['lang_switch']; ?> </a>
        </div>
    </nav>
    <div class="main">
    <div class="school-name"><?php echo $t['school_name']; ?></div>
    <div class="school-en"><?php echo $t['school_en']; ?></div>
    <div class="slogan"><?php echo $t['slogan']; ?></div>
    <div class="team-info"><?php echo $t['teacher']; ?></div>
    <div class="team-info"><?php echo $t['web_admin']; ?></div>
    <div class="team-info"><?php echo $t['leader1']; ?></div>
    <div class="team-info"><?php echo $t['leader2']; ?></div>
        <!-- 统计组件开始 -->
        <?php
        // 引入数据库配置
        require_once __DIR__ . '/config/config.php';

        // 访问次数统计 - 从数据库读取并自增
        try {
            $stmt = $pdo->prepare('UPDATE site_stats SET visit_count = visit_count + 1 WHERE id = 1');
            $stmt->execute();
            $stmt = $pdo->prepare('SELECT visit_count FROM site_stats WHERE id = 1');
            $stmt->execute();
            $row = $stmt->fetch();
            $visit_count = $row ? (int)$row['visit_count'] : 0;
        } catch (Exception $e) {
            $visit_count = 0;
        }

        // 相册总数
        $album_count = 0;
        try {
            $stmt = $pdo->query('SELECT COUNT(*) AS cnt FROM albums');
            $row = $stmt->fetch();
            $album_count = $row ? (int)$row['cnt'] : 0;
        } catch (Exception $e) {
            $album_count = 0;
        }

        // 照片总数
        $photo_count = 0;
        try {
            $stmt = $pdo->query('SELECT COUNT(*) AS cnt FROM photos');
            $row = $stmt->fetch();
            $photo_count = $row ? (int)$row['cnt'] : 0;
        } catch (Exception $e) {
            $photo_count = 0;
        }
        ?>
        <div class="stats-container">
            <div class="stat-box">
                <div class="stat-label"><?php echo $t['visit_count']; ?></div>
                <div class="stat-value" id="visitCount">0</div>
            </div>
            <div class="stat-box">
                <div class="stat-label"><?php echo $t['album_count']; ?></div>
                <div class="stat-value" id="albumCount">0</div>
            </div>
            <div class="stat-box">
                <div class="stat-label"><?php echo $t['photo_count']; ?></div>
                <div class="stat-value" id="photoCount">0</div>
            </div>
        </div>
        <script>
        // 数字动画函数
        function animateValue(id, start, end, duration) {
            const obj = document.getElementById(id);
            let startTimestamp = null;
            const step = (timestamp) => {
                if (!startTimestamp) startTimestamp = timestamp;
                const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                obj.textContent = Math.floor(progress * (end - start) + start);
                if (progress < 1) {
                    window.requestAnimationFrame(step);
                } else {
                    obj.textContent = end;
                }
            };
            window.requestAnimationFrame(step);
        }
        // PHP变量传递到JS
        animateValue('visitCount', 0, <?php echo $visit_count; ?>, 1200);
        animateValue('albumCount', 0, <?php echo $album_count; ?>, 1400);
        animateValue('photoCount', 0, <?php echo $photo_count; ?>, 1600);
        </script>
        <style>
        .stats-container {
            display: flex;
            justify-content: center;
            gap: 32px;
            margin-top: 48px;
            margin-bottom: 16px;
            flex-wrap: wrap;
        }
        .stat-box {
            background: #e3f2fd;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            padding: 24px 28px;
            text-align: center;
            min-width: 120px;
            flex: 1;
            max-width: 160px;
        }
        .stat-label {
            font-size: 1.1em;
            color: #1976d2;
            margin-bottom: 12px;
        }
        .stat-value {
            font-size: 2.2em;
            font-weight: bold;
            color: #1565c0;
        }
        @media (max-width: 768px) {
            .stats-container {
                flex-direction: column;
                gap: 24px;
                align-items: center;
            }
            .stat-box {
                padding: 20px 24px;
                max-width: 200px;
                width: 100%;
            }
        }
        @media (max-width: 600px) {
            .stat-box {
                padding: 16px 20px;
            }
        }
        </style>
        <!-- 统计组件结束 -->
    </div>
    <footer style="text-align:center;color:#888;font-size:0.95em;margin:48px 0 16px 0;">
        <hr style="margin-bottom:12px;">
        Open Sources：<a href="https://github.com/adminlby/LKYPT_Web" target="_blank" style="color:#1976d2;">https://github.com/adminlby/LKYPT_Web</a>
    </footer>

    <!-- 包含协议检查组件 -->
    <?php include 'components/agreement_checker.php'; ?>
</body>
</html>
