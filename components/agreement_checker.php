<?php
// 协议检查组件 - 仅在用户已登录时包含此组件
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 确保语言文件已加载
if (!isset($t) || empty($t)) {
    require_once __DIR__ . '/../config/lang.php';
    $current_lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'zh';
    $t = $lang[$current_lang] ?? $lang['zh'];
}

// 只有在用户已登录时才包含协议弹窗
if (isset($_SESSION['user']) && isset($_SESSION['user']['email'])):
?>
<!-- 包含协议弹窗组件 -->
<?php include __DIR__ . '/agreement_modal.php'; ?>

<script>
// 页面加载完成后检查协议状态
document.addEventListener('DOMContentLoaded', function() {
    // 检查协议状态
    checkAgreementStatus();
});
</script>
<?php endif; ?>