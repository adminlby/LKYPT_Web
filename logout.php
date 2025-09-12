<?php
session_start();

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/lang.php';
require_once __DIR__ . '/config/UserActivityLogger.php';

$lang = isset($_GET['lang']) && in_array($_GET['lang'], ['zh-HK', 'en']) ? $_GET['lang'] : 'zh-HK';
$t = $langs[$lang];

// 记录用户退出日志（在清除session前）
if (isset($_SESSION['user'])) {
    $userActivityLogger = new UserActivityLogger($pdo, $lang, $t);
    $userActivityLogger->logLogout($_SESSION['user']['email'], $_SESSION['user']['username']);
}

// 清除登录信息
session_unset();
session_destroy();

// 检查是否是AJAX请求
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

$isPostRequest = $_SERVER['REQUEST_METHOD'] === 'POST';

if ($isAjax || $isPostRequest) {
    // AJAX请求，返回JSON响应
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    echo json_encode([
        'success' => true,
        'message' => $lang === 'zh-HK' ? '您已成功登出' : 'You have logged out successfully',
        'redirect' => 'index.php?lang=' . $lang
    ]);
} else {
    // 普通请求，返回HTML页面
    echo '<!DOCTYPE html><html lang="' . $lang . '"><head><meta charset="UTF-8"><title>' . $t['login'] . '</title>';
    echo '<meta http-equiv="refresh" content="2;url=index.php?lang=' . $lang . '">';
    echo '<style>body{font-family:Arial,sans-serif;text-align:center;padding-top:100px;}</style></head><body>';
    echo '<h2 style="color:#1976d2;">' . ($lang === 'zh-HK' ? '您已成功登出，正在返回首頁...' : 'You have logged out successfully, returning to home...') . '</h2>';
    echo '</body></html>';
}
