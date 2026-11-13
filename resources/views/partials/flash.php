<?php
$alertTypes = ['success', 'error', 'warning', 'info'];
$icons = [
    'success' => '<path d="M20 6 9 17l-5-5"/>',
    'error' => '<circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01"/>',
    'warning' => '<path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4M12 17h.01"/>',
    'info' => '<circle cx="12" cy="12" r="9"/><path d="M12 16v-4M12 8h.01"/>',
];
?>
<?php foreach ($alertTypes as $type): ?>
    <?php $message = App\Core\Session::getFlash($type); ?>
    <?php if (is_string($message) && $message !== ''): ?>
        <div class="alert alert--<?= e($type) ?>" role="<?= $type === 'error' ? 'alert' : 'status' ?>" data-alert>
            <svg class="alert__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <?= $icons[$type] ?>
            </svg>
            <p class="alert__text"><?= e($message) ?></p>
            <button type="button" class="alert__close" data-dismiss aria-label="Dismiss message">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                    <path d="M18 6 6 18M6 6l12 12"/>
                </svg>
            </button>
        </div>
    <?php endif; ?>
<?php endforeach; ?>
