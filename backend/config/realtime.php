<?php

return [
    // Application settings only: no new environment variables are required.
    'enabled' => true,
    'server_host' => '127.0.0.1',
    'server_port' => 8080,
    // Null values use the API hostname, ws:8080 locally and wss:443 in production.
    'public_host' => env('REVERB_PUBLIC_HOST'),
    'public_port' => env('REVERB_PUBLIC_PORT') ? (int) env('REVERB_PUBLIC_PORT') : null,
    'public_scheme' => env('REVERB_PUBLIC_SCHEME'),
    'public_path' => '',
    // Empty means derive origins from the existing app/frontend URLs and localhost.
    'allowed_origins' => [],
];
