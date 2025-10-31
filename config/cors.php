<?php

return [
    'paths' => ['*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [env('CORS_ALLOWED_ORIGINS')],
    'allowed_headers' => ['*'],
    'exposed_headers' => ['XSRF-TOKEN'],
    'max_age' => 0,
    'supports_credentials' => true,
];
