<?php
function isAdmin($email)
{
    // Define a list of admin emails or fetch from the database
    //$adminEmails = ['lyuzn.jeffery2023@gdhfi.com']; // Replace with actual admin emails
    $adminEmails = ['lyuzn.jeffery2023@gdhfi.com', '2116403107@qq.com', 'cpurescuerhu@outlook.com', 'yangyangzhouyh@outlook.com', '2964608199@qq.com', 'tangke_0225@qq.com', 'ruoxuan.gao@hotmail.com'];
    return in_array($email, $adminEmails);
}

?>
