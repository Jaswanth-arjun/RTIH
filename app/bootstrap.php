<?php

declare(strict_types=1);

use App\Core\Config;
use App\Core\Env;
use App\Core\Session;

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/app/Support/helpers.php';

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $path = BASE_PATH . '/app/' . str_replace('\\', '/', $relative) . '.php';

    if (is_file($path)) {
        require $path;
    }
});

Env::load(BASE_PATH . '/.env');
Config::load(require BASE_PATH . '/config/config.php');

date_default_timezone_set((string) config('app.timezone', 'UTC'));

error_reporting(E_ALL);
ini_set('display_errors', config('app.debug', false) ? '1' : '0');
ini_set('log_errors', '0');

$scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
$basePath = PHP_SAPI === 'cli' ? '' : str_replace('\\', '/', dirname($scriptName));
$basePath = rtrim($basePath, '/');
if ($basePath === '.' || $basePath === '/') {
    $basePath = '';
}
define('BASE_URL', $basePath);

if (PHP_SAPI !== 'cli') {
    if (!headers_sent()) {
        header_remove('X-Powered-By');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
        header(
            "Content-Security-Policy: default-src 'self'; base-uri 'self'; form-action 'self'; "
            . "frame-ancestors 'none'; object-src 'none'; img-src 'self' data:; "
            . "style-src 'self'; script-src 'self'; font-src 'self'"
        );
        header('Cache-Control: no-store, no-cache, must-revalidate, private');
        header('Pragma: no-cache');
    }

    Session::start();
}
