<?php
// 使用页面保护中间件（包含登录检查和封禁检查）
require_once __DIR__ . '/config/page_protection.php';

// 获取图片ID
$photo_id = $_GET['id'] ?? null;
if (!$photo_id || !is_numeric($photo_id)) {
    header('Location: album.php?lang=' . $current_lang);
    exit();
}

// 获取图片详细信息
$photo = null;
$album = null;
$related_photos = [];

try {
    // 获取图片信息
    $stmt = $pdo->prepare('SELECT * FROM photos WHERE id = ?');
    $stmt->execute([$photo_id]);
    $photo = $stmt->fetch();
    
    if (!$photo) {
        header('Location: album.php?lang=' . $current_lang);
        exit();
    }
    
    // 获取所属相册信息
    if ($photo['album_id']) {
        $album_stmt = $pdo->prepare('SELECT * FROM albums WHERE id = ?');
        $album_stmt->execute([$photo['album_id']]);
        $album = $album_stmt->fetch();
        
        // 获取同相册的其他照片
        $related_stmt = $pdo->prepare('SELECT * FROM photos WHERE album_id = ? AND id != ? ORDER BY uploaded_at DESC LIMIT 12');
        $related_stmt->execute([$photo['album_id'], $photo_id]);
        $related_photos = $related_stmt->fetchAll();
    }
    
    // 记录查看照片的活动日志
    $userActivityLogger->logViewPhoto($user_email, $_SESSION['user']['username'], $photo_id, basename($photo['url']));
    
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
    <title><?php echo basename($photo['url']); ?> - <?php echo $t['photo_details'] ?? '图片详情'; ?></title>
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
        
        .photo-container {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        .photo-display {
            position: relative;
            text-align: center;
            background: #000;
        }
        
        .main-photo {
            max-width: 100%;
            max-height: 80vh;
            width: auto;
            height: auto;
            display: block;
            margin: 0 auto;
        }
        
        .photo-info {
            padding: 25px;
        }
        
        .photo-title {
            font-size: 1.5rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        
        .photo-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
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
        
        .album-section {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 1.3rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .album-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            text-decoration: none;
            color: inherit;
            transition: all 0.3s ease;
            display: block;
        }
        
        .album-card:hover {
            background: #e9ecef;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            text-decoration: none;
            color: inherit;
        }
        
        .album-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        
        .album-description {
            color: #666;
            font-size: 0.9rem;
        }
        
        .related-photos {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            padding: 25px;
        }
        
        .photos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .photo-item {
            position: relative;
            aspect-ratio: 1;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .photo-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.2);
        }
        
        .photo-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .photo-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .photo-item:hover .photo-overlay {
            opacity: 1;
        }
        
        .overlay-icon {
            color: #fff;
            font-size: 1.5rem;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: #007bff;
            color: #fff;
        }
        
        .btn-primary:hover {
            background: #0056b3;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: #fff;
        }
        
        .btn-secondary:hover {
            background: #545b62;
        }
        
        .btn-outline {
            background: transparent;
            color: #007bff;
            border: 2px solid #007bff;
        }
        
        .btn-outline:hover {
            background: #007bff;
            color: #fff;
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
            
            .photo-meta {
                grid-template-columns: 1fr;
            }
            
            .photos-grid {
                grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
                gap: 10px;
            }
            
            .action-buttons {
                flex-direction: column;
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
        <!-- 面包屑导航 -->
        <div class="breadcrumb">
            <a href="album.php?lang=<?php echo $current_lang; ?>"><?php echo $t['albums'] ?? '相册'; ?></a>
            <?php if ($album): ?>
                / <a href="album-detail.php?id=<?php echo $album['id']; ?>&lang=<?php echo $current_lang; ?>"><?php echo htmlspecialchars($album['title']); ?></a>
            <?php endif; ?>
            / <?php echo $t['photo_details'] ?? '图片详情'; ?>
        </div>

        <!-- 图片展示 -->
        <div class="photo-container">
            <div class="photo-display">
                <img src="<?php echo htmlspecialchars($photo['url']); ?>" alt="<?php echo htmlspecialchars(basename($photo['url'])); ?>" class="main-photo">
            </div>
            
            <div class="photo-info">
                <h1 class="photo-title"><?php echo htmlspecialchars(basename($photo['url'])); ?></h1>
                
                <div class="photo-meta">
                    <div class="meta-item">
                        <span class="meta-label"><?php echo $t['uploader'] ?? '上传者'; ?></span>
                        <span class="meta-value"><?php echo htmlspecialchars($photo['uploader'] ?? 'Unknown'); ?></span>
                    </div>
                    
                    <div class="meta-item">
                        <span class="meta-label"><?php echo $t['uploaded_at'] ?? '上传时间'; ?></span>
                        <span class="meta-value"><?php echo date('Y-m-d H:i:s', strtotime($photo['uploaded_at'])); ?></span>
                    </div>
                    
                    <?php if ($album): ?>
                    <div class="meta-item">
                        <span class="meta-label"><?php echo $t['album'] ?? '所属相册'; ?></span>
                        <span class="meta-value">
                            <a href="album-detail.php?id=<?php echo $album['id']; ?>&lang=<?php echo $current_lang; ?>" style="color: #007bff; text-decoration: none;">
                                <?php echo htmlspecialchars($album['title']); ?>
                            </a>
                        </span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="meta-item">
                        <span class="meta-label"><?php echo $t['file_url'] ?? '文件链接'; ?></span>
                        <span class="meta-value" style="word-break: break-all; font-family: monospace; font-size: 0.8rem;">
                            <?php echo htmlspecialchars($photo['url']); ?>
                        </span>
                    </div>
                </div>
                
                <div class="action-buttons">
                    <a href="<?php echo htmlspecialchars($photo['url']); ?>" target="_blank" class="btn btn-primary">
                        🔍 <?php echo $t['view_original'] ?? '查看原图'; ?>
                    </a>
                    <a href="download-photo.php?id=<?php echo $photo['id']; ?>&lang=<?php echo $current_lang; ?>" class="btn btn-secondary">
                        📥 <?php echo $t['download'] ?? '下载图片'; ?>
                    </a>
                    <?php if ($album): ?>
                    <a href="album-detail.php?id=<?php echo $album['id']; ?>&lang=<?php echo $current_lang; ?>" class="btn btn-outline">
                        📁 <?php echo $t['view_album'] ?? '查看相册'; ?>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- 所属相册信息 -->
        <?php if ($album): ?>
        <div class="album-section">
            <h2 class="section-title">
                📁 <?php echo $t['album_info'] ?? '相册信息'; ?>
            </h2>
            <a href="album-detail.php?id=<?php echo $album['id']; ?>&lang=<?php echo $current_lang; ?>" class="album-card">
                <div class="album-title"><?php echo htmlspecialchars($album['title']); ?></div>
                <?php if (!empty($album['description'])): ?>
                <div class="album-description"><?php echo htmlspecialchars($album['description']); ?></div>
                <?php endif; ?>
                <div style="margin-top: 10px; color: #666; font-size: 0.8rem;">
                    <?php echo $t['created_at'] ?? '创建时间'; ?>: <?php echo date('Y-m-d', strtotime($album['created_at'])); ?>
                </div>
            </a>
        </div>
        <?php endif; ?>

        <!-- 相关照片 -->
        <?php if (!empty($related_photos)): ?>
        <div class="related-photos">
            <h2 class="section-title">
                🖼️ <?php echo $t['related_photos'] ?? '相关照片'; ?>
                (<?php echo count($related_photos); ?> <?php echo $t['photos'] ?? '张'; ?>)
            </h2>
            
            <div class="photos-grid">
                <?php foreach ($related_photos as $related_photo): ?>
                <a href="photo-detail.php?id=<?php echo $related_photo['id']; ?>&lang=<?php echo $current_lang; ?>" class="photo-item">
                    <img src="<?php echo htmlspecialchars($related_photo['url']); ?>" alt="<?php echo htmlspecialchars(basename($related_photo['url'])); ?>">
                    <div class="photo-overlay">
                        <span class="overlay-icon">👁️</span>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>