<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

require_guest();

$pageTitle = 'Login';
$errors = [];
$dbError = null;
$errorFlash = get_flash('error');

if (is_post()) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    validate_required($errors, 'email', 'Email', $email);
    validate_required($errors, 'password', 'Password', $password);

    if (!$errors) {
        try {
            $stmt = db()->prepare('SELECT id, full_name, email, password_hash, age, height_cm, current_weight_kg, fitness_goal, profile_image, measurement_units, timezone FROM users WHERE email = :email LIMIT 1');
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password_hash'])) {
                $errors['email'] = 'Invalid email or password.';
            } else {
                unset($user['password_hash']);
                $_SESSION['user'] = $user;
                set_flash('success', 'Welcome back, ' . $user['full_name'] . '.');
                redirect('dashboard.php');
            }
        } catch (PDOException $exception) {
            $dbError = 'Unable to log in right now. Check the database setup and try again.';
        }
    }

    if ($errors) {
        set_old(['email' => $email]);
    }
}

$success = get_flash('success');

require_once __DIR__ . '/includes/header.php';
?>
<section class="auth-layout">
    <div class="auth-card">
        <span class="eyebrow">Welcome Back</span>
        <h1>Log in to your FitTrack account</h1>
        <p class="section-copy">Access your dashboard, recent activity, and progress charts.</p>
        <?php if ($success): ?>
            <div class="alert success"><?= e($success) ?></div>
        <?php endif; ?>
        <?php if ($errorFlash): ?>
            <div class="alert error"><?= e($errorFlash) ?></div>
        <?php endif; ?>
        <?php if ($dbError): ?>
            <div class="alert error"><?= e($dbError) ?></div>
        <?php endif; ?>
        <form method="post">
            <div class="form-grid">
                <div class="field-full">
                    <label for="email">Email</label>
                    <input id="email" type="email" name="email" value="<?= e(old('email')) ?>">
                    <?php if (isset($errors['email'])): ?><span class="error-text"><?= e($errors['email']) ?></span><?php endif; ?>
                </div>
                <div class="field-full">
                    <label for="password">Password</label>
                    <input id="password" type="password" name="password">
                    <?php if (isset($errors['password'])): ?><span class="error-text"><?= e($errors['password']) ?></span><?php endif; ?>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit">Login</button>
                <span class="muted">No account yet? <a href="register.php">Create one</a></span>
            </div>
        </form>
    </div>
</section>
<?php
clear_old();
require_once __DIR__ . '/includes/footer.php';
