<?php

declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function set_flash(string $key, string $message): void
{
    $_SESSION['flash'][$key] = $message;
}

function get_flash(string $key): ?string
{
    if (!isset($_SESSION['flash'][$key])) {
        return null;
    }

    $message = $_SESSION['flash'][$key];
    unset($_SESSION['flash'][$key]);

    return $message;
}

function set_old(array $input): void
{
    $_SESSION['old'] = $input;
}

function old(string $key, string $default = ''): string
{
    return (string) ($_SESSION['old'][$key] ?? $default);
}

function clear_old(): void
{
    unset($_SESSION['old']);
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function require_auth(): void
{
    if (!is_logged_in()) {
        set_flash('error', 'Please log in to continue.');
        redirect('login.php');
    }
}

function require_guest(): void
{
    if (is_logged_in()) {
        redirect('dashboard.php');
    }
}

function normalize_number(?string $value): ?float
{
    if ($value === null || trim($value) === '') {
        return null;
    }

    return (float) $value;
}

function measurement_units(): string
{
    $units = current_user()['measurement_units'] ?? 'metric';

    return in_array($units, ['metric', 'imperial', 'hybrid'], true) ? $units : 'metric';
}

function distance_unit(): string
{
    return measurement_units() === 'imperial' ? 'mi' : 'km';
}

function weight_unit(): string
{
    return measurement_units() === 'metric' ? 'kg' : 'lb';
}

function distance_from_km(float $kilometers): float
{
    return measurement_units() === 'imperial' ? $kilometers * 0.621371 : $kilometers;
}

function distance_to_km(float $distance): float
{
    return measurement_units() === 'imperial' ? $distance / 0.621371 : $distance;
}

function weight_from_kg(float $kilograms): float
{
    return measurement_units() === 'metric' ? $kilograms : $kilograms * 2.20462;
}

function weight_to_kg(float $weight): float
{
    return measurement_units() === 'metric' ? $weight : $weight / 2.20462;
}

function format_distance(float $kilometers, int $decimals = 1): string
{
    return number_format(distance_from_km($kilometers), $decimals) . ' ' . distance_unit();
}

function format_weight(float $kilograms, int $decimals = 1): string
{
    return number_format(weight_from_kg($kilograms), $decimals) . ' ' . weight_unit();
}

function format_pace(float $distanceKm, float $durationMinutes): string
{
    $distance = distance_from_km($distanceKm);

    if ($distance <= 0) {
        return '0:00 / ' . distance_unit();
    }

    $paceMinutes = $durationMinutes / $distance;
    $wholeMinutes = (int) floor($paceMinutes);
    $seconds = (int) round(($paceMinutes - $wholeMinutes) * 60);

    if ($seconds === 60) {
        $wholeMinutes++;
        $seconds = 0;
    }

    return sprintf('%d:%02d / %s', $wholeMinutes, $seconds, distance_unit());
}

function validate_required(array &$errors, string $field, string $label, ?string $value): void
{
    if ($value === null || trim($value) === '') {
        $errors[$field] = $label . ' is required.';
    }
}

function refresh_session_user(PDO $pdo, int $userId): void
{
    $stmt = $pdo->prepare('SELECT id, full_name, email, age, height_cm, fitness_goal, profile_image, measurement_units, timezone FROM users WHERE id = :id');
    $stmt->execute(['id' => $userId]);
    $user = $stmt->fetch();

    if ($user) {
        $_SESSION['user'] = $user;
    }
}

function uploaded_profile_path(array $file): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Profile image upload failed.');
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    if (!in_array($extension, $allowed, true)) {
        throw new RuntimeException('Profile image must be a JPG, PNG, WEBP, or GIF.');
    }

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0777, true);
    }

    $filename = uniqid('profile_', true) . '.' . $extension;
    $targetPath = rtrim(UPLOAD_DIR, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new RuntimeException('Unable to save uploaded profile image.');
    }

    return 'uploads/profiles/' . $filename;
}

function page_is_active(string $page): bool
{
    return basename($_SERVER['PHP_SELF'] ?? '') === $page;
}
