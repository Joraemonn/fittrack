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
$timezoneName = (string) (current_user()['timezone'] ?? 'Asia/Singapore');

if (!in_array($timezoneName, timezone_identifiers_list(), true)) {
    $timezoneName = 'Asia/Singapore';
}

$today = new DateTime('now', new DateTimeZone($timezoneName));
$todayDisplay = $today->format('l, j F Y');
$todayMachine = $today->format('Y-m-d');

// NEW: Generates an AI coach insight for a freshly saved run.
// Returns the insight text on success, or null on any failure (Ollama offline, timeout, malformed response, etc.).
// The page must still work even if this returns null.
function generate_run_insight(int $userId, array $latestRun, array $recentRuns, ?array $longestRun, ?array $fastestRun, int $totalRuns): ?string {
    $systemPrompt = <<<PROMPT
You are FitTrack's running coach. You write short, focused insights based on a user's recent runs.

Critical concept — pace in min/km: a LOWER number is FASTER. Example: 5:50/km is faster than 6:40/km. 7:00/km is slower than 6:00/km. Use this correctly when comparing runs.

Rules you must follow:
- Reply in exactly two short sections labelled "Recap:" and "Next run:".
- "Recap" is 1-2 sentences acknowledging how the most recent run compares to past ones — pace, distance, consistency, or progression. Lead with what's genuinely good or interesting in their data. Be accurate about whether they got faster or slower.
- "Next run" is 1 sentence suggesting a specific distance and pace for their next session, with a brief reason. If you suggest a faster pace, use a LOWER number. If you suggest an easier recovery pace, use a HIGHER number.
- Use plain numbers (e.g. "3.5 km", "6:30/km"). Never use bullet points, emojis, or headings.
- Tone: warm and encouraging, but data-grounded. Speak like a coach who actually pays attention — not a hype machine, not a robot. Make encouragement feel earned by referencing specific numbers from their data.
- Keep the entire response under 60 words.

Do not greet the user. Do not explain your reasoning. Do not start with "Great job!" or similar generic openings. Just deliver the two sections.
PROMPT;

    // Build the user-facing prompt with the runner's actual data.
    $latestPace = format_pace((float) $latestRun['distance_km'], (float) $latestRun['duration_minutes']);
    $userPrompt = "The user just logged a new run.\n\n";
    $userPrompt .= "Latest run:\n";
    $userPrompt .= "- Date: " . date('j M Y', strtotime($latestRun['run_date'])) . "\n";
    $userPrompt .= "- Distance: " . number_format((float) $latestRun['distance_km'], 1) . " km\n";
    $userPrompt .= "- Time: " . number_format((float) $latestRun['duration_minutes'], 1) . " min\n";
    $userPrompt .= "- Pace: " . $latestPace . "\n\n";

    if ($recentRuns) {
        $userPrompt .= "Previous runs (most recent first):\n";
        foreach ($recentRuns as $prev) {
            $prevPace = format_pace((float) $prev['distance_km'], (float) $prev['duration_minutes']);
            $userPrompt .= "- " . date('j M Y', strtotime($prev['run_date']))
                . ": " . number_format((float) $prev['distance_km'], 1) . " km"
                . " in " . number_format((float) $prev['duration_minutes'], 1) . " min"
                . " at " . $prevPace . "\n";
        }
        $userPrompt .= "\n";
    }

    $userPrompt .= "Personal bests:\n";
    if ($longestRun) {
        $userPrompt .= "- Longest distance: " . number_format((float) $longestRun['distance_km'], 1) . " km\n";
    }
    if ($fastestRun) {
        $fastestPace = format_pace((float) $fastestRun['distance_km'], (float) $fastestRun['duration_minutes']);
        $userPrompt .= "- Fastest pace: " . $fastestPace . "\n";
    }
    $userPrompt .= "- Total runs logged: " . $totalRuns . "\n\n";
    $userPrompt .= "Write the recap and next-run suggestion.";

    $payload = json_encode([
        'model' => 'llama3.2:3b',
        'stream' => false,
        'system' => $systemPrompt,
        'prompt' => $userPrompt,
    ]);

    if ($payload === false) {
        return null;
    }

    // Call Ollama via cURL.
    $ch = curl_init(OLLAMA_API_URL . '/api/generate');
    if ($ch === false) {
        return null;
    }

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30); // 30s ceiling — first call may load the model.
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3); // 3s to even reach Ollama.

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false || $httpCode !== 200) {
        return null;
    }

    $decoded = json_decode($response, true);
    if (!is_array($decoded) || !isset($decoded['response']) || !is_string($decoded['response'])) {
        return null;
    }

    $insight = trim($decoded['response']);
    return $insight !== '' ? $insight : null;
}

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
            $pdo = db();
            $stmt = $pdo->prepare('INSERT INTO running_logs (user_id, run_date, distance_km, duration_minutes, pace_display, calories_burned) VALUES (:user_id, :run_date, :distance_km, :duration_minutes, :pace_display, :calories_burned)');
            $stmt->execute([
                'user_id' => $userId,
                'run_date' => $runDate,
                'distance_km' => $distance,
                'duration_minutes' => $duration,
                'pace_display' => format_pace($distance, $duration),
                'calories_burned' => $caloriesBurned !== '' ? (int) $caloriesBurned : null,
            ]);

            // NEW: Generate and save the AI coach insight for this run.
            // If Ollama is offline or fails, we silently skip — the run is already saved successfully above.
            $newRunId = (int) $pdo->lastInsertId();
            $latestRun = [
                'run_date' => $runDate,
                'distance_km' => $distance,
                'duration_minutes' => $duration,
            ];

            // Pull the user's last 5 runs (excluding the one we just inserted) and personal bests for context.
            $contextStmt = $pdo->prepare('SELECT run_date, distance_km, duration_minutes FROM running_logs WHERE user_id = :user_id AND id != :new_id ORDER BY run_date DESC, id DESC LIMIT 5');
            $contextStmt->execute(['user_id' => $userId, 'new_id' => $newRunId]);
            $recentRuns = $contextStmt->fetchAll();

            $allStmt = $pdo->prepare('SELECT distance_km, duration_minutes, run_date FROM running_logs WHERE user_id = :user_id');
            $allStmt->execute(['user_id' => $userId]);
            $allRuns = $allStmt->fetchAll();

            $contextLongest = null;
            $contextFastest = null;
            foreach ($allRuns as $r) {
                if ($contextLongest === null || (float) $r['distance_km'] > (float) $contextLongest['distance_km']) {
                    $contextLongest = $r;
                }
                if ($contextFastest === null) {
                    $contextFastest = $r;
                    continue;
                }
                $currentPace = (float) $r['duration_minutes'] / (float) $r['distance_km'];
                $bestPace = (float) $contextFastest['duration_minutes'] / (float) $contextFastest['distance_km'];
                if ($currentPace < $bestPace) {
                    $contextFastest = $r;
                }
            }

            $insight = generate_run_insight(
                $userId,
                $latestRun,
                $recentRuns,
                $contextLongest,
                $contextFastest,
                count($allRuns)
            );

            if ($insight !== null) {
                $updateStmt = $pdo->prepare('UPDATE running_logs SET ai_insight = :insight WHERE id = :id AND user_id = :user_id');
                $updateStmt->execute([
                    'insight' => $insight,
                    'id' => $newRunId,
                    'user_id' => $userId,
                ]);
            }

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

