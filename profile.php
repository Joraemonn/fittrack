<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

require_auth();

$pageTitle = 'Profile';
$errors = [];
$dbError = null;
$success = get_flash('success');
$user = current_user();
$userId = (int) $user['id'];
$measurementOptions = [
    'metric' => 'Metric (kg / km / kcal / C)',
    'imperial' => 'Imperial (lbs / miles / kcal / F)',
    'hybrid' => 'Hybrid / Canadian (lbs / km / kcal / C)',
];
$timezoneOptions = timezone_identifiers_list();
$selectedProfileUnits = (string) ($user['measurement_units'] ?? 'metric');

if (!array_key_exists($selectedProfileUnits, $measurementOptions)) {
    $selectedProfileUnits = 'metric';
}

$heightDisplay = '';
$weightDisplay = '';

if (($user['height_cm'] ?? '') !== '') {
    $heightDisplay = number_format(height_from_cm((float) $user['height_cm'], $selectedProfileUnits), 2, '.', '');
}

if (($user['current_weight_kg'] ?? '') !== '') {
    $weightDisplay = number_format(weight_from_kg((float) $user['current_weight_kg'], $selectedProfileUnits), 2, '.', '');
}

if (is_post()) {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $age = trim($_POST['age'] ?? '');
    $heightValue = trim($_POST['height_cm'] ?? '');
    $weightValue = trim($_POST['current_weight_kg'] ?? '');
    $goal = trim($_POST['fitness_goal'] ?? '');
    $measurementUnits = trim($_POST['measurement_units'] ?? 'metric');
    $profileValueUnits = trim($_POST['profile_value_units'] ?? $measurementUnits);
    $timezone = trim($_POST['timezone'] ?? 'Asia/Singapore');

    validate_required($errors, 'full_name', 'Full name', $fullName);
    validate_required($errors, 'email', 'Email', $email);
    validate_required($errors, 'fitness_goal', 'Fitness goal', $goal);

    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }

    if (!array_key_exists($measurementUnits, $measurementOptions)) {
        $errors['measurement_units'] = 'Please choose a valid measurement unit.';
    }

    if (!array_key_exists($profileValueUnits, $measurementOptions)) {
        $profileValueUnits = $measurementUnits;
    }

    if (!in_array($timezone, $timezoneOptions, true)) {
        $errors['timezone'] = 'Please choose a valid timezone.';
    }

    try {
        $profileImagePath = $user['profile_image'] ?? null;
        if (!empty($_FILES['profile_image']['name'])) {
            $profileImagePath = uploaded_profile_path($_FILES['profile_image']);
        }

        if (!$errors) {
            $pdo = db();
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email AND id != :id LIMIT 1');
            $stmt->execute(['email' => $email, 'id' => $userId]);

            if ($stmt->fetch()) {
                $errors['email'] = 'Another account is already using this email.';
            } else {
                $update = $pdo->prepare('UPDATE users SET full_name = :full_name, email = :email, age = :age, height_cm = :height_cm, current_weight_kg = :current_weight_kg, fitness_goal = :fitness_goal, profile_image = :profile_image, measurement_units = :measurement_units, timezone = :timezone WHERE id = :id');
                $update->execute([
                    'full_name' => $fullName,
                    'email' => $email,
                    'age' => $age !== '' ? (int) $age : null,
                    'height_cm' => $heightValue !== '' ? height_to_cm((float) $heightValue, $profileValueUnits) : null,
                    'current_weight_kg' => $weightValue !== '' ? weight_to_kg((float) $weightValue, $profileValueUnits) : null,
                    'fitness_goal' => $goal,
                    'profile_image' => $profileImagePath,
                    'measurement_units' => $measurementUnits,
                    'timezone' => $timezone,
                    'id' => $userId,
                ]);
                refresh_session_user($pdo, $userId);
                set_flash('success', 'Profile updated successfully.');
                redirect('profile.php');
            }
        }
    } catch (RuntimeException $exception) {
        $dbError = $exception->getMessage();
    } catch (PDOException $exception) {
        $dbError = 'Unable to update the profile right now.';
    }

    if ($errors) {
        set_old($_POST);
    }
}

try {
    refresh_session_user(db(), $userId);
    $user = current_user();
} catch (PDOException $exception) {
    $dbError = $dbError ?: 'Unable to load profile details right now.';
}

$selectedProfileUnits = old('profile_value_units', old('measurement_units', (string) ($user['measurement_units'] ?? 'metric')));

if (!array_key_exists($selectedProfileUnits, $measurementOptions)) {
    $selectedProfileUnits = 'metric';
}

$heightDisplay = ($user['height_cm'] ?? '') !== ''
    ? number_format(height_from_cm((float) $user['height_cm'], $selectedProfileUnits), 2, '.', '')
    : '';
$weightDisplay = ($user['current_weight_kg'] ?? '') !== ''
    ? number_format(weight_from_kg((float) $user['current_weight_kg'], $selectedProfileUnits), 2, '.', '')
    : '';

require_once __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
    <div class="container">
        <span class="eyebrow">Profile</span>
        <h1>Manage your personal fitness details</h1>
    </div>
</section>

