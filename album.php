<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
// ...相册页面内容...
echo '<h2>歡迎 ' . htmlspecialchars($_SESSION['user']['username']) . ' 進入相冊頁面！</h2>';
