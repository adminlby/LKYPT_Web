<!DOCTYPE html>
<html lang="zh-HK">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>關於我們 | S.K.H. Leung Kwai Yee Secondary School Photography Team</title>
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
        
        /* 延迟动画类 */
        .delay-1 { animation-delay: 0.1s; }
        .delay-2 { animation-delay: 0.2s; }
        .delay-3 { animation-delay: 0.3s; }
        .delay-4 { animation-delay: 0.4s; }
        .delay-5 { animation-delay: 0.5s; }
        .delay-6 { animation-delay: 0.6s; }

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
        
        /* 页面内容包装器 */
        .page-content {
            opacity: 0;
            transition: opacity 0.5s ease;
        }
        
        .page-content.show {
            opacity: 1;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', 'Microsoft JhengHei', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            line-height: 1.6;
            position: relative;
        }
        
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('assets/images/lkyss-pt-banner.jpg') center/cover;
            opacity: 0.1;
            z-index: -1;
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(45, 55, 80, 0.95);
            backdrop-filter: blur(10px);
            color: #fff;
            padding: 0 32px;
            height: 64px;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
        }

        .nav-left {
            display: flex;
            align-items: center;
        }

        .nav-logo {
            font-size: 1.3em;
            font-weight: bold;
            margin-right: 32px;
            color: #ffd700;
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

        .main-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .hero-section {
            background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.6)), url('assets/images/lkyss-pt-banner.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: white;
            text-align: center;
            padding: 100px 40px 80px 40px;
            margin: 40px 20px;
            border-radius: 20px;
            position: relative;
            overflow: hidden;
            min-height: 400px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .page-header {
            margin-bottom: 40px;
            color: white;
        }

        .page-title {
            font-size: 4em;
            font-weight: 700;
            margin-bottom: 24px;
            text-shadow: 3px 3px 6px rgba(0,0,0,0.5);
            background: linear-gradient(45deg, #ffd700, #ff8c00);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            color: #ffd700; /* 后备颜色 */
            animation: titleGlow 3s ease-in-out infinite alternate;
        }
        
        /* 兼容性处理 */
        @supports not (-webkit-background-clip: text) {
            .page-title {
                background: none;
                color: #ffd700;
                text-shadow: 3px 3px 6px rgba(0,0,0,0.5);
            }
        }
        
        @keyframes titleGlow {
            0% { filter: drop-shadow(0 0 5px rgba(255,215,0,0.5)); }
            100% { filter: drop-shadow(0 0 20px rgba(255,140,0,0.8)); }
        }

        .page-subtitle {
            font-size: 1.4em;
            opacity: 0.95;
            max-width: 600px;
            margin: 0 auto;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
            font-weight: 300;
        }

        .content-section {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 48px;
            margin-bottom: 40px;
            box-shadow: 0 16px 48px rgba(0, 0, 0, 0.15);
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .content-section:hover {
            transform: translateY(-8px);
            box-shadow: 0 24px 64px rgba(0, 0, 0, 0.2);
        }

        .section-title {
            font-size: 2.2em;
            color: #2c3e50;
            margin-bottom: 25px;
            text-align: center;
            position: relative;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 2px;
        }

        .section-content {
            font-size: 1.1em;
            color: #555;
            line-height: 1.8;
            text-align: justify;
        }

        .highlight-box {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin: 30px 0;
            text-align: center;
        }

        .highlight-box h3 {
            font-size: 1.8em;
            margin-bottom: 15px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }

        .stat-item {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            backdrop-filter: blur(10px);
            padding: 32px 20px;
            border-radius: 16px;
            text-align: center;
            border: 2px solid rgba(102, 126, 234, 0.2);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .stat-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }

        .stat-item:hover {
            border-color: #667eea;
            transform: translateY(-8px);
            box-shadow: 0 12px 32px rgba(102, 126, 234, 0.2);
        }
        
        .stat-item:hover::before {
            left: 100%;
        }

        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 10px;
        }

        .stat-label {
            color: #666;
            font-weight: 500;
        }

        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin: 30px 0;
        }

        .team-member {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05), rgba(118, 75, 162, 0.05));
            backdrop-filter: blur(10px);
            padding: 36px;
            border-radius: 20px;
            text-align: center;
            border: 2px solid rgba(102, 126, 234, 0.15);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .team-member::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transition: left 0.5s ease;
        }

        .team-member:hover {
            border-color: #667eea;
            transform: translateY(-10px);
            box-shadow: 0 16px 40px rgba(102, 126, 234, 0.2);
        }
        
        .team-member:hover::before {
            left: 100%;
        }
        
        .team-showcase {
            margin-bottom: 48px;
            text-align: center;
        }
        
        .showcase-container {
            position: relative;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 12px 40px rgba(0,0,0,0.2);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .showcase-container:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
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
            padding: 40px 32px 32px 32px;
            transform: translateY(100%);
            transition: transform 0.3s ease;
        }
        
        .showcase-container:hover .showcase-overlay {
            transform: translateY(0);
        }
        
        .showcase-text h3 {
            font-size: 2em;
            font-weight: 700;
            margin-bottom: 12px;
            color: #ffd700;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }
        
        .showcase-text p {
            font-size: 1.2em;
            margin-bottom: 16px;
            opacity: 0.9;
        }
        
        .showcase-website {
            font-size: 1em;
            color: #ff8c00;
            font-weight: 600;
        }

        .member-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            margin: 0 auto 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3em;
            color: white;
            box-shadow: 0 8px 24px rgba(102, 126, 234, 0.3);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .member-avatar::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, transparent 30%, rgba(255,255,255,0.2) 50%, transparent 70%);
            transform: translateX(-100%);
            transition: transform 0.6s ease;
        }
        
        .team-member:hover .member-avatar::before {
            transform: translateX(100%);
        }

        .member-name {
            font-size: 1.5em;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .member-role {
            color: #667eea;
            font-weight: 500;
            margin-bottom: 15px;
        }

        .member-description {
            color: #666;
            line-height: 1.6;
        }

        .tech-stack {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }

        .tech-item {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #667eea;
        }

        .tech-name {
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .tech-description {
            color: #666;
            font-size: 0.95em;
        }

        .footer {
            text-align: center;
            padding: 40px 20px;
            color: rgba(255, 255, 255, 0.8);
        }

        .footer-content {
            max-width: 600px;
            margin: 0 auto;
        }

        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                height: auto;
                padding: 16px;
            }

            .nav-logo {
                margin-bottom: 12px;
                margin-right: 0;
            }

            .nav-menu {
                gap: 16px;
                flex-wrap: wrap;
            }

            .main-container {
                padding: 0 15px;
            }
            
            .hero-section {
                padding: 60px 20px 50px 20px;
                margin: 20px 15px;
                background-attachment: scroll;
                min-height: 300px;
            }

            .page-title {
                font-size: 2.8em;
            }

            .content-section {
                padding: 32px 24px;
                margin-bottom: 30px;
            }

            .section-title {
                font-size: 1.8em;
            }

            .team-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 16px;
            }
            
            .stat-item {
                padding: 24px 16px;
            }
            
            .team-member {
                padding: 28px 20px;
            }
            
            .showcase-image {
                max-height: 250px;
            }
            
            .showcase-overlay {
                position: static;
                transform: none;
                background: rgba(0,0,0,0.8);
                padding: 24px 20px;
            }
            
            .showcase-text h3 {
                font-size: 1.5em;
            }
            
            .showcase-text p {
                font-size: 1em;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .page-title {
                font-size: 2em;
            }

            .section-title {
                font-size: 1.5em;
            }
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
    // 确保已启动 session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
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

    <!-- 页面内容 -->
    <div class="page-content" id="pageContent">
    <!-- 导航栏 -->
    <nav class="navbar fade-in-up delay-1">
        <div class="nav-left">
            <div class="nav-logo"><?php echo $t['team']; ?></div>
            <div class="nav-menu">
                <a href="index.php?lang=<?php echo $current_lang; ?>" class="nav-link"><?php echo $t['home']; ?></a>
                <a href="album.php?lang=<?php echo $current_lang; ?>" class="nav-link"><?php echo $t['album']; ?></a>
                <a href="about.php?lang=<?php echo $current_lang; ?>" class="active nav-link"><?php echo $t['about']; ?></a>
                <a href="help.php?lang=<?php echo $current_lang; ?>" class="nav-link"><?php echo $t['help']; ?></a>
            </div>
        </div>
        <div class="nav-actions">
            <?php
            $is_admin = false;
            
            // 只有在有用户会话时才尝试连接数据库
            if (isset($_SESSION['user'])) {
                try {
                    require_once __DIR__ . '/config/config.php';
                    $user_email = $_SESSION['user']['email'];
                    $stmt = $pdo->prepare('SELECT 1 FROM admin_users WHERE email = ? LIMIT 1');
                    $stmt->execute([$user_email]);
                    $is_admin = $stmt->fetch() ? true : false;
                } catch (Exception $e) {
                    // 如果数据库查询失败，仍然允许用户看到登录状态
                    $is_admin = false;
                }
            }
            ?>
            <?php if (isset($_SESSION['user'])): ?>
                <a class="nav-action-btn" href="logout.php?lang=<?php echo $current_lang; ?>"><?php echo $t['logout']; ?></a>
                <?php if ($is_admin): ?>
                    <a class="nav-action-btn" href="/admin/dashboard.php?lang=<?php echo $current_lang; ?>"><?php echo $t['admin_dashboard']; ?></a>
                <?php endif; ?>
            <?php else: ?>
                <a class="nav-action-btn" href="login.php?lang=<?php echo $current_lang; ?>"><?php echo $t['login']; ?></a>
            <?php endif; ?>
            <a class="nav-action-btn" href="?lang=<?php echo $current_lang === 'zh-HK' ? 'en' : 'zh-HK'; ?>">
                <?php echo $t['lang_switch']; ?>
            </a>
        </div>
    </nav>

    <!-- 英雄区块 -->
    <div class="hero-section fade-in-scale delay-2">
        <div class="page-header">
            <h1 class="page-title fade-in-up delay-3">LKYSS Photography Team</h1>
            <p class="page-subtitle fade-in-up delay-4">Capturing Moments, Creating Memories<br><?php echo $t['about_subtitle']; ?></p>
        </div>
    </div>
    
    <div class="main-container">
        <!-- 学校介绍 -->
        <div class="content-section fade-in-up delay-5">
            <h2 class="section-title"><?php echo $t['school_intro_title']; ?></h2>
            <div class="section-content">
                <p><?php echo $t['school_intro_content']; ?></p>
                
                <div class="highlight-box">
                    <h3><?php echo $t['school_mission_title']; ?></h3>
                    <p><?php echo $t['school_mission_content']; ?></p>
                </div>
            </div>
        </div>

        <!-- 团队宣传展示区域 -->
        <div class="content-section fade-in-left delay-3" style="padding: 0; background: transparent; box-shadow: none;">
            <div class="team-showcase">
                <h2 class="section-title fade-in-up delay-4" style="color: white; text-shadow: 2px 2px 4px rgba(0,0,0,0.5); margin-bottom: 40px;">
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
        </div>

        <!-- 摄影队介绍 -->
        <div class="content-section fade-in-right delay-6">
            <h2 class="section-title"><?php echo $t['team_intro_title']; ?></h2>
            <div class="section-content">
                <p><?php echo $t['team_intro_content']; ?></p>
                
                <div class="stats-grid">
                    <div class="stat-item fade-in-up delay-1">
                        <div class="stat-number">2016</div>
                        <div class="stat-label"><?php echo $t['founded_year']; ?></div>
                    </div>
                    <div class="stat-item fade-in-up delay-2">
                        <div class="stat-number">15+</div>
                        <div class="stat-label"><?php echo $t['active_members']; ?></div>
                    </div>
                    <div class="stat-item fade-in-up delay-3">
                        <div class="stat-number">4500+</div>
                        <div class="stat-label"><?php echo $t['photos_captured']; ?></div>
                    </div>
                    <div class="stat-item fade-in-up delay-4">
                        <div class="stat-number">80+</div>
                        <div class="stat-label"><?php echo $t['events_covered']; ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 团队成员 -->
        <div class="content-section fade-in-left delay-6">
            <h2 class="section-title"><?php echo $t['team_members_title']; ?></h2>
            <div class="team-grid">
                <div class="team-member">
                    <div class="member-avatar">👨🏼‍🏫</div>
                    <div class="member-name"><?php echo $t['teacher_name']; ?></div>
                    <div class="member-role"><?php echo $t['teacher_role']; ?></div>
                    <div class="member-description"><?php echo $t['teacher_description']; ?></div>
                </div>
                
                <div class="team-member">
                    <div class="member-avatar">🎥</div>
                    <div class="member-name"><?php echo $t['leader1_name']; ?></div>
                    <div class="member-role"><?php echo $t['leader1_role']; ?></div>
                    <div class="member-description"><?php echo $t['leader1_description']; ?></div>
                </div>
                
                <div class="team-member">
                    <div class="member-avatar">🎨</div>
                    <div class="member-name"><?php echo $t['leader2_name']; ?></div>
                    <div class="member-role"><?php echo $t['leader2_role']; ?></div>
                    <div class="member-description"><?php echo $t['leader2_description']; ?></div>
                </div>
                
                <div class="team-member">
                    <div class="member-avatar">💻</div>
                    <div class="member-name"><?php echo $t['webmaster_name']; ?></div>
                    <div class="member-role"><?php echo $t['webmaster_role']; ?></div>
                    <div class="member-description"><?php echo $t['webmaster_description']; ?></div>
                </div>
            </div>
        </div>

        <!-- 技术信息 -->
        <div class="content-section fade-in-up delay-6">
            <h2 class="section-title"><?php echo $t['tech_info_title']; ?></h2>
            <div class="section-content">
                <p><?php echo $t['tech_info_content']; ?></p>
                
                <div class="tech-stack">
                    <div class="tech-item">
                        <div class="tech-name">PHP + MySQL</div>
                        <div class="tech-description"><?php echo $t['backend_tech']; ?></div>
                    </div>
                    <div class="tech-item">
                        <div class="tech-name">HTML + CSS + JavaScript</div>
                        <div class="tech-description"><?php echo $t['frontend_tech']; ?></div>
                    </div>
                    <div class="tech-item">
                        <div class="tech-name">Google OAuth 2.0</div>
                        <div class="tech-description"><?php echo $t['auth_tech']; ?></div>
                    </div>
                    <div class="tech-item">
                        <div class="tech-name">Multi-language Support</div>
                        <div class="tech-description"><?php echo $t['lang_tech']; ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 联系信息 -->
        <div class="content-section">
            <h2 class="section-title"><?php echo $t['contact_title']; ?></h2>
            <div class="section-content">
                <p><?php echo $t['contact_content']; ?></p>
                
                <div class="highlight-box">
                    <h3><?php echo $t['school_name']; ?></h3>
                    <p><?php echo $t['school_en']; ?></p>
                    <p><?php echo $t['contact_email']; ?></p>
                </div>
            </div>
        </div>
    </div>
    </div>
    <!-- 页面内容结束 -->

    <!-- 页脚 -->
    <footer class="footer fade-in-up delay-6">
        <div class="footer-content">
            <p><?php echo $t['footer_text']; ?></p>
            <p><?php echo $t['slogan']; ?></p>
        </div>
    </footer>

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