<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

require_auth();

$pageTitle = 'Meal Tracker';
$errors = [];
$dbError = null;
$success = get_flash('success');
$meals = [];
$mealColumns = [];
$userId = (int) current_user()['id'];
$timezoneName = (string) (current_user()['timezone'] ?? 'Asia/Singapore');

if (!in_array($timezoneName, timezone_identifiers_list(), true)) {
    $timezoneName = 'Asia/Singapore';
}

$today = new DateTime('now', new DateTimeZone($timezoneName));
$todayDisplay = $today->format('l, j F Y');
$todayMachine = $today->format('Y-m-d');
$currentTime = $today->format('H:i');
$singaporeFoods = [
    'bak_kut_teh',
    'char_kway_teow',
    'chicken_rice',
    'chilli_crab',
    'curry_puff',
    'fried_carrot_cake',
    'hokkien_mee',
    'ice_kacang',
    'kaya_toast',
    'laksa',
    'lor_mee',
    'mee_siam',
    'nasi_lemak',
    'popiah',
    'porridge',
    'roti_prata',
    'satay',
    'sliced_fish_soup',
    'tau_suan',
    'yong_tau_foo',
];

$mealKey = static function (string $value): string {
    return strtolower((string) preg_replace('/[^a-z0-9]+/', '_', trim($value)));
};

$isSingaporeMeal = static function (array $meal) use ($singaporeFoods, $mealKey): bool {
    $foodName = isset($meal['food_name']) ? $mealKey((string) $meal['food_name']) : '';
    $mealName = $mealKey((string) ($meal['meal_name'] ?? ''));

    return in_array($foodName, $singaporeFoods, true) || in_array($mealName, $singaporeFoods, true);
};

$formatMacro = static function ($value): string {
    return $value !== null ? number_format((float) $value, 1) . ' g' : '--';
};

$formatServing = static function (array $meal): string {
    if (isset($meal['serving_grams']) && $meal['serving_grams'] !== null && $meal['serving_grams'] !== '') {
        return number_format((float) $meal['serving_grams'], 0) . ' g';
    }

    return '100 g';
};

$formatMealTime = static function (?string $value): string {
    if (!$value) {
        return '--';
    }

    return strtolower(date('g:i a', strtotime($value)));
};

$formatMealDate = static function (?string $value): string {
    if (!$value) {
        return '--';
    }

    return date('M j, Y', strtotime($value));
};

$mealSource = static function (array $meal): string {
    return ($meal['source'] ?? '') === 'scanned' ? 'scanned' : 'manual';
};

if (is_post()) {
    $mealDate = trim($_POST['meal_date'] ?? '');
    $mealTime = trim($_POST['meal_time'] ?? '');
    $mealName = trim($_POST['meal_name'] ?? '');
    $calories = trim($_POST['calories'] ?? '');
    $protein = trim($_POST['protein_g'] ?? '');
    $carbs = trim($_POST['carbs_g'] ?? '');
    $fats = trim($_POST['fats_g'] ?? '');

    validate_required($errors, 'meal_date', 'Date', $mealDate);
    validate_required($errors, 'meal_name', 'Meal name', $mealName);
    validate_required($errors, 'calories', 'Calories', $calories);

    if ($calories !== '' && (!ctype_digit($calories) || (int) $calories < 0)) {
        $errors['calories'] = 'Calories must be a whole number.';
    }

    if (!$errors) {
        try {
            $stmt = db()->prepare('INSERT INTO meals (user_id, meal_date, meal_time, meal_name, calories, protein_g, carbs_g, fats_g) VALUES (:user_id, :meal_date, :meal_time, :meal_name, :calories, :protein_g, :carbs_g, :fats_g)');
            $stmt->execute([
                'user_id' => $userId,
                'meal_date' => $mealDate,
                'meal_time' => $mealTime !== '' ? $mealTime : null,
                'meal_name' => $mealName,
                'calories' => (int) $calories,
                'protein_g' => normalize_number($protein),
                'carbs_g' => normalize_number($carbs),
                'fats_g' => normalize_number($fats),
            ]);
            set_flash('success', 'Meal added successfully.');
            redirect('meals.php');
        } catch (PDOException $exception) {
            $dbError = 'Unable to save the meal right now.';
        }
    } else {
        set_old($_POST);
    }
}

