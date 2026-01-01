<?php

// Secret key
$secret = 'Pwt5atr!4';

// Get headers
$headers = getallheaders();
$received_secret = $headers['X-Deploy-Secret'] ?? $_SERVER['HTTP_X_DEPLOY_SECRET'] ?? '';

// Validate secret
if ($received_secret !== $secret) {
    http_response_code(403);
    header('Content-Type: application/json');
    die(json_encode(['status' => 'error', 'message' => 'Forbidden']));
}

// Create trigger file
$trigger_file = '/home/bhij4149/encitycoffee/.deploy-trigger';
$trigger_data = json_encode([
    'timestamp' => date('Y-m-d H:i:s'),
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
]);

if (file_put_contents($trigger_file, $trigger_data)) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'message' => 'Deploy trigger created. Cron will deploy shortly.',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
} else {
    http_response_code(500);
    header('Content-Type: application/json');
    die(json_encode(['status' => 'error', 'message' => 'Failed to create trigger']));
}