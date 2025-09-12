<?php
session_start();
require_once '../config/config.php';
require_once '../config/lang.php';
require_once '../config/OperationLogger.php';

// 检查用户是否登录
if (!isset($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}

// 语言处理
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

// 检查管理员权限
$user_email = $_SESSION['user']['email'];
$stmt = $pdo->prepare('SELECT 1 FROM admin_users WHERE email = ? LIMIT 1');
$stmt->execute([$user_email]);
$is_admin = $stmt->fetch() ? true : false;
if (!$is_admin) {
    $error_message = $t['access_denied'];
    header("Location: ../index.php?error=" . urlencode($error_message));
    exit();
}

// 初始化操作日志记录器
$logger = new OperationLogger($pdo);
$current_user = $_SESSION['user']['email'];
$current_username = $_SESSION['user']['username'] ?? $_SESSION['user']['email'];

// 处理表单提交
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'upload':
                $album_id = $_POST['album_id'] ?? '';
                $new_album_title = trim($_POST['new_album_title'] ?? '');
                $new_album_description = trim($_POST['new_album_description'] ?? '');
                
                // 检查是否需要创建新相册
                if (empty($album_id) && !empty($new_album_title)) {
                    try {
                        // 创建新相册
                        $stmt = $pdo->prepare("INSERT INTO albums (title, description, created_at) VALUES (?, ?, NOW())");
                        $stmt->execute([$new_album_title, $new_album_description]);
                        $album_id = $pdo->lastInsertId();
                        
                        // 记录创建相册的操作日志
                        $logger->logAlbumOperation(
                            $current_user, 
                            $current_username, 
                            'create', 
                            $album_id, 
                            $new_album_title, 
                            '通过照片上传页面创建了新相册' . ($new_album_description ? "，描述：{$new_album_description}" : ''),
                            null,
                            ['title' => $new_album_title, 'description' => $new_album_description]
                        );
                    } catch (PDOException $e) {
                        $message = $t['upload_error'] . ': ' . $e->getMessage();
                        $message_type = 'error';
                        break;
                    }
                } elseif (empty($album_id)) {
                    $message = $t['upload_error'] . ': ' . $t['no_album_selected'];
                    $message_type = 'error';
                    break;
                }
                
                // 处理文件上传
                if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                    $file = $_FILES['photo'];
                    
                    // 验证文件类型
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                    $file_type = mime_content_type($file['tmp_name']);
                    
                    if (!in_array($file_type, $allowed_types)) {
                        $message = $t['invalid_file_type'];
                        $message_type = 'error';
                        break;
                    }
                    
                    // 验证文件大小 (20MB)
                    if ($file['size'] > 20 * 1024 * 1024) {
                        $message = $t['file_too_large'];
                        $message_type = 'error';
                        break;
                    }
                    
                    // 生成唯一文件名
                    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $filename = date('Y-m-d_H-i-s') . '_' . uniqid() . '.' . $ext;
                    $upload_path = '../uploads/' . $filename;
                    $url = '/uploads/' . $filename;
                    
                    // 移动上传的文件
                    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                        try {
                            // 保存到数据库
                            $stmt = $pdo->prepare("INSERT INTO photos (url, uploaded_at, uploader, album_id) VALUES (?, NOW(), ?, ?)");
                            $stmt->execute([$url, $current_username, $album_id]);
                            $photo_id = $pdo->lastInsertId();
                            
                            // 获取相册信息用于日志
                            $stmt = $pdo->prepare("SELECT title FROM albums WHERE id = ?");
                            $stmt->execute([$album_id]);
                            $album_info = $stmt->fetch();
                            $album_title = $album_info ? $album_info['title'] : '未知相册';
                            
                            // 记录照片上传的操作日志
                            $logger->logPhotoOperation(
                                $current_user, 
                                $current_username, 
                                'create', 
                                $photo_id, 
                                $filename, 
                                "上传照片到相册「{$album_title}」",
                                null,
                                ['url' => $url, 'album_id' => $album_id, 'filename' => $filename]
                            );
                            
                            $message = !empty($new_album_title) ? $t['album_created_with_photo'] : $t['photo_uploaded'];
                            $message_type = 'success';
                        } catch (PDOException $e) {
                            // 删除已上传的文件
                            unlink($upload_path);
                            
                            // 记录失败日志
                            $logger->logPhotoOperation(
                                $current_user, 
                                $current_username, 
                                'create', 
                                null, 
                                $filename, 
                                '照片上传失败', 
                                null, 
                                null, 
                                'failed', 
                                $e->getMessage()
                            );
                            
                            $message = $t['upload_error'] . ': ' . $e->getMessage();
                            $message_type = 'error';
                        }
                    } else {
                        $message = $t['upload_error'] . ': Failed to move uploaded file';
                        $message_type = 'error';
                    }
                } else {
                    $upload_error = $_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE;
                    $error_messages = [
                        UPLOAD_ERR_NO_FILE => 'No file uploaded',
                        UPLOAD_ERR_INI_SIZE => 'File too large (server limit)',
                        UPLOAD_ERR_FORM_SIZE => 'File too large (form limit)',
                        UPLOAD_ERR_PARTIAL => 'File partially uploaded',
                        UPLOAD_ERR_NO_TMP_DIR => 'No temporary directory',
                        UPLOAD_ERR_CANT_WRITE => 'Cannot write file',
                        UPLOAD_ERR_EXTENSION => 'Upload stopped by extension'
                    ];
                    $message = $t['upload_error'] . ': ' . ($error_messages[$upload_error] ?? 'Unknown error');
                    $message_type = 'error';
                }
                break;
                
            case 'set_cover':
                $photo_id = $_POST['photo_id'] ?? '';
                $album_id = $_POST['album_id'] ?? '';
                
                if (!empty($photo_id) && !empty($album_id)) {
                    try {
                        // 获取照片信息
                        $stmt = $pdo->prepare("SELECT * FROM photos WHERE id = ? AND album_id = ?");
                        $stmt->execute([$photo_id, $album_id]);
                        $photo = $stmt->fetch();
                        
                        if ($photo) {
                            // 获取相册信息
                            $stmt = $pdo->prepare("SELECT * FROM albums WHERE id = ?");
                            $stmt->execute([$album_id]);
                            $album = $stmt->fetch();
                            
                            if ($album) {
                                // 更新相册的封面照片
                                $stmt = $pdo->prepare("UPDATE albums SET cover_photo_id = ? WHERE id = ?");
                                $stmt->execute([$photo_id, $album_id]);
                                
                                // 记录操作日志
                                $photo_filename = basename($photo['url']);
                                $logger->logPhotoOperation(
                                    $current_user, 
                                    $current_username, 
                                    'set_cover', 
                                    $photo_id, 
                                    $photo_filename, 
                                    "将照片「{$photo_filename}」设置为相册「{$album['title']}」的封面",
                                    $photo,
                                    null
                                );
                                
                                $message = "已将照片设置为相册封面";
                                $message_type = 'success';
                            } else {
                                $message = "相册不存在";
                                $message_type = 'error';
                            }
                        } else {
                            $message = "照片不存在或不属于指定相册";
                            $message_type = 'error';
                        }
                    } catch (PDOException $e) {
                        $message = "设置封面失败: " . $e->getMessage();
                        $message_type = 'error';
                    }
                }
                break;
                
            case 'delete':
                $photo_id = $_POST['photo_id'] ?? '';
                if (!empty($photo_id)) {
                    try {
                        // 获取照片信息
                        $stmt = $pdo->prepare("SELECT * FROM photos WHERE id = ?");
                        $stmt->execute([$photo_id]);
                        $photo = $stmt->fetch();
                        
                        if ($photo) {
                            // 删除文件
                            $file_path = '../' . ltrim($photo['url'], '/');
                            if (file_exists($file_path)) {
                                unlink($file_path);
                            }
                            
                            // 从数据库删除
                            $stmt = $pdo->prepare("DELETE FROM photos WHERE id = ?");
                            $stmt->execute([$photo_id]);
                            
                            // 记录删除操作日志
                            $logger->logPhotoOperation(
                                $current_user, 
                                $current_username, 
                                'delete', 
                                $photo_id, 
                                basename($photo['url']), 
                                '删除了照片',
                                $photo,
                                null
                            );
                            
                            $message = $t['photo_deleted'];
                            $message_type = 'success';
                        } else {
                            $message = 'Photo not found';
                            $message_type = 'error';
                        }
                    } catch (PDOException $e) {
                        // 记录失败日志
                        $logger->logPhotoOperation(
                            $current_user, 
                            $current_username, 
                            'delete', 
                            $photo_id, 
                            '未知照片', 
                            '删除照片失败', 
                            null, 
                            null, 
                            'failed', 
                            $e->getMessage()
                        );
                        
                        $message = 'Error: ' . $e->getMessage();
                        $message_type = 'error';
                    }
                }
                break;
        }
    }
}

