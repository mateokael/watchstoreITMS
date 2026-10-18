<?php
session_start();
include('../includes/config.php');
if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/index.php');
    exit();
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $login = trim($_POST['login'] ?? '');      // username OR email
    $pass  = (string)($_POST['password'] ?? '');

    $errors = [];
    if ($login === '') {
        $errors['login'] = 'Enter your email address.';
    } elseif (strlen($login) > 100) {
        $errors['login'] = 'That is too long. Use at most 100 characters.';
    } elseif (str_contains($login, '@') && !filter_var($login, FILTER_VALIDATE_EMAIL)) {
        $errors['login'] = 'That does not look like a valid email address.';
    } elseif (!str_contains($login, '@') && !preg_match('/^[A-Za-z0-9._-]{3,20}$/', $login)) {
        $errors['login'] = 'Enter your email address (or your username: letters, numbers, dots, dashes, underscores).';
    }
    if ($pass === '') {
        $errors['password'] = 'Enter your password.';
    } elseif (strlen($pass) > 200) {
        $errors['password'] = 'That password is too long.';
    }

    if (!$errors) {
        $row = mysqli_fetch_assoc(mysqli_execute_query($conn, 'SELECT user_id, username, email, password, role, status, profile_pic FROM users WHERE email = ? OR username = ? LIMIT 1', [$login, $login]));
        if ($row && password_verify($pass, $row['password'])) {
            if ($row['status'] !== 'active') {
                $errors['login'] = 'This account has been deactivated. Please contact the store.';
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int)$row['user_id'];
    $_SESSION['email'] = $row['email'];
    $_SESSION['role'] = $row['role'];
    $_SESSION['username'] = $row['username'];
    $_SESSION['avatar'] = $row['profile_pic'] ?? null;
                $next = $_SESSION['next'] ?? null;
                unset($_SESSION['next']);
                unset($_SESSION['old'], $_SESSION['errors']);
                if ($row['role'] === 'admin' && !$next) {
                    header('Location: ' . BASE_URL . '/admin/dashboard.php');
                    exit();
                }
                if ($row['role'] === 'employee' && !$next) {
                    header('Location: ' . BASE_URL . '/admin/orders.php');
                    exit();
                }
                if ($next && str_starts_with($next, BASE_URL . '/') && !str_starts_with($next, '//')) {
                    header('Location: ' . $next);
                } else {
                    header('Location: ' . BASE_URL . '/index.php');
                }
                exit();
            }
        } else {
            $errors['password'] = 'Wrong email/username or password.';
        }
    }
    $_SESSION['old'] = ['login' => $login];
    $_SESSION['errors'] = $errors;
    $_SESSION['message'] = 'We could not log you in. Please fix the highlighted fields.';
    header('Location: ' . BASE_URL . '/user/login.php');
    exit();
}

$pageTitle = 'Log in';
include('../includes/header.php');
?>
<div class="row justify-content-center">
  <div class="col-md-6 col-lg-5">
    <h1 class="mb-3">Log in</h1>
    <form class="panel" method="post" novalidate>
      <div class="mb-3">
        <label for="login" class="form-label">Email</label>
        <input type="text" class="form-control<?= (isset($_SESSION['errors']['login']) ? ' is-invalid' : '') ?>" id="login" name="login" value="<?= htmlspecialchars((string)($_SESSION['old']['login'] ?? '')) ?>" autocomplete="username">
        <?= (isset($_SESSION['errors']['login']) ? '<div class="invalid-feedback d-block">' . htmlspecialchars($_SESSION['errors']['login']) . '</div>' : '') ?>
      </div>
      <div class="mb-3">
        <div class="d-flex justify-content-between"><label for="password" class="form-label">Password</label><a class="small" href="<?= BASE_URL ?>/user/forgot_password.php">Forgot password?</a></div>
        <input type="password" class="form-control<?= (isset($_SESSION['errors']['password']) ? ' is-invalid' : '') ?>" id="password" name="password" autocomplete="current-password">
        <?= (isset($_SESSION['errors']['password']) ? '<div class="invalid-feedback d-block">' . htmlspecialchars($_SESSION['errors']['password']) . '</div>' : '') ?>
      </div>
      <button type="submit" class="btn btn-primary w-100">Log in</button>
      <p class="mt-3 mb-0 small">New here? <a href="<?= BASE_URL ?>/user/register.php">Create an account</a></p>
    </form>
  </div>
</div>
<?php unset($_SESSION['old'], $_SESSION['errors']); include('../includes/footer.php'); ?>
