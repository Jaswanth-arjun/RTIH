<?php

declare(strict_types=1);

namespace App\Core;

final class View
{
    /**
     * @param array<string, mixed> $data
     */
    public static function render(string $view, array $data = [], int $status = 200): void
    {
        $layout = (string) ($data['layout'] ?? 'layouts/app');
        unset($data['layout']);

        $data['title'] = $data['title'] ?? config('app.name', 'TaskFlow');
        $data['heading'] = $data['heading'] ?? $data['title'];

        $content = self::capture($view, $data);

        if (!headers_sent()) {
            http_response_code($status);
        }

        extract($data, EXTR_SKIP);
        require self::path($layout);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function renderError(int $status, string $title, string $message, array $data = []): void
    {
        self::render('errors/error', array_merge($data, [
            'layout' => 'layouts/minimal',
            'title' => $title,
            'heading' => $title,
            'code' => $status,
            'message' => $message,
        ]), $status);
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function capture(string $view, array $data): string
    {
        extract($data, EXTR_SKIP);

        ob_start();
        require self::path($view);

        return (string) ob_get_clean();
    }

    private static function path(string $view): string
    {
        $path = BASE_PATH . '/resources/views/' . str_replace('.', '/', $view) . '.php';

        if (!is_file($path)) {
            throw new \RuntimeException('View not found: ' . $view);
        }

        return $path;
    }
}
