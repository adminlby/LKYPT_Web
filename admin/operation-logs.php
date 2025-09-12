<?php
session_start();
require_once '../config/config.php';
require_once '../config/lang.php';
require_once '../config/OperationLogger.php';

// 检查用户是否登录
if (!isset($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
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

// 记录查看操作日志的行为
$logger->logSystemOperation($user_email, $_SESSION['user']['username'] ?? $user_email, 'view', '查看操作日志页面');

// 获取筛选参数
$page = max(1, $_GET['page'] ?? 1);
$per_page = max(10, min(100, $_GET['per_page'] ?? 20));
$filters = [
    'user_id' => $_GET['user_id'] ?? '',
    'operation_type' => $_GET['operation_type'] ?? '',
    'module' => $_GET['module'] ?? '',
    'status' => $_GET['status'] ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? ''
];

// 获取操作日志数据
$result = $logger->getLogs($page, $per_page, $filters);
$logs = $result['data'];
$total_records = $result['total'];
$total_pages = $result['total_pages'];

?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>操作日志 - <?php echo $t['admin_dashboard']; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }

        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .admin-header {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .admin-header h1 {
            color: #333;
            margin-bottom: 10px;
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
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .filters-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .filters-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            font-weight: 600;
            margin-bottom: 5px;
            color: #333;
        }

        .filter-group input,
        .filter-group select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }

        .filter-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5a6fd8;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .logs-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .logs-table th,
        .logs-table td {
            padding: 12px 8px;
            text-align: left;
            border-bottom: 1px solid #eee;
            vertical-align: top;
        }

        .logs-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
            position: sticky;
            top: 0;
        }

        .logs-table tr:hover {
            background: #f8f9fa;
        }

        .operation-type {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
            text-align: center;
            white-space: nowrap;
        }

        .operation-type.create {
            background: #d4edda;
            color: #155724;
        }

        .operation-type.update {
            background: #d1ecf1;
            color: #0c5460;
        }

        .operation-type.delete {
            background: #f8d7da;
            color: #721c24;
        }

        .operation-type.login {
            background: #cce5ff;
            color: #004085;
        }

        .operation-type.logout {
            background: #e2e3e5;
            color: #383d41;
        }

        .operation-type.view {
            background: #fff3cd;
            color: #856404;
        }

        .module-badge {
            padding: 2px 6px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 500;
            text-align: center;
        }

        .module-badge.album {
            background: #e3f2fd;
            color: #1976d2;
        }

        .module-badge.photo {
            background: #f3e5f5;
            color: #7b1fa2;
        }

        .module-badge.system {
            background: #e8f5e8;
            color: #2e7d32;
        }

        .status-badge {
            padding: 2px 6px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 500;
            text-align: center;
        }

        .status-badge.success {
            background: #d4edda;
            color: #155724;
        }

        .status-badge.failed {
            background: #f8d7da;
            color: #721c24;
        }

        .status-badge.warning {
            background: #fff3cd;
            color: #856404;
        }

        .operation-desc {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .operation-desc:hover {
            white-space: normal;
            overflow: visible;
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

        .pagination-info {
            text-align: center;
            color: #666;
            margin-bottom: 10px;
        }

        .no-data {
            text-align: center;
            color: #666;
            padding: 40px 20px;
            font-style: italic;
        }

        @media (max-width: 768px) {
            .admin-container {
                padding: 10px;
            }

            .filters-row {
                grid-template-columns: 1fr;
            }

            .logs-table {
                font-size: 12px;
            }

            .logs-table th,
            .logs-table td {
                padding: 8px 4px;
            }

            .operation-desc {
                max-width: 150px;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="admin-container">
        <div class="admin-header">
            <h1><?php echo $t['operation_logs']; ?></h1>
            <div class="breadcrumb">
                <a href="dashboard.php"><?php echo $t['admin_dashboard']; ?></a> / <?php echo $t['operation_logs']; ?>
            </div>
        </div>

        <div class="content-card">
            <!-- 筛选器 -->
            <div class="filters-section">
                <form method="GET" action="">
                    <?php if (isset($_GET['lang'])): ?>
                        <input type="hidden" name="lang" value="<?php echo htmlspecialchars($_GET['lang']); ?>">
                    <?php endif; ?>
                    <div class="filters-row">
                        <div class="filter-group">
                            <label for="user_id"><?php echo $t['operation_user']; ?></label>
                            <input type="text" id="user_id" name="user_id" value="<?php echo htmlspecialchars($filters['user_id']); ?>" placeholder="<?php echo $t['search_user_email']; ?>">
                        </div>
                        <div class="filter-group">
                            <label for="operation_type"><?php echo $t['operation_type']; ?></label>
                            <select id="operation_type" name="operation_type">
                                <option value=""><?php echo $t['all_types']; ?></option>
                                <option value="create" <?php echo $filters['operation_type'] === 'create' ? 'selected' : ''; ?>><?php echo $t['create']; ?></option>
                                <option value="update" <?php echo $filters['operation_type'] === 'update' ? 'selected' : ''; ?>><?php echo $t['update']; ?></option>
                                <option value="delete" <?php echo $filters['operation_type'] === 'delete' ? 'selected' : ''; ?>><?php echo $t['delete']; ?></option>
                                <option value="login" <?php echo $filters['operation_type'] === 'login' ? 'selected' : ''; ?>><?php echo $t['login']; ?></option>
                                <option value="logout" <?php echo $filters['operation_type'] === 'logout' ? 'selected' : ''; ?>><?php echo $t['logout']; ?></option>
                                <option value="view" <?php echo $filters['operation_type'] === 'view' ? 'selected' : ''; ?>><?php echo $t['view']; ?></option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="module"><?php echo $t['operation_module']; ?></label>
                            <select id="module" name="module">
                                <option value=""><?php echo $t['all_modules']; ?></option>
                                <option value="album" <?php echo $filters['module'] === 'album' ? 'selected' : ''; ?>><?php echo $t['album']; ?></option>
                                <option value="photo" <?php echo $filters['module'] === 'photo' ? 'selected' : ''; ?>><?php echo $t['photo']; ?></option>
                                <option value="system" <?php echo $filters['module'] === 'system' ? 'selected' : ''; ?>><?php echo $t['system']; ?></option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="status"><?php echo $t['status']; ?></label>
                            <select id="status" name="status">
                                <option value=""><?php echo $t['all_status']; ?></option>
                                <option value="success" <?php echo $filters['status'] === 'success' ? 'selected' : ''; ?>><?php echo $t['success']; ?></option>
                                <option value="failed" <?php echo $filters['status'] === 'failed' ? 'selected' : ''; ?>><?php echo $t['failed']; ?></option>
                                <option value="warning" <?php echo $filters['status'] === 'warning' ? 'selected' : ''; ?>><?php echo $t['warning']; ?></option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="date_from"><?php echo $t['start_date']; ?></label>
                            <input type="date" id="date_from" name="date_from" value="<?php echo htmlspecialchars($filters['date_from']); ?>">
                        </div>
                        <div class="filter-group">
                            <label for="date_to"><?php echo $t['end_date']; ?></label>
                            <input type="date" id="date_to" name="date_to" value="<?php echo htmlspecialchars($filters['date_to']); ?>">
                        </div>
                    </div>
                    <div class="filters-row">
                        <div class="filter-group">
                            <label for="per_page"><?php echo $t['per_page_items']; ?></label>
                            <select id="per_page" name="per_page">
                                <option value="20" <?php echo $per_page == 20 ? 'selected' : ''; ?>><?php echo $t['items_20']; ?></option>
                                <option value="50" <?php echo $per_page == 50 ? 'selected' : ''; ?>><?php echo $t['items_50']; ?></option>
                                <option value="100" <?php echo $per_page == 100 ? 'selected' : ''; ?>><?php echo $t['items_100']; ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="filter-actions">
                        <button type="submit" class="btn btn-primary"><?php echo $t['search']; ?></button>
                        <a href="operation-logs.php<?php echo isset($_GET['lang']) ? '?lang=' . htmlspecialchars($_GET['lang']) : ''; ?>" class="btn btn-secondary"><?php echo $t['reset']; ?></a>
                    </div>
                </form>
            </div>

            <!-- 分页信息 -->
            <?php if ($total_records > 0): ?>
                <div class="pagination-info">
                    <?php echo sprintf($t['showing_records'], (($page - 1) * $per_page) + 1, min($page * $per_page, $total_records), $total_records); ?>
                </div>
            <?php endif; ?>

            <!-- 操作日志列表 -->
            <?php if (count($logs) > 0): ?>
                <table class="logs-table">
                    <thead>
                        <tr>
                            <th><?php echo $t['operation_time']; ?></th>
                            <th><?php echo $t['operation_user']; ?></th>
                            <th><?php echo $t['operation_type']; ?></th>
                            <th><?php echo $t['operation_module']; ?></th>
                            <th><?php echo $t['operation_desc']; ?></th>
                            <th><?php echo $t['operation_target']; ?></th>
                            <th><?php echo $t['status']; ?></th>
                            <th><?php echo $t['ip_address']; ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?php echo date('m-d H:i:s', strtotime($log['operation_time'])); ?></td>
                                <td>
                                    <div style="font-weight: 500;"><?php echo htmlspecialchars($log['user_name'] ?: $t['unknown_user']); ?></div>
                                    <small style="color: #666;"><?php echo htmlspecialchars($log['user_id']); ?></small>
                                </td>
                                <td>
                                    <span class="operation-type <?php echo $log['operation_type']; ?>">
                                        <?php 
                                        $type_names = [
                                            'create' => $t['create'],
                                            'update' => $t['update'], 
                                            'delete' => $t['delete'],
                                            'login' => $t['login'],
                                            'logout' => $t['logout'],
                                            'view' => $t['view']
                                        ];
                                        echo $type_names[$log['operation_type']] ?? $log['operation_type'];
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="module-badge <?php echo $log['module']; ?>">
                                        <?php 
                                        $module_names = [
                                            'album' => $t['album'],
                                            'photo' => $t['photo'],
                                            'system' => $t['system']
                                        ];
                                        echo $module_names[$log['module']] ?? $log['module'];
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="operation-desc" title="<?php echo htmlspecialchars($log['operation_desc']); ?>">
                                        <?php echo htmlspecialchars($log['operation_desc']); ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($log['target_title']): ?>
                                        <div style="font-weight: 500;"><?php echo htmlspecialchars($log['target_title']); ?></div>
                                        <?php if ($log['target_id']): ?>
                                            <small style="color: #666;">ID: <?php echo htmlspecialchars($log['target_id']); ?></small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="color: #999;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $log['status']; ?>">
                                        <?php 
                                        $status_names = [
                                            'success' => $t['success'],
                                            'failed' => $t['failed'],
                                            'warning' => $t['warning']
                                        ];
                                        echo $status_names[$log['status']] ?? $log['status'];
                                        ?>
                                    </span>
                                    <?php if ($log['error_message']): ?>
                                        <div style="font-size: 11px; color: #d32f2f; margin-top: 2px;">
                                            <?php echo htmlspecialchars($log['error_message']); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td style="font-family: monospace; font-size: 12px;">
                                    <?php echo htmlspecialchars($log['ip_address']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- 分页导航 -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php
                        // 构建URL参数
                        $url_params = array_filter($filters) + ['per_page' => $per_page];
                        if (isset($_GET['lang'])) {
                            $url_params['lang'] = $_GET['lang'];
                        }
                        $base_url = 'operation-logs.php?' . http_build_query($url_params) . '&page=';
                        ?>

                        <?php if ($page > 1): ?>
                            <a href="<?php echo $base_url . ($page-1); ?>"><?php echo $t['prev']; ?></a>
                        <?php endif; ?>

                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        if ($start_page > 1) {
                            echo '<a href="' . $base_url . '1">1</a>';
                            if ($start_page > 2) {
                                echo '<span>...</span>';
                            }
                        }
                        
                        for ($i = $start_page; $i <= $end_page; $i++) {
                            if ($i == $page) {
                                echo '<span class="current">' . $i . '</span>';
                            } else {
                                echo '<a href="' . $base_url . $i . '">' . $i . '</a>';
                            }
                        }
                        
                        if ($end_page < $total_pages) {
                            if ($end_page < $total_pages - 1) {
                                echo '<span>...</span>';
                            }
                            echo '<a href="' . $base_url . $total_pages . '">' . $total_pages . '</a>';
                        }
                        ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="<?php echo $base_url . ($page+1); ?>"><?php echo $t['next']; ?></a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="no-data">
                    <?php echo $t['no_operation_logs']; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>