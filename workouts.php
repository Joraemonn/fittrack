<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

require_auth();

$pageTitle = 'Workout Tracker';
$errors = [];
$dbError = null;
$success = get_flash('success');
$workouts = [];
$userId = (int) current_user()['id'];
$timezoneName = (string) (current_user()['timezone'] ?? 'Asia/Singapore');
$weightUnit = weight_unit();
$exerciseCatalog = exercise_catalog();
$apiMuscles = [
    '' => 'All Muscles',
    'abdominals' => 'Abdominals',
    'abductors' => 'Abductors',
    'adductors' => 'Adductors',
    'biceps' => 'Biceps',
    'calves' => 'Calves',
    'chest' => 'Chest',
    'forearms' => 'Forearms',
    'glutes' => 'Glutes',
    'hamstrings' => 'Hamstrings',
    'lats' => 'Lats',
    'lower_back' => 'Lower Back',
    'middle_back' => 'Middle Back',
    'neck' => 'Neck',
    'quadriceps' => 'Quadriceps',
    'traps' => 'Traps',
    'triceps' => 'Triceps',
];

if (!in_array($timezoneName, timezone_identifiers_list(), true)) {
    $timezoneName = 'Asia/Singapore';
}

$today = new DateTime('now', new DateTimeZone($timezoneName));
$todayDisplay = $today->format('l, j F Y');
$todayMachine = $today->format('Y-m-d');

