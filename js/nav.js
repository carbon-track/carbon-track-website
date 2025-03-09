$(document).ready(function() {
    // Load navbar
    loadNavbar();
    
    // 初始化消息模态框
    initMessageModal();
    
    var translateElement = $('<div>', { id: 'google_translate_element' });
    $('.nav-item #userStatus').parent().before(translateElement);

    // 动态加载Google Translate脚本
    /*var googleTranslateScript = document.createElement('script');
    googleTranslateScript.type = 'text/javascript';
    googleTranslateScript.async = true;
    googleTranslateScript.src = '//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit';
    
    // 设置一个标记，用于检查脚本是否成功加载
    var scriptLoaded = false;
    
    // 设置一个计时器，5秒后检查脚本是否加载
    setTimeout(function() {
        if (!scriptLoaded) {
            // 如果脚本未加载，从文档中移除该脚本节点
            document.body.removeChild(googleTranslateScript);
            console.log('Google Translate script loading aborted due to timeout.');
        }
    }, 5000); // 5秒后执行

    // 监听脚本加载事件
    googleTranslateScript.onload = function() {
        scriptLoaded = true;
        console.log('Google Translate script loaded successfully.');
    };

    // 监听脚本加载失败事件
    googleTranslateScript.onerror = function() {
        scriptLoaded = true; // 将标记设置为true，即使加载失败也不再尝试移除脚本
        console.log('Failed to load the Google Translate script.');
    };

    document.body.appendChild(googleTranslateScript);*/
  var loggedIn = localStorage.getItem('loggedIn');
  var expiration = localStorage.getItem('expiration');
  var now = new Date();

  if (loggedIn && expiration && now.getTime() < expiration) {
    var logoutButton = document.getElementById('logoutButton');
    
    // 移除"btn-danger"类
    logoutButton.classList.remove('btn-danger');
    
    // 设置按钮颜色
    logoutButton.style.color = 'rgba(255, 255, 255, .5)';
    logoutButton.style.border = '1px solid rgba(255, 255, 255, .5)';
    var username = localStorage.getItem('username');
    updateLoginStatus();
            checkUnreadMessages();
        setInterval(checkUnreadMessages(), 30);
        // 模态框显示时加载消息
$('#messagesModal').on('show.bs.modal', function(e) {
    console.log('消息模态框正在打开，确保模态框已初始化');
    
    // 确保模态框结构已初始化
    if (!document.getElementById('messageList') || !document.getElementById('conversationList')) {
        console.log('重新初始化消息模态框结构');
        initMessageModal();
    }
    
    // 清空现有内容
    $('#messageList').empty();
    $('#conversationList').empty();
    
    var modalBody = $(this).find('.modal-body');
    modalBody.prepend('<div id="loading"><div class="loader">加载中...</div></div>'); 
    
    // 检查用户登录状态
    var token = localStorage.getItem('token');
    if (!token) {
        $('#loading').remove();
        console.error('用户未登录，无法获取消息');
        $('#conversationList').append('<div class="alert alert-warning">请先登录</div>');
        return;
    }
    
    fetchMessages().then(function(data) {
        $('#loading').remove(); // 移除加载动画
        console.log('获取消息成功，返回数据:', data);
        
        if (data.success) {
            // 确认消息数据存在
            if (data.messages && data.messages.length > 0) {
                console.log('成功接收消息，数量:', data.messages.length);
                buildConversationsList(data);
            } else {
                console.warn('没有接收到消息数据或消息数组为空');
                // 显示无消息提示
                $('#conversationList').empty().append('<div class="alert alert-info">暂无消息</div>');
                $('#messageList').empty().append('<div class="alert alert-info text-center">暂无消息</div>');
            }
        } else {
            console.error('获取消息失败:', data);
            $('#conversationList').empty().append('<div class="alert alert-danger">获取消息失败</div>');
        }
        
        checkUnreadMessages();
        // 修复setInterval的用法
        setInterval(checkUnreadMessages, 30000); // 每30秒检查一次未读消息
    }).catch(function(error) {
        $('#loading').remove(); // 确保即使发生错误也要移除加载动画
        console.error('获取消息出错:', error);
        $('#conversationList').empty().append('<div class="alert alert-danger">加载消息时出错</div>');
    });
});  } else {
    logout(); // 如果过期或未登录，执行注销操作
    
  }
    updateButtonForRemainingTime();
$('nav .fas.fa-envelope').css('color', 'rgba(255, 255, 255, .5)');
    // 绑定登录表单提交事件处理器
    $('#loginModal').submit(function(event) {
        event.preventDefault(); // 阻止表单默认提交行为

        // 获取输入的用户名和密码
        var username = $('#username').val();
        var password = $('#password').val();

        // 发送AJAX请求到服务器进行验证
        $.ajax({
            type: 'POST',
            url: 'login.php', // 您的登录处理脚本
            data: { username: username, password: password },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // 登录成功
                    var now = new Date();
                    var expiration = new Date(now.getTime() + 7 * 24 * 60 * 60 * 1000); // 设置7天后的时间
                    localStorage.setItem('loggedIn', true); // 存储登录状态
                    localStorage.setItem('username', response.real_username); // 存储用户名
                    localStorage.setItem('expiration', expiration.getTime()); // 存储过期时间戳
                    localStorage.setItem('email', response.email); // 存储email
                    localStorage.setItem('token', response.token);
                    localStorage.setItem('id', response.id);
                    $('[data-target="#loginModal"]').hide();
                    $('[data-target="#registerModal"]').hide();
                    var logoutButton = document.getElementById('logoutButton');
                    
                    // 移除"btn-danger"类
                    logoutButton.classList.remove('btn-danger');
                    
                    // 设置按钮颜色
                    logoutButton.style.color = 'rgba(255, 255, 255, .5)';
                    logoutButton.style.border = '1px solid rgba(255, 255, 255, .5)';
                    $('.logoutControl').show();
                    $('a[href="center.html"]').hide();
                    $('a[href="calculate.html"]').hide();
                    $('a[href="CStore.html"]').hide();

                    $('#loginModal').modal('hide').on('hidden.bs.modal', function() {
                        // 这里的代码会在模态框完全关闭后执行
                        $('.modal-backdrop').remove(); // 如果需要的话，手动移除遮罩
                    });

                    updateLoginStatus(); // 更新页面显示登录状态
                } else {
                    // 登录失败，显示错误信息
                    alert('登录失败: ' + response.message);
                }
            },
            error: function() {
                alert('登录请求失败，请稍后再试。');
                $('#refreshAlert').show();
            }
        });
    });
    // 页面加载时检查登录状态
    checkLoginStatus();
    updateButtonForRemainingTime();

    // 点击站内信图标时打开模态框并加载消息
    $('#messagesIcon, [data-target="#messagesModal"]').on('click', function(e) {
        e.preventDefault();
        
        // 检查登录状态
        var loggedIn = localStorage.getItem('loggedIn');
        if (!loggedIn) {
            alert('请先登录以访问消息');
            return;
        }
        
        // 确保模态框已初始化
        if (!document.getElementById('messagesModal')) {
            console.log('点击时发现模态框不存在，正在初始化');
            initMessageModal();
        }
        
        // 显示模态框
        $('#messagesModal').modal('show');
    });
