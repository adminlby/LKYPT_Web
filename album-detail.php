<?php
// 使用页面保护中间件（包含登录检查和封禁检查）
require_once __DIR__ . '/config/page_protection.php';

// 获取相册ID
$album_id = $_GET['id'] ?? null;
if (!$album_id || !is_numeric($album_id)) {
    header('Location: album.php?lang=' . $current_lang);
    exit();
}

// 获取相册详细信息
$album = null;
$photos = [];
$total_photos = 0;

try {
    // 获取相册信息
    $stmt = $pdo->prepare('
        SELECT a.*, 
               cp.url as cover_photo_url,
               cp.id as cover_photo_id
        FROM albums a 
        LEFT JOIN photos cp ON a.cover_photo_id = cp.id 
        WHERE a.id = ?
    ');
    $stmt->execute([$album_id]);
    $album = $stmt->fetch();
    
    if (!$album) {
        header('Location: album.php?lang=' . $current_lang);
        exit();
    }
    
    // 获取相册中的所有照片
    $photo_stmt = $pdo->prepare('SELECT * FROM photos WHERE album_id = ? ORDER BY uploaded_at DESC');
    $photo_stmt->execute([$album_id]);
    $photos = $photo_stmt->fetchAll();
    $total_photos = count($photos);
    
    // 如果没有设置封面照片但有照片，使用第一张照片作为封面
    if (empty($album['cover_photo_url']) && !empty($photos)) {
        $album['cover_photo_url'] = $photos[0]['url'];
        $album['cover_photo_id'] = $photos[0]['id'];
    }
    
    // 记录查看相册的活动日志
    $userActivityLogger->logViewAlbum($user_email, $_SESSION['user']['username'], $album_id, $album['title']);
    
} catch (PDOException $e) {
    header('Location: album.php?lang=' . $current_lang);
    exit();
}

?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($album['title']); ?> - <?php echo $t['album_details'] ?? '相册详情'; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', 'Microsoft JhengHei', Arial, sans-serif;
            background: #f8f9fa;
            line-height: 1.6;
        }
        
        .navbar {
            background: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .navbar .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .navbar-brand {
            font-size: 1.5rem;
            font-weight: bold;
            color: #333;
            text-decoration: none;
        }
        
        .navbar-nav {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        
        .nav-link {
            color: #666;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 4px;
            transition: all 0.3s ease;
        }
        
        .nav-link:hover {
            background: #e9ecef;
            color: #333;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .breadcrumb {
            background: #fff;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .breadcrumb a {
            color: #007bff;
            text-decoration: none;
        }
        
        .breadcrumb a:hover {
            text-decoration: underline;
        }
        
        .album-header {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        .album-cover {
            position: relative;
            height: 300px;
            background: #000;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .cover-image {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        
        .no-cover {
            color: #666;
            font-size: 1.2rem;
        }
        
        .album-info {
            padding: 30px;
        }
        
        .album-title {
            font-size: 2rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        
        .album-description {
            color: #666;
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        
        .album-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .meta-item {
            display: flex;
            flex-direction: column;
        }
        
        .meta-label {
            font-weight: 600;
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 4px;
        }
        
        .meta-value {
            color: #333;
            font-size: 1rem;
        }
        
        .photos-section {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            padding: 30px;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .section-title {
            font-size: 1.5rem;
            font-weight: bold;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .photo-count {
            background: #e9ecef;
            color: #495057;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .view-options {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .view-toggle {
            display: flex;
            background: #e9ecef;
            border-radius: 6px;
            overflow: hidden;
        }
        
        .view-btn {
            padding: 8px 12px;
            border: none;
            background: transparent;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .view-btn.active {
            background: #007bff;
            color: #fff;
        }
        
        .photos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .photos-grid.large {
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        }
        
        .photos-grid.small {
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        }
        
        .photo-card {
            position: relative;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
        }
        
        .photo-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            text-decoration: none;
            color: inherit;
        }
        
        .photo-image {
            width: 100%;
            aspect-ratio: 1;
            object-fit: cover;
        }
        
        .photo-info {
            padding: 12px;
        }
        
        .photo-name {
            font-size: 0.9rem;
            font-weight: 500;
            color: #333;
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .photo-date {
            font-size: 0.8rem;
            color: #666;
        }
        
        .photo-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .photo-card:hover .photo-overlay {
            opacity: 1;
        }
        
        .overlay-content {
            text-align: center;
            color: #fff;
        }
        
        .overlay-icon {
            font-size: 2rem;
            margin-bottom: 8px;
        }
        
        .overlay-text {
            font-size: 0.9rem;
        }
        
        .no-photos {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        
        .no-photos-icon {
            font-size: 4rem;
            margin-bottom: 20px;
        }
        
        .no-photos-text {
            font-size: 1.2rem;
            margin-bottom: 10px;
        }
        
        .no-photos-subtext {
            font-size: 1rem;
            color: #999;
        }
        
        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: #6c757d;
            color: #fff;
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }
        
        .back-button:hover {
            background: #545b62;
            text-decoration: none;
            color: #fff;
        }
        
        .cover-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(255, 193, 7, 0.9);
            color: #333;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .navbar .container {
                padding: 0 15px;
            }
            
            .navbar-nav {
                gap: 10px;
            }
            
            .album-info {
                padding: 20px;
            }
            
            .album-title {
                font-size: 1.5rem;
            }
            
            .album-meta {
                grid-template-columns: 1fr;
            }
            
            .photos-grid {
                grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
                gap: 10px;
            }
            
            .photos-grid.large {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }
            
            .section-header {
                flex-direction: column;
                align-items: stretch;
            }
            
            .view-options {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- 导航栏 -->
    <nav class="navbar">
        <div class="container">
            <a href="index.php?lang=<?php echo $current_lang; ?>" class="navbar-brand">LKYPT</a>
            <div class="navbar-nav">
                <a href="album.php?lang=<?php echo $current_lang; ?>" class="nav-link">
                    <?php echo $t['albums'] ?? '相册'; ?>
                </a>
                <a href="logout.php?lang=<?php echo $current_lang; ?>" class="nav-link">
                    <?php echo $t['logout'] ?? '退出'; ?>
                </a>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- 返回按钮 -->
        <a href="album.php?lang=<?php echo $current_lang; ?>" class="back-button">
            ← <?php echo $t['back_to_albums'] ?? '返回相册列表'; ?>
        </a>

        <!-- 面包屑导航 -->
        <div class="breadcrumb">
            <a href="album.php?lang=<?php echo $current_lang; ?>"><?php echo $t['albums'] ?? '相册'; ?></a>
            / <?php echo htmlspecialchars($album['title']); ?>
        </div>

        <!-- 相册头部信息 -->
        <div class="album-header">
            <div class="album-cover">
                <?php if (!empty($album['cover_photo_url'])): ?>
                    <img src="<?php echo htmlspecialchars($album['cover_photo_url']); ?>" alt="<?php echo htmlspecialchars($album['title']); ?>" class="cover-image">
                <?php else: ?>
                    <div class="no-cover">📁 <?php echo $t['no_cover'] ?? '无封面图片'; ?></div>
                <?php endif; ?>
            </div>
            
            <div class="album-info">
                <h1 class="album-title"><?php echo htmlspecialchars($album['title']); ?></h1>
                
                <?php if (!empty($album['description'])): ?>
                <div class="album-description">
                    <?php echo nl2br(htmlspecialchars($album['description'])); ?>
                </div>
                <?php endif; ?>
                
                <div class="album-meta">
                    <div class="meta-item">
                        <span class="meta-label"><?php echo $t['created_by'] ?? '创建者'; ?></span>
                        <span class="meta-value"><?php echo htmlspecialchars($album['created_by'] ?? 'Unknown'); ?></span>
                    </div>
                    
                    <div class="meta-item">
                        <span class="meta-label"><?php echo $t['created_at'] ?? '创建时间'; ?></span>
                        <span class="meta-value"><?php echo date('Y-m-d H:i:s', strtotime($album['created_at'])); ?></span>
                    </div>
                    
                    <div class="meta-item">
                        <span class="meta-label"><?php echo $t['last_updated'] ?? '最后更新'; ?></span>
                        <span class="meta-value"><?php echo date('Y-m-d H:i:s', strtotime($album['updated_at'])); ?></span>
                    </div>
                    
                    <div class="meta-item">
                        <span class="meta-label"><?php echo $t['total_photos'] ?? '照片总数'; ?></span>
                        <span class="meta-value"><?php echo $total_photos; ?> <?php echo $t['photos'] ?? '张'; ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 照片展示区域 -->
        <div class="photos-section">
            <div class="section-header">
                <div>
                    <h2 class="section-title">
                        🖼️ <?php echo $t['photos_in_album'] ?? '相册中的照片'; ?>
                        <span class="photo-count"><?php echo $total_photos; ?> <?php echo $t['photos'] ?? '张'; ?></span>
                    </h2>
                </div>
                
                <div class="view-options">
                    <div class="view-toggle">
                        <button class="view-btn" onclick="changeView('small')">⚏</button>
                        <button class="view-btn active" onclick="changeView('medium')">⚏⚏</button>
                        <button class="view-btn" onclick="changeView('large')">⚏⚏⚏</button>
                    </div>
                </div>
            </div>

            <?php if (empty($photos)): ?>
                <div class="no-photos">
                    <div class="no-photos-icon">📷</div>
                    <div class="no-photos-text"><?php echo $t['no_photos_in_album'] ?? '此相册中暂无照片'; ?></div>
                    <div class="no-photos-subtext"><?php echo $t['upload_photos_hint'] ?? '您可以上传照片到此相册'; ?></div>
                </div>
            <?php else: ?>
                <div class="photos-grid" id="photosGrid">
                    <?php foreach ($photos as $photo): ?>
                    <a href="photo-detail.php?id=<?php echo $photo['id']; ?>&lang=<?php echo $current_lang; ?>" class="photo-card">
                        <img src="<?php echo htmlspecialchars($photo['url']); ?>" alt="<?php echo htmlspecialchars(basename($photo['url'])); ?>" class="photo-image">
                        
                        <?php if ($album['cover_photo_id'] == $photo['id']): ?>
                        <div class="cover-badge">
                            ⭐ <?php echo $t['cover_photo'] ?? '封面'; ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="photo-overlay">
                            <div class="overlay-content">
                                <div class="overlay-icon">👁️</div>
                                <div class="overlay-text"><?php echo $t['view_photo'] ?? '查看照片'; ?></div>
                            </div>
                        </div>
                        
                        <div class="photo-info">
                            <div class="photo-name"><?php echo htmlspecialchars(basename($photo['url'])); ?></div>
                            <div class="photo-date"><?php echo date('Y-m-d', strtotime($photo['uploaded_at'])); ?></div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function changeView(size) {
            const grid = document.getElementById('photosGrid');
            const buttons = document.querySelectorAll('.view-btn');
            
            // 移除所有按钮的active类
            buttons.forEach(btn => btn.classList.remove('active'));
            
            // 移除所有网格的size类
            grid.classList.remove('small', 'large');
            
            // 添加对应的类和active状态
            if (size === 'small') {
                grid.classList.add('small');
                buttons[0].classList.add('active');
            } else if (size === 'large') {
                grid.classList.add('large');
                buttons[2].classList.add('active');
            } else {
                // medium是默认状态
                buttons[1].classList.add('active');
            }
        }
    </script>
</body>
</html>