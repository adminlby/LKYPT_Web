<?php
session_start();
require_once '../config/config.php';
require_once '../config/lang.php';
require_once '../config/UserActivityLogger.php';

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

// 分页和搜索参数
$page = max(1, $_GET['page'] ?? 1);
$per_page = max(10, min(100, $_GET['per_page'] ?? 20));
$search_email = $_GET['search_email'] ?? '';
$search_action = $_GET['search_action'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$offset = ($page - 1) * $per_page;

// 构建搜索查询
$where_conditions = [];
$params = [];

if (!empty($search_email)) {
    $where_conditions[] = "user_email LIKE ?";
    $params[] = "%$search_email%";
}

if (!empty($search_action)) {
    $where_conditions[] = "action_type = ?";
    $params[] = $search_action;
}

if (!empty($date_from)) {
    $where_conditions[] = "DATE(created_at) >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $where_conditions[] = "DATE(created_at) <= ?";
    $params[] = $date_to;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// 获取总记录数
$count_sql = "SELECT COUNT(*) FROM user_activity_logs $where_clause";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $per_page);

// 获取活动日志列表
$sql = "SELECT * FROM user_activity_logs $where_clause ORDER BY created_at DESC LIMIT $per_page OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$activity_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 获取操作类型统计
$stats_sql = "SELECT action_type, COUNT(*) as count FROM user_activity_logs $where_clause GROUP BY action_type ORDER BY count DESC";
$stats_stmt = $pdo->prepare($stats_sql);
$stats_stmt->execute($params);
$action_stats = $stats_stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $current_lang === 'zh-HK' ? '用户活动日志' : 'User Activity Logs'; ?> - <?php echo $t['admin_dashboard']; ?></title>
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', 'Microsoft JhengHei', Arial, sans-serif;
            background: #f8f9fa;
        }

        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 32px;
        }

        .page-title {
            font-size: 2.2em;
            color: #2c3e50;
            margin-bottom: 32px;
            font-weight: bold;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-value {
            font-size: 1.8em;
            font-weight: bold;
            color: #3498db;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 0.9em;
            color: #7f8c8d;
        }

        .content-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 20px;
        }

        .search-controls {
            background: #fff;
            padding: 20px;
            border-radius: 8px 8px 0 0;
            border-bottom: 1px solid #dee2e6;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            align-items: end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }

        .form-group input,
        .form-group select {
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 14px;
        }

        .search-btn {
            background: #3498db;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            height: fit-content;
        }

        .search-btn:hover {
            background: #2980b9;
        }

        .logs-table {
            width: 100%;
            border-collapse: collapse;
        }

        .logs-table th {
            background: #ecf0f1;
            padding: 12px;
            text-align: left;
            font-weight: bold;
            color: #2c3e50;
            border-bottom: 2px solid #bdc3c7;
        }

        .logs-table td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
            vertical-align: top;
        }

        .logs-table tr:hover {
            background: #f8f9fa;
        }

        .action-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
            color: white;
        }

        .action-login { background: #28a745; }
        .action-logout { background: #6c757d; }
        .action-view_album { background: #17a2b8; }
        .action-view_photo { background: #fd7e14; }
        .action-upload_photo { background: #20c997; }
        .action-download_photo { background: #ffc107; color: #212529; }
        .action-page_view { background: #6f42c1; }
        .action-search { background: #e83e8c; }

        .status-success { color: #28a745; font-weight: bold; }
        .status-error { color: #dc3545; font-weight: bold; }

        .pagination {
            background: #fff;
            padding: 20px;
            border-radius: 0 0 8px 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }

        .pagination-info {
            color: #6c757d;
        }

        .pagination-links {
            display: flex;
            gap: 8px;
        }

        .pagination-links a, .pagination-links span {
            padding: 8px 12px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            text-decoration: none;
            color: #495057;
        }

        .pagination-links a:hover {
            background: #e9ecef;
        }

        .pagination-links .current {
            background: #3498db;
            color: #fff;
            border-color: #3498db;
        }

        .additional-data {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            cursor: pointer;
        }

        .additional-data:hover {
            white-space: normal;
            word-break: break-all;
        }

        @media (max-width: 768px) {
            .admin-container {
                padding: 16px;
            }
            
            .search-controls {
                grid-template-columns: 1fr;
            }
            
            .logs-table {
                font-size: 12px;
            }
            
            .logs-table td, .logs-table th {
                padding: 8px;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="admin-container">
        <h1 class="page-title"><?php echo $current_lang === 'zh-HK' ? '用户活动日志' : 'User Activity Logs'; ?></h1>
        
        <!-- 统计卡片 -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($total_records); ?></div>
                <div class="stat-label"><?php echo $current_lang === 'zh-HK' ? '总活动记录' : 'Total Activities'; ?></div>
            </div>
            <?php foreach (array_slice($action_stats, 0, 4) as $stat): ?>
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($stat['count']); ?></div>
                <div class="stat-label"><?php echo ucfirst(str_replace('_', ' ', $stat['action_type'])); ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="content-card">
            <!-- 搜索控件 -->
            <form method="GET" class="search-controls">
                <div class="form-group">
                    <label><?php echo $current_lang === 'zh-HK' ? '用户邮箱' : 'User Email'; ?></label>
                    <input type="text" name="search_email" value="<?php echo htmlspecialchars($search_email); ?>" placeholder="<?php echo $current_lang === 'zh-HK' ? '搜索用户邮箱...' : 'Search user email...'; ?>">
                </div>
                
                <div class="form-group">
                    <label><?php echo $current_lang === 'zh-HK' ? '操作类型' : 'Action Type'; ?></label>
                    <select name="search_action">
                        <option value=""><?php echo $current_lang === 'zh-HK' ? '所有操作' : 'All Actions'; ?></option>
                        <option value="login" <?php echo $search_action === 'login' ? 'selected' : ''; ?>>Login</option>
                        <option value="logout" <?php echo $search_action === 'logout' ? 'selected' : ''; ?>>Logout</option>
                        <option value="view_album" <?php echo $search_action === 'view_album' ? 'selected' : ''; ?>>View Album</option>
                        <option value="view_photo" <?php echo $search_action === 'view_photo' ? 'selected' : ''; ?>>View Photo</option>
                        <option value="upload_photo" <?php echo $search_action === 'upload_photo' ? 'selected' : ''; ?>>Upload Photo</option>
                        <option value="download_photo" <?php echo $search_action === 'download_photo' ? 'selected' : ''; ?>>Download Photo</option>
                        <option value="page_view" <?php echo $search_action === 'page_view' ? 'selected' : ''; ?>>Page View</option>
                        <option value="search" <?php echo $search_action === 'search' ? 'selected' : ''; ?>>Search</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label><?php echo $current_lang === 'zh-HK' ? '开始日期' : 'From Date'; ?></label>
                    <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                </div>
                
                <div class="form-group">
                    <label><?php echo $current_lang === 'zh-HK' ? '结束日期' : 'To Date'; ?></label>
                    <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                </div>
                
                <div class="form-group">
                    <label><?php echo $current_lang === 'zh-HK' ? '每页记录' : 'Records per page'; ?></label>
                    <select name="per_page">
                        <option value="20" <?php echo $per_page == 20 ? 'selected' : ''; ?>>20</option>
                        <option value="50" <?php echo $per_page == 50 ? 'selected' : ''; ?>>50</option>
                        <option value="100" <?php echo $per_page == 100 ? 'selected' : ''; ?>>100</option>
                    </select>
                </div>
                
                <button type="submit" class="search-btn"><?php echo $current_lang === 'zh-HK' ? '搜索' : 'Search'; ?></button>
                <input type="hidden" name="lang" value="<?php echo $current_lang; ?>">
            </form>

            <!-- 活动日志表格 -->
            <div style="overflow-x: auto;">
                <table class="logs-table">
                    <thead>
                        <tr>
                            <th><?php echo $current_lang === 'zh-HK' ? '时间' : 'Time'; ?></th>
                            <th><?php echo $current_lang === 'zh-HK' ? '用户' : 'User'; ?></th>
                            <th><?php echo $current_lang === 'zh-HK' ? '操作' : 'Action'; ?></th>
                            <th><?php echo $current_lang === 'zh-HK' ? '描述' : 'Description'; ?></th>
                            <th><?php echo $current_lang === 'zh-HK' ? '目标' : 'Target'; ?></th>
                            <th>IP</th>
                            <th><?php echo $current_lang === 'zh-HK' ? '状态' : 'Status'; ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($activity_logs)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 40px; color: #666;">
                                    <?php echo $current_lang === 'zh-HK' ? '没有找到活动记录' : 'No activity logs found'; ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($activity_logs as $log): ?>
                                <tr>
                                    <td style="white-space: nowrap;">
                                        <?php echo date('Y-m-d H:i:s', strtotime($log['created_at'])); ?>
                                    </td>
                                    <td>
                                        <div style="font-weight: bold;"><?php echo htmlspecialchars($log['user_name']); ?></div>
                                        <div style="font-size: 12px; color: #666;"><?php echo htmlspecialchars($log['user_email']); ?></div>
                                    </td>
                                    <td>
                                        <span class="action-badge action-<?php echo $log['action_type']; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $log['action_type'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($log['action_description']); ?>
                                        <?php if (!empty($log['additional_data'])): ?>
                                            <div class="additional-data" title="<?php echo htmlspecialchars($log['additional_data']); ?>">
                                                <small style="color: #666;"><?php echo htmlspecialchars($log['additional_data']); ?></small>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($log['target_type'] && $log['target_name']): ?>
                                            <div><?php echo htmlspecialchars($log['target_type']); ?></div>
                                            <div style="font-size: 12px; color: #666;"><?php echo htmlspecialchars($log['target_name']); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-family: monospace; font-size: 12px;">
                                        <?php echo htmlspecialchars($log['ip_address']); ?>
                                    </td>
                                    <td>
                                        <span class="status-<?php echo $log['response_status']; ?>">
                                            <?php echo ucfirst($log['response_status']); ?>
                                        </span>
                                        <?php if (!empty($log['error_message'])): ?>
                                            <div style="font-size: 12px; color: #dc3545;" title="<?php echo htmlspecialchars($log['error_message']); ?>">
                                                <?php echo htmlspecialchars(substr($log['error_message'], 0, 50)) . (strlen($log['error_message']) > 50 ? '...' : ''); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- 分页 -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <div class="pagination-info">
                        <?php echo sprintf($current_lang === 'zh-HK' ? '显示第 %d-%d 条，共 %d 条记录' : 'Showing %d-%d of %d records', 
                            $offset + 1, 
                            min($offset + $per_page, $total_records), 
                            $total_records); ?>
                    </div>
                    <div class="pagination-links">
                        <?php
                        $query_params = http_build_query(array_filter([
                            'search_email' => $search_email,
                            'search_action' => $search_action,
                            'date_from' => $date_from,
                            'date_to' => $date_to,
                            'per_page' => $per_page,
                            'lang' => $current_lang
                        ]));
                        
                        if ($page > 1): ?>
                            <a href="?page=1&<?php echo $query_params; ?>">1</a>
                            <?php if ($page > 3): ?>
                                <span>...</span>
                            <?php endif; ?>
                            <a href="?page=<?php echo $page - 1; ?>&<?php echo $query_params; ?>"><?php echo $page - 1; ?></a>
                        <?php endif; ?>
                        
                        <span class="current"><?php echo $page; ?></span>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&<?php echo $query_params; ?>"><?php echo $page + 1; ?></a>
                            <?php if ($page < $total_pages - 2): ?>
                                <span>...</span>
                            <?php endif; ?>
                            <a href="?page=<?php echo $total_pages; ?>&<?php echo $query_params; ?>"><?php echo $total_pages; ?></a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>