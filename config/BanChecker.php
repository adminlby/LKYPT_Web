<?php
// 用户封禁检查中间件
class BanChecker {
    private $pdo;
    private $current_lang;
    private $t;
    
    public function __construct($pdo, $current_lang, $t) {
        $this->pdo = $pdo;
        $this->current_lang = $current_lang;
        $this->t = $t;
    }
    
    /**
     * 检查用户是否被封禁
     * @param string $email 用户邮箱
     * @return array|null 如果被封禁返回封禁信息，否则返回null
     */
    public function checkUserBan($email) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM user_bans 
                WHERE email = ? AND status = 'active' 
                AND (is_permanent = 1 OR banned_until > NOW())
                ORDER BY banned_at DESC 
                LIMIT 1
            ");
            $stmt->execute([$email]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // 如果表不存在，返回null（未封禁）
            return null;
        }
    }
    
    /**
     * 显示封禁页面
     * @param array $ban_info 封禁信息
     */
    public function showBanPage($ban_info) {
        $ban_message = $this->t['user_banned_message'];
        $reason = htmlspecialchars($ban_info['reason']);
        
        if ($ban_info['is_permanent']) {
            $ban_until_text = $this->t['permanent_ban_message'];
        } else {
            $ban_until_date = date('Y-m-d H:i', strtotime($ban_info['banned_until']));
            $ban_until_text = sprintf($this->t['ban_until_date'], $ban_until_date);
        }
        
        $reason_text = sprintf($this->t['ban_reason_label'], $reason);
        
        ?>
        <!DOCTYPE html>
        <html lang="<?php echo $this->current_lang; ?>">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php echo $this->t['account_banned']; ?></title>
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
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: #333;
                }
                
                .ban-container {
                    background: white;
                    padding: 40px;
                    border-radius: 12px;
                    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
                    max-width: 500px;
                    width: 90%;
                    text-align: center;
                }
                
                .ban-icon {
                    font-size: 64px;
                    color: #dc3545;
                    margin-bottom: 20px;
                }
                
                .ban-title {
                    font-size: 24px;
                    font-weight: bold;
                    color: #dc3545;
                    margin-bottom: 20px;
                }
                
                .ban-message {
                    font-size: 16px;
                    line-height: 1.6;
                    margin-bottom: 15px;
                    color: #666;
                }
                
                .ban-details {
                    background: #f8f9fa;
                    padding: 20px;
                    border-radius: 8px;
                    margin-bottom: 20px;
                    text-align: left;
                }
                
                .ban-detail {
                    margin-bottom: 10px;
                    font-size: 14px;
                }
                
                .ban-detail strong {
                    color: #333;
                }
                
                .ban-actions {
                    margin-top: 30px;
                }
                
                .btn {
                    padding: 12px 24px;
                    border: none;
                    border-radius: 6px;
                    font-size: 14px;
                    font-weight: 500;
                    text-decoration: none;
                    display: inline-block;
                    transition: all 0.3s ease;
                    margin: 0 5px;
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
                
                @media (max-width: 576px) {
                    .ban-container {
                        padding: 30px 20px;
                    }
                    
                    .ban-icon {
                        font-size: 48px;
                    }
                    
                    .ban-title {
                        font-size: 20px;
                    }
                }
            </style>
        </head>
        <body>
            <div class="ban-container">
                <div class="ban-icon">🚫</div>
                <h1 class="ban-title"><?php echo $this->t['account_banned']; ?></h1>
                <p class="ban-message"><?php echo $ban_message; ?></p>
                
                <div class="ban-details">
                    <div class="ban-detail">
                        <strong><?php echo $this->t['ban_until']; ?>:</strong> <?php echo $ban_until_text; ?>
                    </div>
                    <div class="ban-detail">
                        <strong><?php echo $this->t['ban_reason']; ?>:</strong> <?php echo $reason; ?>
                    </div>
                    <?php if (!empty($ban_info['banned_at'])): ?>
                    <div class="ban-detail">
                        <strong><?php echo $this->t['banned_at']; ?>:</strong> 
                        <?php echo date('Y-m-d H:i', strtotime($ban_info['banned_at'])); ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="ban-actions">
                    <a href="/index.php?lang=<?php echo $this->current_lang; ?>" class="btn btn-primary">
                        <?php echo $this->t['back_home']; ?>
                    </a>
                    <a href="/logout.php?lang=<?php echo $this->current_lang; ?>" class="btn btn-secondary">
                        <?php echo $this->t['logout']; ?>
                    </a>
                </div>
            </div>
        </body>
        </html>
        <?php
        exit();
    }
    
    /**
     * 检查并处理用户封禁状态
     * @param string $email 用户邮箱
     */
    public function checkAndHandleBan($email) {
        $ban_info = $this->checkUserBan($email);
        if ($ban_info) {
            $this->showBanPage($ban_info);
        }
    }
}
?>