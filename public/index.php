<?php

require __DIR__ . '/../src/bootstrap.php';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$path = rtrim($path, '/') ?: '/';

$base_url = app_config()['app']['base_url'] ?? '';
$base_path = $base_url !== '' ? (parse_url($base_url, PHP_URL_PATH) ?: '') : '';
$base_path = rtrim($base_path, '/');

if ($base_path !== '' && str_starts_with($path, $base_path)) {
    $path = substr($path, strlen($base_path));
    $path = rtrim($path, '/') ?: '/';
}

if (str_starts_with($path, '/assets/')) {
    $asset_base = realpath(__DIR__ . '/assets');
    $asset_path = realpath(__DIR__ . $path);
    if ($asset_base && $asset_path && str_starts_with($asset_path, $asset_base) && is_file($asset_path)) {
        $extension = strtolower(pathinfo($asset_path, PATHINFO_EXTENSION));
        $mime_map = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'svg' => 'image/svg+xml',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
        ];
        if (isset($mime_map[$extension])) {
            header('Content-Type: ' . $mime_map[$extension]);
        }
        readfile($asset_path);
        exit;
    }
}

$routes = [
    '/' => __DIR__ . '/public/index.php',
    '/public' => __DIR__ . '/public/index.php',
    '/public/index.php' => __DIR__ . '/public/index.php',
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
