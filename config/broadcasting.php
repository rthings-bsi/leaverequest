<?php

return [
    // During local development use the log driver by default to avoid
    // requiring external services like Pusher. This is a safe fallback
    // while debugging connectivity and broadcast errors.
    'default' => env('BROADCAST_DRIVER', 'log'),

    'connections' => [
        'pusher' => [
            'driver' => 'pusher',
            'key' => env('PUSHER_APP_KEY'),
            'secret' => env('PUSHER_APP_SECRET'),
            'app_id' => env('PUSHER_APP_ID'),
            'options' => [
                // Host/port for self-hosted or Pusher-compatible servers
                'cluster' => env('PUSHER_APP_CLUSTER'),
                'useTLS' => env('PUSHER_APP_USE_TLS', true),
                'host' => env('PUSHER_HOST', null),
                'port' => env('PUSHER_PORT', null),
                'scheme' => env('PUSHER_SCHEME', null),
                'encrypted' => env('PUSHER_APP_ENCRYPTED', null),
            ],
        ],

        'ably' => [
            'driver' => 'ably',
            'key' => env('ABLY_KEY'),
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
        ],

        'log' => [
            'driver' => 'log',
        ],

        'null' => [
            'driver' => 'null',
        ],
    ],
];
