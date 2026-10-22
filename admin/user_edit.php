<?php
// Admin: add or edit a customer / employee. ?id=5 edits, no id adds.
session_start();
include('../includes/config.php');
include('../includes/guard_admin.php');

$id = (int)($_GET['id'] ?? 0);
$u = null;
$c = null;
if ($id) {
    $u = mysqli_fetch_assoc(mysqli_execute_query(
        $conn,
        'SELECT * FROM users WHERE user_id = ? AND user_id <> ?',
        [$id, (int)$_SESSION['user_id']]
    ));
    if (!$u) {
        $_SESSION['message'] = 'That account does not exist (edit your own account from My profile).';
        header('Location: ' . BASE_URL . '/admin/users.php');
        exit();
    }
    $c = mysqli_fetch_assoc(mysqli_execute_query($conn, 'SELECT * FROM customer WHERE user_id = ?', [$id]));
}
$roleNow   = $_SESSION['old']['role'] ?? ($u['role'] ?? 'user');
$statusNow = $_SESSION['old']['status'] ?? ($u['status'] ?? 'active');

$pageTitle = $id ? 'Edit account' : 'Add customer or employee';
include('../includes/header.php');
?>
<nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/users.php">Customers &amp; employees</a></li><li class="breadcrumb-item active"><?= $id ? 'Edit' : 'Add' ?></li></ol></nav>
<h1 class="mb-3"><?= $id ? 'Edit ' . htmlspecialchars((string)$u['username']) : 'Add customer or employee' ?></h1>

<form method="post" action="<?= BASE_URL ?>/admin/user_save.php" class="panel" novalidate>
<input type="hidden" name="user_id" value="<?= $id ?>">
  <div class="row g-3">
    <div class="col-md-4">
      <label class="form-label" for="role">Account type</label>
      <select class="form-select" id="role" name="role">
        <option value="user" <?= $roleNow === 'user' ? 'selected' : '' ?>>Customer</option>
        <option value="employee" <?= $roleNow === 'employee' ? 'selected' : '' ?>>Employee (restock + confirm orders)</option>
        <option value="admin" <?= $roleNow === 'admin' ? 'selected' : '' ?>>Admin (everything)</option>
      </select>
    </div>
    <div class="col-md-4">
      <label class="form-label" for="username">Username</label>
      <input class="form-control" id="username" name="username" value="<?= htmlspecialchars((string)($_SESSION['old']['username'] ?? ($u['username'] ?? ''))) ?>" maxlength="20" required>
    </div>
    <div class="col-md-4">
      <label class="form-label" for="email">Email</label>
      <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars((string)($_SESSION['old']['email'] ?? ($u['email'] ?? ''))) ?>" maxlength="100" required>
    </div>
    <div class="col-md-4">
      <label class="form-label" for="password"><?= $id ? 'New password (leave empty to keep)' : 'Password' ?></label>
      <input type="password" class="form-control" id="password" name="password" autocomplete="new-password" <?= $id ? '' : 'required' ?>>
      <div class="form-text">At least 8 characters.</div>
    </div>
    <div class="col-md-4">
      <label class="form-label" for="status">Status</label>
      <select class="form-select" id="status" name="status">
        <option value="active" <?= $statusNow === 'active' ? 'selected' : '' ?>>Active</option>
        <option value="inactive" <?= $statusNow === 'inactive' ? 'selected' : '' ?>>Inactive (cannot log in)</option>
      </select>
    </div>
  </div>

  <div id="customerFields" class="mt-4">
    <h2 class="h5">Customer details <small class="text-secondary fs-6">(delivery information, optional here; the customer can fill it in on their profile)</small></h2>
    <div class="row g-3">
      <div class="col-md-2"><label class="form-label" for="title">Title</label><input class="form-control" id="title" name="title" value="<?= htmlspecialchars((string)($_SESSION['old']['title'] ?? ($c['title'] ?? ''))) ?>" maxlength="8"></div>
      <div class="col-md-5"><label class="form-label" for="fname">First name</label><input class="form-control" id="fname" name="fname" value="<?= htmlspecialchars((string)($_SESSION['old']['fname'] ?? ($c['fname'] ?? ''))) ?>" maxlength="50"></div>
      <div class="col-md-5"><label class="form-label" for="lname">Last name</label><input class="form-control" id="lname" name="lname" value="<?= htmlspecialchars((string)($_SESSION['old']['lname'] ?? ($c['lname'] ?? ''))) ?>" maxlength="50"></div>
      <div class="col-12"><label class="form-label" for="addressline">Address</label><input class="form-control" id="addressline" name="addressline" value="<?= htmlspecialchars((string)($_SESSION['old']['addressline'] ?? ($c['addressline'] ?? ''))) ?>" maxlength="120"></div>
      <div class="col-md-5"><label class="form-label" for="town">Town / city</label><input class="form-control" id="town" name="town" value="<?= htmlspecialchars((string)($_SESSION['old']['town'] ?? ($c['town'] ?? ''))) ?>" maxlength="60"></div>
      <div class="col-md-3"><label class="form-label" for="zipcode">Zip code</label><input class="form-control" id="zipcode" name="zipcode" value="<?= htmlspecialchars((string)($_SESSION['old']['zipcode'] ?? ($c['zipcode'] ?? ''))) ?>" maxlength="10"></div>
      <div class="col-md-4"><label class="form-label" for="phone">Phone</label><input class="form-control" id="phone" name="phone" value="<?= htmlspecialchars((string)($_SESSION['old']['phone'] ?? ($c['phone'] ?? ''))) ?>" maxlength="20"></div>
    </div>
  </div>

  <div class="mt-4 d-flex gap-2">
    <button class="btn btn-primary" type="submit"><?= $id ? 'Save changes' : 'Create account' ?></button>
    <a class="btn btn-link" href="<?= BASE_URL ?>/admin/users.php">Cancel</a>
  </div>
</form>
<script>
  // Employees don't need delivery details
  const roleSel = document.getElementById('role'), box = document.getElementById('customerFields');
  function toggleCustomer() { box.style.display = roleSel.value === 'user' ? '' : 'none'; }
  roleSel.addEventListener('change', toggleCustomer); toggleCustomer();
</script>
<?php unset($_SESSION['old'], $_SESSION['errors']); include('../includes/footer.php'); ?>
