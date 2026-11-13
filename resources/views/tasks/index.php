<?php
$baseQuery = [
    'q' => $filters['q'],
    'status' => $filters['status'],
    'sort' => $filters['sort'],
];
$currentPath = App\Core\Request::fullPath();
$hasFilters = $filters['q'] !== '' || $filters['status'] !== '';
$pages = (int) $tasks['pages'];
$currentPage = (int) $tasks['page'];
?>
<div class="page-heading">
    <div>
        <p class="eyebrow">Dashboard</p>
        <h1>My tasks</h1>
        <p class="page-heading__subtitle">Track what matters, move work forward and keep your day focused.</p>
    </div>
    <a class="btn btn--primary" href="<?= e(url('/tasks/create')) ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
            <path d="M12 5v14M5 12h14"/>
        </svg>
        New task
    </a>
</div>

<section class="stats-grid" aria-label="Task summary">
    <article class="stat-card stat-card--total">
        <span class="stat-card__icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" aria-hidden="true">
                <rect x="3" y="4" width="18" height="16" rx="3"/>
                <path d="M8 9h8M8 13h8M8 17h5"/>
            </svg>
        </span>
        <span class="stat-card__body">
            <span class="stat-card__label">Total tasks</span>
            <span class="stat-card__value" data-testid="stat-total"><?= (int) $stats['total'] ?></span>
        </span>
    </article>
    <article class="stat-card stat-card--pending">
        <span class="stat-card__icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" aria-hidden="true">
                <circle cx="12" cy="12" r="9"/>
                <path d="M12 7v5l3 2"/>
            </svg>
        </span>
        <span class="stat-card__body">
            <span class="stat-card__label">Pending</span>
            <span class="stat-card__value" data-testid="stat-pending"><?= (int) $stats['pending'] ?></span>
        </span>
    </article>
    <article class="stat-card stat-card--progress">
        <span class="stat-card__icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" aria-hidden="true">
                <path d="M21 12a9 9 0 1 1-3-6.7"/>
                <path d="M21 3v6h-6"/>
            </svg>
        </span>
        <span class="stat-card__body">
            <span class="stat-card__label">In progress</span>
            <span class="stat-card__value" data-testid="stat-progress"><?= (int) $stats['in_progress'] ?></span>
        </span>
    </article>
    <article class="stat-card stat-card--completed">
        <span class="stat-card__icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="12" cy="12" r="9"/>
                <path d="m8.5 12 2.5 2.5 4.5-5"/>
            </svg>
        </span>
        <span class="stat-card__body">
            <span class="stat-card__label">Completed</span>
            <span class="stat-card__value" data-testid="stat-completed"><?= (int) $stats['completed'] ?></span>
        </span>
    </article>
</section>

<?php if ((int) $stats['overdue'] > 0): ?>
    <div class="notice notice--warning">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/>
            <path d="M12 9v4M12 17h.01"/>
        </svg>
        <p>
            You have <strong><?= (int) $stats['overdue'] ?></strong>
            overdue <?= (int) $stats['overdue'] === 1 ? 'task' : 'tasks' ?>.
            <a href="<?= e(url_with_query('/tasks', array_merge($baseQuery, ['sort' => 'due_date']))) ?>">Review by due date</a>
        </p>
    </div>
<?php endif; ?>

