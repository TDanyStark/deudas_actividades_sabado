<?php

$config = require __DIR__ . '/../config/config.php';

date_default_timezone_set($config['app']['timezone']);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/auth.php';

function app_config(): array
{
    global $config;
    return $config;
}
