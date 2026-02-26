<?php

return [
    // QR 코드 유효 시간 (초) — 기본 10초
    'qr_ttl_seconds' => env('QR_TTL_SECONDS', 10),

    // QR 자동 갱신 여부
    'qr_auto_refresh' => env('QR_AUTO_REFRESH', true),
];
