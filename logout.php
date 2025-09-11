<?php
session_start();
require_once __DIR__ . '/config/lang.php';
$lang = isset($_GET['lang']) && in_array($_GET['lang'], ['zh-HK', 'en']) ? $_GET['lang'] : 'zh-HK';
$t = $langs[$lang];
// 清除登录信息
session_unset();
session_destroy();

echo '<!DOCTYPE html><html lang="' . $lang . '"><head><meta charset="UTF-8"><title>' . $t['login'] . '</title>';
echo '<meta http-equiv="refresh" content="2;url=index.php?lang=' . $lang . '">';
echo '<style>body{font-family:Arial,sans-serif;text-align:center;padding-top:100px;}</style></head><body>';
echo '<h2 style="color:#1976d2;">' . ($lang === 'zh-HK' ? '您已成功登出，正在返回首頁...' : 'You have logged out successfully, returning to home...') . '</h2>';
echo '</body></html>';
