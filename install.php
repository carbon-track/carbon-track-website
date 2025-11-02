<?php

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $host = $_POST['host'];
    $db = $_POST['db'];
    $user = $_POST['user'];
    $pass = $_POST['pass'];
    $charset = 'utf8mb4';
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    $db_content = "<?php\n";
    $db_content .= "require_once 'global_variables.php';\n";
    $db_content .= "\$host = '$host';\n";
    $db_content .= "\$db   = '$db';\n";
    $db_content .= "\$user = '$user';\n";
    $db_content .= "\$pass = '$pass';\n";
    $db_content .= "\$charset = '$charset';\n\n";
    $db_content .= "\$options = [\n";
    $db_content .= "    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,\n";
    $db_content .= "    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,\n";
    $db_content .= "    PDO::ATTR_EMULATE_PREPARES   => false,\n";
    $db_content .= "];\n\n";
    $db_content .= "\$dsn = \"mysql:host=\$host;dbname=\$db;charset=\$charset\";\n";
    $db_content .= "try {\n";
    $db_content .= "    \$pdo = new PDO(\$dsn, \$user, \$pass, \$options);\n";
    $db_content .= "} catch (\\PDOException \$e) {\n";
    $db_content .= "    throw new \\PDOException(\$e->getMessage(), (int)\$e->getCode());\n";
    $db_content .= "}\n";
    $db_content .= "?>\n";

    try {
        file_put_contents('db.php', $db_content);

        // Attempt database initialization
        $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
        $pdo = new PDO($dsn, $user, $pass, $options);

        $pdo->exec("CREATE TABLE IF NOT EXISTS `error_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `error_type` varchar(50) DEFAULT NULL,
  `error_message` text,
  `error_file` varchar(255) DEFAULT NULL,
  `error_line` int(11) DEFAULT NULL,
  `error_time` datetime DEFAULT NULL,
  `script_name` varchar(255) DEFAULT NULL,
  `client_get` text,
  `client_post` text,
  `client_files` text,
  `client_cookie` text,
  `client_session` text,
  `client_server` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=19;");

        $pdo->exec("CREATE TABLE IF NOT EXISTS `messages` (
  `message_id` int(11) NOT NULL AUTO_INCREMENT,
  `sender_id` text NOT NULL,
  `receiver_id` text NOT NULL,
  `content` text NOT NULL,
  `send_time` datetime NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`message_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=28;");

        $pdo->exec("CREATE TABLE IF NOT EXISTS `points_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` text NOT NULL,
  `time` text NOT NULL,
  `img` text NOT NULL,
  `points` double DEFAULT NULL,
  `auth` text,
  `raw` double NOT NULL,
  `act` text NOT NULL,
  `uid` int(11) NOT NULL,
  `type` text,
  UNIQUE KEY `id_3` (`id`),
  UNIQUE KEY `id_4` (`id`),
  UNIQUE KEY `id_5` (`id`),
  KEY `id` (`id`),
  KEY `id_2` (`id`),
  KEY `points` (`points`),
  KEY `points_2` (`points`),
  KEY `points_3` (`points`),
  KEY `points_4` (`points`),
  KEY `id_6` (`id`),
  KEY `id_7` (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=243;");

        $pdo->exec("CREATE TABLE IF NOT EXISTS `products` (
  `name` text NOT NULL,
  `product_id` int(10) NOT NULL AUTO_INCREMENT,
  `points_required` int(10) NOT NULL,
  `description` text NOT NULL,
  `image_path` text NOT NULL,
  `stock` int(11) NOT NULL,
  PRIMARY KEY (`product_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4;");

        $pdo->exec("CREATE TABLE IF NOT EXISTS `schools` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=5;");

        $pdo->exec("CREATE TABLE IF NOT EXISTS `spec_points_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` text NOT NULL,
  `time` text NOT NULL,
  `img` text NOT NULL,
  `points` double DEFAULT NULL,
  `auth` text,
  `raw` double NOT NULL,
  `act` text NOT NULL,
  `uid` int(11) NOT NULL,
  UNIQUE KEY `id_3` (`id`),
  UNIQUE KEY `id_4` (`id`),
  UNIQUE KEY `id_5` (`id`),
  KEY `id` (`id`),
  KEY `id_2` (`id`),
  KEY `points` (`points`),
  KEY `points_2` (`points`),
  KEY `points_3` (`points`),
  KEY `points_4` (`points`),
  KEY `id_6` (`id`),
  KEY `id_7` (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=73;");

        $pdo->exec("CREATE TABLE IF NOT EXISTS `transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `points_spent` double NOT NULL,
  `transaction_time` text NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_email` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_2` (`id`),
  UNIQUE KEY `id_4` (`id`),
  KEY `id` (`id`),
  KEY `id_3` (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=18;");

        $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` char(128) NOT NULL,
  `password` char(128) NOT NULL,
  `lastlgn` text NOT NULL,
  `email` text NOT NULL,
  `points` double NOT NULL,
  `school` text NOT NULL,
  `location` text NOT NULL,
  `status` enum('active','deleted') NOT NULL DEFAULT 'active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `id_2` (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=46;");

        echo json_encode(['success' => true, 'message' => 'Database configuration saved and database initialized successfully.']);

    } catch (PDOException $e) {
        // Provide a user-friendly message, but log the detail for debugging if possible
        error_log("Install Error (DB): " . $e->getMessage()); // Log detailed error
        echo json_encode(['success' => false, 'error' => 'Database connection or initialization failed. Check credentials and permissions.']);
    } catch (Exception $e) {
         // Provide a user-friendly message
        error_log("Install Error (File Write): " . $e->getMessage()); // Log detailed error
        echo json_encode(['success' => false, 'error' => 'Failed to write database configuration file (db.php).']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed. Please use POST.']);
}
?>
