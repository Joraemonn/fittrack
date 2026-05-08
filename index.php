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
            <h1>Log every rep, run <span class="hero-amp">&amp;</span> meal in one place.</h1>
            <p>Stay consistent by keeping your workouts, nutrition, and progress all in one place.</p>
            <div class="cta-row">
                <a class="button" href="<?= is_logged_in() ? 'dashboard.php' : 'register.php' ?>">Start Your Journey</a>
            </div>
        </div>
        <div class="hero-card hero-art">
            <div class="hero-stat-grid">
                <div class="metric-box hero-stat">
                    <strong>3</strong>
                    <span>Tracking modules</span>
                </div>
                <div class="metric-box hero-stat">
                    <strong>&infin;</strong>
                    <span>Entries logged</span>
                </div>
            </div>
            <div class="hero-metrics">
                <div class="metric-box">
                    <span class="metric-icon">&#x1F3CB;</span>
                    <strong>Workouts</strong>
                    <span>Sets, reps, and weight, every session tracked.</span>
                </div>
                <div class="metric-box">
                    <span class="metric-icon">&#x1F957;</span>
                    <strong>Meals</strong>
                    <span>Calories, protein, carbs, fats in seconds.</span>
                </div>
            </div>
            <div class="hero-statement">
                <div>
                    <strong>Your fitness journey starts here.</strong>
                    <p class="section-copy">Build momentum with every entry you log.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section" id="features">
    <div class="container">
        <div class="section-heading home-section-heading">
            <h2>Everything you need<br>to stay consistent.</h2>
            <p class="section-copy">Three focused tools. One place. No noise.</p>
        </div>
        <div class="feature-showcase">
            <article class="feature-card feature-card-light">
                <span class="metric-icon">&#x1F3CB;</span>
                <strong>01</strong>
                <h3>Workouts</h3>
                <p>Track strength sessions by sets, reps, and weight. See your lifts grow over time.</p>
            </article>
            <article class="feature-card">
                <span class="metric-icon">&#x1F957;</span>
                <strong>02</strong>
                <h3>Meals</h3>
                <p>Log calories, protein, carbs, and fats. Stay on top of your nutrition daily.</p>
            </article>
            <article class="feature-card">
                <span class="metric-icon">&#x1F3C3;</span>
                <strong>03</strong>
                <h3>Running</h3>
                <p>Monitor distance, pace, duration, and calories burned on every run.</p>
            </article>
        </div>
    </div>
</section>

<section class="home-bottom">
    <div class="container home-audience-grid">
        <article class="testimonial">
            <h3>Everyday athletes</h3>
            <strong>Stay on top of training</strong>
            <p class="section-copy">Meals, workouts, and weekly gym consistency in one clear place.</p>
        </article>
        <article class="testimonial">
            <h3>Nutrition goals</h3>
            <strong>Track meals clearly</strong>
            <p class="section-copy">Daily calories in a structured, easy-to-read format.</p>
        </article>
        <article class="testimonial">
            <h3>Runners</h3>
            <strong>See your pace improve</strong>
            <p class="section-copy">Clean running logs and progress visuals over time.</p>
        </article>
    </div>
</section>
<?php
require_once __DIR__ . '/includes/footer.php';
