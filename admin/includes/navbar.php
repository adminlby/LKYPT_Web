<?php
// 后台导航栏组件
if (!isset($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}

// 检查管理员权限
$user_email = $_SESSION['user']['email'];
$stmt = $pdo->prepare('SELECT 1 FROM admin_users WHERE email = ? LIMIT 1');
$stmt->execute([$user_email]);
$is_admin = $stmt->fetch() ? true : false;
if (!$is_admin) {
    echo '<h2 style="color:#d32f2f;text-align:center;margin-top:100px;">' . $t['access_denied'] . '</h2>';
    exit;
}
?>
<style>
.admin-navbar {
    background: #2c3e50;
    color: #fff;
    padding: 0 32px;
    height: 64px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.admin-nav-left {
    display: flex;
    align-items: center;
}
.admin-nav-logo {
    font-size: 1.4em;
    font-weight: bold;
    margin-right: 40px;
    color: #ffd700;
}
.admin-nav-menu {
    display: flex;
    gap: 32px;
}
.admin-nav-menu a {
    color: #ecf0f1;
    text-decoration: none;
    font-size: 1em;
    padding: 8px 16px;
    border-radius: 4px;
    transition: all 0.2s;
}
.admin-nav-menu a:hover {
    background: #34495e;
    color: #ffd700;
}
.admin-nav-menu a.active {
    background: #3498db;
    color: #fff;
}
.admin-nav-right {
    display: flex;
    align-items: center;
    gap: 16px;
}
.admin-user-info {
    color: #bdc3c7;
    font-size: 0.9em;
}
.admin-nav-btn {
    background: #e74c3c;
    color: #fff;
    padding: 8px 16px;
    border-radius: 4px;
    text-decoration: none;
    font-size: 0.9em;
    transition: background 0.2s;
}
.admin-nav-btn:hover {
    background: #c0392b;
}
@media (max-width: 768px) {
    .admin-navbar {
        flex-direction: column;
        height: auto;
        padding: 16px;
    }
    .admin-nav-logo {
        margin-bottom: 12px;
        margin-right: 0;
    }
    .admin-nav-menu {
        gap: 16px;
        flex-wrap: wrap;
    }
}
</style>

<nav class="admin-navbar">
    <div class="admin-nav-left">
        <div class="admin-nav-logo"><?php echo $t['admin_dashboard']; ?></div>
        <div class="admin-nav-menu">
            <a href="dashboard.php?lang=<?php echo $current_lang; ?>" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                <?php echo $current_lang === 'zh-HK' ? '總覽' : 'Overview'; ?>
            </a>
            <a href="albums.php?lang=<?php echo $current_lang; ?>" class="<?php echo basename($_SERVER['PHP_SELF']) == 'albums.php' ? 'active' : ''; ?>">
                <?php echo $current_lang === 'zh-HK' ? '相冊管理' : 'Album Management'; ?>
            </a>
            <a href="photos.php?lang=<?php echo $current_lang; ?>" class="<?php echo basename($_SERVER['PHP_SELF']) == 'photos.php' ? 'active' : ''; ?>">
                <?php echo $current_lang === 'zh-HK' ? '照片管理' : 'Photo Management'; ?>
            </a>
            <a href="users.php?lang=<?php echo $current_lang; ?>" class="<?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">
                <?php echo $current_lang === 'zh-HK' ? '用戶管理' : 'User Management'; ?>
            </a>
        </div>
    </div>
    <div class="admin-nav-right">
        <div class="admin-user-info">
            <?php echo htmlspecialchars($_SESSION['user']['username']); ?>
        </div>
        <a href="../index.php?lang=<?php echo $current_lang; ?>" class="admin-nav-btn">
            <?php echo $current_lang === 'zh-HK' ? '返回網站' : 'Back to Site'; ?>
        </a>
        <a href="../logout.php?lang=<?php echo $current_lang; ?>" class="admin-nav-btn">
            <?php echo $t['logout']; ?>
        </a>
    </div>
</nav>