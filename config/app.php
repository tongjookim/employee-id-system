<?php

return [
    'name' => env('APP_NAME', '사원증관리시스템'),
    'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),
    'timezone' => 'Asia/Seoul',
    'locale' => 'ko',
    'fallback_locale' => 'en',
    'faker_locale' => 'ko_KR',
    'cipher' => 'AES-256-CBC',
    'key' => env('APP_KEY'),
    'previous_keys' => [...array_filter(explode(',', env('APP_PREVIOUS_KEYS', '')))],
    'maintenance' => ['driver' => env('APP_MAINTENANCE_DRIVER', 'file')],
];