try {
    $pdo = db();

    foreach ($pdo->query('SHOW COLUMNS FROM meals') as $column) {
        $mealColumns[$column['Field']] = true;
    }

    $stmt = $pdo->prepare('SELECT * FROM meals WHERE user_id = :user_id AND meal_date = :meal_date ORDER BY meal_time DESC, id DESC');
    $stmt->execute([
        'user_id' => $userId,
        'meal_date' => $todayMachine,
    ]);
    $meals = $stmt->fetchAll();
} catch (PDOException $exception) {
    $dbError = $dbError ?: 'Unable to load meal history right now.';
}

$mealCount = count($meals);
$totalCalories = array_sum(array_map(static fn (array $meal): int => (int) $meal['calories'], $meals));
$totalProtein = array_sum(array_map(static fn (array $meal): float => (float) ($meal['protein_g'] ?? 0), $meals));
$totalCarbs = array_sum(array_map(static fn (array $meal): float => (float) ($meal['carbs_g'] ?? 0), $meals));
$totalFats = array_sum(array_map(static fn (array $meal): float => (float) ($meal['fats_g'] ?? 0), $meals));
$manualFormOpen = is_post() && (bool) $errors;

require_once __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
    <div class="container">
        <span class="eyebrow">Meal Tracker</span>
        <h1>Make every meal count</h1>
        <div class="dashboard-hero-meta">
            <p>Track every meal and fuel the grind</p>
            <time datetime="<?= e($todayMachine) ?>"><?= e($todayDisplay) ?></time>
        </div>
    </div>
</section>

