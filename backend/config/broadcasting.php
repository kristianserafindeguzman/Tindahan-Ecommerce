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
            'options' => ['host' => '127.0.0.1', 'port' => 8080, 'scheme' => 'http', 'useTLS' => false],
            'client_options' => ['connect_timeout' => 1, 'timeout' => 2],
        ],
        'log' => ['driver' => 'log'],
        'null' => ['driver' => 'null'],
    ],
];
