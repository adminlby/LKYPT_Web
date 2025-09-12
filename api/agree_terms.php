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
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// 检查用户是否已登录
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['email'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => '用户未登录']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => '方法不允许']);
    exit;
}

try {
    // 获取POST数据
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['action']) || $input['action'] !== 'agree') {
        echo json_encode(['success' => false, 'message' => '无效的请求数据']);
        exit;
    }
    
    $user_email = $_SESSION['user']['email'];
    $agreement_version = $input['version'] ?? '1.0';
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    // 检查是否已经同意过
    $check_stmt = $pdo->prepare('
        SELECT id, agreed_at 
        FROM user_agreement_status 
        WHERE user_email = ? AND agreement_version = ?
        ORDER BY agreed_at DESC 
        LIMIT 1
    ');
    $check_stmt->execute([$user_email, $agreement_version]);
    $existing = $check_stmt->fetch();
    
    // 如果已经同意过，更新记录；否则插入新记录
    if ($existing) {
        $update_stmt = $pdo->prepare('
            UPDATE user_agreement_status 
            SET agreed_at = CURRENT_TIMESTAMP, ip_address = ?, user_agent = ?
            WHERE id = ?
        ');
        $update_stmt->execute([$ip_address, $user_agent, $existing['id']]);
    } else {
        $insert_stmt = $pdo->prepare('
            INSERT INTO user_agreement_status (user_email, agreement_version, ip_address, user_agent)
            VALUES (?, ?, ?, ?)
        ');
        $insert_stmt->execute([$user_email, $agreement_version, $ip_address, $user_agent]);
    }
    
    // 记录到用户活动日志
    if (class_exists('UserActivityLogger')) {
        UserActivityLogger::log('agreement_accepted', [
            'version' => $agreement_version,
            'ip_address' => $ip_address
        ]);
    }
    
    echo json_encode([
        'success' => true, 
        'message' => '协议同意状态已保存',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (PDOException $e) {
    error_log('Database error in agree_terms.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => '数据库错误']);
} catch (Exception $e) {
    error_log('Error in agree_terms.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => '服务器错误']);
}
?>