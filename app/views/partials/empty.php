<?php
/** @var string $heading */
/** @var string $text */
/** @var string|null $iconName */
/** @var string|null $actionHref */
/** @var string|null $actionLabel */
?>
<div class="empty">
    <span class="role-icon"><?= icon($iconName ?? 'briefcase') ?></span>
    <h3><?= e($heading) ?></h3>
    <p><?= e($text) ?></p>
    <?php if (!empty($actionHref) && !empty($actionLabel)): ?>
        <a class="btn-sw" href="<?= url($actionHref) ?>"><?= e($actionLabel) ?> <?= icon('arrow') ?></a>
    <?php endif; ?>
</div>
