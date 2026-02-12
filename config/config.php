<?php

return [
    'db' => [
        'host' => getenv('DB_HOST') ?: 'localhost',
        'name' => getenv('DB_NAME') ?: 'iglesia_deudas',
        'user' => getenv('DB_USER') ?: 'root',
        'pass' => getenv('DB_PASS') ?: '',
        'charset' => 'utf8mb4',
    ],
    'app' => [
        'base_url' => getenv('APP_BASE_URL') ?: '',
        'timezone' => getenv('APP_TIMEZONE') ?: 'America/Bogota',
    ],
];
