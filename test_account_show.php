<?php
// Simple test file to check if the route works
// Access this at: http://localhost:84/test_account_show.php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Create a test request to the show endpoint
$request = Illuminate\Http\Request::create(
    '/admin/settings/system-accounts/1',
    'GET',
    [],
    [],
    [],
    ['HTTP_ACCEPT' => 'application/json', 'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']
);

$response = $kernel->handle($request);

echo "Status Code: " . $response->getStatusCode() . "\n";
echo "Content-Type: " . $response->headers->get('Content-Type') . "\n";
echo "Response:\n";
echo $response->getContent();

$kernel->terminate($request, $response);
