<?php
// Deprecated migration script: Do not use.
// This endpoint has been intentionally disabled. Please run the SQL script under sql/ instead.

header('Content-Type: application/json; charset=utf-8');
http_response_code(410); // Gone
echo json_encode([
  'success' => false,
  'error' => 'This migration endpoint has been removed. Use the SQL migration file instead.',
  'sql_migration' => 'sql/2025-09-03_add_avatars_table_and_migrate_users.sql'
]);

// No closing PHP tag