if (is_post()) {
    $workoutDate = trim($_POST['workout_date'] ?? '');
    $exerciseName = trim($_POST['exercise_name'] ?? '');
    $muscleGroup = trim($_POST['muscle_group'] ?? '');
    $sets = trim($_POST['sets'] ?? '');
    $reps = trim($_POST['reps'] ?? '');
    $weightUsed = trim($_POST['weight_used'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $matchedExercise = find_exercise($exerciseName);

    if ($matchedExercise) {
        $exerciseName = $matchedExercise['name'];
        $muscleGroup = $matchedExercise['muscle_group'];
    }

    validate_required($errors, 'workout_date', 'Date', $workoutDate);
    validate_required($errors, 'exercise_name', 'Exercise name', $exerciseName);
    validate_required($errors, 'muscle_group', 'Muscle group', $muscleGroup);
    validate_required($errors, 'sets', 'Sets', $sets);
    validate_required($errors, 'reps', 'Reps', $reps);

    if ($sets !== '' && (!ctype_digit($sets) || (int) $sets <= 0)) {
        $errors['sets'] = 'Sets must be a positive whole number.';
    }

    if ($reps !== '' && (!ctype_digit($reps) || (int) $reps <= 0)) {
        $errors['reps'] = 'Reps must be a positive whole number.';
    }

    if (!$errors) {
        try {
            $stmt = db()->prepare('INSERT INTO workouts (user_id, workout_date, exercise_name, muscle_group, sets, reps, weight_used, notes) VALUES (:user_id, :workout_date, :exercise_name, :muscle_group, :sets, :reps, :weight_used, :notes)');
            $stmt->execute([
                'user_id' => $userId,
                'workout_date' => $workoutDate,
                'exercise_name' => $exerciseName,
                'muscle_group' => $muscleGroup,
                'sets' => (int) $sets,
                'reps' => (int) $reps,
                'weight_used' => $weightUsed !== '' ? weight_to_kg((float) $weightUsed) : null,
                'notes' => $notes !== '' ? $notes : null,
            ]);
            set_flash('success', 'Workout added successfully.');
            redirect('workouts.php');
        } catch (PDOException $exception) {
            $dbError = 'Unable to save the workout right now.';
        }
    } else {
        set_old($_POST);
    }
}

try {
    $stmt = db()->prepare('SELECT * FROM workouts WHERE user_id = :user_id ORDER BY workout_date DESC, id DESC');
    $stmt->execute(['user_id' => $userId]);
    $workouts = $stmt->fetchAll();
} catch (PDOException $exception) {
    $dbError = $dbError ?: 'Unable to load workout history right now.';
}

require_once __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
    <div class="container">
        <span class="eyebrow">Workout Tracker</span>
        <h1>Track your training sessions</h1>
        <div class="dashboard-hero-meta">
            <p>Log every workout and level up</p>
            <time datetime="<?= e($todayMachine) ?>"><?= e($todayDisplay) ?></time>
        </div>
    </div>
</section>

<section class="section">
    <div class="container tracker-grid">
        <article class="panel workout-form-panel">
            <h2>Add Workout</h2>
            <p class="section-subtitle">Fill in your session details</p>
            <?php if ($success): ?><div class="alert success"><?= e($success) ?></div><?php endif; ?>
            <?php if ($dbError): ?><div class="alert error"><?= e($dbError) ?></div><?php endif; ?>
            <form method="post">
                <div class="form-grid workout-form-grid">
                    <div class="field">
                        <label for="workout_date">Date</label>
                        <input id="workout_date" type="date" name="workout_date" value="<?= e(old('workout_date', date('Y-m-d'))) ?>">
                        <?php if (isset($errors['workout_date'])): ?><span class="error-text"><?= e($errors['workout_date']) ?></span><?php endif; ?>
                    </div>
                    <div class="field">
                        <label for="exercise_name">Exercise Name</label>
                        <div class="exercise-picker-trigger">
                            <input id="exercise_name" name="exercise_name" placeholder="Choose an exercise" value="<?= e(old('exercise_name')) ?>" autocomplete="off" readonly aria-haspopup="dialog" data-exercise-name data-exercise-open>
                        </div>
                        <?php if (isset($errors['exercise_name'])): ?><span class="error-text"><?= e($errors['exercise_name']) ?></span><?php endif; ?>
                    </div>
                    <div class="workout-match-context field-full" data-muscle-group-field <?= old('muscle_group') === '' ? 'hidden' : '' ?>>
                        <div class="field workout-muscle-field">
                            <label for="muscle_group">Muscle Group</label>
                            <input id="muscle_group" name="muscle_group" placeholder="Chest, Legs, Back..." value="<?= e(old('muscle_group')) ?>" data-muscle-group readonly>
                            <?php if (isset($errors['muscle_group'])): ?><span class="error-text"><?= e($errors['muscle_group']) ?></span><?php endif; ?>
                        </div>
                        <div class="exercise-preview" data-exercise-preview hidden>
                            <div>
                                <span class="eyebrow">Exercise Match</span>
                                <strong data-exercise-preview-name></strong>
                                <p class="muted" data-exercise-preview-muscle></p>
                            </div>
                        </div>
                    </div>
                    <div class="field workout-metric-field">
                        <label for="sets">Sets</label>
                        <input id="sets" type="number" name="sets" value="<?= e(old('sets')) ?>">
                        <?php if (isset($errors['sets'])): ?><span class="error-text"><?= e($errors['sets']) ?></span><?php endif; ?>
                    </div>
                    <div class="field workout-metric-field">
                        <label for="reps">Reps</label>
                        <input id="reps" type="number" name="reps" value="<?= e(old('reps')) ?>">
                        <?php if (isset($errors['reps'])): ?><span class="error-text"><?= e($errors['reps']) ?></span><?php endif; ?>
                    </div>
                    <div class="field workout-metric-field">
                        <label for="weight_used">Weight (<?= e($weightUnit) ?>)</label>
                        <input id="weight_used" type="number" step="0.01" name="weight_used" value="<?= e(old('weight_used')) ?>">
                    </div>
                    <div class="field-full">
                        <label for="notes">Notes</label>
                        <textarea id="notes" name="notes" placeholder="How the session felt, PBs, form cues..."><?= e(old('notes')) ?></textarea>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit">Save Workout</button>
                </div>
            </form>
            <div class="exercise-modal" data-exercise-modal hidden>
                <div class="exercise-modal__backdrop" data-exercise-close></div>
                <section class="exercise-modal__panel" role="dialog" aria-modal="true" aria-labelledby="exercise-modal-title">
                    <header class="exercise-modal__header">
                        <button type="button" class="exercise-modal__create" data-custom-exercise-open>Create</button>
                        <h3 id="exercise-modal-title">Add Exercise</h3>
                        <button type="button" class="exercise-modal__close" aria-label="Close exercise picker" data-exercise-close>&times;</button>
                    </header>
                    <div class="exercise-search-wrap">
                        <span aria-hidden="true"></span>
                        <input type="text" placeholder="Search exercises" data-exercise-search>
                        <button type="button" aria-label="Clear search" data-exercise-clear>&times;</button>
                    </div>
                    <div class="exercise-filter-row">
                        <div class="exercise-muscle-select" data-exercise-muscle-select>
                            <button type="button" class="exercise-muscle-button" aria-expanded="false" data-exercise-muscle-toggle>
                                <span data-exercise-muscle-label>All Muscles</span>
                                <span aria-hidden="true"></span>
                            </button>
                            <div class="exercise-muscle-menu" data-exercise-muscle-menu hidden>
                                <?php foreach ($apiMuscles as $value => $label): ?>
                                    <button type="button" class="<?= $value === '' ? 'active' : '' ?>" data-exercise-muscle-option value="<?= e($value) ?>"><?= e($label) ?></button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <p class="exercise-result-count" data-exercise-count>Loading exercises...</p>
                    <div class="exercise-results" data-exercise-results></div>
                    <p class="exercise-attribution">Exercise data powered by API Ninjas</p>
                </section>
            </div>
            <div class="exercise-modal custom-exercise-modal" data-custom-exercise-modal hidden>
                <div class="exercise-modal__backdrop" data-custom-exercise-close></div>
                <section class="exercise-modal__panel custom-exercise-modal__panel" role="dialog" aria-modal="true" aria-labelledby="custom-exercise-title">
                    <header class="exercise-modal__header">
                        <span></span>
                        <h3 id="custom-exercise-title">Create Exercise</h3>
                        <button type="button" class="exercise-modal__close" aria-label="Close create exercise form" data-custom-exercise-close>&times;</button>
                    </header>
                    <form class="custom-exercise-form" data-custom-exercise-form>
                        <p class="custom-exercise-message" data-custom-exercise-message hidden></p>
                        <div class="field">
                            <label for="custom_exercise_name">Exercise Name</label>
                            <input id="custom_exercise_name" name="exercise_name" data-custom-exercise-name>
                        </div>
                        <div class="field">
                            <label for="custom_muscle_group">Muscle Group</label>
                            <input id="custom_muscle_group" type="hidden" name="muscle_group" data-custom-exercise-muscle>
                            <button type="button" class="exercise-muscle-button custom-muscle-trigger" data-custom-muscle-open>
                                <span data-custom-muscle-label>Choose Muscle Group</span>
                                <span aria-hidden="true"></span>
                            </button>
                        </div>
                        <div class="field" data-custom-muscle-other-field hidden>
                            <label for="custom_muscle_group_other">Custom Muscle Group</label>
                            <input id="custom_muscle_group_other" data-custom-muscle-other>
                        </div>
                        <div class="form-actions">
                            <button type="button" class="button secondary" data-custom-exercise-close>Cancel</button>
                            <button type="submit">Create Exercise</button>
                        </div>
                    </form>
                </section>
            </div>
            <div class="exercise-modal custom-muscle-modal" data-custom-muscle-modal hidden>
                <div class="exercise-modal__backdrop" data-custom-muscle-close></div>
                <section class="exercise-modal__panel custom-muscle-modal__panel" role="dialog" aria-modal="true" aria-labelledby="custom-muscle-title">
                    <header class="exercise-modal__header">
                        <span></span>
                        <h3 id="custom-muscle-title">Choose Muscle Group</h3>
                        <button type="button" class="exercise-modal__close" aria-label="Close muscle group picker" data-custom-muscle-close>&times;</button>
                    </header>
                    <div class="custom-muscle-list" data-custom-muscle-menu>
                        <?php foreach ($apiMuscles as $value => $label): ?>
                            <?php if ($value === '') {
                                continue;
                            } ?>
                            <button type="button" data-custom-muscle-option value="<?= e($label) ?>"><?= e($label) ?></button>
                        <?php endforeach; ?>
                        <button type="button" data-custom-muscle-option value="__other">Others</button>
                    </div>
                </section>
            </div>
            <script>
                window.FITTRACK_EXERCISE_API = 'api/exercises.php';
                window.FITTRACK_CUSTOM_EXERCISE_API = 'api/custom_exercises.php';
            </script>
            <script type="application/json" id="exercise-catalog-data"><?= json_encode($exerciseCatalog, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES) ?></script>
        </article>

        <article class="list-panel workout-history-panel">
            <div class="workout-history-heading">
                <div>
                    <h2>Workout History</h2>
                    <p class="section-subtitle">Your recent logged sessions</p>
                </div>
                <?php if ($workouts): ?>
                    <span><?= e((string) count($workouts)) ?> <?= count($workouts) === 1 ? 'Entry' : 'Entries' ?></span>
                <?php endif; ?>
            </div>
            <?php if ($workouts): ?>
                <div class="workout-history-list">
                    <?php foreach ($workouts as $workout): ?>
                        <article class="workout-history-card">
                            <div class="workout-history-card__top">
                                <strong><?= e($workout['exercise_name']) ?></strong>
                                <time datetime="<?= e($workout['workout_date']) ?>"><?= e(date('M j, Y', strtotime($workout['workout_date']))) ?></time>
                            </div>
                            <div class="workout-history-card__stats">
                                <span>
                                    <small>Muscle</small>
                                    <b><?= e($workout['muscle_group']) ?></b>
                                </span>
                                <span>
                                    <small>Sets</small>
                                    <b><?= e((string) $workout['sets']) ?></b>
                                </span>
                                <span>
                                    <small>Reps</small>
                                    <b><?= e((string) $workout['reps']) ?></b>
                                </span>
                                <span>
                                    <small>Weight</small>
                                    <b><?= $workout['weight_used'] !== null ? e(format_weight((float) $workout['weight_used'])) : '--' ?></b>
                                </span>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">No workouts logged yet. Add your first session to get started.</div>
            <?php endif; ?>
        </article>
    </div>
</section>
<?php
clear_old();
require_once __DIR__ . '/includes/footer.php';
