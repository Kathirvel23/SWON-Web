<?php $items = flashes(); ?>
<?php if ($items !== []): ?>
    <div class="flash-stack" aria-live="polite" aria-atomic="false">
        <?php foreach ($items as $flash): ?>
            <?php
            $type = in_array($flash['type'], ['success', 'error', 'info'], true) ? $flash['type'] : 'info';
            $iconName = $type === 'success' ? 'check' : ($type === 'error' ? 'alert' : 'info');
            ?>
            <div class="flash flash-<?= e($type) ?>" data-flash role="status">
                <?= icon($iconName) ?>
                <span><?= e($flash['message']) ?></span>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
