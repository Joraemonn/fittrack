<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

require_guest();

$pageTitle = 'Register';
$errors = [];
$dbError = null;

if (is_post()) {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $goal = trim($_POST['fitness_goal'] ?? '');

    validate_required($errors, 'full_name', 'Full name', $fullName);
    validate_required($errors, 'email', 'Email', $email);
    validate_required($errors, 'password', 'Password', $password);
    validate_required($errors, 'confirm_password', 'Confirm password', $confirmPassword);
    validate_required($errors, 'fitness_goal', 'Fitness goal', $goal);

    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }

    if ($password !== '' && strlen($password) < 6) {
        $errors['password'] = 'Password must be at least 6 characters.';
    }

    if ($password !== $confirmPassword) {
        $errors['confirm_password'] = 'Passwords do not match.';
    }

    if (!$errors) {
        try {
            $pdo = db();
            $existing = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
            $existing->execute(['email' => $email]);

            if ($existing->fetch()) {
                $errors['email'] = 'An account with that email already exists.';
            } else {
                $stmt = $pdo->prepare('INSERT INTO users (full_name, email, password_hash, fitness_goal) VALUES (:full_name, :email, :password_hash, :fitness_goal)');
                $stmt->execute([
                    'full_name' => $fullName,
                    'email' => $email,
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                    'fitness_goal' => $goal,
                ]);

                $userId = (int) $pdo->lastInsertId();
                refresh_session_user($pdo, $userId);
                set_flash('success', 'Your account has been created. Welcome to FitTrack.');
                redirect('dashboard.php');
            }
        } catch (PDOException $exception) {
            $dbError = 'Unable to register right now. Check the database setup and try again.';
        }
    }

    if ($errors) {
        set_old([
            'full_name' => $fullName,
            'email' => $email,
            'fitness_goal' => $goal,
        ]);
    }
}

require_once __DIR__ . '/includes/header.php';
?>
<section class="auth-layout">
    <div class="auth-card">
        <span class="eyebrow">Create Account</span>
        <h1>Start tracking your fitness journey</h1>
        <p class="section-copy">Create a personal FitTrack account to log workouts, meals, weight, and running progress.</p>
        <?php if ($dbError): ?>
            <div class="alert error"><?= e($dbError) ?></div>
        <?php endif; ?>
        <form method="post">
            <div class="form-grid">
                <div class="field-full">
                    <label for="full_name">Full Name</label>
                    <input id="full_name" name="full_name" value="<?= e(old('full_name')) ?>">
                    <?php if (isset($errors['full_name'])): ?><span class="error-text"><?= e($errors['full_name']) ?></span><?php endif; ?>
                </div>
                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" type="email" name="email" value="<?= e(old('email')) ?>">
                    <?php if (isset($errors['email'])): ?><span class="error-text"><?= e($errors['email']) ?></span><?php endif; ?>
                </div>
                <div class="field">
                    <label for="fitness_goal">Fitness Goal</label>
                    <input id="fitness_goal" name="fitness_goal" placeholder="Lose weight, gain muscle, improve pace..." value="<?= e(old('fitness_goal')) ?>">
                    <?php if (isset($errors['fitness_goal'])): ?><span class="error-text"><?= e($errors['fitness_goal']) ?></span><?php endif; ?>
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <input id="password" type="password" name="password">
                    <?php if (isset($errors['password'])): ?><span class="error-text"><?= e($errors['password']) ?></span><?php endif; ?>
                </div>
                <div class="field">
                    <label for="confirm_password">Confirm Password</label>
                    <input id="confirm_password" type="password" name="confirm_password">
                    <?php if (isset($errors['confirm_password'])): ?><span class="error-text"><?= e($errors['confirm_password']) ?></span><?php endif; ?>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit">Register</button>
                <span class="muted">Already have an account? <a href="login.php">Log in</a></span>
            </div>
        </form>
    </div>
</section>
<?php
clear_old();
require_once __DIR__ . '/includes/footer.php';
