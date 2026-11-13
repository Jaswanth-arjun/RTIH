<?php

declare(strict_types=1);

namespace App\Core;

final class Logger
{
    /**
     * @param array<string, mixed> $context
     */
    public static function error(string $message, array $context = []): void
    {
        $directory = BASE_PATH . '/storage/logs';
        if (!is_dir($directory)) {
            @mkdir($directory, 0775, true);
        }

        $line = '[' . date('Y-m-d H:i:s') . '] ' . $message;
        if ($context !== []) {
            $encoded = json_encode($context, JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);
            if ($encoded !== false) {
                $line .= ' ' . $encoded;
            }
        }

        @file_put_contents($directory . '/app.log', $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}
