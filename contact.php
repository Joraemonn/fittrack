<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'Contact';
$errors = [];
$dbError = null;

if (is_post()) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');

    validate_required($errors, 'name', 'Name', $name);
    validate_required($errors, 'email', 'Email', $email);
    validate_required($errors, 'message', 'Message', $message);

    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }

    if (!$errors) {
        try {
            $stmt = db()->prepare('INSERT INTO contact_messages (name, email, message) VALUES (:name, :email, :message)');
            $stmt->execute([
                'name' => $name,
                'email' => $email,
                'message' => $message,
            ]);
            set_flash('success', 'Thanks for reaching out. Your message has been saved.');
            redirect('contact.php');
        } catch (PDOException $exception) {
            $dbError = 'Unable to save your message right now. Check your database connection and try again.';
        }
    } else {
        set_old($_POST);
    }
}

$success = get_flash('success');
$pageDescription = 'Send a message to the FitTrack site owner using the contact form.';

require_once __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
    <div class="container">
        <span class="eyebrow">Contact</span>
        <h1>Questions, feedback, or project ideas?</h1>
        <p>Send a quick message through the form below. In this version, messages are stored directly in the database.</p>
    </div>
</section>

<section class="section">
    <div class="container content-grid">
        <article class="panel">
            <h2>Get in touch</h2>
            <p class="section-copy">Use this page for feedback, support, or general questions about the FitTrack project.</p>
            <p class="section-copy">Email: hello@fittrack.local</p>
        </article>
        <article class="panel">
            <?php if ($success): ?>
                <div class="alert success"><?= e($success) ?></div>
            <?php endif; ?>
            <?php if ($dbError): ?>
                <div class="alert error"><?= e($dbError) ?></div>
            <?php endif; ?>
            <form method="post">
                <div class="form-grid">
                    <div class="field">
                        <label for="name">Name</label>
                        <input id="name" name="name" value="<?= e(old('name')) ?>">
                        <?php if (isset($errors['name'])): ?><span class="error-text"><?= e($errors['name']) ?></span><?php endif; ?>
                    </div>
                    <div class="field">
                        <label for="email">Email</label>
                        <input id="email" type="email" name="email" value="<?= e(old('email')) ?>">
                        <?php if (isset($errors['email'])): ?><span class="error-text"><?= e($errors['email']) ?></span><?php endif; ?>
                    </div>
                    <div class="field-full">
                        <label for="message">Message</label>
                        <textarea id="message" name="message"><?= e(old('message')) ?></textarea>
                        <?php if (isset($errors['message'])): ?><span class="error-text"><?= e($errors['message']) ?></span><?php endif; ?>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit">Send Message</button>
                </div>
            </form>
        </article>
    </div>
</section>
<?php
clear_old();
require_once __DIR__ . '/includes/footer.php';
