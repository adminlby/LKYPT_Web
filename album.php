<?php
// 使用页面保护中间件（包含登录检查和封禁检查）
require_once __DIR__ . '/config/page_protection.php';

// 获取相册和照片数据
$albums = [];
try {
    // 获取所有相册，包含封面照片信息，按创建时间倒序排列
    try {
        $stmt = $pdo->query('
            SELECT a.*, 
                   cp.url as cover_photo_url
            FROM albums a 
            LEFT JOIN photos cp ON a.cover_photo_id = cp.id 
            ORDER BY a.created_at DESC
        ');
        $albums_data = $stmt->fetchAll();
    } catch (PDOException $e) {
        // 如果查询失败，可能是因为外键约束问题，尝试简化查询
        $stmt = $pdo->query('SELECT * FROM albums ORDER BY created_at DESC');
        $albums_data = $stmt->fetchAll();
        // 为每个相册添加空的封面信息
        foreach ($albums_data as &$album_item) {
            $album_item['cover_photo_url'] = null;
        }
    }
    
    // 为每个相册获取对应的照片
    foreach ($albums_data as $album) {
        $album['photos'] = [];
        try {
            $photo_stmt = $pdo->prepare('SELECT * FROM photos WHERE album_id = ? ORDER BY uploaded_at DESC');
            $photo_stmt->execute([$album['id']]);
            $album['photos'] = $photo_stmt->fetchAll();
            
            // 如果没有设置封面照片但有照片，使用第一张照片作为封面
            if (empty($album['cover_photo_url']) && !empty($album['photos'])) {
                $album['cover_photo_url'] = $album['photos'][0]['url'];
            }
        } catch (Exception $e) {
            $album['photos'] = [];
        }
        $albums[] = $album;
    }
} catch (Exception $e) {
    $albums = [];
}
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['album']; ?> | <?php echo $t['team']; ?></title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📷</text></svg>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* 頁面載入動畫 */
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
        
        /* 頁面內容進入動畫 */
        .fade-in {
            opacity: 0;
            animation: none;
        }
        
        .fade-in-up {
            opacity: 0;
            transform: translateY(30px);
            animation: none;
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
        .fade-in.animate {
            animation: fadeIn 0.8s ease forwards;
        }
        
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
        
        @keyframes fadeIn {
            to {
                opacity: 1;
            }
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

        /* 頁面切換動畫 */
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
        
        /* 頁面內容包裝器 */
        .page-content {
            opacity: 0;
            transition: opacity 0.5s ease;
        }
        
        .page-content.show {
            opacity: 1;
        }

        body {
            margin: 0;
            font-family: 'Segoe UI', 'Microsoft JhengHei', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
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
        
        /* 英雄區塊 */
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
            min-height: 300px;
            display: flex;
            flex-direction: column;
            justify-content: center;
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
            margin: 0 auto 30px auto;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
            font-weight: 300;
        }
        
        .main {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 32px;
        }
        
        /* 英雄區樣式 */
        .hero-section {
            height: 30vh;
            background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('assets/images/team-banner.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
            position: relative;
            margin: 20px;
            border-radius: 15px;
            overflow: hidden;
        }
        
        .hero-content {
            max-width: 800px;
            padding: 0 20px;
        }
        
        .hero-title {
            font-size: 2.5em;
            font-weight: 700;
            margin-bottom: 15px;
            background: linear-gradient(45deg, #fff, #f0f0f0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .hero-subtitle {
            font-size: 1.1em;
            font-weight: 300;
            opacity: 0.9;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
        }
        
        /* 主要內容區域 */
        .main {
            padding: 80px 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 50vh;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        /* 空狀態樣式 */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: 0 16px 48px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            max-width: 500px;
            margin: 0 auto;
        }
        
        .empty-state h3 {
            font-size: 1.8em;
            margin-bottom: 15px;
            color: #2c3e50;
        }
        
        .empty-state p {
            color: #666;
            font-size: 1.1em;
        }
        
        /* 時間線樣式 */
        .timeline {
            position: relative;
            margin: 40px 0;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 30px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: linear-gradient(to bottom, #667eea, #764ba2);
        }
        
        .album-item {
            position: relative;
            margin-bottom: 60px;
            padding-left: 80px;
        }
        
        .album-item::before {
            content: '';
            position: absolute;
            left: 21px;
            top: 20px;
            width: 20px;
            height: 20px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            border: 4px solid #fff;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
        
        .album-item:hover::before {
            box-shadow: 0 8px 24px rgba(102, 126, 234, 0.5);
        }
        
        .album-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: 0 16px 48px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .album-item:hover .album-card {
            transform: translateY(-8px);
            box-shadow: 0 24px 64px rgba(0, 0, 0, 0.15);
        }
        
        .album-header {
            padding: 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            position: relative;
        }

        .album-header.with-cover {
            padding: 0;
            height: 200px;
            position: relative;
            overflow: hidden;
        }

        .album-cover-photo {
            width: 100%;
            height: 100%;
            object-fit: cover;
            position: absolute;
            top: 0;
            left: 0;
        }

        .album-header-content {
            position: relative;
            z-index: 2;
            padding: 24px;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.9) 0%, rgba(118, 75, 162, 0.9) 100%);
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
        }

        .album-header.with-cover .album-header-content {
            background: linear-gradient(to top, rgba(102, 126, 234, 0.9) 0%, rgba(118, 75, 162, 0.6) 50%, rgba(0,0,0,0.1) 100%);
        }
        
        .album-title {
            font-size: 1.5em;
            font-weight: bold;
            margin-bottom: 8px;
        }
        
        .album-title a {
            color: white;
            text-decoration: none;
            transition: opacity 0.2s;
        }
        
        .album-title a:hover {
            opacity: 0.8;
        }
        
        .album-description {
            font-size: 1em;
            opacity: 0.9;
            margin-bottom: 12px;
            line-height: 1.4;
        }
        
        .album-date {
            font-size: 0.9em;
            opacity: 0.8;
        }
        
        .album-photos {
            padding: 24px;
        }
        
        .photo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 16px;
        }
        
        .photo-item {
            background: #f8f9fa;
            border-radius: 12px;
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
            border: 1px solid rgba(102, 126, 234, 0.1);
        }
        
        .photo-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(102, 126, 234, 0.15);
        }
        
        .photo-item a {
            display: block;
            text-decoration: none;
            color: inherit;
        }
        
        .photo-img {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }
        
        .photo-info {
            padding: 12px;
        }
        
        .photo-uploader {
            font-size: 0.8em;
            color: #667eea;
            margin-bottom: 4px;
            font-weight: 500;
        }
        
        .photo-date {
            font-size: 0.75em;
            color: #999;
        }
        
        .no-photos {
            text-align: center;
            color: #667eea;
            font-style: italic;
            padding: 40px 20px;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05), rgba(118, 75, 162, 0.05));
            border-radius: 12px;
            border: 1px solid rgba(102, 126, 234, 0.1);
        }
        
        /* 響應式設計 */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2em;
            }
            
            .hero-subtitle {
                font-size: 1em;
            }
            
            .albums-grid {
                grid-template-columns: 1fr;
                gap: 24px;
                margin: 30px 0;
            }
            
            .album-item {
                margin: 0 10px;
            }
            
            .album-cover {
                height: 200px;
            }
            
            .album-info {
                padding: 20px;
            }
            
            .album-title {
                font-size: 1.3em;
            }
            
            .main {
                padding: 60px 0;
            }
            
            .container {
                padding: 0 15px;
            }
            
            .hero-section {
                height: 25vh;
                background-attachment: scroll;
                margin: 10px;
            }
            
            .empty-state {
                padding: 40px 15px;
                margin: 0 15px;
            }
        }
        
        @media (max-width: 480px) {
            .hero-title {
                font-size: 1.8em;
            }
            
            .hero-subtitle {
                font-size: 0.9em;
            }
            
            .album-cover {
                height: 180px;
            }
            
            .album-title {
                font-size: 1.2em;
            }
            
            .album-meta {
                flex-direction: column;
                gap: 8px;
                align-items: flex-start;
            }
            
            .hero-section {
                height: 20vh;
            }
        }
            box-shadow: 0 0 0 2px #667eea;
        }
        
        .album-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .album-header {
            padding: 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            position: relative;
        }

        .album-header.with-cover {
            padding: 0;
            height: 200px;
            position: relative;
            overflow: hidden;
        }

        .album-cover-photo {
            width: 100%;
            height: 100%;
            object-fit: cover;
            position: absolute;
            top: 0;
            left: 0;
        }

        .album-header-content {
            position: relative;
            z-index: 2;
            padding: 24px;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.9) 0%, rgba(118, 75, 162, 0.9) 100%);
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
        }

        .album-header.with-cover .album-header-content {
            background: linear-gradient(to top, rgba(0,0,0,0.8) 0%, rgba(0,0,0,0.4) 50%, rgba(0,0,0,0.1) 100%);
        }
        
        .album-title {
            font-size: 1.5em;
            font-weight: bold;
            margin-bottom: 8px;
        }
        
        .album-title a {
            color: white;
            text-decoration: none;
            transition: opacity 0.2s;
        }
        
        .album-title a:hover {
            opacity: 0.8;
        }
        
        .album-description {
            font-size: 1em;
            opacity: 0.9;
            margin-bottom: 12px;
            line-height: 1.4;
        }
        
        .album-date {
            font-size: 0.9em;
            opacity: 0.8;
        }
        
        .album-photos {
            padding: 24px;
        }
        
        .photo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 16px;
        }
        
        .photo-item {
            background: #f8f9fa;
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .photo-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        }
        
        .photo-item a {
            display: block;
            text-decoration: none;
            color: inherit;
        }
        
        .photo-img {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }
        
        .photo-info {
            padding: 12px;
        }
        
        .photo-uploader {
            font-size: 0.8em;
            color: #666;
            margin-bottom: 4px;
        }
        
        .photo-date {
            font-size: 0.75em;
            color: #999;
        }
        
        .no-photos {
            text-align: center;
            color: #999;
            font-style: italic;
            padding: 40px 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .no-albums {
            text-align: center;
            color: #666;
            font-size: 1.2em;
            margin-top: 80px;
            padding: 40px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: 0 16px 48px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(102, 126, 234, 0.1);
        }
        
        @media (max-width: 768px) {
            .main {
                padding: 0 16px;
            }
            .navbar {
                flex-direction: column;
                height: auto;
                padding: 12px 16px;
            }
            .nav-logo {
                margin-bottom: 8px;
                margin-right: 0;
            }
            .timeline::before {
                left: 15px;
            }
            .album-item {
                padding-left: 50px;
            }
            .album-item::before {
                left: 6px;
                width: 16px;
                height: 16px;
            }
            .photo-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                gap: 12px;
            }
            .photo-img {
                height: 120px;
            }
        }
    </style>