<section class="section">
    <div class="container settings-layout">
        <aside class="settings-sidebar" aria-label="Profile settings navigation">
            <button type="button" class="active" data-settings-tab="profile-information">Profile Information</button>
            <button type="button" data-settings-tab="display-settings">Display</button>
        </aside>

        <article class="profile-single-card">
            <div class="profile-overview">
                <div class="profile-summary">
                    <label class="avatar-upload" for="profile_image" aria-label="Change profile photo">
                        <?php if (!empty($user['profile_image'])): ?>
                            <img class="avatar" src="<?= e($user['profile_image']) ?>" alt="Profile photo">
                        <?php else: ?>
                            <span class="avatar avatar-placeholder"></span>
                        <?php endif; ?>
                        <span class="avatar-camera" aria-hidden="true">
                            <svg viewBox="0 0 64 48" focusable="false">
                                <path d="M18 12 L22 5 H42 L46 12 H53 C58 12 61 15 61 20 V39 C61 44 58 47 53 47 H11 C6 47 3 44 3 39 V20 C3 15 6 12 11 12 H18 Z"></path>
                                <circle cx="32" cy="30" r="11"></circle>
                            </svg>
                        </span>
                    </label>
                    <div>
                        <h2><?= e($user['full_name'] ?? 'FitTrack User') ?></h2>
                    </div>
                </div>
            </div>

            <div class="profile-edit-panel">
                <?php if ($success): ?><div class="alert success"><?= e($success) ?></div><?php endif; ?>
                <?php if ($dbError): ?><div class="alert error"><?= e($dbError) ?></div><?php endif; ?>
                <form method="post" enctype="multipart/form-data" data-profile-form>
                    <input type="hidden" name="profile_value_units" value="<?= e($selectedProfileUnits) ?>" data-profile-value-units>
                    <section id="profile-information" class="settings-section" data-settings-panel="profile-information">
                        <div>
                            <h2>Edit Profile Information</h2>
                            <p class="section-subtitle">Keep your account details and fitness goal up to date.</p>
                        </div>
                        <div class="form-grid profile-form-stack">
                            <div class="field field-full">
                                <label for="full_name">Name</label>
                                <input id="full_name" name="full_name" value="<?= e(old('full_name', (string) ($user['full_name'] ?? ''))) ?>">
                                <?php if (isset($errors['full_name'])): ?><span class="error-text"><?= e($errors['full_name']) ?></span><?php endif; ?>
                            </div>
                            <div class="field field-full">
                                <label for="age">Age</label>
                                <input id="age" type="number" name="age" value="<?= e(old('age', (string) ($user['age'] ?? ''))) ?>">
                            </div>
                            <div class="field field-full">
                                <label for="email">Email</label>
                                <input id="email" type="email" name="email" value="<?= e(old('email', (string) ($user['email'] ?? ''))) ?>">
                                <?php if (isset($errors['email'])): ?><span class="error-text"><?= e($errors['email']) ?></span><?php endif; ?>
                            </div>
                            <div class="field field-full">
                                <label for="height_cm" data-height-label>Height (<?= e(height_unit($selectedProfileUnits)) ?>)</label>
                                <input id="height_cm" type="number" step="0.01" name="height_cm" value="<?= e(old('height_cm', $heightDisplay)) ?>" data-height-input>
                            </div>
                            <div class="field field-full">
                                <label for="current_weight_kg" data-weight-label>Weight (<?= e(weight_unit($selectedProfileUnits)) ?>)</label>
                                <input id="current_weight_kg" type="number" step="0.01" name="current_weight_kg" value="<?= e(old('current_weight_kg', $weightDisplay)) ?>" data-weight-input>
                            </div>
                            <div class="field">
                                <input id="profile_image" class="sr-only-file" type="file" name="profile_image" accept=".jpg,.jpeg,.png,.gif,.webp">
                            </div>
                            <div class="field field-full">
                                <label for="fitness_goal">Fitness Goal</label>
                                <input id="fitness_goal" name="fitness_goal" value="<?= e(old('fitness_goal', (string) ($user['fitness_goal'] ?? ''))) ?>">
                                <?php if (isset($errors['fitness_goal'])): ?><span class="error-text"><?= e($errors['fitness_goal']) ?></span><?php endif; ?>
                            </div>
                        </div>
                    </section>

                    <section id="display-settings" class="settings-section" data-settings-panel="display-settings" hidden>
                        <div>
                            <h2>Display</h2>
                            <p class="section-subtitle">Choose how FitTrack should show units and local time.</p>
                        </div>
                        <div class="form-grid">
                            <div class="field">
                                <label for="measurement_units">Measurement Units</label>
                                <select id="measurement_units" name="measurement_units">
                                    <?php $selectedMeasurement = old('measurement_units', (string) ($user['measurement_units'] ?? 'metric')); ?>
                                    <?php foreach ($measurementOptions as $value => $label): ?>
                                        <option value="<?= e($value) ?>" <?= $selectedMeasurement === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['measurement_units'])): ?><span class="error-text"><?= e($errors['measurement_units']) ?></span><?php endif; ?>
                            </div>
                            <div class="field">
                                <label for="timezone">Timezone</label>
                                <select id="timezone" name="timezone">
                                    <?php $selectedTimezone = old('timezone', (string) ($user['timezone'] ?? 'Asia/Singapore')); ?>
                                    <?php foreach ($timezoneOptions as $timezoneOption): ?>
                                        <option value="<?= e($timezoneOption) ?>" <?= $selectedTimezone === $timezoneOption ? 'selected' : '' ?>><?= e($timezoneOption) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['timezone'])): ?><span class="error-text"><?= e($errors['timezone']) ?></span><?php endif; ?>
                            </div>
                        </div>
                    </section>

                    <div class="form-actions">
                        <button type="submit">Update Profile</button>
                    </div>
                </form>
            </div>
        </article>
    </div>
</section>
<?php
clear_old();
require_once __DIR__ . '/includes/footer.php';
