<?php
// Admin: total income of the shop for a date range picked with the date pickers.
session_start();
include('../includes/config.php');
include('../includes/guard_admin.php');

include('../includes/date_range.php');
$incFrom = $from;
$incTo = $to;
include('../includes/income_summary.php');
$sum = $incomeSummary;

$days    = (int)((strtotime($to) - strtotime($from)) / 86400) + 1;
$monthly = $days > 62;
$fmt     = $monthly ? '%Y-%m' : '%Y-%m-%d';

$sales = [];
foreach (mysqli_fetch_all(mysqli_execute_query($conn, 'SELECT DATE_FORMAT(DATE(date_delivered), \'' . $fmt . '\') AS b, SUM(quantity * unit_price) AS v
                 FROM v_order_details
                 WHERE status = \'delivered\' AND DATE(date_delivered) BETWEEN ? AND ? GROUP BY b', [$from, $to]), MYSQLI_ASSOC) as $r) {
    $sales[$r['b']] = (float)$r['v'];
}
foreach (mysqli_fetch_all(mysqli_execute_query($conn, 'SELECT DATE_FORMAT(DATE(date_delivered), \'' . $fmt . '\') AS b, SUM(shipping) AS v
                 FROM (
                     SELECT orderinfo_id, date_delivered, MAX(shipping) AS shipping
                     FROM v_order_details
                     WHERE status = \'delivered\' AND DATE(date_delivered) BETWEEN ? AND ?
                     GROUP BY orderinfo_id, date_delivered
                 ) AS delivered_orders GROUP BY b', [$from, $to]), MYSQLI_ASSOC) as $r) {
    $sales[$r['b']] = ($sales[$r['b']] ?? 0) + (float)$r['v'];
}
$exp = [];
foreach (mysqli_fetch_all(mysqli_execute_query($conn, 'SELECT DATE_FORMAT(expense_date, \'' . $fmt . '\') AS b, SUM(amount) AS v FROM expense
                 WHERE expense_date BETWEEN ? AND ? GROUP BY b', [$from, $to]), MYSQLI_ASSOC) as $r) {
    $exp[$r['b']] = (float)$r['v'];
}
$labels = [];
$dIncome = [];
$dExp = [];
$cur = new DateTime($monthly ? date('Y-m-01', strtotime($from)) : $from);
$end = new DateTime($to);
while ($cur <= $end) {
    $k = $cur->format($monthly ? 'Y-m' : 'Y-m-d');
    $labels[]  = $monthly ? $cur->format('M Y') : $cur->format('M j');
    $dIncome[] = round($sales[$k] ?? 0, 2);
    $dExp[]    = round($exp[$k] ?? 0, 2);
    $cur->modify($monthly ? '+1 month' : '+1 day');
}

$orders = mysqli_fetch_all(mysqli_execute_query($conn, 'SELECT orderinfo_id, DATE(date_delivered) AS sale_date, fname, lname,
                         SUM(quantity) AS pieces, SUM(quantity * unit_price) AS items, MAX(shipping) AS shipping
                  FROM v_order_details
                  WHERE status = \'delivered\' AND DATE(date_delivered) BETWEEN ? AND ?
                  GROUP BY orderinfo_id, DATE(date_delivered), fname, lname
                  ORDER BY sale_date DESC, orderinfo_id DESC', [$from, $to]), MYSQLI_ASSOC);

$today = date('Y-m-d');
$quick = [
    'Today'      => [$today, $today],
    'This month' => [date('Y-m-01'), $today],
    'Last month' => [date('Y-m-01', strtotime('first day of last month')), date('Y-m-t', strtotime('first day of last month'))],
    'This year'  => [date('Y-01-01'), $today],
];

$pageTitle = 'Income';
$containerClass = 'container-fluid px-3 px-lg-5 admin-open-page admin-income';
include('../includes/header.php');
?>
<section class="income-overview">
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
  <h1 class="admin-page-title mb-0">Shop income</h1>
  <button class="btn btn-outline-light no-print" type="button" onclick="window.print()"><i class="fa-solid fa-print"></i> Print / Save as PDF</button>
</div>
<p class="text-secondary">Pick a date range to see how much the shop earned. A sale counts on the day the order was <strong>delivered</strong>; pending and cancelled orders are not income.</p>

<form method="get" class="row g-2 align-items-end mb-2 no-print">
  <div class="col-auto"><label class="form-label small mb-0" for="from">From</label><input type="date" class="form-control" id="from" name="from" value="<?= htmlspecialchars((string)$from) ?>" max="<?= htmlspecialchars((string)$today) ?>"></div>
  <div class="col-auto"><label class="form-label small mb-0" for="to">To</label><input type="date" class="form-control" id="to" name="to" value="<?= htmlspecialchars((string)$to) ?>"></div>
  <div class="col-auto"><button class="btn btn-light" type="submit">Show income</button></div>
</form>
<div class="d-flex flex-wrap gap-2 mb-4 no-print">
  <?php foreach ($quick as $label => [$f, $t]): ?>
    <a class="btn btn-sm btn-outline-light" href="<?= BASE_URL . '/admin/income.php?from=' . $f . '&to=' . $t ?>"><?= htmlspecialchars((string)$label) ?></a>
  <?php endforeach; ?>
</div>

<h2 class="admin-section-title mb-3"><?= htmlspecialchars((string)(date('M j, Y', strtotime($from)))) ?> &ndash; <?= htmlspecialchars((string)(date('M j, Y', strtotime($to)))) ?></h2>
</section>
<div class="row g-3 mb-4">
  <div class="col-12 col-lg-4"><div class="stat h-100"><div class="text-secondary small">Total income</div><div class="figure"><?= (CURRENCY . number_format($sum['income'], 2)) ?></div><div class="small text-secondary"><?= (CURRENCY . number_format($sum['sales'], 2)) ?> watches + <?= (CURRENCY . number_format($sum['shipping'], 2)) ?> shipping</div></div></div>
  <div class="col-6 col-lg-2"><div class="stat h-100"><div class="text-secondary small">Orders delivered</div><div class="figure"><?= $sum['orders'] ?></div><div class="small text-secondary"><?= $sum['units'] ?> watches</div></div></div>
  <div class="col-6 col-lg-2"><div class="stat h-100"><div class="text-secondary small">Gross profit</div><div class="figure"><?= (CURRENCY . number_format($sum['gross_profit'], 2)) ?></div><div class="small text-secondary">sales &minus; cost</div></div></div>
  <div class="col-6 col-lg-2"><div class="stat h-100"><div class="text-secondary small">Expenses</div><div class="figure"><?= (CURRENCY . number_format($sum['expenses'], 2)) ?></div></div></div>
  <div class="col-6 col-lg-2"><div class="stat h-100"><div class="text-secondary small">Net income</div><div class="figure <?= $sum['net'] < 0 ? 'text-danger' : '' ?>"><?= (CURRENCY . number_format($sum['net'], 2)) ?></div></div></div>
</div>

<div class="panel mb-4">
  <h2 class="h5">Income vs expenses <?= $monthly ? 'per month' : 'per day' ?></h2>
  <div class="chart-box"><canvas id="incomeChart" aria-label="Income and expenses chart" role="img"></canvas></div>
</div>

<div class="panel p-0 table-responsive">
  <table class="table table-hover mb-0">
    <caption class="px-3 pt-3 caption-top">Delivered orders in this range</caption>
    <thead><tr><th scope="col">Order</th><th scope="col">Delivered</th><th scope="col">Customer</th><th scope="col" class="num text-end">Watches</th><th scope="col" class="num text-end">Items</th><th scope="col" class="num text-end">Shipping</th><th scope="col" class="num text-end">Total</th></tr></thead>
    <tbody>
    <?php if (!$orders): ?><tr><td colspan="7" class="text-center text-secondary py-4">No delivered orders in this range.</td></tr><?php endif; ?>
    <?php foreach ($orders as $o): ?>
      <tr>
        <td><a href="<?= BASE_URL . '/admin/orderDetails.php?id=' . (int)$o['orderinfo_id'] ?>">#<?= (int)$o['orderinfo_id'] ?></a></td>
        <td><?= htmlspecialchars((string)(date('M j, Y', strtotime($o['sale_date'])))) ?></td>
        <td><?= htmlspecialchars((string)($o['fname'] . ' ' . $o['lname'])) ?></td>
        <td class="num text-end"><?= (int)$o['pieces'] ?></td>
        <td class="num text-end"><?= (CURRENCY . number_format($o['items'], 2)) ?></td>
        <td class="num text-end"><?= (CURRENCY . number_format($o['shipping'], 2)) ?></td>
        <td class="num text-end"><?= (CURRENCY . number_format($o['items'] + $o['shipping'], 2)) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
    <?php if ($orders): ?><tfoot><tr class="fw-bold"><td colspan="6" class="text-end">Total income</td><td class="num text-end"><?= (CURRENCY . number_format($sum['income'], 2)) ?></td></tr></tfoot><?php endif; ?>
  </table>
</div>

<?= '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>' ?>
<script>
  const cur = <?= json_encode(CURRENCY, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;
  new Chart(document.getElementById('incomeChart'), {
    type: 'bar',
    data: {
      labels: <?= json_encode($labels, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>,
      datasets: [
        { label: 'Income', data: <?= json_encode($dIncome, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>, backgroundColor: '#000000' },
        { label: 'Expenses', data: <?= json_encode($dExp, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>, backgroundColor: '#999999' }
      ]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { tooltip: { callbacks: { label: c => c.dataset.label + ': ' + cur + Number(c.parsed.y).toLocaleString(undefined, {minimumFractionDigits: 2}) } } },
      scales: { y: { beginAtZero: true, ticks: { callback: v => cur + Number(v).toLocaleString() } } }
    }
  });
</script>
<?php include('../includes/footer.php'); ?>
