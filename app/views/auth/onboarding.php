<?php
/** @var array<string, mixed> $user */
/** @var list<array<string, mixed>> $areas */
/** @var array<string, string> $errors */
/** @var array<string, mixed> $old */
$errors   = $errors ?? [];
$old      = $old ?? [];
$isWorker = $user['role'] === 'worker';
$val      = static fn (string $key): string => trim((string) ($old[$key] ?? ''));
?>
<h1>Complete your profile</h1>
<p class="auth-sub">
    <?= $isWorker ? 'This is what hosts see when you send a Flight Request.' : 'Tell workers who is hiring.' ?>
</p>

<form method="post" action="<?= url('/onboarding') ?>" class="mt-3" data-single-submit>
    <?= csrf_field() ?>

    <div class="form-grid">
        <?php if ($isWorker): ?>
            <div class="field span-2">
                <label class="label" for="full_name">Full name <span class="req">*</span></label>
                <input class="input <?= isset($errors['full_name']) ? 'is-invalid' : '' ?>" id="full_name" name="full_name"
                       value="<?= e($val('full_name')) ?>" maxlength="160" autocomplete="name" <?= field_aria($errors, 'full_name', 'full_name') ?> required autofocus>
                <?php if (isset($errors['full_name'])): ?><span class="field-error" id="full_name-error"><?= e($errors['full_name']) ?></span><?php endif; ?>
            </div>
        <?php else: ?>
            <div class="field span-2">
                <label class="label" for="org_name">Business name <span class="req">*</span></label>
                <input class="input <?= isset($errors['org_name']) ? 'is-invalid' : '' ?>" id="org_name" name="org_name"
                       value="<?= e($val('org_name')) ?>" maxlength="160" autocomplete="organization" <?= field_aria($errors, 'org_name', 'org_name') ?> required autofocus>
                <?php if (isset($errors['org_name'])): ?><span class="field-error" id="org_name-error"><?= e($errors['org_name']) ?></span><?php endif; ?>
            </div>
            <div class="field span-2">
                <label class="label" for="contact_name">Contact person</label>
                <input class="input <?= isset($errors['contact_name']) ? 'is-invalid' : '' ?>" id="contact_name" name="contact_name"
                       value="<?= e($val('contact_name')) ?>" maxlength="160">
            </div>
        <?php endif; ?>

        <div class="field">
            <label class="label" for="area_id">Area <span class="req">*</span></label>
            <select class="select <?= isset($errors['area_id']) ? 'is-invalid' : '' ?>" id="area_id" name="area_id" <?= field_aria($errors, 'area_id', 'area_id') ?> required>
                <option value="">Choose an area</option>
                <?php foreach ($areas as $area): ?>
                    <option value="<?= (int) $area['id'] ?>" <?= (string) ($old['area_id'] ?? '') === (string) $area['id'] ? 'selected' : '' ?>>
                        <?= e($area['name']) ?> · <?= e($area['city']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['area_id'])): ?><span class="field-error" id="area_id-error"><?= e($errors['area_id']) ?></span><?php endif; ?>
        </div>

        <?php if ($isWorker): ?>
            <div class="field">
                <label class="label" for="availability">Availability</label>
                <select class="select" id="availability" name="availability">
                    <?php foreach (['FLEXIBLE' => 'Flexible', 'FULL_TIME' => 'Full time', 'PART_TIME' => 'Part time', 'WEEKENDS' => 'Weekends'] as $key => $label): ?>
                        <option value="<?= e($key) ?>" <?= ($old['availability'] ?? 'FLEXIBLE') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($isWorker): ?>
        <div class="field mt-2">
            <label class="label" for="skills">Skills</label>
            <input class="input <?= isset($errors['skills']) ? 'is-invalid' : '' ?>" id="skills" name="skills"
                   value="<?= e($val('skills')) ?>" maxlength="255" placeholder="Event setup, Crowd management, Billing" <?= field_aria($errors, 'skills', 'skills') ?>>
            <span class="field-hint">Separate skills with commas.</span>
            <?php if (isset($errors['skills'])): ?><span class="field-error" id="skills-error"><?= e($errors['skills']) ?></span><?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="field mt-2">
        <label class="label" for="about">About <?= $isWorker ? 'you' : 'your business' ?></label>
        <textarea class="textarea <?= isset($errors['about']) ? 'is-invalid' : '' ?>" id="about" name="about" maxlength="600"
                  placeholder="<?= $isWorker ? 'Short introduction for hosts…' : 'What kind of work do you hire for?' ?>" <?= field_aria($errors, 'about', 'about') ?>><?= e($val('about')) ?></textarea>
        <?php if (isset($errors['about'])): ?><span class="field-error" id="about-error"><?= e($errors['about']) ?></span><?php endif; ?>
    </div>

    <button type="submit" class="btn-sw btn-block btn-lg-sw mt-3" data-busy="Saving…">
        Finish setup <?= icon('arrow') ?>
    </button>
</form>
