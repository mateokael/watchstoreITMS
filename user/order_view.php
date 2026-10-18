<?php
// One order. Customers see their own orders; staff (admin / employee) can see any.
session_start();
include('../includes/config.php');
include('../includes/guard_login.php');

$id = (int)($_GET['id'] ?? 0);
$o = mysqli_fetch_assoc(mysqli_execute_query($conn, 'SELECT o.*, c.user_id, c.title, c.fname, c.lname, c.addressline, c.town, c.zipcode, c.phone
             FROM orderinfo o JOIN customer c ON c.customer_id = o.customer_id WHERE o.orderinfo_id = ?', [$id]));
$mine = $o && (int)$o['user_id'] === (int)$_SESSION['user_id'];
if (!$o || (!$mine && !in_array($_SESSION['role'] ?? '', ['admin', 'employee']))) {
    http_response_code(404);
    $_SESSION['warning'] = 'We could not find that order.';
    header('Location: ' . BASE_URL . '/user/myorders.php');
    exit();
}

$lines    = mysqli_fetch_all(mysqli_execute_query($conn, 'SELECT item_id, brand, model, img_path, quantity, unit_price, unit_cost, line_total FROM v_order_details WHERE orderinfo_id = ? ORDER BY item_id', [$id]), MYSQLI_ASSOC);
$subtotal = array_sum(array_column($lines, 'line_total'));

$pageTitle = 'Order #' . $id;
include('../includes/header.php');
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <h1 class="mb-0">Order #<?= $id ?></h1>
  <div class="fs-5"><?= '<span class="badge bg-' . ($statusColors[$o['status']] ?? 'secondary') . '">' . ucfirst($o['status']) . '</span>' ?></div>
</div>
<div class="row g-4">
  <div class="col-md-7">
    <div class="panel p-0 table-responsive">
      <table class="table mb-0">
        <thead><tr><th scope="col">Watch</th><th scope="col" class="num text-end">Price</th><th scope="col" class="num text-end">Qty</th><th scope="col" class="num text-end">Total</th></tr></thead>
        <tbody>
        <?php foreach ($lines as $l): ?>
          <tr>
            <td><a href="<?= BASE_URL . '/product.php?id=' . (int)$l['item_id'] ?>"><?= htmlspecialchars((string)(($l['brand'] . ' ' . $l['model']))) ?></a>
              <?php if ($mine && $o['status'] === 'delivered'): ?><div class="small"><a href="<?= BASE_URL . '/product.php?id=' . (int)$l['item_id'] . '#reviews' ?>">Write a review</a></div><?php endif; ?></td>
            <td class="num text-end"><?= (CURRENCY . number_format($l['unit_price'], 2)) ?></td>
            <td class="num text-end"><?= (int)$l['quantity'] ?></td>
            <td class="num text-end"><?= (CURRENCY . number_format($l['unit_price'] * $l['quantity'], 2)) ?></td>
          </tr>
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
      <h2 class="h4">Delivery</h2>
      <p class="mb-1"><?= htmlspecialchars((string)(trim(($o['title'] ?? '') . ' ' . $o['fname'] . ' ' . $o['lname']))) ?></p>
      <p class="mb-1"><?= htmlspecialchars((string)$o['addressline']) ?>, <?= htmlspecialchars((string)$o['town']) ?> <?= htmlspecialchars((string)$o['zipcode']) ?></p>
      <p class="mb-0"><?= htmlspecialchars((string)$o['phone']) ?></p>
    </div>
    <div class="panel">
      <p class="mb-1">Placed <?= htmlspecialchars((string)(date('M j, Y g:i A', strtotime($o['date_placed'])))) ?></p>
      <p class="mb-1"><?= $o['date_shipped'] ? 'Shipped ' . htmlspecialchars((string)(date('M j, Y', strtotime($o['date_shipped'])))) : 'Not shipped yet' ?></p>
      <p class="mb-0 small text-secondary">Receipt: <?= $o['receipt_pref'] === 'print' ? 'printed copy from the store' : 'sent by email' ?> when the order is completed.</p>
      <?php if ($mine && $o['status'] === 'pending'): ?>
        <form method="post" action="<?= BASE_URL ?>/user/cancel_order.php" class="mt-3" onsubmit="return confirm('Cancel this order? The watches go back into stock.');">
<input type="hidden" name="order_id" value="<?= $id ?>">
          <button class="btn btn-outline-danger" type="submit">Cancel order</button>
        </form>
      <?php endif; ?>
    </div>
    <?php if (mysqli_fetch_assoc(mysqli_execute_query($conn, 'SELECT * FROM receipt WHERE orderinfo_id = ?', [$id]))): ?><a class="btn btn-outline-primary mt-3" href="<?= BASE_URL . '/user/receipt.php?id=' . $id ?>"><i class="fa-solid fa-receipt"></i> View receipt</a><?php endif; ?>
    <?php if (in_array($_SESSION['role'] ?? '', ['admin', 'employee'])): ?><a class="btn btn-link mt-3" href="<?= BASE_URL . '/admin/orderDetails.php?id=' . $id ?>">Open in admin</a><?php endif; ?>
  </div>
</div>
<?php include('../includes/footer.php'); ?>
