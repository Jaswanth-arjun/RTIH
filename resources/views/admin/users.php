<?php
$currentUserId = App\Core\Auth::id();
$initialsFor = static function (string $name): string {
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $letters = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $letters .= mb_strtoupper(mb_substr($part, 0, 1));
    }

    return $letters !== '' ? $letters : '?';
};
?>
<div class="page-heading">
    <div>
        <p class="eyebrow">Administration</p>
        <h1>User management</h1>
        <p class="page-heading__subtitle">Manage account access. Task content stays private to each owner, regardless of role.</p>
    </div>
    <a class="btn btn--ghost" href="<?= e(url('/tasks')) ?>">Back to my tasks</a>
</div>

<div class="notice notice--info">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <circle cx="12" cy="12" r="9"/>
        <path d="M12 16v-4M12 8h.01"/>
    </svg>
    <p>
        <strong>Privacy rule:</strong> every task query is scoped to its owner. Administrators can activate or
        deactivate accounts, but cannot view or change another user's task content.
    </p>
</div>

<section class="stats-grid stats-grid--three" aria-label="User summary">
    <article class="stat-card stat-card--total">
        <span class="stat-card__icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
        </span>
        <span class="stat-card__body">
            <span class="stat-card__label">Total users</span>
            <span class="stat-card__value"><?= (int) $summary['total_users'] ?></span>
        </span>
    </article>
    <article class="stat-card stat-card--completed">
        <span class="stat-card__icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M20 6 9 17l-5-5"/>
            </svg>
        </span>
        <span class="stat-card__body">
            <span class="stat-card__label">Active accounts</span>
            <span class="stat-card__value"><?= (int) $summary['active_users'] ?></span>
        </span>
    </article>
    <article class="stat-card stat-card--progress">
        <span class="stat-card__icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                <rect x="3" y="4" width="18" height="16" rx="3"/>
                <path d="M8 9h8M8 13h8M8 17h5"/>
            </svg>
        </span>
        <span class="stat-card__body">
            <span class="stat-card__label">Tasks across users</span>
            <span class="stat-card__value"><?= (int) $summary['total_tasks'] ?></span>
        </span>
    </article>
</section>

<section class="panel table-panel">
    <div class="panel__header">
        <div>
            <h2>Accounts</h2>
            <p class="muted">Signed-in users are listed below. Deactivated users lose access immediately.</p>
        </div>
        <span class="chip"><?= (int) $summary['admins'] ?> admin<?= (int) $summary['admins'] === 1 ? '' : 's' ?></span>
    </div>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th scope="col">User</th>
                    <th scope="col">Role</th>
                    <th scope="col">Status</th>
                    <th scope="col">Tasks</th>
                    <th scope="col">Completed</th>
                    <th scope="col">Joined</th>
                    <th scope="col"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <?php $isSelf = (int) $user['id'] === $currentUserId; ?>
                    <tr>
                        <td>
                            <div class="user-cell">
                                <span class="avatar avatar--small" aria-hidden="true"><?= e($initialsFor((string) $user['name'])) ?></span>
                                <span class="user-cell__text">
                                    <strong><?= e($user['name']) ?><?= $isSelf ? ' (you)' : '' ?></strong>
                                    <span><?= e($user['email']) ?></span>
                                </span>
                            </div>
                        </td>
                        <td>
                            <span class="badge badge--role-<?= e($user['role']) ?>">
                                <?= e($user['role'] === 'admin' ? 'Admin' : 'Member') ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge badge--account-<?= (int) $user['is_active'] === 1 ? 'active' : 'inactive' ?>">
                                <?= (int) $user['is_active'] === 1 ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td class="data-table__number"><?= (int) $user['task_count'] ?></td>
                        <td class="data-table__number"><?= (int) $user['completed_count'] ?></td>
                        <td><?= e(format_date($user['created_at'])) ?></td>
                        <td class="data-table__actions">
                            <?php if ($isSelf): ?>
                                <span class="muted">Current account</span>
                            <?php else: ?>
                                <form method="post" action="<?= e(url('/admin/users/' . (int) $user['id'] . '/toggle')) ?>">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn--sm <?= (int) $user['is_active'] === 1 ? 'btn--danger-ghost' : 'btn--secondary' ?>">
                                        <?= (int) $user['is_active'] === 1 ? 'Deactivate' : 'Activate' ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
