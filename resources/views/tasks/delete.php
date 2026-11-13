<?php
$statusClass = task_status_class($task['status']);
?>
<nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="<?= e(url('/tasks')) ?>">My tasks</a>
    <span aria-hidden="true">/</span>
    <a href="<?= e(url('/tasks/' . (int) $task['id'])) ?>">Task #<?= (int) $task['id'] ?></a>
    <span aria-hidden="true">/</span>
    <span aria-current="page">Delete</span>
</nav>

<section class="panel delete-panel">
    <span class="delete-panel__icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
            <path d="M10 11v6M14 11v6"/>
        </svg>
    </span>
    <h1>Delete this task?</h1>
    <p class="delete-panel__intro">You are about to permanently delete the following task. This action cannot be undone.</p>

    <div class="delete-panel__task">
        <div class="badge-row">
            <span class="badge badge--status-<?= e($statusClass) ?>"><?= e(task_status_label($task['status'])) ?></span>
            <span class="badge badge--priority-<?= e(task_priority_class($task['priority'])) ?>"><?= e(task_priority_label($task['priority'])) ?> priority</span>
        </div>
        <h2><?= e($task['title']) ?></h2>
        <?php if (($task['description'] ?? '') !== ''): ?>
            <p class="muted"><?= e(excerpt($task['description'], 220)) ?></p>
        <?php endif; ?>
        <p class="muted">Created <?= e(format_datetime($task['created_at'])) ?></p>
    </div>

    <form method="post" action="<?= e(url('/tasks/' . (int) $task['id'] . '/delete')) ?>" class="delete-panel__actions">
        <?= csrf_field() ?>
        <a class="btn btn--ghost" href="<?= e(url('/tasks/' . (int) $task['id'])) ?>">Cancel</a>
        <button type="submit" class="btn btn--danger">Yes, delete task</button>
    </form>
</section>
