<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Please log in to view meal history.']);
    exit;
}

$date = trim($_GET['date'] ?? '');

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Choose a valid date.']);
    exit;
}

$selectedDate = DateTime::createFromFormat('Y-m-d', $date);

if (!$selectedDate || $selectedDate->format('Y-m-d') !== $date) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Choose a valid date.']);
    exit;
}

$user = current_user();
$userId = (int) $user['id'];
$timezoneName = (string) ($user['timezone'] ?? 'Asia/Singapore');

if (!in_array($timezoneName, timezone_identifiers_list(), true)) {
    $timezoneName = 'Asia/Singapore';
}

$today = new DateTime('today', new DateTimeZone($timezoneName));
$day = new DateTime($date, new DateTimeZone($timezoneName));
$diffDays = (int) $today->diff($day)->format('%r%a');

if ($diffDays === 0) {
    $relative = 'today';
} elseif ($diffDays === -1) {
    $relative = 'yesterday';
} elseif ($diffDays < -1 && $diffDays > -14) {
    $relative = abs($diffDays) . ' days ago';
} elseif ($diffDays <= -14) {
    $relative = max(1, (int) floor(abs($diffDays) / 7)) . ' weeks ago';
} else {
    $relative = 'in ' . $diffDays . ' days';
}

try {
    $stmt = db()->prepare('SELECT * FROM meals WHERE user_id = :user_id AND meal_date = :meal_date ORDER BY meal_time DESC, id DESC');
    $stmt->execute([
        'user_id' => $userId,
        'meal_date' => $date,
    ]);
    $meals = $stmt->fetchAll();

    $totals = [
        'calories' => array_sum(array_map(static fn (array $meal): int => (int) $meal['calories'], $meals)),
        'protein_g' => array_sum(array_map(static fn (array $meal): float => (float) ($meal['protein_g'] ?? 0), $meals)),
        'carbs_g' => array_sum(array_map(static fn (array $meal): float => (float) ($meal['carbs_g'] ?? 0), $meals)),
        'fats_g' => array_sum(array_map(static fn (array $meal): float => (float) ($meal['fats_g'] ?? 0), $meals)),
    ];

    echo json_encode([
        'success' => true,
        'date' => $date,
        'display_date' => $day->format('l, F j Y'),
        'relative' => $relative,
        'count' => count($meals),
        'totals' => $totals,
        'meals' => $meals,
    ]);
} catch (PDOException $exception) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Unable to load meals for this date.']);
}