<section class="panel filters-panel" aria-label="Task filters">
    <form method="get" action="<?= e(url('/tasks')) ?>" class="filters" role="search">
        <div class="search-field">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" aria-hidden="true">
                <circle cx="11" cy="11" r="7"/>
                <path d="m20 20-3.5-3.5"/>
            </svg>
            <label class="sr-only" for="q">Search tasks by title</label>
            <input
                type="search"
                id="q"
                name="q"
                value="<?= e($filters['q']) ?>"
                class="input"
                placeholder="Search tasks by title…"
                maxlength="150"
                autocomplete="off"
            >
        </div>

        <div class="filter-group">
            <label class="sr-only" for="status-filter">Filter by status</label>
            <select id="status-filter" name="status" class="input">
                <option value="">All statuses</option>
                <?php foreach (task_statuses() as $statusKey => $statusLabel): ?>
                    <option value="<?= e($statusKey) ?>" <?= $filters['status'] === $statusKey ? 'selected' : '' ?>>
                        <?= e($statusLabel) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label class="sr-only" for="sort-filter">Sort tasks</label>
            <select id="sort-filter" name="sort" class="input">
                <option value="newest" <?= $filters['sort'] === 'newest' ? 'selected' : '' ?>>Newest first</option>
                <option value="oldest" <?= $filters['sort'] === 'oldest' ? 'selected' : '' ?>>Oldest first</option>
                <option value="title" <?= $filters['sort'] === 'title' ? 'selected' : '' ?>>Title A–Z</option>
                <option value="due_date" <?= $filters['sort'] === 'due_date' ? 'selected' : '' ?>>Due date</option>
                <option value="priority" <?= $filters['sort'] === 'priority' ? 'selected' : '' ?>>Priority</option>
            </select>
        </div>

        <div class="filters__actions">
            <button type="submit" class="btn btn--secondary">Apply filters</button>
            <?php if ($hasFilters || $filters['sort'] !== 'newest'): ?>
                <a class="btn btn--ghost" href="<?= e(url('/tasks')) ?>">Reset</a>
            <?php endif; ?>
        </div>
    </form>
</section>

<div class="results-bar">
    <p class="results-bar__count">
        <?php if ((int) $tasks['total'] === 0): ?>
            No tasks found
        <?php else: ?>
            Showing <strong><?= (int) $tasks['from'] ?>–<?= (int) $tasks['to'] ?></strong>
            of <strong><?= (int) $tasks['total'] ?></strong> <?= (int) $tasks['total'] === 1 ? 'task' : 'tasks' ?>
        <?php endif; ?>
        <?php if ($filters['q'] !== ''): ?>
            for “<span class="results-bar__term"><?= e($filters['q']) ?></span>”
        <?php endif; ?>
    </p>
    <?php if ($filters['status'] !== ''): ?>
        <span class="chip">
            Status: <?= e(task_status_label($filters['status'])) ?>
            <a href="<?= e(url_with_query('/tasks', array_merge($baseQuery, ['status' => '']))) ?>" aria-label="Clear status filter">×</a>
        </span>
    <?php endif; ?>
</div>

<?php if ($tasks['items'] === []): ?>
    <section class="empty-state">
        <span class="empty-state__icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 11.5 11.5 14 15.5 10"/>
                <rect x="3" y="4" width="18" height="16" rx="3"/>
                <path d="M8 20v2M16 20v2"/>
            </svg>
        </span>
        <?php if ($hasFilters): ?>
            <h2>No tasks match those filters</h2>
            <p>Try a different search term or status, or clear the filters to see all of your tasks.</p>
            <a class="btn btn--secondary" href="<?= e(url('/tasks')) ?>">Clear filters</a>
        <?php else: ?>
            <h2>Your task list is empty</h2>
            <p>Create your first task to start tracking your work. It only takes a few seconds.</p>
            <a class="btn btn--primary" href="<?= e(url('/tasks/create')) ?>">Create your first task</a>
        <?php endif; ?>
    </section>