// 绑定发送按钮的点击事件
$('#sendMessage').on('click', function() {
    sendMessage();
});



    // 注册表单提交事件
    $('#registerModal').submit(function(event) {
        event.preventDefault(); // 阻止表单默认提交行为
        registerUser();
    });

    // 发送验证码按钮点击事件
    $('#sendVerificationCode').on('click', function() {
        sendVerificationCode();
    });

    // 注销按钮点击事件
    $('#logoutButton').on('click', function() {
        logout();
    });
  
    $("#footer-placeholder").load("footer.html");
    
});
function googleTranslateElementInit() {
  new google.translate.TranslateElement({pageLanguage: 'en', layout: google.translate.TranslateElement.InlineLayout.SIMPLE}, 'google_translate_element');
}

function checkUnreadMessages() {
    
    var token = localStorage.getItem('token');
    var id = localStorage.getItem('id');
    $.ajax({
            type: 'POST',
            url: 'chkmsg.php',
            data: { token: token, uid:id },
            dataType: 'json',
            success: function(response) {
                if (response.unreadCount > 0) {
                $('#unreadMessagesCount').text(response.unreadCount).show();
                // 更改图标颜色为红色
                $('nav .fas.fa-envelope').css('color', 'red');
            } else {
                $('#unreadMessagesCount').hide();
                // 没有未读消息时，恢复图标原始颜色
                $('nav .fas.fa-envelope').css('color', 'rgba(255, 255, 255, .5)');
            }},
            error: function() {
                $('#emailHelp').hide();
                console.log('请求未读消息数量失败');
            }
    });
}
// 更新页面显示登录状态的函数

// 检查登录状态
function checkLoginStatus() {
    var loggedIn = localStorage.getItem('loggedIn');
    var expiration = localStorage.getItem('expiration');
    var now = new Date();

    if (loggedIn && expiration && now.getTime() < expiration) {
        updateLoginStatus();
    } else {
        logout(); // 如果过期或未登录，执行注销操作
    }
}

