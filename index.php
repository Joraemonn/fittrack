<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'Home';
$pageDescription = 'Your all-in-one personal fitness tracker for workouts, meals, and running progress.';

require_once __DIR__ . '/includes/header.php';
?>
<section class="hero">
    <div class="container hero-grid">
        <div class="hero-copy">
            <span class="eyebrow">Fitness tracking made simple</span>
            <h1>Log every rep, run and meals in one place.</h1>
            <p>FitTrack helps students, runners, and everyday gym-goers stay consistent by keeping workouts, nutrition, and progress charts together in one focused place.</p>
            <div class="cta-row">
                <a class="button" href="<?= is_logged_in() ? 'dashboard.php' : 'register.php' ?>">Start Your Journey</a>
            </div>
        </div>
        <div class="hero-card hero-art">
            <div class="hero-stripes"></div>
            <div class="hero-metrics">
                <div class="metric-box">
                    <strong>Workouts</strong>
                    <span>Track strength sessions by sets, reps, and weight.</span>
                </div>
                <div class="metric-box">
                    <strong>Meals</strong>
                    <span>Log calories, protein, carbs, and fats in seconds.</span>
                </div>
                <div class="metric-box">
                    <strong>Running</strong>
                    <span>Monitor distance, pace, duration, and calories burned.</span>
                </div>
            </div>
            <div class="hero-statement">
                <strong>Your fitness journey starts here.</strong>
                <p class="section-copy">Stay consistent, monitor your improvement, and build momentum with every entry you log.</p>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container home-detail-grid">
        <div class="panel">
            <span class="eyebrow">Why FitTrack</span>
            <h2>A focused personal fitness log for real routines</h2>
            <p class="section-copy">Whether you want to lose weight, gain muscle, or improve your pace, FitTrack makes it easy to capture the data that matters and review your progress over time.</p>
            <div class="cta-row">
                <a class="button" href="<?= is_logged_in() ? 'workouts.php' : 'login.php' ?>">Open Tracker</a>
            </div>
        </div>
        <div class="home-audience-grid">
            <article class="testimonial">
                <h3>Students</h3>
                <p class="section-copy">Stay on top of training, meals, and weekly gym consistency between classes.</p>
            </article>
            <article class="testimonial">
                <h3>Nutrition Goals</h3>
                <p class="section-copy">Track meals and daily calories in a structured, easy-to-read way.</p>
            </article>
            <article class="testimonial">
                <h3>Runners</h3>
                <p class="section-copy">Watch your distance and pace improve with clean running logs and progress visuals.</p>
            </article>
        </div>
    </div>
</section>
<?php
require_once __DIR__ . '/includes/footer.php';
