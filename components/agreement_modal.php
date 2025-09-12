<!-- 用户协议弹窗组件 -->
<style>
    /* 弹窗遮罩层 */
    .agreement-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        z-index: 10000;
        backdrop-filter: blur(5px);
    }

        .agreement-modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* 弹窗内容容器 */
        .agreement-modal-content {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            position: relative;
        }

        /* 弹窗头部 */
        .agreement-modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 12px 12px 0 0;
        }

        .agreement-modal-header h2 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }

        .agreement-modal-header p {
            margin: 8px 0 0 0;
            opacity: 0.9;
            font-size: 14px;
        }

        /* 弹窗主体内容 */
        .agreement-modal-body {
            padding: 0;
            flex: 1;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        /* 滚动内容区域 */
        .agreement-content-scroll {
            flex: 1;
            overflow-y: auto;
            padding: 24px;
            font-family: 'Segoe UI', 'Microsoft JhengHei', Arial, sans-serif;
            line-height: 1.6;
            max-height: 60vh;
        }

        .agreement-content-scroll h3 {
            color: #333;
            margin: 24px 0 12px 0;
            font-size: 18px;
            border-left: 4px solid #667eea;
            padding-left: 12px;
        }

        .agreement-content-scroll h2 {
            color: #2c3e50;
            margin: 20px 0 15px 0;
            font-size: 20px;
            font-weight: 600;
        }

        .agreement-content-scroll ul {
            margin: 12px 0;
            padding-left: 24px;
        }

        .agreement-content-scroll li {
            margin: 8px 0;
            color: #555;
        }

        .agreement-content-scroll p {
            margin: 12px 0;
            color: #555;
        }

        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 16px;
            margin: 16px 0;
            color: #856404;
            font-weight: 500;
        }

        .disclaimer-box {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin: 16px 0;
        }

        .contact-box {
            background: #e3f2fd;
            border: 1px solid #bbdefb;
            border-radius: 8px;
            padding: 20px;
            margin: 16px 0;
        }

        /* 滚动指示器 */
        .scroll-indicator {
            background: #f8f9fa;
            padding: 12px 24px;
            border-top: 1px solid #dee2e6;
            text-align: center;
            color: #6c757d;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .scroll-indicator.scrolled {
            display: none;
        }

        .scroll-arrow {
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-5px);
            }
            60% {
                transform: translateY(-3px);
            }
        }

        /* 弹窗底部 */
        .agreement-modal-footer {
            background: #f8f9fa;
            padding: 20px 24px;
            border-top: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 0 0 12px 12px;
        }

        .scroll-status {
            font-size: 14px;
            color: #6c757d;
        }

        .scroll-status.completed {
            color: #28a745;
            font-weight: 600;
        }

        /* 按钮样式 */
        .agreement-buttons {
            display: flex;
            gap: 12px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-secondary {
            background: #dc3545;
            color: white;
        }

        .btn-secondary:hover {
            background: #c82333;
            transform: translateY(-1px);
        }

        .btn-primary {
            background: #28a745;
            color: white;
            opacity: 0.5;
            cursor: not-allowed;
        }

        .btn-primary.enabled {
            opacity: 1;
            cursor: pointer;
        }

        .btn-primary.enabled:hover {
            background: #218838;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
        }

        /* 响应式设计 */
        @media (max-width: 768px) {
            .agreement-modal-content {
                width: 95%;
                max-height: 95vh;
            }
            
            .agreement-modal-header {
                padding: 16px;
            }
            
            .agreement-modal-header h2 {
                font-size: 20px;
            }
            
            .agreement-content-scroll {
                padding: 16px;
                max-height: 50vh;
            }
            
            .agreement-modal-footer {
                padding: 16px;
                flex-direction: column;
                gap: 12px;
            }
            
            .agreement-buttons {
                width: 100%;
            }
            
            .btn {
                flex: 1;
                justify-content: center;
            }
        }
    </style>
</head>
<body>

<!-- 用户协议弹窗模板 -->
<div id="agreementModal" class="agreement-modal">
    <div class="agreement-modal-content">
        <!-- 弹窗头部 -->
        <div class="agreement-modal-header">
            <h2 id="agreementTitle"><?php echo $t['agreement_modal_title'] ?? '用戶協議與隱私政策'; ?></h2>
            <p id="agreementSubtitle"><?php echo $t['agreement_modal_subtitle'] ?? '請仔細閱讀以下內容並滾動至底部'; ?></p>
        </div>

        <!-- 弹窗主体 -->
        <div class="agreement-modal-body">
            <!-- 滚动内容区域 -->
            <div id="agreementContentScroll" class="agreement-content-scroll">
                <!-- 内容将通过JavaScript动态加载 -->
            </div>

            <!-- 滚动指示器 -->
            <div id="scrollIndicator" class="scroll-indicator">
                <span><?php echo $t['agreement_scroll_indicator'] ?? '請滾動閱讀完整內容'; ?></span>
                <span class="scroll-arrow">↓</span>
            </div>
        </div>

        <!-- 弹窗底部 -->
        <div class="agreement-modal-footer">
            <div id="scrollStatus" class="scroll-status">
                <?php echo $t['agreement_scroll_status_reading'] ?? '請滾動至內容底部以啟用同意按鈕'; ?>
            </div>
            <div class="agreement-buttons">
                <button type="button" class="btn btn-secondary" onclick="closeAgreementModal()">
                    <?php echo $t['agreement_btn_later'] ?? '稍後閱讀'; ?>
                </button>
                <button type="button" id="agreeButton" class="btn btn-primary" onclick="agreeToTerms()" disabled>
                    <?php echo $t['agreement_btn_agree'] ?? '我已閱讀並同意'; ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// 全局变量
let hasScrolledToBottom = false;
let agreementModal = null;
let scrollContainer = null;
let agreeButton = null;
let scrollStatus = null;
let scrollIndicator = null;

// 多语言文本
const i18n = {
    scrollStatusReading: '<?php echo addslashes($t['agreement_scroll_status_reading'] ?? '請滾動至內容底部以啟用同意按鈕'); ?>',
    scrollStatusCompleted: '<?php echo addslashes($t['agreement_scroll_status_completed'] ?? '✓ 已閱讀完整內容，可以點擊同意'); ?>',
    successMessage: '<?php echo addslashes($t['agreement_success_message'] ?? '感謝您的確認，您現在可以正常使用系統'); ?>'
};

// 初始化弹窗
function initAgreementModal() {
    agreementModal = document.getElementById('agreementModal');
    scrollContainer = document.getElementById('agreementContentScroll');
    agreeButton = document.getElementById('agreeButton');
    scrollStatus = document.getElementById('scrollStatus');
    scrollIndicator = document.getElementById('scrollIndicator');

    // 绑定滚动事件
    if (scrollContainer) {
        scrollContainer.addEventListener('scroll', checkScrollPosition);
    }
    
    // 防止通过ESC键关闭弹窗
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && agreementModal && agreementModal.classList.contains('show')) {
            event.preventDefault();
            event.stopPropagation();
        }
    });
    
    // 防止点击背景关闭弹窗
    if (agreementModal) {
        agreementModal.addEventListener('click', function(event) {
            if (event.target === agreementModal) {
                event.preventDefault();
                event.stopPropagation();
                // 可以添加提示信息
                showTempMessage('请阅读协议内容并做出选择');
            }
        });
    }
}

