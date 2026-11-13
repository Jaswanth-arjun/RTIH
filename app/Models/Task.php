<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class Task
{
    /**
     * @param array{title: string, description: ?string, status: string, priority: string, due_date: ?string} $data
     */
    public static function create(int $userId, array $data): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO tasks (user_id, title, description, status, priority, due_date)
             VALUES (:user_id, :title, :description, :status, :priority, :due_date)'
        );
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':title', $data['title']);
        $stmt->bindValue(':description', $data['description'], $data['description'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':status', $data['status']);
        $stmt->bindValue(':priority', $data['priority']);
        $stmt->bindValue(':due_date', $data['due_date'], $data['due_date'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->execute();

        return (int) Database::connection()->lastInsertId();
    }

    /**
     * Ownership is part of the query so a user can never load somebody else's task.
     *
     * @return array<string, mixed>|null
     */
    public static function findForUser(int $id, int $userId): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT id, user_id, title, description, status, priority, due_date, created_at, updated_at
             FROM tasks
             WHERE id = :id AND user_id = :user_id
             LIMIT 1'
        );
        $stmt->execute([':id' => $id, ':user_id' => $userId]);
        $task = $stmt->fetch();

        return $task === false ? null : $task;
    }

    /**
     * @param array{search?: string, status?: string, sort?: string, page?: int, per_page?: int} $filters
     * @return array{items: list<array<string, mixed>>, total: int, page: int, per_page: int, pages: int, from: int, to: int}
     */
    public static function paginateForUser(int $userId, array $filters): array
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $status = (string) ($filters['status'] ?? '');
        $sort = (string) ($filters['sort'] ?? 'newest');
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = max(1, min(50, (int) ($filters['per_page'] ?? 8)));

        $where = ['user_id = :user_id'];
        $params = [':user_id' => $userId];

        if ($search !== '') {
            // Escape LIKE wildcards so the search is literal, then use a prepared statement.
            $escaped = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search);
            $where[] = "title LIKE :search ESCAPE '!'";
            $params[':search'] = '%' . $escaped . '%';
        }

        if (array_key_exists($status, task_statuses())) {
            $where[] = 'status = :status';
            $params[':status'] = $status;
        }

        $whereSql = implode(' AND ', $where);

        $countStmt = Database::connection()->prepare(
            'SELECT COUNT(*) FROM tasks WHERE ' . $whereSql
        );
        foreach ($params as $key => $value) {
            $countStmt->bindValue($key, $value);
        }
        $countStmt->execute();
        $total = (int) $countStmt->fetchColumn();

        $pages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $pages);
        $offset = ($page - 1) * $perPage;

        $orderBy = self::orderBy($sort);

        $stmt = Database::connection()->prepare(
            'SELECT id, user_id, title, description, status, priority, due_date, created_at, updated_at
             FROM tasks
             WHERE ' . $whereSql . '
             ORDER BY ' . $orderBy . '
             LIMIT :limit OFFSET :offset'
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'pages' => $pages,
            'from' => $total === 0 ? 0 : $offset + 1,
            'to' => min($total, $offset + count($items)),
        ];
    }

    /**
     * @return array{total: int, pending: int, in_progress: int, completed: int, overdue: int}
     */
    public static function statsForUser(int $userId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT COUNT(*) AS total,
                    COALESCE(SUM(status = \'pending\'), 0) AS pending,
                    COALESCE(SUM(status = \'in_progress\'), 0) AS in_progress,
                    COALESCE(SUM(status = \'completed\'), 0) AS completed,
                    COALESCE(SUM(status <> \'completed\' AND due_date IS NOT NULL AND due_date < CURDATE()), 0) AS overdue
             FROM tasks
             WHERE user_id = :user_id'
        );
        $stmt->execute([':user_id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'total' => (int) ($row['total'] ?? 0),
            'pending' => (int) ($row['pending'] ?? 0),
            'in_progress' => (int) ($row['in_progress'] ?? 0),
            'completed' => (int) ($row['completed'] ?? 0),
            'overdue' => (int) ($row['overdue'] ?? 0),
        ];
    }

    /**
     * @param array{title: string, description: ?string, status: string, priority: string, due_date: ?string} $data
     */
    public static function updateForUser(int $id, int $userId, array $data): bool
    {
        $stmt = Database::connection()->prepare(
            'UPDATE tasks
             SET title = :title,
                 description = :description,
                 status = :status,
                 priority = :priority,
                 due_date = :due_date
             WHERE id = :id AND user_id = :user_id'
        );
        $stmt->bindValue(':title', $data['title']);
        $stmt->bindValue(':description', $data['description'], $data['description'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':status', $data['status']);
        $stmt->bindValue(':priority', $data['priority']);
        $stmt->bindValue(':due_date', $data['due_date'], $data['due_date'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    public static function updateStatusForUser(int $id, int $userId, string $status): bool
    {
        $stmt = Database::connection()->prepare(
            'UPDATE tasks SET status = :status WHERE id = :id AND user_id = :user_id'
        );
        $stmt->execute([
            ':status' => $status,
            ':id' => $id,
            ':user_id' => $userId,
        ]);

        return $stmt->rowCount() > 0;
    }

    public static function deleteForUser(int $id, int $userId): bool
    {
        $stmt = Database::connection()->prepare(
            'DELETE FROM tasks WHERE id = :id AND user_id = :user_id'
        );
        $stmt->execute([':id' => $id, ':user_id' => $userId]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Sorting is limited to a fixed allowlist; no user input reaches the SQL text.
     */
    private static function orderBy(string $sort): string
    {
        return match ($sort) {
            'oldest' => 'created_at ASC, id ASC',
            'title' => 'title ASC, id ASC',
            'due_date' => 'due_date IS NULL, due_date ASC, created_at DESC',
            'priority' => "FIELD(priority, 'high', 'medium', 'low'), created_at DESC",
            default => 'created_at DESC, id DESC',
        };
    }
}
