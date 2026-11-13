<?php
$fieldError = static function (string $key) use ($errors): ?string {
    $message = $errors[$key] ?? null;

    return is_string($message) && $message !== '' ? $message : null;
};
$value = static function (string $key, string $default = '') use ($old, $task): string {
    if (array_key_exists($key, $old)) {
        return (string) $old[$key];
    }

    return (string) ($task[$key] ?? $default);
};
$titleValue = $value('title');
$descriptionValue = $value('description');
$statusValue = $value('status', 'pending');
$priorityValue = $value('priority', 'medium');
$dueDateValue = $value('due_date');
?>
<form method="post" action="<?= e($action) ?>" class="form task-form" data-task-form>
    <?= csrf_field() ?>

    <div class="form-field">
        <div class="form-label-row">
            <label for="title">Title <span class="required" aria-hidden="true">*</span></label>
            <span class="form-hint">Required · up to 150 characters</span>
        </div>
        <input
            type="text"
            id="title"
            name="title"
            value="<?= e($titleValue) ?>"
            class="input<?= $fieldError('title') ? ' input--error' : '' ?>"
            required
            maxlength="150"
            autocomplete="off"
            placeholder="e.g. Prepare the quarterly report"
            <?= $fieldError('title') ? 'aria-invalid="true" aria-describedby="title-error"' : '' ?>
            autofocus
        >
        <?php if ($fieldError('title')): ?>
            <p class="form-error" id="title-error"><?= e($fieldError('title')) ?></p>
        <?php endif; ?>
    </div>

    <div class="form-field">
        <div class="form-label-row">
            <label for="description">Description</label>
            <span class="form-hint"><span data-char-count="description"><?= mb_strlen($descriptionValue) ?></span> / 5000</span>
        </div>
        <textarea
            id="description"
            name="description"
            class="input input--textarea<?= $fieldError('description') ? ' input--error' : '' ?>"
            rows="5"
            maxlength="5000"
            placeholder="Add any details, links or context that will help you finish this task."
            <?= $fieldError('description') ? 'aria-invalid="true" aria-describedby="description-error"' : '' ?>
        ><?= e($descriptionValue) ?></textarea>
        <?php if ($fieldError('description')): ?>
            <p class="form-error" id="description-error"><?= e($fieldError('description')) ?></p>
        <?php endif; ?>
    </div>

    <div class="form-grid">
        <div class="form-field">
            <label for="status">Status <span class="required" aria-hidden="true">*</span></label>
            <select id="status" name="status" class="input" required>
                <?php foreach (task_statuses() as $statusKey => $statusLabel): ?>
                    <option value="<?= e($statusKey) ?>" <?= $statusValue === $statusKey ? 'selected' : '' ?>>
                        <?= e($statusLabel) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if ($fieldError('status')): ?>
                <p class="form-error"><?= e($fieldError('status')) ?></p>
            <?php endif; ?>
        </div>

        <div class="form-field">
            <label for="priority">Priority <span class="required" aria-hidden="true">*</span></label>
            <select id="priority" name="priority" class="input" required>
                <?php foreach (task_priorities() as $priorityKey => $priorityLabel): ?>
                    <option value="<?= e($priorityKey) ?>" <?= $priorityValue === $priorityKey ? 'selected' : '' ?>>
                        <?= e($priorityLabel) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if ($fieldError('priority')): ?>
                <p class="form-error"><?= e($fieldError('priority')) ?></p>
            <?php endif; ?>
        </div>

        <div class="form-field">
            <label for="due_date">Due date</label>
            <input
                type="date"
                id="due_date"
                name="due_date"
                value="<?= e($dueDateValue) ?>"
                class="input<?= $fieldError('due_date') ? ' input--error' : '' ?>"
                <?= $fieldError('due_date') ? 'aria-invalid="true" aria-describedby="due-date-error"' : '' ?>
            >
            <?php if ($fieldError('due_date')): ?>
                <p class="form-error" id="due-date-error"><?= e($fieldError('due_date')) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <div class="form-actions">
        <a class="btn btn--ghost" href="<?= e($cancelUrl) ?>">Cancel</a>
        <button type="submit" class="btn btn--primary"><?= e($submitLabel) ?></button>
    </div>
</form>
