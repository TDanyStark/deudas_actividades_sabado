<?php

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

function url(string $path): string
{
    $base = app_config()['app']['base_url'];
    if ($base === '') {
        return $path;
    }
    if ($base[0] === '/') {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        if ($host !== '') {
            $is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
            $scheme = $is_https ? 'https' : 'http';
            $base = $scheme . '://' . $host . $base;
        }
    }
    return rtrim($base, '/') . '/' . ltrim($path, '/');
}
