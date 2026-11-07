<?php
/** @var array<string, mixed> $user */
/** @var list<array<string, mixed>> $areas */
/** @var array<string, string> $errors */
/** @var array<string, mixed> $old */
$errors = $errors ?? [];
$old    = $old ?? [];
$val    = static fn (string $key, string $fallback = ''): string
    => array_key_exists($key, $old) ? trim((string) $old[$key]) : $fallback;
$areaId = (string) ($old['area_id'] ?? $user['area_id'] ?? '');
?>
<div class="row-between mb-3">
    <div class="profile-head">
        <span class="avatar avatar-lg"><?= e(initials((string) $user['display_name'])) ?></span>
        <div>
            <h1 class="h-xl"><?= e($user['display_name']) ?></h1>
            <div class="profile-meta">
                <span class="chip chip-cat">Host</span>
                <span><?= e(mask_phone((string) $user['phone'])) ?></span>
                <span>Member since <?= e(format_date((string) ($user['onboarded_at'] ?? ''))) ?></span>
            </div>
        </div>
    </div>
    <a class="btn-sw btn-ghost btn-sm" href="<?= url('/employer') ?>">Dashboard</a>
</div>

<div class="card-sw card-pad">
    <h2 class="h-sm mb-3">Business profile</h2>

    <form method="post" action="<?= url('/profile') ?>" data-single-submit>
        <?= csrf_field() ?>

        <div class="form-grid">
            <div class="field span-2">
                <label class="label" for="org_name">Business name <span class="req">*</span></label>
                <input class="input <?= isset($errors['org_name']) ? 'is-invalid' : '' ?>" id="org_name" name="org_name"
                       maxlength="160" value="<?= e($val('org_name', (string) ($user['org_name'] ?? ''))) ?>" autocomplete="organization" <?= field_aria($errors, 'org_name', 'org_name') ?> required>
                <?php if (isset($errors['org_name'])): ?><span class="field-error" id="org_name-error"><?= e($errors['org_name']) ?></span><?php endif; ?>
            </div>

            <div class="field">
                <label class="label" for="contact_name">Contact person</label>
                <input class="input" id="contact_name" name="contact_name" maxlength="160" autocomplete="name"
                       value="<?= e($val('contact_name', (string) ($user['contact_name'] ?? ''))) ?>">
            </div>

            <div class="field">
                <label class="label" for="area_id">Primary area <span class="req">*</span></label>
                <select class="select <?= isset($errors['area_id']) ? 'is-invalid' : '' ?>" id="area_id" name="area_id" <?= field_aria($errors, 'area_id', 'area_id') ?> required>
                    <option value="">Choose an area</option>
                    <?php foreach ($areas as $area): ?>
                        <option value="<?= (int) $area['id'] ?>" <?= $areaId === (string) $area['id'] ? 'selected' : '' ?>>
                            <?= e($area['name']) ?> · <?= e($area['city']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['area_id'])): ?><span class="field-error" id="area_id-error"><?= e($errors['area_id']) ?></span><?php endif; ?>
            </div>
        </div>

        <div class="field mt-2">
            <label class="label" for="about">About the business</label>
            <textarea class="textarea" id="about" name="about" maxlength="600"
                      placeholder="What kind of work do you hire for?"><?= e($val('about', (string) ($user['employer_about'] ?? ''))) ?></textarea>
        </div>

        <div class="row-between mt-3">
            <span class="tiny muted">Phone number changes are handled by support.</span>
            <button type="submit" class="btn-sw" data-busy="Saving…">Save changes</button>
        </div>
    </form>
</div>
