$(document).ready(function() {
    // Load navbar
    loadNavbar();
    
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
    $('#conversationList').empty().html('<div class="text-center p-3"><div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div><div class="mt-2">加载消息中...</div></div>');
    
    // 检查用户登录状态
    var token = localStorage.getItem('token');
    if (!token) {
        $('#conversationList').empty().html('<div class="alert alert-warning">请先登录</div>');
        $('#messageList').empty().html('<div class="alert alert-warning text-center">请先登录以查看消息</div>');
        return;
    }
    
    // 添加加载指示器
    $('#messageList').html('<div class="text-center p-3"><div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div><div class="mt-2">加载消息中...</div></div>');
    
    // 获取消息数据
    console.log('开始获取消息数据');
    fetchMessages().then(function(data) {
        console.log('消息数据获取成功，开始构建对话列表');
        
        // 构建对话列表
            buildConversationsList(data);
    }).catch(function(error) {
        console.error('获取消息出错:', error);
        $('#conversationList').empty().html('<div class="alert alert-danger">加载消息失败</div>');
        $('#messageList').empty().html('<div class="alert alert-danger text-center">加载消息失败: ' + error.message + '</div>');
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
                $('#loading').hide();
                if (response.success) {
                    console.log('登录成功，身份：', response.real_username);
                    $('#loginModal').modal('hide');
                    localStorage.setItem('loggedIn', true);
                    localStorage.setItem('username', response.real_username);
                    localStorage.setItem('token', response.token);
                    
                    // 如果响应中包含用户ID，保存它
                    if (response.user_id) {
                        localStorage.setItem('userId', response.user_id);
                        console.log('已保存用户ID:', response.user_id);
                    }
                    
                    // 设置7天的到期时间
                    localStorage.setItem('expiration', new Date().getTime() + 7 * 24 * 60 * 60 * 1000);
                    updateLoginStatus();
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
                    showAlert(response.message || '登录失败', 'error');
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
                }
            },
            error: function() {
                $('#loading').hide();
                showAlert('登录请求失败，请稍后再试。', 'error');
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
            }
        });
    });
    // 页面加载时检查登录状态
    checkLoginStatus();
    updateButtonForRemainingTime();

    // 点击站内信图标时打开模态框并加载消息
    $('#messagesIcon, [data-target="#messagesModal"]').on('click', function(e) {
        e.preventDefault();
        
        var loggedIn = localStorage.getItem('loggedIn');
        if (!loggedIn) {
            e.preventDefault();
            e.stopPropagation();
            showAlert('请先登录以访问消息', 'warning');
            return false;
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
    
    // 将消息图标添加class便于操作
    $('.fa-envelope').addClass('msg-icon');
    
    // 设置图标容器为relative，以便放置徽章
    $('.fa-envelope').parent().css('position', 'relative');
    
    // 启动未读消息检查
    startUnreadMessageCheck();
});
function googleTranslateElementInit() {
  new google.translate.TranslateElement({pageLanguage: 'en', layout: google.translate.TranslateElement.InlineLayout.SIMPLE}, 'google_translate_element');
}

function checkUnreadMessages() {
    console.log('检查未读消息');
    var token = localStorage.getItem('token');
    var username = localStorage.getItem('username');
    
    if (!token || !username) {
        console.log('未登录，不检查未读消息');
        return; // 如果未登录，不检查
    }
    
    // 尝试从localStorage获取userId
    var userId = localStorage.getItem('userId');
    
    // 如果没有userId，尝试从token中解析
    if (!userId) {
        console.log('从localStorage中获取userId失败，尝试通过getUserInfo获取');
        // 向服务器请求用户信息
        getUserInfo(token);
        return;
    }
    
    console.log('使用userId检查未读消息:', userId);
    
    $.ajax({
        url: 'chkmsg.php',
        type: 'POST',
        data: {
            token: token,
            uid: userId
        },
        success: function(response) {
            console.log('未读消息检查结果:', response);
            if (response.success) {
                const unreadCount = response.unreadCount || 0;
                
                // 获取消息图标元素
                const msgIcon = $('.msg-icon');
                
                if (unreadCount > 0) {
                    // 有未读消息，改变图标颜色和添加未读数量
                    msgIcon.addClass('text-danger');
                    
                    // 移除旧的消息计数
                    $('.msg-badge').remove();
                    
                    // 添加未读消息数量徽章
                    msgIcon.parent().append(`<span class="msg-badge position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">${unreadCount}</span>`);
                } else {
                    // 无未读消息，恢复图标颜色和移除未读数量
                    msgIcon.removeClass('text-danger');
                    $('.msg-badge').remove();
                }
            }
        },
        error: function(xhr, status, error) {
            console.error('检查未读消息失败:', error);
        }
    });
}

// 获取用户信息
function getUserInfo(token) {
    $.ajax({
        url: 'get_user_info.php',
        type: 'POST',
        data: { token: token },
        success: function(response) {
            if (response.success && response.user_id) {
                localStorage.setItem('userId', response.user_id);
                // 获取到用户ID后，再次检查未读消息
                checkUnreadMessages();
            }
        },
        error: function(xhr, status, error) {
            console.error('获取用户信息失败:', error);
        }
    });
}

// 定时检查未读消息
function startUnreadMessageCheck() {
    // 立即检查一次
    checkUnreadMessages();
    
    // 每30秒检查一次
    setInterval(checkUnreadMessages, 30000);
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
        
        // 开始检查未读消息
        startUnreadMessageCheck();
        
        // 其他已登录状态的UI更新...
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
                // 关闭注册模态框，显示成功消息，然后打开登录模态框
                $('#registerModal').modal('hide');
                showAlert('注册成功！Register success!', 'success', function() {
                    // 在用户关闭提示后显示登录模态框
                    $('#loginModal').modal('show');
                });
                
                // 清空表单
                $('#regusername, #regpassword, #email, #verificationCode').val('');
                $('#registerError').hide();
            } else {
                // 显示注册失败消息
                $('#registerError').text(response.message).show();
            }
        },
        error: function() {
            // 显示错误信息
            showAlert('注册请求失败，请稍后再试。Register request failed, please try later.', 'error');
            $('#registerError').text('注册请求失败，请稍后再试。Register request failed, please try later.').show();
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
        showAlert('请填写所有必填信息后再发送验证码。', 'warning');
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
                    showAlert('验证码发送失败: ' + response.error, 'error');
                }
            },
            error: function() {
                $('#emailHelp').hide();
                showAlert('请求发送验证码失败，请稍后再试。', 'error');
            }
        });
    } else {
        showAlert('请输入邮箱地址。', 'warning');
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
        
        try {
        $.ajax({
            type: 'POST',
            url: 'getmsg.php',
                data: JSON.stringify({ token: token }),
            contentType: 'application/json',
            success: function(data) {
                    console.log('消息获取成功，原始响应:', data);
                    
                    // 检查数据结构
                    if (typeof data === 'string') {
                        try {
                            // 尝试解析字符串为JSON
                            data = JSON.parse(data);
                            console.log('解析字符串响应为JSON对象:', data);
                        } catch (e) {
                            console.error('解析响应字符串失败:', e);
                        }
                    }
                    
                    // 确保data是一个包含messages数组的对象
                    if (!data || typeof data !== 'object') {
                        console.error('响应数据格式错误，期望为对象，实际为:', typeof data);
                        data = { success: false, messages: [] };
                    }
                    
                    // 如果响应中没有messages字段，添加一个空数组
                    if (!data.messages) {
                        console.warn('响应中没有messages字段，添加空数组');
                        data.messages = [];
                    }
                    
                    // 确保messages是一个数组
                    if (!Array.isArray(data.messages)) {
                        console.error('messages不是数组，转换为数组');
                        // 如果messages是对象，尝试将其转换为数组
                        if (typeof data.messages === 'object') {
                            data.messages = [data.messages];
                        } else {
                            data.messages = [];
                        }
                    }
                    
                    // 为每条消息添加默认字段
                    data.messages.forEach(function(message, index) {
                        // 确保每条消息都有content字段
                        if (!message.content && (message.message || message.text)) {
                            // 不需要在这里净化，displayMessages函数会处理
                            message.content = message.message || message.text;
                        }
                        
                        // 确保每条消息都有时间戳
                        if (!message.send_time && !message.created_at) {
                            message.send_time = new Date().toISOString();
                        }
                    });
                    
                resolve(data);
            },
                error: function(xhr, status, error) {
                    console.error('无法加载消息:', error);
                    // 记录详细错误信息
                    console.error('错误详情:', {
                        status: xhr.status,
                        statusText: xhr.statusText,
                        responseText: xhr.responseText
                    });
                    reject(new Error('加载消息时发生错误: ' + error));
                }
            });
        } catch (err) {
            console.error('发送请求时发生异常:', err);
            reject(err);
        }
    });
}


