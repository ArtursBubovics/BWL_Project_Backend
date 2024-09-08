<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', '/*', '*'], // Add your routes here
    'allowed_methods' => ['*'],
    'allowed_origins' => ['*'],
    'allowed_origins' => ['http://127.0.0.1:3000'], // Update this with the origin of your React app
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];