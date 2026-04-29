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
$weightUnit = weight_unit();
$exerciseCatalog = exercise_catalog();

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
        <p>Log gym and exercise sessions with sets, reps, weight, and notes so you can review progress over time.</p>
    </div>
</section>

<section class="section">
    <div class="container tracker-grid">
        <article class="panel">
            <h2>Add Workout</h2>
            <?php if ($success): ?><div class="alert success"><?= e($success) ?></div><?php endif; ?>
            <?php if ($dbError): ?><div class="alert error"><?= e($dbError) ?></div><?php endif; ?>
            <form method="post">
                <div class="form-grid">
                    <div class="field">
                        <label for="workout_date">Date</label>
                        <input id="workout_date" type="date" name="workout_date" value="<?= e(old('workout_date', date('Y-m-d'))) ?>">
                        <?php if (isset($errors['workout_date'])): ?><span class="error-text"><?= e($errors['workout_date']) ?></span><?php endif; ?>
                    </div>
                    <div class="field">
                        <label for="exercise_name">Exercise Name</label>
                        <div class="exercise-picker-trigger">
                            <input id="exercise_name" name="exercise_name" placeholder="Bench Press" value="<?= e(old('exercise_name')) ?>" autocomplete="off" data-exercise-name data-exercise-open>
                            <button type="button" aria-label="Choose exercise" data-exercise-open>
                                <span aria-hidden="true"></span>
                            </button>
                        </div>
                        <?php if (isset($errors['exercise_name'])): ?><span class="error-text"><?= e($errors['exercise_name']) ?></span><?php endif; ?>
                    </div>
                    <div class="field">
                        <label for="muscle_group">Muscle Group</label>
                        <input id="muscle_group" name="muscle_group" placeholder="Chest, Legs, Back..." value="<?= e(old('muscle_group')) ?>" data-muscle-group>
                        <?php if (isset($errors['muscle_group'])): ?><span class="error-text"><?= e($errors['muscle_group']) ?></span><?php endif; ?>
                    </div>
                    <div class="exercise-preview field-full" data-exercise-preview hidden>
                        <img src="" alt="" data-exercise-image>
                        <div>
                            <span class="eyebrow">Exercise Match</span>
                            <strong data-exercise-preview-name></strong>
                            <p class="muted" data-exercise-preview-muscle></p>
                        </div>
                    </div>
                    <div class="field">
                        <label for="weight_used">Weight Used (<?= e($weightUnit) ?>)</label>
                        <input id="weight_used" type="number" step="0.01" name="weight_used" value="<?= e(old('weight_used')) ?>">
                    </div>
                    <div class="field">
                        <label for="sets">Sets</label>
                        <input id="sets" type="number" name="sets" value="<?= e(old('sets')) ?>">
                        <?php if (isset($errors['sets'])): ?><span class="error-text"><?= e($errors['sets']) ?></span><?php endif; ?>
                    </div>
                    <div class="field">
                        <label for="reps">Reps</label>
                        <input id="reps" type="number" name="reps" value="<?= e(old('reps')) ?>">
                        <?php if (isset($errors['reps'])): ?><span class="error-text"><?= e($errors['reps']) ?></span><?php endif; ?>
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
                        <button type="button" class="text-button" data-exercise-close>Cancel</button>
                        <h3 id="exercise-modal-title">Add Exercise</h3>
                        <button type="button" class="text-button accent-text" data-exercise-create>Create</button>
                    </header>
                    <div class="exercise-search-wrap">
                        <span aria-hidden="true"></span>
                        <input type="search" placeholder="Search exercises" data-exercise-search>
                        <button type="button" aria-label="Clear search" data-exercise-clear>&times;</button>
                    </div>
                    <div class="exercise-filter-row">
                        <select aria-label="Equipment filter" data-exercise-equipment>
                            <option>All Equipment</option>
                        </select>
                        <select aria-label="Muscle filter" data-exercise-muscle-filter>
                            <option value="">All Muscles</option>
                            <?php foreach (array_values(array_unique(array_column($exerciseCatalog, 'muscle_group'))) as $muscle): ?>
                                <option value="<?= e($muscle) ?>"><?= e($muscle) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="exercise-results-label">Search Results</div>
                    <div class="exercise-results" data-exercise-results></div>
                </section>
            </div>
            <script type="application/json" id="exercise-catalog-data"><?= json_encode($exerciseCatalog, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES) ?></script>
        </article>

        <article class="list-panel">
            <h2>Workout History</h2>
            <?php if ($workouts): ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Exercise</th>
                                <th>Muscle</th>
                                <th>Sets x Reps</th>
                                <th>Weight</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($workouts as $workout): ?>
                                <tr>
                                    <td><?= e(date('M j, Y', strtotime($workout['workout_date']))) ?></td>
                                    <td><?= e($workout['exercise_name']) ?></td>
                                    <td><?= e($workout['muscle_group']) ?></td>
                                    <td><?= e((string) $workout['sets']) ?> x <?= e((string) $workout['reps']) ?></td>
                                    <td><?= $workout['weight_used'] !== null ? e(format_weight((float) $workout['weight_used'])) : '--' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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
