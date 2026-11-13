<?php
$errors = is_array($errors ?? null) ? $errors : [];
$old = is_array($old ?? null) ? $old : [];
$nameValue = (string) ($old['name'] ?? '');
$emailValue = (string) ($old['email'] ?? '');
?>
<section class="auth-card">
    <header class="auth-card__header">
        <p class="eyebrow">Get started</p>
        <h1><?= e($heading) ?></h1>
        <p>Create your private workspace in a few seconds.</p>
    </header>

    <form method="post" action="<?= e(url('/register')) ?>" class="form">
        <?= csrf_field() ?>

        <div class="form-field">
            <label for="name">Full name</label>
            <input
                type="text"
                id="name"
                name="name"
                value="<?= e($nameValue) ?>"
                class="input<?= isset($errors['name']) ? ' input--error' : '' ?>"
                required
                minlength="2"
                maxlength="100"
                autocomplete="name"
                placeholder="Jane Doe"
                <?= isset($errors['name']) ? 'aria-invalid="true" aria-describedby="name-error"' : '' ?>
                autofocus
            >
            <?php if (isset($errors['name'])): ?>
                <p class="form-error" id="name-error"><?= e($errors['name']) ?></p>
            <?php endif; ?>
        </div>

        <div class="form-field">
            <label for="email">Email address</label>
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
            >
            <?php if (isset($errors['email'])): ?>
                <p class="form-error" id="email-error"><?= e($errors['email']) ?></p>
            <?php endif; ?>
        </div>

        <div class="form-grid">
            <div class="form-field">
                <label for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="input<?= isset($errors['password']) ? ' input--error' : '' ?>"
                    required
                    minlength="8"
                    maxlength="72"
                    autocomplete="new-password"
                    placeholder="At least 8 characters"
                    <?= isset($errors['password']) ? 'aria-invalid="true" aria-describedby="password-error"' : '' ?>
                >
                <?php if (isset($errors['password'])): ?>
                    <p class="form-error" id="password-error"><?= e($errors['password']) ?></p>
                <?php endif; ?>
            </div>

            <div class="form-field">
                <label for="password_confirmation">Confirm password</label>
                <input
                    type="password"
                    id="password_confirmation"
                    name="password_confirmation"
                    class="input<?= isset($errors['password_confirmation']) ? ' input--error' : '' ?>"
                    required
                    minlength="8"
                    maxlength="72"
                    autocomplete="new-password"
                    placeholder="Repeat password"
                    <?= isset($errors['password_confirmation']) ? 'aria-invalid="true" aria-describedby="password-confirmation-error"' : '' ?>
                >
                <?php if (isset($errors['password_confirmation'])): ?>
                    <p class="form-error" id="password-confirmation-error"><?= e($errors['password_confirmation']) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <button type="submit" class="btn btn--primary btn--block">
            Create account
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M5 12h14M13 6l6 6-6 6"/>
            </svg>
        </button>
    </form>

    <p class="auth-switch">
        Already have an account?
        <a href="<?= e(url('/login')) ?>">Sign in</a>
    </p>
</section>