// 显示临时提示信息
function showTempMessage(message) {
    const toast = document.createElement('div');
    toast.style.cssText = `
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: #f8d7da;
        color: #721c24;
        padding: 12px 20px;
        border-radius: 6px;
        z-index: 10002;
        font-family: inherit;
        font-size: 14px;
        border: 1px solid #f5c6cb;
    `;
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        if (document.body.contains(toast)) {
            document.body.removeChild(toast);
        }
    }, 2000);
}

// 检查滚动位置
function checkScrollPosition() {
    if (!scrollContainer) return;

    const scrollTop = scrollContainer.scrollTop;
    const scrollHeight = scrollContainer.scrollHeight;
    const clientHeight = scrollContainer.clientHeight;
    
    // 计算滚动百分比
    const scrollPercentage = (scrollTop / (scrollHeight - clientHeight)) * 100;
    
    // 当滚动到90%以上时认为已读完
    if (scrollPercentage >= 90) {
        if (!hasScrolledToBottom) {
            hasScrolledToBottom = true;
            enableAgreeButton();
        }
    }
}

// 启用同意按钮
function enableAgreeButton() {
    if (agreeButton) {
        agreeButton.disabled = false;
        agreeButton.classList.add('enabled');
    }
    
    if (scrollStatus) {
        scrollStatus.textContent = i18n.scrollStatusCompleted;
        scrollStatus.classList.add('completed');
    }
    
    if (scrollIndicator) {
        scrollIndicator.classList.add('scrolled');
    }
}

