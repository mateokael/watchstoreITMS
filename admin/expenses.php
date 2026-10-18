<?php
// Admin: expenses list (filter by date range / category) with a total.
session_start();
include('../includes/config.php');
include('../includes/guard_admin.php');

include('../includes/date_range.php');
if (!isset($_GET['from']) && !isset($_GET['to'])) {
    $from = date('Y-01-01');
}
$cat = (int)($_GET['cat'] ?? 0);

$where  = ['e.expense_date BETWEEN ? AND ?'];
$params = [$from, $to];
if ($cat) {
    $where[] = 'e.category_id = ?';
    $params[] = $cat;
}
$rows = mysqli_fetch_all(mysqli_execute_query($conn, 'SELECT e.*, ec.name AS category, u.username
                FROM expense e
                INNER JOIN expense_category ec ON ec.category_id = e.category_id
                LEFT JOIN users u ON u.user_id = e.created_by
                WHERE ' . implode(' AND ', $where) . ' ORDER BY e.expense_date DESC, e.expense_id DESC', $params), MYSQLI_ASSOC);
$total = array_sum(array_map(fn($r) => (float)$r['amount'], $rows));
$cats  = mysqli_fetch_all(mysqli_execute_query($conn, 'SELECT category_id, name FROM expense_category ORDER BY name'), MYSQLI_ASSOC);

$pageTitle = 'Expenses';
include('../includes/header.php');
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <h1 class="mb-0">Expenses <small class="text-secondary fs-5">(<?= count($rows) ?>)</small></h1>
  <a href="<?= BASE_URL ?>/admin/expense_edit.php" class="btn btn-brass"><i class="fa-solid fa-plus"></i> Add expense</a>
</div>

<form method="get" class="row g-2 align-items-end mb-3">
  <div class="col-auto"><label class="form-label small mb-0" for="from">From</label><input type="date" class="form-control" id="from" name="from" value="<?= htmlspecialchars((string)$from) ?>"></div>
  <div class="col-auto"><label class="form-label small mb-0" for="to">To</label><input type="date" class="form-control" id="to" name="to" value="<?= htmlspecialchars((string)$to) ?>"></div>
  <div class="col-auto"><label class="form-label small mb-0" for="cat">Category</label>
    <select class="form-select" id="cat" name="cat"><option value="0">All</option>
      <?php foreach ($cats as $c): ?><option value="<?= (int)$c['category_id'] ?>" <?= $cat === (int)$c['category_id'] ? 'selected' : '' ?>><?= htmlspecialchars((string)$c['name']) ?></option><?php endforeach; ?>
    </select></div>
  <div class="col-auto"><button class="btn btn-primary" type="submit">Filter</button></div>
</form>

<div class="panel p-0 table-responsive">
  <table class="table table-hover mb-0 align-middle">
    <thead><tr><th scope="col">Date</th><th scope="col">Category</th><th scope="col">Description</th><th scope="col">Recorded by</th><th scope="col" class="num text-end">Amount</th><th scope="col" class="text-end">Actions</th></tr></thead>
    <tbody>
    <?php if (!$rows): ?><tr><td colspan="6" class="text-center text-secondary py-4">No expenses in this range.</td></tr><?php endif; ?>
    <?php foreach ($rows as $r): $eid = (int)$r['expense_id']; ?>
      <tr>
        <td><?= htmlspecialchars((string)(date('M j, Y', strtotime($r['expense_date'])))) ?></td>
        <td><?= htmlspecialchars((string)$r['category']) ?></td>
        <td><?= htmlspecialchars((string)$r['description']) ?></td>
        <td class="text-secondary"><?= htmlspecialchars((string)($r['username'] ?? '-')) ?></td>
        <td class="num text-end"><?= (CURRENCY . number_format($r['amount'], 2)) ?></td>
        <td class="text-end">
          <div class="d-flex gap-1 justify-content-end">
            <a class="btn btn-sm btn-outline-primary" href="<?= BASE_URL . '/admin/expense_edit.php?id=' . $eid ?>"><i class="fa-regular fa-pen-to-square"></i> Edit</a>
            <form method="post" action="<?= BASE_URL ?>/admin/expense_delete.php" onsubmit="return confirm('Delete this expense?');">
<input type="hidden" name="expense_id" value="<?= $eid ?>">
              <button class="btn btn-sm btn-outline-danger" type="submit"><i class="fa-regular fa-trash-can"></i> Delete</button>
            </form>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
    <?php if ($rows): ?><tfoot><tr class="fw-bold"><td colspan="4" class="text-end">Total</td><td class="num text-end"><?= (CURRENCY . number_format($total, 2)) ?></td><td></td></tr></tfoot><?php endif; ?>
  </table>
</div>
<?php include('../includes/footer.php'); ?>
