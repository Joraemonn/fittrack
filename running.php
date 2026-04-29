<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

require_auth();

$pageTitle = 'Running Tracker';
$errors = [];
$dbError = null;
$success = get_flash('success');
$runs = [];
$runLabels = [];
$runDistanceValues = [];
$runDurationValues = [];
$bestRun = null;
$longestRun = null;
$fastestRun = null;
$userId = (int) current_user()['id'];
$distanceUnit = distance_unit();

if (is_post()) {
    $runDate = trim($_POST['run_date'] ?? '');
    $distanceKm = trim($_POST['distance_km'] ?? '');
    $durationMinutes = trim($_POST['duration_minutes'] ?? '');
    $caloriesBurned = trim($_POST['calories_burned'] ?? '');

    validate_required($errors, 'run_date', 'Date', $runDate);
    validate_required($errors, 'distance_km', 'Distance', $distanceKm);
    validate_required($errors, 'duration_minutes', 'Duration', $durationMinutes);

    if ($distanceKm !== '' && (float) $distanceKm <= 0) {
        $errors['distance_km'] = 'Distance must be greater than 0.';
    }

    if ($durationMinutes !== '' && (float) $durationMinutes <= 0) {
        $errors['duration_minutes'] = 'Duration must be greater than 0.';
    }

    if (!$errors) {
        try {
            $distance = distance_to_km((float) $distanceKm);
            $duration = (float) $durationMinutes;
            $stmt = db()->prepare('INSERT INTO running_logs (user_id, run_date, distance_km, duration_minutes, pace_display, calories_burned) VALUES (:user_id, :run_date, :distance_km, :duration_minutes, :pace_display, :calories_burned)');
            $stmt->execute([
                'user_id' => $userId,
                'run_date' => $runDate,
                'distance_km' => $distance,
                'duration_minutes' => $duration,
                'pace_display' => format_pace($distance, $duration),
                'calories_burned' => $caloriesBurned !== '' ? (int) $caloriesBurned : null,
            ]);
            set_flash('success', 'Run added successfully.');
            redirect('running.php');
        } catch (PDOException $exception) {
            $dbError = 'Unable to save the running session right now.';
        }
    } else {
        set_old($_POST);
    }
}

try {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM running_logs WHERE user_id = :user_id ORDER BY run_date DESC, id DESC');
    $stmt->execute(['user_id' => $userId]);
    $runs = $stmt->fetchAll();

    $stmt = $pdo->prepare('SELECT DATE_FORMAT(run_date, "%b %e") AS label, distance_km, duration_minutes FROM running_logs WHERE user_id = :user_id ORDER BY run_date ASC, id ASC');
    $stmt->execute(['user_id' => $userId]);
    foreach ($stmt->fetchAll() as $row) {
        $runLabels[] = $row['label'];
        $runDistanceValues[] = distance_from_km((float) $row['distance_km']);
        $runDurationValues[] = (float) $row['duration_minutes'];
    }

    $bestRun = $runs[0] ?? null;
    foreach ($runs as $run) {
        if ($longestRun === null || (float) $run['distance_km'] > (float) $longestRun['distance_km']) {
            $longestRun = $run;
        }

        if ($fastestRun === null) {
            $fastestRun = $run;
            continue;
        }

        $currentPace = (float) $run['duration_minutes'] / (float) $run['distance_km'];
        $bestPace = (float) $fastestRun['duration_minutes'] / (float) $fastestRun['distance_km'];
        if ($currentPace < $bestPace) {
            $fastestRun = $run;
        }
    }
} catch (PDOException $exception) {
    $dbError = $dbError ?: 'Unable to load running history right now.';
}

