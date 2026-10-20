<?php

return [
    // Application settings only: no new environment variables are required.
    'enabled' => true,
    'server_host' => '127.0.0.1',
    'server_port' => 8080,
    // Null values use the API hostname, ws:8080 locally and wss:443 in production.
    'public_host' => null,
    'public_port' => null,
    'public_scheme' => null,
    'public_path' => '',
    // Empty means derive origins from the existing app/frontend URLs and localhost.
    'allowed_origins' => [],
];
