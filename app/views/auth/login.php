<?php
/** @var array<string, string> $errors */
/** @var array<string, mixed> $old */
/** @var bool $otpBypass */
$errors    = $errors ?? [];
$old       = $old ?? [];
$otpBypass = $otpBypass ?? false;
?>
<h1>Sign in to SWON</h1>
<p class="auth-sub">
    <?= $otpBypass ? 'Enter your mobile number to continue.' : 'Enter your mobile number. We will send a one-time code.' ?>
</p>

<?php if ($otpBypass): ?>
    <div class="flash flash-info mt-3">
        <?= icon('info') ?>
        <span><strong>Test mode:</strong> OTP verification is disabled. You will go straight to role selection.</span>
    </div>
<?php endif; ?>

<form method="post" action="<?= url('/login') ?>" data-single-submit class="mt-3">
    <?= csrf_field() ?>

    <div class="field">
        <label class="label" for="phone">Mobile number <span class="req">*</span></label>
        <div class="phone-input">
            <span class="phone-prefix">+91</span>
            <input
                class="input <?= isset($errors['phone']) ? 'is-invalid' : '' ?>"
                id="phone"
                name="phone"
                type="tel"
                inputmode="numeric"
                autocomplete="tel-national"
                maxlength="14"
                placeholder="98765 43210"
                value="<?= e($old['phone'] ?? '') ?>"
                aria-describedby="phone-hint<?= isset($errors['phone']) ? ' phone-error' : '' ?>"
                <?= isset($errors['phone']) ? 'aria-invalid="true"' : '' ?>
                required
                autofocus>
        </div>
        <?php if (isset($errors['phone'])): ?>
            <span class="field-error" id="phone-error"><?= e($errors['phone']) ?></span>
        <?php endif; ?>
        <span class="field-hint" id="phone-hint">Indian mobile numbers only (10 digits, starting 6–9).</span>
    </div>

    <button type="submit" class="btn-sw btn-block btn-lg-sw mt-3" data-busy="<?= $otpBypass ? 'Signing in…' : 'Sending code…' ?>">
        <?= $otpBypass ? 'Continue' : 'Send code' ?> <?= icon('arrow') ?>
    </button>
</form>

<div class="auth-divider">Demo accounts</div>

<?php if (!empty($devMode) || $otpBypass): ?>
    <div class="demo-box">
        <p class="tiny muted mb-2">
            <?= $otpBypass ? 'Test mode — no OTP is required. Click a number to fill it.' : 'Use OTP dev mode — the code appears on the next screen. Click a number to fill it.' ?>
        </p>
        <div class="demo-row">
            <span><strong>Worker</strong> · Arun Kumar</span>
            <button type="button" class="btn-link-sw demo-phone" data-fill-phone="9000000001">9000000001</button>
        </div>
        <div class="demo-row">
            <span><strong>Host</strong> · Kovai Event Works</span>
            <button type="button" class="btn-link-sw demo-phone" data-fill-phone="9000000002">9000000002</button>
        </div>
        <div class="demo-row">
            <span><strong>Worker</strong> · Priya Sharma</span>
            <button type="button" class="btn-link-sw demo-phone" data-fill-phone="9000000003">9000000003</button>
        </div>
        <div class="demo-row">
            <span><strong>Host</strong> · Tiruppur Knit Staffing</span>
            <button type="button" class="btn-link-sw demo-phone" data-fill-phone="9000000004">9000000004</button>
        </div>
    </div>

    <p class="tiny muted mt-3 mb-0">
        <?= $otpBypass ? 'New numbers create a fresh account and go straight to role selection.' : 'New numbers create a fresh account at first verification.' ?>
    </p>
<?php else: ?>
    <p class="tiny muted mb-0">
        By continuing you agree to receive a one-time verification code by SMS.
    </p>
<?php endif; ?>
