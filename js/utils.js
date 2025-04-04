/**
 * 通用工具函数
 */

/**
 * 显示自定义模态提示框，替代原生alert
 * @param {string} message - 提示消息内容
 * @param {string} type - 提示类型: 'info', 'success', 'warning', 'error'
 * @param {Function|null} callback - 模态框关闭后的回调函数
 */
function showAlert(message, type = 'info', callback = null) {
    ensureAlertStyles(); // Make sure styles are loaded

    // Remove any existing alert modal to prevent duplicates
    const existingAlert = document.getElementById('iosAlertModal');
    if (existingAlert) {
        // Immediately remove existing modal to prevent conflicts
        existingAlert.remove(); 
        // Also remove backdrop if it exists
        const backdrop = document.querySelector('.modal-backdrop');
        if (backdrop) backdrop.remove();
    }

    // Determine title based on type
    let title;
    switch (type) {
        case 'success': title = 'Success'; break;
        case 'warning': title = 'Warning'; break;
        case 'error': title = 'Error'; break;
        default: title = 'Information'; break;
    }

    // Detect theme
    const bodyClasses = document.body.className;
    let themeClass = 'ios-alert-light'; // Default to light
    // Check for manual dark theme OR auto theme + system dark mode preference
    if (bodyClasses.includes('dark-theme') || 
        (bodyClasses.includes('auto-theme') && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        themeClass = 'ios-alert-dark';
    }

    // Create modal HTML
    const modalId = 'iosAlertModal';
    const modalHTML = `
    <div class="modal fade ios-alert-modal ${themeClass}" id="${modalId}" tabindex="-1" aria-labelledby="${modalId}Label" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="${modalId}Label">${title}</h5>
                </div>
                <div class="modal-body">
                    ${message} 
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-ios" data-bs-dismiss="modal">OK</button> 
                </div>
            </div>
        </div>
    </div>
    `;

    // Insert modal into DOM
    document.body.insertAdjacentHTML('beforeend', modalHTML);

    const modalElement = document.getElementById(modalId);
    const modal = new bootstrap.Modal(modalElement);

    // Use the 'hidden.bs.modal' event for cleanup and callback
    modalElement.addEventListener('hidden.bs.modal', function handler(event) {
        // Ensure we're not responding to events from nested modals if any
        if (event.target !== modalElement) {
            return;
        }
        
        modalElement.removeEventListener('hidden.bs.modal', handler); // Clean up this listener
        
        // Execute callback if provided
        if (callback && typeof callback === 'function') {
            try {
                 callback();
            } catch (e) {
                 console.error("Error in showAlert callback:", e);
            }
        }
        
        // Remove the modal from DOM
        modalElement.remove();
        
        // Double-check for backdrop removal (Bootstrap 5 should handle this, but just in case)
        const stillExistingBackdrop = document.querySelector('.modal-backdrop');
        if (stillExistingBackdrop) {
            stillExistingBackdrop.remove();
        }
        // Restore body scrollability if Bootstrap hasn't
        if (document.body.style.overflow === 'hidden') {
             document.body.style.overflow = ''; 
             document.body.style.paddingRight = ''; 
        }
    }, { once: true }); // Use { once: true } for automatic listener removal

    // Show the modal
    modal.show();
}

/**
 * 美化HTML格式的错误消息，防止XSS攻击
 * @param {string} html - 待处理的HTML字符串
 * @returns {string} - 安全的HTML字符串
 */
function sanitizeHTML(html) {
    if (!html) return '';
    
    // 创建一个安全的标签和属性白名单
    const allowedTags = {
        'p': [],
        'br': [],
        'b': [],
        'i': [],
        'strong': [],
        'em': [],
        'u': [],
        'ul': [],
        'ol': [],
        'li': []
    };
    
    // 创建临时元素解析HTML
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = html;
    
    // 递归清理节点
    function cleanNode(node) {
        // 如果是文本节点，直接返回
        if (node.nodeType === 3) {
            return;
        }
        
        // 处理元素节点
        if (node.nodeType === 1) {
            const tagName = node.tagName.toLowerCase();
            
            // 如果标签不在白名单中，替换为其纯文本内容
            if (!allowedTags[tagName]) {
                const text = node.textContent;
                const textNode = document.createTextNode(text);
                node.parentNode.replaceChild(textNode, node);
                return;
            }
            
            // 移除所有可能包含JS的属性
            const attrs = [...node.attributes];
            for (const attr of attrs) {
                const attrName = attr.name.toLowerCase();
                const attrValue = attr.value;
                
                // 危险属性检查
                if (
                    attrName.startsWith('on') || // 事件处理程序
                    attrValue.includes('javascript:') || // javascript: URL
                    attrValue.includes('data:') || // data: URL
                    attrName === 'href' && attrValue.includes('javascript:') || // js链接
                    attrName === 'src' && attrValue.includes('javascript:') // js源
                ) {
                    node.removeAttribute(attrName);
                }
            }
            
            // 递归处理所有子节点
            [...node.childNodes].forEach(cleanNode);
        }
    }
    
    // 清理所有节点
    [...tempDiv.childNodes].forEach(cleanNode);
    
    // 返回清理后的HTML
    return tempDiv.innerHTML;
}

/**
 * 格式化日期时间
 * @param {string|Date} dateString - 日期字符串或Date对象
 * @param {boolean} showTime - 是否显示时间
 * @returns {string} - 格式化后的日期时间字符串
 */
function formatDateTime(dateString, showTime = true) {
    if (!dateString) return '';
    
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return dateString; // 无效日期，返回原始字符串
    
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    
    if (showTime) {
        return `${year}-${month}-${day} ${hours}:${minutes}`;
    } else {
        return `${year}-${month}-${day}`;
    }
}

/**
 * 获取用户令牌
 * @returns {string|null} - 用户令牌或null
 */
function getToken() {
    return localStorage.getItem('token');
}

/**
 * 检查用户是否已登录
 * @returns {boolean} - 是否已登录
 */
function isLoggedIn() {
    return !!getToken();
}

/**
 * Ensures the necessary CSS styles for the iOS-style alert modal are present in the document head.
 */
function ensureAlertStyles() {
    if (document.getElementById('ios-alert-styles')) return; // Styles already exist

    const css = `
        .ios-alert-modal .modal-dialog {
            max-width: 300px; /* Smaller width for iOS style */
            margin: 1.75rem auto;
        }
        .ios-alert-modal .modal-content {
            border-radius: 14px; /* iOS rounded corners */
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            text-align: center;
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        .ios-alert-modal .modal-header {
            border-bottom: none;
            padding: 16px 16px 0;
            justify-content: center; /* Center title */
        }
        .ios-alert-modal .modal-title {
            font-size: 17px;
            font-weight: 600; /* Semibold */
            margin-bottom: 4px; /* Space below title */
        }
        .ios-alert-modal .modal-body {
            padding: 0 16px 16px; /* Reduced padding */
            font-size: 13px; /* Smaller font size */
            line-height: 1.4;
        }
        .ios-alert-modal .modal-footer {
            border-top: 1px solid #dbdbdf; /* iOS separator */
            padding: 0;
            justify-content: center; /* Center button */
        }
        .ios-alert-modal .modal-footer button {
            flex-grow: 1; /* Button takes full width */
            border: none;
            background-color: transparent;
            color: #007aff; /* iOS blue */
            font-size: 17px;
            font-weight: 600; /* Semibold */
            padding: 11px 0;
            border-radius: 0 0 14px 14px; /* Round bottom corners */
            transition: background-color 0.2s ease;
        }
        .ios-alert-modal .modal-footer button:hover {
            background-color: rgba(0, 122, 255, 0.05); /* Subtle hover */
        }
        .ios-alert-modal .modal-footer button:focus {
            outline: none;
            box-shadow: none;
        }
        .ios-alert-modal .alert-icon {
            font-size: 24px;
            margin-bottom: 8px;
            display: block; /* Make icon block level */
        }
        .ios-alert-modal .alert-icon.icon-success { color: #34c759; } /* iOS green */
        .ios-alert-modal .alert-icon.icon-warning { color: #ff9500; } /* iOS orange */
        .ios-alert-modal .alert-icon.icon-error { color: #ff3b30; } /* iOS red */
        .ios-alert-modal .alert-icon.icon-info { color: #007aff; } /* iOS blue */

        /* Light Mode Styles */
        .ios-alert-light .modal-content {
            background-color: rgba(248, 248, 248, 0.95); /* Slightly transparent white */
            color: #000;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
        .ios-alert-light .modal-footer {
            border-color: #dbdbdf;
        }
        .ios-alert-light .modal-footer button {
            color: #007aff;
        }
        .ios-alert-light .modal-footer button:hover {
            background-color: rgba(0, 122, 255, 0.05);
        }

        /* Dark Mode Styles */
        .ios-alert-dark .modal-content {
            background-color: rgba(44, 44, 46, 0.95); /* Slightly transparent dark gray */
            color: #fff;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
        .ios-alert-dark .modal-footer {
            border-color: #545458;
        }
        .ios-alert-dark .modal-footer button {
            color: #0a84ff; /* Slightly brighter blue for dark mode */
        }
        .ios-alert-dark .modal-footer button:hover {
            background-color: rgba(10, 132, 255, 0.15);
        }
    `;

    const styleElement = document.createElement('style');
    styleElement.id = 'ios-alert-styles';
    styleElement.textContent = css;
    document.head.appendChild(styleElement);
} 