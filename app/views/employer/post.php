<?php
/** @var list<array<string, mixed>> $categories */
/** @var list<array<string, mixed>> $areas */
/** @var array<string, string> $errors */
/** @var array<string, mixed> $old */
$errors = $errors ?? [];
$old    = $old ?? [];
$val    = static fn (string $key, string $fallback = ''): string
    => array_key_exists($key, $old) ? trim((string) $old[$key]) : $fallback;
$publishChecked = !array_key_exists('submitted', $old) || isset($old['publish_now']);
?>
<a class="tiny" href="<?= url('/employer/jobs') ?>">← Back to my opportunities</a>

<div class="row-between mt-2 mb-3">
    <div>
        <h1>Create Opportunity</h1>
        <p class="muted mb-0">Set pay, dates and headcount. Workers see exactly what you publish.</p>
    </div>
</div>

<?php if ($errors !== []): ?>
    <div class="flash flash-error mb-3">
        <?= icon('alert') ?>
        <span>Please fix <?= count($errors) ?> field<?= count($errors) === 1 ? '' : 's' ?> below.</span>
    </div>
<?php endif; ?>

<div class="card-sw card-pad">
    <form method="post" action="<?= url('/employer/jobs') ?>" data-single-submit>
        <?= csrf_field() ?>
        <input type="hidden" name="submitted" value="1">

        <h2 class="h-sm mb-2">Role details</h2>
        <div class="form-grid">
            <div class="field span-2">
                <label class="label" for="title">Opportunity title <span class="req">*</span></label>
                <input class="input <?= isset($errors['title']) ? 'is-invalid' : '' ?>" id="title" name="title"
                       maxlength="180" value="<?= e($val('title')) ?>" placeholder="Wedding Reception Crew" <?= field_aria($errors, 'title', 'title') ?> required autofocus>
                <?php if (isset($errors['title'])): ?><span class="field-error" id="title-error"><?= e($errors['title']) ?></span><?php endif; ?>
            </div>

            <div class="field">
                <label class="label" for="category_id">Category <span class="req">*</span></label>
                <select class="select <?= isset($errors['category_id']) ? 'is-invalid' : '' ?>" id="category_id" name="category_id" <?= field_aria($errors, 'category_id', 'category_id') ?> required>
                    <option value="">Choose a category</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= (int) $category['id'] ?>" <?= $val('category_id') === (string) $category['id'] ? 'selected' : '' ?>>
                            <?= e($category['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['category_id'])): ?><span class="field-error" id="category_id-error"><?= e($errors['category_id']) ?></span><?php endif; ?>
            </div>

            <div class="field">
                <label class="label" for="headcount">People needed <span class="req">*</span></label>
                <input class="input <?= isset($errors['headcount']) ? 'is-invalid' : '' ?>" id="headcount" name="headcount"
                       type="number" min="1" max="200" value="<?= e($val('headcount', '1')) ?>" <?= field_aria($errors, 'headcount', 'headcount') ?> required>
                <?php if (isset($errors['headcount'])): ?><span class="field-error" id="headcount-error"><?= e($errors['headcount']) ?></span><?php endif; ?>
            </div>

            <div class="field span-2">
                <label class="label" for="description">Description <span class="req">*</span></label>
                <textarea class="textarea <?= isset($errors['description']) ? 'is-invalid' : '' ?>" id="description" name="description"
                          maxlength="5000" placeholder="What will the crew do? Uniform, meals, reporting point…" <?= field_aria($errors, 'description', 'description') ?> required><?= e($val('description')) ?></textarea>
                <span class="field-hint">At least 20 characters. Mention reporting time, meals and anything to bring.</span>
                <?php if (isset($errors['description'])): ?><span class="field-error" id="description-error"><?= e($errors['description']) ?></span><?php endif; ?>
            </div>
        </div>

        <div class="divider"></div>

        <h2 class="h-sm mb-2">Pay</h2>
        <div class="form-grid">
            <div class="field">
                <label class="label" for="pay_amount">Amount (₹) <span class="req">*</span></label>
                <input class="input <?= isset($errors['pay_amount']) ? 'is-invalid' : '' ?>" id="pay_amount" name="pay_amount"
                       type="number" min="1" step="1" value="<?= e($val('pay_amount')) ?>" placeholder="900" <?= field_aria($errors, 'pay_amount', 'pay_amount') ?> required>
                <?php if (isset($errors['pay_amount'])): ?><span class="field-error" id="pay_amount-error"><?= e($errors['pay_amount']) ?></span><?php endif; ?>
            </div>

            <div class="field">
                <label class="label" for="pay_type">Pay basis</label>
                <select class="select" id="pay_type" name="pay_type">
                    <?php foreach (['DAY' => 'Per day', 'HOUR' => 'Per hour', 'TOTAL' => 'Total for the opportunity'] as $key => $label): ?>
                        <option value="<?= e($key) ?>" <?= $val('pay_type', 'DAY') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="divider"></div>

        <h2 class="h-sm mb-2">Schedule</h2>
        <div class="form-grid">
            <div class="field">
                <label class="label" for="starts_on">Start date <span class="req">*</span></label>
                <input class="input <?= isset($errors['starts_on']) ? 'is-invalid' : '' ?>" id="starts_on" name="starts_on"
                       type="date" min="<?= date('Y-m-d') ?>" value="<?= e($val('starts_on', date('Y-m-d'))) ?>" <?= field_aria($errors, 'starts_on', 'starts_on') ?> required>
                <?php if (isset($errors['starts_on'])): ?><span class="field-error" id="starts_on-error"><?= e($errors['starts_on']) ?></span><?php endif; ?>
            </div>

            <div class="field">
                <label class="label" for="ends_on">End date <span class="req">*</span></label>
                <input class="input <?= isset($errors['ends_on']) ? 'is-invalid' : '' ?>" id="ends_on" name="ends_on"
                       type="date" min="<?= date('Y-m-d') ?>" value="<?= e($val('ends_on', date('Y-m-d'))) ?>" <?= field_aria($errors, 'ends_on', 'ends_on') ?> required>
                <?php if (isset($errors['ends_on'])): ?><span class="field-error" id="ends_on-error"><?= e($errors['ends_on']) ?></span><?php endif; ?>
            </div>

            <div class="field">
                <label class="label" for="start_time">Start time <span class="req">*</span></label>
                <input class="input <?= isset($errors['start_time']) ? 'is-invalid' : '' ?>" id="start_time" name="start_time"
                       type="time" value="<?= e($val('start_time', '09:00')) ?>" <?= field_aria($errors, 'start_time', 'start_time') ?> required>
                <?php if (isset($errors['start_time'])): ?><span class="field-error" id="start_time-error"><?= e($errors['start_time']) ?></span><?php endif; ?>
            </div>

            <div class="field">
                <label class="label" for="end_time">End time <span class="req">*</span></label>
                <input class="input <?= isset($errors['end_time']) ? 'is-invalid' : '' ?>" id="end_time" name="end_time"
                       type="time" value="<?= e($val('end_time', '18:00')) ?>" <?= field_aria($errors, 'end_time', 'end_time') ?> required>
                <?php if (isset($errors['end_time'])): ?><span class="field-error" id="end_time-error"><?= e($errors['end_time']) ?></span><?php endif; ?>
            </div>
        </div>

        <div class="divider"></div>

        <h2 class="h-sm mb-2">Location</h2>
        <div class="form-grid">
            <div class="field">
                <label class="label" for="area_id">Area <span class="req">*</span></label>
                <select class="select <?= isset($errors['area_id']) ? 'is-invalid' : '' ?>" id="area_id" name="area_id" <?= field_aria($errors, 'area_id', 'area_id') ?> required>
                    <option value="">Choose an area</option>
                    <?php foreach ($areas as $area): ?>
                        <option value="<?= (int) $area['id'] ?>" <?= $val('area_id') === (string) $area['id'] ? 'selected' : '' ?>>
                            <?= e($area['name']) ?> · <?= e($area['city']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['area_id'])): ?><span class="field-error" id="area_id-error"><?= e($errors['area_id']) ?></span><?php endif; ?>
            </div>

            <div class="field">
                <label class="label" for="address">Address <span class="req">*</span></label>
                <input class="input <?= isset($errors['address']) ? 'is-invalid' : '' ?>" id="address" name="address"
                       maxlength="255" value="<?= e($val('address')) ?>" placeholder="Venue name and street" autocomplete="street-address" <?= field_aria($errors, 'address', 'address') ?> required>
                <?php if (isset($errors['address'])): ?><span class="field-error" id="address-error"><?= e($errors['address']) ?></span><?php endif; ?>
            </div>
        </div>

        <div class="check-row mt-3">
            <input type="checkbox" id="publish_now" name="publish_now" value="1" <?= $publishChecked ? 'checked' : '' ?>>
            <label for="publish_now">
                <strong>Publish immediately</strong>
                <span class="tiny muted block">Uncheck to save as a draft you can review first.</span>
            </label>
        </div>

        <div class="row-between mt-3">
            <a class="btn-sw btn-ghost" href="<?= url('/employer/jobs') ?>">Cancel</a>
            <button type="submit" class="btn-sw btn-lg-sw" data-busy="Saving…">
                Save opportunity <?= icon('arrow') ?>
            </button>
        </div>
    </form>
</div>
