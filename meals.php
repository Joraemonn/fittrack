<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

require_auth();

$pageTitle = 'Meal Tracker';
$errors = [];
$dbError = null;
$success = get_flash('success');
$meals = [];
$userId = (int) current_user()['id'];

if (is_post()) {
    $mealDate = trim($_POST['meal_date'] ?? '');
    $mealTime = trim($_POST['meal_time'] ?? '');
    $mealName = trim($_POST['meal_name'] ?? '');
    $calories = trim($_POST['calories'] ?? '');
    $protein = trim($_POST['protein_g'] ?? '');
    $carbs = trim($_POST['carbs_g'] ?? '');
    $fats = trim($_POST['fats_g'] ?? '');

    validate_required($errors, 'meal_date', 'Date', $mealDate);
    validate_required($errors, 'meal_name', 'Meal name', $mealName);
    validate_required($errors, 'calories', 'Calories', $calories);

    if ($calories !== '' && (!ctype_digit($calories) || (int) $calories < 0)) {
        $errors['calories'] = 'Calories must be a whole number.';
    }

    if (!$errors) {
        try {
            $stmt = db()->prepare('INSERT INTO meals (user_id, meal_date, meal_time, meal_name, calories, protein_g, carbs_g, fats_g) VALUES (:user_id, :meal_date, :meal_time, :meal_name, :calories, :protein_g, :carbs_g, :fats_g)');
            $stmt->execute([
                'user_id' => $userId,
                'meal_date' => $mealDate,
                'meal_time' => $mealTime !== '' ? $mealTime : null,
                'meal_name' => $mealName,
                'calories' => (int) $calories,
                'protein_g' => normalize_number($protein),
                'carbs_g' => normalize_number($carbs),
                'fats_g' => normalize_number($fats),
            ]);
            set_flash('success', 'Meal added successfully.');
            redirect('meals.php');
        } catch (PDOException $exception) {
            $dbError = 'Unable to save the meal right now.';
        }
    } else {
        set_old($_POST);
    }
}

try {
    $stmt = db()->prepare('SELECT * FROM meals WHERE user_id = :user_id ORDER BY meal_date DESC, meal_time DESC, id DESC');
    $stmt->execute(['user_id' => $userId]);
    $meals = $stmt->fetchAll();
} catch (PDOException $exception) {
    $dbError = $dbError ?: 'Unable to load meal history right now.';
}

require_once __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
    <div class="container">
        <span class="eyebrow">Meal Tracker</span>
        <h1>Stay consistent with nutrition logging</h1>
        <p>Save meals with calories and macros so you can connect your nutrition to your training progress.</p>
    </div>
</section>

<section class="section">
    <div class="container tracker-grid">
        <article class="panel">
            <h2>Add Meal</h2>
            <?php if ($success): ?><div class="alert success"><?= e($success) ?></div><?php endif; ?>
            <?php if ($dbError): ?><div class="alert error"><?= e($dbError) ?></div><?php endif; ?>
            <form method="post">
                <div class="form-grid">
                    <div class="field">
                        <label for="meal_date">Date</label>
                        <input id="meal_date" type="date" name="meal_date" value="<?= e(old('meal_date', date('Y-m-d'))) ?>">
                        <?php if (isset($errors['meal_date'])): ?><span class="error-text"><?= e($errors['meal_date']) ?></span><?php endif; ?>
                    </div>
                    <div class="field">
                        <label for="meal_time">Meal Time</label>
                        <input id="meal_time" type="time" name="meal_time" value="<?= e(old('meal_time')) ?>">
                    </div>
                    <div class="field-full">
                        <label for="meal_name">Meal Name</label>
                        <input id="meal_name" name="meal_name" placeholder="Chicken rice, protein shake..." value="<?= e(old('meal_name')) ?>">
                        <?php if (isset($errors['meal_name'])): ?><span class="error-text"><?= e($errors['meal_name']) ?></span><?php endif; ?>
                    </div>
                    <div class="field">
                        <label for="calories">Calories</label>
                        <input id="calories" type="number" name="calories" value="<?= e(old('calories')) ?>">
                        <?php if (isset($errors['calories'])): ?><span class="error-text"><?= e($errors['calories']) ?></span><?php endif; ?>
                    </div>
                    <div class="field">
                        <label for="protein_g">Protein (g)</label>
                        <input id="protein_g" type="number" step="0.01" name="protein_g" value="<?= e(old('protein_g')) ?>">
                    </div>
                    <div class="field">
                        <label for="carbs_g">Carbs (g)</label>
                        <input id="carbs_g" type="number" step="0.01" name="carbs_g" value="<?= e(old('carbs_g')) ?>">
                    </div>
                    <div class="field">
                        <label for="fats_g">Fats (g)</label>
                        <input id="fats_g" type="number" step="0.01" name="fats_g" value="<?= e(old('fats_g')) ?>">
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit">Save Meal</button>
                </div>
            </form>
        </article>
        <article class="list-panel">
            <h2>Meal History</h2>
            <?php if ($meals): ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Meal</th>
                                <th>Calories</th>
                                <th>Protein</th>
                                <th>Carbs</th>
                                <th>Fats</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($meals as $meal): ?>
                                <tr>
                                    <td><?= e(date('M j, Y', strtotime($meal['meal_date']))) ?></td>
                                    <td><?= e($meal['meal_name']) ?></td>
                                    <td><?= e((string) $meal['calories']) ?></td>
                                    <td><?= $meal['protein_g'] !== null ? e(number_format((float) $meal['protein_g'], 1)) . ' g' : '--' ?></td>
                                    <td><?= $meal['carbs_g'] !== null ? e(number_format((float) $meal['carbs_g'], 1)) . ' g' : '--' ?></td>
                                    <td><?= $meal['fats_g'] !== null ? e(number_format((float) $meal['fats_g'], 1)) . ' g' : '--' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">No meals logged yet. Add your first meal to begin tracking nutrition.</div>
            <?php endif; ?>
        </article>
    </div>
</section>
<?php
clear_old();
require_once __DIR__ . '/includes/footer.php';