function buildConversationsList(data) {
    console.log('开始构建对话列表，收到的数据:', data);
    
    // 获取对话列表容器
    var conversationList = $('#conversationList');
    if (!conversationList.length) {
        console.error('无法创建对话列表容器，可能存在DOM结构问题');
        showAlert('无法加载消息界面，请刷新页面重试', 'error');
        return;
    }
    
    // 清空现有列表
    conversationList.empty();
    
    try {
        // 如果没有消息或消息为空，显示提示
        if (!data || !data.messages || data.messages.length === 0) {
            console.warn('没有收到任何消息数据');
            conversationList.append('<div class="alert alert-info">暂无消息</div>');
            $('#messageList').html('<div class="alert alert-info text-center">暂无消息</div>');
            return;
        }
        
        console.log('收到消息数量:', data.messages.length);
        
        // 按发送者ID分组消息
    var conversations = {};
        data.messages.forEach(function(message, index) {
            console.log(`处理第 ${index+1} 条消息:`, message);
            
            // 确保消息有发送者ID
            var senderId = message.sender_id || 'unknown';
            
            if (!conversations[senderId]) {
                conversations[senderId] = {
                    sender_id: senderId,
                messages: []
            };
        }
            
            conversations[senderId].messages.push(message);
        });
        
        console.log('分组后的对话:', conversations);
        
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
                        <div class="small text-muted">点击查看消息 (${conversation.messages.length}条)</div>
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
                console.log('点击对话项:', senderId, '，消息数量:', conversation.messages.length);
                
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
            console.log('默认选中第一个对话:', firstSenderId, '，消息数量:', conversations[firstSenderId].messages.length);
            
            // 延迟一点时间再显示消息，确保DOM已经完全渲染
            setTimeout(() => {
                displayMessages(conversations[firstSenderId].messages, firstSenderId);
            }, 100);
        }
    } catch (err) {
        console.error('构建对话列表时出错:', err);
        conversationList.append('<div class="alert alert-danger">加载对话列表时出错，请刷新页面重试</div>');
    }
}


