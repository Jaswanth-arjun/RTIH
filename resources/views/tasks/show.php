<?php
$statusClass = task_status_class($task['status']);
$priorityClass = task_priority_class($task['priority']);
$dueState = due_date_state($task['due_date']);
?>
<nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="<?= e(url('/tasks')) ?>">My tasks</a>
    <span aria-hidden="true">/</span>
    <span aria-current="page">Task #<?= (int) $task['id'] ?></span>
</nav>

<article class="panel task-detail">
    <header class="task-detail__header">
        <div class="badge-row">
            <span class="badge badge--status-<?= e($statusClass) ?>"><?= e(task_status_label($task['status'])) ?></span>
            <span class="badge badge--priority-<?= e($priorityClass) ?>"><?= e(task_priority_label($task['priority'])) ?> priority</span>
            <?php if ($dueState === 'overdue' && $task['status'] !== 'completed'): ?>
                <span class="badge badge--overdue">Overdue</span>
            <?php endif; ?>
        </div>
        <h1><?= e($task['title']) ?></h1>
        <p class="task-detail__created">Created <?= e(format_datetime($task['created_at'])) ?> · Last updated <?= e(format_datetime($task['updated_at'])) ?></p>
    </header>

    <div class="task-detail__body">
        <h2>Description</h2>
        <?php if (($task['description'] ?? '') !== ''): ?>
            <div class="prose"><?= nl2br(e($task['description'])) ?></div>
        <?php else: ?>
            <p class="muted">No description was provided for this task.</p>
        <?php endif; ?>
    </div>

    <dl class="detail-grid">
        <div class="detail-grid__item">
            <dt>Status</dt>
            <dd><span class="badge badge--status-<?= e($statusClass) ?>"><?= e(task_status_label($task['status'])) ?></span></dd>
        </div>
        <div class="detail-grid__item">
            <dt>Priority</dt>
            <dd><span class="badge badge--priority-<?= e($priorityClass) ?>"><?= e(task_priority_label($task['priority'])) ?></span></dd>
        </div>
        <div class="detail-grid__item">
            <dt>Due date</dt>
            <dd>
                <?php if (($task['due_date'] ?? '') !== ''): ?>
                    <span class="meta-item meta-item--due meta-item--<?= e($dueState) ?>"><?= e(due_date_label($task['due_date'])) ?></span>
                    <span class="muted">(<?= e(format_date($task['due_date'])) ?>)</span>
                <?php else: ?>
                    <span class="muted">Not set</span>
                <?php endif; ?>
            </dd>
        </div>
        <div class="detail-grid__item">
            <dt>Task ID</dt>
            <dd>#<?= (int) $task['id'] ?></dd>
        </div>
    </dl>

    <footer class="task-detail__footer">
        <div class="task-detail__actions">
            <a class="btn btn--secondary" href="<?= e(url('/tasks/' . (int) $task['id'] . '/edit')) ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M12 20h9"/>
                    <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/>
                </svg>
                Edit task
            </a>
            <a class="btn btn--danger-ghost" href="<?= e(url('/tasks/' . (int) $task['id'] . '/delete')) ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                </svg>
                Delete
            </a>
        </div>

        <form method="post" action="<?= e(url('/tasks/' . (int) $task['id'] . '/status')) ?>" class="status-form status-form--detail">
            <?= csrf_field() ?>
            <input type="hidden" name="return_to" value="<?= e('/tasks/' . (int) $task['id']) ?>">
            <label for="detail-status" class="sr-only">Change status</label>
            <select id="detail-status" name="status" class="input input--compact">
                <?php foreach (task_statuses() as $statusKey => $statusLabel): ?>
                    <option value="<?= e($statusKey) ?>" <?= $task['status'] === $statusKey ? 'selected' : '' ?>>
                        <?= e($statusLabel) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn--primary btn--sm">Update status</button>
        </form>
    </footer>
</article>

<div class="back-link-row">
    <a class="back-link" href="<?= e(url('/tasks')) ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M19 12H5M11 18l-6-6 6-6"/>
        </svg>
        Back to all tasks
    </a>
</div>
