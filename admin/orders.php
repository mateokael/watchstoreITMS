<?php
session_start();
include('../includes/config.php');
include('../includes/guard_staff.php');

$status = $_GET['status'] ?? '';
$q      = trim($_GET['q'] ?? '');
$where = [];
$params = [];
if (in_array($status, ORDER_STATUSES, true)) {
    $where[] = 'o.status = ?';
    $params[] = $status;
}
if ($q !== '') {
    $where[] = '(o.orderinfo_id = ? OR o.fname LIKE ? OR o.lname LIKE ?)';
    $params[] = (int)ltrim($q, '#');
    $params[] = "%$q%";
    $params[] = "%$q%";
}

// The order totals view returns one row per order.
$orders = mysqli_fetch_all(mysqli_execute_query($conn, 'SELECT o.*, o.grand_total AS total FROM v_order_totals o'
    . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
    . ' ORDER BY o.orderinfo_id DESC', $params), MYSQLI_ASSOC);

$pageTitle = 'Orders';
include('../includes/header.php');
?>
<h1 class="mb-3">Orders <small class="text-secondary fs-5">(<?= count($orders) ?>)</small></h1>
<form method="get" class="d-flex flex-wrap gap-2 mb-3">
  <input type="search" class="form-control" style="max-width:260px" name="q" value="<?= htmlspecialchars((string)$q) ?>" placeholder="Order # or customer name" aria-label="Search orders">
  <select class="form-select" style="max-width:180px" name="status" aria-label="Filter by status">
    <option value="">All statuses</option>
    <?php foreach (ORDER_STATUSES as $s): ?><option value="<?= $s ?>" <?= $s === $status ? 'selected' : '' ?>><?= ucfirst($s) ?></option><?php endforeach; ?>
  </select>
  <button class="btn btn-primary" type="submit">Filter</button>
</form>
<div class="panel p-0 table-responsive">
  <table class="table table-hover mb-0">
    <thead><tr><th scope="col">Order</th><th scope="col">Placed</th><th scope="col">Customer</th><th scope="col">Status</th><th scope="col" class="num text-end">Total</th><th scope="col"><span class="visually-hidden">Open</span></th></tr></thead>
    <tbody>
    <?php if (!$orders): ?><tr><td colspan="6" class="text-center text-secondary py-4">No orders match.</td></tr><?php endif; ?>
    <?php foreach ($orders as $o): ?>
      <tr>
        <td>#<?= (int)$o['orderinfo_id'] ?></td>
        <td><?= htmlspecialchars((string)(date('M j, Y g:i A', strtotime($o['date_placed'])))) ?></td>
        <td><?= htmlspecialchars((string)($o['fname'] . ' ' . $o['lname'])) ?></td>
        <td><?= '<span class="badge bg-' . ($statusColors[$o['status']] ?? 'secondary') . '">' . ucfirst($o['status']) . '</span>' ?></td>
        <td class="num text-end"><?= (CURRENCY . number_format($o['total'], 2)) ?></td>
        <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="<?= BASE_URL . '/admin/orderDetails.php?id=' . (int)$o['orderinfo_id'] ?>">Open</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php include('../includes/footer.php'); ?>