<section class="section">
    <div class="container tracker-grid">
        <article class="panel meal-entry-panel">
            <div class="meal-scan-hero-card">
                <span class="meal-ai-powered"><span></span>AI POWERED</span>
                <h2>Scan your meal</h2>
                <p>Scan or upload your food photo, and we’ll identify it with nutrition details automatically.</p>
                <div class="meal-scan-hero-actions">
                    <button class="button meal-scan-entry" type="button" data-meal-scan-open>
                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                            <path d="M5 7h1a2 2 0 0 0 2 -2a1 1 0 0 1 1 -1h6a1 1 0 0 1 1 1a2 2 0 0 0 2 2h1a2 2 0 0 1 2 2v9a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-9a2 2 0 0 1 2 -2"></path>
                            <path d="M9 13a3 3 0 1 0 6 0a3 3 0 0 0 -6 0"></path>
                        </svg>
                        <span>Use Camera</span>
                    </button>
                    <button class="button secondary meal-scan-entry" type="button" data-meal-scan-open data-meal-scan-prefer-upload>
                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                            <path d="M15 8h.01"></path>
                            <path d="M3 6a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v12a3 3 0 0 1 -3 3h-12a3 3 0 0 1 -3 -3v-12z"></path>
                            <path d="M3 16l5 -5c.928 -.893 2.072 -.893 3 0l5 5"></path>
                            <path d="M14 14l1 -1c.928 -.893 2.072 -.893 3 0l3 3"></path>
                        </svg>
                        <span>Upload Photo</span>
                    </button>
                </div>
                <div class="meal-support-hint">Supports 121 foods including <strong>20 Singapore classics</strong> like chicken rice, laksa &amp; nasi lemak 🇸🇬</div>
                <button class="meal-manual-toggle <?= $manualFormOpen ? 'active' : '' ?>" type="button" aria-expanded="<?= $manualFormOpen ? 'true' : 'false' ?>" data-manual-meal-toggle>Can't scan? Enter macros manually</button>
            </div>
            <?php if ($success): ?><div class="alert success"><?= e($success) ?></div><?php endif; ?>
            <?php if ($dbError): ?><div class="alert error"><?= e($dbError) ?></div><?php endif; ?>
            <form method="post" class="meal-manual-form <?= $manualFormOpen ? 'open' : '' ?>" data-manual-meal-form>
                <div class="form-grid">
                    <div class="field">
                        <label for="meal_date">Date</label>
                        <input id="meal_date" type="date" name="meal_date" value="<?= e(old('meal_date', date('Y-m-d'))) ?>">
                        <?php if (isset($errors['meal_date'])): ?><span class="error-text"><?= e($errors['meal_date']) ?></span><?php endif; ?>
                    </div>
                    <div class="field">
                        <label for="meal_time">Meal Time</label>
                        <input id="meal_time" type="time" name="meal_time" value="<?= e(old('meal_time')) ?>">
                    </div>
                    <div class="field-full">
                        <label for="meal_name">Meal Name</label>
                        <input id="meal_name" name="meal_name" value="<?= e(old('meal_name')) ?>">
                        <?php if (isset($errors['meal_name'])): ?><span class="error-text"><?= e($errors['meal_name']) ?></span><?php endif; ?>
                    </div>
                    <div class="field">
                        <label for="calories">Calories</label>
                        <input id="calories" type="number" name="calories" value="<?= e(old('calories')) ?>">
                        <?php if (isset($errors['calories'])): ?><span class="error-text"><?= e($errors['calories']) ?></span><?php endif; ?>
                    </div>
                    <div class="field">
                        <label for="protein_g">Protein (g)</label>
                        <input id="protein_g" type="number" step="0.01" name="protein_g" value="<?= e(old('protein_g')) ?>">
                    </div>
                    <div class="field">
                        <label for="carbs_g">Carbs (g)</label>
                        <input id="carbs_g" type="number" step="0.01" name="carbs_g" value="<?= e(old('carbs_g')) ?>">
                    </div>
                    <div class="field">
                        <label for="fats_g">Fats (g)</label>
                        <input id="fats_g" type="number" step="0.01" name="fats_g" value="<?= e(old('fats_g')) ?>">
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit">Save Meal</button>
                </div>
            </form>
        </article>
        <article class="list-panel meal-today-panel" data-meal-history-panel>
            <div class="meal-today-heading">
                <h2>Today's Meals</h2>
                <div>
                    <button class="meal-history-link" type="button" data-meal-history-open><i class="ti ti-history" aria-hidden="true"></i>Meal History</button>
                    <span class="meal-count-chip" data-meal-count><?= e((string) $mealCount) ?> LOGGED</span>
                </div>
            </div>
            <div class="meal-summary-grid">
                <div class="stat-card meal-summary-card <?= $mealCount === 0 ? 'is-empty' : '' ?>">
                    <span class="stat-label">CAL</span>
                    <p class="stat-value" data-meal-total-calories><?= e((string) $totalCalories) ?></p>
                </div>
                <div class="stat-card meal-summary-card <?= $mealCount === 0 ? 'is-empty' : '' ?>">
                    <span class="stat-label">PROT</span>
                    <p class="stat-value"><span data-meal-total-protein><?= e($mealCount === 0 ? '0' : number_format($totalProtein, 1)) ?></span><span class="unit"> g</span></p>
                </div>
                <div class="stat-card meal-summary-card <?= $mealCount === 0 ? 'is-empty' : '' ?>">
                    <span class="stat-label">CARB</span>
                    <p class="stat-value"><span data-meal-total-carbs><?= e($mealCount === 0 ? '0' : number_format($totalCarbs, 1)) ?></span><span class="unit"> g</span></p>
                </div>
                <div class="stat-card meal-summary-card <?= $mealCount === 0 ? 'is-empty' : '' ?>">
                    <span class="stat-label">FAT</span>
                    <p class="stat-value"><span data-meal-total-fats><?= e($mealCount === 0 ? '0' : number_format($totalFats, 1)) ?></span><span class="unit"> g</span></p>
                </div>
            </div>
            <div class="meal-history-list" data-meal-history-list <?= !$meals ? 'hidden' : '' ?>>
                <?php foreach ($meals as $index => $meal): ?>
                    <?php $source = $mealSource($meal); ?>
                    <article class="meal-history-card" data-meal-card>
                        <div class="meal-history-card__top">
                            <div class="meal-history-title">
                                <span class="meal-number-badge"><?= e((string) ($index + 1)) ?></span>
                                <div>
                                    <div class="meal-history-name-row">
                                        <strong><?= e($meal['meal_name']) ?><?= $isSingaporeMeal($meal) ? ' SG' : '' ?></strong>
                                        <span class="meal-source-pill <?= $source === 'scanned' ? 'ai' : 'manual' ?>"><i class="ti <?= $source === 'scanned' ? 'ti-sparkles' : 'ti-pencil' ?>" aria-hidden="true"></i><?= $source === 'scanned' ? 'AI SCAN' : 'MANUAL' ?></span>
                                    </div>
                                    <span class="meal-serving-chip">Serving <?= e($formatServing($meal)) ?></span>
                                </div>
                            </div>
                            <div class="meal-history-meta">
                                <time datetime="<?= e($meal['meal_date']) ?>"><?= e($formatMealDate($meal['meal_date'] ?? null)) ?></time>
                                <small><?= e($formatMealTime($meal['meal_time'] ?? null)) ?></small>
                            </div>
                        </div>
                        <div class="meal-history-card__stats">
                            <div><small>CAL</small><b><?= e((string) $meal['calories']) ?></b></div>
                            <div><small>PROTEIN</small><b><?= $meal['protein_g'] !== null ? e(number_format((float) $meal['protein_g'], 1)) . '<span>g</span>' : '--' ?></b></div>
                            <div><small>CARBS</small><b><?= $meal['carbs_g'] !== null ? e(number_format((float) $meal['carbs_g'], 1)) . '<span>g</span>' : '--' ?></b></div>
                            <div><small>FATS</small><b><?= $meal['fats_g'] !== null ? e(number_format((float) $meal['fats_g'], 1)) . '<span>g</span>' : '--' ?></b></div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            <div class="meal-today-empty" data-meal-history-empty <?= $meals ? 'hidden' : '' ?>>
                <span><i class="ti ti-bowl-spoon" aria-hidden="true"></i></span>
                <h3>No meals logged today</h3>
                <p>Scan or upload your first meal to start tracking</p>
            </div>
        </article>
    </div>
