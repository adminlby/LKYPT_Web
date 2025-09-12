<!DOCTYPE html>
<html lang="zh-HK">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>網站說明 | S.K.H. Leung Kwai Yee Secondary School Photography Team</title>
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
            line-height: 1.7;
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
            max-width: 1000px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .page-header {
            text-align: center;
            margin-bottom: 60px;
            color: white;
        }

        .page-title {
            font-size: 3em;
            font-weight: bold;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .page-subtitle {
            font-size: 1.2em;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
        }

        .content-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .section-title {
            font-size: 2em;
            color: #2c3e50;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .section-title::before {
            font-size: 1.2em;
        }

        .section-content {
            color: #555;
            line-height: 1.8;
        }

        .section-content h2 {
            color: #2c3e50;
            margin: 30px 0 15px 0;
            font-size: 1.4em;
            border-left: 4px solid #667eea;
            padding-left: 15px;
        }

        .section-content h3 {
            color: #34495e;
            margin: 20px 0 10px 0;
            font-size: 1.2em;
        }

        .section-content ul {
            margin: 15px 0;
            padding-left: 25px;
        }

        .section-content li {
            margin-bottom: 8px;
        }

        .section-content p {
            margin-bottom: 15px;
        }

        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            border-left: 4px solid #f39c12;
        }

        .info-box {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            border-left: 4px solid #17a2b8;
        }

        .contact-box {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 25px;
            border-radius: 15px;
            margin: 30px 0;
            text-align: center;
        }

        .contact-box h3 {
            margin-bottom: 15px;
            font-size: 1.5em;
        }

        .contact-box a {
            color: #ffd700;
            text-decoration: none;
        }

        .contact-box a:hover {
            text-decoration: underline;
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
                font-size: 2.2em;
            }

            .content-section {
                padding: 25px;
            }

            .section-title {
                font-size: 1.6em;
            }
        }

        @media (max-width: 480px) {
            .page-title {
                font-size: 1.8em;
            }

            .section-title {
                font-size: 1.4em;
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
                <a href="about.php?lang=<?php echo $current_lang; ?>"><?php echo $t['about']; ?></a>
                <a href="help.php?lang=<?php echo $current_lang; ?>" class="active"><?php echo $t['help']; ?></a>
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
            <h1 class="page-title"><?php echo $t['help_title']; ?></h1>
            <p class="page-subtitle"><?php echo $t['help_subtitle']; ?></p>
        </div>

        <!-- 用户协议 -->
        <div class="content-section">
            <h2 class="section-title">📑 <?php echo $t['user_agreement_title']; ?></h2>
            <div class="section-content">
                <p><?php echo $t['user_agreement_intro']; ?></p>
                
                <h2><?php echo $t['usage_scope_title']; ?></h2>
                <ul>
                    <li><?php echo $t['usage_scope_1']; ?></li>
                    <li><?php echo $t['usage_scope_2']; ?></li>
                    <li><?php echo $t['usage_scope_3']; ?></li>
                </ul>

                <h2><?php echo $t['photo_usage_title']; ?></h2>
                <ul>
                    <li><?php echo $t['photo_usage_1']; ?></li>
                    <li><?php echo $t['photo_usage_2']; ?></li>
                    <li><?php echo $t['photo_usage_3']; ?></li>
                    <li><?php echo $t['photo_usage_4']; ?></li>
                </ul>
                
                <div class="warning-box">
                    <strong><?php echo $t['photo_usage_warning']; ?></strong>
                </div>

                <h2><?php echo $t['liability_limitation_title']; ?></h2>
                <ul>
                    <li><?php echo $t['liability_limitation_1']; ?></li>
                    <li><?php echo $t['liability_limitation_2']; ?></li>
                </ul>

                <h2><?php echo $t['agreement_modification_title']; ?></h2>
                <ul>
                    <li><?php echo $t['agreement_modification_1']; ?></li>
                    <li><?php echo $t['agreement_modification_2']; ?></li>
                </ul>
            </div>
        </div>

        <!-- 隐私政策 -->
        <div class="content-section">
            <h2 class="section-title">🔒 <?php echo $t['privacy_policy_title']; ?></h2>
            <div class="section-content">
                <p><?php echo $t['privacy_policy_intro']; ?></p>

                <h2><?php echo $t['data_collection_title']; ?></h2>
                <p><?php echo $t['data_collection_intro']; ?></p>
                <ul>
                    <li><?php echo $t['data_collection_1']; ?></li>
                    <li><?php echo $t['data_collection_2']; ?></li>
                    <li><?php echo $t['data_collection_3']; ?></li>
                </ul>

                <h2><?php echo $t['data_usage_title']; ?></h2>
                <p><?php echo $t['data_usage_intro']; ?></p>
                <ul>
                    <li><?php echo $t['data_usage_1']; ?></li>
                    <li><?php echo $t['data_usage_2']; ?></li>
                    <li><?php echo $t['data_usage_3']; ?></li>
                    <li><?php echo $t['data_usage_4']; ?></li>
                </ul>

                <h2><?php echo $t['data_protection_title']; ?></h2>
                <ul>
                    <li><?php echo $t['data_protection_1']; ?></li>
                    <li><?php echo $t['data_protection_2']; ?></li>
                </ul>

                <h2><?php echo $t['data_sharing_title']; ?></h2>
                <ul>
                    <li><?php echo $t['data_sharing_1']; ?></li>
                    <li><?php echo $t['data_sharing_2']; ?></li>
                </ul>

                <h2><?php echo $t['user_rights_title']; ?></h2>
                <p><?php echo $t['user_rights_intro']; ?></p>
                <ul>
                    <li><?php echo $t['user_rights_1']; ?></li>
                    <li><?php echo $t['user_rights_2']; ?></li>
                    <li><?php echo $t['user_rights_3']; ?></li>
                </ul>
            </div>
        </div>

        <!-- 免责声明 -->
        <div class="content-section">
            <h2 class="section-title">⚖️ <?php echo $t['disclaimer_title']; ?></h2>
            <div class="section-content">
                <ul>
                    <li><?php echo $t['disclaimer_1']; ?></li>
                    <li><?php echo $t['disclaimer_2']; ?></li>
                    <li><?php echo $t['disclaimer_3']; ?></li>
                    <li><?php echo $t['disclaimer_4']; ?></li>
                </ul>
                
                <div class="warning-box">
                    <strong><?php echo $t['disclaimer_warning']; ?></strong>
                </div>
            </div>
        </div>

        <!-- 版权声明 -->
        <div class="content-section">
            <div class="disclaimer-box">
                <h3>©️ <?php echo $t['copyright_declaration_title']; ?></h3>
                <p><?php echo $t['copyright_declaration_content']; ?></p>
                <p><?php echo $t['copyright_declaration_restriction']; ?></p>
                <p><strong><?php echo $t['copyright_declaration_legal']; ?></strong></p>
            </div>
        </div>

        <!-- 联系方式 -->
        <div class="content-section">
            <div class="contact-box">
                <h3><?php echo $t['contact_method_title']; ?></h3>
                <p><?php echo $t['contact_method_text']; ?></p>
                <p>📧 <?php echo $t['contact_method_email']; ?></p>
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
</body>
</html>