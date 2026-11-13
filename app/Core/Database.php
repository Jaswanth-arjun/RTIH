<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            (string) config('db.host', '127.0.0.1'),
            (int) config('db.port', 3306),
            (string) config('db.name', 'task_tracker')
        );

        self::$connection = new PDO(
            $dsn,
            (string) config('db.user', 'root'),
            (string) config('db.password', ''),
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_STRINGIFY_FETCHES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
            ]
        );

        return self::$connection;
    }

    /**
     * Used by the integration test suite to point the app at a disposable database.
     */
    public static function reset(): void
    {
        self::$connection = null;
    }
}
