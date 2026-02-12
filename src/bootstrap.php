<?php

// Simple .env loader for local development.
$env_path = __DIR__ . '/../.env';
if (is_readable($env_path)) {
    $lines = file($env_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || str_starts_with($trimmed, '#')) {
            continue;
        }
        $parts = explode('=', $trimmed, 2);
        $key = trim($parts[0]);
        $value = $parts[1] ?? '';
        $value = trim($value);
        if ($key !== '' && getenv($key) === false) {
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

$config = require __DIR__ . '/../config/config.php';

date_default_timezone_set($config['app']['timezone']);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/auth.php';

if (!function_exists('app_config')) {
    function app_config(): array
    {
        global $config;
        return $config;
    }
}
