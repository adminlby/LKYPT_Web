<?php
// 设置错误报告用于调试
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// 使用绝对路径
$root_path = dirname(__DIR__);
require_once $root_path . '/config/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// 检查用户是否已登录
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['email'])) {
    echo json_encode(['agreed' => false, 'needsUpdate' => true, 'message' => '用户未登录']);
    exit;
}

try {
    $user_email = $_SESSION['user']['email'];
    $current_version = '1.0'; // 当前协议版本
    
    // 查询用户最新的协议同意记录
    $stmt = $pdo->prepare('
        SELECT agreement_version, agreed_at 
        FROM user_agreement_status 
        WHERE user_email = ?
        ORDER BY agreed_at DESC 
        LIMIT 1
    ');
    $stmt->execute([$user_email]);
    $agreement = $stmt->fetch();
    
    if (!$agreement) {
        // 首次使用，需要阅读协议
        echo json_encode([
            'agreed' => false, 
            'needsUpdate' => true,
            'message' => '首次使用需要阅读协议',
            'reason' => 'first_time'
        ]);
        exit;
    }
    
    // 检查协议版本是否为最新
    if ($agreement['agreement_version'] !== $current_version) {
        echo json_encode([
            'agreed' => true, 
            'needsUpdate' => true,
            'message' => '协议已更新，需要重新阅读',
            'reason' => 'version_updated',
            'last_agreed' => $agreement['agreed_at']
        ]);
        exit;
    }
    
    // 检查是否超过1周（7天）
    $agreed_time = new DateTime($agreement['agreed_at']);
    $current_time = new DateTime();
    $interval = $current_time->diff($agreed_time);
    $days_since_agreed = $interval->days;
    
    if ($days_since_agreed >= 7) {
        echo json_encode([
            'agreed' => true, 
            'needsUpdate' => true,
            'message' => '超过一周，需要重新确认协议',
            'reason' => 'weekly_reminder',
            'last_agreed' => $agreement['agreed_at'],
            'days_since' => $days_since_agreed
        ]);
        exit;
    }
    
    // 协议状态正常，无需重新阅读
    echo json_encode([
        'agreed' => true, 
        'needsUpdate' => false,
        'message' => '协议状态正常',
        'last_agreed' => $agreement['agreed_at'],
        'days_since' => $days_since_agreed,
        'version' => $agreement['agreement_version']
    ]);
    
} catch (PDOException $e) {
    error_log('Database error in check_agreement.php: ' . $e->getMessage());
    echo json_encode([
        'agreed' => false, 
        'needsUpdate' => true, 
        'message' => '数据库错误',
        'reason' => 'database_error'
    ]);
} catch (Exception $e) {
    error_log('Error in check_agreement.php: ' . $e->getMessage());
    echo json_encode([
        'agreed' => false, 
        'needsUpdate' => true, 
        'message' => '服务器错误',
        'reason' => 'server_error'
    ]);
}
?>