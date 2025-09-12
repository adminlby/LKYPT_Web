<?php
// 页面访问保护中间件 - 用于需要登录的页面
// 在需要用户登录的页面顶部包含此文件

// 确保已启动 session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lang.php';
require_once __DIR__ . '/BanChecker.php';

// 设置语言
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

// 检查是否已登录
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['email'])) {
    // 未登录，重定向到登录页面
    header('Location: login.php?lang=' . $current_lang);
    exit();
}

// 检查用户是否被封禁
$user_email = $_SESSION['user']['email'];
$banChecker = new BanChecker($pdo, $current_lang, $t);
$ban_info = $banChecker->checkUserBan($user_email);

if ($ban_info) {
    // 用户被封禁，清除 session 并显示封禁页面
    session_destroy();
    $banChecker->showBanPage($ban_info);
}

// 页面保护通过，用户可以访问页面
// 可以使用 $user_email 获取当前用户邮箱
// 可以使用 $_SESSION['user'] 获取用户信息
?>