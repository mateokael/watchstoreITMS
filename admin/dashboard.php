<?php
// Sales summary + low-stock alerts. Cancelled orders are left out of all sales numbers.
session_start();
include('../includes/config.php');
include('../includes/guard_admin.php');

$sales = mysqli_fetch_assoc(mysqli_execute_query($conn, "SELECT COALESCE(SUM(ol.unit_price * ol.quantity), 0) AS revenue,
                        COALESCE(SUM((ol.unit_price - i.cost_price) * ol.quantity), 0) AS profit,
                        COALESCE(SUM(ol.quantity), 0) AS units
                 FROM orderline ol
                 JOIN orderinfo o ON o.orderinfo_id = ol.orderinfo_id
                 JOIN item i ON i.item_id = ol.item_id
                 WHERE o.status <> 'cancelled'"));

$byStatus = [];
foreach (mysqli_fetch_all(mysqli_execute_query($conn, 'SELECT status, COUNT(*) AS n FROM orderinfo GROUP BY status'), MYSQLI_ASSOC) as $r) {
    $byStatus[$r['status']] = (int)$r['n'];
}
$customers = (int)(mysqli_fetch_row(mysqli_execute_query($conn, "SELECT COUNT(*) FROM users WHERE role = 'user'")) ?? [null])[0];

$top = mysqli_fetch_all(mysqli_execute_query($conn, "SELECT i.item_id, i.brand, i.model, SUM(ol.quantity) AS units, SUM(ol.unit_price * ol.quantity) AS revenue
               FROM orderline ol
               JOIN orderinfo o ON o.orderinfo_id = ol.orderinfo_id
               JOIN item i ON i.item_id = ol.item_id
               WHERE o.status <> 'cancelled'
               GROUP BY i.item_id, i.brand, i.model ORDER BY units DESC, revenue DESC LIMIT 5"), MYSQLI_ASSOC);

$monthly = mysqli_fetch_all(mysqli_execute_query($conn, "SELECT DATE_FORMAT(o.date_placed, '%Y-%m') AS ym, COUNT(DISTINCT o.orderinfo_id) AS orders,
                          SUM(ol.unit_price * ol.quantity) AS sales
                   FROM orderinfo o JOIN orderline ol ON ol.orderinfo_id = o.orderinfo_id
                   WHERE o.status <> 'cancelled'
                   GROUP BY DATE_FORMAT(o.date_placed, '%Y-%m') ORDER BY ym DESC LIMIT 6"), MYSQLI_ASSOC);

$low = mysqli_fetch_all(mysqli_execute_query($conn, 'SELECT i.item_id, i.brand, i.model, s.quantity, s.low_stock_level
               FROM item i JOIN stock s ON s.item_id = i.item_id
               WHERE i.is_active = 1 AND s.quantity <= s.low_stock_level ORDER BY s.quantity ASC, i.brand'), MYSQLI_ASSOC);

$recent = mysqli_fetch_all(mysqli_execute_query($conn, 'SELECT o.orderinfo_id, o.date_placed, o.status, c.fname, c.lname,
                         COALESCE(SUM(ol.unit_price * ol.quantity), 0) + o.shipping AS total
                  FROM orderinfo o
                  JOIN customer c ON c.customer_id = o.customer_id
                  LEFT JOIN orderline ol ON ol.orderinfo_id = o.orderinfo_id
                  GROUP BY o.orderinfo_id ORDER BY o.orderinfo_id DESC LIMIT 5'), MYSQLI_ASSOC);

$pageTitle = 'Dashboard';
include('../includes/header.php');
?>
<h1 class="mb-3">Dashboard</h1>

<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3"><div class="stat"><div class="text-secondary small">Sales (open + completed orders)</div><div class="figure"><?= (CURRENCY . number_format($sales['revenue'], 2)) ?></div></div></div>
  <div class="col-6 col-lg-3"><div class="stat"><div class="text-secondary small">Estimated profit</div><div class="figure"><?= (CURRENCY . number_format($sales['profit'], 2)) ?></div></div></div>
  <div class="col-6 col-lg-3"><div class="stat"><div class="text-secondary small">Watches sold</div><div class="figure"><?= (int)$sales['units'] ?></div></div></div>
  <div class="col-6 col-lg-3"><div class="stat"><div class="text-secondary small">Customers</div><div class="figure"><?= $customers ?></div></div></div>
</div>

<div class="d-flex flex-wrap gap-2 mb-3 no-print">
  <a class="btn btn-sm btn-primary" href="<?= BASE_URL ?>/admin/income.php"><i class="fa-solid fa-coins"></i> Income by date</a>
  <a class="btn btn-sm btn-outline-primary" href="<?= BASE_URL ?>/admin/reports.php"><i class="fa-solid fa-chart-column"></i> Reports</a>
  <a class="btn btn-sm btn-outline-primary" href="<?= BASE_URL ?>/admin/expenses.php"><i class="fa-solid fa-receipt"></i> Expenses</a>
  <a class="btn btn-sm btn-outline-primary" href="<?= BASE_URL ?>/admin/users.php"><i class="fa-solid fa-users"></i> Customers &amp; employees</a>
</div>
<div class="d-flex flex-wrap gap-2 mb-4">
  <?php foreach (ORDER_STATUSES as $s): ?>
    <a class="text-decoration-none" href="<?= BASE_URL . '/admin/orders.php?status=' . $s ?>"><?= '<span class="badge bg-' . ($statusColors[$s] ?? 'secondary') . '">' . ucfirst($s) . '</span>' ?> <span class="ms-1"><?= $byStatus[$s] ?? 0 ?></span></a>
  <?php endforeach; ?>
</div>

<div class="row g-4">
  <div class="col-lg-6">
    <div class="panel h-100">
      <h2 class="h4">Top sellers</h2>
      <?php if (!$top): ?><p class="text-secondary mb-0">No sales yet.</p><?php else: ?>
      <table class="table mb-0"><thead><tr><th scope="col">Watch</th><th scope="col" class="num text-end">Sold</th><th scope="col" class="num text-end">Sales</th></tr></thead><tbody>
        <?php foreach ($top as $t): ?>
          <tr><td><a href="<?= BASE_URL . '/product.php?id=' . (int)$t['item_id'] ?>"><?= htmlspecialchars((string)(($t['brand'] . ' ' . $t['model']))) ?></a></td><td class="num text-end"><?= (int)$t['units'] ?></td><td class="num text-end"><?= (CURRENCY . number_format($t['revenue'], 2)) ?></td></tr>
        <?php endforeach; ?>
      </tbody></table>
      <?php endif; ?>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="panel h-100">
      <h2 class="h4">Sales by month</h2>
      <?php if (!$monthly): ?><p class="text-secondary mb-0">No sales yet.</p><?php else: ?>
      <table class="table mb-0"><thead><tr><th scope="col">Month</th><th scope="col" class="num text-end">Orders</th><th scope="col" class="num text-end">Sales</th></tr></thead><tbody>
        <?php foreach ($monthly as $m): ?>
          <tr><td><?= htmlspecialchars((string)(date('F Y', strtotime($m['ym'] . '-01')))) ?></td><td class="num text-end"><?= (int)$m['orders'] ?></td><td class="num text-end"><?= (CURRENCY . number_format($m['sales'], 2)) ?></td></tr>
        <?php endforeach; ?>
      </tbody></table>
      <?php endif; ?>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="panel h-100">
      <h2 class="h4">Low stock</h2>
      <?php if (!$low): ?><p class="text-secondary mb-0">Every watch has healthy stock.</p><?php else: ?>
      <table class="table mb-0"><tbody>
        <?php foreach ($low as $l): ?>
          <tr>
            <td><?= htmlspecialchars((string)(($l['brand'] . ' ' . $l['model']))) ?></td>
            <td class="num text-end"><?= (int)$l['quantity'] === 0 ? '<span class="badge bg-danger">Sold out</span>' : (int)$l['quantity'] . ' left' ?></td>
            <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="<?= BASE_URL . '/item/edit.php?id=' . (int)$l['item_id'] ?>">Restock</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody></table>
      <?php endif; ?>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="panel h-100">
      <h2 class="h4">Recent orders</h2>
      <?php if (!$recent): ?><p class="text-secondary mb-0">No orders yet.</p><?php else: ?>
      <table class="table mb-0"><tbody>
        <?php foreach ($recent as $r): ?>
          <tr>
            <td><a href="<?= BASE_URL . '/admin/orderDetails.php?id=' . (int)$r['orderinfo_id'] ?>">#<?= (int)$r['orderinfo_id'] ?></a></td>
            <td><?= htmlspecialchars((string)($r['fname'] . ' ' . $r['lname'])) ?></td>
            <td><?= '<span class="badge bg-' . ($statusColors[$r['status']] ?? 'secondary') . '">' . ucfirst($r['status']) . '</span>' ?></td>
            <td class="num text-end"><?= (CURRENCY . number_format($r['total'], 2)) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody></table>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php include('../includes/footer.php'); ?>
