<?php
session_start();
require_once '../config/config.php';
require_once '../config/lang.php';
require_once '../config/OperationLogger.php';
require_once '../config/WatermarkProcessor.php';

// 设置JSON响应头
header('Content-Type: application/json');

// 检查用户是否登录和管理员权限
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$user_email = $_SESSION['user']['email'];
$stmt = $pdo->prepare('SELECT 1 FROM admin_users WHERE email = ? LIMIT 1');
$stmt->execute([$user_email]);
$is_admin = $stmt->fetch() ? true : false;
if (!$is_admin) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

// 语言处理
$default_lang = 'zh-HK';
if (isset($_POST['lang']) && in_array($_POST['lang'], ['zh-HK', 'en'])) {
    $current_lang = $_POST['lang'];
} elseif (isset($_COOKIE['site_lang']) && in_array($_COOKIE['site_lang'], ['zh-HK', 'en'])) {
    $current_lang = $_COOKIE['site_lang'];
} else {
    $current_lang = $default_lang;
}
$t = $langs[$current_lang];

// 初始化操作日志记录器
$logger = new OperationLogger($pdo);
$current_user = $_SESSION['user']['email'];
$current_username = $_SESSION['user']['username'] ?? $_SESSION['user']['email'];

// 处理单个文件上传
if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
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
            echo json_encode(['success' => false, 'message' => $t['upload_error'] . ': ' . $e->getMessage()]);
            exit;
        }
    } elseif (empty($album_id)) {
        echo json_encode(['success' => false, 'message' => $t['upload_error'] . ': ' . $t['no_album_selected']]);
        exit;
    }
    
    $file = $_FILES['photo'];
    
    // 验证文件类型
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $file_type = mime_content_type($file['tmp_name']);
    
    if (!in_array($file_type, $allowed_types)) {
        echo json_encode(['success' => false, 'message' => $t['invalid_file_type']]);
        exit;
    }
    
    // 验证文件大小 (20MB)
    if ($file['size'] > 20 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => $t['file_too_large']]);
        exit;
    }
    
    // 生成唯一文件名
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = date('Y-m-d_H-i-s') . '_' . uniqid() . '.' . $ext;
    $temp_upload_path = '../uploads/temp_' . $filename; // 临时文件路径
    
    // 移动上传的文件到临时位置
    if (move_uploaded_file($file['tmp_name'], $temp_upload_path)) {
        try {
            // 初始化水印处理器
            $watermarkProcessor = new WatermarkProcessor();
            
            // 检查水印处理要求
            $requirements = $watermarkProcessor->checkRequirements();
            if ($requirements !== true) {
                unlink($temp_upload_path);
                echo json_encode(['success' => false, 'message' => '水印处理环境检查失败: ' . implode(', ', $requirements)]);
                exit;
            }
            
            // 处理图片水印
            $watermarkResult = $watermarkProcessor->processImage($temp_upload_path, $filename, $current_username);
            
            if (!$watermarkResult['success']) {
                echo json_encode(['success' => false, 'message' => '水印处理失败: ' . $watermarkResult['error']]);
                exit;
            }
            
            // 数据库中保存的URL是水印版本的URL（用于预览）
            $preview_url = $watermarkResult['watermarked_url'];
            $original_url = $watermarkResult['original_url'];
            
            // 保存到数据库
            $stmt = $pdo->prepare("INSERT INTO photos (url, original_url, uploaded_at, uploader, album_id) VALUES (?, ?, NOW(), ?, ?)");
            $stmt->execute([$preview_url, $original_url, $current_username, $album_id]);
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
                "上传照片到相册「{$album_title}」(已添加水印)",
                null,
                [
                    'preview_url' => $preview_url, 
                    'original_url' => $original_url,
                    'album_id' => $album_id, 
                    'filename' => $filename
                ]
            );
            
            echo json_encode([
                'success' => true, 
                'message' => $t['photo_uploaded'] . ' (已添加水印)',
                'filename' => $file['name'],
                'url' => $preview_url,
                'original_url' => $original_url
            ]);
        } catch (PDOException $e) {
            // 删除已处理的文件
            if (isset($watermarkResult) && $watermarkResult['success']) {
                @unlink('../uploads/original/' . $filename);
                @unlink('../uploads/watermarked/' . $filename);
            }
            
            // 记录失败日志
            $logger->logPhotoOperation(
                $current_user, 
                $current_username, 
                'create', 
                null, 
                $filename, 
                '照片上传失败(数据库错误)', 
                null, 
                null, 
                'failed', 
                $e->getMessage()
            );
            
            echo json_encode(['success' => false, 'message' => $t['upload_error'] . ': ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => $t['upload_error'] . ': Failed to move uploaded file']);
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
    echo json_encode(['success' => false, 'message' => $t['upload_error'] . ': ' . ($error_messages[$upload_error] ?? 'Unknown error')]);
}
?>