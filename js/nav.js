// Global variable to store the fetched site key
var turnstileSiteKey = null;

// Function to fetch the Turnstile site key from the backend
async function fetchTurnstileSiteKey() {
    try {
        const response = await fetch('get_config.php');
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const config = await response.json();
        if (config && config.turnstile_sitekey) {
            turnstileSiteKey = config.turnstile_sitekey;
            console.log('Turnstile Site Key fetched successfully.');
        } else {
            console.error('Failed to retrieve Turnstile site key from config.');
        }
    } catch (error) {
        console.error('Error fetching Turnstile site key:', error);
    }
}

$(document).ready(function() {
    // Load navbar
    loadNavbar();
    // turnstileManager.fetchSiteKey(); // Pre-fetch site key if needed, manager handles singleton
    
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
    // 检查未读消息
    checkUnreadMessagesAndUpdate();
    // 启动定时检查
    startUnreadChecksAndUpdates();
    
        // 模态框显示时加载消息
$('#messagesModal').on('show.bs.modal', function(e) {
        console.log('[show.bs.modal] Message modal opening...'); // Log start
        
        // 添加加载动画 (for conversation list)
        var modalBody = $(this).find('.modal-body #conversationList'); // Target conversation list specifically
        // Clear previous content and add spinner
        modalBody.html('<div id="convListLoading" class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="sr-only">Loading conversations...</span></div></div>');
        
        // Ensure initial state of right panel is correct immediately
        $('#messageContainer').hide(); 
        $('#noConversationSelected').show();
        console.log('[show.bs.modal] Initial state set: messageContainer hidden, noConversationSelected shown.');
        
        // 清除新消息标志
        localStorage.removeItem('hasNewMessages');
        
        // 获取消息 (conversations)
    fetchMessages().then(function(data) {
            console.log('[show.bs.modal] fetchMessages resolved.');
            $('#convListLoading').remove(); // 移除对话列表加载动画
            
        if (data.success) {
                console.log('[show.bs.modal] fetchMessages success. Building list and setting panel state.');
                // 构建会话列表
            buildConversationsList(data);
                // Explicitly set right panel state AGAIN after build (belt and suspenders)
                $('#messageContainer').hide(); 
                $('#noConversationSelected').show();
                console.log('[show.bs.modal] Post-build state set: messageContainer hidden, noConversationSelected shown.');
        } else {
                console.error('[show.bs.modal] fetchMessages failed:', data.message);
                showAlert('Failed to load conversations: ' + (data.message || 'Unknown error'), 'error');
                $('#conversationList').html('<p class="text-danger">Failed to load conversations.</p>');
                // Ensure correct state even on failure
                $('#messageContainer').hide(); 
                $('#noConversationSelected').show().text('Error loading conversations.');
            }
            
            // 这部分可能不需要在模态框显示时重复启动，因为它已经在登录后启动了
            // startUnreadChecksAndUpdates(); 
    }).catch(function(error) {
             console.error('[show.bs.modal] fetchMessages AJAX error:', error);
             $('#convListLoading').remove(); // Ensure spinner removed on catch
             showAlert('Error loading conversations: ' + error.message, 'error');
             $('#conversationList').html('<p class="text-danger">Error connecting to server.</p>');
             // Ensure correct state even on AJAX error
             $('#messageContainer').hide(); 
             $('#noConversationSelected').show().text('Error connecting to server.');
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
                    console.log('登录成功，身份：', response.real_username, '用户ID:', response.id);
                    $('#loginModal').modal('hide');
                    localStorage.setItem('loggedIn', true);
                    localStorage.setItem('username', response.real_username);
                    localStorage.setItem('token', response.token);
                    
                    // 保存用户ID到localStorage
                    if (response.id) {
                        localStorage.setItem('userId', response.id);
                        console.log('用户ID已保存到localStorage:', response.id);
                    } else {
                        console.warn('登录响应中未找到id字段');
                        console.log('完整响应:', JSON.stringify(response));
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
                    
                    // 登录成功后立即检查未读消息
                    checkUnreadMessages();
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
  
    // 加载footer
    $("#footer-placeholder").load("footer.html", function() {
      console.log("Footer loaded with auto theme detection");
      // 不再需要手动处理主题，新的footer.html已经有自动主题检测
    });
    
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

// 检查未读消息
function checkUnreadMessages() {
    console.log('旧的checkUnreadMessages函数已弃用，调用新的checkUnreadMessagesAndUpdate函数');
    // 调用新函数
    checkUnreadMessagesAndUpdate();
}

// 定时检查未读消息
function startUnreadMessageCheck() {
    console.log('旧的startUnreadMessageCheck函数已弃用，调用新的startUnreadChecksAndUpdates函数');
    // 调用新函数
    startUnreadChecksAndUpdates();
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
        $('#userStatus').text('Please sign in or register:');
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
    console.log('Attempting user registration');
    var username = $('#registerUsername').val();
    var password = $('#registerPassword').val();
    var email = $('#registerEmail').val();
    var verificationCode = $('#verificationCode').val();

    // Get Turnstile token using the manager
    var turnstileResponse = null;
    // ** Ensure registerModalWidgetId is declared/accessible in this scope ** 
    // (We'll declare it globally later if needed, or pass it in)
    if (typeof registerModalWidgetId !== 'undefined' && registerModalWidgetId) { 
        turnstileResponse = turnstileManager.getResponse(registerModalWidgetId);
        console.log('[Register] Got Turnstile response via manager:', !!turnstileResponse);
    } else {
        console.warn('[Register] Cannot get Turnstile response: registerModalWidgetId is not defined or null.');
    }
    
    // Basic validation
    if (!username || !password || !email || !verificationCode) {
        showAlert('Please fill in all fields.', 'warning');
        return;
    }
    
    if (!turnstileResponse) {
        showAlert('Please complete the verification.', 'warning');
        return;
    }

    console.log('Proceeding with registration AJAX call');
    $.ajax({
        type: 'POST',
        url: 'register.php',
        data: {
            username: username,
            password: password,
            email: email,
            code: verificationCode,
            'cf-turnstile-response': turnstileResponse // Send the token
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showAlert('Registration successful! Please log in.', 'success');
                $('#registerModal').modal('hide');
                $('#loginModal').modal('show'); // Show login modal after successful registration
            } else {
                showAlert(response.message || 'Registration failed.', 'error');
                // Optionally reset the Turnstile widget on failure?
                if (typeof registerModalWidgetId !== 'undefined' && registerModalWidgetId) {
                   turnstileManager.resetWidget(registerModalWidgetId);
                }
            }
        },
        error: function() {
            showAlert('An error occurred during registration. Please try again.', 'error');
             // Optionally reset the Turnstile widget on error?
            if (typeof registerModalWidgetId !== 'undefined' && registerModalWidgetId) {
               turnstileManager.resetWidget(registerModalWidgetId);
            }
        }
    });
}

// 发送验证码
function sendVerificationCode() {
    console.log('点击发送验证码按钮');
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
    
    // 获取用户输入的邮箱
    var email = $('#email').val();
    console.log('准备发送验证码到邮箱:', email);
    
    // 检查邮箱是否有效
    if (!email || !email.includes('@')) {
        showAlert('请输入有效的邮箱地址。', 'warning');
        return;
    }
    
    // 获取cf_token (如果存在)
    var cftoken = '';
    try {
        cftoken = localStorage.getItem('cf_token') || '';
        console.log('cf_token:', cftoken ? '已获取' : '未找到');
    } catch (e) {
        console.error('获取cf_token时出错:', e);
    }
    
    // 准备发送请求
    console.log('准备发送XHR请求到 sendVerificationCode.php');
    
    // 显示加载状态
    $('#sendVerificationCode').prop('disabled', true);
    $('#sendVerificationCode').html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> 发送中...');
    
    // 发送AJAX请求
    $.ajax({
        type: 'POST',
        url: 'sendVerificationCode.php',
        data: {
            cf_token: cftoken, 
            email: email 
        },
        dataType: 'json',
        success: function(response) {
            console.log('验证码请求响应:', response);
            if (response.success) {
                $('#emailHelp').show();
                showAlert('验证码已发送到您的邮箱，请查收。', 'success');
                
                // 记录发送时间到localStorage
                const now = Date.now();
                localStorage.setItem('lastSentTime', now.toString());
                
                // 更新按钮状态
                updateButtonForRemainingTime();
            } else {
                $('#emailHelp').hide();
                $('#sendVerificationCode').prop('disabled', false);
                $('#sendVerificationCode').text('发送验证码 Send verification code');
                showAlert('验证码发送失败: ' + (response.error || response.message || '未知错误'), 'error');
            }
        },
        error: function(xhr, status, error) {
            console.error('发送验证码请求失败:', error);
            console.error('状态码:', xhr.status);
            console.error('响应文本:', xhr.responseText);
            
            $('#emailHelp').hide();
            $('#sendVerificationCode').prop('disabled', false);
            $('#sendVerificationCode').text('发送验证码 Send verification code');
            showAlert('请求发送验证码失败，请稍后再试。', 'error');
        }
    });
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
        $('#sendVerificationCode').text('发送验证码 Send verification code');
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
        
        // 获取当前用户ID
        const currentUserId = localStorage.getItem('userId') || localStorage.getItem('id') || sessionStorage.getItem('id');
        console.log('当前用户ID (buildConversationsList):', currentUserId);
        
        if (!currentUserId) {
            console.warn('未找到当前用户ID，对话列表可能无法正确构建');
        }
        
        // 按对话伙伴ID分组消息
    var conversations = {};
        data.messages.forEach(function(message, index) {
            console.log(`处理第 ${index+1} 条消息:`, message);
            
            // 确定对话伙伴ID - 如果当前用户是发送者，则对话伙伴是接收者；反之亦然
            var partnerId;
            if (message.sender_id == currentUserId) {
                partnerId = message.receiver_id;
            } else {
                partnerId = message.sender_id;
            }
            
            // 如果partnerId为undefined或null，设为'unknown'
            partnerId = partnerId || 'unknown';
            
            if (!conversations[partnerId]) {
                conversations[partnerId] = {
                    user_id: partnerId,
                messages: []
            };
        }
            
            conversations[partnerId].messages.push(message);
        });
        
        console.log('分组后的对话:', conversations);
        
        // 如果没有对话，显示提示
        if (Object.keys(conversations).length === 0) {
            conversationList.append('<div class="alert alert-info">暂无对话</div>');
            return;
        }
        
        // 处理每个对话，添加更多信息
        Object.keys(conversations).forEach(function(partnerId) {
            const conversation = conversations[partnerId];
            
            // 按时间排序消息
            conversation.messages.sort(function(a, b) {
                return new Date(a.send_time || a.created_at || 0) - new Date(b.send_time || b.created_at || 0);
            });
            
            // 获取最新消息
            const latestMessage = conversation.messages[conversation.messages.length - 1];
            
            // 设置对话伙伴名称 - 根据实际消息中的信息
            // 如果没有提供名称，则使用"用户+ID"作为默认名称
            conversation.username = '用户 ' + partnerId;
            
            // 计算未读消息数
            conversation.unread_count = conversation.messages.filter(function(msg) {
                return msg.is_read == 0 && msg.sender_id == partnerId && msg.receiver_id == currentUserId;
            }).length;
            
            // 设置最新消息内容和时间
            conversation.last_message = latestMessage.content || '';
            conversation.last_time = latestMessage.send_time || latestMessage.created_at || '';
        });
        
        // 将对话转换为数组并按最新消息时间排序
        const sortedConversations = Object.values(conversations).sort(function(a, b) {
            const timeA = a.last_time ? new Date(a.last_time) : new Date(0);
            const timeB = b.last_time ? new Date(b.last_time) : new Date(0);
            return timeB - timeA;
        });
        
        // 显示排序后的对话
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
            
            conversationList.append(conversationItem);
        });
        
        // 绑定点击事件
        $('.conversation-item').click(function() {
            var userId = $(this).data('user-id');
            var username = $(this).find('h6').text();
            
            console.log('点击会话项:', userId, username);
            
            // 显示消息区域，隐藏"无会话"提示
            $('#messageContainer').css('display', 'flex').show();
            $('#noConversationSelected').hide();
            
            // 加载与该用户的消息
            loadMessages(userId, username);
            
            // 高亮选中的会话
            $('.conversation-item').removeClass('active');
            $(this).addClass('active');
            
            // 清除未读标记
            $(this).find('.badge').remove();
            
            // 移除可能导致ARIA错误的属性
            $('.modal').removeAttr('aria-hidden');
        });
    } catch (error) {
        console.error('构建对话列表时出错:', error);
        conversationList.html('<div class="alert alert-danger">加载对话列表失败</div>');
    }
}


function displayMessages(messages, sender) {
    console.log('开始显示消息，消息数量:', messages ? messages.length : 0, '发送者ID:', sender);
    
    localStorage.setItem('currentChatPartnerId', sender);
    
    $('#messageContainer').css('display', 'flex').show();
    $('#noConversationSelected').hide();
    
    let messageList = document.getElementById('messageList');
    if (!messageList) {
        console.error('无法创建messageList元素，可能存在DOM结构问题');
        showAlert('无法加载消息界面，请刷新页面重试', 'error');
        return;
    }
    
    messageList.innerHTML = '';
    //ensureMessageStyles();
    
    if (!messages || messages.length === 0) {
        messageList.innerHTML = '<div class="text-center p-4"><i class="fas fa-comment-slash fa-3x text-muted mb-3"></i><p>暂无消息记录</p></div>';
        return;
    }
    
    const currentUserId = localStorage.getItem('userId') || localStorage.getItem('id') || sessionStorage.getItem('id');
    console.log('当前用户ID (displayMessages):', currentUserId);
    
    if (!currentUserId) {
        console.warn('未找到当前用户ID，消息可能无法正确显示');
        // Consider handling this case more robustly, maybe show an error or force logout
    }
    
    messages.sort(function(a, b) {
        return new Date(a.send_time || a.created_at || 0) - new Date(b.send_time || b.created_at || 0);
    });
    
    let lastDate = '';
    
    messages.forEach(function(message, index) {
        console.log(`处理第 ${index+1} 条消息:`, message);
        
        const timestamp = message.send_time || message.created_at || new Date().toISOString();
        const messageDate = new Date(timestamp);
        const formattedDate = messageDate.toLocaleDateString();
        const formattedTime = messageDate.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        
        if (formattedDate !== lastDate) {
            const dateDiv = document.createElement('div');
            dateDiv.className = 'text-center my-3 date-separator'; // Added class for easier styling
            dateDiv.innerHTML = `<span class="badge bg-secondary px-3 py-2">${formattedDate}</span>`;
            messageList.appendChild(dateDiv);
            lastDate = formattedDate;
        }
        
        const messageContainer = document.createElement('div');
        const isSent = message.sender_id == currentUserId;
        console.log(`消息${index+1} 是否由当前用户发送:`, isSent, 'sender_id:', message.sender_id, 'currentUserId:', currentUserId);
        
        messageContainer.className = isSent ? 'message-container sent-container' : 'message-container received-container';
        
        let content = message.content || message.message || '';
        if (typeof content !== 'string') {
            content = JSON.stringify(content); // Basic handling for non-string content
        }
        const safeContent = sanitizeHTML(content); // Use simplified sanitizer
        console.log(`消息${index+1} 内容:`, content, '安全内容:', safeContent);
        
        const bubbleClass = isSent ? 'sent' : 'received';
        
        // Read Status (Only for sent messages)
        let readStatusHTML = '';
        if (isSent) {
            const isRead = message.is_read == 1 || message.is_read === true || message.is_read === '1';
            console.log(`消息${index+1} 已读状态:`, message.is_read, isRead ? '已读' : '未读');
            
            // Simpler read status: checkmark icon + text
            const readIcon = isRead ? '<i class="fas fa-check-double text-primary"></i>' : '<i class="fas fa-check text-muted"></i>';
            const readText = isRead ? 'Read' : 'Sent'; // Changed '未读' to 'Sent' for clarity
            
            readStatusHTML = `
                <div class="message-read-status">
                    ${readIcon} ${readText}
                </div>`;
        }
        
        messageContainer.innerHTML = `
            <div class="message-bubble ${bubbleClass}" data-message-id="${message.message_id || message.id || ''}"> 
                ${safeContent || '<span class="text-muted">(空消息)</span>'}
                <div class="message-meta">
                    <span class="message-time">${formattedTime}</span>
                    ${readStatusHTML} 
                </div>
            </div>
        `;
        
        messageList.appendChild(messageContainer);
    });
    
    $('.modal').removeAttr('aria-hidden');
    scrollToBottom(messageList);
}

// Styles need adjustment for message-meta
// function ensureMessageStyles() {
//     if (!$('head style:contains(".message-bubble")').length) {
//         const messageStyles = `
//         <style>
//             /* ... (other styles remain the same) ... */
//             .message-bubble {
//                 max-width: 80%;
//                 padding: 10px 15px;
//                 border-radius: 18px;
//                 position: relative;
//                 box-shadow: 0 1px 2px rgba(0,0,0,0.1);
//                 word-break: break-word;
//                 color: #000; 
//                 margin-bottom: 5px; /* Add margin below bubble */
//             }
//             .message-bubble.sent {
//                 background-color: #dcf8c6;
//                 border-bottom-right-radius: 5px;
//             }
//             .message-bubble.received {
//                 background-color: #f1f0f0;
//                 border-bottom-left-radius: 5px;
//             }
//             .message-meta {
//                 font-size: 0.75rem;
//                 color: #888;
//                 margin-top: 4px;
//                 text-align: right;
//                 display: flex; /* Use flex to align items */
//                 justify-content: flex-end; /* Align items to the right */
//                 align-items: center; /* Center items vertically */
//             }
//             .message-time {
//                  margin-right: 5px; /* Space between time and status */
//             }
//             .message-read-status {
//                 /* Status aligned by flexbox */
//             }
//             .message-read-status .fa-check-double { color: #007bff; } /* Blue check for read */
//             .message-read-status .fa-check { color: #6c757d; } /* Gray check for sent */
            
//              /* Date separator style */
//             .date-separator span {
//                 font-size: 0.8rem;
//                 font-weight: 500;
//             }
//             /* ... (rest of the styles) ... */
//         </style>`;
//         $('head').append(messageStyles);
//         console.log('添加了消息样式到页面 (with meta updates)');
//     }
// }

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
    
    // 获取必要参数 - 统一使用的存储方式，优先使用localStorage，然后尝试sessionStorage
    var token = localStorage.getItem('token') || sessionStorage.getItem('token');
    var receiverId = localStorage.getItem('currentChatPartnerId');
    
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
    
    // 创建临时消息元素 - 修改类名使其显示在右侧
    const tempMessage = document.createElement('div');
    tempMessage.className = 'message-container sent-container';
    tempMessage.innerHTML = `
        <div class="message-bubble sent" data-temp-id="${tempMessageId}">
            ${safeMessageDisplay}
            <div class="message-time">发送中...</div>
            <div class="message-read-status" style="font-size: 0.75rem; text-align: right; margin-top: 2px;">
                <span style="background-color: #f0f0f0; color: #888; padding: 1px 5px; border-radius: 10px; display: inline-block;">
                    <i class="fas fa-clock"></i> 发送中
                </span>
            </div>
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
                    
                    // 更新已读状态为"未读"，使用更明显的样式
                    const readStatusElement = messageBubble.querySelector('.message-read-status');
                    if (readStatusElement) {
                        readStatusElement.innerHTML = `
                            <span style="background-color: #f0f0f0; color: #888; padding: 1px 5px; border-radius: 10px; display: inline-block;">
                                <i class="fas fa-check"></i> 未读
                            </span>
                        `;
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
                    
                    // 更新已读状态
                    const readStatusElement = messageBubble.querySelector('.message-read-status');
                    if (readStatusElement) {
                        readStatusElement.innerHTML = `
                            <span style="background-color: #ffebee; color: #d32f2f; padding: 1px 5px; border-radius: 10px; display: inline-block;">
                                <i class="fas fa-times"></i> 发送失败
                            </span>
                        `;
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
                
                // 更新已读状态
                const readStatusElement = messageBubble.querySelector('.message-read-status');
                if (readStatusElement) {
                    readStatusElement.innerHTML = `
                        <span style="background-color: #ffebee; color: #d32f2f; padding: 1px 5px; border-radius: 10px; display: inline-block;">
                            <i class="fas fa-times"></i> 发送失败
                        </span>
                    `;
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
    <nav class="navbar navbar-expand-lg navbar-dark original-navbar">
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
                        <button class="btn btn-ios-primary btn-sm mx-1" data-toggle="modal" data-target="#loginModal">Sign in</button>
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
                    console.log('登录成功，身份：', response.real_username, '用户ID:', response.id);
                    $('#loginModal').modal('hide');
                    localStorage.setItem('loggedIn', true);
                    localStorage.setItem('username', response.real_username);
                    localStorage.setItem('token', response.token);
                    
                    // 保存用户ID到localStorage
                    if (response.id) {
                        localStorage.setItem('userId', response.id);
                        console.log('用户ID已保存到localStorage:', response.id);
        } else {
                        console.warn('登录响应中未找到id字段');
                        console.log('完整响应:', JSON.stringify(response));
                    }
                    
                    // 设置7天的到期时间
                    localStorage.setItem('expiration', new Date().getTime() + 7 * 24 * 60 * 60 * 1000);
                    updateLoginStatus();
                    $submitButton.prop('disabled', false).text('Login');
                    
                    // 登录成功后立即检查未读消息
                    checkUnreadMessages();
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
            display: flex !important;
            flex-direction: column !important;
            width: 100% !important;
        }
        .message-bubble {
            max-width: 80%;
            padding: 10px 15px;
            border-radius: 18px;
            position: relative;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
            word-break: break-word;
            color: #000 !important; /* 确保文字显示为黑色 */
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            z-index: 1 !important;
        }
        .message-bubble * {
            color: #000 !important;
            visibility: visible !important;
            display: inline !important;
        }
        .message-bubble.sent {
            background-color: #dcf8c6 !important;
            align-self: flex-end !important;
            margin-left: auto !important;
            border-bottom-right-radius: 5px !important;
        }
        .message-bubble.received {
            background-color: #f1f0f0 !important;
            align-self: flex-start !important;
            margin-right: auto !important;
            border-bottom-left-radius: 5px !important;
        }
        .message-time {
            font-size: 0.7rem !important;
            color: #999 !important;
            margin-top: 5px !important;
            text-align: right !important;
            display: block !important;
            visibility: visible !important;
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
            height: 350px !important;
            overflow-y: auto !important;
        }
        .no-conversation {
            height: 400px;
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
        /* 确保消息区域正确显示 */
        #messageContainer {
            height: 100%;
            display: flex !important;
            flex-direction: column !important;
        }
        /* 修复z-index问题 */
        .modal {
            z-index: 1050 !important;
        }
        .modal-backdrop {
            z-index: 1040 !important;
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
                                <div id="messageContainer" class="d-flex flex-column" style="display: none;">
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
                                <div id="noConversationSelected" class="no-conversation">
                                    <div class="welcome-message">
                                        <img src="img/message_welcome.svg" alt="欢迎使用站内信" class="welcome-img" onerror="this.onerror=null; this.src=''; this.style.display='none'; document.querySelector('.welcome-message').style.paddingTop='10px';">
                                        <h3>欢迎使用站内信</h3>
                                        <p>从左侧选择一个联系人开始对话，或者开始一个新的对话</p>
                                        <div class="welcome-features">
                                            <div class="feature-item">
                                                <i class="fas fa-shield-alt"></i>
                                                <span>安全加密的消息</span>
                                            </div>
                                            <div class="feature-item">
                                                <i class="fas fa-bell"></i>
                                                <span>实时消息通知</span>
                                            </div>
                                            <div class="feature-item">
                                                <i class="fas fa-history"></i>
                                                <span>完整的消息历史</span>
                                            </div>
                                        </div>
                                    </div>
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
        $('#messageContainer').hide();
        $('#noConversationSelected').show();
        
        // 加载会话列表
        loadConversations();
        
        // 启动已读状态检查
        startMessageReadStatusCheck();
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
                // 首先尝试从localStorage获取userId，然后从response.debug中尝试获取
                const currentUserId = localStorage.getItem('userId') || response.debug?.user_id || "";
                console.log('当前用户ID (loadConversations):', currentUserId);
                
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
                            
                            console.log('点击会话项:', userId, username);
                            
                            // 显示消息区域，隐藏"无会话"提示
                            $('#messageContainer').css('display', 'flex').show();
                            $('#noConversationSelected').hide();
                            
                            // 加载与该用户的消息
                            loadMessages(userId, username);
                            
                            // 高亮选中的会话
                            $('.conversation-item').removeClass('active');
                            $(this).addClass('active');
                            
                            // 清除未读标记
                            $(this).find('.badge').remove();
                            
                            // 移除可能导致ARIA错误的属性
                            $('.modal').removeAttr('aria-hidden');
                        });
                    } else {
                        // 没有会话时显示提示
                        $('#conversationList').html('<div class="text-center text-muted py-3">暂无会话</div>');
                        
                        // 显示"无会话"提示
                        $('#messageContainer').hide();
                        $('#noConversationSelected').show();
                    }
                } else {
                    // 没有消息时显示提示
                    $('#conversationList').html('<div class="text-center text-muted py-3">暂无会话</div>');
                    
                    // 显示"无会话"提示
                    $('#messageContainer').hide();
                    $('#noConversationSelected').show();
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
                    <h5 class="modal-title" id="loginModalLabel">Sign in</h5>
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
                        <button type="submit" class="btn btn-success btn-block">Sign in</button>
                    </form>
                </div>
                <div class="modal-footer" style="flex-direction: column;">
                    <div class="alert alert-warning" role="alert" id="refreshAlert" style="display: none; width: 100%; border-radius: 12px;">
                        If you're having trouble, please reload the page or press <a href="#" onclick="location.reload();">here</a>.
                    </div>
                    <div style="display: flex; justify-content: space-between; width: 100%;">
                        <a href="iforgot.html">Forgot password?</a>
                        <a href="#" data-toggle="modal" data-target="#registerModal">Create new account</a>
                    </div>
                </div>
            </div>
        </div>
    </div>`;
    
    // 将模态框添加到body
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    
    console.log('登录模态框已创建并初始化完成');
}

// Make sure this is declared at a scope accessible by initRegisterModal and registerUser
var registerModalWidgetId = null; 

// 初始化注册模态框
function initRegisterModal() {
    console.log("Initializing Register Modal and its event listeners.");
    // Check if modal already exists
    if (!document.getElementById('registerModal')) {
    const modalHTML = `
    <div class="modal fade" id="registerModal" tabindex="-1" role="dialog" aria-labelledby="registerModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content ios-modal-content">
                    <div class="modal-header ios-modal-header">
                        <h5 class="modal-title" id="registerModalLabel">Register</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                    <div class="modal-body ios-modal-body">
                        <form id="registerForm" onsubmit="return false;"> <!-- Prevent default form submission -->
                        <div class="form-group">
                                <label for="registerUsername">Username</label>
                                <input type="text" class="form-control ios-input" id="registerUsername" required>
                        </div>
                        <div class="form-group">
                                <label for="registerPassword">Password</label>
                                <input type="password" class="form-control ios-input" id="registerPassword" required>
                        </div>
                        <div class="form-group">
                                <label for="registerEmail">Email</label>
                                <input type="email" class="form-control ios-input" id="registerEmail" required>
                            </div>
                            <div class="form-group">
                                <label for="verificationCode">Verification Code</label>
                                <div class="input-group">
                                    <input type="text" class="form-control ios-input" id="verificationCode" required>
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary ios-button" type="button" id="sendCodeBtn">Send Code</button>
                            </div>
                        </div>
                                <small id="codeHelp" class="form-text text-muted">Enter the code sent to your email.</small>
                </div>
                            <!-- *** Turnstile Widget Placeholder *** -->
                            <div id="turnstile-register-widget" style="margin-top: 15px; margin-bottom: 15px;">
                                <!-- Turnstile will render here -->
                    </div>
                            
                            <div id="registerError" class="alert alert-danger" style="display: none;"></div>
                            
                            <button type="button" class="btn btn-primary btn-block ios-button" id="registerButton" disabled>Register</button> 
                        </form>
                </div>
            </div>
        </div>
    </div>`;
        $('body').append(modalHTML);
        console.log("Register Modal HTML appended.");

        // Attach event listeners for buttons *inside* the modal
        // Use .off().on() to prevent duplicate bindings if init is called multiple times
        $('body').off('click', '#sendCodeBtn').on('click', '#sendCodeBtn', sendVerificationCode);
        $('body').off('click', '#registerButton').on('click', '#registerButton', registerUser);

        // Add Turnstile render/remove logic to modal events
        $('#registerModal').off('shown.bs.modal').on('shown.bs.modal', async function () {
             console.log('[Register Modal] Shown event triggered.');
             $('#registerButton').prop('disabled', true); // Disable button initially
             // Define callbacks for the registration widget
             const registerWidgetOptions = {
                callback: function(token) {
                    console.log('[Turnstile] Register Success callback. Token:', token);
                    $('#registerButton').prop('disabled', false); // Enable register button
                },
                'expired-callback': function() {
                    console.log('[Turnstile] Register Expired callback.');
                    $('#registerButton').prop('disabled', true);
                    // Maybe show a message?
                },
                'error-callback': function(errorCode) {
                    console.error('[Turnstile] Register Error callback. Code:', errorCode);
                    $('#registerButton').prop('disabled', true);
                    showAlert(`Verification error (${errorCode}). Please refresh and try again.`, 'error');
                }
             };
             // Render using the manager
             console.log('[Register Modal] Requesting Turnstile render...');
             registerModalWidgetId = await turnstileManager.renderWidget('#turnstile-register-widget', registerWidgetOptions, 200); 
             if (registerModalWidgetId) {
                console.log('[Register Modal] Turnstile widget render requested. ID:', registerModalWidgetId);
             } else {
                console.error('[Register Modal] Failed to render Turnstile widget via manager.');
                 showAlert('Failed to load verification component. Please close and retry.', 'error');
             }
        });

        $('#registerModal').off('hidden.bs.modal').on('hidden.bs.modal', function () {
            console.log('[Register Modal] Hidden event triggered. Cleaning up widget ID:', registerModalWidgetId);
            if (registerModalWidgetId) {
                turnstileManager.removeWidget(registerModalWidgetId);
                registerModalWidgetId = null; // Reset ID
            }
             // Also reset the register button state and clear errors/fields
             $('#registerButton').prop('disabled', true); 
             $('#registerError').hide().text('');
             // Optional: Clear form fields? 
             // $('#registerForm')[0].reset(); 
        });

    } else {
         console.log("Register Modal already exists.");
         // Ensure listeners are attached even if modal exists (might be needed if listeners were somehow detached)
         // Consider a more robust check or different initialization pattern if this becomes an issue.
         // For now, assume listeners persist if the modal exists.
    }
}

// 安全净化HTML内容，防止XSS攻击 (Simplified version)
function sanitizeHTML(html) {
    if (typeof html !== 'string') return '';
    const temp = document.createElement('div');
    temp.textContent = html; // Treat the input purely as text
    return temp.innerHTML; // Return the HTML-encoded string
}

// 加载与特定用户的消息
function loadMessages(userId, username) {
    console.log('加载与用户ID为 ' + userId + ' 的消息');
    
    // 获取登录token
    var token = localStorage.getItem('token') || sessionStorage.getItem('token');
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
    
    // 发送AJAX请求获取所有消息
    $.ajax({
        url: 'getmsg.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ token: token }),
        success: function(response) {
            if (response.success) {
                // 获取当前用户ID
                const currentUserId = localStorage.getItem('userId') || localStorage.getItem('id') || sessionStorage.getItem('id');
                console.log('当前用户ID (loadMessages):', currentUserId);
                
                // 过滤出与当前对话伙伴相关的消息
                const filteredMessages = response.messages.filter(function(message) {
                    return (message.sender_id == userId && message.receiver_id == currentUserId) || 
                           (message.sender_id == currentUserId && message.receiver_id == userId);
                });
                
                console.log('过滤后的消息数量:', filteredMessages.length);
                
                // 显示过滤后的消息
                displayMessages(filteredMessages, userId);
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

// 定期更新消息已读状态
function startMessageReadStatusCheck() {
    console.log('启动定期更新消息已读状态');
    
    // 清除可能存在的旧定时器
    if (window.readStatusTimer) {
        clearInterval(window.readStatusTimer);
    }
    
    // 设置定时器，每5秒检查一次已读状态
    window.readStatusTimer = setInterval(function() {
        // 获取当前打开的会话ID
        const currentChatPartnerId = localStorage.getItem('currentChatPartnerId');
        
        // 如果没有打开的会话，或者消息模态框没有显示，则不更新
        if (!currentChatPartnerId || !$('#messagesModal').hasClass('show')) {
            return;
        }
        
        console.log('只检查消息已读状态，不获取所有消息，会话ID:', currentChatPartnerId);
        
        // 获取当前显示的消息元素，而不是重新获取所有消息
        const messageElements = $('#messageList .message-bubble.sent');
        
        // 如果没有发送的消息，则不需要检查
        if (messageElements.length === 0) {
            return;
        }
        
        // 获取token
        var token = localStorage.getItem('token') || sessionStorage.getItem('token');
        if (!token) {
            console.error('未找到token，无法检查消息状态');
            return;
        }
        
        // 只检查消息已读状态，不获取所有消息
        $.ajax({
            url: 'chkreadstatus.php', // 这个API需要单独实现，只返回消息的已读状态
            type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
                token: token,
                partner_id: currentChatPartnerId 
        }),
        success: function(response) {
                if (response.success && response.read_messages) {
                    // 更新消息已读状态
                    updateMessageReadStatus(response.read_messages);
                }
            },
            error: function(xhr, status, error) {
                console.error('检查消息已读状态失败:', error);
            }
        });
    }, 5000);
    
    // 当模态框关闭时，清除定时器
    $('#messagesModal').on('hidden.bs.modal', function() {
        console.log('消息模态框已关闭，清除已读状态检查定时器');
        if (window.readStatusTimer) {
            clearInterval(window.readStatusTimer);
        }
    });
}

// 辅助函数：更新消息已读状态
function updateMessageReadStatus(readMessageIds) {
    // 如果没有已读消息ID列表，则不处理
    if (!readMessageIds || !readMessageIds.length) {
        return;
    }
    
    console.log('更新消息已读状态:', readMessageIds);
    
    // 遍历所有发送的消息，更新已读状态
    $('.message-bubble.sent').each(function() {
        const messageId = $(this).data('message-id');
        
        // 如果消息ID在已读列表中，则更新状态为已读
        if (messageId && readMessageIds.includes(messageId)) {
            const readStatusElement = $(this).find('.message-read-status');
            if (readStatusElement.length) {
                readStatusElement.html(`
                    <span style="background-color: #9FE2BF; color: #006400; padding: 1px 5px; border-radius: 10px; display: inline-block;">
                        <i class="fas fa-check-double"></i> 已读
                    </span>
                `);
            }
        }
    });
}

// 开始所有消息相关的定期检查
function startUnreadChecksAndUpdates() {
    console.log('启动所有消息相关的定期检查');
    
    // 立即检查一次未读消息
    checkUnreadMessagesAndUpdate();
    
    // 清除可能存在的旧定时器
    if (window.unreadMessageTimer) {
        clearInterval(window.unreadMessageTimer);
    }
    
    // 设置每30秒检查一次未读消息的定时器
    window.unreadMessageTimer = setInterval(function() {
        console.log('定时检查未读消息');
        
        // 检查未读消息，只更新图标
        checkUnreadMessagesAndUpdate();
        
        // 如果模态框已打开，并且有新消息标志，则刷新消息列表
        if ($('#messagesModal').hasClass('show') && localStorage.getItem('hasNewMessages') === 'true') {
            console.log('模态框已打开且有新消息，刷新消息列表');
            localStorage.removeItem('hasNewMessages');
            refreshMessagesAndConversations();
        }
    }, 30000);
    
    // 当模态框关闭时，清除定时器
    $('#messagesModal').on('hidden.bs.modal', function() {
        console.log('消息模态框已关闭，清除定时器');
        if (window.unreadMessageTimer) {
            clearInterval(window.unreadMessageTimer);
        }
    });
}

// 检查未读消息并更新图标
function checkUnreadMessagesAndUpdate() {
    console.log('检查未读消息并更新图标');
    
    // 统一使用的存储方式，优先使用localStorage，然后尝试sessionStorage
    var token = localStorage.getItem('token') || sessionStorage.getItem('token');
    var userId = localStorage.getItem('userId') || localStorage.getItem('id') || sessionStorage.getItem('id');
    
    if (!token || !userId) {
        console.log('未登录，跳过未读消息检查');
        return; // 如果未登录，不检查
    }
    
    console.log('发送未读消息检查请求，用户ID:', userId);
    
    $.ajax({
        url: 'chkmsg.php',
        type: 'POST',
        data: {
            token: token
            // Remove uid as backend derives it from token
            // uid: userId 
        },
        dataType: 'json',
        success: function(response) {
            console.log('未读消息检查响应:', response);
            
            if (response.success) {
                const unreadCount = response.unreadCount || 0;
                console.log('未读消息数量:', unreadCount);
                
                // 显示未读消息数量和更新图标
                updateUnreadMessageIndicators(unreadCount);
                
                // 如果有未读消息，设置一个标志表示有新消息
                if (unreadCount > 0) {
                    localStorage.setItem('hasNewMessages', 'true');
                }
            }
        },
        error: function(xhr, status, error) {
            console.error('检查未读消息失败:', error, xhr.responseText);
            console.log('请求未读消息数量失败');
        }
    });
}

// 更新未读消息指示器
function updateUnreadMessageIndicators(unreadCount) {
    // 获取消息图标元素
    const msgIcon = $('.msg-icon');
    $('nav .fas.fa-envelope');
    
    if (unreadCount > 0) {
        // 显示未读消息数量徽章
        $('#unreadMessagesCount').text(unreadCount).show();
        
        // 更改图标颜色为红色
        $('nav .fas.fa-envelope').css('color', 'red');
        msgIcon.addClass('text-danger');
        
        // 移除旧的消息计数徽章，添加新的
        $('.msg-badge').remove();
        msgIcon.parent().append(`<span class="msg-badge position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">${unreadCount}</span>`);
    } else {
        // 无未读消息，恢复图标原始颜色和移除未读数量
        $('#unreadMessagesCount').hide();
        $('nav .fas.fa-envelope').css('color', 'rgba(255, 255, 255, .5)');
        msgIcon.removeClass('text-danger');
        $('.msg-badge').remove();
    }
}

// 刷新消息和会话列表
function refreshMessagesAndConversations() {
    // 如果模态框未显示，则不进行刷新
    if (!$('#messagesModal').hasClass('show')) {
        console.log('模态框未显示，不刷新消息');
        return;
    }
    
    console.log('刷新消息和会话列表');
    
    // 添加加载动画
    var modalBody = $('#messagesModal').find('.modal-body');
    if (!$('#loading').length) {
        modalBody.prepend('<div id="loading"><div class="spinner-border text-primary" role="status"><span class="sr-only">加载中...</span></div></div>');
    }
    
    // 获取消息
    fetchMessages().then(function(data) {
        $('#loading').remove(); // 移除加载动画
        
        if (data.success) {
            // 重建会话列表
            buildConversationsList(data);
            
            // 如果有当前选中的会话，则刷新该会话的消息
            const currentChatPartnerId = localStorage.getItem('currentChatPartnerId');
            if (currentChatPartnerId && $('#messageContainer').is(':visible')) {
                const partnerName = $('#messagesModalLabel').text().replace('与 ', '').replace(' 的对话', '');
                loadMessages(currentChatPartnerId, partnerName);
            }
        } else {
            console.error('获取消息失败:', data.message);
            showAlert('刷新消息失败：' + (data.message || '未知错误'), 'error');
        }
    }).catch(function(error) {
        $('#loading').remove(); // 确保即使发生错误也要移除加载动画
        console.error('刷新消息出错:', error);
        showAlert('刷新消息出错：' + error.message, 'error');
    });
}

// Add Turnstile callback function (can be empty if only using explicit rendering)
function turnstileCallbackRegister(token) {
    console.log('Register Turnstile verified with token:', token);
    // You can enable the submit button here if it was disabled
}

// Dynamically load Turnstile script if not already loaded
function loadTurnstileScript() {
    if (!window.turnstile && !document.querySelector('script[src*="challenges.cloudflare.com/turnstile"]')) {
        const script = document.createElement('script');
        // Use a generic callback name or handle loading state differently
        script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js?onload=turnstileScriptLoadedCallback'; 
        script.async = true;
        script.defer = true;
        document.head.appendChild(script);
    }
}

// Callback function when Turnstile script is loaded
function turnstileScriptLoadedCallback() {
    console.log('Turnstile API script loaded globally.');
    // Now Turnstile should be available. 
    // You might need to trigger rendering if modals were opened before script load.
}

// Make sure to call loadTurnstileScript early, e.g., in document ready
$(document).ready(function() {
    // ... other ready functions ...
    loadTurnstileScript();
     fetchTurnstileSiteKey(); // Fetch the key when the document is ready
    // ... rest of the document ready function ...
});

