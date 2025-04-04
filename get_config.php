<?php
// get_config.php
require_once 'global_variables.php'; // Include global variables where keys are defined

header('Content-Type: application/json');

// Create an array with the configuration values needed by the frontend
$config = [
    'turnstile_sitekey' => $turnstile_sitekey // Get the site key from global_variables.php
];

// Output the configuration as JSON
echo json_encode($config);

?> 