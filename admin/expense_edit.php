<?php
// Admin: add or edit one expense. ?id=3 edits, no id adds.
session_start();
include('../includes/config.php');
include('../includes/guard_admin.php');

$id = (int)($_GET['id'] ?? 0);
$x = null;
if ($id) {
    $x = mysqli_fetch_assoc(mysqli_execute_query($conn, 'SELECT * FROM expense WHERE expense_id = ?', [$id]));
    if (!$x) {
        $_SESSION['message'] = 'That expense does not exist.';
        header('Location: ' . BASE_URL . '/admin/expenses.php');
        exit();
    }
}
$cats = mysqli_fetch_all(mysqli_execute_query($conn, 'SELECT category_id, name FROM expense_category ORDER BY name'), MYSQLI_ASSOC);
$catNow  = (int)($_SESSION['old']['category_id'] ?? ($x['category_id'] ?? 0));
$descNow = htmlspecialchars((string)($_SESSION['old']['description'] ?? ($x['description'] ?? '')));
$amtNow  = htmlspecialchars((string)($_SESSION['old']['amount'] ?? ($x['amount'] ?? '')));
$dateNow = htmlspecialchars((string)($_SESSION['old']['expense_date'] ?? ($x['expense_date'] ?? date('Y-m-d'))));

$pageTitle = $id ? 'Edit expense' : 'Add expense';
include('../includes/header.php');
?>
<nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/expenses.php">Expenses</a></li><li class="breadcrumb-item active"><?= $id ? 'Edit' : 'Add' ?></li></ol></nav>
<h1 class="mb-3"><?= $id ? 'Edit expense' : 'Add expense' ?></h1>
<form method="post" action="<?= BASE_URL ?>/admin/expense_save.php" class="panel" style="max-width:640px" novalidate>
<input type="hidden" name="expense_id" value="<?= $id ?>">
  <div class="row g-3">
    <div class="col-md-6"><label class="form-label" for="expense_date">Date</label><input type="date" class="form-control" id="expense_date" name="expense_date" value="<?= $dateNow ?>" required></div>
    <div class="col-md-6"><label class="form-label" for="category_id">Category</label>
      <select class="form-select" id="category_id" name="category_id" required>
        <option value="">Choose...</option>
        <?php foreach ($cats as $c): ?><option value="<?= (int)$c['category_id'] ?>" <?= $catNow === (int)$c['category_id'] ? 'selected' : '' ?>><?= htmlspecialchars((string)$c['name']) ?></option><?php endforeach; ?>
      </select></div>
    <div class="col-12"><label class="form-label" for="description">Description</label><input class="form-control" id="description" name="description" value="<?= $descNow ?>" maxlength="200" required></div>
    <div class="col-md-6"><label class="form-label" for="amount">Amount (<?= htmlspecialchars((string)(CURRENCY)) ?>)</label><input type="number" step="0.01" min="0.01" class="form-control" id="amount" name="amount" value="<?= $amtNow ?>" required></div>
  </div>
  <div class="mt-4 d-flex gap-2">
    <button class="btn btn-primary" type="submit"><?= $id ? 'Save changes' : 'Add expense' ?></button>
    <a class="btn btn-link" href="<?= BASE_URL ?>/admin/expenses.php">Cancel</a>
  </div>
</form>
<?php unset($_SESSION['old'], $_SESSION['errors']); include('../includes/footer.php'); ?>