// 注销函数
function logout() {
    localStorage.clear();
    updateLoginStatus(); // 更新UI为未登录状态
}
function updateLoginStatus() {
    var username = localStorage.getItem('username');
    if (username) {
        // 根据您的页面结构，这里可能需要调整
        $('#userStatus').text('Welcome, ' + username);
        $('a[href="calculate.html"]').show();
        // 显示注销按钮，隐藏登录按钮
        $('#logoutButton').show();
        $('a[href="center.html"]').show();
        $('a[href="calculate.html"]').show();
        $('a[href="CStore.html"]').show();
        $('.logoutControl').show();
        $('#loginButton').hide();
        $('[data-target="#loginModal"]').hide();
        $('[data-target="#registerModal"]').hide();
    } else {
        $('.logoutControl').hide();
        $('#userStatus').text('Please login or register:');
        $('#loginButton').show();
        $('[data-target="#loginModal"]').show();
        $('[data-target="#registerModal"]').show();
        $('#logoutButton').hide();
        $('a[href="center.html"]').hide();
        $('a[href="calculate.html"]').hide();
        $('a[href="CStore.html"]').hide();
    }
}

// 注册用户
function registerUser() {
    // 获取表单数据
    var regusername = $('#regusername').val();
    var email = $('#email').val();
    var regpassword = $('#regpassword').val();
    var verificationCode = $('#verificationCode').val();

    // 发送AJAX请求到服务器进行注册
    $.ajax({
        type: 'POST',
        url: 'register.php', // 您的注册处理脚本
        data: {
            email: email,
            regusername: regusername,
            regpassword: regpassword,
            verificationCode: verificationCode
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // 注册成功
                $('#registerModal').modal('hide'); // 关闭模态框
                alert('注册成功！Register success!');
            } else {
                // 注册失败，显示错误信息
                $('#registerError').show().text('连接不稳定，请尝试直接登录! Connection unstable, please try to sign in directly!');
            }
        },
        error: function() {
            alert('注册请求失败，请稍后再试。Register request failed, please try later.');
            $('#refreshAlert').show();
        }
    });
}

// 发送验证码
function sendVerificationCode() {
    var allFilled = true;
    $('.required-input').each(function() {
        if ($(this).val() === '') {
            allFilled = false;
            // 可以在这里为对应的输入框添加一些视觉提示，例如边框颜色变红
            $(this).css('border-color', 'red'); // 举例：将未填写的输入框边框设为红色
        } else {
            $(this).css('border-color', ''); // 已填写的输入框恢复原样
        }
    });

    // 如果有未填写的必填输入框，显示提示信息并终止函数执行
    if (!allFilled) {
        alert('请填写所有必填信息后再发送验证码。');
        return; // 终止函数执行
    }
    // 记录发送时间到localStorage
    const now = Date.now();
    localStorage.setItem('lastSentTime', now.toString());

    // 获取用户输入的邮箱并发送验证码...
    var email = $('#email').val();
    var cftoken=localStorage.getItem('cf_token');
    if (email) {
        $.ajax({
            type: 'POST',
            url: 'sendVerificationCode.php',
            data: {cf_token:cftoken, email: email },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#emailHelp').show();
                    // 禁用发送按钮，60秒后再启用
                    const now = Date.now();
                    localStorage.setItem('lastSentTime', now.toString());
                    $('#sendVerificationCode').prop('disabled', true);
                    updateButtonForRemainingTime();
                    
                } else {
                    $('#emailHelp').hide();
                    alert('验证码发送失败: ' + response.error);
                }
            },
            error: function() {
                $('#emailHelp').hide();
                alert('请求发送验证码失败，请稍后再试。');
            }
        });
    } else {
        alert('请输入邮箱地址。');
    }
}

function updateButtonForRemainingTime() {
    const now = Date.now();
    const lastSentTime = localStorage.getItem('lastSentTime') ? parseInt(localStorage.getItem('lastSentTime'), 10) : 0;
    let remainingTime = 60000 - (now - lastSentTime);

    remainingTime = Math.max(0, remainingTime);

    if (remainingTime > 0) {
        $('#sendVerificationCode').text(`请等待${Math.ceil(remainingTime / 1000)}秒`);
        $('#sendVerificationCode').prop('disabled', true);
        // 使用setTimeout来延迟下一次调用，无需清除定时器
        setTimeout(updateButtonForRemainingTime, 1000);
    } else {
        $('#sendVerificationCode').text('发送验证码');
        $('#sendVerificationCode').prop('disabled', false);
    }
}
// 获取消息
function fetchMessages() {
    return new Promise((resolve, reject) => {
        var token = localStorage.getItem('token');
        
        if (!token) {
            console.error('Token不存在，无法获取消息');
            reject(new Error('未登录或会话过期'));
            return;
        }
        
        console.log('正在获取消息，使用token进行身份验证');
        
        $.ajax({
            type: 'POST',
            url: 'getmsg.php',
            data: JSON.stringify({ token: token }),
            contentType: 'application/json',
            success: function(data) {
                console.log('消息获取成功:', data);
                resolve(data);
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('无法加载消息:', textStatus, errorThrown);
                reject(new Error('加载消息时发生错误: ' + errorThrown));
            }
        });
    });
}


