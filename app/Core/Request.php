<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    public static function method(): string
    {
        return strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    }

    public static function path(): string
    {
        $uri = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
        $uri = is_string($uri) ? $uri : '/';

        if (BASE_URL !== '' && str_starts_with($uri, BASE_URL)) {
            $uri = substr($uri, strlen(BASE_URL));
        }

        $uri = '/' . trim($uri, '/');

        return $uri === '/' ? '/' : rtrim($uri, '/');
    }

    /**
     * Full local path and query string, useful for post-login redirects and filters.
     */
    public static function fullPath(): string
    {
        $path = self::path();
        $query = (string) ($_SERVER['QUERY_STRING'] ?? '');

        return $path . ($query === '' ? '' : '?' . $query);
    }

    public static function isPost(): bool
    {
        return self::method() === 'POST';
    }

    public static function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    public static function string(string $key, string $default = ''): string
    {
        $value = self::input($key);

        return is_scalar($value) ? trim((string) $value) : $default;
    }

    public static function int(string $key, int $default = 0): int
    {
        $value = self::input($key);

        return is_scalar($value) ? (int) $value : $default;
    }

    /**
     * @return array<string, mixed>
     */
    public static function post(): array
    {
        return is_array($_POST) ? $_POST : [];
    }
}
