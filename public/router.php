<?php

declare(strict_types=1);

/**
 * Router for PHP's built-in development server:
 * php -S 127.0.0.1:8000 -t public public/router.php
 */

$path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
$path = is_string($path) ? $path : '/';
$candidate = realpath(__DIR__ . $path);

if (
    $path !== '/'
    && $candidate !== false
    && is_file($candidate)
    && str_starts_with(str_replace('\\', '/', $candidate), str_replace('\\', '/', __DIR__))
) {
    return false;
}

require __DIR__ . '/index.php';
