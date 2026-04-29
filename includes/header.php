<?php

declare(strict_types=1);

$pageTitle = $pageTitle ?? APP_NAME;
$pageDescription = $pageDescription ?? 'Track workouts, meals, and running progress in one place.';
$authUser = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | <?= e(APP_NAME) ?></title>
    <meta name="description" content="<?= e($pageDescription) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;700;800&family=Open+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="site-shell">
    <header class="site-header">
        <div class="container nav-wrap">
            <a class="brand" href="index.php">
                <span class="brand-mark">FT</span>
                <span>
                    <strong>FitTrack</strong>
                    <small>Track Every Rep</small>
                </span>
            </a>
            <button class="nav-toggle" type="button" aria-label="Toggle navigation" data-nav-toggle>
                <span></span><span></span><span></span>
            </button>
            <div class="nav-layout" data-nav-menu>
                <?php if ($authUser): ?>
                    <nav class="site-nav main-nav">
                        <a href="dashboard.php" class="<?= page_is_active('dashboard.php') ? 'active' : '' ?>">Dashboard</a>
                        <a href="workouts.php" class="<?= page_is_active('workouts.php') ? 'active' : '' ?>">Workouts</a>
                        <a href="meals.php" class="<?= page_is_active('meals.php') ? 'active' : '' ?>">Meals</a>
                        <a href="running.php" class="<?= page_is_active('running.php') ? 'active' : '' ?>">Running</a>
                    </nav>
                    <nav class="site-nav account-nav">
                        <a href="profile.php" class="<?= page_is_active('profile.php') ? 'active' : '' ?>">Profile</a>
                        <a href="logout.php" class="nav-button">Logout</a>
                    </nav>
                <?php else: ?>
                    <nav class="site-nav account-nav">
                        <a href="login.php" class="<?= page_is_active('login.php') ? 'active' : '' ?>">Login</a>
                        <a href="register.php" class="nav-button">Register</a>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </header>
    <main>
