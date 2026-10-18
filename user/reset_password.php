<?php
// Step 2: the link from the email lands here. Valid for 1 hour, one use.
session_start();
include('../includes/config.php');

header('Referrer-Policy: no-referrer');
$token = (string)($_GET['token'] ?? $_POST['token'] ?? '');
$row = preg_match('/^[a-f0-9]{64}$/', $token)
    ? mysqli_fetch_assoc(mysqli_execute_query($conn, 'SELECT r.id, r.user_id, u.email, u.username FROM password_resets r JOIN users u ON u.user_id = r.user_id
              WHERE r.token_hash = ? AND r.expires_at > NOW()', [hash('sha256', $token)]))
    : null;

if (!$row) {
    $_SESSION['message'] = 'That reset link is invalid or has expired. Please ask for a new one.';
    header('Location: ' . BASE_URL . '/user/forgot_password.php');
    exit();
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $new = (string)($_POST['new_password'] ?? '');
    if (strlen($new) < 8) {
        $_SESSION['message'] = 'The new password needs at least 8 characters.';
    } elseif ($new !== (string)($_POST['confirm_password'] ?? '')) {
        $_SESSION['message'] = 'The two passwords do not match.';
    } else {
        mysqli_execute_query($conn, 'UPDATE users SET password = ? WHERE user_id = ?', [password_hash($new, PASSWORD_BCRYPT), (int)$row['user_id']]);
        mysqli_execute_query($conn, 'DELETE FROM password_resets WHERE user_id = ?', [(int)$row['user_id']]);
        $accountEmailType = 'changed';
        $accountEmail = $row['email'];
        $accountName = $row['username'];
        include('../includes/account_email.php');
        $_SESSION['success'] = 'Password changed. You can log in with the new one.';
        header('Location: ' . BASE_URL . '/user/login.php');
        exit();
    }
    header('Location: ' . BASE_URL . '/' . 'user/reset_password.php?token=' . $token);
    exit();
}

$pageTitle = 'Choose a new password';
include('../includes/header.php');
?>
<div class="row justify-content-center">
  <div class="col-md-6 col-lg-5">
    <h1 class="mb-3">New password</h1>
    <form class="panel" method="post" novalidate>
<input type="hidden" name="token" value="<?= htmlspecialchars((string)$token) ?>">
      <p class="text-secondary">For <strong><?= htmlspecialchars((string)$row['username']) ?></strong>.</p>
      <div class="mb-3"><label class="form-label" for="np">New password</label><input type="password" class="form-control" id="np" name="new_password" required minlength="8" autocomplete="new-password"><div class="form-text">At least 8 characters.</div></div>
      <div class="mb-3"><label class="form-label" for="np2">Confirm new password</label><input type="password" class="form-control" id="np2" name="confirm_password" required autocomplete="new-password"></div>
      <button type="submit" class="btn btn-primary w-100">Save new password</button>
    </form>
  </div>
</div>
<?php include('../includes/footer.php'); ?>
