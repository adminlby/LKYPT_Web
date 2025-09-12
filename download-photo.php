<?php
// 使用页面保护中间件（包含登录检查和封禁检查）
require_once __DIR__ . '/config/page_protection.php';

// 获取用户信息
$user_email = $_SESSION['user']['email'];
$user_name = $_SESSION['user']['username'] ?? '';

// 获取照片ID
$photo_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$photo_id) {
    http_response_code(400);
    exit('Invalid photo ID');
}

try {
    // 获取照片信息
    $stmt = $pdo->prepare('
        SELECT p.*, a.title as album_title 
        FROM photos p
        LEFT JOIN albums a ON p.album_id = a.id 
        WHERE p.id = ?
    ');
    $stmt->execute([$photo_id]);
    $photo = $stmt->fetch();
    
    if (!$photo) {
        http_response_code(404);
        exit('Photo not found');
    }
    
    // 检查文件是否存在
    $file_path = $photo['url'];
    
    // 如果是相对路径，转换为绝对路径
    if (!filter_var($file_path, FILTER_VALIDATE_URL)) {
        $file_path = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($file_path, '/');
    }
    
    // 记录下载活动日志
    $logger = new UserActivityLogger($pdo);
    
    $logger->logDownloadPhoto(
        $user_email,
        $user_name,
        $photo_id,
        "照片ID #{$photo_id}",
        $photo['album_title'] ?? ''
    );
    
    // 如果是外部URL，重定向
    if (filter_var($photo['url'], FILTER_VALIDATE_URL)) {
        header('Location: ' . $photo['url']);
        exit;
    }
    
    // 检查本地文件是否存在
    if (!file_exists($file_path)) {
        http_response_code(404);
        exit('File not found');
    }
    
    // 获取文件信息
    $file_size = filesize($file_path);
    $file_info = pathinfo($file_path);
    $file_extension = strtolower($file_info['extension']);
    
    // 设置适当的MIME类型
    $mime_types = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'bmp' => 'image/bmp'
    ];
    
    $mime_type = $mime_types[$file_extension] ?? 'application/octet-stream';
    
    // 生成下载文件名
    $download_filename = 'photo_' . $photo_id . '_' . date('Y-m-d') . '.' . $file_extension;
    
    // 设置下载头信息
    header('Content-Type: ' . $mime_type);
    header('Content-Disposition: attachment; filename="' . $download_filename . '"');
    header('Content-Length: ' . $file_size);
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
    
    // 输出文件内容
    if ($file_size > 8192) {
        // 对于大文件，分块读取以避免内存问题
        $handle = fopen($file_path, 'rb');
        if ($handle === false) {
            http_response_code(500);
            exit('Failed to open file');
        }
        
        while (!feof($handle)) {
            echo fread($handle, 8192);
            flush();
        }
        fclose($handle);
    } else {
        // 小文件直接输出
        readfile($file_path);
    }
    
} catch (Exception $e) {
    // 记录错误日志
    if (isset($logger) && isset($user_email) && isset($user_name)) {
        $logger->logError(
            $user_email,
            $user_name,
            'download_photo',
            "下载照片失败 ID#{$photo_id}: " . $e->getMessage()
        );
    }
    
    http_response_code(500);
    exit('Download failed: ' . $e->getMessage());
}
?>