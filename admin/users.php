<?php
session_start();
require_once '../config/config.php';
require_once '../config/lang.php';
require_once '../config/OperationLogger.php';

// 检查用户是否登录和管理员权限
if (!isset($_SESSION['user'])) {
    header('Location: ../login.php');
    exit();
}

// 语言处理
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

// 检查管理员权限
$user_email = $_SESSION['user']['email'];
$stmt = $pdo->prepare('SELECT 1 FROM admin_users WHERE email = ? LIMIT 1');
$stmt->execute([$user_email]);
$is_admin = $stmt->fetch() ? true : false;
if (!$is_admin) {
    $error_message = $t['access_denied'];
    header("Location: ../index.php?error=" . urlencode($error_message));
    exit();
}

// 初始化操作日志记录器
$logger = new OperationLogger($pdo);
$current_user = $_SESSION['user']['email'];
$current_username = $_SESSION['user']['username'] ?? $_SESSION['user']['email'];

// 处理表单提交
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'ban':
                $email = trim($_POST['email']);
                $username = trim($_POST['username'] ?? '');
                $reason = trim($_POST['reason']);
                $ban_type = $_POST['ban_type'];
                $ban_until = null;
                $is_permanent = false;
                
                if ($ban_type === 'permanent') {
                    $is_permanent = true;
                } else {
                    $duration = (int)$_POST['duration'];
                    $unit = $_POST['unit'];
                    
                    $hours = 0;
                    switch ($unit) {
                        case 'minutes':
                            $hours = $duration / 60;
                            break;
                        case 'hours':
                            $hours = $duration;
                            break;
                        case 'days':
                            $hours = $duration * 24;
                            break;
                    }
                    
                    $ban_until = date('Y-m-d H:i:s', strtotime("+{$hours} hours"));
                }
                
                if (!empty($email) && !empty($reason)) {
                    try {
                        // 检查是否已经被封禁
                        $stmt = $pdo->prepare("SELECT id FROM user_bans WHERE email = ? AND status = 'active'");
                        $stmt->execute([$email]);
                        if ($stmt->fetch()) {
                            $message = "用户已经被封禁";
                            $message_type = 'error';
                            break;
                        }
                        
                        // 插入封禁记录
                        $stmt = $pdo->prepare("INSERT INTO user_bans (email, username, reason, banned_by, banned_until, is_permanent) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$email, $username, $reason, $current_username, $ban_until, $is_permanent]);
                        
                        // 记录操作日志
                        $ban_desc = $is_permanent ? "永久封禁用户 {$email}" : "封禁用户 {$email} 至 {$ban_until}";
                        $logger->logSystemOperation(
                            $current_user, 
                            $current_username, 
                            'ban_user', 
                            $ban_desc . "，原因：{$reason}"
                        );
                        
                        $message = $t['ban_success'];
                        $message_type = 'success';
                    } catch (PDOException $e) {
                        $message = "Error: " . $e->getMessage();
                        $message_type = 'error';
                    }
                }
                break;
                
            case 'unban':
                $ban_id = $_POST['ban_id'];
                if (!empty($ban_id)) {
                    try {
                        // 获取封禁信息
                        $stmt = $pdo->prepare("SELECT * FROM user_bans WHERE id = ?");
                        $stmt->execute([$ban_id]);
                        $ban_info = $stmt->fetch();
                        
                        if ($ban_info) {
                            // 更新封禁状态
                            $stmt = $pdo->prepare("UPDATE user_bans SET status = 'lifted', updated_at = NOW() WHERE id = ?");
                            $stmt->execute([$ban_id]);
                            
                            // 记录操作日志
                            $logger->logSystemOperation(
                                $current_user, 
                                $current_username, 
                                'unban_user', 
                                "解封用户 {$ban_info['email']}"
                            );
                            
                            $message = $t['unban_success'];
                            $message_type = 'success';
                        }
                    } catch (PDOException $e) {
                        $message = "Error: " . $e->getMessage();
                        $message_type = 'error';
                    }
                }
                break;
        }
    }
}

// 记录访问用户管理页面的操作
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $logger->logSystemOperation($current_user, $current_username, 'view', '访问用户管理页面');
}

// 分页和搜索参数
$page = max(1, $_GET['page'] ?? 1);
$per_page = max(10, min(100, $_GET['per_page'] ?? 20));
$search = $_GET['search'] ?? '';
$offset = ($page - 1) * $per_page;

// 构建搜索查询
$where_clause = "WHERE status = 'active'";
$params = [];
if (!empty($search)) {
    $where_clause .= " AND (email LIKE ? OR username LIKE ? OR reason LIKE ?)";
    $params = ["%$search%", "%$search%", "%$search%"];
}

