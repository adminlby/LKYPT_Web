<!DOCTYPE html>
<html lang="zh-HK">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Photography Team | S.K.H. Leung Kwai Yee Secondary School</title>
    <style>
        /* 页面加载动画 */
        .page-loader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            transition: opacity 0.8s ease, visibility 0.8s ease;
        }
        
        .page-loader.fade-out {
            opacity: 0;
            visibility: hidden;
        }
        
        .loader-camera {
            font-size: 4rem;
            color: #ffd700;
            animation: cameraShutter 2s ease-in-out infinite;
            margin-bottom: 20px;
        }
        
        @keyframes cameraShutter {
            0%, 100% { transform: scale(1) rotate(0deg); }
            25% { transform: scale(1.1) rotate(-5deg); }
            50% { transform: scale(1.2) rotate(0deg); filter: brightness(1.5); }
            75% { transform: scale(1.1) rotate(5deg); }
        }
        
        .loader-text {
            color: white;
            font-size: 1.2rem;
            font-weight: 300;
            letter-spacing: 2px;
            animation: textFade 2s ease-in-out infinite;
        }
        
        @keyframes textFade {
            0%, 100% { opacity: 0.7; }
            50% { opacity: 1; }
        }
        
        /* 页面内容进入动画 */
        .fade-in-up {
            opacity: 0;
            transform: translateY(30px);
            animation: none; /* 初始时不播放动画 */
        }
        
        .fade-in-left {
            opacity: 0;
            transform: translateX(-30px);
            animation: none;
        }
        
        .fade-in-right {
            opacity: 0;
            transform: translateX(30px);
            animation: none;
        }
        
        .fade-in-scale {
            opacity: 0;
            transform: scale(0.9);
            animation: none;
        }
        
        /* 动画激活状态 */
        .fade-in-up.animate {
            animation: fadeInUp 0.8s ease forwards;
        }
        
        .fade-in-left.animate {
            animation: fadeInLeft 0.8s ease forwards;
        }
        
        .fade-in-right.animate {
            animation: fadeInRight 0.8s ease forwards;
        }
        
        .fade-in-scale.animate {
            animation: fadeInScale 0.8s ease forwards;
        }
        
        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes fadeInLeft {
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @keyframes fadeInRight {
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @keyframes fadeInScale {
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
        
        /* 页面切换动画 */
        .page-transition {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            z-index: 8888;
            display: flex;
            justify-content: center;
            align-items: center;
            opacity: 0;
            visibility: hidden;
            transition: all 0.5s ease;
        }
        
        .page-transition.active {
            opacity: 1;
            visibility: visible;
        }
        
        .transition-camera {
            font-size: 3rem;
            color: #ffd700;
            animation: transitionSpin 1s ease-in-out infinite;
        }
        
        @keyframes transitionSpin {
            0% { transform: rotate(0deg) scale(1); }
            50% { transform: rotate(180deg) scale(1.2); }
            100% { transform: rotate(360deg) scale(1); }
        }
        
        /* 延迟动画类 */
        .delay-1 { animation-delay: 0.1s; }
        .delay-2 { animation-delay: 0.2s; }
        .delay-3 { animation-delay: 0.3s; }
        .delay-4 { animation-delay: 0.4s; }
        .delay-5 { animation-delay: 0.5s; }
        .delay-6 { animation-delay: 0.6s; }

        body {
            margin: 0;
            font-family: 'Segoe UI', 'Microsoft JhengHei', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
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
            max-width: 900px;
            margin: 32px auto 0 auto;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.15);
            padding: 48px 40px;
            position: relative;
            overflow: hidden;
        }
        
        .main::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="camera" x="0" y="0" width="20" height="20" patternUnits="userSpaceOnUse"><circle cx="10" cy="10" r="1" fill="%23f0f0f0" opacity="0.3"/></pattern></defs><rect width="100" height="100" fill="url(%23camera)"/></svg>');
            pointer-events: none;
            z-index: 0;
        }
        
        .main > * {
            position: relative;
            z-index: 1;
        }
        
        /* 英雄区域样式 */
                .hero-section {
            background: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.5)), url('assets/images/lkyss-pt-banner.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: white;
            text-align: center;
            padding: 80px 40px 60px 40px;
            border-radius: 16px;
            margin-bottom: 48px;
            position: relative;
            overflow: hidden;
            min-height: 400px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .hero-content {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .hero-icon {
            font-size: 4em;
            margin-bottom: 20px;
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        
        .hero-title {
            font-size: 3.5em;
            font-weight: 700;
            margin-bottom: 24px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
            background: linear-gradient(45deg, #ffd700, #ff8c00);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            color: #ffd700; /* 后备颜色 */
            animation: titleGlow 3s ease-in-out infinite alternate;
        }
        
        /* 兼容性处理 */
        @supports not (-webkit-background-clip: text) {
            .hero-title {
                background: none;
                color: #ffd700;
                text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
            }
        }
        
        @keyframes titleGlow {
            0% { filter: drop-shadow(0 0 5px rgba(255,215,0,0.5)); }
            100% { filter: drop-shadow(0 0 20px rgba(255,140,0,0.8)); }
        }        .hero-subtitle {
            font-size: 1.3em;
            margin: 0 0 12px 0;
            opacity: 0.9;
        }
        
        .hero-slogan {
            font-size: 1.1em;
            font-style: italic;
            margin: 0 0 32px 0;
            opacity: 0.8;
            color: #ffd700;
        }
        
        .hero-description {
            font-size: 1.3em;
            margin-bottom: 32px;
            opacity: 0.95;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
            font-weight: 300;
        }        .hero-description p {
            font-size: 1.1em;
            margin: 12px 0;
            opacity: 0.9;
        }
        
        .hero-cta {
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .cta-button {
            padding: 12px 24px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .cta-button.primary {
            background: #ffd700;
            color: #2d3e50;
        }
        
        .cta-button.primary:hover {
            background: #ffec80;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 215, 0, 0.4);
        }
        
        .cta-button.secondary {
            background: transparent;
            color: white;
            border: 2px solid white;
        }
        
        .cta-button.secondary:hover {
            background: white;
            color: #2d3e50;
            transform: translateY(-2px);
        }
        
        /* 团队区域样式 */
        .team-showcase {
            margin-bottom: 48px;
            text-align: center;
        }
        
        .showcase-container {
            position: relative;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .showcase-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 16px 48px rgba(0,0,0,0.2);
        }
        
        .showcase-image {
            width: 100%;
            height: auto;
            display: block;
            max-height: 400px;
            object-fit: cover;
        }
        
        .showcase-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(0,0,0,0.8));
            color: white;
            padding: 32px 24px 24px 24px;
            transform: translateY(100%);
            transition: transform 0.3s ease;
        }
        
        .showcase-container:hover .showcase-overlay {
            transform: translateY(0);
        }
        
        .showcase-text h3 {
            font-size: 1.8em;
            font-weight: 700;
            margin-bottom: 8px;
            color: #ffd700;
        }
        
        .showcase-text p {
            font-size: 1.1em;
            margin-bottom: 12px;
            opacity: 0.9;
        }
        
        .showcase-website {
            font-size: 0.9em;
            color: #ff8c00;
            font-weight: 500;
        }

        .team-section {
            margin-bottom: 40px;
        }
        
        .section-title {
            font-size: 1.8em;
            color: #2d3e50;
            margin-bottom: 24px;
            text-align: center;
            font-weight: bold;
        }
        
        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }
        
        .team-member {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 24px;
            border-radius: 12px;
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 1px solid #dee2e6;
        }
        
        .team-member:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        .member-icon {
            font-size: 2.5em;
            margin-bottom: 16px;
        }
        
        .member-role {
            font-size: 0.9em;
            color: #6c757d;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .member-name {
            font-size: 1.1em;
            color: #2d3e50;
            font-weight: bold;
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
        
        /* 页面内容包装器 */
        .page-content {
            opacity: 0;
            transition: opacity 0.5s ease;
        }
        
        .page-content.show {
            opacity: 1;
        }
    </style>
</head>
<body>
    <!-- 页面加载器 -->
    <div class="page-loader" id="pageLoader">
        <div class="loader-camera">📸</div>
        <div class="loader-text">LKYSS Photography Team</div>
    </div>
    
    <!-- 页面切换动画 -->
    <div class="page-transition" id="pageTransition">
        <div class="transition-camera">📸</div>
    </div>

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
    
    <!-- 页面内容 -->
    <div class="page-content" id="pageContent">
    <nav class="navbar fade-in-up delay-1">
        <div class="nav-left">
            <div class="nav-logo"><?php echo $t['team']; ?></div>
            <div class="nav-menu">
                <a href="index.php?lang=<?php echo $current_lang; ?>" class="active nav-link"><?php echo $t['home']; ?></a>
                <a href="album.php?lang=<?php echo $current_lang; ?>" class="nav-link"><?php echo $t['album']; ?></a>
                <a href="about.php?lang=<?php echo $current_lang; ?>" class="nav-link"><?php echo $t['about']; ?></a>
                <a href="help.php?lang=<?php echo $current_lang; ?>" class="nav-link"><?php echo $t['help']; ?></a>
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
    
    <!-- 摄影队英雄区域 -->
    <div class="hero-section fade-in-scale delay-2">
        <div class="hero-content">
            <div class="hero-icon fade-in-up delay-3">📸</div>
            <h1 class="hero-title fade-in-up delay-4">LKYSS Photography Team</h1>
            <p class="hero-subtitle fade-in-up delay-5"><?php echo $t['school_name']; ?></p>
            <p class="hero-slogan fade-in-up delay-6">Capturing Moments, Creating Memories</p>
            <div class="hero-description fade-in-up delay-6">
                <p>🎯 <?php echo $current_lang === 'zh-HK' ? '專業攝影團隊，記錄校園美好時光' : 'Professional photography team, capturing beautiful campus moments'; ?></p>
                <p>🏆 <?php echo $current_lang === 'zh-HK' ? '致力於捕捉每個珍貴瞬間，創造永恆回憶' : 'Dedicated to capturing precious moments and creating eternal memories'; ?></p>
            </div>
            <?php if (!isset($_SESSION['user'])): ?>
            <div class="hero-cta fade-in-up delay-6">
                <a href="album.php?lang=<?php echo $current_lang; ?>" class="cta-button primary nav-link">
                    📷 <?php echo $current_lang === 'zh-HK' ? '瀏覽作品集' : 'Browse Portfolio'; ?>
                </a>
                <a href="about.php?lang=<?php echo $current_lang; ?>" class="cta-button secondary nav-link">
                    ℹ️ <?php echo $current_lang === 'zh-HK' ? '了解我們' : 'About Us'; ?>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="main">
        <!-- 团队宣传图展示区域 -->
        <div class="team-showcase fade-in-left delay-3">
            <h2 class="section-title fade-in-up delay-4">
                🎨 <?php echo $current_lang === 'zh-HK' ? '團隊風采' : 'Team Showcase'; ?>
            </h2>
            <div class="showcase-container fade-in-scale delay-5">
                <img src="assets/images/lkyss-pt-banner.jpg" alt="LKYSS Photography Team Showcase" class="showcase-image">
                <div class="showcase-overlay">
                    <div class="showcase-text">
                        <h3>LKYSS Photography Team</h3>
                        <p><?php echo $current_lang === 'zh-HK' ? '記錄美好瞬間，分享精彩時光' : 'Capturing Moments, Creating Memories'; ?></p>
                        <span class="showcase-website">lkypt.lbynb.top</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 团队介绍区域 -->
        <div class="team-section fade-in-right delay-4">
            <h2 class="section-title fade-in-up delay-5">
                👥 <?php echo $current_lang === 'zh-HK' ? '團隊成員' : 'Team Members'; ?>
            </h2>
            <div class="team-grid">
                <div class="team-member fade-in-up delay-1">
                    <div class="member-icon">👨🏼‍🏫</div>
                    <div class="member-info">
                        <div class="member-role"><?php echo $current_lang === 'zh-HK' ? '指導老師' : 'Supervisor'; ?></div>
                        <div class="member-name"><?php echo $current_lang === 'zh-HK' ? '梁鑽淇老師' : 'Mr. Leung Chun Ki'; ?></div>
                    </div>
                </div>
                <div class="team-member fade-in-up delay-2">
                    <div class="member-icon">📝</div>
                    <div class="member-info">
                        <div class="member-role"><?php echo $current_lang === 'zh-HK' ? '隊長 / 網站管理員' : 'Captain / Web Admin'; ?></div>
                        <div class="member-name"><?php echo $current_lang === 'zh-HK' ? '劉濱源 (4C 13)' : 'LIU Bonny (4C 18)'; ?></div>
                    </div>
                </div>
                <div class="team-member fade-in-up delay-3">
                    <div class="member-icon">📷</div>
                    <div class="member-info">
                        <div class="member-role"><?php echo $current_lang === 'zh-HK' ? '隊長' : 'Captain'; ?></div>
                        <div class="member-name"><?php echo $current_lang === 'zh-HK' ? '吳釗航 (4D 19)' : 'Wu Chiu Hong (4D 19)'; ?></div>
                    </div>
                </div>
                <div class="team-member fade-in-up delay-4">
                    <div class="member-icon">📸</div>
                    <div class="member-info">
                        <div class="member-role"><?php echo $current_lang === 'zh-HK' ? '設計＆顧問' : 'Team Member'; ?></div>
                        <div class="member-name"><?php echo $current_lang === 'zh-HK' ? '陳永添 (6D 02)' : 'CHAN Wing Tim (6D 02)'; ?></div>
                    </div>
                </div>
                <div class="team-member fade-in-up delay-5">
                    <div class="member-icon">🌄</div>
                    <div class="member-info">
                        <div class="member-role"><?php echo $current_lang === 'zh-HK' ? '設計＆顧問' : 'Team Member'; ?></div>
                        <div class="member-name"><?php echo $current_lang === 'zh-HK' ? '吳臻榮 (6B 15)' : 'NG Chun Wing (6B 15)'; ?></div>
                    </div>
                </div>
            </div>
        </div>
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
        <!-- 统计区域 -->
        <div class="stats-section">
            <h2 class="section-title">
                📊 <?php echo $current_lang === 'zh-HK' ? '攝影隊成果' : 'Photography Achievements'; ?>
            </h2>
            <div class="stats-container fade-in-up delay-6">
            <div class="stat-box fade-in-left delay-1">
                <div class="stat-label"><?php echo $t['visit_count']; ?></div>
                <div class="stat-value" id="visitCount">0</div>
            </div>
            <div class="stat-box fade-in-left delay-2">
                <div class="stat-label"><?php echo $t['album_count']; ?></div>
                <div class="stat-value" id="albumCount">0</div>
            </div>
            <div class="stat-box fade-in-left delay-3">
                <div class="stat-label"><?php echo $t['photo_count']; ?></div>
                <div class="stat-value" id="photoCount">0</div>
            </div>
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
            margin-top: 24px;
            margin-bottom: 16px;
            flex-wrap: wrap;
        }
        .stat-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            padding: 24px 28px;
            text-align: center;
            min-width: 120px;
            flex: 1;
            max-width: 160px;
            transition: transform 0.3s ease;
        }
        
        .stat-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }
        
        .stat-label {
            font-size: 1.1em;
            color: #ffd700;
            margin-bottom: 12px;
            font-weight: 500;
        }
        .stat-value {
            font-size: 2.2em;
            font-weight: bold;
            color: white;
        }
        @media (max-width: 768px) {
            .main {
                margin: 20px 16px 0 16px;
                padding: 32px 24px;
            }
            
            .hero-section {
                padding: 40px 20px 30px 20px;
                background-attachment: scroll;
                min-height: 300px;
            }
            
            .hero-title {
                font-size: 2.2em;
            }
            
            .hero-description {
                font-size: 1em;
            }
            
            .hero-icon {
                font-size: 3em;
            }
            
            .hero-cta {
                flex-direction: column;
                align-items: center;
            }
            
            .cta-button {
                width: 200px;
                justify-content: center;
            }
            
            .team-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }
            
            .showcase-image {
                max-height: 250px;
            }
            
            .showcase-overlay {
                position: static;
                transform: none;
                background: rgba(0,0,0,0.8);
                padding: 20px;
            }
            
            .showcase-text h3 {
                font-size: 1.4em;
            }
            
            .stats-container {
                flex-direction: column;
                gap: 16px;
                align-items: center;
            }
            .stat-box {
                padding: 20px 24px;
                max-width: 200px;
                width: 100%;
            }
            
            .navbar {
                flex-direction: column;
                height: auto;
                padding: 12px 16px;
            }
            .nav-left {
                flex-direction: column;
                width: 100%;
                margin-bottom: 12px;
            }
            .nav-logo {
                margin-bottom: 12px;
                margin-right: 0;
            }
            .nav-menu {
                justify-content: center;
                flex-wrap: wrap;
                gap: 12px;
            }
            .nav-actions {
                justify-content: center;
                flex-wrap: wrap;
                gap: 8px;
            }
        }
        @media (max-width: 600px) {
            .stat-box {
                padding: 16px 20px;
            }
            
            .hero-title {
                font-size: 1.8em;
            }
            
            .team-member {
                padding: 20px;
            }
            
            .hero-description {
                font-size: 1em;
            }
            
            .nav-menu a {
                padding: 8px 12px;
                font-size: 0.9em;
            }
        }
        </style>
        <!-- 统计组件结束 -->
    </div>
    <footer style="text-align:center;color:#888;font-size:0.95em;margin:48px 0 16px 0;">
        <hr style="margin-bottom:12px;">
        Open Sources：<a href="https://github.com/adminlby/LKYPT_Web" target="_blank" style="color:#1976d2;">https://github.com/adminlby/LKYPT_Web</a>
    </footer>
    </div>
    <!-- 页面内容结束 -->

    <!-- 包含协议检查组件 -->
    <?php include 'components/agreement_checker.php'; ?>

    <script>
        // 页面加载完成后执行
        window.addEventListener('load', function() {
            // 延迟隐藏加载器，让用户看到加载动画
            setTimeout(function() {
                const loader = document.getElementById('pageLoader');
                if (loader) {
                    loader.classList.add('fade-out');
                    // 加载器完全隐藏后移除元素并启动内容动画
                    setTimeout(function() {
                        loader.style.display = 'none';
                        // 启动页面内容动画
                        startContentAnimations();
                    }, 800);
                }
            }, 1500); // 1.5秒后开始隐藏
        });

        // 启动内容动画
        function startContentAnimations() {
            // 显示页面内容
            const pageContent = document.getElementById('pageContent');
            if (pageContent) {
                pageContent.classList.add('show');
            }
            
            // 启动各个元素的动画
            const animateElements = document.querySelectorAll('.fade-in-up, .fade-in-left, .fade-in-right, .fade-in-scale');
            animateElements.forEach(function(element, index) {
                setTimeout(function() {
                    element.classList.add('animate');
                }, index * 100); // 每个元素延迟100ms
            });
        }

        // 页面切换功能
        function transitionToPage(url) {
            const transition = document.getElementById('pageTransition');
            if (transition) {
                transition.classList.add('active');
                setTimeout(function() {
                    window.location.href = url;
                }, 500);
            } else {
                window.location.href = url;
            }
        }

        // 为所有导航链接添加页面切换效果
        document.addEventListener('DOMContentLoaded', function() {
            const navLinks = document.querySelectorAll('.nav-link');
            navLinks.forEach(function(link) {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const url = this.getAttribute('href');
                    transitionToPage(url);
                });
            });
        });

        // 监听浏览器后退按钮
        window.addEventListener('popstate', function(e) {
            const transition = document.getElementById('pageTransition');
            if (transition) {
                transition.classList.add('active');
            }
        });

        // 页面可见性改变时的处理
        document.addEventListener('visibilitychange', function() {
            if (document.visibilityState === 'visible') {
                const transition = document.getElementById('pageTransition');
                if (transition && transition.classList.contains('active')) {
                    setTimeout(function() {
                        transition.classList.remove('active');
                    }, 100);
                }
            }
        });
    </script>
</body>
</html>
