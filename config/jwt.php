<?php

return [
    'secret' => env('JWT_SECRET'),
    'algorithm' => env('JWT_ALGORITHM', 'HS256'),
    'ttl' => (int) env('JWT_TTL', 60),
    'issuer' => env('JWT_ISSUER', env('APP_URL', 'http://localhost')),
];
