<?php
// Receipt for a COMPLETED (delivered) order. The customer who bought it and store staff can open it.
session_start();
include('../includes/config.php');
include('../includes/guard_login.php');

$id = (int)($_GET['id'] ?? 0);
$o = mysqli_fetch_assoc(mysqli_execute_query($conn, 'SELECT * FROM v_order_totals WHERE orderinfo_id = ?', [$id]));
$mine = $o && (int)$o['user_id'] === (int)$_SESSION['user_id'];
$rcp  = $o ? mysqli_fetch_assoc(mysqli_execute_query($conn, 'SELECT * FROM receipt WHERE orderinfo_id = ?', [$id])) : null;
if (!$o || (!$mine && !in_array($_SESSION['role'] ?? '', ['admin', 'employee'])) || !$rcp) {
    http_response_code(404);
    $_SESSION['warning'] = 'No receipt is available for that order yet. A receipt is issued when the order is completed.';
    header('Location: ' . BASE_URL . (in_array($_SESSION['role'] ?? '', ['admin', 'employee']) ? '/admin/orders.php' : '/user/myorders.php'));
    exit();
}

$lines    = mysqli_fetch_all(mysqli_execute_query($conn, 'SELECT item_id, brand, model, img_path, quantity, unit_price, unit_cost, line_total FROM v_order_details WHERE orderinfo_id = ? ORDER BY item_id', [$id]), MYSQLI_ASSOC);
$subtotal = array_sum(array_column($lines, 'line_total'));
$total    = $subtotal + (float)$o['shipping'];
$auto     = in_array($_SESSION['role'] ?? '', ['admin', 'employee']) && !empty($_GET['autoprint']);

$pageTitle = 'Receipt ' . ('R-' . str_pad((string)(int)$rcp['receipt_id'], 6, '0', STR_PAD_LEFT));
include('../includes/header.php');
?>
<div class="d-flex justify-content-between align-items-center mb-3 no-print">
  <a href="<?= BASE_URL . '/' . (in_array($_SESSION['role'] ?? '', ['admin', 'employee']) && !$mine ? 'admin/orderDetails.php?id=' . $id : 'user/order_view.php?id=' . $id) ?>">&larr; Back to order</a>
  <div class="d-flex gap-2">
    <a class="btn btn-outline-primary" href="<?= BASE_URL . '/user/receipt_pdf.php?id=' . $id ?>"><i class="fa-solid fa-file-pdf"></i> Download PDF</a>
    <button class="btn btn-primary" type="button" onclick="window.print()"><i class="fa-solid fa-print"></i> Print</button>
  </div>
</div>

<div class="receipt">
  <div class="text-center mb-3">
    <div class="h3 mb-0" style="font-family:'Instrument Serif',Georgia,serif">WatchStore</div>
    <div class="small text-secondary">Official receipt</div>
  </div>

  <div class="d-flex justify-content-between align-items-start mb-3">
    <div>
      <div class="fw-bold">Receipt no. <?= htmlspecialchars((string)(('R-' . str_pad((string)(int)$rcp['receipt_id'], 6, '0', STR_PAD_LEFT)))) ?></div>
      <div class="small">Order #<?= $id ?> &middot; placed <?= htmlspecialchars((string)(date('M j, Y g:i A', strtotime($o['date_placed'])))) ?></div>
      <div class="small">Completed: <?= htmlspecialchars((string)(date('M j, Y', strtotime($o['date_delivered'] ?: $o['date_placed'])))) ?></div>
    </div>
    <div class="stamp text-success">COMPLETED</div>
  </div>

  <div class="mb-3 small">
    <div class="fw-bold">Customer</div>
    <div><?= htmlspecialchars((string)(trim(($o['title'] ?? '') . ' ' . $o['fname'] . ' ' . $o['lname']))) ?></div>
    <div><?= htmlspecialchars((string)$o['addressline']) ?>, <?= htmlspecialchars((string)$o['town']) ?> <?= htmlspecialchars((string)$o['zipcode']) ?></div>
    <div><?= htmlspecialchars((string)$o['phone']) ?> &middot; <?= htmlspecialchars((string)$o['email']) ?></div>
  </div>

  <table class="table table-sm">
    <thead><tr><th scope="col">Watch</th><th scope="col" class="text-end">Price</th><th scope="col" class="text-end">Qty</th><th scope="col" class="text-end">Amount</th></tr></thead>
    <tbody>
    <?php foreach ($lines as $l): ?>
      <tr><td><?= htmlspecialchars((string)(($l['brand'] . ' ' . $l['model']))) ?></td><td class="num text-end"><?= (CURRENCY . number_format($l['unit_price'], 2)) ?></td><td class="num text-end"><?= (int)$l['quantity'] ?></td><td class="num text-end"><?= (CURRENCY . number_format($l['unit_price'] * $l['quantity'], 2)) ?></td></tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr><td colspan="3" class="text-end">Subtotal</td><td class="num text-end"><?= (CURRENCY . number_format($subtotal, 2)) ?></td></tr>
      <tr><td colspan="3" class="text-end">Shipping</td><td class="num text-end"><?= (CURRENCY . number_format($o['shipping'], 2)) ?></td></tr>
      <tr class="fw-bold fs-5"><td colspan="3" class="text-end">Total paid</td><td class="num text-end"><?= (CURRENCY . number_format($total, 2)) ?></td></tr>
    </tfoot>
  </table>

  <p class="small mb-1">Thank you for your purchase!</p>
  <p class="small text-secondary mb-0">Issued <?= htmlspecialchars((string)(date('M j, Y g:i A', strtotime($rcp['issued_at'])))) ?> by the WatchStore system.</p>
</div>
<?php if ($auto): ?><script>window.addEventListener('load', () => setTimeout(() => window.print(), 300));</script><?php endif; ?>
<?php include('../includes/footer.php'); ?>
