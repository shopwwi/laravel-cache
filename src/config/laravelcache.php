<?php
return [
    'default' => 'file',
    'stores' => [
        'file' => [
            'driver' => 'file',
            'path' => runtime_path('cache/data'),
        ],
        'memcached' => [
            'driver' => 'memcached',
            'persistent_id' => 'MEMCACHED_PERSISTENT_ID',
            'sasl' => ['MEMCACHED_USERNAME','MEMCACHED_PASSWORD'],
            'options' => [
                // Memcached::OPT_CONNECT_TIMEOUT => 2000,
            ],
            'servers' => [
                [
                    'host' => '127.0.0.1',
                    'port' => 11211,
                    'weight' => 100,
                ],
            ],
        ],
        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
            'lock_connection' => 'default',
        ],
        'database' => [
            'driver' => 'database',
            'table' => 'cache',
            'connection' => null,
            'lock_connection' => null,
        ],
    ],
    'prefix' => 'shopwwi_cache_'
];
