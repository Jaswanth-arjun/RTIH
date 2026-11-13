<?php
$appName = (string) config('app.name', 'TaskFlow');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title><?= e($title) ?> · <?= e($appName) ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= e(asset('img/favicon.svg')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="minimal-body">
<a class="skip-link" href="#main-content">Skip to main content</a>

<main class="minimal-shell" id="main-content">
    <a class="brand brand--centered" href="<?= e(url('/')) ?>">
        <span class="brand__mark" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M4 12.5 9 17.5 20 6.5"/>
            </svg>
        </span>
        <span class="brand__text">
            <span class="brand__name"><?= e($appName) ?></span>
            <span class="brand__tagline">Task tracker</span>
        </span>
    </a>

    <?php require BASE_PATH . '/resources/views/partials/flash.php'; ?>
    <?= $content ?>
</main>

<script src="<?= e(asset('js/app.js')) ?>" defer></script>
</body>
</html>