function buildConversationsList(data) {
    console.log('开始构建对话列表，收到的数据:', data);
    
    // 获取对话列表容器
    var conversationList = $('#conversationList');
    if (!conversationList.length) {
        console.log('找不到对话列表容器，正在重新初始化消息模态框');
        initMessageModal();
        // 重新获取容器
        conversationList = $('#conversationList');
        if (!conversationList.length) {
            console.error('无法创建对话列表容器，可能存在DOM结构问题');
            alert('无法加载消息界面，请刷新页面重试');
            return;
        }
    }
    
    // 清空现有列表
    conversationList.empty();
    
    // 如果没有消息或消息为空，显示提示
    if (!data.messages || data.messages.length === 0) {
        console.warn('没有收到任何消息数据');
        conversationList.append('<div class="alert alert-info">暂无消息</div>');
        $('#messageList').html('<div class="alert alert-info text-center">暂无消息</div>');
        return;
    }
    
    console.log('收到消息数量:', data.messages.length);
    
    // 按发送者ID分组消息
    var conversations = {};
    data.messages.forEach(function(message) {
        var senderId = message.sender_id;
        
        if (!conversations[senderId]) {
            conversations[senderId] = {
                sender_id: senderId,
                messages: []
            };
        }
        
        conversations[senderId].messages.push(message);
    });
    
    // 如果没有对话，显示提示
    if (Object.keys(conversations).length === 0) {
        conversationList.append('<div class="alert alert-info">暂无对话</div>');
        return;
    }
    
    // 为每个发送者创建一个对话项
    Object.values(conversations).forEach(function(conversation) {
        var senderId = conversation.sender_id;
        
        // 创建对话项
        var listItem = $('<div></div>').addClass('conversation-item').html(`
            <div class="d-flex align-items-center p-2">
                <div class="flex-shrink-0">
                    <img src="img/team.jpg" alt="Avatar" class="rounded-circle" style="width: 40px; height: 40px;">
                </div>
                <div class="flex-grow-1 ms-3">
                    <div class="fw-bold">CarbonTrack系统消息</div>
                    <div class="small text-muted">点击查看消息</div>
                </div>
            </div>
        `);
        
        // 样式设置
        listItem.css({
            'border-bottom': '1px solid #e9ecef',
            'cursor': 'pointer',
            'transition': 'background-color 0.2s'
        });
        
        // 悬停效果
        listItem.hover(
            function() { $(this).css('background-color', '#f8f9fa'); },
            function() { $(this).css('background-color', ''); }
        );
        
        // 点击显示此对话的消息
        listItem.click(function() {
            // 移除其他对话项的选中状态
            $('.conversation-item').removeClass('active').css('background-color', '');
            // 添加当前对话项的选中状态
            $(this).addClass('active').css('background-color', '#e9ecef');
            
            // 显示该发送者的所有消息
            displayMessages(conversation.messages, senderId);
        });
        
        // 添加到对话列表
        conversationList.append(listItem);
    });
    
    // 默认选中第一个对话并显示其消息
    if (conversationList.children('.conversation-item').length > 0) {
        var firstConversation = conversationList.children('.conversation-item').first();
        firstConversation.addClass('active').css('background-color', '#e9ecef');
        
        // 获取第一个对话的发送者ID和消息
        var firstSenderId = Object.keys(conversations)[0];
        displayMessages(conversations[firstSenderId].messages, firstSenderId);
    }
}