</head>
<body>
    <!-- 页面加载器 -->
    <div class="page-loader">
        <i class="fas fa-camera loader-camera"></i>
        <div class="loader-text">LKYSS Photography Team</div>
    </div>
    
    <!-- 页面内容 -->
    <div class="page-content">
    <nav class="navbar">
        <div class="nav-left">
            <div class="nav-logo"><?php echo $t['team']; ?></div>
            <div class="nav-menu">
                <a href="index.php?lang=<?php echo $current_lang; ?>"><?php echo $t['home']; ?></a>
                <a href="album.php?lang=<?php echo $current_lang; ?>" class="active"><?php echo $t['album']; ?></a>
                <a href="about.php?lang=<?php echo $current_lang; ?>"><?php echo $t['about']; ?></a>
                <a href="help.php?lang=<?php echo $current_lang; ?>"><?php echo $t['help']; ?></a>
            </div>
        </div>
        <div class="nav-actions">
            <?php
            $is_admin = false;
            if (isset($_SESSION['user'])) {
                $user_email = $_SESSION['user']['email'];
                $stmt = $pdo->prepare('SELECT 1 FROM admin_users WHERE email = ? LIMIT 1');
                $stmt->execute([$user_email]);
                $is_admin = $stmt->fetch() ? true : false;
            }
            ?>
            <a class="nav-action-btn" href="logout.php?lang=<?php echo $current_lang; ?>"><?php echo $t['logout']; ?></a>
            <?php if ($is_admin): ?>
                <a class="nav-action-btn" href="/admin/dashboard.php?lang=<?php echo $current_lang; ?>"><?php echo $t['admin_dashboard']; ?></a>
            <?php endif; ?>
            <a class="nav-action-btn" href="?lang=<?php echo $current_lang === 'zh-HK' ? 'en' : 'zh-HK'; ?>"><?php echo $t['lang_switch']; ?></a>
        </div>
    </nav>
    
    <!-- 英雄區 -->
    <section class="hero-section fade-in" style="animation-delay: 0.3s;">
        <div class="hero-content">
            <h1 class="hero-title fade-in-up" style="animation-delay: 0.6s;"><?php echo $t['album_page_title']; ?></h1>
            <p class="hero-subtitle fade-in-up" style="animation-delay: 0.8s;"><?php echo $t['album_page_subtitle']; ?></p>
        </div>
    </section>

    <div class="main fade-in" style="animation-delay: 1.0s;">
        <div class="container">
            <?php if (empty($albums)): ?>
                <div class="no-albums fade-in" style="animation-delay: 1.2s;">
                    <div class="empty-state">
                        <i class="fas fa-camera" style="font-size: 4em; color: #667eea; margin-bottom: 20px;"></i>
                        <h3><?php echo $t['no_albums']; ?></h3>
                        <p><?php echo $t['preparing_content']; ?></p>
                    </div>
                </div>
            <?php else: ?>
                <div class="timeline fade-in" style="animation-delay: 1.2s;">
                    <?php foreach ($albums as $album): ?>
                        <div class="album-item fade-in-up" style="animation-delay: 1.4s;">
                            <div class="album-card">
                                <div class="album-header <?php echo !empty($album['cover_photo_url']) ? 'with-cover' : ''; ?>">
                                    <?php if (!empty($album['cover_photo_url'])): ?>
                                        <img src="<?php echo htmlspecialchars($album['cover_photo_url']); ?>" 
                                             alt="<?php echo htmlspecialchars($album['title']); ?>" 
                                             class="album-cover-photo">
                                    <?php endif; ?>
                                    <div class="album-header-content">
                                        <div class="album-title">
                                            <a href="album-detail.php?id=<?php echo $album['id']; ?>&lang=<?php echo $current_lang; ?>">
                                                <?php echo htmlspecialchars($album['title']); ?>
                                            </a>
                                        </div>
                                        <?php if (!empty($album['description'])): ?>
                                            <div class="album-description"><?php echo htmlspecialchars($album['description']); ?></div>
                                        <?php endif; ?>
                                        <div class="album-date">
                                            <?php echo date($t['date_format'], strtotime($album['created_at'])); ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="album-photos">
                                    <?php if (empty($album['photos'])): ?>
                                        <div class="no-photos"><?php echo $t['no_photos']; ?></div>
                                    <?php else: ?>
                                        <div class="photo-grid">
                                            <?php foreach ($album['photos'] as $photo): ?>
                                                <div class="photo-item">
                                                    <a href="photo-detail.php?id=<?php echo $photo['id']; ?>&lang=<?php echo $current_lang; ?>">
                                                        <img src="<?php echo htmlspecialchars($photo['url']); ?>" alt="Photo" class="photo-img">
                                                    </a>
                                                    <div class="photo-info">
                                                        <?php if (!empty($photo['uploader'])): ?>
                                                            <div class="photo-uploader"><?php echo htmlspecialchars($photo['uploader']); ?></div>
                                                        <?php endif; ?>
                                                        <div class="photo-date"><?php echo date($t['photo_date_format'], strtotime($photo['uploaded_at'])); ?></div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    </div>

    <!-- 包含协议检查组件 -->
    <?php include 'components/agreement_checker.php'; ?>
    
    <script>
        // 頁面載入動畫
        window.addEventListener('load', function() {
            const loader = document.querySelector('.page-loader');
            const content = document.querySelector('.page-content');
            
            if (loader && content) {
                // 隱藏載入器，顯示內容
                setTimeout(() => {
                    loader.style.opacity = '0';
                    content.style.opacity = '1';
                    
                    setTimeout(() => {
                        loader.style.display = 'none';
                        
                        // 觸發動畫
                        const animatedElements = document.querySelectorAll('.fade-in, .fade-in-up, .fade-in-left, .fade-in-right, .fade-in-scale');
                        animatedElements.forEach(el => {
                            el.classList.add('animate');
                        });
                    }, 300);
                }, 1000);
            } else {
                // 如果沒有載入器，直接顯示動畫
                const animatedElements = document.querySelectorAll('.fade-in, .fade-in-up, .fade-in-left, .fade-in-right, .fade-in-scale');
                animatedElements.forEach(el => {
                    el.classList.add('animate');
                });
            }
        });
        
        // 平滑滾動
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
        
        // 頁面切換動畫
        document.addEventListener('DOMContentLoaded', function() {
            const links = document.querySelectorAll('a[href]:not([href^="#"]):not([href^="mailto"]):not([href^="tel"]):not([target="_blank"])');
            
            links.forEach(link => {
                link.addEventListener('click', function(e) {
                    const href = this.getAttribute('href');
                    if (href && !href.startsWith('#') && !href.startsWith('mailto') && !href.startsWith('tel')) {
                        e.preventDefault();
                        
                        // 淡出動畫
                        document.body.style.opacity = '0';
                        document.body.style.transform = 'translateY(20px)';
                        
                        setTimeout(() => {
                            window.location.href = href;
                        }, 300);
                    }
                });
            });
        });
        
        // 相冊項目點擊效果
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.album-item').forEach(item => {
                item.addEventListener('click', function(e) {
                    // 添加點擊動畫效果
                    this.style.transform = 'scale(0.98)';
                    setTimeout(() => {
                        this.style.transform = '';
                    }, 150);
                });
            });
        });
    </script>
</body>
</html>