function displayMessages(messages, sender) {
    console.log('开始显示消息，消息数量:', messages ? messages.length : 0, '发送者ID:', sender);
    
    // 保存当前聊天的发送者ID，用于发送回复消息
    localStorage.setItem('currentChatPartnerId', sender);
    
    // 获取messageList元素
    let messageList = document.getElementById('messageList');
    if (!messageList) {
        console.error('无法创建messageList元素，可能存在DOM结构问题');
        showAlert('无法加载消息界面，请刷新页面重试', 'error');
        return;
    }
    
    // 清空现有消息
    messageList.innerHTML = '';
    
    // 如果没有消息，显示提示信息
    if (!messages || messages.length === 0) {
        messageList.innerHTML = '<div class="text-center p-4"><i class="fas fa-comment-slash fa-3x text-muted mb-3"></i><p>暂无消息记录</p></div>';
        return;
    }
    
    // 获取当前用户ID
    const currentUserId = localStorage.getItem('userId');
    console.log('当前用户ID (localStorage):', currentUserId);
    
    // 对消息按时间排序
    messages.sort(function(a, b) {
        return new Date(a.send_time || a.created_at || 0) - new Date(b.send_time || b.created_at || 0);
    });
    
    // 用于跟踪日期变化
    let lastDate = '';
    
    // 显示消息
    messages.forEach(function(message, index) {
        try {
            console.log(`处理第 ${index+1}/${messages.length} 条消息:`, message);
            
            // 处理时间戳
            const timestamp = message.send_time || message.created_at || new Date().toISOString();
            const messageDate = new Date(timestamp);
            const formattedDate = messageDate.toLocaleDateString();
            const formattedTime = messageDate.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            
            // 如果日期变化了，添加日期分隔线
            if (formattedDate !== lastDate) {
                const dateDiv = document.createElement('div');
                dateDiv.className = 'text-center my-3';
                dateDiv.innerHTML = `<span class="badge bg-secondary px-3 py-2">${formattedDate}</span>`;
                messageList.appendChild(dateDiv);
                lastDate = formattedDate;
            }
            
            // 创建消息容器
            const messageContainer = document.createElement('div');
            messageContainer.className = 'message-container';
            
            // 确定消息类型（发送/接收）
            const isSent = message.sender_id == currentUserId;
            console.log(`消息 ${index+1} 是否为发送消息:`, isSent, '(sender_id:', message.sender_id, ', currentUserId:', currentUserId, ')');
            
            // 获取安全的消息内容
            let safeContent;
            try {
                // 安全处理HTML内容
                safeContent = sanitizeHTML(message.content || '');
                console.log(`消息 ${index+1} 内容类型:`, typeof message.content);
                console.log(`消息 ${index+1} 原始内容:`, message.content);
                console.log(`消息 ${index+1} 安全处理后的内容:`, safeContent);
            } catch (err) {
                console.error(`处理消息 ${index+1} 内容时出错:`, err);
                safeContent = '<p>消息内容无法显示</p>';
            }
            
            // 创建消息气泡
            const bubbleClass = isSent ? 'sent' : 'received';
            
            // 设置消息内容
            messageContainer.innerHTML = `
                <div class="message-bubble ${bubbleClass}" style="overflow-wrap: break-word;">
                    ${safeContent}
                    <div class="message-time">${formattedTime}</div>
                </div>
                <div style="clear: both;"></div>
            `;
            
            // 添加到消息列表
            messageList.appendChild(messageContainer);
        } catch (err) {
            console.error(`处理消息 ${index+1} 时出错:`, err, message);
        }
    });
    
    // 滚动到底部
    scrollToBottom(messageList);
    
    // 确保消息容器可见
    $('#messageContainer').removeClass('d-none').addClass('d-flex');
    $('#noConversationSelected').addClass('d-none');
}

