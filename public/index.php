<?php

require __DIR__ . '/../src/bootstrap.php';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$path = rtrim($path, '/') ?: '/';

$routes = [
    '/' => __DIR__ . '/public/index.php',
    '/public' => __DIR__ . '/public/index.php',
    '/admin/login' => __DIR__ . '/admin/login.php',
    '/admin/logout' => __DIR__ . '/admin/logout.php',
    '/admin/users' => __DIR__ . '/admin/users.php',
    '/admin/assignments' => __DIR__ . '/admin/assignments.php',
    '/admin/debts' => __DIR__ . '/admin/debts.php',
    '/activity' => __DIR__ . '/activity/index.php',
];

if (isset($routes[$path])) {
    require $routes[$path];
    exit;
}

http_response_code(404);
echo 'Not Found.';
