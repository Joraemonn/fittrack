<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Please log in to save scanned meals.']);
    exit;
}

if (!is_post()) {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Only POST requests are allowed.']);
    exit;
}

$payload = json_decode(file_get_contents('php://input') ?: '', true);

if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid meal scan payload.']);
    exit;
}

$user = current_user();
$userId = (int) $user['id'];
$timezoneName = (string) ($user['timezone'] ?? 'Asia/Singapore');

if (!in_array($timezoneName, timezone_identifiers_list(), true)) {
    $timezoneName = 'Asia/Singapore';
}

$now = new DateTime('now', new DateTimeZone($timezoneName));
$mealDate = trim((string) ($payload['meal_date'] ?? $now->format('Y-m-d')));
$mealTime = trim((string) ($payload['meal_time'] ?? $now->format('H:i')));
$foodName = trim((string) ($payload['food_name'] ?? ''));
$foodDisplay = trim((string) ($payload['food_display'] ?? $foodName));
$calories = $payload['calories'] ?? null;
$protein = $payload['protein_g'] ?? null;
$carbs = $payload['carbs_g'] ?? null;
$fats = $payload['fats_g'] ?? null;
$servingGrams = $payload['serving_grams'] ?? null;
$aiConfidence = $payload['ai_confidence'] ?? null;

$errors = [];

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $mealDate)) {
    $errors['meal_date'] = 'Choose a valid date.';
}

if ($mealTime !== '' && !preg_match('/^\d{2}:\d{2}$/', $mealTime)) {
    $errors['meal_time'] = 'Choose a valid time.';
}

if ($foodDisplay === '') {
    $errors['food_display'] = 'Food name is required.';
}

if (!is_numeric($calories) || (float) $calories < 0) {
    $errors['calories'] = 'Calories must be zero or more.';
}

foreach (['protein_g' => $protein, 'carbs_g' => $carbs, 'fats_g' => $fats] as $field => $value) {
    if ($value !== null && $value !== '' && (!is_numeric($value) || (float) $value < 0)) {
        $errors[$field] = 'Nutrition values must be zero or more.';
    }
}

if ($servingGrams !== null && $servingGrams !== '' && (!is_numeric($servingGrams) || (float) $servingGrams <= 0 || (float) $servingGrams > 10000)) {
    $errors['serving_grams'] = 'Serving size must be between 1 and 10000 grams.';
}

if ($aiConfidence !== null && $aiConfidence !== '' && (!is_numeric($aiConfidence) || (float) $aiConfidence < 0 || (float) $aiConfidence > 100)) {
    $errors['ai_confidence'] = 'AI confidence must be between 0 and 100.';
}

if ($errors) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Please check the scanned meal details.', 'errors' => $errors]);
    exit;
}

try {
    $pdo = db();
    $columns = [];

    foreach ($pdo->query('SHOW COLUMNS FROM meals') as $column) {
        $columns[$column['Field']] = true;
    }

    $insertColumns = [
        'user_id',
        'meal_date',
        'meal_time',
        'meal_name',
        'calories',
        'protein_g',
        'carbs_g',
        'fats_g',
    ];
    $params = [
        'user_id' => $userId,
        'meal_date' => $mealDate,
        'meal_time' => $mealTime !== '' ? $mealTime : null,
        'meal_name' => $foodDisplay,
        'calories' => (int) round((float) $calories),
        'protein_g' => $protein !== null && $protein !== '' ? round((float) $protein, 2) : null,
        'carbs_g' => $carbs !== null && $carbs !== '' ? round((float) $carbs, 2) : null,
        'fats_g' => $fats !== null && $fats !== '' ? round((float) $fats, 2) : null,
    ];

    $optionalValues = [
        'food_name' => $foodName !== '' ? $foodName : strtolower(str_replace(' ', '_', $foodDisplay)),
        'food_display' => $foodDisplay,
        'serving_grams' => $servingGrams !== null && $servingGrams !== '' ? round((float) $servingGrams, 1) : null,
        'source' => 'scanned',
        'ai_confidence' => $aiConfidence !== null && $aiConfidence !== '' ? round((float) $aiConfidence, 2) : null,
    ];

    foreach ($optionalValues as $column => $value) {
        if (isset($columns[$column])) {
            $insertColumns[] = $column;
            $params[$column] = $value;
        }
    }

    $placeholders = array_map(fn (string $column): string => ':' . $column, $insertColumns);
    $quotedColumns = array_map(fn (string $column): string => '`' . str_replace('`', '``', $column) . '`', $insertColumns);
    $sql = sprintf(
        'INSERT INTO meals (%s) VALUES (%s)',
        implode(', ', $quotedColumns),
        implode(', ', $placeholders)
    );

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $mealId = (int) $pdo->lastInsertId();

    $selectColumns = [
        'id',
        'meal_date',
        'meal_time',
        'meal_name',
        'calories',
        'protein_g',
        'carbs_g',
        'fats_g',
    ];

    foreach (['food_name', 'food_display', 'serving_grams', 'source', 'ai_confidence'] as $column) {
        if (isset($columns[$column])) {
            $selectColumns[] = $column;
        }
    }

    $quotedSelectColumns = array_map(fn (string $column): string => '`' . str_replace('`', '``', $column) . '`', $selectColumns);
    $mealStmt = $pdo->prepare(sprintf(
        'SELECT %s FROM meals WHERE id = :id AND user_id = :user_id LIMIT 1',
        implode(', ', $quotedSelectColumns)
    ));
    $mealStmt->execute([
        'id' => $mealId,
        'user_id' => $userId,
    ]);
    $meal = $mealStmt->fetch();

    echo json_encode([
        'success' => true,
        'meal_id' => $mealId,
        'meal' => $meal,
    ]);
} catch (PDOException $exception) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Unable to save the scanned meal right now.']);
}