// 滚动消息列表到底部
function scrollToBottom(element) {
    if (!element) return;
    
    setTimeout(() => {
        element.scrollTop = element.scrollHeight;
    }, 100);
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
        showAlert('发送失败：无法确定接收者或未登录', 'error');
        return;
    }
    
    console.log('准备向ID为 ' + receiverId + ' 的接收者发送消息');
    
    // 立即清空输入框，提供即时反馈
    messageInput.value = '';
    
    // 生成临时消息ID
    const tempMessageId = 'msg_' + Date.now();
    
    // 显示一个临时的发送中消息
    var messageList = document.getElementById('messageList');
    if (!messageList) {
        console.error('找不到messageList元素，可能存在DOM结构问题');
        showAlert('无法发送消息，请刷新页面重试', 'error');
        return;
    }
    
    // 安全处理消息内容（仅用于显示）
    var safeMessageDisplay = sanitizeHTML(message);
    
    // 创建临时消息元素
    const tempMessage = document.createElement('div');
    tempMessage.className = 'message-container';
    tempMessage.innerHTML = `
        <div class="message-bubble sent" data-temp-id="${tempMessageId}">
            ${safeMessageDisplay}
            <div class="message-time">发送中...</div>
        </div>
    `;
    
    // 添加到消息列表
    messageList.appendChild(tempMessage);
    
    // 滚动到底部
    scrollToBottom(messageList);
    
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
            
            // 获取消息气泡元素
            const messageBubble = document.querySelector(`[data-temp-id="${tempMessageId}"]`);
            
            // 检查响应状态
            if (response.success) {
                // 更新时间戳
                if (messageBubble) {
                    const timeElement = messageBubble.querySelector('.message-time');
                    if (timeElement) {
                        const timestamp = response.timestamp || response.created_at || new Date().toISOString();
                        const date = new Date(timestamp);
                        timeElement.textContent = date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                    }
                }
                
                // 触发未读消息检查以更新UI
                checkUnreadMessages();
} else {
                // 显示发送失败
                if (messageBubble) {
                    messageBubble.classList.add('failed');
                    const timeElement = messageBubble.querySelector('.message-time');
                    if (timeElement) {
                        timeElement.textContent = '发送失败';
                        timeElement.style.color = '#ff4d4f';
                    }
                }
                
                // 显示错误提示
                showAlert('消息发送失败：' + (response.message || '未知错误'), 'error');
            }
        },
        error: function(xhr, status, error) {
            console.error("Error sending message:", error);
            
            // 将临时消息状态更新为发送失败
            const messageBubble = document.querySelector(`[data-temp-id="${tempMessageId}"]`);
            if (messageBubble) {
                messageBubble.classList.add('failed');
                const timeElement = messageBubble.querySelector('.message-time');
                if (timeElement) {
                    timeElement.textContent = '发送失败';
                    timeElement.style.color = '#ff4d4f';
                }
            }
            
            // 显示错误提示
            showAlert('消息发送失败，请稍后再试。', 'error');
        }
    });
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
    
    // 初始化所有模态框
    initAllModals();
    
    // 设置事件监听器
    setupNavbarEventListeners();
    
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
            $('#loading').hide();
            if (response.success) {
                    console.log('登录成功，身份：', response.real_username);
                    $('#loginModal').modal('hide');
                    localStorage.setItem('loggedIn', true);
                    localStorage.setItem('username', response.real_username);
                    localStorage.setItem('token', response.token);
                    
                    // 如果响应中包含用户ID，保存它
                    if (response.user_id) {
                        localStorage.setItem('userId', response.user_id);
                        console.log('已保存用户ID:', response.user_id);
                    }
                    
                    // 设置7天的到期时间
                    localStorage.setItem('expiration', new Date().getTime() + 7 * 24 * 60 * 60 * 1000);
                    updateLoginStatus();
                    $submitButton.prop('disabled', false).text('Login');
        } else {
                    showAlert(response.message || '登录失败', 'error');
                    $submitButton.prop('disabled', false).text('Login');
            }
        },
        error: function() {
                $('#loading').hide();
                showAlert('登录请求失败，请稍后再试。', 'error');
                $submitButton.prop('disabled', false).text('Login');
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
        
        var loggedIn = localStorage.getItem('loggedIn');
        if (!loggedIn) {
            e.preventDefault();
            e.stopPropagation();
            showAlert('请先登录以访问消息', 'warning');
            return false;
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

// 初始化所有模态框
function initAllModals() {
    console.log('初始化所有模态框');
    
    // 初始化站内信模态框
    initMessageModal();
    
    // 初始化登录模态框
    initLoginModal();
    
    // 初始化注册模态框
    initRegisterModal();
}

// 初始化消息模态框
function initMessageModal() {
    console.log('初始化消息模态框');
    
    // 检查模态框是否已经存在
    if ($('#messagesModal').length) {
        console.log('消息模态框已存在，无需重新创建');
        return;
    }
    
    // 添加消息模态框样式
    const messageStyles = `
    <style>
        .message-container {
            margin-bottom: 15px;
            display: flex;
            flex-direction: column;
            width: 100%;
            clear: both;
            overflow: hidden; /* Add this to contain floated elements */
        }
        .message-bubble {
            max-width: 80%;
            padding: 10px 15px;
            border-radius: 18px;
            position: relative;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
            word-break: break-word;
            display: inline-block; /* Add this to ensure proper sizing */
        }
        .message-bubble.sent {
            background-color: #dcf8c6;
            align-self: flex-end;
            margin-left: auto;
            border-bottom-right-radius: 5px;
            float: right; /* Add this to ensure right alignment */
            clear: both; /* Add this to prevent wrapping issues */
        }
        .message-bubble.received {
            background-color: #f1f0f0;
            align-self: flex-start;
            margin-right: auto;
            border-bottom-left-radius: 5px;
            float: left; /* Add this to ensure left alignment */
            clear: both; /* Add this to prevent wrapping issues */
        }
        .message-time {
            font-size: 0.7rem;
            color: #999;
            margin-top: 5px;
            text-align: right;
        }
        .conversation-item {
            cursor: pointer;
            transition: background-color 0.2s;
            border-radius: 8px;
            margin-bottom: 5px;
        }
        .conversation-item:hover {
            background-color: #f5f5f5;
        }
        .conversation-item.active {
            background-color: #e9ecef;
            border-left: 3px solid #007bff;
        }
        .message-input-container {
            border-top: 1px solid #dee2e6;
            padding-top: 15px;
        }
        #messageList {
            background-color: #e5ddd5;
            background-image: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI1MCIgaGVpZ2h0PSI1MCIgb3BhY2l0eT0iMC4yIj48cGF0aCBkPSJNMTIuNSAwdjUwaDI1VjBIMTIuNXoiIGZpbGw9IiNmZmYiLz48L3N2Zz4=');
            padding: 15px;
            border-radius: 8px;
        }
        .no-conversation {
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            color: #6c757d;
        }
        .no-conversation i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        /* 添加HTML内容样式 */
        .message-bubble p {
            margin-bottom: 0.5rem;
            line-height: 1.4;
        }
        .message-bubble p:last-child {
            margin-bottom: 0;
        }
        .message-bubble ul, .message-bubble ol {
            padding-left: 1.5rem;
            margin-bottom: 0.5rem;
        }
        .message-bubble li {
            margin-bottom: 0.2rem;
        }
        .message-bubble a {
            color: #0d6efd;
            text-decoration: underline;
        }
        .message-bubble.sent a {
            color: #0d6efd;
        }
        .message-bubble img {
            max-width: 100%;
            height: auto;
            margin: 0.5rem 0;
            border-radius: 4px;
        }
    </style>`;
    
    // 将样式添加到头部
    if (!$('head style:contains(".message-bubble")').length) {
        $('head').append(messageStyles);
    }
    
    // 创建模态框HTML结构
    var modalHTML = `
        <div class="modal fade" id="messagesModal" tabindex="-1" aria-labelledby="messagesModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="messagesModalLabel">消息</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <!-- 左侧会话列表 -->
                            <div class="col-md-4 border-end">
                                <div class="mb-3">
                                    <input type="text" class="form-control" id="conversationSearch" placeholder="搜索会话...">
                                </div>
                                <div id="conversationList" class="overflow-auto" style="max-height: 400px;">
                                    <!-- 会话列表将通过JS动态加载 -->
                                </div>
                            </div>
                            <!-- 右侧消息内容 -->
                            <div class="col-md-8">
                                <div id="messageContainer" class="d-none d-flex flex-column">
                                    <div id="messageList" class="overflow-auto flex-grow-1" style="height: 350px; padding: 10px;">
                                        <!-- 消息内容将通过JS动态加载 -->
                                    </div>
                                    <div class="message-input-container mt-3">
                                        <div class="input-group">
                                            <textarea id="messageInput" class="form-control" placeholder="输入消息..." rows="2"></textarea>
                                            <button class="btn btn-primary" id="sendMessageBtn">发送</button>
                                        </div>
                                    </div>
                                </div>
                                <div id="noConversationSelected" class="no-conversation" style="height: 400px;">
                                    <i class="fas fa-comments"></i>
                                    <p>选择一个会话开始聊天</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // 将模态框添加到body
    $('body').append(modalHTML);
    
    // 初始化后绑定事件
    $('#sendMessageBtn').click(function() {
        sendMessage();
    });
    
    // 在输入框中按Enter键发送消息
    $('#messageInput').keypress(function(e) {
        if (e.which === 13 && !e.shiftKey) { // 按下Enter键且未按Shift键
            e.preventDefault(); // 阻止默认行为（换行）
            sendMessage(); // 发送消息
        }
    });
    
    // 搜索会话
    $('#conversationSearch').on('input', function() {
        const searchText = $(this).val().toLowerCase();
        $('.conversation-item').each(function() {
            const username = $(this).find('h6').text().toLowerCase();
            const lastMessage = $(this).find('small').text().toLowerCase();
            if (username.includes(searchText) || lastMessage.includes(searchText)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
    
    // 为模态框显示事件添加处理程序，当模态框显示时加载会话列表
    $('#messagesModal').on('shown.bs.modal', function() {
        console.log('消息模态框已显示，加载会话列表');
        
        // 默认显示"选择一个会话"提示
        $('#messageContainer').addClass('d-none').removeClass('d-flex');
        $('#noConversationSelected').removeClass('d-none');
        
        // 加载会话列表
        loadConversations();
    });
    
    console.log('消息模态框初始化完成');
}

// 加载会话列表
function loadConversations() {
    console.log('开始加载会话列表');
    
    // 获取登录token
    var token = localStorage.getItem('token');
    if (!token) {
        console.error('未找到token，用户可能未登录');
        showAlert('请先登录以查看会话列表', 'warning');
        return;
    }

    // 显示加载状态
    $('#conversationList').html('<div class="text-center"><div class="spinner-border spinner-border-sm text-primary" role="status"></div><span class="ms-2">加载中...</span></div>');

    // 使用getmsg.php获取所有消息，然后从消息中构建会话列表
    $.ajax({
        url: 'getmsg.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ token: token }),
        success: function(response) {
            if (response.success) {
                // 清空列表
                $('#conversationList').empty();
                
                // 从消息中提取唯一的对话者
                const conversations = {};
                const currentUserId = response.debug?.user_id || "";
                
                if (response.messages && response.messages.length > 0) {
                    // 处理消息，构建会话列表
                    response.messages.forEach(function(message) {
                        // 确定对话者ID
                        let partnerId;
                        
                        // 如果当前用户是接收者，那么发送者就是对话伙伴
                        if (message.receiver_id == currentUserId) {
                            partnerId = message.sender_id;
            } else {
                            // 否则接收者是对话伙伴
                            partnerId = message.receiver_id;
                        }
                        
                        // 如果这个对话伙伴还没有记录，创建一个
                        if (!conversations[partnerId]) {
                            conversations[partnerId] = {
                                user_id: partnerId,
                                username: '用户 ' + partnerId,
                                messages: [],
                                unread_count: 0,
                                last_message: '',
                                last_time: null
                            };
                        }
                        
                        // 添加消息到这个对话
                        conversations[partnerId].messages.push(message);
                        
                        // 如果消息是未读的并且当前用户是接收者，增加未读计数
                        if (message.is_read == 0 && message.receiver_id == currentUserId) {
                            conversations[partnerId].unread_count++;
                        }
                    });
                    
                    // 为每个对话找出最新的消息
                    Object.values(conversations).forEach(function(conversation) {
                        // 按时间排序消息
                        conversation.messages.sort(function(a, b) {
                            return new Date(b.send_time || b.created_at || 0) - new Date(a.send_time || a.created_at || 0);
                        });
                        
                        // 设置最新消息
                        if (conversation.messages.length > 0) {
                            const latestMessage = conversation.messages[0];
                            // 截取消息内容，防止过长
                            const content = latestMessage.content || '';
                            conversation.last_message = content.length > 15 ? content.substring(0, 15) + '...' : content;
                            conversation.last_time = latestMessage.send_time || latestMessage.created_at || '';
                        }
                    });
                    
                    // 将对话转换为数组并按最新消息时间排序
                    const sortedConversations = Object.values(conversations).sort(function(a, b) {
                        const timeA = a.last_time ? new Date(a.last_time) : new Date(0);
                        const timeB = b.last_time ? new Date(b.last_time) : new Date(0);
                        return timeB - timeA;
                    });
                    
                    // 显示排序后的会话
                    if (sortedConversations.length > 0) {
                        sortedConversations.forEach(function(conversation) {
                            const lastMessage = conversation.last_message || '无消息';
                            const unreadCount = conversation.unread_count || 0;
                            const unreadBadge = unreadCount > 0 ? `<span class="badge bg-danger rounded-pill">${unreadCount}</span>` : '';
                            
                            const conversationItem = `
                                <div class="conversation-item p-2 border-bottom" data-user-id="${conversation.user_id}">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="conversation-info">
                                            <h6 class="mb-1">${sanitizeHTML(conversation.username)}</h6>
                                            <small class="text-muted text-truncate d-block" style="max-width: 150px;">${sanitizeHTML(lastMessage)}</small>
                                        </div>
                                        ${unreadBadge}
                                    </div>
                                </div>
                            `;
                            
                            $('#conversationList').append(conversationItem);
                        });
                        
                        // 绑定点击事件
                        $('.conversation-item').click(function() {
                            var userId = $(this).data('user-id');
                            var username = $(this).find('h6').text();
                            
                            console.log('点击会话项，用户ID:', userId, '用户名:', username);
                            
                            // 显示消息区域，隐藏提示
                            $('#messageContainer').removeClass('d-none').addClass('d-flex');
                            $('#noConversationSelected').addClass('d-none');
                            
                            // 加载与该用户的消息
                            loadMessages(userId, username);
                            
                            // 高亮选中的会话
                            $('.conversation-item').removeClass('active');
                            $(this).addClass('active');
                            
                            // 清除未读标记
                            $(this).find('.badge').remove();
                        });
                    } else {
                        // 没有会话时显示提示
                        $('#conversationList').html('<div class="text-center text-muted py-3">暂无会话</div>');
                    }
                } else {
                    // 没有消息时显示提示
                    $('#conversationList').html('<div class="text-center text-muted py-3">暂无会话</div>');
                }
            } else {
                // 显示错误信息
                showAlert(response.message || '加载会话失败', 'error');
                $('#conversationList').html('<div class="text-center text-danger">加载失败</div>');
            }
        },
        error: function(xhr, status, error) {
            console.error('加载会话列表失败:', error);
            // 显示错误信息
            showAlert('网络错误，请稍后再试', 'error');
            $('#conversationList').html('<div class="text-center text-danger">加载失败</div>');
        }
    });
}

// 初始化登录模态框
function initLoginModal() {
    console.log('初始化登录模态框');
    
    // 检查模态框是否已存在
    if (document.getElementById('loginModal')) {
        console.log('登录模态框已存在，无需重新创建');
        return;
    }
    
    // 创建模态框HTML
    const modalHTML = `
    <div class="modal fade" id="loginModal" tabindex="-1" role="dialog" aria-labelledby="loginModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="loginModalLabel">Sign In</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="form-group">
                            <label for="username">Account / Username / Email</label>
                            <input type="text" class="form-control" id="username" placeholder="Enter your username or email">
                        </div>
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" class="form-control" id="password" placeholder="Enter your password">
                        </div>
                        <button type="submit" class="btn btn-success btn-block">Sign In</button>
                    </form>
                </div>
                <div class="modal-footer" style="flex-direction: column;">
                    <div class="alert alert-warning" role="alert" id="refreshAlert" style="display: none; width: 100%; border-radius: 12px;">
                        If you're having trouble, please reload the page or press <a href="#" onclick="location.reload();">here</a>.
                    </div>
                    <div style="display: flex; justify-content: space-between; width: 100%;">
                        <a href="iforgot.html">Forgot password?</a>
                        <a href="#" data-toggle="modal" data-target="#registerModal">Create Account</a>
                    </div>
                </div>
            </div>
        </div>
    </div>`;
    
    // 将模态框添加到body
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    
    console.log('登录模态框已创建并初始化完成');
}

// 初始化注册模态框
function initRegisterModal() {
    console.log('初始化注册模态框');
    
    // 检查模态框是否已存在
    if (document.getElementById('registerModal')) {
        console.log('注册模态框已存在，无需重新创建');
        return;
    }
    
    // 创建模态框HTML
    const modalHTML = `
    <div class="modal fade" id="registerModal" tabindex="-1" role="dialog" aria-labelledby="registerModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="registerModalLabel">Create Account</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="form-group">
                            <label for="regusername">Username</label>
                            <input type="text" class="form-control required-input" id="regusername" placeholder="Choose a username">
                        </div>
                        <div class="form-group">
                            <label for="regpassword">Password</label>
                            <input type="password" class="form-control required-input" id="regpassword" placeholder="Create a password">
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" class="form-control required-input" id="email" name="email" placeholder="Enter your email address" required>
                            
                            <div style="margin-top: 16px;">
                                <button type="button" class="btn btn-outline-primary" id="sendVerificationCode">Send Verification Code</button>
                            </div>

                            <div style="margin-top: 16px;">
                                <label for="verificationCode">Verification Code</label>
                                <input type="text" class="form-control" id="verificationCode" placeholder="Enter the code sent to your email" required>
                                <small id="emailHelp" class="form-text text-muted" style="display: none; margin-top: 8px;">The code has been sent to your email.</small>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success btn-block">Create Account</button>
                        <div id="registerError" style="display:none;" class="alert alert-danger mt-3"></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <div class="alert alert-warning" role="alert" id="refreshAlert" style="display: none; width: 100%; border-radius: 12px;">
                        If you're having trouble, please reload the page or press <a href="#" onclick="location.reload();">here</a>.
                    </div>
                    <a href="#" data-toggle="modal" data-target="#loginModal">Already have an account? Sign in</a>
                </div>
            </div>
        </div>
    </div>`;
    
    // 将模态框添加到body
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    
    console.log('注册模态框已创建并初始化完成');
}

// 安全净化HTML内容，防止XSS攻击
function sanitizeHTML(html) {
    if (!html) return '';
    
    // 检查是否已经是HTML格式（包含标签）
    const containsHTML = /<[a-z][\s\S]*>/i.test(html);
    
    if (!containsHTML) {
        // 如果不是HTML，按纯文本处理
        const tempElement = document.createElement('div');
        tempElement.textContent = html;
        return tempElement.innerHTML;
    }
    
    // 创建DOMParser处理HTML
    const parser = new DOMParser();
    const doc = parser.parseFromString(`<div>${html}</div>`, 'text/html');
    const container = doc.body.firstChild;
    
    // 允许的标签和属性
    const allowedTags = {
        'p': { allowedAttrs: ['style'] },
        'br': { allowedAttrs: [] },
        'b': { allowedAttrs: [] },
        'i': { allowedAttrs: [] },
        'strong': { allowedAttrs: [] },
        'em': { allowedAttrs: [] },
        'u': { allowedAttrs: [] },
        'ul': { allowedAttrs: [] },
        'ol': { allowedAttrs: [] },
        'li': { allowedAttrs: [] },
        'span': { allowedAttrs: ['class', 'style'] },
        'div': { allowedAttrs: ['class', 'style'] },
        'a': { allowedAttrs: ['href', 'target'] },
        'img': { allowedAttrs: ['src', 'alt', 'width', 'height'] }
    };
    
    // 递归清理节点
    function cleanNode(node) {
        // 如果是文本节点，直接返回
        if (node.nodeType === Node.TEXT_NODE) {
            return;
        }
        
        // 处理元素节点
        if (node.nodeType === Node.ELEMENT_NODE) {
            const tagName = node.tagName.toLowerCase();
            
            // 如果标签不在允许列表中，替换为其内容
            if (!allowedTags[tagName]) {
                // 创建文档片段
                const fragment = document.createDocumentFragment();
                
                // 将所有子节点移动到片段中
                while (node.firstChild) {
                    const child = node.firstChild;
                    node.removeChild(child);
                    fragment.appendChild(child);
                }
                
                // 替换节点
                node.parentNode.replaceChild(fragment, node);
                return;
            }
            
            // 检查并清理属性
            const attrs = Array.from(node.attributes);
            attrs.forEach(attr => {
                const attrName = attr.name.toLowerCase();
                const attrValue = attr.value;
                
                // 检查属性是否允许
                const tagConfig = allowedTags[tagName];
                if (!tagConfig.allowedAttrs.includes(attrName)) {
                    node.removeAttribute(attrName);
                    return;
                }
                
                // 检查属性值是否安全
                if (attrValue.toLowerCase().includes('javascript:') || 
                    (attrName === 'src' && attrValue.toLowerCase().includes('data:')) ||
                    attrName.startsWith('on')) {
                    node.removeAttribute(attrName);
                }
            });
            
            // 处理子节点
            Array.from(node.childNodes).forEach(cleanNode);
        }
    }
    
    // 清理所有子节点
    Array.from(container.childNodes).forEach(cleanNode);
    
    return container.innerHTML;
}

// 加载与特定用户的消息
function loadMessages(userId, username) {
    console.log('加载与用户ID为 ' + userId + ' 的消息');
    
    // 获取登录token
    var token = localStorage.getItem('token');
    if (!token) {
        console.error('未找到token，用户可能未登录');
        showAlert('请先登录以查看消息', 'warning');
        return;
    }
    
    // 保存当前聊天对象的ID
    localStorage.setItem('currentChatPartnerId', userId);
    
    // 更新消息区域标题
    $('#messagesModalLabel').text('与 ' + sanitizeHTML(username) + ' 的对话');
    
    // 显示加载状态
    $('#messageList').html('<div class="text-center mt-3"><div class="spinner-border text-primary" role="status"></div><div class="mt-2">加载消息中...</div></div>');
    
    // 确保消息容器可见
    $('#messageContainer').removeClass('d-none').addClass('d-flex');
    $('#noConversationSelected').addClass('d-none');
    
    // 发送AJAX请求获取消息
    $.ajax({
        url: 'getmsg.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ token: token }),
        success: function(response) {
            console.log('获取消息响应:', response);
            
            if (response.success) {
                // 获取当前用户ID
                const currentUserId = response.debug?.user_id || "";
                console.log('当前用户ID:', currentUserId);
                
                // 过滤获取与特定用户的消息
                const filteredMessages = response.messages.filter(function(message) {
                    const isRelevant = (message.sender_id == userId && message.receiver_id == currentUserId) || 
                                      (message.sender_id == currentUserId && message.receiver_id == userId);
                    if (isRelevant) {
                        console.log('相关消息:', message);
                    }
                    return isRelevant;
                });
                
                console.log('过滤后的消息数量:', filteredMessages.length);
                
                // 显示过滤后的消息
                displayMessages(filteredMessages, userId);
                
                // 注意：getmsg.php已经将消息标记为已读
            } else {
                // 显示错误信息
                showAlert(response.message || '加载消息失败', 'error');
                $('#messageList').html('<div class="text-center text-danger mt-3">加载失败</div>');
            }
        },
        error: function(xhr, status, error) {
            console.error('加载消息失败:', error);
            // 显示错误信息
            showAlert('网络错误，请稍后再试', 'error');
            $('#messageList').html('<div class="text-center text-danger mt-3">加载失败</div>');
        }
    });
}

