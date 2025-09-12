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

// 获取统计数据
try {
    // 总照片数
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM photos');
    $total_photos = $stmt->fetch()['count'];
    
    // 总相册数（从 albums 表读取）
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM albums');
    $total_albums = $stmt->fetch()['count'];
    
    // 分页和搜索参数
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $per_page = isset($_GET['per_page']) ? max(10, min(100, (int)$_GET['per_page'])) : 20;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $offset = ($page - 1) * $per_page;
    
    // 构建搜索条件
    $where_clause = '';
    $params = [];
    if (!empty($search)) {
        $where_clause = "WHERE username LIKE ? OR email LIKE ? OR ip_address LIKE ? OR status LIKE ? OR error_reason LIKE ? OR input_email LIKE ?";
        $search_param = '%' . $search . '%';
        $params = array_fill(0, 6, $search_param);
    }
    
    // 获取总记录数
    $count_sql = "SELECT COUNT(*) as total FROM login_logs $where_clause";
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $total_records = $stmt->fetch()['total'];
    $total_pages = ceil($total_records / $per_page);
    
    // 获取登录日志
    $logs_sql = "SELECT * FROM login_logs $where_clause ORDER BY login_time DESC LIMIT $per_page OFFSET $offset";
    $stmt = $pdo->prepare($logs_sql);
    $stmt->execute($params);
    $login_logs = $stmt->fetchAll();
} catch (Exception $e) {
    $total_photos = 0;
    $total_albums = 0;
    $login_logs = [];
    $total_records = 0;
    $total_pages = 0;
}
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['admin_dashboard']; ?></title>
    <style>
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
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 24px;
            margin-bottom: 40px;
        }
        .stat-card {
            background: #fff;
            border-radius: 8px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-value {
            font-size: 2.5em;
            font-weight: bold;
            color: #3498db;
            margin-bottom: 8px;
        }
        .stat-label {
            font-size: 1.1em;
            color: #7f8c8d;
        }
        .logs-section {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .logs-header {
            background: #34495e;
            color: #fff;
            padding: 20px 24px;
            font-size: 1.2em;
            font-weight: bold;
        }
        .logs-table-container {
            max-height: 500px;
            overflow-y: auto;
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
            position: sticky;
            top: 0;
        }
        .logs-table td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
        }
        .logs-table tr:hover {
            background: #f8f9fa;
        }
        .status-success {
            color: #27ae60;
            font-weight: bold;
        }
        .status-fail {
            color: #e74c3c;
            font-weight: bold;
        }
        .page-title {
            font-size: 2.2em;
            color: #2c3e50;
            margin-bottom: 32px;
            font-weight: bold;
        }
        .search-controls {
            background: #fff;
            padding: 20px;
            border-radius: 8px 8px 0 0;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            gap: 16px;
            align-items: center;
            flex-wrap: wrap;
        }
        .search-input {
            flex: 1;
            min-width: 200px;
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 1em;
        }
        .search-btn {
            background: #3498db;
            color: #fff;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1em;
        }
        .search-btn:hover {
            background: #2980b9;
        }
        .per-page-select {
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
        }
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
        @media (max-width: 768px) {
            .admin-container {
                padding: 16px;
            }
            .logs-table-container {
                overflow-x: auto;
            }
            .logs-table {
                min-width: 600px;
            }
            .search-controls {
                flex-direction: column;
                align-items: stretch;
            }
            .pagination {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="admin-container">
        <h1 class="page-title">
            <?php echo $current_lang === 'zh-HK' ? '管理員總覽' : 'Admin Overview'; ?>
        </h1>
        
        <!-- 统计组件 -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo $total_albums; ?></div>
                <div class="stat-label"><?php echo $current_lang === 'zh-HK' ? '總相冊數量' : 'Total Albums'; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $total_photos; ?></div>
                <div class="stat-label"><?php echo $current_lang === 'zh-HK' ? '總照片數量' : 'Total Photos'; ?></div>
            </div>
        </div>
        
        <!-- 登录日志 -->
        <div class="logs-section">
            <div class="logs-header">
                <?php echo $current_lang === 'zh-HK' ? '登入日誌' : 'Login Logs'; ?>
                <span style="font-size: 0.9em; font-weight: normal; float: right;">
                    <?php echo $current_lang === 'zh-HK' ? "共 $total_records 條記錄" : "$total_records records total"; ?>
                </span>
            </div>
            
            <!-- 搜索和控制 -->
            <form method="GET" class="search-controls">
                <input type="hidden" name="lang" value="<?php echo $current_lang; ?>">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="<?php echo $current_lang === 'zh-HK' ? '搜索用戶名、郵箱、IP、狀態等...' : 'Search username, email, IP, status...'; ?>" 
                       class="search-input">
                <button type="submit" class="search-btn">
                    <?php echo $current_lang === 'zh-HK' ? '搜索' : 'Search'; ?>
                </button>
                <select name="per_page" onchange="this.form.submit()" class="per-page-select">
                    <option value="10" <?php echo $per_page == 10 ? 'selected' : ''; ?>>10/<?php echo $current_lang === 'zh-HK' ? '頁' : 'page'; ?></option>
                    <option value="20" <?php echo $per_page == 20 ? 'selected' : ''; ?>>20/<?php echo $current_lang === 'zh-HK' ? '頁' : 'page'; ?></option>
                    <option value="50" <?php echo $per_page == 50 ? 'selected' : ''; ?>>50/<?php echo $current_lang === 'zh-HK' ? '頁' : 'page'; ?></option>
                    <option value="100" <?php echo $per_page == 100 ? 'selected' : ''; ?>>100/<?php echo $current_lang === 'zh-HK' ? '頁' : 'page'; ?></option>
                </select>
                <?php if (!empty($search)): ?>
                    <a href="?lang=<?php echo $current_lang; ?>" class="search-btn" style="background: #e74c3c; text-decoration: none;">
                        <?php echo $current_lang === 'zh-HK' ? '清除' : 'Clear'; ?>
                    </a>
                <?php endif; ?>
            </form>
            
            <div class="logs-table-container">
                <table class="logs-table">
                    <thead>
                        <tr>
                            <th><?php echo $current_lang === 'zh-HK' ? '用戶名' : 'Username'; ?></th>
                            <th><?php echo $current_lang === 'zh-HK' ? '電郵' : 'Email'; ?></th>
                            <th><?php echo $current_lang === 'zh-HK' ? 'IP地址' : 'IP Address'; ?></th>
                            <th><?php echo $current_lang === 'zh-HK' ? '登入時間' : 'Login Time'; ?></th>
                            <th><?php echo $current_lang === 'zh-HK' ? '狀態' : 'Status'; ?></th>
                            <th><?php echo $current_lang === 'zh-HK' ? '錯誤原因' : 'Error Reason'; ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($login_logs)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: #7f8c8d; padding: 24px;">
                                    <?php echo $current_lang === 'zh-HK' ? '暫無登入記錄' : 'No login records'; ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($login_logs as $log): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($log['username'] ?: '-'); ?></td>
                                    <td><?php echo htmlspecialchars($log['email'] ?: $log['input_email'] ?: '-'); ?></td>
                                    <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                                    <td><?php echo date('Y-m-d H:i:s', strtotime($log['login_time'])); ?></td>
                                    <td class="<?php echo $log['status'] === 'success' ? 'status-success' : 'status-fail'; ?>">
                                        <?php echo $log['status'] === 'success' ? 
                                            ($current_lang === 'zh-HK' ? '成功' : 'Success') : 
                                            ($current_lang === 'zh-HK' ? '失敗' : 'Failed'); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($log['error_reason'] ?: '-'); ?></td>
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
                    <?php 
                    $start = ($page - 1) * $per_page + 1;
                    $end = min($page * $per_page, $total_records);
                    echo $current_lang === 'zh-HK' ? 
                        "顯示第 $start - $end 條，共 $total_records 條" : 
                        "Showing $start - $end of $total_records records";
                    ?>
                </div>
                <div class="pagination-links">
                    <?php
                    $base_url = "?lang=$current_lang&search=" . urlencode($search) . "&per_page=$per_page&page=";
                    
                    // 上一页
                    if ($page > 1): ?>
                        <a href="<?php echo $base_url . ($page - 1); ?>">&laquo; <?php echo $current_lang === 'zh-HK' ? '上一頁' : 'Prev'; ?></a>
                    <?php endif;
                    
                    // 页码
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    
                    if ($start_page > 1): ?>
                        <a href="<?php echo $base_url . '1'; ?>">1</a>
                        <?php if ($start_page > 2): ?>
                            <span>...</span>
                        <?php endif;
                    endif;
                    
                    for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="current"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="<?php echo $base_url . $i; ?>"><?php echo $i; ?></a>
                        <?php endif;
                    endfor;
                    
                    if ($end_page < $total_pages): ?>
                        <?php if ($end_page < $total_pages - 1): ?>
                            <span>...</span>
                        <?php endif; ?>
                        <a href="<?php echo $base_url . $total_pages; ?>"><?php echo $total_pages; ?></a>
                    <?php endif;
                    
                    // 下一页
                    if ($page < $total_pages): ?>
                        <a href="<?php echo $base_url . ($page + 1); ?>"><?php echo $current_lang === 'zh-HK' ? '下一頁' : 'Next'; ?> &raquo;</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