// NEW: Find the most recent run that has an AI insight, to display in the Coach card.
$latestInsight = null;
$latestInsightRunDate = null;
foreach ($runs as $r) {
    if (!empty($r['ai_insight'])) {
        $latestInsight = $r['ai_insight'];
        $latestInsightRunDate = $r['run_date'];
        break;
    }
}

$pbPaceRunId = $fastestRun['id'] ?? null;
$longestRunId = $longestRun['id'] ?? null;
$latestRun = $runs[0] ?? null;
$parsedInsight = null;

if ($latestInsight && preg_match('/Recap:\s*(.*?)\s*Next run:\s*(.*)\z/is', $latestInsight, $matches) === 1) {
    $parsedInsight = [
        'recap' => trim($matches[1]),
        'next' => trim($matches[2]),
    ];
}

$formatRunNumber = static function (float $value, int $decimals = 1): string {
    $formatted = number_format($value, $decimals);

    return rtrim(rtrim($formatted, '0'), '.');
};

$paceValue = static function (float $distanceKm, float $durationMinutes): string {
    $pace = format_pace($distanceKm, $durationMinutes);

    return preg_replace('/\s*\/\s*' . preg_quote(distance_unit(), '/') . '$/', '', $pace) ?? $pace;
};

require_once __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
    <div class="container">
        <span class="eyebrow">Running Tracker</span>
        <h1>Every run becomes a record</h1>
        <div class="dashboard-hero-meta">
            <p>Every mile matters and every pace is progress</p>
            <time datetime="<?= e($todayMachine) ?>"><?= e($todayDisplay) ?></time>
        </div>
    </div>
