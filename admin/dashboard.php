<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/lang.php';
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

if (!isset($_SESSION['user'])) {
    echo '<h2 style="color:#d32f2f;text-align:center;margin-top:100px;">' . $t['access_denied'] . '</h2>';
    exit;
}
$user_email = $_SESSION['user']['email'];
$stmt = $pdo->prepare('SELECT 1 FROM admin_users WHERE email = ? LIMIT 1');
$stmt->execute([$user_email]);
$is_admin = $stmt->fetch() ? true : false;
if (!$is_admin) {
    echo '<h2 style="color:#d32f2f;text-align:center;margin-top:100px;">' . $t['access_denied'] . '</h2>';
    exit;
}
// 管理员内容
?><!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo $t['admin_dashboard']; ?></title>
    <style>
        body { font-family: Arial, sans-serif; background: #f7f7f7; }
        .admin-box { max-width: 600px; margin: 80px auto; background: #fff; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.08); padding: 40px 32px; text-align: center; }
        h1 { color: #1976d2; }
    </style>
</head>
<body>
    <div class="admin-box">
        <h1><?php echo $t['admin_dashboard']; ?></h1>
        <p>
            <?php
            $admin_name = isset($_SESSION['user']['username']) ? $_SESSION['user']['username'] : '';
            if ($current_lang === 'zh-HK') {
                echo '歡迎管理員：' . htmlspecialchars($admin_name) . '！';
            } else {
                echo 'Welcome, admin: ' . htmlspecialchars($admin_name) . '!';
            }
            ?>
        </p>
        <!-- 可在此扩展更多管理功能 -->
    </div>
</body>
</html>
