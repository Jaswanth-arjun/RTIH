<?php $errors = is_array($errors ?? null) ? $errors : []; ?>
<div class="page-heading">
    <div>
        <p class="eyebrow">Edit task</p>
        <h1>Edit task</h1>
        <p class="page-heading__subtitle">Update the title, description, status, priority or due date.</p>
    </div>
    <a class="btn btn--ghost" href="<?= e(url('/tasks/' . (int) $task['id'])) ?>">Back to task</a>
</div>

<section class="panel form-panel">
    <?php
    $action = url('/tasks/' . (int) $task['id']);
    $submitLabel = 'Save changes';
    $cancelUrl = url('/tasks/' . (int) $task['id']);
    require BASE_PATH . '/resources/views/partials/task-form.php';
    ?>
</section>
