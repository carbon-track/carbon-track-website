<?php
// Adds an 'avatar' column to users table if it doesn't exist
require_once 'db.php';

header('Content-Type: text/plain; charset=UTF-8');

try {
	// Check if column exists
	$check = $pdo->query("SHOW COLUMNS FROM `users` LIKE 'avatar'");
	if ($check->rowCount() === 0) {
		$pdo->exec("ALTER TABLE `users` ADD COLUMN `avatar` VARCHAR(255) NULL AFTER `location`");
		echo "Avatar column added.\n";
	} else {
		echo "Avatar column already exists.\n";
	}
	// Optionally set a default for users with NULL
	$stmt = $pdo->prepare("UPDATE `users` SET `avatar` = :def WHERE `avatar` IS NULL OR `avatar` = ''");
	$stmt->execute([':def' => 'avatar1.svg']);
	echo "Initialized empty avatars to default.\n";
} catch (Exception $e) {
	http_response_code(500);
	echo 'Migration failed: ' . $e->getMessage();
}
// No closing PHP tag
