<?php

declare(strict_types=1);

use App\Core\HttpException;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Router;
use App\Core\View;

require dirname(__DIR__) . '/app/bootstrap.php';

$router = new Router();
(require BASE_PATH . '/routes/web.php')($router);

try {
    $router->dispatch(Request::method(), Request::path());
} catch (HttpException $exception) {
    $status = $exception->getStatusCode();

    if ($status >= 500) {
        Logger::error($exception->getMessage(), [
            'type' => 'http_exception',
            'status' => $status,
            'path' => Request::path(),
        ]);
    }

    $titles = [
        403 => 'Access denied',
        404 => 'Page not found',
        405 => 'Method not allowed',
        419 => 'Security check failed',
        422 => 'Invalid request',
        429 => 'Too many requests',
    ];

    $messages = [
        403 => 'You do not have permission to access this page.',
        404 => 'The page you are looking for could not be found.',
        405 => 'That request method is not supported for this page.',
        419 => 'Your security token has expired. Please go back, refresh the page and try again.',
        422 => 'The request could not be processed because some information was invalid.',
        429 => 'Too many requests. Please wait a moment and try again.',
    ];

    try {
        View::renderError(
            $status,
            $titles[$status] ?? 'Request failed',
            $exception->getPublicMessage() ?? ($messages[$status] ?? 'The request could not be completed.')
        );
    } catch (Throwable $renderException) {
        Logger::error('Failed to render HTTP error page: ' . $renderException->getMessage());
        http_response_code($status);
        header('Content-Type: text/html; charset=UTF-8');
        echo '<!doctype html><html lang="en"><meta charset="utf-8"><title>Error</title>'
            . '<body style="font-family:sans-serif;padding:2rem"><h1>Error ' . $status . '</h1>'
            . '<p>' . htmlspecialchars($exception->getPublicMessage() ?? 'Something went wrong.', ENT_QUOTES, 'UTF-8') . '</p></body></html>';
    }
} catch (Throwable $exception) {
    Logger::error($exception->getMessage(), [
        'type' => get_class($exception),
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'trace' => substr($exception->getTraceAsString(), 0, 5000),
        'path' => Request::path(),
    ]);

    try {
        View::renderError(
            500,
            'Something went wrong',
            'An unexpected error occurred. Please try again. If the problem continues, contact the administrator.'
        );
    } catch (Throwable $renderException) {
        Logger::error('Failed to render 500 error page: ' . $renderException->getMessage());
        http_response_code(500);
        header('Content-Type: text/html; charset=UTF-8');
        echo '<!doctype html><html lang="en"><meta charset="utf-8"><title>Server error</title>'
            . '<body style="font-family:sans-serif;padding:2rem"><h1>Something went wrong</h1>'
            . '<p>Please try again later.</p></body></html>';
    }
}
