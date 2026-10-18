<?php
session_start();
include('../includes/config.php');
if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/index.php');
    exit();
}
$pageTitle = 'Create account';
include('../includes/header.php');
?>
<div class="row justify-content-center">
  <div class="col-md-6 col-lg-5">
    <h1 class="mb-3">Create account</h1>
    <form class="panel" action="<?= BASE_URL ?>/user/store.php" method="post" enctype="multipart/form-data" novalidate>
      <div class="mb-3">
        <label for="username" class="form-label">Username</label>
        <input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars((string)($_SESSION['old']['username'] ?? '')) ?>" autocomplete="username">
        <div class="form-text">3 to 20 letters, numbers, dots, dashes or underscores. Every username is unique.</div>
      </div>
      <div class="mb-3">
        <label for="email" class="form-label">Email</label>
        <input type="text" class="form-control" id="email" name="email" value="<?= htmlspecialchars((string)($_SESSION['old']['email'] ?? '')) ?>" autocomplete="email">
      </div>
      <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <input type="password" class="form-control" id="password" name="password" autocomplete="new-password">
        <div class="form-text">At least 8 characters.</div>
      </div>
      <div class="mb-3">
        <label for="password2" class="form-label">Confirm password</label>
        <input type="password" class="form-control" id="password2" name="confirmPass" autocomplete="new-password">
      </div>
      <div class="mb-3">
        <label for="avatar" class="form-label">Profile photo <span class="text-secondary small">(optional)</span></label>
        <input type="file" class="form-control" id="avatar" name="avatar" accept="image/jpeg,image/png,image/webp">
        <div class="form-text">JPG, PNG or WEBP, up to 3 MB. You can change it later from your profile.</div>
      </div>
      <button type="submit" class="btn btn-primary w-100">Create account</button>
      <p class="mt-3 mb-0 small">Already registered? <a href="<?= BASE_URL ?>/user/login.php">Log in</a></p>
    </form>
  </div>
</div>
<?php unset($_SESSION['old'], $_SESSION['errors']); include('../includes/footer.php'); ?>
