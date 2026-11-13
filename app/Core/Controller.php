<?php

declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    /**
     * @param array<string, mixed> $data
     */
    protected function render(string $view, array $data = [], int $status = 200): void
    {
        View::render($view, $data, $status);
    }

    protected function flash(string $type, string $message): void
    {
        Session::flash($type, $message);
    }

    /**
     * @param array<string, string> $errors
     * @param array<string, mixed> $old
     */
    protected function redirectWithErrors(string $path, array $errors, array $old = []): never
    {
        Session::flash('errors', $errors);
        if ($old !== []) {
            Session::flash('old', $old);
        }

        redirect($path);
    }
}
