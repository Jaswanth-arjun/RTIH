<?php
$currentUser = App\Core\Auth::user();
$initials = '?';
if (is_array($currentUser)) {
    $initials = mb_strtoupper(mb_substr((string) $currentUser['name'], 0, 1));
}
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
<body class="app-body">
<a class="skip-link" href="#main-content">Skip to main content</a>

<div class="app-shell">
    <aside class="sidebar" id="sidebar" aria-label="Sidebar">
        <a class="brand" href="<?= e(url('/tasks')) ?>">
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

        <nav class="sidebar__nav" aria-label="Main navigation">
            <p class="sidebar__label">Workspace</p>
            <a class="nav-link <?= e(nav_active('/tasks')) ?>" href="<?= e(url('/tasks')) ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <rect x="3" y="4" width="18" height="16" rx="3"/>
                    <path d="M8 9h8M8 13h8M8 17h5"/>
                </svg>
                <span>My tasks</span>
            </a>
            <a class="nav-link <?= e(nav_active('/tasks/create')) ?>" href="<?= e(url('/tasks/create')) ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <circle cx="12" cy="12" r="9"/>
                    <path d="M12 8v8M8 12h8"/>
                </svg>
                <span>New task</span>
            </a>

            <?php if (($currentUser['role'] ?? '') === 'admin'): ?>
                <p class="sidebar__label sidebar__label--spaced">Administration</p>
                <a class="nav-link <?= e(nav_active('/admin/users')) ?>" href="<?= e(url('/admin/users')) ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                    <span>Users</span>
                </a>
            <?php endif; ?>
        </nav>

        <div class="sidebar__footer">
            <div class="user-card">
                <span class="avatar" aria-hidden="true"><?= e($initials) ?></span>
                <span class="user-card__text">
                    <span class="user-card__name"><?= e($currentUser['name'] ?? '') ?></span>
                    <span class="user-card__role"><?= e(($currentUser['role'] ?? 'user') === 'admin' ? 'Administrator' : 'Member') ?></span>
                </span>
                <form method="post" action="<?= e(url('/logout')) ?>" class="logout-form">
                    <?= csrf_field() ?>
                    <button type="submit" class="icon-button icon-button--dark" title="Sign out" aria-label="Sign out">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                            <path d="m16 17 5-5-5-5M21 12H9"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <div class="sidebar-backdrop" data-sidebar-close hidden></div>

    <div class="app-main">
        <header class="topbar">
            <button type="button" class="icon-button topbar__menu" data-sidebar-toggle aria-controls="sidebar" aria-expanded="false" aria-label="Open navigation">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                    <path d="M4 7h16M4 12h16M4 17h16"/>
                </svg>
            </button>
            <div class="topbar__titles">
                <span class="topbar__eyebrow"><?= e($appName) ?></span>
                <span class="topbar__title"><?= e($heading) ?></span>
            </div>
            <div class="topbar__actions">
                <a class="btn btn--primary btn--sm" href="<?= e(url('/tasks/create')) ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                        <path d="M12 5v14M5 12h14"/>
                    </svg>
                    New task
                </a>
            </div>
        </header>

        <main class="content" id="main-content">
            <?php require BASE_PATH . '/resources/views/partials/flash.php'; ?>
            <?= $content ?>
        </main>
    </div>
</div>

<script src="<?= e(asset('js/app.js')) ?>" defer></script>
</body>
</html>
