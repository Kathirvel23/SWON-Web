<?php
/** @var string $phone */
/** @var array<string, mixed>|null $dev */
/** @var array<string, string> $errors */
$errors = $errors ?? [];
?>
<h1>Enter your code</h1>
<p class="auth-sub">
    <?php if ($dev !== null): ?>
        We generated a test code for <?= e(mask_phone($phone)) ?>. It is shown below.
    <?php else: ?>
        We sent a 6-digit code to <?= e(mask_phone($phone)) ?>.
    <?php endif; ?>
</p>

<?php if ($dev !== null): ?>
    <div class="otp-notice" role="status" aria-live="polite">
        <span class="otp-notice-icon" aria-hidden="true"><?= icon('bell') ?></span>
        <div class="otp-notice-body">
            <div class="otp-notice-head">
                <strong>SWON verification code</strong>
                <span class="otp-notice-tag">Test mode</span>
            </div>
            <p>No SMS was sent. Use this code to continue.</p>
            <div class="otp-notice-code-row">
                <span class="otp-notice-code"><?= e($dev['code']) ?></span>
                <button type="button" class="btn-sw btn-sm btn-soft" data-fill-otp="<?= e($dev['code']) ?>">Fill code</button>
            </div>
            <span class="tiny muted">For testing only — replace with SMS before public launch.</span>
        </div>
    </div>
<?php endif; ?>

<form method="post" action="<?= url('/otp') ?>" data-single-submit class="mt-3">
    <?= csrf_field() ?>

    <div class="field">
        <label class="label" for="code">Verification code</label>
        <input
            class="input otp-input <?= isset($errors['code']) ? 'is-invalid' : '' ?>"
            id="code"
            name="code"
            type="text"
            inputmode="numeric"
            autocomplete="one-time-code"
            maxlength="6"
            placeholder=""
            data-otp
            <?= isset($errors['code']) ? 'aria-invalid="true" aria-describedby="code-error"' : '' ?>
            required>
        <?php if (isset($errors['code'])): ?>
            <span class="field-error" id="code-error"><?= e($errors['code']) ?></span>
        <?php endif; ?>
    </div>

    <button type="submit" class="btn-sw btn-block btn-lg-sw mt-3" data-busy="Verifying…">
        Verify and continue <?= icon('arrow') ?>
    </button>
</form>

<div class="row-between mt-3">
    <form method="post" action="<?= url('/otp/resend') ?>">
        <?= csrf_field() ?>
        <button type="submit" class="btn-sw btn-ghost btn-sm" data-resend data-cooldown="60">
            <span data-resend-label>Resend code</span>
        </button>
    </form>
    <a href="<?= url('/login') ?>" class="tiny">Change number</a>
</div>

<p class="tiny muted mt-3 mb-0">Codes expire after 2 minutes. After 5 incorrect attempts you will need a new code.</p>
