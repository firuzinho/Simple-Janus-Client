<?php

require_once(__DIR__ . '/src/Autoloader.php');
require_once(__DIR__ . '/vendor/autoload.php');

use Videoroom\App\AppFactory;

try {
    $logger = new \Katzgrau\KLogger\Logger(__DIR__ . '/logs');

    $appFactory = new AppFactory();
    
    $app = $appFactory();

    $app->run();


} catch (Exception $e) {
    $logger->error("Error: " . $e->getMessage());

    http_response_code(500);

    exit(json_encode([
        'errors' => [
            'status' => 500,
            'title' => 'Internal Server Error',
        ]
    ]));
}