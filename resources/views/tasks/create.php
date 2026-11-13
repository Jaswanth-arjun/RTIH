<?php $errors = is_array($errors ?? null) ? $errors : []; ?>
<div class="page-heading">
    <div>
        <p class="eyebrow">New task</p>
        <h1>Create a task</h1>
        <p class="page-heading__subtitle">Give your task a clear title, then add the details you need to get it done.</p>
    </div>
    <a class="btn btn--ghost" href="<?= e(url('/tasks')) ?>">Back to tasks</a>
</div>

<section class="panel form-panel">
    <?php
    $action = url('/tasks');
    $submitLabel = 'Create task';
    $cancelUrl = url('/tasks');
    require BASE_PATH . '/resources/views/partials/task-form.php';
    ?>
</section>
