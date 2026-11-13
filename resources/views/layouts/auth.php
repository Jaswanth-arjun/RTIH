<?php
$appName = (string) config('app.name', 'TaskFlow');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="TaskFlow is a simple, secure task tracker built with core PHP and MySQL.">
    <title><?= e($title) ?> · <?= e($appName) ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= e(asset('img/favicon.svg')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="auth-body">
<a class="skip-link" href="#main-content">Skip to main content</a>

<div class="auth-shell">
    <aside class="auth-aside">
        <a class="brand brand--light" href="<?= e(url('/')) ?>">
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

        <div class="auth-aside__copy">
            <p class="auth-aside__eyebrow">Stay focused, ship more</p>
            <h2>Every task, one calm workspace.</h2>
            <p>Create tasks, move them from pending to completed, and keep your day organised — with private, per-user workspaces.</p>
            <ul class="feature-list">
                <li>
                    <span class="feature-list__icon" aria-hidden="true">✓</span>
                    Simple create, edit and delete flow
                </li>
                <li>
                    <span class="feature-list__icon" aria-hidden="true">✓</span>
                    Status, priority, due dates, search and filters
                </li>
                <li>
                    <span class="feature-list__icon" aria-hidden="true">✓</span>
                    Secure sessions, hashed passwords and CSRF protection
                </li>
            </ul>
        </div>

        <p class="auth-aside__footnote">Built with core PHP, PDO and MySQL.</p>
        <span class="auth-aside__orb auth-aside__orb--one" aria-hidden="true"></span>
        <span class="auth-aside__orb auth-aside__orb--two" aria-hidden="true"></span>
    </aside>

    <main class="auth-main" id="main-content">
        <div class="auth-main__inner">
            <a class="auth-mobile-brand" href="<?= e(url('/')) ?>">
                <span class="brand__mark" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 12.5 9 17.5 20 6.5"/>
                    </svg>
                </span>
                <span class="brand__name"><?= e($appName) ?></span>
            </a>

            <?php require BASE_PATH . '/resources/views/partials/flash.php'; ?>
            <?= $content ?>

            <p class="auth-legal">© <?= date('Y') ?> <?= e($appName) ?>. All rights reserved.</p>
        </div>
    </main>
</div>

<script src="<?= e(asset('js/app.js')) ?>" defer></script>
</body>
</html>
