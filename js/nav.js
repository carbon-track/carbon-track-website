$(document).ready(function() {
    // 加载导航栏
    loadNavbar();
    
    // 检查登录状态
    checkLoginStatus();
    
    // 设置图标容器为relative，以便放置徽章
    $('.fa-envelope').parent().css('position', 'relative');
    
    // 启动未读消息检查
    startUnreadMessageCheck();
    
    // 加载页脚
    $("#footer-placeholder").load("footer.html");
});

