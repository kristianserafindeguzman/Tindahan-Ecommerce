<?php

return [
    'default' => 'reverb',
    'servers' => [
        'reverb' => [
            'host' => '127.0.0.1',
            'port' => 8080,
            'path' => '',
            'hostname' => null,
            'options' => ['tls' => []],
            'max_request_size' => 10000,
            'scaling' => ['enabled' => false, 'channel' => 'reverb', 'server' => []],
            'pulse_ingest_interval' => 15,
            'telescope_ingest_interval' => 15,
        ],
    ],
    'apps' => [
        'provider' => 'config',
        'apps' => [[
            'key' => 'tindahan',
            'secret' => '',
            'app_id' => 'tindahan',
            'allowed_origins' => [],
            'ping_interval' => 60,
            'activity_timeout' => 30,
            'max_connections' => null,
            'max_message_size' => 10000,
            'accept_client_events_from' => 'none',
            'rate_limiting' => ['enabled' => true, 'max_attempts' => 120, 'decay_seconds' => 60, 'terminate_on_limit' => true],
        ]],
    ],
];
