<?php
session_start();
require_once '../config/config.php';
require_once '../config/lang.php';

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

// 处理表单提交
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $title = trim($_POST['title']);
                $description = trim($_POST['description']);
                $created_at = trim($_POST['created_at']);
                
                if (!empty($title)) {
                    try {
                        // 如果用户没有选择日期，使用当前时间
                        if (empty($created_at)) {
                            $stmt = $pdo->prepare("INSERT INTO albums (title, description, created_at) VALUES (?, ?, NOW())");
                            $stmt->execute([$title, $description]);
                        } else {
                            // 验证日期格式并使用用户选择的日期
                            $date = DateTime::createFromFormat('Y-m-d\TH:i', $created_at);
                            if ($date === false) {
                                // 如果日期格式无效，使用当前时间
                                $stmt = $pdo->prepare("INSERT INTO albums (title, description, created_at) VALUES (?, ?, NOW())");
                                $stmt->execute([$title, $description]);
                            } else {
                                $formatted_date = $date->format('Y-m-d H:i:s');
                                $stmt = $pdo->prepare("INSERT INTO albums (title, description, created_at) VALUES (?, ?, ?)");
                                $stmt->execute([$title, $description, $formatted_date]);
                            }
                        }
                        $message = $t['album_created'];
                        $message_type = 'success';
                    } catch (PDOException $e) {
                        $message = "Error: " . $e->getMessage();
                        $message_type = 'error';
                    }
                }
                break;
                
            case 'update':
                $id = $_POST['id'];
                $title = trim($_POST['title']);
                $description = trim($_POST['description']);
                $created_at = trim($_POST['created_at']);
                
                if (!empty($title) && !empty($id)) {
                    try {
                        // 如果用户没有提供日期，只更新标题和描述
                        if (empty($created_at)) {
                            $stmt = $pdo->prepare("UPDATE albums SET title = ?, description = ? WHERE id = ?");
                            $stmt->execute([$title, $description, $id]);
                        } else {
                            // 验证日期格式并更新所有字段
                            $date = DateTime::createFromFormat('Y-m-d\TH:i', $created_at);
                            if ($date === false) {
                                // 如果日期格式无效，只更新标题和描述
                                $stmt = $pdo->prepare("UPDATE albums SET title = ?, description = ? WHERE id = ?");
                                $stmt->execute([$title, $description, $id]);
                            } else {
                                $formatted_date = $date->format('Y-m-d H:i:s');
                                $stmt = $pdo->prepare("UPDATE albums SET title = ?, description = ?, created_at = ? WHERE id = ?");
                                $stmt->execute([$title, $description, $formatted_date, $id]);
                            }
                        }
                        $message = $t['album_updated'];
                        $message_type = 'success';
                    } catch (PDOException $e) {
                        $message = "Error: " . $e->getMessage();
                        $message_type = 'error';
                    }
                }
                break;
                
            case 'delete':
                $id = $_POST['id'];
                if (!empty($id)) {
                    try {
                        // 先删除相册中的所有照片
                        $stmt = $pdo->prepare("DELETE FROM photos WHERE album_id = ?");
                        $stmt->execute([$id]);
                        
                        // 然后删除相册
                        $stmt = $pdo->prepare("DELETE FROM albums WHERE id = ?");
                        $stmt->execute([$id]);
                        
                        $message = $t['album_deleted'];
                        $message_type = 'success';
                    } catch (PDOException $e) {
                        $message = "Error: " . $e->getMessage();
                        $message_type = 'error';
                    }
                }
                break;
        }
    }
}

// 分页和搜索参数
$page = max(1, $_GET['page'] ?? 1);
$per_page = max(10, min(100, $_GET['per_page'] ?? 20));
$search = $_GET['search'] ?? '';
$offset = ($page - 1) * $per_page;

// 构建搜索查询
$where_clause = '';
$params = [];
if (!empty($search)) {
    $where_clause = "WHERE title LIKE ? OR description LIKE ?";
    $params = ["%$search%", "%$search%"];
}

// 获取总记录数
$count_sql = "SELECT COUNT(*) FROM albums $where_clause";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $per_page);