// 显示协议弹窗
function showAgreementModal() {
    if (!agreementModal) {
        initAgreementModal();
    }
    
    // 重置状态
    hasScrolledToBottom = false;
    if (agreeButton) {
        agreeButton.disabled = true;
        agreeButton.classList.remove('enabled');
    }
    if (scrollStatus) {
        scrollStatus.textContent = i18n.scrollStatusReading;
        scrollStatus.classList.remove('completed');
    }
    if (scrollIndicator) {
        scrollIndicator.classList.remove('scrolled');
    }
    
    // 加载协议内容
    loadAgreementContent();
    
    // 显示弹窗
    agreementModal.classList.add('show');
    document.body.style.overflow = 'hidden';
}

// 关闭协议弹窗（带确认）
function closeAgreementModal() {
    // 显示确认对话框
    const confirmMessage = '<?php echo addslashes($t['agreement_decline_confirm'] ?? '您確定要拒絕協議嗎？這將會退出登錄並返回首頁。'); ?>';
    
    if (confirm(confirmMessage)) {
        hideAgreementModal();
        
        // 强制用户同意协议，如果选择拒绝则退出登录
        logoutAndRedirect();
    }
}

// 直接隐藏协议弹窗（不带确认）
function hideAgreementModal() {
    if (agreementModal) {
        agreementModal.classList.remove('show');
        document.body.style.overflow = '';
    }
}

// 退出登录并重定向到首页
function logoutAndRedirect() {
    // 显示退出提示
    showSuccessMessage('正在退出登录...');
    
    // 清除用户会话
    fetch('logout.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // 重定向到首页
            window.location.href = data.redirect || 'index.php';
        } else {
            // 如果退出失败也重定向
            window.location.href = 'index.php';
        }
    })
    .catch(error => {
        console.error('Logout error:', error);
        // 即使退出失败也重定向
        window.location.href = 'index.php';
    });
}

// 同意协议
function agreeToTerms() {
    if (!hasScrolledToBottom) {
        alert('请先完整阅读协议内容');
        return;
    }
    
    // 发送同意请求到后端
    fetch('api/agree_terms.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'agree',
            version: '1.0',
            timestamp: new Date().toISOString()
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // 直接关闭弹窗，不触发确认对话框
            hideAgreementModal();
            // 显示成功消息
            showSuccessMessage(i18n.successMessage);
        } else {
            alert('操作失败，请重试: ' + (data.message || ''));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('网络错误，请重试');
    });
}

// 显示成功消息
function showSuccessMessage(message) {
    // 创建临时提示
    const toast = document.createElement('div');
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: #28a745;
        color: white;
        padding: 16px 24px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        z-index: 10001;
        font-family: inherit;
        font-size: 14px;
        max-width: 300px;
    `;
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        document.body.removeChild(toast);
    }, 3000);
}

// 加载协议内容
function loadAgreementContent() {
    if (!scrollContainer) return;
    
    // 获取当前语言参数
    const urlParams = new URLSearchParams(window.location.search);
    const lang = urlParams.get('lang') || 'zh';
    
    console.log('Loading agreement content for language:', lang);
    
    // 通过AJAX加载真实的协议内容
    fetch(`api/get_agreement_content.php?lang=${lang}`)
    .then(response => {
        console.log('Content API response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Content API data:', data);
        if (data.success) {
            scrollContainer.innerHTML = data.content;
            
            // 更新标题
            const titleElement = document.getElementById('agreementTitle');
            const subtitleElement = document.getElementById('agreementSubtitle');
            if (titleElement && data.title) {
                titleElement.textContent = data.title;
            }
            if (subtitleElement && data.subtitle) {
                subtitleElement.textContent = data.subtitle;
            }
        } else {
            console.error('Content API returned error:', data.message);
            scrollContainer.innerHTML = '<p>加载内容失败，请刷新重试</p>';
        }
    })
    .catch(error => {
        console.error('Error loading agreement content:', error);
        scrollContainer.innerHTML = '<p>网络错误，请检查连接后重试</p>';
    });
}

// 检查是否需要显示协议弹窗
function checkAgreementStatus() {
    fetch('api/check_agreement.php')
    .then(response => {
        console.log('Agreement check response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Agreement check data:', data);
        if (!data.agreed || data.needsUpdate) {
            showAgreementModal();
        }
    })
    .catch(error => {
        console.error('Error checking agreement status:', error);
        // 如果API出错，为安全起见显示协议弹窗
        showAgreementModal();
    });
}

// 页面加载完成后初始化
document.addEventListener('DOMContentLoaded', function() {
    initAgreementModal();
});
</script>