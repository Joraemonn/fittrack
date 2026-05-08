<?php

declare(strict_types=1);

function exercise_catalog(): array
{
    $illustrations = [
        'push' => exercise_illustration('push'),
        'pull' => exercise_illustration('pull'),
        'legs' => exercise_illustration('legs'),
        'hinge' => exercise_illustration('hinge'),
        'arms' => exercise_illustration('arms'),
        'core' => exercise_illustration('core'),
    ];

    return array_map(static function (array $exercise): array {
        $exercise['muscleGroup'] = $exercise['muscle_group'];
        $exercise['targetMuscles'] = [$exercise['muscle_group']];
        $exercise['secondaryMuscles'] = [];
        $exercise['bodyPart'] = $exercise['muscle_group'];
        $exercise['equipment'] = '';
        $exercise['instructions'] = '';
        $exercise['imageUrl'] = $exercise['image_url'];
        $exercise['gifUrl'] = '';

        return $exercise;
    }, [
        ['name' => 'Plank', 'muscle' => 'abdominals', 'muscle_group' => 'Abdominals', 'image_url' => $illustrations['core']],
        ['name' => 'Crunch', 'muscle' => 'abdominals', 'muscle_group' => 'Abdominals', 'image_url' => $illustrations['core']],
        ['name' => 'Hanging Knee Raise', 'muscle' => 'abdominals', 'muscle_group' => 'Abdominals', 'image_url' => $illustrations['core']],
        ['name' => 'Hip Abduction Machine', 'muscle' => 'abductors', 'muscle_group' => 'Abductors', 'image_url' => $illustrations['legs']],
        ['name' => 'Side Lying Leg Raise', 'muscle' => 'abductors', 'muscle_group' => 'Abductors', 'image_url' => $illustrations['legs']],
        ['name' => 'Hip Adduction Machine', 'muscle' => 'adductors', 'muscle_group' => 'Adductors', 'image_url' => $illustrations['legs']],
        ['name' => 'Cable Hip Adduction', 'muscle' => 'adductors', 'muscle_group' => 'Adductors', 'image_url' => $illustrations['legs']],
        ['name' => 'Bicep Curl', 'muscle' => 'biceps', 'muscle_group' => 'Biceps', 'image_url' => $illustrations['arms']],
        ['name' => 'Hammer Curl', 'muscle' => 'biceps', 'muscle_group' => 'Biceps', 'image_url' => $illustrations['arms']],
        ['name' => 'Preacher Curl', 'muscle' => 'biceps', 'muscle_group' => 'Biceps', 'image_url' => $illustrations['arms']],
        ['name' => 'Calf Raise', 'muscle' => 'calves', 'muscle_group' => 'Calves', 'image_url' => $illustrations['legs']],
        ['name' => 'Seated Calf Raise', 'muscle' => 'calves', 'muscle_group' => 'Calves', 'image_url' => $illustrations['legs']],
        ['name' => 'Bench Press', 'muscle' => 'chest', 'muscle_group' => 'Chest', 'image_url' => $illustrations['push']],
        ['name' => 'Incline Bench Press', 'muscle' => 'chest', 'muscle_group' => 'Chest', 'image_url' => $illustrations['push']],
        ['name' => 'Incline Bench Press (Smith Machine)', 'muscle' => 'chest', 'muscle_group' => 'Chest', 'image_url' => $illustrations['push']],
        ['name' => 'Incline Bench Press (Barbell)', 'muscle' => 'chest', 'muscle_group' => 'Chest', 'image_url' => $illustrations['push']],
        ['name' => 'Incline Bench Press (Dumbbell)', 'muscle' => 'chest', 'muscle_group' => 'Chest', 'image_url' => $illustrations['push']],
        ['name' => 'Pec Fly', 'muscle' => 'chest', 'muscle_group' => 'Chest', 'image_url' => $illustrations['push']],
        ['name' => 'Push Up', 'muscle' => 'chest', 'muscle_group' => 'Chest', 'image_url' => $illustrations['core']],
        ['name' => 'Reverse Curl', 'muscle' => 'forearms', 'muscle_group' => 'Forearms', 'image_url' => $illustrations['arms']],
        ['name' => 'Wrist Curl', 'muscle' => 'forearms', 'muscle_group' => 'Forearms', 'image_url' => $illustrations['arms']],
        ['name' => 'Hip Thrust', 'muscle' => 'glutes', 'muscle_group' => 'Glutes', 'image_url' => $illustrations['legs']],
        ['name' => 'Glute Bridge', 'muscle' => 'glutes', 'muscle_group' => 'Glutes', 'image_url' => $illustrations['legs']],
        ['name' => 'Leg Curl', 'muscle' => 'hamstrings', 'muscle_group' => 'Hamstrings', 'image_url' => $illustrations['legs']],
        ['name' => 'Romanian Deadlift', 'muscle' => 'hamstrings', 'muscle_group' => 'Hamstrings', 'image_url' => $illustrations['hinge']],
        ['name' => 'Lat Pulldown', 'muscle' => 'lats', 'muscle_group' => 'Lats', 'image_url' => $illustrations['pull']],
        ['name' => 'Pull Up', 'muscle' => 'lats', 'muscle_group' => 'Lats', 'image_url' => $illustrations['pull']],
        ['name' => 'Deadlift', 'muscle' => 'lower_back', 'muscle_group' => 'Lower Back', 'image_url' => $illustrations['hinge']],
        ['name' => 'Back Extension', 'muscle' => 'lower_back', 'muscle_group' => 'Lower Back', 'image_url' => $illustrations['hinge']],
        ['name' => 'Seated Cable Row', 'muscle' => 'middle_back', 'muscle_group' => 'Middle Back', 'image_url' => $illustrations['pull']],
        ['name' => 'Face Pull', 'muscle' => 'middle_back', 'muscle_group' => 'Middle Back', 'image_url' => $illustrations['pull']],
        ['name' => 'Neck Flexion', 'muscle' => 'neck', 'muscle_group' => 'Neck', 'image_url' => $illustrations['arms']],
        ['name' => 'Neck Extension', 'muscle' => 'neck', 'muscle_group' => 'Neck', 'image_url' => $illustrations['arms']],
        ['name' => 'Squat', 'muscle' => 'quadriceps', 'muscle_group' => 'Quadriceps', 'image_url' => $illustrations['legs']],
        ['name' => 'Leg Press', 'muscle' => 'quadriceps', 'muscle_group' => 'Quadriceps', 'image_url' => $illustrations['legs']],
        ['name' => 'Leg Extension', 'muscle' => 'quadriceps', 'muscle_group' => 'Quadriceps', 'image_url' => $illustrations['legs']],
        ['name' => 'Shrug', 'muscle' => 'traps', 'muscle_group' => 'Traps', 'image_url' => $illustrations['pull']],
        ['name' => 'Upright Row', 'muscle' => 'traps', 'muscle_group' => 'Traps', 'image_url' => $illustrations['pull']],
        ['name' => 'Tricep Pushdown', 'muscle' => 'triceps', 'muscle_group' => 'Triceps', 'image_url' => $illustrations['arms']],
        ['name' => 'Overhead Tricep Extension', 'muscle' => 'triceps', 'muscle_group' => 'Triceps', 'image_url' => $illustrations['arms']],
    ]);
}