function displayMessages(messages, sender) {
    console.log('开始显示消息，消息数量:', messages ? messages.length : 0, '发送者ID:', sender);
    
    // 保存当前聊天的发送者ID，用于发送回复消息
    localStorage.setItem('currentChatPartnerId', sender);
    
    // 获取或创建messageList元素
    let messageList = document.getElementById('messageList');
    if (!messageList) {
        console.log('找不到messageList元素，正在重新初始化消息模态框');
        initMessageModal();
        // 重新获取元素
        messageList = document.getElementById('messageList');
        if (!messageList) {
            console.error('无法创建messageList元素，可能存在DOM结构问题');
            alert('无法加载消息界面，请刷新页面重试');
            return;
        }
    }
    
    // 清空现有消息
    messageList.innerHTML = '';
    
    // 如果没有消息，显示提示信息
    if (!messages || messages.length === 0) {
        messageList.innerHTML = '<div class="alert alert-info text-center">暂无消息</div>';
        return;
    }
    
    // 创建消息样式
    let style = document.createElement('style');
    style.textContent = `
        .message-container { margin-bottom: 16px; }
        .message-bubble {
            padding: 10px 15px;
            border-radius: 18px;
            max-width: 80%;
            word-wrap: break-word;
        }
        .message-time {
            font-size: 12px;
            color: #777;
            margin-top: 5px;
        }
        .received {
            background-color: #f1f0f0;
            margin-right: auto;
        }
    `;
    document.head.appendChild(style);
    
    // 根据时间排序消息
    messages.sort((a, b) => {
        const timeA = a.send_time || a.created_at;
        const timeB = b.send_time || b.created_at;
        return new Date(timeA) - new Date(timeB);
    });
    
    // 添加每条消息
    messages.forEach(message => {
        // 创建消息容器
        let container = document.createElement('div');
        container.className = 'message-container';
        
        // 创建消息气泡
        let bubble = document.createElement('div');
        bubble.className = 'message-bubble received';
        
        // 添加消息内容
        bubble.textContent = message.content || '(空消息)';
        
        // 创建时间显示
        let time = document.createElement('div');
        time.className = 'message-time';
        
        // 格式化时间
        let timestamp = message.send_time || message.created_at;
        if (timestamp) {
            let date = new Date(timestamp);
            time.textContent = date.toLocaleString();
        } else {
            time.textContent = '未知时间';
        }
        
        // 组装消息
        container.appendChild(bubble);
        container.appendChild(time);
        messageList.appendChild(container);
    });
    
    // 滚动到底部
    messageList.scrollTop = messageList.scrollHeight;
}
function sendMessage() {
    // 获取消息输入
    var messageInput = document.getElementById('messageInput');
    if (!messageInput) {
        console.error('找不到消息输入框');
        return;
    }
    
    var message = messageInput.value.trim();
    if (!message) {
        console.log('消息内容为空，不发送');
        return; // 如果消息为空，不做任何操作
    }
    
    // 获取必要参数
    var receiverId = localStorage.getItem('currentChatPartnerId');
    var token = localStorage.getItem('token');
    
    if (!receiverId || !token) {
        console.error('缺少接收者ID或token', { receiverId, token });
        alert('发送失败：无法确定接收者或未登录');
        return;
    }
    
    console.log('准备向ID为 ' + receiverId + ' 的接收者发送消息');
    
    // 立即清空输入框，提供即时反馈
    messageInput.value = '';
    
    // 显示一个临时的发送中消息
    var messageList = document.getElementById('messageList');
    if (!messageList) {
        console.log('找不到messageList元素，正在重新初始化消息模态框');
        initMessageModal();
        // 重新获取元素
        messageList = document.getElementById('messageList');
        if (!messageList) {
            console.error('无法创建messageList元素，可能存在DOM结构问题');
            alert('无法发送消息，请刷新页面重试');
            return;
        }
    }
    
    var tempMessage = document.createElement('div');
    tempMessage.className = 'message-container sending';
    tempMessage.innerHTML = `
        <div class="message-bubble sending" style="background-color:#e8f5ff;margin-left:auto;opacity:0.8;">
            ${message}
            <div class="message-time">发送中...</div>
        </div>
    `;
    messageList.appendChild(tempMessage);
    messageList.scrollTop = messageList.scrollHeight;
    
    // 发送消息到服务器
    $.ajax({
        url: 'send_message.php',
        type: 'POST',
        data: {
            token: token,
            receiverId: receiverId,
            message: message
        },
        success: function(response) {
            console.log('消息发送成功:', response);
            
            // 检查响应状态
            if (response.success) {
                // 显示发送成功的消息
                tempMessage.innerHTML = `
                    <div class="message-bubble sent" style="background-color:#007bff;color:white;margin-left:auto;">
                        ${message}
                        <div class="message-time" style="color:#ddd;">
                            ${new Date().toLocaleString()}
                        </div>
                    </div>
                `;
                
                // 刷新消息列表
                fetchMessages().then(function(data) {
                    if (data.success) {
                        buildConversationsList(data);
                    }
                });
            } else {
                // 显示发送失败
                tempMessage.innerHTML = `
                    <div class="message-bubble error" style="background-color:#ffdddd;margin-left:auto;">
                        ${message}
                        <div class="message-time">发送失败</div>
                    </div>
                `;
                console.error('发送失败:', response);
            }
        },
        error: function(xhr, status, error) {
            console.error('发送消息时出错:', error);
            
            // 显示发送失败
            tempMessage.innerHTML = `
                <div class="message-bubble error" style="background-color:#ffdddd;margin-left:auto;">
                    ${message}
                    <div class="message-time">发送失败: ${error}</div>
                </div>
            `;
        }
    });
}

// 辅助函数：滚动到消息列表底部
function scrollToBottom(element) {
    if (element) {
        setTimeout(() => {
            element.scrollTop = element.scrollHeight;
        }, 50);
    }
}

