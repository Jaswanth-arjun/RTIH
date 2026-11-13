<?php
$isLoggedIn = App\Core\Auth::check();
$primaryUrl = $isLoggedIn ? url('/tasks') : url('/login');
$primaryLabel = $isLoggedIn ? 'Back to my tasks' : 'Go to sign in';
?>
<section class="error-card">
    <p class="error-card__code" aria-hidden="true"><?= (int) $code ?></p>
    <h1><?= e($title) ?></h1>
    <p class="error-card__message"><?= e($message) ?></p>
    <div class="error-card__actions">
        <a class="btn btn--primary" href="<?= e($primaryUrl) ?>"><?= e($primaryLabel) ?></a>
        <button type="button" class="btn btn--ghost" data-history-back>Go back</button>
    </div>
</section>
