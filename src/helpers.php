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
    return rtrim($base, '/') . '/' . ltrim($path, '/');
}
