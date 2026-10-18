<?php
// Step 1 of "I forgot my password": ask for the email, send a one-hour reset link.
session_start();
include('../includes/config.php');
if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/user/profile.php');
    exit();
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $u = filter_var($email, FILTER_VALIDATE_EMAIL)
        ? mysqli_fetch_assoc(mysqli_execute_query($conn, "SELECT user_id, username, email FROM users WHERE email = ? AND status = 'active'", [$email])) : null;

    if ($u && !mysqli_fetch_row(mysqli_execute_query($conn, 'SELECT 1 FROM password_resets WHERE user_id = ? AND created_at > (NOW() - INTERVAL 1 MINUTE)', [(int)$u['user_id']]))) {
        $token = bin2hex(random_bytes(32));
        mysqli_execute_query($conn, 'DELETE FROM password_resets WHERE user_id = ?', [(int)$u['user_id']]);
        mysqli_execute_query($conn, 'INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, NOW() + INTERVAL 1 HOUR)', [(int)$u['user_id'], hash('sha256', $token)]);
        $accountEmailType = 'reset';
        $accountEmail = $u['email'];
        $accountName = $u['username'];
        $accountLink = APP_URL . '/user/reset_password.php?token=' . $token;
        include('../includes/account_email.php');
    }
    $_SESSION['success'] = 'If that email has an account, we sent a reset link. It works for 1 hour.';
    header('Location: ' . BASE_URL . '/user/forgot_password.php');
    exit();
}

$pageTitle = 'Forgot password';
include('../includes/header.php');
?>
<div class="row justify-content-center">
  <div class="col-md-6 col-lg-5">
    <h1 class="mb-3">Forgot password</h1>
    <form class="panel" method="post" novalidate>
      <p class="text-secondary">Enter the email you registered with and we will send you a link to choose a new password.</p>
      <div class="mb-3">
        <label for="email" class="form-label">Email</label>
        <input type="email" class="form-control" id="email" name="email" required autocomplete="email">
      </div>
      <button type="submit" class="btn btn-primary w-100">Send reset link</button>
      <p class="mt-3 mb-0 small"><a href="<?= BASE_URL ?>/user/login.php">Back to log in</a></p>
      <?php if (MAIL_MODE !== 'smtp'): ?>
        <p class="mt-3 mb-0 small text-secondary">Developer note: email is in log mode, so nothing is really sent. The message is saved in <code>logs/mail.log</code>. See <code>includes/mail_config.sample.php</code> to switch on real email.</p>
      <?php endif; ?>
    </form>
  </div>
</div>
<?php include('../includes/footer.php'); ?>
