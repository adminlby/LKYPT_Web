<?php
/**
 * 操作日志记录工具类
 * 用于记录管理员在后台的各种操作行为
 */
class OperationLogger {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * 记录操作日志
     * 
     * @param string $user_id 操作用户邮箱
     * @param string $user_name 操作用户名
     * @param string $operation_type 操作类型：create/update/delete/login/logout/view
     * @param string $module 操作模块：album/photo/user/system
     * @param string $target_id 操作目标ID（如相册ID、照片ID等）
     * @param string $target_title 操作目标标题/名称
     * @param string $operation_desc 操作描述
     * @param array $before_data 操作前数据
     * @param array $after_data 操作后数据
     * @param string $status 操作状态：success/failed/warning
     * @param string $error_message 错误信息（如果操作失败）
     * @return bool 是否记录成功
     */
    public function log($user_id, $user_name, $operation_type, $module, $target_id = null, $target_title = null, 
                       $operation_desc = '', $before_data = null, $after_data = null, $status = 'success', $error_message = null) {
        try {
            // 获取IP地址
            $ip_address = $this->getClientIP();
            
            // 获取用户代理
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            // 准备数据
            $before_json = $before_data ? json_encode($before_data, JSON_UNESCAPED_UNICODE) : null;
            $after_json = $after_data ? json_encode($after_data, JSON_UNESCAPED_UNICODE) : null;
            
            $sql = "INSERT INTO operation_logs (
                        user_id, user_name, operation_type, module, target_id, target_title, 
                        operation_desc, before_data, after_data, ip_address, user_agent, 
                        operation_time, status, error_message
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?)";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                $user_id, $user_name, $operation_type, $module, $target_id, $target_title,
                $operation_desc, $before_json, $after_json, $ip_address, $user_agent,
                $status, $error_message
            ]);
            
            return $result;
        } catch (Exception $e) {
            // 记录日志失败，避免影响主要业务逻辑
            error_log("操作日志记录失败: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 快捷方法：记录相册操作
     */
    public function logAlbumOperation($user_id, $user_name, $operation_type, $album_id, $album_title, $description = '', $before_data = null, $after_data = null, $status = 'success', $error_message = null) {
        $operation_desc = $this->getAlbumOperationDesc($operation_type, $album_title, $description);
        return $this->log($user_id, $user_name, $operation_type, 'album', $album_id, $album_title, $operation_desc, $before_data, $after_data, $status, $error_message);
    }
    
    /**
     * 快捷方法：记录照片操作
     */
    public function logPhotoOperation($user_id, $user_name, $operation_type, $photo_id, $photo_title, $description = '', $before_data = null, $after_data = null, $status = 'success', $error_message = null) {
        $operation_desc = $this->getPhotoOperationDesc($operation_type, $photo_title, $description);
        return $this->log($user_id, $user_name, $operation_type, 'photo', $photo_id, $photo_title, $operation_desc, $before_data, $after_data, $status, $error_message);
    }
    
    /**
     * 快捷方法：记录系统操作
     */
    public function logSystemOperation($user_id, $user_name, $operation_type, $description = '') {
        $operation_desc = $this->getSystemOperationDesc($operation_type, $description);
        return $this->log($user_id, $user_name, $operation_type, 'system', null, null, $operation_desc);
    }
    
    /**
     * 快捷方法：记录登录操作
     */
    public function logLogin($user_id, $user_name, $status = 'success', $error_message = null) {
        $operation_desc = $status === 'success' ? '用户成功登录管理后台' : '用户登录管理后台失败';
        return $this->log($user_id, $user_name, 'login', 'system', null, null, $operation_desc, null, null, $status, $error_message);
    }
    
    /**
     * 快捷方法：记录登出操作
     */
    public function logLogout($user_id, $user_name) {
        return $this->log($user_id, $user_name, 'logout', 'system', null, null, '用户登出管理后台');
    }
    
    /**
     * 获取客户端IP地址
     */
    private function getClientIP() {
        $ip_keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                $ip = trim($ips[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    /**
     * 生成相册操作描述
     */
    private function getAlbumOperationDesc($operation_type, $album_title, $description) {
        switch ($operation_type) {
            case 'create':
                return "创建了相册「{$album_title}」" . ($description ? "，描述：{$description}" : '');
            case 'update':
                return "更新了相册「{$album_title}」" . ($description ? "，{$description}" : '');
            case 'delete':
                return "删除了相册「{$album_title}」";
            case 'view':
                return "查看了相册「{$album_title}」";
            default:
                return "对相册「{$album_title}」执行了{$operation_type}操作";
        }
    }
    
    /**
     * 生成照片操作描述
     */
    private function getPhotoOperationDesc($operation_type, $photo_title, $description) {
        switch ($operation_type) {
            case 'create':
                return "上传了照片「{$photo_title}」" . ($description ? "，{$description}" : '');
            case 'update':
                return "更新了照片「{$photo_title}」" . ($description ? "，{$description}" : '');
            case 'delete':
                return "删除了照片「{$photo_title}」";
            case 'view':
                return "查看了照片「{$photo_title}」";
            default:
                return "对照片「{$photo_title}」执行了{$operation_type}操作";
        }
    }
    
    /**
     * 生成系统操作描述
     */
    private function getSystemOperationDesc($operation_type, $description) {
        switch ($operation_type) {
            case 'login':
                return "登录管理后台" . ($description ? "，{$description}" : '');
            case 'logout':
                return "登出管理后台" . ($description ? "，{$description}" : '');
            case 'view':
                return "访问了" . ($description ?: '管理后台页面');
            default:
                return $description ?: "执行了{$operation_type}操作";
        }
    }
    
    /**
     * 获取操作日志列表
     */
    public function getLogs($page = 1, $per_page = 20, $filters = []) {
        $offset = ($page - 1) * $per_page;
        
        // 构建查询条件
        $where_conditions = [];
        $params = [];
        
        if (!empty($filters['user_id'])) {
            $where_conditions[] = "user_id LIKE ?";
            $params[] = '%' . $filters['user_id'] . '%';
        }
        
        if (!empty($filters['operation_type'])) {
            $where_conditions[] = "operation_type = ?";
            $params[] = $filters['operation_type'];
        }
        
        if (!empty($filters['module'])) {
            $where_conditions[] = "module = ?";
            $params[] = $filters['module'];
        }
        
        if (!empty($filters['status'])) {
            $where_conditions[] = "status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['date_from'])) {
            $where_conditions[] = "operation_time >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }
        
        if (!empty($filters['date_to'])) {
            $where_conditions[] = "operation_time <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }
        
        $where_clause = empty($where_conditions) ? '' : 'WHERE ' . implode(' AND ', $where_conditions);
        
        // 获取总数
        $count_sql = "SELECT COUNT(*) FROM operation_logs {$where_clause}";
        $count_stmt = $this->pdo->prepare($count_sql);
        $count_stmt->execute($params);
        $total = $count_stmt->fetchColumn();
        
        // 获取列表
        $list_sql = "SELECT * FROM operation_logs {$where_clause} ORDER BY operation_time DESC LIMIT {$per_page} OFFSET {$offset}";
        $list_stmt = $this->pdo->prepare($list_sql);
        $list_stmt->execute($params);
        $logs = $list_stmt->fetchAll();
        
        return [
            'total' => $total,
            'data' => $logs,
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => ceil($total / $per_page)
        ];
    }
}
?>