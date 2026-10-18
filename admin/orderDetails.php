<?php
session_start();
include('../includes/config.php');
include('../includes/guard_staff.php');

$id = (int)($_GET['id'] ?? 0);
$o = mysqli_fetch_assoc(mysqli_execute_query($conn, 'SELECT * FROM v_order_totals WHERE orderinfo_id = ?', [$id]));
if (!$o) {
    $_SESSION['message'] = 'That order does not exist.';
    header('Location: ' . BASE_URL . '/admin/orders.php');
    exit();
}
$lines    = mysqli_fetch_all(mysqli_execute_query($conn, 'SELECT item_id, brand, model, img_path, quantity, unit_price, unit_cost, line_total FROM v_order_details WHERE orderinfo_id = ? ORDER BY item_id', [$id]), MYSQLI_ASSOC);
$rcp      = $o['status'] === 'delivered' ? mysqli_fetch_assoc(mysqli_execute_query($conn, 'SELECT * FROM receipt WHERE orderinfo_id = ?', [$id])) : null;
$subtotal = array_sum(array_column($lines, 'line_total'));
$next     = ($nextStatuses[$o['status']] ?? []);
if (!(($_SESSION['role'] ?? '') === 'admin')) {
    $next = array_values(array_diff($next, ['cancelled']));   // employees confirm and deliver; cancelling is the admin's call
}

$pageTitle = 'Order #' . $id;
include('../includes/header.php');
?>
<nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/orders.php">Orders</a></li><li class="breadcrumb-item active">#<?= $id ?></li></ol></nav>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <h1 class="mb-0">Order #<?= $id ?></h1>
  <div class="fs-5"><?= '<span class="badge bg-' . ($statusColors[$o['status']] ?? 'secondary') . '">' . ucfirst($o['status']) . '</span>' ?>
</div>
</div>

<div class="row g-4">
  <div class="col-md-7">
    <div class="panel p-0 table-responsive">
      <table class="table mb-0">
        <thead><tr><th scope="col">Watch</th><th scope="col" class="num text-end">Price</th><th scope="col" class="num text-end">Qty</th><th scope="col" class="num text-end">Total</th></tr></thead>
        <tbody>
        <?php foreach ($lines as $l): ?>
          <tr><td><?= htmlspecialchars((string)(($l['brand'] . ' ' . $l['model']))) ?></td><td class="num text-end"><?= (CURRENCY . number_format($l['unit_price'], 2)) ?></td><td class="num text-end"><?= (int)$l['quantity'] ?></td><td class="num text-end"><?= (CURRENCY . number_format($l['unit_price'] * $l['quantity'], 2)) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr><td colspan="3" class="text-end">Subtotal</td><td class="num text-end"><?= (CURRENCY . number_format($subtotal, 2)) ?></td></tr>
          <tr><td colspan="3" class="text-end">Shipping</td><td class="num text-end"><?= (CURRENCY . number_format($o['shipping'], 2)) ?></td></tr>
          <tr class="fw-bold"><td colspan="3" class="text-end">Total</td><td class="num text-end"><?= (CURRENCY . number_format($subtotal + $o['shipping'], 2)) ?></td></tr>
        </tfoot>
      </table>
    </div>
  </div>
  <div class="col-md-5">
    <div class="panel mb-3">
      <h2 class="h4">Customer</h2>
      <p class="mb-1"><?= htmlspecialchars((string)(trim(($o['title'] ?? '') . ' ' . $o['fname'] . ' ' . $o['lname']))) ?></p>
      <p class="mb-1"><?= htmlspecialchars((string)$o['addressline']) ?>, <?= htmlspecialchars((string)$o['town']) ?> <?= htmlspecialchars((string)$o['zipcode']) ?></p>
      <p class="mb-1"><?= htmlspecialchars((string)$o['phone']) ?></p>
      <p class="mb-0 text-secondary"><?= htmlspecialchars((string)$o['email']) ?></p>
    </div>
    <?php if ($o['status'] === 'delivered'): ?>
    <div class="panel mb-3">
      <h2 class="h4">Receipt</h2>
      <p class="small mb-2">Customer's choice: <strong><?= $o['receipt_pref'] === 'print' ? 'printed copy from the store' : 'email' ?></strong>.
        <?php if ($rcp): ?><br>Receipt <?= htmlspecialchars((string)(('R-' . str_pad((string)(int)$rcp['receipt_id'], 6, '0', STR_PAD_LEFT)))) ?>: <strong><?= htmlspecialchars((string)(($rcp['method'] === 'email' ? ($rcp['emailed_at'] ? 'Emailed' : 'Email not sent yet') : ($rcp['printed_at'] ? 'Printed' : 'Waiting to be printed')))) ?></strong><?php endif; ?></p>
      <div class="d-flex flex-wrap gap-2">
        <form method="post" action="<?= BASE_URL ?>/admin/receipt_action.php"><input type="hidden" name="order_id" value="<?= $id ?>"><input type="hidden" name="action" value="email">
          <button class="btn btn-sm btn-primary" type="submit"><i class="fa-solid fa-envelope"></i> <?= $rcp && $rcp['emailed_at'] ? 'Resend by email' : 'Email receipt' ?></button></form>
        <form method="post" action="<?= BASE_URL ?>/admin/receipt_action.php"><input type="hidden" name="order_id" value="<?= $id ?>"><input type="hidden" name="action" value="print">
          <button class="btn btn-sm btn-outline-primary" type="submit"><i class="fa-solid fa-print"></i> Print for customer</button></form>
        <?php if ($rcp): ?><a class="btn btn-sm btn-link" href="<?= BASE_URL . '/user/receipt.php?id=' . $id ?>">View receipt</a><?php endif; ?>
        <a class="btn btn-sm btn-outline-secondary" href="<?= BASE_URL . '/user/receipt_pdf.php?id=' . $id ?>"><i class="fa-solid fa-file-pdf"></i> PDF</a>
      </div>
    </div>
    <?php endif; ?>
    <div class="panel">
      <h2 class="h4">Update status</h2>
      <p class="small text-secondary">Placed <?= htmlspecialchars((string)(date('M j, Y g:i A', strtotime($o['date_placed'])))) ?><?= $o['date_shipped'] ? '. Shipped ' . htmlspecialchars((string)(date('M j, Y', strtotime($o['date_shipped'])))) : '' ?><?= $o['date_delivered'] ? '. Delivered ' . htmlspecialchars((string)(date('M j, Y', strtotime($o['date_delivered'])))) : '' ?></p>
      <?php if (!$next): ?>
        <p class="mb-0">This order is <?= htmlspecialchars((string)$o['status']) ?>, so its status can't change any more.</p>
      <?php else: ?>
        <form method="post" action="<?= BASE_URL ?>/admin/updateOrder.php" class="d-flex gap-2">
<input type="hidden" name="order_id" value="<?= $id ?>">
          <select class="form-select" name="status" aria-label="New status">
            <?php foreach ($next as $s): ?><option value="<?= $s ?>"><?= $s === 'cancelled' ? 'Cancel (returns stock)' : ($s === 'shipped' ? 'Confirm and mark as shipped' : 'Mark as ' . $s) ?></option><?php endforeach; ?>
          </select>
          <button class="btn btn-primary" type="submit">Update</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php include('../includes/footer.php'); ?>
