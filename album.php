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
            max-width: 1000px;
            margin: 48px auto 0 auto;
            padding: 0 32px;
        }
        .page-title {
            font-size: 2em;
            font-weight: bold;
            color: #2d3e50;
            margin-bottom: 32px;
            text-align: center;
        }
        
        /* 时间线样式 */
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
            background: #e0e0e0;
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
            background: #667eea;
            border-radius: 50%;
            border: 4px solid #fff;
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
            background: #f8f9fa;
            border-radius: 12px;
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
    
    <div class="main">
        <div class="page-title"><?php echo $t['album']; ?></div>
        
        <?php if (empty($albums)): ?>
            <div class="no-albums"><?php echo $t['no_albums']; ?></div>
        <?php else: ?>
            <div class="timeline">
                <?php foreach ($albums as $album): ?>
                    <div class="album-item">
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
                                        <?php echo date('Y年m月d日 H:i', strtotime($album['created_at'])); ?>
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
                                                    <div class="photo-date"><?php echo date('m-d H:i', strtotime($photo['uploaded_at'])); ?></div>
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
</body>
</html>