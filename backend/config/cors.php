<?php

$frontendOrigins = preg_split(
    '/\s*,\s*/',
    (string) env('TELEPINOY_FRONTEND_ORIGINS', ''),
    -1,
    PREG_SPLIT_NO_EMPTY,
);

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => array_values(array_unique(array_merge([
        'capacitor://localhost',
        'http://localhost',
        'https://localhost',
    ], $frontendOrigins))),
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];