// 记录访问照片管理页面的操作
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $logger->logSystemOperation($current_user, $current_username, 'view', '访问照片管理页面');
}

// 获取所有相册列表（用于下拉菜单）
$albums = [];
try {
    $stmt = $pdo->query("SELECT id, title FROM albums ORDER BY created_at DESC");
    $albums = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $albums = [];
}

// 分页和筛选参数
$page = max(1, $_GET['page'] ?? 1);
$per_page = max(10, min(100, $_GET['per_page'] ?? 20));
$search = $_GET['search'] ?? '';
$album_filter = $_GET['album_filter'] ?? '';
$offset = ($page - 1) * $per_page;

// 构建搜索查询
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(p.url LIKE ? OR p.uploader LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($album_filter)) {
    $where_conditions[] = "p.album_id = ?";
    $params[] = $album_filter;
}

$where_clause = empty($where_conditions) ? '' : 'WHERE ' . implode(' AND ', $where_conditions);

// 获取总记录数
$count_sql = "SELECT COUNT(*) FROM photos p $where_clause";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $per_page);

// 获取照片列表
$sql = "SELECT p.*, a.title as album_title 
        FROM photos p 
        LEFT JOIN albums a ON p.album_id = a.id 
        $where_clause 
        ORDER BY p.uploaded_at DESC 
        LIMIT $per_page OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$photos = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['photo_management']; ?> - <?php echo $t['admin_dashboard']; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }

        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .admin-header {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .admin-header h1 {
            color: #333;
            margin-bottom: 10px;
        }

        .breadcrumb {
            color: #666;
            font-size: 14px;
        }

        .breadcrumb a {
            color: #667eea;
            text-decoration: none;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        .content-card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .message {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .upload-form {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .mode-selector {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            justify-content: center;
        }

        .mode-btn {
            padding: 8px 16px;
            border: 2px solid #667eea;
            border-radius: 6px;
            background: white;
            color: #667eea;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .mode-btn.active {
            background: #667eea;
            color: white;
        }

        .mode-btn:hover {
            background: #5a6fd8;
            color: white;
        }

        .upload-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .batch-upload-section {
            display: none;
        }

        .batch-upload-section.active {
            display: block;
        }

        .progress-container {
            margin-top: 20px;
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 6px;
            background: white;
        }

        .progress-item {
            padding: 10px 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .progress-item:last-child {
            border-bottom: none;
        }

        .progress-filename {
            flex: 1;
            font-weight: 500;
            word-break: break-all;
        }

        .progress-bar {
            width: 100px;
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: #667eea;
            transition: width 0.3s ease;
            width: 0%;
        }

        .progress-status {
            min-width: 80px;
            text-align: center;
            font-size: 12px;
            font-weight: 500;
        }

        .status-waiting {
            color: #6c757d;
        }

        .status-uploading {
            color: #007bff;
        }

        .status-success {
            color: #28a745;
        }

        .status-error {
            color: #dc3545;
        }

        .upload-summary {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 6px;
            margin-top: 15px;
            text-align: center;
            font-weight: 500;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 60px;
            font-family: inherit;
        }

        .file-input {
            position: relative;
            display: inline-block;
            cursor: pointer;
            width: 100%;
        }

        .file-input input[type=file] {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .file-input-label {
            display: block;
            padding: 10px;
            border: 2px dashed #ddd;
            border-radius: 6px;
            text-align: center;
            background: #fafafa;
            transition: all 0.3s ease;
        }

        .file-input:hover .file-input-label {
            border-color: #667eea;
            background: #f0f0ff;
        }

        .new-album-section {
            border-top: 1px solid #ddd;
            padding-top: 20px;
            margin-top: 20px;
        }

        .or-divider {
            text-align: center;
            margin: 15px 0;
            color: #666;
            font-weight: 500;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5a6fd8;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }

        .search-controls {
            display: flex;
            gap: 15px;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .search-input {
            flex: 1;
            min-width: 250px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
        }

        .filter-select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            min-width: 150px;
        }

        .per-page-select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 6px;
        }

        .photos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .photo-card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease;
        }

        .photo-card:hover {
            transform: translateY(-2px);
        }

        .photo-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            cursor: pointer;
        }

        .photo-info {
            padding: 15px;
        }

        .photo-title {
            font-weight: 600;
            margin-bottom: 8px;
            word-break: break-all;
        }

        .photo-meta {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }

        .photo-album {
            background: #e3f2fd;
            color: #1976d2;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 11px;
            margin-bottom: 10px;
            display: inline-block;
        }

        .photo-actions {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
        }

        .pagination a,
        .pagination span {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: #333;
        }

        .pagination a:hover {
            background: #f8f9fa;
        }

        .pagination .current {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .pagination-info {
            text-align: center;
            color: #666;
            margin-bottom: 10px;
        }

        .no-data {
            text-align: center;
            color: #666;
            padding: 60px 20px;
            font-style: italic;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.8);
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 600px;
            max-height: 80%;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-header h2 {
            margin: 0;
        }

        .close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: #000;
        }

        .modal-image {
            width: 100%;
            max-height: 400px;
            object-fit: contain;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .toggle-section {
            background: #e9ecef;
            padding: 10px 15px;
            border-radius: 6px;
            cursor: pointer;
            margin-bottom: 15px;
            user-select: none;
        }

        .toggle-section:hover {
            background: #dee2e6;
        }

        .toggle-content {
            display: none;
            margin-top: 15px;
        }

        .toggle-content.active {
            display: block;
        }

        @media (max-width: 768px) {
            .admin-container {
                padding: 10px;
            }

            .upload-section {
                grid-template-columns: 1fr;
            }

            .search-controls {
                flex-direction: column;
                align-items: stretch;
            }

            .search-input {
                min-width: auto;
            }

            .photos-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 15px;
            }

            .photo-actions {
                flex-direction: column;
            }

            .btn-sm {
                padding: 8px 12px;
                font-size: 12px;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="admin-container">
        <div class="admin-header">
            <h1><?php echo $t['photo_management']; ?></h1>
            <div class="breadcrumb">
                <a href="dashboard.php?lang=<?php echo $current_lang; ?>"><?php echo $t['admin_dashboard']; ?></a> / <?php echo $t['photo_management']; ?>
            </div>
        </div>

        <div class="content-card">
            <?php if ($message): ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- 上传照片表单 -->
            <div class="upload-form">
                <h3><?php echo $t['upload_photo']; ?></h3>
                
                <!-- 上传模式选择器 -->
                <div class="mode-selector">
                    <button class="mode-btn active" onclick="switchMode('single')" id="single-mode-btn">
                        <?php echo $t['single_upload']; ?>
                    </button>
                    <button class="mode-btn" onclick="switchMode('batch')" id="batch-mode-btn">
                        <?php echo $t['batch_upload']; ?>
                    </button>
                </div>

                <!-- 单张上传模式 -->
                <div id="single-upload-section">
                    <form method="POST" action="" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="upload">
                        
                        <div class="upload-section">
                            <div>
                                <div class="form-group">
                                    <label><?php echo $t['choose_file']; ?></label>
                                    <div class="file-input">
                                        <input type="file" name="photo" accept="image/*" required onchange="showFileName(this)">
                                        <div class="file-input-label" id="file-label">
                                            📷 <?php echo $t['choose_file']; ?> (JPEG, PNG, GIF, WebP, Max 20MB)
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div>
                                <div class="form-group">
                                    <label for="album_id"><?php echo $t['select_album']; ?></label>
                                    <select id="album_id" name="album_id" onchange="toggleNewAlbumSection()">
                                        <option value=""><?php echo $t['no_album_selected']; ?></option>
                                        <?php foreach ($albums as $album): ?>
                                            <option value="<?php echo $album['id']; ?>">
                                                <?php echo htmlspecialchars($album['title']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="toggle-section" onclick="toggleNewAlbum()">
                                    ➕ <?php echo $t['or_create_new']; ?>
                                </div>
                                
                                <div class="toggle-content" id="new-album-section">
                                    <div class="form-group">
                                        <label for="new_album_title"><?php echo $t['new_album_title']; ?></label>
                                        <input type="text" id="new_album_title" name="new_album_title" 
                                               placeholder="<?php echo $t['new_album_title']; ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="new_album_description"><?php echo $t['new_album_description']; ?></label>
                                        <textarea id="new_album_description" name="new_album_description" 
                                                  placeholder="<?php echo $t['album_description']; ?>" rows="3"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-success"><?php echo $t['upload']; ?></button>
                    </form>
                </div>

                <!-- 批量上传模式 -->
                <div id="batch-upload-section" class="batch-upload-section">
                    <div class="upload-section">
                        <div>
                            <div class="form-group">
                                <label><?php echo $t['select_multiple_files']; ?></label>
                                <div class="file-input">
                                    <input type="file" id="batch-files" accept="image/*" multiple onchange="showBatchFiles(this)">
                                    <div class="file-input-label" id="batch-file-label">
                                        📷 <?php echo $t['select_multiple_files']; ?> (JPEG, PNG, GIF, WebP, Max 20MB each)
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <div class="form-group">
                                <label for="batch_album_id"><?php echo $t['select_album']; ?></label>
                                <select id="batch_album_id">
                                    <option value=""><?php echo $t['no_album_selected']; ?></option>
                                    <?php foreach ($albums as $album): ?>
                                        <option value="<?php echo $album['id']; ?>">
                                            <?php echo htmlspecialchars($album['title']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="toggle-section" onclick="toggleBatchNewAlbum()">
                                ➕ <?php echo $t['or_create_new']; ?>
                            </div>
                            
                            <div class="toggle-content" id="batch-new-album-section">
                                <div class="form-group">
                                    <label for="batch_new_album_title"><?php echo $t['new_album_title']; ?></label>
                                    <input type="text" id="batch_new_album_title" 
                                           placeholder="<?php echo $t['new_album_title']; ?>">
                                </div>
                                <div class="form-group">
                                    <label for="batch_new_album_description"><?php echo $t['new_album_description']; ?></label>
                                    <textarea id="batch_new_album_description" 
                                              placeholder="<?php echo $t['album_description']; ?>" rows="3"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <button type="button" onclick="startBatchUpload()" class="btn btn-success" id="batch-upload-btn" disabled>
                        <?php echo $t['upload']; ?>
                    </button>
                    
                    <!-- 上传进度 -->
                    <div id="progress-container" class="progress-container" style="display: none;">
                        <h4><?php echo $t['upload_progress']; ?></h4>
                        <div id="progress-list"></div>
                        <div id="upload-summary" style="display: none;"></div>
                    </div>
                </div>
            </div>

            <!-- 搜索和筛选 -->
            <div class="search-controls">
                <form method="GET" action="" style="display: flex; gap: 15px; align-items: center; flex: 1; flex-wrap: wrap;">
                    <?php if (isset($_GET['lang'])): ?>
                        <input type="hidden" name="lang" value="<?php echo htmlspecialchars($_GET['lang']); ?>">
                    <?php endif; ?>
                    
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="<?php echo $t['search']; ?>..." class="search-input">
                    
                    <select name="album_filter" class="filter-select" onchange="this.form.submit()">
                        <option value=""><?php echo $t['all_albums']; ?></option>
                        <?php foreach ($albums as $album): ?>
                            <option value="<?php echo $album['id']; ?>" <?php echo $album_filter == $album['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($album['title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <select name="per_page" class="per-page-select" onchange="this.form.submit()">
                        <option value="20" <?php echo $per_page == 20 ? 'selected' : ''; ?>>20 <?php echo $t['results']; ?></option>
                        <option value="50" <?php echo $per_page == 50 ? 'selected' : ''; ?>>50 <?php echo $t['results']; ?></option>
                        <option value="100" <?php echo $per_page == 100 ? 'selected' : ''; ?>>100 <?php echo $t['results']; ?></option>
                    </select>
                    
                    <button type="submit" class="btn btn-primary"><?php echo $t['search']; ?></button>
                    
                    <?php if (!empty($search) || !empty($album_filter)): ?>
                        <a href="photos.php<?php echo isset($_GET['lang']) ? '?lang=' . htmlspecialchars($_GET['lang']) : ''; ?>" 
                           class="btn btn-secondary"><?php echo $t['reset']; ?></a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- 分页信息 -->
            <?php if ($total_records > 0): ?>
                <div class="pagination-info">
                    <?php 
                    $start = $offset + 1;
                    $end = min($offset + $per_page, $total_records);
                    echo sprintf($t['showing_results'], $start, $end, $total_records);
                    ?>
                </div>
            <?php endif; ?>

            <!-- 照片网格 -->
            <?php if (count($photos) > 0): ?>
                <div class="photos-grid">
                    <?php foreach ($photos as $photo): ?>
                        <div class="photo-card">
                            <img src="<?php echo htmlspecialchars($photo['url']); ?>" 
                                 alt="Photo" class="photo-image" 
                                 onclick="viewPhoto('<?php echo addslashes($photo['url']); ?>', '<?php echo addslashes(basename($photo['url'])); ?>', '<?php echo addslashes($photo['uploader']); ?>', '<?php echo date('Y-m-d H:i:s', strtotime($photo['uploaded_at'])); ?>', '<?php echo addslashes($photo['album_title'] ?? $t['no_album_selected']); ?>')">
                            <div class="photo-info">
                                <div class="photo-title"><?php echo htmlspecialchars(basename($photo['url'])); ?></div>
                                
                                <?php if ($photo['album_title']): ?>
                                    <div class="photo-album"><?php echo htmlspecialchars($photo['album_title']); ?></div>
                                <?php endif; ?>
                                
                                <div class="photo-meta">
                                    <strong><?php echo $t['uploader']; ?>:</strong> <?php echo htmlspecialchars($photo['uploader']); ?>
                                </div>
                                <div class="photo-meta">
                                    <strong><?php echo $t['uploaded_at']; ?>:</strong> <?php echo date('Y-m-d H:i', strtotime($photo['uploaded_at'])); ?>
                                </div>
                                
                                <div class="photo-actions">
                                    <button onclick="copyUrl('<?php echo htmlspecialchars($photo['url']); ?>')" 
                                            class="btn btn-primary btn-sm">📋 URL</button>
                                    <?php if (!empty($photo['album_id'])): ?>
                                        <button onclick="setCover(<?php echo $photo['id']; ?>, <?php echo $photo['album_id']; ?>, '<?php echo addslashes(basename($photo['url'])); ?>')" 
                                                class="btn btn-success btn-sm">🖼️ <?php echo $t['set_as_cover']; ?></button>
                                    <?php endif; ?>
                                    <button onclick="deletePhoto(<?php echo $photo['id']; ?>, '<?php echo addslashes(basename($photo['url'])); ?>')" 
                                            class="btn btn-danger btn-sm"><?php echo $t['delete']; ?></button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- 分页导航 -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php
                        $url_params = ['per_page' => $per_page];
                        if (!empty($search)) $url_params['search'] = $search;
                        if (!empty($album_filter)) $url_params['album_filter'] = $album_filter;
                        if (isset($_GET['lang'])) $url_params['lang'] = $_GET['lang'];
                        $base_url = 'photos.php?' . http_build_query($url_params) . '&page=';
                        ?>

                        <?php if ($page > 1): ?>
                            <a href="<?php echo $base_url . ($page-1); ?>"><?php echo $t['prev']; ?></a>
                        <?php endif; ?>

                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        if ($start_page > 1) {
                            echo '<a href="' . $base_url . '1">1</a>';
                            if ($start_page > 2) {
                                echo '<span>...</span>';
                            }
                        }
                        
                        for ($i = $start_page; $i <= $end_page; $i++) {
                            if ($i == $page) {
                                echo '<span class="current">' . $i . '</span>';
                            } else {
                                echo '<a href="' . $base_url . $i . '">' . $i . '</a>';
                            }
                        }
                        
                        if ($end_page < $total_pages) {
                            if ($end_page < $total_pages - 1) {
                                echo '<span>...</span>';
                            }
                            echo '<a href="' . $base_url . $total_pages . '">' . $total_pages . '</a>';
                        }
                        ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="<?php echo $base_url . ($page+1); ?>"><?php echo $t['next']; ?></a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="no-data">
                    <?php echo $t['no_photos_found']; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 查看照片模态框 -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modal-title"></h2>
                <span class="close" onclick="closeViewModal()">&times;</span>
            </div>
            <img id="modal-image" class="modal-image" src="" alt="Photo">
            <div id="modal-info"></div>
        </div>
    </div>

    <!-- 删除确认模态框 -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><?php echo $t['delete']; ?></h2>
                <span class="close" onclick="closeDeleteModal()">&times;</span>
            </div>
            <p><?php echo $t['confirm_delete_photo']; ?></p>
            <p><strong id="delete_photo_name"></strong></p>
            <form method="POST" action="">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="photo_id" id="delete_photo_id">
                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" onclick="closeDeleteModal()" class="btn"><?php echo $t['cancel']; ?></button>
                    <button type="submit" class="btn btn-danger"><?php echo $t['delete']; ?></button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showFileName(input) {
            const label = document.getElementById('file-label');
            if (input.files && input.files[0]) {
                label.textContent = '📷 ' + input.files[0].name;
            } else {
                label.textContent = '📷 <?php echo $t['choose_file']; ?> (JPEG, PNG, GIF, WebP, Max 20MB)';
            }
        }

        // 切换上传模式
        function switchMode(mode) {
            const singleSection = document.getElementById('single-upload-section');
            const batchSection = document.getElementById('batch-upload-section');
            const singleBtn = document.getElementById('single-mode-btn');
            const batchBtn = document.getElementById('batch-mode-btn');
            
            if (mode === 'single') {
                singleSection.style.display = 'block';
                batchSection.style.display = 'none';
                singleBtn.classList.add('active');
                batchBtn.classList.remove('active');
            } else {
                singleSection.style.display = 'none';
                batchSection.style.display = 'block';
                batchBtn.classList.add('active');
                singleBtn.classList.remove('active');
            }
        }

        // 显示批量选择的文件
        function showBatchFiles(input) {
            const label = document.getElementById('batch-file-label');
            const uploadBtn = document.getElementById('batch-upload-btn');
            
            if (input.files && input.files.length > 0) {
                label.textContent = `📷 已选择 ${input.files.length} 个文件`;
                uploadBtn.disabled = false;
            } else {
                label.textContent = '📷 <?php echo $t['select_multiple_files']; ?> (JPEG, PNG, GIF, WebP, Max 20MB each)';
                uploadBtn.disabled = true;
            }
        }

        // 开始批量上传
        async function startBatchUpload() {
            const filesInput = document.getElementById('batch-files');
            const albumId = document.getElementById('batch_album_id').value;
            const newAlbumTitle = document.getElementById('batch_new_album_title').value;
            const newAlbumDescription = document.getElementById('batch_new_album_description').value;
            const progressContainer = document.getElementById('progress-container');
            const progressList = document.getElementById('progress-list');
            const uploadBtn = document.getElementById('batch-upload-btn');
            
            if (!filesInput.files || filesInput.files.length === 0) {
                alert('请先选择要上传的文件');
                return;
            }
            
            // 禁用上传按钮
            uploadBtn.disabled = true;
            uploadBtn.textContent = '上传中...';
            
            // 显示进度容器
            progressContainer.style.display = 'block';
            progressList.innerHTML = '';
            
            const files = Array.from(filesInput.files);
            let uploadedCount = 0;
            let failedCount = 0;
            let targetAlbumId = albumId;
            
            // 如果需要创建新相册，先创建相册
            if (!albumId && newAlbumTitle) {
                try {
                    // 创建一个临时文件用于创建相册
                    const canvas = document.createElement('canvas');
                    canvas.width = 1;
                    canvas.height = 1;
                    const ctx = canvas.getContext('2d');
                    ctx.fillStyle = '#ffffff';
                    ctx.fillRect(0, 0, 1, 1);
                    
                    const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/png'));
                    
                    const formData = new FormData();
                    formData.append('action', 'upload');
                    formData.append('photo', blob, 'temp.png');
                    formData.append('new_album_title', newAlbumTitle);
                    formData.append('new_album_description', newAlbumDescription);
                    
                    const response = await fetch('upload-ajax.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    if (result.success && result.album_id) {
                        targetAlbumId = result.album_id;
                        // 删除临时文件
                        if (result.photo_id) {
                            const deleteFormData = new FormData();
                            deleteFormData.append('action', 'delete');
                            deleteFormData.append('photo_id', result.photo_id);
                            fetch('', { method: 'POST', body: deleteFormData });
                        }
                    } else {
                        alert('创建新相册失败: ' + (result.error || '未知错误'));
                        resetUploadForm();
                        return;
                    }
                } catch (error) {
                    alert('创建新相册失败: ' + error.message);
                    resetUploadForm();
                    return;
                }
            }
            
            // 开始上传文件
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                const progressItem = createProgressItem(file.name, i);
                progressList.appendChild(progressItem);
                
                try {
                    const result = await uploadSingleFile(file, targetAlbumId, i);
                    if (result.success) {
                        updateProgressItem(i, 'success', '上传成功');
                        uploadedCount++;
                    } else {
                        updateProgressItem(i, 'error', result.error || '上传失败');
                        failedCount++;
                    }
                } catch (error) {
                    updateProgressItem(i, 'error', error.message);
                    failedCount++;
                }
            }
            
            // 显示上传总结
            showUploadSummary(uploadedCount, failedCount);
            resetUploadForm();
        }

        // 创建进度项
        function createProgressItem(fileName, index) {
            const item = document.createElement('div');
            item.className = 'progress-item';
            item.id = `progress-${index}`;
            item.innerHTML = `
                <div class="progress-file-name">${fileName}</div>
                <div class="progress-status">
                    <span class="status-text">准备上传...</span>
                    <div class="progress-bar">
                        <div class="progress-fill"></div>
                    </div>
                </div>
            `;
            return item;
        }

        // 更新进度项状态
        function updateProgressItem(index, status, message) {
            const item = document.getElementById(`progress-${index}`);
            if (!item) return;
            
            const statusText = item.querySelector('.status-text');
            const progressFill = item.querySelector('.progress-fill');
            
            statusText.textContent = message;
            
            if (status === 'success') {
                item.classList.add('success');
                progressFill.style.width = '100%';
            } else if (status === 'error') {
                item.classList.add('error');
                progressFill.style.width = '100%';
            } else if (status === 'uploading') {
                statusText.textContent = '上传中...';
                progressFill.style.width = '50%';
            }
        }

        // 上传单个文件
        async function uploadSingleFile(file, albumId, index) {
            updateProgressItem(index, 'uploading', '上传中...');
            
            const formData = new FormData();
            formData.append('action', 'upload');
            formData.append('photo', file);
            if (albumId) {
                formData.append('album_id', albumId);
            }
            
            const response = await fetch('upload-ajax.php', {
                method: 'POST',
                body: formData
            });
            
            if (!response.ok) {
                throw new Error('网络错误: ' + response.status);
            }
            
            return await response.json();
        }

        // 显示上传总结
        function showUploadSummary(uploaded, failed) {
            const summary = document.getElementById('upload-summary');
            summary.innerHTML = `
                <div class="upload-summary">
                    <h4>上传完成</h4>
                    <div class="summary-stats">
                        <span class="success-count">成功: ${uploaded}</span>
                        ${failed > 0 ? `<span class="error-count">失败: ${failed}</span>` : ''}
                    </div>
                    <button onclick="refreshPage()" class="btn btn-primary">刷新页面查看</button>
                </div>
            `;
            summary.style.display = 'block';
        }

        // 重置上传表单
        function resetUploadForm() {
            const uploadBtn = document.getElementById('batch-upload-btn');
            uploadBtn.disabled = false;
            uploadBtn.textContent = '<?php echo $t['upload']; ?>';
        }

        // 刷新页面
        function refreshPage() {
            window.location.reload();
        }

        function toggleNewAlbum() {
            const section = document.getElementById('new-album-section');
            section.classList.toggle('active');
            
            // 清空相册选择
            if (section.classList.contains('active')) {
                document.getElementById('album_id').value = '';
            }
        }

        function toggleBatchNewAlbum() {
            const section = document.getElementById('batch-new-album-section');
            section.classList.toggle('active');
            
            // 清空相册选择
            if (section.classList.contains('active')) {
                document.getElementById('batch_album_id').value = '';
            }
        }

        function toggleNewAlbumSection() {
            const albumSelect = document.getElementById('album_id');
            const newAlbumSection = document.getElementById('new-album-section');
            const newAlbumInput = document.getElementById('new_album_title');
            const newAlbumDescription = document.getElementById('new_album_description');
            
            if (albumSelect.value) {
                newAlbumSection.classList.remove('active');
                newAlbumInput.value = '';
                newAlbumDescription.value = '';
            }
        }

        function viewPhoto(url, title, uploader, uploadedAt, albumTitle) {
            document.getElementById('modal-title').textContent = title;
            document.getElementById('modal-image').src = url;
            document.getElementById('modal-info').innerHTML = `
                <div style="margin-top: 15px;">
                    <p><strong><?php echo $t['photo_url']; ?>:</strong> <code>${url}</code></p>
                    <p><strong><?php echo $t['uploader']; ?>:</strong> ${uploader}</p>
                    <p><strong><?php echo $t['uploaded_at']; ?>:</strong> ${uploadedAt}</p>
                    <p><strong><?php echo $t['album']; ?>:</strong> ${albumTitle}</p>
                </div>
            `;
            document.getElementById('viewModal').style.display = 'block';
        }

        function closeViewModal() {
            document.getElementById('viewModal').style.display = 'none';
        }

        function deletePhoto(photoId, photoName) {
            document.getElementById('delete_photo_id').value = photoId;
            document.getElementById('delete_photo_name').textContent = photoName;
            document.getElementById('deleteModal').style.display = 'block';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }

        function copyUrl(url) {
            const fullUrl = window.location.origin + url;
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(fullUrl).then(() => {
                    alert('URL copied to clipboard: ' + fullUrl);
                }).catch(() => {
                    fallbackCopyTextToClipboard(fullUrl);
                });
            } else {
                fallbackCopyTextToClipboard(fullUrl);
            }
        }

        function fallbackCopyTextToClipboard(text) {
            const textArea = document.createElement("textarea");
            textArea.value = text;
            textArea.style.top = "0";
            textArea.style.left = "0";
            textArea.style.position = "fixed";
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            try {
                document.execCommand('copy');
                alert('URL copied to clipboard: ' + text);
            } catch (err) {
                console.error('Fallback: Oops, unable to copy', err);
                prompt('Copy URL manually:', text);
            }
            document.body.removeChild(textArea);
        }

        // 设置封面照片
        function setCover(photoId, albumId, fileName) {
            if (confirm('确定要将照片 "' + fileName + '" 设置为相册封面吗？')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="set_cover">
                    <input type="hidden" name="photo_id" value="${photoId}">
                    <input type="hidden" name="album_id" value="${albumId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // 页面初始化
        document.addEventListener('DOMContentLoaded', function() {
            // 默认显示单张上传模式
            const batchSection = document.getElementById('batch-upload-section');
            if (batchSection) {
                batchSection.style.display = 'none';
            }
        });

        // 点击模态框外部关闭
        window.onclick = function(event) {
            const viewModal = document.getElementById('viewModal');
            const deleteModal = document.getElementById('deleteModal');
            if (event.target == viewModal) {
                viewModal.style.display = 'none';
            }
            if (event.target == deleteModal) {
                deleteModal.style.display = 'none';
            }
        }
    </script>
</body>
</html>