<?php else: ?>
    <section class="task-list" aria-label="Tasks" data-testid="task-list">
        <?php foreach ($tasks['items'] as $task): ?>
            <?php
            $statusClass = task_status_class($task['status']);
            $priorityClass = task_priority_class($task['priority']);
            $dueState = due_date_state($task['due_date']);
            ?>
            <article class="task-card task-card--<?= e($statusClass) ?>" data-task-id="<?= (int) $task['id'] ?>">
                <div class="task-card__main">
                    <div class="task-card__top">
                        <a class="task-card__title" href="<?= e(url('/tasks/' . (int) $task['id'])) ?>">
                            <?= e($task['title']) ?>
                        </a>
                        <div class="badge-row">
                            <span class="badge badge--status-<?= e($statusClass) ?>"><?= e(task_status_label($task['status'])) ?></span>
                            <span class="badge badge--priority-<?= e($priorityClass) ?>"><?= e(task_priority_label($task['priority'])) ?> priority</span>
                        </div>
                    </div>

                    <?php if (($task['description'] ?? '') !== ''): ?>
                        <p class="task-card__description"><?= e(excerpt($task['description'])) ?></p>
                    <?php else: ?>
                        <p class="task-card__description task-card__description--empty">No description provided.</p>
                    <?php endif; ?>

                    <div class="task-card__meta">
                        <span class="meta-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true">
                                <rect x="3" y="5" width="18" height="16" rx="3"/>
                                <path d="M8 3v4M16 3v4M3 10h18"/>
                            </svg>
                            Created <?= e(format_datetime($task['created_at'])) ?>
                        </span>
                        <?php if (($task['due_date'] ?? '') !== ''): ?>
                            <span class="meta-item meta-item--due meta-item--<?= e($dueState) ?>">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true">
                                    <circle cx="12" cy="12" r="9"/>
                                    <path d="M12 7v5l3 2"/>
                                </svg>
                                <?= e(due_date_label($task['due_date'])) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="task-card__side">
                    <form method="post" action="<?= e(url('/tasks/' . (int) $task['id'] . '/status')) ?>" class="status-form">
                        <?= csrf_field() ?>
                        <input type="hidden" name="return_to" value="<?= e($currentPath) ?>">
                        <label class="sr-only" for="status-<?= (int) $task['id'] ?>">Change status for <?= e($task['title']) ?></label>
                        <select id="status-<?= (int) $task['id'] ?>" name="status" class="input input--compact">
                            <?php foreach (task_statuses() as $statusKey => $statusLabel): ?>
                                <option value="<?= e($statusKey) ?>" <?= $task['status'] === $statusKey ? 'selected' : '' ?>>
                                    <?= e($statusLabel) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn--secondary btn--sm">Update</button>
                    </form>

                    <div class="task-card__actions">
                        <a href="<?= e(url('/tasks/' . (int) $task['id'])) ?>" class="link-action">View</a>
                        <a href="<?= e(url('/tasks/' . (int) $task['id'] . '/edit')) ?>" class="link-action">Edit</a>
                        <a href="<?= e(url('/tasks/' . (int) $task['id'] . '/delete')) ?>" class="link-action link-action--danger">Delete</a>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </section>

    <?php if ($pages > 1): ?>
        <nav class="pagination" aria-label="Task pages">
            <?php if ($currentPage > 1): ?>
                <a class="pagination__item pagination__item--nav" href="<?= e(url_with_query('/tasks', array_merge($baseQuery, ['page' => $currentPage - 1]))) ?>" rel="prev">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="m15 18-6-6 6-6"/>
                    </svg>
                    <span>Previous</span>
                </a>
            <?php else: ?>
                <span class="pagination__item pagination__item--nav is-disabled" aria-disabled="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="m15 18-6-6 6-6"/>
                    </svg>
                    <span>Previous</span>
                </span>
            <?php endif; ?>

            <div class="pagination__pages">
                <?php $lastWasEllipsis = false; ?>
                <?php for ($page = 1; $page <= $pages; $page++): ?>
                    <?php
                    $isEdge = $page === 1 || $page === $pages;
                    $isNear = abs($page - $currentPage) <= 1;
                    ?>
                    <?php if ($isEdge || $isNear): ?>
                        <?php $lastWasEllipsis = false; ?>
                        <?php if ($page === $currentPage): ?>
                            <span class="pagination__item is-current" aria-current="page"><?= $page ?></span>
                        <?php else: ?>
                            <a class="pagination__item" href="<?= e(url_with_query('/tasks', array_merge($baseQuery, ['page' => $page]))) ?>"><?= $page ?></a>
                        <?php endif; ?>
                    <?php elseif (!$lastWasEllipsis): ?>
                        <?php $lastWasEllipsis = true; ?>
                        <span class="pagination__ellipsis" aria-hidden="true">…</span>
                    <?php endif; ?>
                <?php endfor; ?>
            </div>

            <?php if ($currentPage < $pages): ?>
                <a class="pagination__item pagination__item--nav" href="<?= e(url_with_query('/tasks', array_merge($baseQuery, ['page' => $currentPage + 1]))) ?>" rel="next">
                    <span>Next</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="m9 18 6-6-6-6"/>
                    </svg>
                </a>
            <?php else: ?>
                <span class="pagination__item pagination__item--nav is-disabled" aria-disabled="true">
                    <span>Next</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="m9 18 6-6-6-6"/>
                    </svg>
                </span>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
<?php endif; ?>
