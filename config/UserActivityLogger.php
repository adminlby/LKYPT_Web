<?php
/**
 * 用户活动日志记录器
 * 专门用于记录普通用户的行为日志（不包括管理员操作）
 */
class UserActivityLogger {
    private $pdo;
    private $lang;
    private $t;
    
    public function __construct($pdo, $lang = 'zh-HK', $translations = null) {
        $this->pdo = $pdo;
        $this->lang = $lang;
        $this->t = $translations;
        
        // 如果没有传入翻译，则加载默认翻译
        if (!$this->t) {
            require_once __DIR__ . '/lang.php';
            $this->t = $langs[$lang] ?? $langs['zh-HK'];
        }
    }
    
    /**
     * 获取活动描述的多语言文本
     */
    private function getActivityText($key, $params = []) {
        $text = $this->t[$key] ?? $key;
        
        // 替换参数
        if (!empty($params)) {
            foreach ($params as $placeholder => $value) {
                $text = str_replace('{' . $placeholder . '}', $value, $text);
            }
        }
        
        return $text;
    }
    
    /**
     * 记录用户活动日志
     * @param string $user_email 用户邮箱
     * @param string $user_name 用户姓名
     * @param string $action_type 操作类型
     * @param string $action_description 操作描述
     * @param array $options 额外选项
     */
    public function log($user_email, $user_name, $action_type, $action_description = '', $options = []) {
        try {
            // 获取基本信息
            $ip_address = $this->getClientIp();
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $session_id = session_id();
            $referrer_url = $_SERVER['HTTP_REFERER'] ?? '';
            $request_url = $this->getCurrentUrl();
            $request_method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
            
            // 记录开始时间
            $start_time = microtime(true);
            
            // 准备数据
            $data = [
                'user_email' => $user_email,
                'user_name' => $user_name,
                'action_type' => $action_type,
                'action_description' => $action_description,
                'target_type' => $options['target_type'] ?? null,
                'target_id' => $options['target_id'] ?? null,
                'target_name' => $options['target_name'] ?? null,
                'ip_address' => $ip_address,
                'user_agent' => $user_agent,
                'session_id' => $session_id,
                'referrer_url' => $referrer_url,
                'request_url' => $request_url,
                'request_method' => $request_method,
                'response_status' => $options['response_status'] ?? 'success',
                'error_message' => $options['error_message'] ?? null,
                'execution_time' => $options['execution_time'] ?? null,
                'additional_data' => isset($options['additional_data']) ? json_encode($options['additional_data']) : null
            ];
            
            // 插入数据库
            $sql = "INSERT INTO user_activity_logs (
                user_email, user_name, action_type, action_description, target_type, target_id, target_name,
                ip_address, user_agent, session_id, referrer_url, request_url, request_method,
                response_status, error_message, execution_time, additional_data
            ) VALUES (
                :user_email, :user_name, :action_type, :action_description, :target_type, :target_id, :target_name,
                :ip_address, :user_agent, :session_id, :referrer_url, :request_url, :request_method,
                :response_status, :error_message, :execution_time, :additional_data
            )";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($data);
            
        } catch (PDOException $e) {
            // 静默处理日志记录错误，避免影响主业务流程
            error_log("UserActivityLogger Error: " . $e->getMessage());
        }
    }
    
    /**
     * 记录用户登录
     */
    public function logLogin($user_email, $user_name, $login_method = 'google_oauth') {
        $description = $this->getActivityText('activity_login', ['method' => $login_method]);
        $this->log($user_email, $user_name, 'login', $description, [
            'additional_data' => ['login_method' => $login_method]
        ]);
    }
    
    /**
     * 记录用户登出
     */
    public function logLogout($user_email, $user_name) {
        $description = $this->getActivityText('activity_logout');
        $this->log($user_email, $user_name, 'logout', $description);
    }
    
    /**
     * 记录查看相册
     */
    public function logViewAlbum($user_email, $user_name, $album_id, $album_name = '') {
        $description = $this->getActivityText('activity_view_album', ['name' => $album_name]);
        $this->log($user_email, $user_name, 'view_album', $description, [
            'target_type' => 'album',
            'target_id' => $album_id,
            'target_name' => $album_name
        ]);
    }
    
    /**
     * 记录查看照片
     */
    public function logViewPhoto($user_email, $user_name, $photo_id, $photo_name = '') {
        $description = $this->getActivityText('activity_view_photo', ['name' => $photo_name]);
        $this->log($user_email, $user_name, 'view_photo', $description, [
            'target_type' => 'photo',
            'target_id' => $photo_id,
            'target_name' => $photo_name
        ]);
    }
    
    /**
     * 记录上传照片
     */
    public function logUploadPhoto($user_email, $user_name, $photo_id, $photo_name, $album_id = null) {
        $description = $this->getActivityText('activity_upload_photo', ['name' => $photo_name]);
        $this->log($user_email, $user_name, 'upload_photo', $description, [
            'target_type' => 'photo',
            'target_id' => $photo_id,
            'target_name' => $photo_name,
            'additional_data' => ['album_id' => $album_id]
        ]);
    }
    
    /**
     * 记录下载照片
     */
    public function logDownloadPhoto($user_email, $user_name, $photo_id, $photo_name = '', $album_name = '') {
        $display_name = $photo_name;
        if ($album_name && $photo_name) {
            $display_name = "({$album_name}) {$photo_name}";
        } elseif ($album_name) {
            $display_name = "({$album_name}) ID#{$photo_id}";
        } elseif (!$photo_name) {
            $display_name = "ID#{$photo_id}";
        }
        
        $description = $this->getActivityText('activity_download_photo', ['name' => $display_name]);
        $this->log($user_email, $user_name, 'download_photo', $description, [
            'target_type' => 'photo',
            'target_id' => $photo_id,
            'target_name' => $photo_name,
            'additional_data' => ['album_name' => $album_name]
        ]);
    }
    
    /**
     * 记录搜索操作
     */
    public function logSearch($user_email, $user_name, $search_query, $search_type = 'general') {
        $description = $this->getActivityText('activity_search', ['query' => $search_query]);
        $this->log($user_email, $user_name, 'search', $description, [
            'additional_data' => [
                'search_query' => $search_query,
                'search_type' => $search_type
            ]
        ]);
    }
    
    /**
     * 记录页面访问
     */
    public function logPageView($user_email, $user_name, $page_name) {
        $description = $this->getActivityText('activity_page_view', ['page' => $page_name]);
        $this->log($user_email, $user_name, 'page_view', $description);
    }
    
    /**
     * 记录错误操作
     */
    public function logError($user_email, $user_name, $action_type, $error_message) {
        $description = $this->getActivityText('activity_error', ['error' => $error_message]);
        $this->log($user_email, $user_name, $action_type, $description, [
            'response_status' => 'error',
            'error_message' => $error_message
        ]);
    }
    
    /**
     * 获取客户端真实IP地址
     */
    private function getClientIp() {
        $ip_keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                // 处理多个IP的情况（X-Forwarded-For可能包含多个IP）
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                // 验证IP格式
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    /**
     * 获取当前URL
     */
    private function getCurrentUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        return $protocol . '://' . $host . $uri;
    }
    
    /**
     * 获取用户活动统计
     */
    public function getUserActivityStats($user_email, $days = 30) {
        try {
            $sql = "SELECT 
                        action_type,
                        COUNT(*) as count,
                        DATE(created_at) as date
                    FROM user_activity_logs 
                    WHERE user_email = ? 
                        AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                    GROUP BY action_type, DATE(created_at)
                    ORDER BY created_at DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$user_email, $days]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("UserActivityLogger Stats Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * 获取用户最近活动
     */
    public function getUserRecentActivity($user_email, $limit = 50) {
        try {
            $sql = "SELECT * FROM user_activity_logs 
                    WHERE user_email = ? 
                    ORDER BY created_at DESC 
                    LIMIT ?";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$user_email, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("UserActivityLogger Recent Activity Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * 清理旧日志（可定期执行）
     */
    public function cleanOldLogs($days = 365) {
        try {
            $sql = "DELETE FROM user_activity_logs 
                    WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$days]);
            return $stmt->rowCount();
            
        } catch (PDOException $e) {
            error_log("UserActivityLogger Clean Error: " . $e->getMessage());
            return 0;
        }
    }
}
?>