<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\User;

final class Auth
{
    /** @var array<string, mixed>|null */
    private static ?array $user = null;

    private static bool $loaded = false;

    /**
     * A valid hash used to keep failed-login timing similar whether or not the account exists.
     */
    private const DUMMY_HASH = '$2y$12$CdstufaMApXCjBSVH3G90ecSNil73dvPRbZPWBQK8GxTjraz1aB4u';

    /**
     * @return array<string, mixed>|null
     */
    public static function user(): ?array
    {
        if (self::$loaded) {
            return self::$user;
        }

        self::$loaded = true;
        $userId = Session::get('user_id');

        if (!is_int($userId) && !(is_string($userId) && ctype_digit($userId))) {
            return null;
        }

        $lastActivity = Session::get('_last_activity');
        $lifetime = (int) config('session.lifetime', 7200);
        if (is_int($lastActivity) && $lifetime > 0 && (time() - $lastActivity) > $lifetime) {
            self::logout();
            Session::flash('error', 'Your session expired. Please sign in again.');
            return null;
        }

        $user = User::findPublicById((int) $userId);
        if ($user === null || (int) $user['is_active'] !== 1) {
            self::logout();
            return null;
        }

        $_SESSION['_last_activity'] = time();
        self::$user = $user;

        return self::$user;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function id(): ?int
    {
        $user = self::user();

        return $user === null ? null : (int) $user['id'];
    }

    public static function isAdmin(): bool
    {
        return (self::user()['role'] ?? '') === 'admin';
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function attempt(string $email, string $password): ?array
    {
        $user = User::findByEmail(mb_strtolower(trim($email)));

        if ($user === null || (int) $user['is_active'] !== 1) {
            password_verify($password, self::DUMMY_HASH);
            return null;
        }

        if (!password_verify($password, (string) $user['password_hash'])) {
            return null;
        }

        if (password_needs_rehash((string) $user['password_hash'], PASSWORD_DEFAULT)) {
            User::updatePasswordHash((int) $user['id'], password_hash($password, PASSWORD_DEFAULT));
        }

        return $user;
    }

    /**
     * @param array<string, mixed> $user
     */
    public static function login(array $user): void
    {
        Session::regenerate();
        Session::put('user_id', (int) $user['id']);
        Session::put('_last_activity', time());
        Csrf::rotate();

        unset($user['password_hash']);
        self::$user = $user;
        self::$loaded = true;
    }

    public static function logout(): void
    {
        unset($_SESSION['user_id'], $_SESSION['_last_activity'], $_SESSION['_login_attempts']);
        Session::regenerate();
        Csrf::rotate();

        self::$user = null;
        self::$loaded = true;
    }

    public static function requireLogin(): void
    {
        if (self::check()) {
            return;
        }

        $current = Request::fullPath();
        if ($current !== '/login' && !str_starts_with($current, '/login?')) {
            Session::put('intended', $current);
        }

        Session::flash('warning', 'Please sign in to continue.');
        redirect('/login');
    }

    public static function requireGuest(): void
    {
        if (self::check()) {
            redirect('/tasks');
        }
    }

    public static function requireRole(string $role): void
    {
        self::requireLogin();

        if ((self::user()['role'] ?? '') !== $role) {
            abort(403, 'Role authorization failed.', 'You do not have permission to access this page.');
        }
    }

    public static function tooManyAttempts(string $email): bool
    {
        $attempts = self::loginAttempts();
        $key = self::throttleKey($email);
        $entry = $attempts[$key] ?? null;

        if (!is_array($entry)) {
            return false;
        }

        $window = (int) config('security.login_lockout_seconds', 900);
        if (time() - (int) ($entry['first'] ?? 0) > $window) {
            unset($attempts[$key]);
            Session::put('_login_attempts', $attempts);
            return false;
        }

        return (int) ($entry['count'] ?? 0) >= (int) config('security.login_max_attempts', 5);
    }

    public static function recordFailedAttempt(string $email): void
    {
        $attempts = self::loginAttempts();
        $key = self::throttleKey($email);
        $window = (int) config('security.login_lockout_seconds', 900);
        $entry = $attempts[$key] ?? null;

        if (!is_array($entry) || time() - (int) ($entry['first'] ?? 0) > $window) {
            $attempts[$key] = ['count' => 1, 'first' => time()];
        } else {
            $entry['count'] = (int) ($entry['count'] ?? 0) + 1;
            $attempts[$key] = $entry;
        }

        Session::put('_login_attempts', $attempts);
    }

    public static function clearFailedAttempts(string $email): void
    {
        $attempts = self::loginAttempts();
        unset($attempts[self::throttleKey($email)]);
        Session::put('_login_attempts', $attempts);
    }

    public static function lockoutMinutesRemaining(string $email): int
    {
        $entry = self::loginAttempts()[self::throttleKey($email)] ?? null;
        if (!is_array($entry)) {
            return 0;
        }

        $remaining = (int) ($entry['first'] ?? 0)
            + (int) config('security.login_lockout_seconds', 900)
            - time();

        return max(0, (int) ceil($remaining / 60));
    }

    /**
     * @return array<string, array{count: int, first: int}>
     */
    private static function loginAttempts(): array
    {
        $attempts = Session::get('_login_attempts', []);

        return is_array($attempts) ? $attempts : [];
    }

    private static function throttleKey(string $email): string
    {
        return hash('sha256', mb_strtolower(trim($email)));
    }
}
