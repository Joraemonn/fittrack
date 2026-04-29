<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

require_auth();

$pageTitle = 'Dashboard';
$success = get_flash('success');
$dbError = null;
$user = current_user();

$workoutsThisWeek = 0;
$caloriesToday = 0;
$distanceThisWeek = 0;
$recentActivity = [];
$workoutLabels = [];
$workoutValues = [];
$runLabels = [];
$runDistanceValues = [];
$runDurationValues = [];
$distanceUnit = distance_unit();

try {
    $pdo = db();
    $userId = (int) $user['id'];

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM workouts WHERE user_id = :user_id AND YEARWEEK(workout_date, 1) = YEARWEEK(CURDATE(), 1)');
    $stmt->execute(['user_id' => $userId]);
    $workoutsThisWeek = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT COALESCE(SUM(calories), 0) FROM meals WHERE user_id = :user_id AND meal_date = CURDATE()');
    $stmt->execute(['user_id' => $userId]);
    $caloriesToday = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT COALESCE(SUM(distance_km), 0) FROM running_logs WHERE user_id = :user_id AND YEARWEEK(run_date, 1) = YEARWEEK(CURDATE(), 1)');
    $stmt->execute(['user_id' => $userId]);
    $distanceThisWeek = (float) $stmt->fetchColumn();

    $recentSets = [];

    $stmt = $pdo->prepare('SELECT workout_date AS activity_date, CONCAT(exercise_name, " workout") AS title, CONCAT(sets, " sets x ", reps, " reps") AS meta FROM workouts WHERE user_id = :user_id ORDER BY workout_date DESC, id DESC LIMIT 3');
    $stmt->execute(['user_id' => $userId]);
    foreach ($stmt->fetchAll() as $row) {
        $recentSets[] = ['date' => $row['activity_date'], 'title' => $row['title'], 'meta' => $row['meta']];
    }

    $stmt = $pdo->prepare('SELECT meal_date AS activity_date, CONCAT(meal_name, " meal") AS title, CONCAT(calories, " kcal") AS meta FROM meals WHERE user_id = :user_id ORDER BY meal_date DESC, meal_time DESC, id DESC LIMIT 3');
    $stmt->execute(['user_id' => $userId]);
    foreach ($stmt->fetchAll() as $row) {
        $recentSets[] = ['date' => $row['activity_date'], 'title' => $row['title'], 'meta' => $row['meta']];
    }

    $stmt = $pdo->prepare('SELECT run_date AS activity_date, "Running session" AS title, distance_km, duration_minutes FROM running_logs WHERE user_id = :user_id ORDER BY run_date DESC, id DESC LIMIT 3');
    $stmt->execute(['user_id' => $userId]);
    foreach ($stmt->fetchAll() as $row) {
        $recentSets[] = [
            'date' => $row['activity_date'],
            'title' => $row['title'],
            'meta' => format_distance((float) $row['distance_km']) . ' in ' . number_format((float) $row['duration_minutes'], 1) . ' min',
        ];
    }

    usort($recentSets, static fn(array $a, array $b): int => strcmp($b['date'], $a['date']));
    $recentActivity = array_slice($recentSets, 0, 5);

    $stmt = $pdo->prepare('SELECT DATE_FORMAT(workout_date, "%b %e") AS label, COUNT(*) AS total FROM workouts WHERE user_id = :user_id GROUP BY workout_date ORDER BY workout_date ASC LIMIT 12');
    $stmt->execute(['user_id' => $userId]);
    foreach ($stmt->fetchAll() as $row) {
        $workoutLabels[] = $row['label'];
        $workoutValues[] = (int) $row['total'];
    }

    $stmt = $pdo->prepare('SELECT DATE_FORMAT(run_date, "%b %e") AS label, distance_km, duration_minutes FROM running_logs WHERE user_id = :user_id ORDER BY run_date ASC, id ASC LIMIT 12');
    $stmt->execute(['user_id' => $userId]);
    foreach ($stmt->fetchAll() as $row) {
        $runLabels[] = $row['label'];
        $runDistanceValues[] = distance_from_km((float) $row['distance_km']);
        $runDurationValues[] = (float) $row['duration_minutes'];
    }
} catch (PDOException $exception) {
    $dbError = 'Dashboard data could not be loaded. Make sure the database is configured and imported.';
}

require_once __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
    <div class="container">
        <span class="eyebrow">Dashboard</span>
        <h1>Welcome back, <?= e($user['full_name'] ?? 'Athlete') ?>.</h1>
        <p>Your latest fitness summary lives here, from workouts and calories to running progress.</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <?php if ($success): ?>
            <div class="alert success"><?= e($success) ?></div>
        <?php endif; ?>
        <?php if ($dbError): ?>
            <div class="alert error"><?= e($dbError) ?></div>
        <?php endif; ?>

        <div class="stats-grid">
            <article class="stat-card">
                <span class="stat-label">Workouts This Week</span>
                <p class="stat-value"><?= e((string) $workoutsThisWeek) ?></p>
            </article>
            <article class="stat-card">
                <span class="stat-label">Calories Today</span>
                <p class="stat-value"><?= e((string) $caloriesToday) ?></p>
            </article>
            <article class="stat-card">
                <span class="stat-label">Distance This Week</span>
                <p class="stat-value"><?= e(format_distance($distanceThisWeek)) ?></p>
            </article>
        </div>

        <div class="dashboard-grid">
            <div class="charts-grid">
                <article class="chart-panel">
                    <h3>Workout Frequency</h3>
                    <p class="section-subtitle">See how often you trained across recent logged dates.</p>
                    <div class="chart-wrap">
                        <?php if ($workoutLabels): ?>
                            <canvas data-chart="bar" data-label="Workouts" data-labels='<?= e(json_encode($workoutLabels)) ?>' data-values='<?= e(json_encode($workoutValues)) ?>'></canvas>
                        <?php else: ?>
                            <div class="empty-state">Log workouts to generate your frequency chart.</div>
                        <?php endif; ?>
                    </div>
                </article>
                <article class="chart-panel">
                    <h3>Running Progress</h3>
                    <p class="section-subtitle">Compare distance and duration across your recent runs.</p>
                    <div class="chart-wrap">
                        <?php if ($runLabels): ?>
                            <canvas data-chart="line" data-label="Distance (<?= e($distanceUnit) ?>)" data-secondary-label="Duration (min)" data-labels='<?= e(json_encode($runLabels)) ?>' data-values='<?= e(json_encode($runDistanceValues)) ?>' data-secondary-values='<?= e(json_encode($runDurationValues)) ?>'></canvas>
                        <?php else: ?>
                            <div class="empty-state">Add running sessions to view your progress chart.</div>
                        <?php endif; ?>
                    </div>
                </article>
            </div>

            <article class="list-panel">
                <h3>Recent Activity</h3>
                <p class="section-subtitle">Your latest logged fitness actions across trackers.</p>
                <?php if ($recentActivity): ?>
                    <div class="recent-list">
                        <?php foreach ($recentActivity as $activity): ?>
                            <div class="recent-item">
                                <strong><?= e($activity['title']) ?></strong>
                                <p class="muted"><?= e($activity['meta']) ?></p>
                                <small class="muted"><?= e(date('F j, Y', strtotime($activity['date']))) ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">No activity yet. Start by logging a workout, meal, or run.</div>
                <?php endif; ?>
            </article>
        </div>
    </div>
</section>
<?php
require_once __DIR__ . '/includes/footer.php';
