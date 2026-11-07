<div class="card-sw card-pad text-center">
    <span class="role-icon role-icon-center"><?= icon('shield') ?></span>
    <h1>Session expired</h1>
    <p class="muted mt-2">Your security token expired or the form was submitted twice. Please go back, refresh and try again.</p>
    <div class="row-gap justify-center">
        <a class="btn-sw" href="<?= url('/login') ?>">Sign in again</a>
        <a class="btn-sw btn-ghost" href="<?= url('/') ?>">Home</a>
    </div>
</div>