function exercise_illustration(string $type): string
{
    $poses = [
        'push' => '<path d="M172 198h142M214 198l24-46h78l26 46M242 152l-14-35M314 152l14-35M218 116h126M246 226h66" /><circle cx="278" cy="92" r="20" /><path d="M278 112v38M186 178l-26 30M354 178l26 30" />',
        'pull' => '<path d="M154 84h252M208 84v58M352 84v58M210 143c28 35 104 35 132 0M276 168v64M276 232l-40 52M276 232l42 52M248 188l-52 24M304 188l52 24" /><circle cx="276" cy="138" r="22" />',
        'legs' => '<path d="M178 250h188M210 250l24-78h88l34 78M228 172l-26-58M320 172l28-58M202 114h150M272 88v42" /><circle cx="272" cy="64" r="20" /><path d="M238 250l-24 54M332 250l24 54" />',
        'hinge' => '<path d="M148 256h260M184 256v34M372 256v34M214 256l52-80 72 80M266 176l-34-46M338 256l32-60M232 130l56-30M288 100l62 34" /><circle cx="216" cy="116" r="19" />',
        'arms' => '<path d="M190 238h174M214 238l42-82h56l42 82M256 156l-22-44M312 156l24-44M234 112h102M282 84v48" /><circle cx="282" cy="60" r="20" /><path d="M208 196l-48 24M356 196l48 24M160 220v42M404 220v42" />',
        'core' => '<path d="M150 236h260M194 204l90-44 92 44M284 160l-52-54M284 160l58-52M232 106h110M198 236l42-32M370 236l-42-32" /><circle cx="214" cy="96" r="20" />',
    ];

    $pose = $poses[$type] ?? $poses['push'];
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 560 360"><defs><linearGradient id="bg" x1="0" x2="1" y1="0" y2="1"><stop stop-color="#102418"/><stop offset="1" stop-color="#06120b"/></linearGradient></defs><rect width="560" height="360" rx="36" fill="url(#bg)"/><circle cx="448" cy="82" r="78" fill="#63f08d" opacity=".12"/><circle cx="120" cy="270" r="92" fill="#2be6c8" opacity=".09"/><g fill="none" stroke="#e9fff0" stroke-width="14" stroke-linecap="round" stroke-linejoin="round" opacity=".94">' . $pose . '</g><g fill="none" stroke="#63f08d" stroke-width="12" stroke-linecap="round" opacity=".95"><path d="M132 294h296"/></g></svg>';

    return 'data:image/svg+xml;base64,' . base64_encode($svg);
}

function find_exercise(string $exerciseName): ?array
{
    $needle = strtolower(trim($exerciseName));

    foreach (exercise_catalog() as $exercise) {
        if (strtolower($exercise['name']) === $needle) {
            return $exercise;
        }
    }

    return null;
}