// 获取相册列表
$sql = "SELECT a.*, 
        (SELECT COUNT(*) FROM photos p WHERE p.album_id = a.id) as photo_count
        FROM albums a $where_clause 
        ORDER BY a.created_at DESC 
        LIMIT $per_page OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$albums = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['album_management']; ?> - <?php echo $t['admin_dashboard']; ?></title>
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
            max-width: 1200px;
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

        .create-form {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
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
        .form-group textarea {
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

        .albums-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .albums-table th,
        .albums-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .albums-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }

        .albums-table tr:hover {
            background: #f8f9fa;
        }

        .album-title {
            font-weight: 600;
            color: #333;
        }

        .album-description {
            color: #666;
            font-size: 14px;
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .photo-count {
            background: #667eea;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }

        .actions {
            display: flex;
            gap: 8px;
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
            margin: 10% auto;
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-header h2 {
            margin: 0;
        }

        .close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: #000;
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

            .albums-table {
                font-size: 14px;
            }

            .albums-table th,
            .albums-table td {
                padding: 8px;
            }

            .actions {
                flex-direction: column;
            }

            .btn-sm {
                padding: 8px 12px;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="admin-container">
        <div class="admin-header">
            <h1><?php echo $t['album_management']; ?></h1>
            <div class="breadcrumb">
                <a href="dashboard.php"><?php echo $t['admin_dashboard']; ?></a> / <?php echo $t['album_management']; ?>
            </div>
        </div>

        <div class="content-card">
            <?php if ($message): ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- 创建相册表单 -->
            <div class="create-form">
                <h3><?php echo $t['create_album']; ?></h3>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="create">
                    <div class="form-group">
                        <label for="title"><?php echo $t['album_title']; ?></label>
                        <input type="text" id="title" name="title" required>
                    </div>
                    <div class="form-group">
                        <label for="description"><?php echo $t['album_description']; ?></label>
                        <textarea id="description" name="description"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="created_at"><?php echo $t['custom_date']; ?></label>
                        <input type="datetime-local" id="created_at" name="created_at">
                        <small style="color: #666; font-size: 0.9em;"><?php echo $t['date_optional']; ?></small>
                    </div>
                    <button type="submit" class="btn btn-success"><?php echo $t['create_album']; ?></button>
                </form>
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
                        <option value="100" <?php echo $per_page == 100 ? 'selected' : ''; ?>>100 <?php echo $t['results']; ?></option>
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

            <!-- 相册列表 -->
            <?php if (count($albums) > 0): ?>
                <table class="albums-table">
                    <thead>
                        <tr>
                            <th><?php echo $t['album_title']; ?></th>
                            <th><?php echo $t['album_description']; ?></th>
                            <th><?php echo $t['photo_count_in_album']; ?></th>
                            <th><?php echo $t['created_date']; ?></th>
                            <th><?php echo $t['actions']; ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($albums as $album): ?>
                            <tr>
                                <td class="album-title"><?php echo htmlspecialchars($album['title']); ?></td>
                                <td class="album-description" title="<?php echo htmlspecialchars($album['description']); ?>">
                                    <?php echo htmlspecialchars($album['description']); ?>
                                </td>
                                <td>
                                    <span class="photo-count"><?php echo $album['photo_count']; ?></span>
                                </td>
                                <td><?php echo date('Y-m-d H:i', strtotime($album['created_at'])); ?></td>
                                <td class="actions">
                                    <button onclick="editAlbum(<?php echo $album['id']; ?>, '<?php echo addslashes($album['title']); ?>', '<?php echo addslashes($album['description']); ?>', '<?php echo date('Y-m-d\TH:i', strtotime($album['created_at'])); ?>')" 
                                            class="btn btn-primary btn-sm"><?php echo $t['edit']; ?></button>
                                    <button onclick="deleteAlbum(<?php echo $album['id']; ?>, '<?php echo addslashes($album['title']); ?>')" 
                                            class="btn btn-danger btn-sm"><?php echo $t['delete']; ?></button>
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

                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        if ($start_page > 1) {
                            echo '<a href="?page=1&per_page=' . $per_page . '&search=' . urlencode($search) . '">1</a>';
                            if ($start_page > 2) {
                                echo '<span>...</span>';
                            }
                        }
                        
                        for ($i = $start_page; $i <= $end_page; $i++) {
                            if ($i == $page) {
                                echo '<span class="current">' . $i . '</span>';
                            } else {
                                echo '<a href="?page=' . $i . '&per_page=' . $per_page . '&search=' . urlencode($search) . '">' . $i . '</a>';
                            }
                        }
                        
                        if ($end_page < $total_pages) {
                            if ($end_page < $total_pages - 1) {
                                echo '<span>...</span>';
                            }
                            echo '<a href="?page=' . $total_pages . '&per_page=' . $per_page . '&search=' . urlencode($search) . '">' . $total_pages . '</a>';
                        }
                        ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page+1; ?>&per_page=<?php echo $per_page; ?>&search=<?php echo urlencode($search); ?>"><?php echo $t['next']; ?></a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="no-data">
                    <?php echo $t['no_albums']; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 编辑相册模态框 -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><?php echo $t['edit_album']; ?></h2>
                <span class="close" onclick="closeEditModal()">&times;</span>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-group">
                    <label for="edit_title"><?php echo $t['album_title']; ?></label>
                    <input type="text" id="edit_title" name="title" required>
                </div>
                <div class="form-group">
                    <label for="edit_description"><?php echo $t['album_description']; ?></label>
                    <textarea id="edit_description" name="description"></textarea>
                </div>
                <div class="form-group">
                    <label for="edit_created_at"><?php echo $t['created_date']; ?></label>
                    <input type="datetime-local" id="edit_created_at" name="created_at">
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" onclick="closeEditModal()" class="btn"><?php echo $t['cancel']; ?></button>
                    <button type="submit" class="btn btn-success"><?php echo $t['save']; ?></button>
                </div>
            </form>
        </div>
    </div>

    <!-- 删除确认模态框 -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><?php echo $t['delete_album']; ?></h2>
                <span class="close" onclick="closeDeleteModal()">&times;</span>
            </div>
            <p><?php echo $t['confirm_delete']; ?></p>
            <p><strong id="delete_album_title"></strong></p>
            <form method="POST" action="">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="delete_id">
                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" onclick="closeDeleteModal()" class="btn"><?php echo $t['cancel']; ?></button>
                    <button type="submit" class="btn btn-danger"><?php echo $t['delete']; ?></button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function editAlbum(id, title, description, createdAt) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_title').value = title;
            document.getElementById('edit_description').value = description;
            document.getElementById('edit_created_at').value = createdAt;
            document.getElementById('editModal').style.display = 'block';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        function deleteAlbum(id, title) {
            document.getElementById('delete_id').value = id;
            document.getElementById('delete_album_title').textContent = title;
            document.getElementById('deleteModal').style.display = 'block';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }

        // 点击模态框外部关闭
        window.onclick = function(event) {
            const editModal = document.getElementById('editModal');
            const deleteModal = document.getElementById('deleteModal');
            if (event.target == editModal) {
                editModal.style.display = 'none';
            }
            if (event.target == deleteModal) {
                deleteModal.style.display = 'none';
            }
        }
    </script>
</body>
</html>