// 辅助函数：刷新消息列表
function refreshMessages(receiverId) {
    fetchMessages().then(function(data) {
        if (data.success) {
            var userId = localStorage.getItem('id');
            var conversationMessages = data.messages.filter(function(msg) {
                return (msg.sender_id === userId && msg.receiver_id === receiverId) || 
                       (msg.sender_id === receiverId && msg.receiver_id === userId);
            });
            displayMessages(conversationMessages, receiverId);
        }
    }).catch(function(error) {
        console.error('Failed to refresh messages:', error);
    });
}

// Function to load navbar from navbar.html
function loadNavbar() {
    // 创建导航栏内容
    const navbarContent = `
    <nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #002A5C;">
        <div class="container">
            <a class="navbar-brand" href="index.html">
                <img src="img/team.jpg" width="36" height="36" class="d-inline-block align-top rounded-circle" alt="Logo">
                <span class="navbar-title-chinese">校园碳账户</span> | CarbonTrack
            </a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mr-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.html">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.html">About</a>
                    </li>
                    <li class="nav-item logoutControl" style="display:none;">
                        <a class="nav-link" href="center.html">User Center</a>
                    </li>
                    <li class="nav-item logoutControl" style="display:none;">
                        <a class="nav-link" href="CStore.html">Store</a>
                    </li>
                    <li class="nav-item logoutControl" style="display:none;">
                        <a class="nav-link" href="calculate.html">Carbon Count</a>
                    </li>
                </ul>
                <div class="navbar-nav align-items-center">
                    <!-- Message icon only visible when logged in (add logoutControl class) -->
                    <div class="nav-item mr-2 message-icon-container logoutControl" style="display:none;">
                        <a class="nav-link" href="#" data-toggle="modal" data-target="#messagesModal" id="messagesIcon">
                            <i class="fas fa-envelope"></i>
                            <span class="badge badge-danger badge-counter" id="unreadMessagesCount" style="display: none;">0</span>
                        </a>
                    </div>
                    <span class="navbar-text mx-2 text-light" id="userStatus">Please login or register:</span>
                    <div class="nav-item auth-buttons">
                        <!-- Use iOS-style button classes -->
                        <button class="btn btn-ios-primary btn-sm mx-1" data-toggle="modal" data-target="#loginModal">Sign In</button>
                        <button class="btn btn-ios-secondary btn-sm mx-1" data-toggle="modal" data-target="#registerModal">Register</button>
                        <button class="btn btn-outline-danger btn-sm mx-1" id="logoutButton" style="display:none;">Logout</button>
                    </div>
                </div>
            </div>
        </div>
    </nav>
    `;

    // 插入导航栏到页面中
    $('#navbar-container').html(navbarContent);
    
    // 添加导航栏事件监听器
    setupNavbarEventListeners();
}

