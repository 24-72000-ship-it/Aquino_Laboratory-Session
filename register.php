<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/db.php';

if (!empty($_SESSION['user_id'])) {
    redirect_to(($_SESSION['role'] ?? '') === 'admin' ? 'admin/dashboard.php' : 'resident/dashboard.php');
}

$fullName = '';
$email = '';
$address = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    $fullName = request_string($_POST, 'full_name');
    $email = strtolower(request_string($_POST, 'email'));
    $address = request_string($_POST, 'address');
    $password = request_password($_POST, 'password');
    $confirmPassword = request_password($_POST, 'confirm_password');

    if ($fullName === '' || mb_strlen($fullName) > 120) {
        $error = 'Enter your full name (up to 120 characters).';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
        $error = 'Enter a valid email address.';
    } elseif ($address === '' || mb_strlen($address) > 255) {
        $error = 'Enter your barangay address or purok (up to 255 characters).';
    } elseif (strlen($password) < 8 || strlen($password) > 72) {
        $error = 'Choose a password between 8 and 72 characters.';
    } elseif ($password !== $confirmPassword) {
        $error = 'The passwords do not match.';
    } else {
        $statement = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $statement->execute(['email' => $email]);
        if ($statement->fetch()) {
            $error = 'An account with that email already exists.';
        } else {
            $statement = $pdo->prepare(
                "INSERT INTO users (full_name, email, address, password, role)
                 VALUES (:full_name, :email, :address, :password, 'resident')"
            );
            try {
                $statement->execute([
                    'full_name' => $fullName,
                    'email' => $email,
                    'address' => $address,
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                ]);
                set_flash('Your resident account is ready. Sign in to continue.');
                redirect_to('login.php');
            } catch (PDOException $exception) {
                if ($exception->getCode() !== '23000') {
                    throw $exception;
                }
                $error = 'An account with that email already exists.';
            }
        }
    }
}

require_once __DIR__ . '/includes/auth_layout.php';
render_auth_start('Create your account', 'Join your community', 'Create your account', 'Create a resident account to share concerns and help make your barangay better.');
?>
<div class="auth-heading">
    <p class="eyebrow">A STRONGER COMMUNITY STARTS HERE</p>
    <h2>Create your account</h2>
    <p>Join your neighbors in building a better barangay.</p>
</div>
<?php if ($error !== ''): ?>
    <div class="inline-message inline-message-error" role="alert"><i class="bi bi-exclamation-circle"></i><?= escape($error) ?></div>
<?php endif; ?>
<form method="post" action="register.php" class="auth-form" data-register-form data-loading-form>
    <input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>">
    <div class="form-floating">
        <input class="form-control" id="full_name" name="full_name" type="text" maxlength="120" placeholder="Full name" autocomplete="name" value="<?= escape($fullName) ?>" required>
        <label for="full_name">Full name</label>
    </div>
    <div class="form-floating">
        <input class="form-control" id="email" name="email" type="email" maxlength="190" placeholder="name@example.com" autocomplete="email" value="<?= escape($email) ?>" required>
        <label for="email">Email address</label>
    </div>
    <div class="form-floating">
        <input class="form-control" id="address" name="address" type="text" maxlength="255" placeholder="Purok / street / barangay" autocomplete="street-address" value="<?= escape($address) ?>" required>
        <label for="address">Address / purok</label>
    </div>
    <div class="form-floating password-field">
        <input class="form-control" id="password" name="password" type="password" minlength="8" maxlength="72" placeholder="At least 8 characters" autocomplete="new-password" aria-describedby="password-strength" required>
        <label for="password">Password</label>
        <button class="password-toggle" type="button" data-password-toggle="password" aria-label="Show password"><i class="bi bi-eye"></i></button>
    </div>
    <div class="password-strength" id="password-strength" aria-live="polite">
        <div class="strength-meter" aria-hidden="true"><span></span><span></span><span></span><span></span></div>
        <span class="strength-copy">At least 8 characters</span>
    </div>
    <div class="form-floating password-field">
        <input class="form-control" id="confirm_password" name="confirm_password" type="password" minlength="8" maxlength="72" placeholder="Confirm password" autocomplete="new-password" aria-describedby="confirm-feedback" required>
        <label for="confirm_password">Confirm password</label>
        <button class="password-toggle" type="button" data-password-toggle="confirm_password" aria-label="Show password"><i class="bi bi-eye"></i></button>
    </div>
    <p class="field-feedback" id="confirm-feedback" aria-live="polite"></p>
    <button class="btn btn-primary auth-submit" type="submit"><span>Create resident account</span><i class="bi bi-arrow-up-right"></i></button>
</form>
<p class="auth-switch">Already have an account? <a href="login.php">Sign in <i class="bi bi-arrow-right"></i></a></p>
<?php render_auth_end(); ?>
