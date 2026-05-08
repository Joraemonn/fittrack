<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

require_auth();

$pageTitle = 'Dashboard';
$success = get_flash('success');
$dbError = null;
$user = current_user();

$workoutsThisWeek = 0;
$workoutsLastWeek = 0;
$caloriesToday = 0;
$caloriesYesterday = 0;
$distanceThisWeek = 0;
$distanceLastWeek = 0;
$latestRunDistance = null;
$previousRunDistance = null;
$latestRunDate = null;
$previousRunDate = null;
$recentActivity = [];
$workoutLabels = [];
$workoutValues = [];
$runLabels = [];
$runDistanceValues = [];
$runDurationValues = [];
$distanceUnit = distance_unit();
$timezoneName = (string) ($user['timezone'] ?? 'Asia/Singapore');

if (!in_array($timezoneName, timezone_identifiers_list(), true)) {
    $timezoneName = 'Asia/Singapore';
}

$todayDisplay = (new DateTime('now', new DateTimeZone($timezoneName)))->format('l, j F Y');

$formatTrend = static function (float $current, float $previous, string $suffix = ''): string {
    $difference = $current - $previous;

    if (abs($difference) < 0.01) {
        return 'No change vs last week';
    }

    $arrow = $difference > 0 ? '↑' : '↓';
    $amount = abs($difference);
    $formatted = abs($amount - round($amount)) < 0.01 ? number_format($amount, 0) : number_format($amount, 1);

    return $arrow . ' ' . $formatted . $suffix . ' vs last week';
};

$buildTrend = static function (float $current, float $previous, string $suffix = '', string $compareLabel = 'last week'): array {
    $difference = $current - $previous;

    if (abs($difference) < 0.01) {
        return [
            'class' => 'neutral',
            'value' => '',
            'context' => 'No change vs ' . $compareLabel,
        ];
    }

    $arrow = $difference > 0 ? '↑' : '↓';
    $amount = abs($difference);
    $formatted = abs($amount - round($amount)) < 0.01 ? number_format($amount, 0) : number_format($amount, 1);

    return [
        'class' => $difference > 0 ? 'positive' : 'negative',
        'value' => $arrow . ' ' . $formatted . $suffix,
        'context' => 'vs ' . $compareLabel,
    ];
};

