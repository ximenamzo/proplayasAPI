<?php

return [
    'secret' => env('JWT_SECRET', 'ProplayasOrg00!'),
    'ttl' => env('JWT_TTL', 60 * 60 * 24 * 7), // 7 days
];