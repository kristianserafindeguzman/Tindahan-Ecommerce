<?php

return [
    'default' => env('BROADCAST_CONNECTION', 'null'),
    'connections' => [
        // Credentials are filled from the existing application key by AppServiceProvider.
        'reverb' => [
            'driver' => 'reverb',
            'key' => 'tindahan',
            'secret' => '',
            'app_id' => 'tindahan',
            'options' => [
                'host' => env('REVERB_PUBLISH_HOST', '127.0.0.1'),
                'port' => (int) env('REVERB_PUBLISH_PORT', 8080),
                'scheme' => env('REVERB_PUBLISH_SCHEME', 'http'),
                'useTLS' => env('REVERB_PUBLISH_SCHEME', 'http') === 'https',
            ],
            'client_options' => ['connect_timeout' => 1, 'timeout' => 2],
        ],
        'log' => ['driver' => 'log'],
        'null' => ['driver' => 'null'],
    ],
];
