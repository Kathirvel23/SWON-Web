<?php
/** @var array<string, mixed> $user */
/** @var list<array<string, mixed>> $areas */
/** @var array<string, string> $errors */
/** @var array<string, mixed> $old */
$errors = $errors ?? [];
$old    = $old ?? [];
$val    = static fn (string $key, string $fallback = ''): string
    => array_key_exists($key, $old) ? trim((string) $old[$key]) : $fallback;
$areaId   = (string) ($old['area_id'] ?? $user['area_id'] ?? '');
$current  = (string) ($old['availability'] ?? $user['availability'] ?? 'FLEXIBLE');
?>
<div class="row-between mb-3">
    <div class="profile-head">
        <span class="avatar avatar-lg"><?= e(initials((string) $user['display_name'])) ?></span>
        <div>
            <h1 class="h-xl"><?= e($user['display_name']) ?></h1>
            <div class="profile-meta">
                <span class="chip chip-cat">Worker</span>
                <span><?= e(mask_phone((string) $user['phone'])) ?></span>
                <span>Member since <?= e(format_date((string) ($user['onboarded_at'] ?? ''))) ?></span>
            </div>
        </div>
    </div>
    <a class="btn-sw btn-ghost btn-sm" href="<?= url('/worker') ?>">Dashboard</a>
</div>

<div class="card-sw card-pad">
    <h2 class="h-sm mb-3">Edit profile</h2>

    <form method="post" action="<?= url('/profile') ?>" data-single-submit>
        <?= csrf_field() ?>

        <div class="form-grid">
            <div class="field span-2">
                <label class="label" for="full_name">Full name <span class="req">*</span></label>
                <input class="input <?= isset($errors['full_name']) ? 'is-invalid' : '' ?>" id="full_name" name="full_name"
                       maxlength="160" value="<?= e($val('full_name', (string) ($user['full_name'] ?? ''))) ?>" autocomplete="name" <?= field_aria($errors, 'full_name', 'full_name') ?> required>
                <?php if (isset($errors['full_name'])): ?><span class="field-error" id="full_name-error"><?= e($errors['full_name']) ?></span><?php endif; ?>
            </div>

            <div class="field">
                <label class="label" for="area_id">Preferred area <span class="req">*</span></label>
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

            <div class="field">
                <label class="label" for="availability">Availability</label>
                <select class="select" id="availability" name="availability">
                    <?php foreach (['FLEXIBLE' => 'Flexible', 'FULL_TIME' => 'Full time', 'PART_TIME' => 'Part time', 'WEEKENDS' => 'Weekends'] as $key => $label): ?>
                        <option value="<?= e($key) ?>" <?= $current === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="field mt-2">
            <label class="label" for="skills">Skills</label>
            <input class="input <?= isset($errors['skills']) ? 'is-invalid' : '' ?>" id="skills" name="skills" maxlength="255"
                   value="<?= e($val('skills', (string) ($user['skills'] ?? ''))) ?>"
                   placeholder="Event setup, Crowd management, Billing" <?= field_aria($errors, 'skills', 'skills') ?>>
            <span class="field-hint">Separate skills with commas. Hosts see these on your Flight Requests.</span>
            <?php if (isset($errors['skills'])): ?><span class="field-error" id="skills-error"><?= e($errors['skills']) ?></span><?php endif; ?>
        </div>

        <div class="field mt-2">
            <label class="label" for="about">About you</label>
            <textarea class="textarea <?= isset($errors['about']) ? 'is-invalid' : '' ?>" id="about" name="about" maxlength="600"
                      placeholder="A short introduction…" <?= field_aria($errors, 'about', 'about') ?>><?= e($val('about', (string) ($user['worker_about'] ?? ''))) ?></textarea>
            <?php if (isset($errors['about'])): ?><span class="field-error" id="about-error"><?= e($errors['about']) ?></span><?php endif; ?>
        </div>

        <div class="row-between mt-3">
            <span class="tiny muted">Phone number changes are handled by support.</span>
            <button type="submit" class="btn-sw" data-busy="Saving…">Save changes</button>
        </div>
    </form>
</div>
