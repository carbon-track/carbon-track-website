<?php
// 生成一个256位（32字节）的随机密钥
$randomKey = openssl_random_pseudo_bytes(32);
// 将密钥转换为Base64编码，以便安全存储和使用
$base64EncodedKey = base64_encode($randomKey);
echo $base64EncodedKey;
?>
