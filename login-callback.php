<?php
// Google OAuth 回调处理

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/lang.php';
require_once __DIR__ . '/config/BanChecker.php';
require_once __DIR__ . '/config/UserActivityLogger.php';
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

function log_attempt($username, $email, $ip, $status, $error_reason, $input_email) {
    global $pdo;
    $stmt = $pdo->prepare('INSERT INTO login_logs (username, email, ip_address, status, error_reason, input_email) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$username, $email, $ip, $status, $error_reason, $input_email]);
}

function show_error($msg, $input_email = '', $username = '', $email = '') {
    $ip = $_SERVER['REMOTE_ADDR'];
    log_attempt($username, $email, $ip, 'fail', $msg, $input_email);
    echo '<!DOCTYPE html><html lang="' . htmlspecialchars($GLOBALS['current_lang']) . '"><head><meta charset="UTF-8">';
    echo '<title>登入錯誤 / Login Error</title>';
    echo '<style>body{font-family:Arial,sans-serif;background:#f7f7f7;text-align:center;padding-top:100px;}';
    echo '.error-box{display:inline-block;background:#fff3f3;border:2px solid #d32f2f;color:#d32f2f;padding:32px 48px;border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,0.08);font-size:1.3em;}';
    echo '</style></head><body>';
    echo '<div class="error-box">' . $msg . '</div>';
    echo '<br><br><a href="login.php?lang=' . htmlspecialchars($GLOBALS['current_lang']) . '" style="color:#4285f4;font-size:1.1em;">' . ($GLOBALS['current_lang'] === 'zh-HK' ? '返回登入頁面' : 'Back to Login') . '</a>';
    echo '</body></html>';
    exit;
}

$client_id = 'YOUR_CLIENT_ID';
$client_secret = 'YOUR_CLIENT_SECRET';
$redirect_uri = 'https://lkypt.lbynb.top/login-callback.php';

// 允许的邮箱后缀和邮箱
$allowed_domains = ['@house.skhlkyss.edu.hk', '@skhlkyss.edu.hk'];
$allowed_emails = ['liub6696@gmail.com'];

if (!isset($_GET['code'])) {
    $input_email = isset($_GET['email']) ? $_GET['email'] : '';
    show_error($t['error_no_code'], $input_email);
}

// 获取 access_token
$token_url = 'https://oauth2.googleapis.com/token';
$post_fields = [
    'code' => $_GET['code'],
    'client_id' => $client_id,
    'client_secret' => $client_secret,
    'redirect_uri' => $redirect_uri,
    'grant_type' => 'authorization_code',
];

$ch = curl_init($token_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_fields));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);
$data = json_decode($response, true);

if (!isset($data['access_token'])) {
    $input_email = isset($_GET['email']) ? $_GET['email'] : '';
    show_error($t['error_no_token'], $input_email);
}

// 获取用户信息
$userinfo_url = 'https://www.googleapis.com/oauth2/v2/userinfo';
$ch = curl_init($userinfo_url);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $data['access_token']]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$userinfo = curl_exec($ch);
curl_close($ch);
$user = json_decode($userinfo, true);

if (!isset($user['email'])) {
    $input_email = '';
    show_error($t['error_no_email'], $input_email);
}

$email = $user['email'];
$username = isset($user['name']) ? $user['name'] : $email;
$ip = $_SERVER['REMOTE_ADDR'];

// 校验邮箱后缀
$valid = false;
foreach ($allowed_domains as $domain) {
    if (substr($email, -strlen($domain)) === $domain) {
        $valid = true;
        break;
    }
}
if (in_array($email, $allowed_emails)) {
    $valid = true;
}

if (!$valid) {
    show_error($t['error_invalid_email'], $email, $username, $email);
}

// 检查用户是否被封禁
$banChecker = new BanChecker($pdo, $current_lang, $t);
$ban_info = $banChecker->checkUserBan($email);

if ($ban_info) {
    // 用户被封禁，记录尝试登录日志并显示封禁页面
    log_attempt($username, $email, $ip, 'banned', '用户被封禁', $email);
    $banChecker->showBanPage($ban_info);
}

// 登录成功日志
log_attempt($username, $email, $ip, 'success', '', $email);

// 登录成功，设置 session
session_start();
$_SESSION['user'] = [
    'username' => $username,
    'email' => $email,
];

// 记录用户活动日志
$userActivityLogger = new UserActivityLogger($pdo, $current_lang, $t);
$userActivityLogger->logLogin($email, $username, 'google_oauth');

// 跳转到相册页面
header('Location: album.php');
exit;
