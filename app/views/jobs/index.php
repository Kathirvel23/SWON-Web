<?php
/** @var list<array<string, mixed>> $jobs */
/** @var array<string, string> $filters */
/** @var list<array<string, mixed>> $categories */
/** @var list<array<string, mixed>> $areas */
/** @var int $total */
/** @var int $page */
/** @var int $pages */
$hasFilters = $filters['q'] !== '' || $filters['category'] !== '' || $filters['area'] !== '';
$queryBase  = array_filter([
    'q'        => $filters['q'],
    'category' => $filters['category'],
    'area'     => $filters['area'],
    'sort'     => $filters['sort'],
], static fn (string $v): bool => $v !== '');
$pageUrl = static fn (int $p): string => url('/jobs?' . http_build_query(array_merge($queryBase, ['page' => $p])));
$from    = $total === 0 ? 0 : (($page - 1) * 9) + 1;
$to      = min($total, $page * 9);
?>
<div class="row-between mb-3">
    <div>
        <h1>Find Opportunities</h1>
        <p class="muted mb-0" aria-live="polite"><?= (int) $total ?> open <?= $total === 1 ? 'opportunity' : 'opportunities' ?> across Coimbatore and Tiruppur.</p>
    </div>
    <?php if ($hasFilters): ?>
        <a class="btn-sw btn-ghost btn-sm" href="<?= url('/jobs') ?>">Clear filters</a>
    <?php endif; ?>
</div>

<form method="get" action="<?= url('/jobs') ?>" class="filter-bar mb-3">
    <div class="search-wrap">
        <?= icon('search') ?>
        <input class="input" type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Search opportunity, skill or category" aria-label="Search opportunities" enterkeyhint="search">
    </div>

    <select class="select" name="category" aria-label="Category">
        <option value="">All categories</option>
        <?php foreach ($categories as $category): ?>
            <option value="<?= e($category['slug']) ?>" <?= $filters['category'] === $category['slug'] ? 'selected' : '' ?>>
                <?= e($category['name']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <select class="select" name="area" aria-label="Area">
        <option value="">All areas</option>
        <?php foreach ($areas as $area): ?>
            <option value="<?= (int) $area['id'] ?>" <?= $filters['area'] === (string) $area['id'] ? 'selected' : '' ?>>
                <?= e($area['name']) ?> · <?= e($area['city']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <select class="select" name="sort" aria-label="Sort">
        <option value="newest" <?= $filters['sort'] === 'newest' ? 'selected' : '' ?>>Newest first</option>
        <option value="pay" <?= $filters['sort'] === 'pay' ? 'selected' : '' ?>>Highest pay</option>
    </select>

    <button type="submit" class="btn-sw">Apply</button>
</form>

<?php if ($jobs === []): ?>
    <?php \App\View::partial('partials/empty', [
        'heading'    => 'No opportunities match these filters',
        'text'       => 'Try a different area or category — new opportunities are added daily.',
        'iconName'   => 'search',
        'actionHref' => '/jobs',
        'actionLabel' => 'Reset filters',
    ]); ?>
<?php else: ?>
    <div class="job-grid">
        <?php foreach ($jobs as $job): ?>
            <?php \App\View::partial('partials/job-card', ['job' => $job]); ?>
        <?php endforeach; ?>
    </div>

    <div class="row-between mt-3">
        <span class="tiny muted">Showing <?= $from ?>–<?= $to ?> of <?= (int) $total ?></span>

        <?php if ($pages > 1): ?>
            <nav class="pager" aria-label="Pagination">
                <?php if ($page > 1): ?>
                    <a href="<?= e($pageUrl($page - 1)) ?>" aria-label="Previous page">←</a>
                <?php endif; ?>

                <?php for ($p = max(1, $page - 2); $p <= min($pages, $page + 2); $p++): ?>
                    <?php if ($p === $page): ?>
                        <span class="current" aria-current="page"><?= $p ?></span>
                    <?php else: ?>
                        <a href="<?= e($pageUrl($p)) ?>"><?= $p ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($page < $pages): ?>
                    <a href="<?= e($pageUrl($page + 1)) ?>" aria-label="Next page">→</a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    </div>
<?php endif; ?>
