<?php

return [
    'paths' => ['*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('CORS_ALLOWED_ORIGINS', 'http://localhost'))
    ))),
    'allowed_headers' => ['*'],
    'exposed_headers' => ['XSRF-TOKEN'],
    'max_age' => (int) env('CORS_MAX_AGE', 0),
    'supports_credentials' => filter_var(env('CORS_SUPPORTS_CREDENTIALS', true), FILTER_VALIDATE_BOOLEAN),
];
