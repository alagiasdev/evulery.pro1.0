<?php

return [
    'host'       => env('MAIL_HOST', 'localhost'),
    'port'       => env('MAIL_PORT', 587),
    'username'   => env('MAIL_USERNAME', ''),
    'password'   => env('MAIL_PASSWORD', ''),
    'encryption' => env('MAIL_ENCRYPTION', 'tls'),
    'from'       => env('MAIL_FROM', 'noreply@evulery.pro'),
    'from_name'  => env('MAIL_FROM_NAME', 'Evulery'),
];
