<?php
/**
 * 用户活动日志记录器
 * 专门用于记录普通用户的行为日志（不包括管理员操作）
 */
class UserActivityLogger {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
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
        $this->log($user_email, $user_name, 'login', "用户通过 {$login_method} 登录", [
            'additional_data' => ['login_method' => $login_method]
        ]);
    }
    
    /**
     * 记录用户登出
     */
    public function logLogout($user_email, $user_name) {
        $this->log($user_email, $user_name, 'logout', '用户退出登录');
    }
    
    /**
     * 记录查看相册
     */
    public function logViewAlbum($user_email, $user_name, $album_id, $album_name = '') {
        $this->log($user_email, $user_name, 'view_album', "查看相册: {$album_name}", [
            'target_type' => 'album',
            'target_id' => $album_id,
            'target_name' => $album_name
        ]);
    }
    
    /**
     * 记录查看照片
     */
    public function logViewPhoto($user_email, $user_name, $photo_id, $photo_name = '') {
        $this->log($user_email, $user_name, 'view_photo', "查看照片: {$photo_name}", [
            'target_type' => 'photo',
            'target_id' => $photo_id,
            'target_name' => $photo_name
        ]);
    }
    
    /**
     * 记录上传照片
     */
    public function logUploadPhoto($user_email, $user_name, $photo_id, $photo_name, $album_id = null) {
        $this->log($user_email, $user_name, 'upload_photo', "上传照片: {$photo_name}", [
            'target_type' => 'photo',
            'target_id' => $photo_id,
            'target_name' => $photo_name,
            'additional_data' => ['album_id' => $album_id]
        ]);
    }
    
    /**
     * 记录搜索操作
     */
    public function logSearch($user_email, $user_name, $search_query, $search_type = 'general') {
        $this->log($user_email, $user_name, 'search', "搜索: {$search_query}", [
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
        $this->log($user_email, $user_name, 'page_view', "访问页面: {$page_name}");
    }
    
    /**
     * 记录错误操作
     */
    public function logError($user_email, $user_name, $action_type, $error_message) {
        $this->log($user_email, $user_name, $action_type, "操作失败: {$error_message}", [
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