<div class="card-sw card-pad text-center">
    <span class="role-icon role-icon-center"><?= icon('alert') ?></span>
    <h1>Something went wrong</h1>
    <p class="muted mt-2"><?= e($message ?? 'An unexpected error occurred. Please try again.') ?></p>
    <div class="row-gap justify-center">
        <a class="btn-sw" href="<?= url('/') ?>">Go to home</a>
        <a class="btn-sw btn-ghost" href="javascript:history.back()">Go back</a>
    </div>
</div>
