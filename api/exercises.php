<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json; charset=utf-8');

$apiMuscles = [
    'abdominals',
    'abductors',
    'adductors',
    'biceps',
    'calves',
    'chest',
    'forearms',
    'glutes',
    'hamstrings',
    'lats',
    'lower_back',
    'middle_back',
    'neck',
    'quadriceps',
    'traps',
    'triceps',
];
$apiTypes = [
    'cardio',
    'olympic_weightlifting',
    'plyometrics',
    'powerlifting',
    'strength',
    'stretching',
    'strongman',
];
$apiNameSeedsByMuscle = [
    'abdominals' => ['crunch', 'plank', 'raise', 'twist', 'sit', 'rollout', 'jackknife', 'mountain climber', 'leg raise', 'knee raise'],
    'abductors' => ['abduction', 'side', 'hip', 'band', 'cable'],
    'adductors' => ['adduction', 'hip', 'side', 'cable', 'machine'],
    'biceps' => ['curl', 'preacher', 'hammer', 'concentration', 'cable'],
    'calves' => ['calf', 'raise', 'seated', 'standing', 'donkey'],
    'chest' => ['press', 'bench', 'fly', 'push', 'dip', 'crossover'],
    'forearms' => ['curl', 'wrist', 'reverse', 'grip', 'carry'],
    'glutes' => ['glute', 'hip', 'thrust', 'bridge', 'kickback', 'lunge'],
    'hamstrings' => ['curl', 'deadlift', 'romanian', 'good morning', 'glute ham'],
    'lats' => ['pulldown', 'pullup', 'pull up', 'row', 'chin'],
    'lower_back' => ['deadlift', 'extension', 'good morning', 'superman', 'hyperextension'],
    'middle_back' => ['row', 'pull', 'fly', 'shrug', 'face pull'],
    'neck' => ['neck', 'flexion', 'extension', 'bridge'],
    'quadriceps' => ['squat', 'press', 'extension', 'lunge', 'step', 'hack'],
    'traps' => ['shrug', 'row', 'pull', 'carry', 'upright'],
    'triceps' => ['pushdown', 'extension', 'dip', 'press', 'skullcrusher'],
];

$allowedParams = ['name', 'muscle', 'type'];
$query = [];

foreach ($allowedParams as $param) {
    $value = trim((string) ($_GET[$param] ?? ''));

    if ($value !== '') {
        $query[$param] = $value;
    }
}

if (!function_exists('curl_init')) {
    http_response_code(500);
    echo json_encode(['error' => 'cURL is not enabled in PHP.']);
    exit;
}

if ($query === []) {
    $exercises = fetch_all_muscle_exercises($apiMuscles);
} elseif (isset($query['muscle']) && !isset($query['name']) && !isset($query['type'])) {
    $exercises = fetch_muscle_catalog_exercises($query['muscle'], $apiTypes, $apiNameSeedsByMuscle);
} else {
    $exercises = fetch_exercises($query);
}

if (!is_array($exercises)) {
    http_response_code(502);
    echo json_encode(['error' => 'Invalid response from API Ninjas.']);
    exit;
}

$exercises = unique_exercises($exercises);

$normalized = array_map(static function (array $exercise): array {
    $muscle = (string) ($exercise['muscle'] ?? '');
    $muscleGroup = readable_label($muscle);
    $name = clean_exercise_name((string) ($exercise['name'] ?? ''));

    return [
        'name' => $name,
        'muscle' => $muscle,
        'muscleGroup' => $muscleGroup,
        'muscle_group' => $muscleGroup,
        'type' => readable_label((string) ($exercise['type'] ?? '')),
        'equipment' => readable_label((string) ($exercise['equipment'] ?? '')),
        'equipments' => readable_label((string) ($exercise['equipment'] ?? '')),
        'difficulty' => readable_label((string) ($exercise['difficulty'] ?? '')),
        'instructions' => (string) ($exercise['instructions'] ?? ''),
        'safety_info' => '',
        'imageUrl' => null,
    ];
}, $exercises);

echo json_encode([
    'source' => 'API Ninjas',
    'count' => count($normalized),
    'exercises' => $normalized,
], JSON_UNESCAPED_SLASHES);

function fetch_exercises(array $query): array
{
    $url = 'https://api.api-ninjas.com/v1/exercises';

    if ($query) {
        $url .= '?' . http_build_query($query);
    }

    $curl = curl_init($url);

    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => api_headers(),
        CURLOPT_TIMEOUT => 12,
    ]);

    $response = curl_exec($curl);
    $statusCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curlError = curl_error($curl);

    curl_close($curl);

    if ($response === false) {
        fail_request(502, $curlError ?: 'Unable to contact API Ninjas.');
    }

    if ($statusCode < 200 || $statusCode >= 300) {
        $decodedError = json_decode((string) $response, true);
        fail_request($statusCode ?: 502, $decodedError['error'] ?? $decodedError['message'] ?? 'API Ninjas rejected the request.');
    }

    $decoded = json_decode((string) $response, true);

    return is_array($decoded) ? $decoded : [];
}