</section>

<section class="section running-page">
    <div class="container">
        <div class="running-stats-grid">
            <article class="running-stat-card <?= $runs ? '' : 'is-empty' ?>">
                <span>Total Runs</span>
                <strong><?= $runs ? e((string) count($runs)) : '--' ?></strong>
            </article>
            <article class="running-stat-card <?= $latestRun ? '' : 'is-empty' ?>">
                <span>Latest</span>
                <strong><?= $latestRun ? e($formatRunNumber(distance_from_km((float) $latestRun['distance_km']))) : '--' ?><?php if ($latestRun): ?><small><?= e($distanceUnit) ?></small><?php endif; ?></strong>
            </article>
            <article class="running-stat-card <?= $longestRun ? '' : 'is-empty' ?>">
                <span>Longest</span>
                <strong><?= $longestRun ? e($formatRunNumber(distance_from_km((float) $longestRun['distance_km']))) : '--' ?><?php if ($longestRun): ?><small><?= e($distanceUnit) ?></small><?php endif; ?></strong>
            </article>
            <article class="running-stat-card <?= $fastestRun ? '' : 'is-empty' ?>">
                <span>Fastest Pace</span>
                <strong><?= $fastestRun ? e($paceValue((float) $fastestRun['distance_km'], (float) $fastestRun['duration_minutes'])) : '--' ?><?php if ($fastestRun): ?><small>/<?= e($distanceUnit) ?></small><?php endif; ?></strong>
            </article>
        </div>

        <article class="running-coach-card">
            <?php if ($latestInsight): ?>
                <div class="running-coach-top">
                    <span class="running-coach-pill"><i class="ti ti-sparkles" aria-hidden="true"></i>AI Coach</span>
                    <span>After your run on <?= e(date('M j, Y', strtotime((string) $latestInsightRunDate))) ?></span>
                </div>
                <div class="running-coach-body">
                    <?php if ($parsedInsight): ?>
                        <section>
                            <h2>Recap</h2>
                            <p><?= e($parsedInsight['recap']) ?></p>
                        </section>
                        <section>
                            <h2>Next Run</h2>
                            <p><?= e($parsedInsight['next']) ?></p>
                        </section>
                    <?php else: ?>
                        <section>
                            <h2>Coach Insight</h2>
                            <p><?= nl2br(e($latestInsight)) ?></p>
                        </section>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="running-coach-empty">
                    <i class="ti ti-sparkles" aria-hidden="true"></i>
                    <p>Log your first run to unlock AI coaching</p>
                </div>
            <?php endif; ?>
        </article>

        <div class="running-content-grid">
            <article class="running-panel running-form-panel">
                <h2>Add Running Session</h2>
                <?php if ($success): ?><div class="alert success"><?= e($success) ?></div><?php endif; ?>
                <?php if ($dbError): ?><div class="alert error"><?= e($dbError) ?></div><?php endif; ?>
                <form method="post">
                    <div class="running-form-grid">
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
                            <label for="duration_minutes">Time (min)</label>
                            <input id="duration_minutes" type="number" step="0.01" name="duration_minutes" value="<?= e(old('duration_minutes')) ?>">
                            <?php if (isset($errors['duration_minutes'])): ?><span class="error-text"><?= e($errors['duration_minutes']) ?></span><?php endif; ?>
                        </div>
                        <div class="field">
                            <label for="calories_burned">Calories</label>
                            <input id="calories_burned" type="number" name="calories_burned" value="<?= e(old('calories_burned')) ?>">
                        </div>
                    </div>
                    <button type="submit" class="running-save-button">Save Run</button>
                </form>
            </article>

            <article class="running-panel running-history-panel">
                <div class="running-panel-heading">
                    <h2>Running History</h2>
                    <span><?= e((string) count($runs)) ?> Logged</span>
                </div>
                <?php if ($runs): ?>
                    <div class="running-history-head">
                        <span>Date</span>
                        <span>Distance</span>
                        <span>Time</span>
                        <span>Pace</span>
                        <span>Cal</span>
                        <span></span>
                    </div>
                    <div class="running-history-list">
                        <?php foreach ($runs as $run): ?>
                            <?php
                                $badge = null;
                                if ((int) $run['id'] === (int) $pbPaceRunId) {
                                    $badge = 'pb';
                                } elseif ((int) $run['id'] === (int) $longestRunId && (int) $run['id'] !== (int) $pbPaceRunId) {
                                    $badge = 'longest';
                                }
                            ?>
                            <article class="running-history-row">
                                <strong><?= e(date('M j, Y', strtotime($run['run_date']))) ?></strong>
                                <span><?= e($formatRunNumber(distance_from_km((float) $run['distance_km']))) ?><small><?= e($distanceUnit) ?></small></span>
                                <span><?= e($formatRunNumber((float) $run['duration_minutes'])) ?><small>min</small></span>
                                <span><?= e($paceValue((float) $run['distance_km'], (float) $run['duration_minutes'])) ?><small>/<?= e($distanceUnit) ?></small></span>
                                <span><?= $run['calories_burned'] !== null ? e((string) $run['calories_burned']) : '<small>&mdash;</small>' ?></span>
                                <span class="running-badge-cell">
                                    <?php if ($badge === 'pb'): ?>
                                        <b class="running-badge running-badge--pb">PB Pace</b>
                                    <?php elseif ($badge === 'longest'): ?>
                                        <b class="running-badge running-badge--longest">Longest</b>
                                    <?php endif; ?>
                                </span>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="running-empty-state">
                        <span><i class="ti ti-run" aria-hidden="true"></i></span>
                        <h2>No runs logged yet</h2>
                        <p>Add your first session to start tracking</p>
                    </div>
                <?php endif; ?>
            </article>
        </div>

        <article class="running-panel running-chart-panel">
            <div class="running-panel-heading">
                <h2>Progress Over Time</h2>
                <div class="running-chart-legend">
                    <span><i></i>Distance (<?= e($distanceUnit) ?>)</span>
                    <span><i></i>Duration (min)</span>
                </div>
            </div>
            <div class="running-chart-wrap">
                <?php if ($runLabels): ?>
                    <canvas data-chart="line" data-chart-theme="mono" data-label="Distance (<?= e($distanceUnit) ?>)" data-secondary-label="Duration (min)" data-labels='<?= e(json_encode($runLabels)) ?>' data-values='<?= e(json_encode($runDistanceValues)) ?>' data-secondary-values='<?= e(json_encode($runDurationValues)) ?>'></canvas>
                <?php else: ?>
                    <div class="running-empty-state running-empty-state--chart">
                        <span><i class="ti ti-run" aria-hidden="true"></i></span>
                        <h2>Your progress chart will appear once you log a session</h2>
                    </div>
                <?php endif; ?>
            </div>
        </article>
    </div>
</section>
<?php
clear_old();
require_once __DIR__ . '/includes/footer.php';
