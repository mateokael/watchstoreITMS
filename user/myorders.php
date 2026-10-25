<?php
session_start();
include('../includes/config.php');
include('../includes/guard_login.php');

$rows = mysqli_fetch_all(mysqli_execute_query($conn, 'SELECT orderinfo_id, date_placed, status, shipping, quantity, unit_price
     FROM v_order_details WHERE user_id = ? ORDER BY orderinfo_id DESC', [(int)$_SESSION['user_id']]), MYSQLI_ASSOC);
$orders = [];
foreach ($rows as $row) {
    $id = $row['orderinfo_id'];
    if (!isset($orders[$id])) {
        $orders[$id] = $row;
        $orders[$id]['subtotal'] = 0.0;
        $orders[$id]['pieces'] = 0;
    }
    $orders[$id]['subtotal'] += (int)$row['quantity'] * (float)$row['unit_price'];
    $orders[$id]['pieces'] += (int)$row['quantity'];
}
$orders = array_values($orders);

$pageTitle = 'My orders';
include('../includes/header.php');
?>
<h1 class="mb-3">My orders</h1>
<?php if (!$orders): ?>
  <div class="panel text-center py-5">
    <h2>No orders yet.</h2>
    <p class="text-secondary">When you check out, your orders will be listed here.</p>
    <a class="btn btn-primary" href="<?= BASE_URL ?>/shop.php">Browse watches</a>
  </div>
<?php else: ?>
<div class="panel p-0 table-responsive">
  <table class="table table-hover mb-0">
    <thead><tr><th scope="col">Order</th><th scope="col">Placed</th><th scope="col" class="num text-end">Watches</th><th scope="col" class="num text-end">Total</th><th scope="col">Status</th><th scope="col"><span class="visually-hidden">View</span></th></tr></thead>
    <tbody>
    <?php foreach ($orders as $o): ?>
      <tr>
        <td>#<?= (int)$o['orderinfo_id'] ?></td>
        <td><?= htmlspecialchars((string)(date('M j, Y g:i A', strtotime($o['date_placed'])))) ?></td>
        <td class="num text-end"><?= (int)$o['pieces'] ?></td>
        <td class="num text-end"><?= (CURRENCY . number_format($o['subtotal'] + $o['shipping'], 2)) ?></td>
        <td><?= '<span class="badge bg-' . ($statusColors[$o['status']] ?? 'secondary') . '">' . ucfirst($o['status']) . '</span>' ?></td>
        <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="<?= BASE_URL . '/user/order_view.php?id=' . (int)$o['orderinfo_id'] ?>">View</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
<?php include('../includes/footer.php'); ?>
