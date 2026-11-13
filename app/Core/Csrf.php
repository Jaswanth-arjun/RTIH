<?php

declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    public static function token(): string
    {
        $token = Session::get('_csrf_token');
        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            Session::put('_csrf_token', $token);
        }

        return $token;
    }

    public static function rotate(): void
    {
        Session::forget('_csrf_token');
    }

    public static function validate(?string $token): bool
    {
        $expected = Session::get('_csrf_token');
        if (!is_string($expected) || $expected === '' || !is_string($token) || $token === '') {
            return false;
        }

        return hash_equals($expected, $token);
    }

    public static function requireValid(?string $token): void
    {
        if (!self::validate($token)) {
            abort(
                419,
                'CSRF token validation failed.',
                'Your security token has expired or is invalid. Please go back, refresh the page and try again.'
            );
        }
    }
}
