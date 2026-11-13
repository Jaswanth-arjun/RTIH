<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class User
{
    /**
     * @return array<string, mixed>|null
     */
    public static function findByEmail(string $email): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT id, name, email, password_hash, role, is_active, created_at
             FROM users
             WHERE email = :email
             LIMIT 1'
        );
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        return $user === false ? null : $user;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function findPublicById(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT id, name, email, role, is_active, created_at
             FROM users
             WHERE id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch();

        return $user === false ? null : $user;
    }

    public static function emailExists(string $email): bool
    {
        $stmt = Database::connection()->prepare(
            'SELECT 1 FROM users WHERE email = :email LIMIT 1'
        );
        $stmt->execute([':email' => $email]);

        return $stmt->fetchColumn() !== false;
    }

    public static function create(string $name, string $email, string $passwordHash): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO users (name, email, password_hash, role, is_active)
             VALUES (:name, :email, :password_hash, :role, 1)'
        );
        $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':password_hash' => $passwordHash,
            ':role' => 'user',
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function updatePasswordHash(int $id, string $passwordHash): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE users SET password_hash = :password_hash WHERE id = :id'
        );
        $stmt->execute([':password_hash' => $passwordHash, ':id' => $id]);
    }

    public static function setActive(int $id, bool $active): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE users SET is_active = :active WHERE id = :id'
        );
        $stmt->execute([':active' => $active ? 1 : 0, ':id' => $id]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function allWithTaskCounts(): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT u.id, u.name, u.email, u.role, u.is_active, u.created_at,
                    COUNT(t.id) AS task_count,
                    COALESCE(SUM(t.status = \'completed\'), 0) AS completed_count
             FROM users u
             LEFT JOIN tasks t ON t.user_id = u.id
             GROUP BY u.id, u.name, u.email, u.role, u.is_active, u.created_at
             ORDER BY u.created_at ASC, u.id ASC'
        );
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function count(): int
    {
        $stmt = Database::connection()->query('SELECT COUNT(*) FROM users');

        return (int) $stmt->fetchColumn();
    }
}
