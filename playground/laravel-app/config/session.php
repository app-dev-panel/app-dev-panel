<?php

declare(strict_types=1);

return [
    'driver' => 'file',
    'lifetime' => 120,
    'expire_on_close' => false,
    'encrypt' => false,
    'files' => storage_path('framework/sessions'),
    'table' => 'sessions',
    'connection' => null,
    'store' => null,
    'lottery' => [2, 100],
    'cookie' => 'adp_session',
    'path' => '/',
    'domain' => null,
    'secure' => false,
    'http_only' => true,
    'same_site' => 'lax',
    'partitioned' => false,
];
