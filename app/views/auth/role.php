<?php
/** @var array<string, string> $errors */
$errors = $errors ?? [];
?>
<h1>How will you use SWON?</h1>
<p class="auth-sub">Choose one. This cannot be changed later without support.</p>

<?php if (isset($errors['role'])): ?>
    <div class="flash flash-error mt-3" id="role-error" role="alert"><?= icon('alert') ?><span><?= e($errors['role']) ?></span></div>
<?php endif; ?>

<form method="post" action="<?= url('/role') ?>" class="mt-3">
    <?= csrf_field() ?>

    <div class="role-grid">
        <label class="role-card">
            <input type="radio" name="role" value="worker" <?= isset($errors['role']) ? 'aria-describedby="role-error"' : '' ?> required>
            <div class="role-body">
                <span class="role-icon"><?= icon('briefcase') ?></span>
                <h3>I want work</h3>
                <p class="tiny muted mb-0">Find daily and contract opportunities, send a Flight Request in seconds and track it.</p>
            </div>
        </label>

        <label class="role-card">
            <input type="radio" name="role" value="employer" <?= isset($errors['role']) ? 'aria-describedby="role-error"' : '' ?> required>
            <div class="role-body">
                <span class="role-icon"><?= icon('users') ?></span>
                <h3>I want to hire</h3>
                <p class="tiny muted mb-0">Post opportunities, review Flight Requests and build your Formation without phone calls.</p>
            </div>
        </label>
    </div>

    <button type="submit" class="btn-sw btn-block btn-lg-sw mt-3" data-busy="Saving…">
        Continue <?= icon('arrow') ?>
    </button>
</form>
