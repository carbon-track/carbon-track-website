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
    // 防止重复创建
    const existingAlert = document.querySelector('.custom-alert-modal');
    if (existingAlert) {
        existingAlert.remove();
    }
    
    // 根据类型确定标题和颜色
    let title, headerClass, iconClass;
    switch (type) {
        case 'success':
            title = '成功';
            headerClass = 'bg-success text-white';
            iconClass = 'fas fa-check-circle';
            break;
        case 'warning':
            title = '警告';
            headerClass = 'bg-warning text-dark';
            iconClass = 'fas fa-exclamation-triangle';
            break;
        case 'error':
            title = '错误';
            headerClass = 'bg-danger text-white';
            iconClass = 'fas fa-times-circle';
            break;
        default: // info
            title = '提示';
            headerClass = 'bg-info text-white';
            iconClass = 'fas fa-info-circle';
    }
    
    // 创建模态框HTML
    const modalHTML = `
    <div class="modal fade custom-alert-modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
            <div class="modal-content">
                <div class="modal-header ${headerClass} py-2">
                    <h5 class="modal-title">
                        <i class="${iconClass} me-2"></i>${title}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-4">
                    <p class="mb-0">${message}</p>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-primary px-4" data-bs-dismiss="modal">确定</button>
                </div>
            </div>
        </div>
    </div>
    `;
    
    // 插入模态框到DOM
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    
    // 获取模态框实例
    const modalElement = document.querySelector('.custom-alert-modal');
    const modal = new bootstrap.Modal(modalElement);
    
    // 在模态框隐藏后执行回调并移除模态框
    if (callback) {
        modalElement.addEventListener('hidden.bs.modal', function () {
            callback();
            modalElement.remove();
        });
    } else {
        modalElement.addEventListener('hidden.bs.modal', function () {
            modalElement.remove();
        });
    }
    
    // 显示模态框
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