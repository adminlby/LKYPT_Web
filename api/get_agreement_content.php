<?php
// 设置错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// 使用绝对路径
$root_path = dirname(__DIR__);
require_once $root_path . '/config/config.php';
require_once $root_path . '/config/lang.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    // 检查语言配置是否正确加载
    if (!isset($langs) || !is_array($langs)) {
        throw new Exception('语言配置未正确加载，$langs变量不存在');
    }
    
    // 创建兼容的$lang变量
    $lang = [
        'zh' => $langs['zh-HK'] ?? [],
        'en' => $langs['en'] ?? []
    ];
    
    if (empty($lang['zh']) && empty($lang['en'])) {
        throw new Exception('语言配置为空');
    }
    
    // 获取当前语言
    $current_lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'zh';
    
    // 验证语言是否存在
    if (!isset($lang[$current_lang])) {
        $current_lang = 'zh'; // 默认使用中文
    }
    
    $t = $lang[$current_lang];
    
    // 检查必要的翻译键是否存在
    $required_keys = ['user_agreement_title', 'help_title', 'help_subtitle'];
    foreach ($required_keys as $key) {
        if (!isset($t[$key])) {
            throw new Exception("缺少必要的翻译键: $key");
        }
    }
    
    // 构建协议内容HTML
    $content = '
    <div class="agreement-content">
        <h2>' . htmlspecialchars($t['user_agreement_title']) . '</h2>
        <p>' . htmlspecialchars($t['user_agreement_intro']) . '</p>
        
        <h3>' . htmlspecialchars($t['usage_scope_title']) . '</h3>
        <ul>
            <li>' . htmlspecialchars($t['usage_scope_1']) . '</li>
            <li>' . htmlspecialchars($t['usage_scope_2']) . '</li>
            <li>' . htmlspecialchars($t['usage_scope_3']) . '</li>
        </ul>
        
        <h3>' . htmlspecialchars($t['photo_usage_title']) . '</h3>
        <ul>
            <li>' . htmlspecialchars($t['photo_usage_1']) . '</li>
            <li>' . htmlspecialchars($t['photo_usage_2']) . '</li>
            <li>' . htmlspecialchars($t['photo_usage_3']) . '</li>
            <li>' . htmlspecialchars($t['photo_usage_4']) . '</li>
        </ul>
        
        <div class="warning-box">
            <strong>' . htmlspecialchars($t['photo_usage_warning']) . '</strong>
        </div>
        
        <h3>' . htmlspecialchars($t['liability_limitation_title']) . '</h3>
        <ul>
            <li>' . htmlspecialchars($t['liability_limitation_1']) . '</li>
            <li>' . htmlspecialchars($t['liability_limitation_2']) . '</li>
        </ul>
        
        <h3>' . htmlspecialchars($t['agreement_modification_title']) . '</h3>
        <ul>
            <li>' . htmlspecialchars($t['agreement_modification_1']) . '</li>
            <li>' . htmlspecialchars($t['agreement_modification_2']) . '</li>
        </ul>
        
        <h2>' . htmlspecialchars($t['privacy_policy_title']) . '</h2>
        <p>' . htmlspecialchars($t['privacy_policy_intro']) . '</p>
        
        <h3>' . htmlspecialchars($t['data_collection_title']) . '</h3>
        <p>' . htmlspecialchars($t['data_collection_intro']) . '</p>
        <ul>
            <li>' . htmlspecialchars($t['data_collection_1']) . '</li>
            <li>' . htmlspecialchars($t['data_collection_2']) . '</li>
            <li>' . htmlspecialchars($t['data_collection_3']) . '</li>
        </ul>
        
        <h3>' . htmlspecialchars($t['data_usage_title']) . '</h3>
        <p>' . htmlspecialchars($t['data_usage_intro']) . '</p>
        <ul>
            <li>' . htmlspecialchars($t['data_usage_1']) . '</li>
            <li>' . htmlspecialchars($t['data_usage_2']) . '</li>
            <li>' . htmlspecialchars($t['data_usage_3']) . '</li>
            <li>' . htmlspecialchars($t['data_usage_4']) . '</li>
        </ul>
        
        <h3>' . htmlspecialchars($t['data_protection_title']) . '</h3>
        <ul>
            <li>' . htmlspecialchars($t['data_protection_1']) . '</li>
            <li>' . htmlspecialchars($t['data_protection_2']) . '</li>
        </ul>
        
        <h3>' . htmlspecialchars($t['data_sharing_title']) . '</h3>
        <ul>
            <li>' . htmlspecialchars($t['data_sharing_1']) . '</li>
            <li>' . htmlspecialchars($t['data_sharing_2']) . '</li>
        </ul>
        
        <h3>' . htmlspecialchars($t['user_rights_title']) . '</h3>
        <p>' . htmlspecialchars($t['user_rights_intro']) . '</p>
        <ul>
            <li>' . htmlspecialchars($t['user_rights_1']) . '</li>
            <li>' . htmlspecialchars($t['user_rights_2']) . '</li>
            <li>' . htmlspecialchars($t['user_rights_3']) . '</li>
        </ul>
        
        <div class="disclaimer-box">
            <h3>©️ ' . htmlspecialchars($t['copyright_declaration_title']) . '</h3>
            <p>' . htmlspecialchars($t['copyright_declaration_content']) . '</p>
            <p>' . htmlspecialchars($t['copyright_declaration_restriction']) . '</p>
            <p><strong>' . htmlspecialchars($t['copyright_declaration_legal']) . '</strong></p>
        </div>
        
        <h2>' . htmlspecialchars($t['disclaimer_title']) . '</h2>
        <ul>
            <li>' . htmlspecialchars($t['disclaimer_1']) . '</li>
            <li>' . htmlspecialchars($t['disclaimer_2']) . '</li>
            <li>' . htmlspecialchars($t['disclaimer_3']) . '</li>
            <li>' . htmlspecialchars($t['disclaimer_4']) . '</li>
        </ul>
        
        <div class="warning-box">
            <strong>' . htmlspecialchars($t['disclaimer_warning']) . '</strong>
        </div>
        
        <div class="contact-box">
            <h3>' . htmlspecialchars($t['contact_method_title']) . '</h3>
            <p>' . htmlspecialchars($t['contact_method_text']) . '</p>
            <p>📧 ' . htmlspecialchars($t['contact_method_email']) . '</p>
        </div>
    </div>';
    
    echo json_encode([
        'success' => true,
        'content' => $content,
        'language' => $current_lang,
        'title' => $t['help_title'],
        'subtitle' => $t['help_subtitle']
    ]);
    
} catch (Exception $e) {
    error_log('Error in get_agreement_content.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => '获取内容失败: ' . $e->getMessage(),
        'content' => '',
        'error_details' => $e->getTraceAsString()
    ]);
}
?>