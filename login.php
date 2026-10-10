<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/db.php';

if (!empty($_SESSION['user_id'])) {
    redirect_to(($_SESSION['role'] ?? '') === 'admin' ? 'admin/dashboard.php' : 'resident/dashboard.php');
}

$email = '';
$error = '';
$flash = get_flash();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    $email = strtolower(request_string($_POST, 'email'));
    $password = request_password($_POST, 'password');
    $statement = $pdo->prepare('SELECT id, full_name, password, role FROM users WHERE email = :email LIMIT 1');
    $statement->execute(['email' => $email]);
    $user = $statement->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        $error = 'The email or password is incorrect. Please try again.';
    } else {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        redirect_to($user['role'] === 'admin' ? 'admin/dashboard.php' : 'resident/dashboard.php');
    }
}

require_once __DIR__ . '/includes/auth_layout.php';
render_auth_start('Welcome back', 'Your Voice. Your Barangay.', 'A kinder, stronger community starts when we listen to one another.', 'Share an idea or raise a concern. Your barangay is listening.');
?>
<div class="auth-heading">
    <p class="eyebrow">WELCOME TO BARYOCARE</p>
    <h2>Welcome back</h2>
    <p>Sign in to connect with your barangay.</p>
</div>
<?php if ($flash): ?>
    <div class="inline-message inline-message-success" role="status"><i class="bi bi-check-circle"></i><?= escape($flash['message']) ?></div>
<?php endif; ?>
<?php if ($error !== ''): ?>
    <div class="inline-message inline-message-error" role="alert"><i class="bi bi-exclamation-circle"></i><?= escape($error) ?></div>
<?php endif; ?>
<form method="post" action="login.php" class="auth-form" data-loading-form>
    <input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>">
    <div class="form-floating">
        <input class="form-control" id="email" name="email" type="email" maxlength="190" placeholder="name@example.com" autocomplete="email" value="<?= escape($email) ?>" required>
        <label for="email">Email address</label>
    </div>
    <div class="form-floating password-field">
        <input class="form-control" id="password" name="password" type="password" placeholder="Your password" autocomplete="current-password" required>
        <label for="password">Password</label>
        <button class="password-toggle" type="button" data-password-toggle="password" aria-label="Show password"><i class="bi bi-eye"></i></button>
    </div>
    <button class="btn btn-primary auth-submit" type="submit"><span>Sign in</span><i class="bi bi-arrow-up-right"></i></button>
</form>
<p class="auth-switch">New to the community? <a href="register.php">Create an account <i class="bi bi-arrow-right"></i></a></p>
<?php render_auth_end(); ?>
