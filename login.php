<?php
// Google OAuth 登录入口

require_once __DIR__ . '/config/config.php';
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

$client_id = 'YOUR_CLIENT_ID'; // 替换为你的 Client ID
$redirect_uri = 'https://lkypt.lbynb.top/login-callback.php'; // 替换为你的回调地址
$scope = 'openid email profile';

// 生成 Google OAuth 登录链接
$auth_url = 'https://accounts.google.com/o/oauth2/v2/auth?'
    . 'client_id=' . urlencode($client_id)
    . '&redirect_uri=' . urlencode($redirect_uri)
    . '&response_type=code'
    . '&scope=' . urlencode($scope)
    . '&access_type=online';
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo $t['login_title']; ?></title>
    <style>
        body { font-family: Arial, sans-serif; background: #f7f7f7; text-align: center; padding-top: 100px; }
        .login-btn {
            background: #4285f4;
            color: #fff;
            padding: 16px 32px;
            border-radius: 8px;
            font-size: 1.2em;
            text-decoration: none;
            display: inline-block;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .login-btn:hover { background: #357ae8; }
        .home-btn {
            background: #6c757d;
            color: #fff;
            padding: 16px 32px;
            border-radius: 8px;
            font-size: 1.2em;
            text-decoration: none;
            display: inline-block;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .home-btn:hover { background: #5a6268; }
    </style>
</head>
<body>
    <h2><?php echo $t['login_title']; ?></h2>
    <br><br>
    <a class="home-btn" href="/index.php"><?php echo $t['back_home']; ?></a>
    <a class="login-btn" href="<?php echo $auth_url; ?>"><?php echo $t['login_btn']; ?></a>
</body>
</html>