require_once __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
    <div class="container">
        <span class="eyebrow">Running Tracker</span>
        <h1>Track distance, time, and pace</h1>
        <p>Save each run and quickly review your best sessions, longest distances, and pace improvements.</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="stats-grid">
            <article class="stat-card">
                <span class="stat-label">Best Run</span>
                <p class="stat-value"><?= $bestRun ? e(format_distance((float) $bestRun['distance_km'])) : '--' ?></p>
            </article>
            <article class="stat-card">
                <span class="stat-label">Longest Distance</span>
                <p class="stat-value"><?= $longestRun ? e(format_distance((float) $longestRun['distance_km'])) : '--' ?></p>
            </article>
            <article class="stat-card">
                <span class="stat-label">Fastest Pace</span>
                <p class="stat-value"><?= $fastestRun ? e(format_pace((float) $fastestRun['distance_km'], (float) $fastestRun['duration_minutes'])) : '--' ?></p>
            </article>
            <article class="stat-card">
                <span class="stat-label">Total Runs</span>
                <p class="stat-value"><?= e((string) count($runs)) ?></p>
            </article>
        </div>

        <div class="tracker-grid">
            <div>
                <article class="panel">
                    <h2>Add Running Session</h2>
                    <?php if ($success): ?><div class="alert success"><?= e($success) ?></div><?php endif; ?>
                    <?php if ($dbError): ?><div class="alert error"><?= e($dbError) ?></div><?php endif; ?>
                    <form method="post">
                        <div class="form-grid">
                            <div class="field">
                                <label for="run_date">Date</label>
                                <input id="run_date" type="date" name="run_date" value="<?= e(old('run_date', date('Y-m-d'))) ?>">
                                <?php if (isset($errors['run_date'])): ?><span class="error-text"><?= e($errors['run_date']) ?></span><?php endif; ?>
                            </div>
                            <div class="field">
                                <label for="distance_km">Distance (<?= e($distanceUnit) ?>)</label>
                                <input id="distance_km" type="number" step="0.01" name="distance_km" value="<?= e(old('distance_km')) ?>">
                                <?php if (isset($errors['distance_km'])): ?><span class="error-text"><?= e($errors['distance_km']) ?></span><?php endif; ?>
                            </div>
                            <div class="field">
                                <label for="duration_minutes">Time Taken (minutes)</label>
                                <input id="duration_minutes" type="number" step="0.01" name="duration_minutes" value="<?= e(old('duration_minutes')) ?>">
                                <?php if (isset($errors['duration_minutes'])): ?><span class="error-text"><?= e($errors['duration_minutes']) ?></span><?php endif; ?>
                            </div>
                            <div class="field">
                                <label for="calories_burned">Calories Burned</label>
                                <input id="calories_burned" type="number" name="calories_burned" value="<?= e(old('calories_burned')) ?>">
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="submit">Save Run</button>
                        </div>
                    </form>
                </article>

                <article class="chart-panel" style="margin-top: 1.25rem;">
                    <h3>Running Progress Chart</h3>
                    <div class="chart-wrap">
                        <?php if ($runLabels): ?>
                            <canvas data-chart="line" data-label="Distance (<?= e($distanceUnit) ?>)" data-secondary-label="Duration (min)" data-labels='<?= e(json_encode($runLabels)) ?>' data-values='<?= e(json_encode($runDistanceValues)) ?>' data-secondary-values='<?= e(json_encode($runDurationValues)) ?>'></canvas>
                        <?php else: ?>
                            <div class="empty-state">Your running chart will appear once you add sessions.</div>
                        <?php endif; ?>
                    </div>
                </article>
            </div>

            <article class="list-panel">
                <h2>Running History</h2>
                <?php if ($runs): ?>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Distance</th>
                                    <th>Time</th>
                                    <th>Pace</th>
                                    <th>Calories</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($runs as $run): ?>
                                    <tr>
                                        <td><?= e(date('M j, Y', strtotime($run['run_date']))) ?></td>
                                        <td><?= e(format_distance((float) $run['distance_km'])) ?></td>
                                        <td><?= e(number_format((float) $run['duration_minutes'], 1)) ?> min</td>
                                        <td><?= e(format_pace((float) $run['distance_km'], (float) $run['duration_minutes'])) ?></td>
                                        <td><?= $run['calories_burned'] !== null ? e((string) $run['calories_burned']) : '--' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">No runs logged yet. Add your first session to track progress.</div>
                <?php endif; ?>
            </article>
        </div>
    </div>
</section>
<?php
clear_old();
require_once __DIR__ . '/includes/footer.php';
