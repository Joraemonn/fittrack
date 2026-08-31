<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_logged_in()) {
    json_response(['error' => 'Please log in to manage custom exercises.'], 401);
}

$userId = (int) current_user()['id'];
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    if ($method === 'GET') {
        json_response([
            'source' => 'FitTrack custom exercises',
            'exercises' => fetch_custom_exercises($userId),
        ]);
    }

    if ($method === 'POST') {
        $payload = json_decode((string) file_get_contents('php://input'), true);

        if (!is_array($payload)) {
            $payload = $_POST;
        }

        $exerciseName = normalize_custom_value((string) ($payload['exercise_name'] ?? $payload['name'] ?? ''));
        $muscleGroup = normalize_custom_value((string) ($payload['muscle_group'] ?? $payload['muscleGroup'] ?? ''));

        if ($exerciseName === '' || $muscleGroup === '') {
            json_response(['error' => 'Exercise name and muscle group are required.'], 422);
        }

        if (should_exclude_custom_exercise_name($exerciseName)) {
            json_response(['error' => 'Please create one clear solo exercise name without partner, SMR, stretches, or combined movements.'], 422);
        }

        $pdo = db();
        $duplicate = $pdo->prepare('SELECT id FROM custom_exercises WHERE user_id = :user_id AND LOWER(exercise_name) = LOWER(:exercise_name) LIMIT 1');
        $duplicate->execute([
            'user_id' => $userId,
            'exercise_name' => $exerciseName,
        ]);

        if ($duplicate->fetch()) {
            json_response(['error' => 'You already created this exercise.'], 409);
        }

        $insert = $pdo->prepare('INSERT INTO custom_exercises (user_id, exercise_name, muscle_group) VALUES (:user_id, :exercise_name, :muscle_group)');
        $insert->execute([
            'user_id' => $userId,
            'exercise_name' => $exerciseName,
            'muscle_group' => $muscleGroup,
        ]);

        json_response([
            'success' => true,
            'exercise' => [
                'id' => (int) $pdo->lastInsertId(),
                'name' => $exerciseName,
                'muscle' => muscle_key($muscleGroup),
                'muscleGroup' => $muscleGroup,
                'muscle_group' => $muscleGroup,
                'source' => 'custom',
            ],
        ], 201);
    }

    json_response(['error' => 'Method not allowed.'], 405);
} catch (PDOException $exception) {
    json_response(['error' => 'Custom exercises are unavailable right now.'], 500);
}

function fetch_custom_exercises(int $userId): array
{
    $stmt = db()->prepare('SELECT id, exercise_name, muscle_group FROM custom_exercises WHERE user_id = :user_id ORDER BY exercise_name ASC');
    $stmt->execute(['user_id' => $userId]);

    return array_map(static function (array $exercise): array {
        $muscleGroup = (string) $exercise['muscle_group'];

        return [
            'id' => (int) $exercise['id'],
            'name' => (string) $exercise['exercise_name'],
            'muscle' => muscle_key($muscleGroup),
            'muscleGroup' => $muscleGroup,
            'muscle_group' => $muscleGroup,
            'source' => 'custom',
        ];
    }, array_values(array_filter($stmt->fetchAll(), static function (array $exercise): bool {
        return !should_exclude_custom_exercise_name((string) $exercise['exercise_name']);
    })));
}

function normalize_custom_value(string $value): string
{
    $value = preg_replace('/\s+/', ' ', trim($value)) ?? '';

    return $value === '' ? '' : ucwords(strtolower($value));
}

function muscle_key(string $muscleGroup): string
{
    return strtolower((string) preg_replace('/\s+/', '_', trim($muscleGroup)));
}

function should_exclude_custom_exercise_name(string $name): bool
{
    return preg_match('/\d/', $name) === 1
        || str_contains($name, '/')
        || preg_match('/^\s*hm\b/i', $name) === 1
        || preg_match('/\bgood\s+morning\b/i', $name) === 1
        || preg_match('/\bpartner\b/i', $name) === 1
        || preg_match('/\bsmr\b/i', $name) === 1
        || preg_match('/\bstretch\b/i', $name) === 1
        || preg_match('/(?:^|[\s-])to(?:[\s-]|$)/i', $name) === 1;
}

function json_response(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}
