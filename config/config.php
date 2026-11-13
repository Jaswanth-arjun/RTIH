<?php

declare(strict_types=1);

use App\Core\Env;

return [
    'app' => [
        'name' => (string) Env::get('APP_NAME', 'TaskFlow'),
        'environment' => (string) Env::get('APP_ENV', 'production'),
        'debug' => Env::bool('APP_DEBUG', false),
        'url' => (string) Env::get('APP_URL', 'http://localhost:8000'),
        'timezone' => (string) Env::get('APP_TIMEZONE', 'UTC'),
    ],

    'db' => [
        'host' => (string) Env::get('DB_HOST', '127.0.0.1'),
        'port' => (int) Env::get('DB_PORT', 3306),
        'name' => (string) Env::get('DB_NAME', 'task_tracker'),
        'user' => (string) Env::get('DB_USER', 'root'),
        'password' => (string) Env::get('DB_PASSWORD', ''),
    ],

    'session' => [
        'name' => (string) Env::get('SESSION_NAME', 'taskflow_session'),
        'lifetime' => (int) Env::get('SESSION_LIFETIME', 7200),
        'secure' => (string) Env::get('SESSION_SECURE', 'auto'),
    ],

    'security' => [
        'login_max_attempts' => (int) Env::get('LOGIN_MAX_ATTEMPTS', 5),
        'login_lockout_seconds' => (int) Env::get('LOGIN_LOCKOUT_SECONDS', 900),
    ],

    'pagination' => [
        'per_page' => (int) Env::get('TASKS_PER_PAGE', 8),
    ],
];
