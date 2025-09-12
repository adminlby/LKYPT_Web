<!DOCTYPE html>
<html lang="zh-HK">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>關於我們 | S.K.H. Leung Kwai Yee Secondary School Photography Team</title>
    <style>
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
            padding: 40px 20px;
        }

        .page-header {
            text-align: center;
            margin-bottom: 60px;
            color: white;
        }

        .page-title {
            font-size: 3.5em;
            font-weight: bold;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .page-subtitle {
            font-size: 1.3em;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
        }

        .content-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .content-section:hover {
            transform: translateY(-5px);
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
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            text-align: center;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }

        .stat-item:hover {
            border-color: #667eea;
            transform: translateY(-5px);
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
            background: #f8f9fa;
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }

        .team-member:hover {
            border-color: #667eea;
            transform: translateY(-5px);
        }

        .member-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5em;
            color: white;
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
                padding: 20px 15px;
            }

            .page-title {
                font-size: 2.5em;
            }

            .content-section {
                padding: 25px;
            }

            .section-title {
                font-size: 1.8em;
            }

            .team-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
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

    <!-- 导航栏 -->
    <nav class="navbar">
        <div class="nav-left">
            <div class="nav-logo"><?php echo $t['team']; ?></div>
            <div class="nav-menu">
                <a href="index.php?lang=<?php echo $current_lang; ?>"><?php echo $t['home']; ?></a>
                <a href="album.php?lang=<?php echo $current_lang; ?>"><?php echo $t['album']; ?></a>
                <a href="about.php?lang=<?php echo $current_lang; ?>" class="active"><?php echo $t['about']; ?></a>
                <a href="help.php?lang=<?php echo $current_lang; ?>"><?php echo $t['help']; ?></a>
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

    <div class="main-container">
        <!-- 页面标题 -->
        <div class="page-header">
            <h1 class="page-title"><?php echo $t['about_title']; ?></h1>
            <p class="page-subtitle"><?php echo $t['about_subtitle']; ?></p>
        </div>

        <!-- 学校介绍 -->
        <div class="content-section">
            <h2 class="section-title"><?php echo $t['school_intro_title']; ?></h2>
            <div class="section-content">
                <p><?php echo $t['school_intro_content']; ?></p>
                
                <div class="highlight-box">
                    <h3><?php echo $t['school_mission_title']; ?></h3>
                    <p><?php echo $t['school_mission_content']; ?></p>
                </div>
            </div>
        </div>

        <!-- 摄影队介绍 -->
        <div class="content-section">
            <h2 class="section-title"><?php echo $t['team_intro_title']; ?></h2>
            <div class="section-content">
                <p><?php echo $t['team_intro_content']; ?></p>
                
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-number">2019</div>
                        <div class="stat-label"><?php echo $t['founded_year']; ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">15+</div>
                        <div class="stat-label"><?php echo $t['active_members']; ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">4500+</div>
                        <div class="stat-label"><?php echo $t['photos_captured']; ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">80+</div>
                        <div class="stat-label"><?php echo $t['events_covered']; ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 团队成员 -->
        <div class="content-section">
            <h2 class="section-title"><?php echo $t['team_members_title']; ?></h2>
            <div class="team-grid">
                <div class="team-member">
                    <div class="member-avatar">👨🏼‍🏫</div>
                    <div class="member-name"><?php echo $t['teacher_name']; ?></div>
                    <div class="member-role"><?php echo $t['teacher_role']; ?></div>
                    <div class="member-description"><?php echo $t['teacher_description']; ?></div>
                </div>
                
                <div class="team-member">
                    <div class="member-avatar">📝</div>
                    <div class="member-name"><?php echo $t['leader1_name']; ?></div>
                    <div class="member-role"><?php echo $t['leader1_role']; ?></div>
                    <div class="member-description"><?php echo $t['leader1_description']; ?></div>
                </div>
                
                <div class="team-member">
                    <div class="member-avatar">📝</div>
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
        <div class="content-section">
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

    <!-- 页脚 -->
    <footer class="footer">
        <div class="footer-content">
            <p><?php echo $t['footer_text']; ?></p>
            <p><?php echo $t['slogan']; ?></p>
        </div>
    </footer>

    <!-- 包含协议检查组件 -->
    <?php include 'components/agreement_checker.php'; ?>
</body>
</html>