function setupNavbarEventListeners() {
    // Set up event listeners for navbar elements
    
    // Unbind any existing handlers first to prevent duplicates
    $('#loginModal form').off('submit');
    $('#registerModal form').off('submit');
    $('#sendVerificationCode').off('click');
    $('#logoutButton').off('click');
    $('#messagesIcon, [data-target="#messagesModal"]').off('click');
    
    // Login form submission
    $('#loginModal form').on('submit', function(e) {
        e.preventDefault();
        
        // Get username and password values
        var username = $('#username').val();
        var password = $('#password').val();
        
        // Prevent double submissions
        var $submitButton = $(this).find('button[type="submit"]');
        $submitButton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Signing in...');
        
        // Call login logic (using AJAX to authenticate)
        $.ajax({
            type: 'POST',
            url: 'login.php',
            data: { username: username, password: password },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // 登录成功
                    var now = new Date();
                    var expiration = new Date(now.getTime() + 7 * 24 * 60 * 60 * 1000); // 设置7天后的时间
                    localStorage.setItem('loggedIn', true); // 存储登录状态
                    localStorage.setItem('username', response.real_username); // 存储用户名
                    localStorage.setItem('expiration', expiration.getTime()); // 存储过期时间戳
                    localStorage.setItem('email', response.email); // 存储email
                    localStorage.setItem('token', response.token);
                    localStorage.setItem('id', response.id);
                    $('[data-target="#loginModal"]').hide();
                    $('[data-target="#registerModal"]').hide();
                    var logoutButton = document.getElementById('logoutButton');
                    
                    // 移除"btn-danger"类
                    logoutButton.classList.remove('btn-danger');
                    
                    // 设置按钮颜色
                    logoutButton.style.color = 'rgba(255, 255, 255, .5)';
                    logoutButton.style.border = '1px solid rgba(255, 255, 255, .5)';
                    $('.logoutControl').show();
                    $('a[href="center.html"]').hide();
                    $('a[href="calculate.html"]').hide();
                    $('a[href="CStore.html"]').hide();

                    $('#loginModal').modal('hide').on('hidden.bs.modal', function() {
                        // 这里的代码会在模态框完全关闭后执行
                        $('.modal-backdrop').remove(); // 如果需要的话，手动移除遮罩
                    });

                    updateLoginStatus(); // 更新页面显示登录状态
                } else {
                    // Show error message
                    alert('Login failed: ' + response.message);
                }
            },
            error: function() {
                alert('Login request failed. Please try again later.');
            },
            complete: function() {
                // Re-enable the button regardless of success/failure
                $submitButton.prop('disabled', false).html('Sign In');
            }
        });
    });
    
    // Register form submission
    $('#registerModal form').on('submit', function(e) {
        e.preventDefault();
        
        // Prevent double submissions
        var $submitButton = $(this).find('button[type="submit"]');
        $submitButton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Creating account...');
        
        registerUser();
        
        // Re-enable button after brief delay
        setTimeout(function() {
            $submitButton.prop('disabled', false).html('Create Account');
        }, 1500);
    });
    
    // Send verification code button
    $('#sendVerificationCode').on('click', function() {
        // Prevent double clicks
        var $button = $(this);
        if ($button.prop('disabled')) return;
        
        $button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Sending...');
        
        sendVerificationCode();
        
        // Disable for 60 seconds to prevent spam
        var countdown = 60;
        var interval = setInterval(function() {
            countdown--;
            if (countdown > 0) {
                $button.html('Resend in ' + countdown + 's');
            } else {
                clearInterval(interval);
                $button.prop('disabled', false).html('Send Verification Code');
            }
        }, 1000);
    });
    
    // Set up other navbar event listeners
    $('#logoutButton').on('click', function() {
        logout();
    });
    
    // Prevent messages from being opened if not logged in
    $('#messagesIcon, [data-target="#messagesModal"]').on('click', function(e) {
        e.preventDefault();
        
        // 检查登录状态
        var loggedIn = localStorage.getItem('loggedIn');
        if (!loggedIn) {
            alert('请先登录以访问消息');
            return;
        }
        
        // 确保模态框已初始化
        if (!document.getElementById('messagesModal')) {
            console.log('点击时发现模态框不存在，正在初始化');
            initMessageModal();
        }
        
        // 显示模态框
        $('#messagesModal').modal('show');
    });
    
    // Check for unread messages when navbar is loaded (only if logged in)
    if (localStorage.getItem('loggedIn')) {
        checkUnreadMessages();
    }
    
    // Clear any previous body padding (to fix desktop whitespace)
    $('body').css('padding-top', '0');
    
    // Apply iOS-style styling to messages icon
    $('.message-icon-container .nav-link').css({
        'padding': '8px 12px',
        'border-radius': '50%',
        'transition': 'all 0.2s ease'
    });
    
    $('.message-icon-container .fa-envelope').css({
        'font-size': '1.1rem',
        'color': 'rgba(255, 255, 255, .5)'
    });
    
    // Add hover effect to message icon
    $('.message-icon-container .nav-link').hover(
        function() {
            $(this).css('background-color', 'rgba(255, 255, 255, 0.1)');
        },
        function() {
            $(this).css('background-color', 'transparent');
        }
    );
    
    // Position badge properly
    $('#unreadMessagesCount').css({
        'position': 'absolute',
        'top': '0',
        'right': '0',
        'transform': 'translate(25%, -25%)',
        'font-size': '0.7rem',
        'padding': '0.25em 0.4em'
    });
    
    // iOS-style navbar handling for mobile
    function applyMobileStyles() {
        // Reset any existing styles to fix potential white bar
        $('.navbar').css({
            'padding': '',
            'background-color': '#002A5C',
            'backdrop-filter': 'none',
            '-webkit-backdrop-filter': 'none'
        });
        
        if (window.innerWidth <= 768) {
            // Add the iOS-style transparent background for mobile
            $('.navbar').css({
                'background-color': 'rgba(0, 42, 92, 0.98)', // More opaque to fix white bar
                'backdrop-filter': 'blur(8px)',
                '-webkit-backdrop-filter': 'blur(8px)',
                'box-shadow': '0 1px 10px rgba(0, 0, 0, 0.15)',
                'padding': '8px 12px'
            });
            
            // Fix potential white bar by ensuring full width and proper background
            $('.navbar').addClass('w-100');
            
            // Adjust logo and text size
            $('.navbar-brand img').css({
                'width': '34px',
                'height': '34px',
                'margin-right': '8px'
            });
            
            $('.navbar-brand').css({
                'font-size': '1.3rem',
                'font-weight': '500'
            });
            
            // Make message icon proportional
            $('.message-icon-container i').css({
                'font-size': '1.3rem'
            });
            
            // Adjust nav links for mobile
            $('.navbar .nav-link').css({
                'font-size': '1.1rem',
                'padding': '0.5rem 0.8rem'
            });
            
            // Fix button layout
            $('.auth-buttons').css({
                'display': 'flex',
                'flex-wrap': 'wrap',
                'justify-content': 'flex-end'
            });
            
            // Style buttons distinctly on mobile too
            $('.auth-buttons .btn-primary').css({
                'margin': '0.25rem',
                'font-size': '0.95rem',
                'padding': '0.375rem 0.75rem',
                'background-color': '#007AFF' // iOS blue color value directly
            });
            
            $('.auth-buttons .btn-outline-light').css({
                'margin': '0.25rem',
                'font-size': '0.95rem',
                'padding': '0.375rem 0.75rem'
            });
            
            // Improve mobile brand and toggler appearance
            $('.navbar-toggler').css({
                'border': 'none',
                'padding': '4px 8px'
            });
            
            // Add top padding to body based on navbar height for mobile only
            setTimeout(function() {
                const navbarHeight = $('.navbar').outerHeight();
                $('body').css('padding-top', navbarHeight + 'px');
            }, 50);
            
            // Very small screens
            if (window.innerWidth <= 576) {
                $('.navbar .navbar-text').hide();
                $('.navbar-title-chinese').css({
                    'font-size': '1.15rem',
                    'font-weight': '500'
                });
                
                $('.navbar-brand').css({
                    'font-size': '1.15rem'
                });
            } else {
                $('.navbar .navbar-text').show();
            }
        } else {
            // Reset styles for desktop
            $('body').css('padding-top', '0');
            $('.navbar').removeClass('navbar-scroll navbar-scroll-hidden w-100');
            $('.navbar').css({
                'background-color': '#002A5C',
                'backdrop-filter': 'none',
                '-webkit-backdrop-filter': 'none',
                'box-shadow': 'none',
                'padding': ''
            });
            
            $('.navbar-brand img').css({
                'width': '36px',
                'height': '36px',
                'margin-right': '10px'
            });
            
            $('.navbar-brand').css({
                'font-size': '1.25rem',
                'font-weight': '400'
            });
            
            $('.message-icon-container i').css({
                'font-size': '1rem'
            });
            
            $('.navbar .nav-link').css({
                'font-size': '',
                'padding': ''
            });
            
            $('.auth-buttons').css({
                'display': '',
                'flex-wrap': '',
                'justify-content': ''
            });
            
            $('.auth-buttons .btn').css({
                'margin': '',
                'font-size': '',
                'padding': ''
            });
            
            $('.navbar .navbar-text').show();
            $('.navbar-title-chinese').css({
                'font-size': '',
                'font-weight': ''
            });
        }
    }
    
    // Apply styles immediately and with a short delay to ensure they take effect
    applyMobileStyles();
    setTimeout(applyMobileStyles, 100); // Apply again after a short delay
    
    // Add scroll behavior
    let lastScrollTop = 0;
    $(window).scroll(function() {
        let st = $(this).scrollTop();
        
        // Only apply scroll behavior on mobile
        if (window.innerWidth <= 768) {
            if (st > lastScrollTop && st > 70) {
                // Scroll down - hide navbar
                $('.navbar').addClass('navbar-scroll navbar-scroll-hidden');
            } else {
                // Scroll up - show navbar
                $('.navbar').addClass('navbar-scroll').removeClass('navbar-scroll-hidden');
            }
            
            // At the top, remove scroll styling
            if (st === 0) {
                $('.navbar').removeClass('navbar-scroll');
            }
        }
        
        lastScrollTop = st;
    });
    
    // Handle window resize
    $(window).resize(function() {
        applyMobileStyles();
    });
}