// 获取总记录数
$count_sql = "SELECT COUNT(*) FROM user_bans $where_clause";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $per_page);

// 获取封禁用户列表
$sql = "SELECT * FROM user_bans $where_clause ORDER BY banned_at DESC LIMIT $per_page OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$banned_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['user_management']; ?> - <?php echo $t['admin_dashboard']; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Segoe UI', 'Microsoft JhengHei', Arial, sans-serif;
            background: #f8f9fa;
        }

        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 32px;
        }

        .page-title {
            font-size: 2.2em;
            color: #2c3e50;
            margin-bottom: 32px;
            font-weight: bold;
        }

        .breadcrumb {
            color: #666;
            font-size: 14px;
        }

        .breadcrumb a {
            color: #667eea;
            text-decoration: none;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        .content-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 20px;
        }

        .message {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .action-buttons {
            margin-bottom: 20px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            margin-right: 10px;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5a6fd8;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }

        .search-controls {
            display: flex;
            gap: 15px;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .search-input {
            flex: 1;
            min-width: 250px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
        }

        .per-page-select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 6px;
        }

        .users-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .users-table th,
        .users-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .users-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }

        .users-table tr:hover {
            background: #f8f9fa;
        }

        .ban-status {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }

        .ban-status.active {
            background: #dc3545;
            color: white;
        }

        .ban-status.lifted {
            background: #28a745;
            color: white;
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
        }

        .pagination a,
        .pagination span {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: #333;
        }

        .pagination a:hover {
            background: #f8f9fa;
        }

        .pagination .current {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        /* 模态框样式 */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            position: relative;
        }

        .modal-header {
            margin-bottom: 20px;
        }

        .modal-header h2 {
            color: #333;
        }

        .close {
            position: absolute;
            right: 15px;
            top: 15px;
            font-size: 24px;
            cursor: pointer;
            color: #999;
        }

        .close:hover {
            color: #333;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .ban-duration-group {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .ban-duration-group input {
            flex: 1;
        }

        .ban-duration-group select {
            flex: 1;
        }

        .radio-group {
            display: flex;
            gap: 20px;
            margin-top: 5px;
        }

        .radio-group label {
            display: flex;
            align-items: center;
            font-weight: normal;
            cursor: pointer;
        }

        .radio-group input[type="radio"] {
            width: auto;
            margin-right: 5px;
        }

        @media (max-width: 768px) {
            .admin-container {
                padding: 10px;
            }
            
            .search-controls {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-input {
                min-width: auto;
            }
            
            .users-table {
                font-size: 12px;
            }
            
            .modal-content {
                margin: 10% auto;
                padding: 20px;
                width: 95%;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="admin-container">
        <h1 class="page-title"><?php echo $t['user_management']; ?></h1>

        <div class="content-card">
            <div style="padding: 30px;">
            <?php if ($message): ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- 操作按钮 -->
            <div class="action-buttons">
                <button onclick="openBanModal()" class="btn btn-danger"><?php echo $t['ban_user']; ?></button>
            </div>

            <!-- 搜索和筛选 -->
            <div class="search-controls">
                <form method="GET" action="" style="display: flex; gap: 15px; align-items: center; flex: 1;">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="<?php echo $t['search']; ?>..." class="search-input">
                    <select name="per_page" class="per-page-select" onchange="this.form.submit()">
                        <option value="10" <?php echo $per_page == 10 ? 'selected' : ''; ?>>10 <?php echo $t['results']; ?></option>
                        <option value="20" <?php echo $per_page == 20 ? 'selected' : ''; ?>>20 <?php echo $t['results']; ?></option>
                        <option value="50" <?php echo $per_page == 50 ? 'selected' : ''; ?>>50 <?php echo $t['results']; ?></option>
                    </select>
                    <button type="submit" class="btn btn-primary"><?php echo $t['search']; ?></button>
                </form>
            </div>

            <!-- 分页信息 -->
            <?php if ($total_records > 0): ?>
                <div class="pagination-info">
                    <?php 
                    $start = $offset + 1;
                    $end = min($offset + $per_page, $total_records);
                    echo sprintf($t['showing_results'], $start, $end, $total_records);
                    ?>
                </div>
            <?php endif; ?>

            <!-- 封禁用户列表 -->
            <?php if (count($banned_users) > 0): ?>
                <table class="users-table">
                    <thead>
                        <tr>
                            <th><?php echo $t['user_email']; ?></th>
                            <th><?php echo $t['username']; ?></th>
                            <th><?php echo $t['ban_reason']; ?></th>
                            <th><?php echo $t['banned_by']; ?></th>
                            <th><?php echo $t['banned_at']; ?></th>
                            <th><?php echo $t['ban_until']; ?></th>
                            <th><?php echo $t['ban_status']; ?></th>
                            <th><?php echo $t['actions']; ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($banned_users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['username'] ?: '-'); ?></td>
                                <td><?php echo htmlspecialchars($user['reason']); ?></td>
                                <td><?php echo htmlspecialchars($user['banned_by']); ?></td>
                                <td><?php echo date('Y-m-d H:i', strtotime($user['banned_at'])); ?></td>
                                <td>
                                    <?php if ($user['is_permanent']): ?>
                                        <span style="color: #dc3545; font-weight: bold;"><?php echo $t['permanent_ban']; ?></span>
                                    <?php elseif ($user['banned_until']): ?>
                                        <?php echo date('Y-m-d H:i', strtotime($user['banned_until'])); ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="ban-status <?php echo $user['status']; ?>">
                                        <?php echo $user['status'] === 'active' ? $t['active_ban'] : $t['lifted_ban']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($user['status'] === 'active'): ?>
                                        <button onclick="unbanUser(<?php echo $user['id']; ?>, '<?php echo addslashes($user['email']); ?>')" 
                                                class="btn btn-success btn-sm"><?php echo $t['unban_user']; ?></button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- 分页导航 -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page-1; ?>&per_page=<?php echo $per_page; ?>&search=<?php echo urlencode($search); ?>"><?php echo $t['prev']; ?></a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="current"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?page=<?php echo $i; ?>&per_page=<?php echo $per_page; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page+1; ?>&per_page=<?php echo $per_page; ?>&search=<?php echo urlencode($search); ?>"><?php echo $t['next']; ?></a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 40px; color: #666;">
                    <?php echo $t['no_banned_users']; ?>
                </div>
            <?php endif; ?>
            </div> <!-- padding container -->
        </div> <!-- content-card -->
    </div> <!-- admin-container -->

    <!-- 封禁用户模态框 -->
    <div id="banModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeBanModal()">&times;</span>
            <div class="modal-header">
                <h2><?php echo $t['ban_user']; ?></h2>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="ban">
                <div class="form-group">
                    <label for="email"><?php echo $t['user_email']; ?></label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="username"><?php echo $t['username']; ?> (<?php echo $t['optional']; ?>)</label>
                    <input type="text" id="username" name="username">
                </div>
                <div class="form-group">
                    <label for="reason"><?php echo $t['ban_reason']; ?></label>
                    <textarea id="reason" name="reason" required placeholder="<?php echo $t['ban_reason']; ?>..."></textarea>
                </div>
                <div class="form-group">
                    <label><?php echo $t['ban_duration']; ?></label>
                    <div class="radio-group">
                        <label>
                            <input type="radio" name="ban_type" value="temporary" checked onchange="toggleDuration()">
                            <?php echo $t['temporary']; ?>
                        </label>
                        <label>
                            <input type="radio" name="ban_type" value="permanent" onchange="toggleDuration()">
                            <?php echo $t['permanent_ban']; ?>
                        </label>
                    </div>
                </div>
                <div class="form-group" id="duration-group">
                    <label><?php echo $t['ban_duration']; ?></label>
                    <div class="ban-duration-group">
                        <input type="number" name="duration" value="1" min="1" required>
                        <select name="unit">
                            <option value="minutes"><?php echo $t['minutes']; ?></option>
                            <option value="hours"><?php echo $t['hours']; ?></option>
                            <option value="days" selected><?php echo $t['days']; ?></option>
                        </select>
                    </div>
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" onclick="closeBanModal()" class="btn"><?php echo $t['cancel']; ?></button>
                    <button type="submit" class="btn btn-danger"><?php echo $t['ban_user']; ?></button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openBanModal() {
            document.getElementById('banModal').style.display = 'block';
        }

        function closeBanModal() {
            document.getElementById('banModal').style.display = 'none';
        }

        function toggleDuration() {
            const durationGroup = document.getElementById('duration-group');
            const banType = document.querySelector('input[name="ban_type"]:checked').value;
            
            if (banType === 'permanent') {
                durationGroup.style.display = 'none';
                document.querySelector('input[name="duration"]').required = false;
            } else {
                durationGroup.style.display = 'block';
                document.querySelector('input[name="duration"]').required = true;
            }
        }

        function unbanUser(banId, email) {
            if (confirm('确定要解封用户 "' + email + '" 吗？')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="unban">
                    <input type="hidden" name="ban_id" value="${banId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // 点击模态框外部关闭
        window.onclick = function(event) {
            const modal = document.getElementById('banModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>