function fetch_all_muscle_exercises(array $muscles): array
{
    global $apiTypes, $apiNameSeedsByMuscle;

    $queries = [];

    foreach ($muscles as $muscle) {
        $queries[] = ['muscle' => $muscle];

        foreach ($apiTypes as $type) {
            $queries[] = [
                'muscle' => $muscle,
                'type' => $type,
            ];
        }

        foreach ($apiNameSeedsByMuscle[$muscle] ?? [] as $seed) {
            $queries[] = [
                'muscle' => $muscle,
                'name' => $seed,
            ];
        }
    }

    return fetch_exercise_batch($queries);
}

function fetch_muscle_catalog_exercises(string $muscle, array $types, array $nameSeedsByMuscle): array
{
    $queries = [['muscle' => $muscle]];

    foreach ($types as $type) {
        $queries[] = [
            'muscle' => $muscle,
            'type' => $type,
        ];
    }

    foreach ($nameSeedsByMuscle[$muscle] ?? [] as $seed) {
        $queries[] = [
            'muscle' => $muscle,
            'name' => $seed,
        ];
    }

    return fetch_exercise_batch($queries);
}

function fetch_exercise_batch(array $queries): array
{
    if (!function_exists('curl_multi_init')) {
        $combined = [];

        foreach ($queries as $query) {
            $combined = array_merge($combined, fetch_exercises($query));
        }

        return unique_exercises($combined);
    }

    $multiHandle = curl_multi_init();
    $handles = [];

    foreach ($queries as $query) {
        $url = 'https://api.api-ninjas.com/v1/exercises?' . http_build_query($query);
        $handle = curl_init($url);

        curl_setopt_array($handle, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => api_headers(),
            CURLOPT_TIMEOUT => 12,
        ]);

        curl_multi_add_handle($multiHandle, $handle);
        $handles[] = $handle;
    }

    do {
        $status = curl_multi_exec($multiHandle, $active);

        if ($active) {
            curl_multi_select($multiHandle, 1);
        }
    } while ($active && $status === CURLM_OK);

    $combined = [];
    $firstError = null;

    foreach ($handles as $handle) {
        $response = curl_multi_getcontent($handle);
        $statusCode = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
        $curlError = curl_error($handle);

        if ($response === false || $statusCode < 200 || $statusCode >= 300) {
            $firstError ??= $curlError ?: 'API Ninjas rejected one or more exercise requests.';
        } else {
            $decoded = json_decode((string) $response, true);

            if (is_array($decoded)) {
                $combined = array_merge($combined, $decoded);
            }
        }

        curl_multi_remove_handle($multiHandle, $handle);
        curl_close($handle);
    }

    curl_multi_close($multiHandle);

    if ($combined === [] && $firstError !== null) {
        fail_request(502, $firstError);
    }

    return unique_exercises($combined);
}

function unique_exercises(array $exercises): array
{
    $unique = [];

    foreach ($exercises as $exercise) {
        if (!is_array($exercise)) {
            continue;
        }

        $name = clean_exercise_name((string) ($exercise['name'] ?? ''));

        if ($name === '' || should_exclude_exercise_name($name)) {
            continue;
        }

        $key = strtolower($name);

        if ($key === '') {
            continue;
        }

        $exercise['name'] = $name;
        $unique[$key] = $exercise;
    }

    uasort($unique, static function (array $a, array $b): int {
        return strcasecmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
    });

    return array_values($unique);
}

function should_exclude_exercise_name(string $name): bool
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

function clean_exercise_name(string $name): string
{
    $name = trim($name);
    $name = preg_replace('/\b(?:FYR|AM)\b\s*/i', '', $name) ?? $name;
    $name = preg_replace('/\s*[-–—]\s*(?:Rope\s+Attachment|Cable\s+Attachment|Bar\s+Attachment|V-Bar\s+Attachment|Straight\s+Bar\s+Attachment)\s*$/i', '', $name) ?? $name;
    $name = preg_replace('/\bRope\s+Triceps\s+Pushdown\b/i', 'Triceps Pushdown', $name) ?? $name;
    $name = preg_replace('/\s*[-–—]\s*(?:Gethin\s+Variation|Variation)\s*$/i', '', $name) ?? $name;
    $name = preg_replace('/\s*\((?:Pull\s+Through|Rope\s+Attachment|Cable\s+Attachment|Barbell|Dumbbell|Machine|Smith\s+Machine|Cable|Band)\)\s*$/i', '', $name) ?? $name;
    $name = preg_replace('/\s+/', ' ', $name) ?? $name;
    $name = trim($name, " \t\n\r\0\x0B-–—");

    return ucwords(strtolower($name));
}

function api_headers(): array
{
    return [
        'X-Api-Key: ' . API_NINJAS_KEY,
        'Accept: application/json',
    ];
}

function fail_request(int $statusCode, string $message): void
{
    http_response_code($statusCode);
    echo json_encode([
        'error' => $message,
        'status' => $statusCode,
    ]);
    exit;
}

function readable_label(string $value): string
{
    $value = str_replace(['_', '-'], ' ', trim($value));

    return $value === '' ? '' : ucwords($value);
}