// 初始化消息模态框结构
function initMessageModal() {
    console.log('初始化消息模态框');
    
    // 检查模态框是否已存在
    if (document.getElementById('messagesModal')) {
        console.log('消息模态框已存在，无需重新创建');
        return;
    }
    
    // 创建模态框HTML
    const modalHTML = `
    <div class="modal fade" id="messagesModal" tabindex="-1" role="dialog" aria-labelledby="messagesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="messagesModalLabel">Messages</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-4" id="conversationList">
                            <!-- 对话列表将在这里动态加载 -->
                        </div>
                        <div class="col-8">
                            <div id="messageList" style="height: 400px; overflow-y: auto;">
                                <!-- 消息列表将在这里动态加载 -->
                            </div>
                            <div class="input-group mt-3">
                                <input type="text" class="form-control" id="messageInput" placeholder="Enter Message...">
                                <div class="input-group-append">
                                    <button class="btn btn-primary" type="button" id="sendMessage">Send</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>`;
    
    // 将模态框添加到body
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    
    // 绑定发送按钮点击事件
    $('#sendMessage').on('click', function() {
        sendMessage();
    });
    
    // 绑定输入框回车事件
    $('#messageInput').on('keypress', function(e) {
        if (e.which === 13) {
            sendMessage();
            return false;
        }
    });
    
    console.log('消息模态框已创建并初始化完成');
}