try {
    $pdo = db();
    $userId = (int) $user['id'];

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM workouts WHERE user_id = :user_id AND YEARWEEK(workout_date, 1) = YEARWEEK(CURDATE(), 1)');
    $stmt->execute(['user_id' => $userId]);
    $workoutsThisWeek = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM workouts WHERE user_id = :user_id AND YEARWEEK(workout_date, 1) = YEARWEEK(CURDATE() - INTERVAL 1 WEEK, 1)');
    $stmt->execute(['user_id' => $userId]);
    $workoutsLastWeek = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT COALESCE(SUM(calories), 0) FROM meals WHERE user_id = :user_id AND meal_date = CURDATE()');
    $stmt->execute(['user_id' => $userId]);
    $caloriesToday = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT COALESCE(SUM(calories), 0) FROM meals WHERE user_id = :user_id AND meal_date = CURDATE() - INTERVAL 1 DAY');
    $stmt->execute(['user_id' => $userId]);
    $caloriesYesterday = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT COALESCE(SUM(distance_km), 0) FROM running_logs WHERE user_id = :user_id AND YEARWEEK(run_date, 1) = YEARWEEK(CURDATE(), 1)');
    $stmt->execute(['user_id' => $userId]);
    $distanceThisWeek = (float) $stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT COALESCE(SUM(distance_km), 0) FROM running_logs WHERE user_id = :user_id AND YEARWEEK(run_date, 1) = YEARWEEK(CURDATE() - INTERVAL 1 WEEK, 1)');
    $stmt->execute(['user_id' => $userId]);
    $distanceLastWeek = (float) $stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT run_date, distance_km FROM running_logs WHERE user_id = :user_id ORDER BY run_date DESC, id DESC LIMIT 2');
    $stmt->execute(['user_id' => $userId]);
    $recentRuns = $stmt->fetchAll();

    if (isset($recentRuns[0])) {
        $latestRunDate = (string) $recentRuns[0]['run_date'];
        $latestRunDistance = (float) $recentRuns[0]['distance_km'];
    }

    if (isset($recentRuns[1])) {
        $previousRunDate = (string) $recentRuns[1]['run_date'];
        $previousRunDistance = (float) $recentRuns[1]['distance_km'];
    }

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
$workoutTrend = $buildTrend($workoutsThisWeek, $workoutsLastWeek);
$calorieTrend = $caloriesYesterday > 0
    ? $buildTrend($caloriesToday, $caloriesYesterday, ' kcal', 'yesterday')
    : ['class' => 'neutral', 'value' => '', 'context' => 'No meals logged yet'];
$distanceTrend = ['class' => 'neutral', 'value' => '', 'context' => 'No recent comparison'];

if ($latestRunDate !== null && $previousRunDate !== null && $latestRunDistance !== null && $previousRunDistance !== null) {
    $latestDate = new DateTime($latestRunDate);
    $previousDate = new DateTime($previousRunDate);
    $daysBetweenRuns = (int) $previousDate->diff($latestDate)->format('%a');

    if ($daysBetweenRuns <= 7) {
        $distanceTrend = $buildTrend(distance_from_km($latestRunDistance), distance_from_km($previousRunDistance), ' ' . $distanceUnit, 'previous run');
    }
}
?>
<section class="page-hero">
    <div class="container">
        <span class="eyebrow">Dashboard</span>
        <h1>Welcome back, <?= e($user['full_name'] ?? 'Athlete') ?>.</h1>
        <div class="dashboard-hero-meta">
            <p>Everything, all in one place.</p>
            <time datetime="<?= e((new DateTime('now', new DateTimeZone($timezoneName)))->format('Y-m-d')) ?>"><?= e($todayDisplay) ?></time>
        </div>
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
                <p class="stat-trend <?= e($workoutTrend['class']) ?>">
                    <?php if ($workoutTrend['value'] !== ''): ?><span><?= e($workoutTrend['value']) ?></span><?php endif; ?>
                    <?= e($workoutTrend['context']) ?>
                </p>
            </article>
            <article class="stat-card">
                <span class="stat-label">Calories Today</span>
                <p class="stat-value"><?= e((string) $caloriesToday) ?></p>
                <p class="stat-trend <?= e($calorieTrend['class']) ?>">
                    <?php if ($calorieTrend['value'] !== ''): ?><span><?= e($calorieTrend['value']) ?></span><?php endif; ?>
                    <?= e($calorieTrend['context']) ?>
                </p>
            </article>
            <article class="stat-card">
                <span class="stat-label">Distance This Week</span>
                <p class="stat-value"><?= e(format_distance($distanceThisWeek)) ?></p>
                <p class="stat-trend <?= e($distanceTrend['class']) ?>">
                    <?php if ($distanceTrend['value'] !== ''): ?><span><?= e($distanceTrend['value']) ?></span><?php endif; ?>
                    <?= e($distanceTrend['context']) ?>
                </p>
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
                <h3>Recent Activities</h3>
                <p class="section-subtitle">Latest logged entries</p>
                <?php if ($recentActivity): ?>
                    <div class="recent-list">
                        <?php foreach ($recentActivity as $activity): ?>
                            <div class="recent-item">
                                <div>
                                    <strong><?= e($activity['title']) ?></strong>
                                    <p class="muted"><?= e($activity['meta']) ?></p>
                                </div>
                                <time class="recent-date" datetime="<?= e($activity['date']) ?>"><?= e(date('M j', strtotime($activity['date']))) ?></time>
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
