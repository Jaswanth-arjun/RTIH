<?php
$errors = is_array($errors ?? null) ? $errors : [];
$old = is_array($old ?? null) ? $old : [];
$emailValue = (string) ($old['email'] ?? '');
?>
<section class="auth-card">
    <header class="auth-card__header">
        <p class="eyebrow">Welcome back</p>
        <h1><?= e($heading) ?></h1>
        <p>Sign in to pick up where you left off.</p>
    </header>

    <form method="post" action="<?= e(url('/login')) ?>" class="form">
        <?= csrf_field() ?>

        <div class="form-field">
            <label for="email">Email address</label>
            <div class="input-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <rect x="3" y="5" width="18" height="14" rx="3"/>
                    <path d="m4 7 8 6 8-6"/>
                </svg>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?= e($emailValue) ?>"
                    class="input<?= isset($errors['email']) ? ' input--error' : '' ?>"
                    required
                    maxlength="190"
                    autocomplete="email"
                    placeholder="you@example.com"
                    <?= isset($errors['email']) ? 'aria-invalid="true" aria-describedby="email-error"' : '' ?>
                    autofocus
                >
            </div>
            <?php if (isset($errors['email'])): ?>
                <p class="form-error" id="email-error"><?= e($errors['email']) ?></p>
            <?php endif; ?>
        </div>

        <div class="form-field">
            <div class="form-label-row">
                <label for="password">Password</label>
                <span class="form-hint">Minimum 8 characters</span>
            </div>
            <div class="input-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <rect x="4" y="10" width="16" height="11" rx="3"/>
                    <path d="M8 10V7a4 4 0 0 1 8 0v3"/>
                </svg>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="input<?= isset($errors['password']) ? ' input--error' : '' ?>"
                    required
                    maxlength="72"
                    autocomplete="current-password"
                    placeholder="Enter your password"
                    <?= isset($errors['password']) ? 'aria-invalid="true" aria-describedby="password-error"' : '' ?>
                >
            </div>
            <?php if (isset($errors['password'])): ?>
                <p class="form-error" id="password-error"><?= e($errors['password']) ?></p>
            <?php endif; ?>
        </div>

        <button type="submit" class="btn btn--primary btn--block">
            Sign in
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M5 12h14M13 6l6 6-6 6"/>
            </svg>
        </button>
    </form>

    <?php if (config('app.environment') === 'local'): ?>
        <div class="demo-box">
            <p class="demo-box__title">Demo accounts</p>
            <p><strong>Member:</strong> demo@taskflow.test / Demo@12345</p>
            <p><strong>Admin:</strong> admin@taskflow.test / Admin@12345</p>
        </div>
    <?php endif; ?>

    <p class="auth-switch">
        New to TaskFlow?
        <a href="<?= e(url('/register')) ?>">Create an account</a>
    </p>
</section>