</section>

<div class="meal-history-modal" data-meal-history-modal hidden>
    <div class="meal-history-modal__backdrop" data-meal-history-close></div>
    <section class="meal-history-modal__panel" role="dialog" aria-modal="true" aria-labelledby="meal-history-modal-title">
        <header class="meal-history-modal__header">
            <div>
                <i class="ti ti-history" aria-hidden="true"></i>
                <h2 id="meal-history-modal-title">Meal History</h2>
            </div>
            <button type="button" aria-label="Close" data-meal-history-close><i class="ti ti-x" aria-hidden="true"></i></button>
        </header>
        <p class="meal-history-modal__subtitle">Pick a date to view what you logged that day</p>
        <div class="meal-history-filter">
            <label for="meal_history_date">DATE</label>
            <div>
                <input id="meal_history_date" type="date" value="<?= e($todayMachine) ?>" data-meal-history-date>
                <i class="ti ti-calendar" aria-hidden="true"></i>
            </div>
        </div>
        <div data-meal-history-modal-content></div>
    </section>
</div>

<div class="exercise-modal meal-scan-modal" data-meal-scan-modal hidden>
    <div class="exercise-modal__backdrop" data-meal-scan-close></div>
    <section class="exercise-modal__panel meal-scan-modal__panel" role="dialog" aria-modal="true" aria-labelledby="meal-scan-title">
        <header class="meal-scan-modal__header">
            <div>
                <span class="meal-ai-powered"><span></span></span>
                <div>
                    <h3 id="meal-scan-title" data-meal-modal-title>Scan Your Meal</h3>
                    <p data-meal-modal-subtitle>Position the food in frame</p>
                </div>
            </div>
            <button class="exercise-modal__close" type="button" aria-label="Close meal scanner" data-meal-scan-close><i class="ti ti-x" aria-hidden="true"></i></button>
        </header>

        <div class="meal-scan-toast" data-meal-scan-toast hidden></div>

        <div class="meal-scan-body" data-meal-scan-root>
            <div class="meal-scan-mode-toggle" role="tablist" aria-label="Meal scan mode">
                <button class="active" type="button" data-meal-scan-mode="camera"><i class="ti ti-camera" aria-hidden="true"></i>Camera</button>
                <button type="button" data-meal-scan-mode="upload"><i class="ti ti-photo" aria-hidden="true"></i>Upload</button>
            </div>

            <div class="meal-scan-status" data-meal-scan-status hidden></div>

            <section class="meal-scan-mode-panel" data-meal-camera-panel>
                <div class="meal-camera-frame">
                    <video data-meal-camera-video autoplay playsinline muted></video>
                    <div class="meal-camera-empty" data-meal-camera-empty>Camera preview will appear here.</div>
                    <span class="meal-live-badge"><span></span>LIVE</span>
                    <span class="meal-frame-corner meal-frame-corner--tl"></span>
                    <span class="meal-frame-corner meal-frame-corner--tr"></span>
                    <span class="meal-frame-corner meal-frame-corner--bl"></span>
                    <span class="meal-frame-corner meal-frame-corner--br"></span>
                    <span class="meal-camera-instruction">Center the food in frame</span>
                </div>
                <div class="meal-scan-actions">
                    <button class="button secondary" type="button" data-meal-camera-start>Start Camera</button>
                    <button class="meal-shutter-button" type="button" aria-label="Capture meal photo" data-meal-camera-capture disabled></button>
                </div>
                <p class="meal-camera-help">Tap the white circle to capture</p>
            </section>

            <section class="meal-scan-mode-panel" data-meal-upload-panel hidden>
                <label class="meal-upload-drop" for="meal_scan_file">
                    <span class="meal-upload-icon"><i class="ti ti-photo-up" aria-hidden="true"></i></span>
                    <strong>Drop your meal photo here</strong>
                    <small>or click anywhere in this box to browse</small>
                    <b><i class="ti ti-upload" aria-hidden="true"></i>Choose File</b>
                    <em><span><i class="ti ti-file-type-jpg" aria-hidden="true"></i>JPG, PNG, WebP</span><span><i class="ti ti-database" aria-hidden="true"></i>Max 10 MB</span></em>
                </label>
                <input id="meal_scan_file" class="sr-only-file" type="file" accept="image/*" data-meal-file-input>
                <p class="meal-upload-tip"><i class="ti ti-bulb" aria-hidden="true"></i><strong>Tip:</strong>  Clear, well-lit photos with the meal centered give the best AI predictions.</p>
            </section>

            <canvas data-meal-canvas hidden></canvas>

            <section class="meal-preview-panel" data-meal-preview-panel hidden>
                <img src="" alt="Selected meal preview" data-meal-preview>
                <div class="meal-analyzing" data-meal-analyzing hidden><span></span>Analyzing meal...</div>
            </section>

            <section class="meal-result-panel" data-meal-result-panel hidden>
                <div class="meal-result-hero">
                    <img src="" alt="Analyzed meal photo" data-meal-result-image>
                    <div class="meal-result-overlay"></div>
                    <button class="meal-result-retake" type="button" data-meal-reset><i class="ti ti-refresh" aria-hidden="true"></i>Retake</button>
                    <div class="meal-result-copy">
                        <div class="meal-result-pills">
                            <span class="meal-match-pill" data-meal-confidence-label>AI prediction</span>
                            <span class="meal-region-pill" data-meal-sg-badge hidden>🇸🇬 Singapore</span>
                        </div>
                        <h4 data-meal-food-name></h4>
                        <p data-meal-food-description></p>
                    </div>
                </div>

                <div class="meal-serving-picker">
                    <div class="meal-serving-picker__heading">
                        <strong>How much did you eat?</strong>
                        <span data-meal-per-100>Per 100g</span>
                    </div>
                    <div class="meal-serving-options" data-meal-serving-options>
                        <button type="button" data-serving-choice="small"><strong>Small</strong><span data-serving-small>125g</span></button>
                        <button type="button" class="active" data-serving-choice="regular"><strong>Regular</strong><span data-serving-regular>250g</span></button>
                        <button type="button" data-serving-choice="large"><strong>Large</strong><span data-serving-large>325g</span></button>
                        <button type="button" data-serving-choice="custom"><strong>Custom</strong><span>—</span></button>
                    </div>
                    <div class="meal-custom-serving" data-meal-custom-serving hidden>
                        <label for="scan_serving_grams">Custom serving (g)</label>
                        <input id="scan_serving_grams" type="number" min="1" max="10000" step="1" value="250" data-meal-serving-grams>
                    </div>
                </div>

                <div class="meal-nutrition-card">
                    <span data-meal-nutrition-label>NUTRITION FOR 250G</span>
                    <div class="meal-nutrition-values">
                        <div class="meal-calorie-display">
                            <small>CALORIES</small>
                            <strong><span data-meal-total-calories>0</span><em>kcal</em></strong>
                        </div>
                        <div>
                            <small>PROTEIN</small>
                            <strong><span data-meal-total-protein>0</span><em>g</em></strong>
                        </div>
                        <div>
                            <small>CARBS</small>
                            <strong><span data-meal-total-carbs>0</span><em>g</em></strong>
                        </div>
                        <div>
                            <small>FATS</small>
                            <strong><span data-meal-total-fats>0</span><em>g</em></strong>
                        </div>
                    </div>
                    <p data-meal-source></p>
                </div>

                <div class="meal-date-time-row">
                    <label class="meal-date-pill" data-meal-date-trigger><i class="ti ti-calendar" aria-hidden="true"></i><span data-meal-date-label>Today</span><input type="date" value="<?= e($todayMachine) ?>" data-meal-save-date></label>
                    <label class="meal-date-pill" data-meal-time-trigger><i class="ti ti-clock" aria-hidden="true"></i><span data-meal-time-label><?= e(strtolower($today->format('g:i a'))) ?></span><input type="time" value="<?= e($currentTime) ?>" data-meal-save-time></label>
                </div>

                <div class="meal-scan-actions">
                    <button class="meal-add-button" type="button" data-meal-save><i class="ti ti-check" aria-hidden="true"></i>Add to today's meals</button>
                </div>
                <button class="meal-manual-instead" type="button" data-meal-manual-instead>Wrong food? Enter manually instead</button>
            </section>
        </div>
    </section>
</div>
<?php
clear_old();
require_once __DIR__ . '/includes/footer.php';

