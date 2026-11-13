<?php

declare(strict_types=1);

/**
 * Global view and controller helpers.
 */

function e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function env(string $key, mixed $default = null): mixed
{
    return App\Core\Env::get($key, $default);
}

function config(string $key, mixed $default = null): mixed
{
    return App\Core\Config::get($key, $default);
}

/**
 * Build an application URL that works when the app is hosted in a subdirectory.
 */
function url(string $path = '/'): string
{
    $path = '/' . ltrim($path, '/');
    if ($path === '/') {
        return BASE_URL === '' ? '/' : BASE_URL . '/';
    }

    return BASE_URL . $path;
}

function asset(string $path): string
{
    return url('/assets/' . ltrim($path, '/'));
}

function url_with_query(string $path, array $query): string
{
    $query = array_filter(
        $query,
        static fn (mixed $value): bool => $value !== null && $value !== ''
    );

    return url($path) . ($query === [] ? '' : '?' . http_build_query($query));
}

function redirect(string $path, int $status = 302): never
{
    if (!headers_sent()) {
        header('Location: ' . url($path), true, $status);
    }
    exit;
}

/**
 * @param array<string, mixed> $context
 */
function abort(int $status, string $message = '', ?string $publicMessage = null): never
{
    throw new App\Core\HttpException($status, $message, $publicMessage);
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(App\Core\Csrf::token()) . '">';
}

/**
 * @return array<string, string>
 */
function task_statuses(): array
{
    return [
        'pending' => 'Pending',
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
    ];
}

/**
 * @return array<string, string>
 */
function task_priorities(): array
{
    return [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
    ];
}

function task_status_label(?string $status): string
{
    return task_statuses()[$status ?? ''] ?? 'Unknown';
}

function task_status_class(?string $status): string
{
    return array_key_exists($status ?? '', task_statuses()) ? (string) $status : 'pending';
}

function task_priority_label(?string $priority): string
{
    return task_priorities()[$priority ?? ''] ?? 'Medium';
}

function task_priority_class(?string $priority): string
{
    return array_key_exists($priority ?? '', task_priorities()) ? (string) $priority : 'medium';
}

function format_date(?string $date, string $format = 'M j, Y'): string
{
    if ($date === null || $date === '') {
        return '—';
    }

    try {
        return (new DateTimeImmutable($date))->format($format);
    } catch (Throwable) {
        return '—';
    }
}

function format_datetime(?string $date, string $format = 'M j, Y, g:i A'): string
{
    return format_date($date, $format);
}

function due_date_state(?string $dueDate): string
{
    if ($dueDate === null || $dueDate === '') {
        return '';
    }

    try {
        $today = new DateTimeImmutable('today');
        $due = new DateTimeImmutable($dueDate);

        if ($due < $today) {
            return 'overdue';
        }
        if ($due == $today) {
            return 'today';
        }
        if ($due <= $today->modify('+3 days')) {
            return 'soon';
        }
    } catch (Throwable) {
        return '';
    }

    return 'upcoming';
}

function due_date_label(?string $dueDate): string
{
    if ($dueDate === null || $dueDate === '') {
        return '';
    }

    try {
        $today = new DateTimeImmutable('today');
        $due = new DateTimeImmutable($dueDate);
        $days = (int) $today->diff($due)->format('%r%a');

        if ($days < 0) {
            $count = abs($days);
            return 'Overdue by ' . $count . ' day' . ($count === 1 ? '' : 's');
        }

        return match ($days) {
            0 => 'Due today',
            1 => 'Due tomorrow',
            default => 'Due ' . $due->format('M j, Y'),
        };
    } catch (Throwable) {
        return '';
    }
}

function excerpt(?string $text, int $limit = 160): string
{
    $plain = trim((string) preg_replace('/\s+/u', ' ', (string) $text));
    if ($plain === '') {
        return '';
    }

    if (mb_strlen($plain) <= $limit) {
        return $plain;
    }

    return rtrim(mb_substr($plain, 0, $limit - 1)) . '…';
}

function is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (string) ($_SERVER['SERVER_PORT'] ?? '') === '443';
}

/**
 * Only allow redirects to local paths, preventing open redirects.
 */
function safe_local_path(?string $path, string $fallback = '/tasks'): string
{
    if (!is_string($path) || $path === '' || !str_starts_with($path, '/')) {
        return $fallback;
    }

    if (str_starts_with($path, '//') || preg_match('/[\\\\\r\n]/', $path) === 1) {
        return $fallback;
    }

    return $path;
}

function nav_active(string $path): string
{
    $current = App\Core\Request::path();

    if ($path === '/tasks') {
        return $current === '/tasks' || preg_match('#^/tasks/[0-9]+#', $current) === 1
            ? 'is-active'
            : '';
    }

    return $current === $path || str_starts_with($current, rtrim($path, '/') . '/')
        ? 'is-active'
        : '';
}
