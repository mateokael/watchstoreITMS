<?php
// Admin only: Reports hub. Pick a From / To date, then click a report. Same page shows the result with a chart.
session_start();
include('../includes/config.php');
include('../includes/guard_admin.php');

include('../includes/date_range.php');
$today = date('Y-m-d');
$r = $_GET['r'] ?? '';
$titles = [
    'customer' => 'Customer Report',
    'receipts' => 'Receipt Records',
    'sales'    => 'Sales Report',
    'monthly'  => 'Monthly Income',
    'expense'  => 'Expense Report',
];
if (!isset($titles[$r])) {
    $r = '';
}
$notes = [
    'customer' => 'Delivered orders only: how many orders and how much each customer bought.',
    'receipts' => 'Every receipt issued in the date range, and how it reached the customer (emailed or printed).',
    'sales'    => 'Delivered orders only: units, sales, cost and profit for each watch.',
    'monthly'  => 'One row per month. Income = watches sold + shipping fees on delivered orders. Net = income - cost of watches - expenses.',
    'expense'  => 'Every expense recorded in the date range, grouped by category.',
];

$cols = [];
$rows = [];
$foot = [];
$chart = ['type' => 'bar', 'labels' => [], 'datasets' => [], 'horizontal' => false, 'money' => true];
$summary = '';

if ($r === 'customer') {
    $data = mysqli_fetch_all(mysqli_execute_query($conn, 'SELECT c.customer_id, c.fname, c.lname, u.username,
                           COUNT(DISTINCT o.orderinfo_id) AS orders,
                           SUM(ol.quantity) AS units,
                           SUM(ol.unit_price * ol.quantity) AS spent,
                           MAX(' . SALE_DATE_SQL . ') AS last_purchase
                    FROM customer c
                    INNER JOIN users u ON u.user_id = c.user_id
                    INNER JOIN orderinfo o ON o.customer_id = c.customer_id
                    INNER JOIN orderline ol ON ol.orderinfo_id = o.orderinfo_id
                    WHERE o.status = \'delivered\' AND ' . SALE_DATE_SQL . ' BETWEEN ? AND ?
                    GROUP BY c.customer_id, c.fname, c.lname, u.username
                    ORDER BY spent DESC', [$from, $to]), MYSQLI_ASSOC);
    $cols = [['Customer', 0], ['Username', 0], ['Orders', 1], ['Watches', 1], ['Total spent', 1], ['Last purchase', 0]];
    $t = ['orders' => 0, 'units' => 0, 'spent' => 0];
    foreach ($data as $d) {
        $rows[] = [htmlspecialchars((string)($d['fname'] . ' ' . $d['lname'])), htmlspecialchars((string)$d['username']), (int)$d['orders'], (int)$d['units'], (CURRENCY . number_format($d['spent'], 2)), htmlspecialchars((string)(date('M j, Y', strtotime($d['last_purchase']))))];
        foreach ($t as $k => $_) { $t[$k] += (float)$d[$k]; }
    }
    $foot = ['Total', '', (int)$t['orders'], (int)$t['units'], (CURRENCY . number_format($t['spent'], 2)), ''];
    $top = array_slice($data, 0, 10);
    $chart['horizontal'] = true;
    $chart['labels'] = array_map(fn($d) => $d['fname'] . ' ' . $d['lname'], $top);
    $chart['datasets'] = [['label' => 'Total spent', 'data' => array_map(fn($d) => round((float)$d['spent'], 2), $top), 'backgroundColor' => '#000000']];

} elseif ($r === 'receipts') {
    $data = mysqli_fetch_all(mysqli_execute_query($conn, 'SELECT r.receipt_id, r.orderinfo_id, r.method, r.issued_at, r.emailed_at, r.printed_at,
                           c.fname, c.lname, iu.username AS issuer,
                           SUM(ol.unit_price * ol.quantity) + o.shipping AS total
                    FROM receipt r
                    INNER JOIN orderinfo o ON o.orderinfo_id = r.orderinfo_id
                    INNER JOIN customer c ON c.customer_id = o.customer_id
                    INNER JOIN orderline ol ON ol.orderinfo_id = o.orderinfo_id
                    LEFT JOIN users iu ON iu.user_id = r.issued_by
                    WHERE DATE(r.issued_at) BETWEEN ? AND ?
                    GROUP BY r.receipt_id, r.orderinfo_id, r.method, r.issued_at, r.emailed_at, r.printed_at, c.fname, c.lname, iu.username, o.shipping
                    ORDER BY r.issued_at DESC', [$from, $to]), MYSQLI_ASSOC);
    $cols = [['Receipt no.', 0], ['Order', 0], ['Customer', 0], ['Issued', 0], ['Given to customer', 0], ['Issued by', 0], ['Total', 1]];
    $sum = 0.0;
    $counts = ['Emailed' => 0, 'Printed' => 0, 'Not yet given' => 0];
    foreach ($data as $d) {
        $txt = ($d['method'] === 'email' ? ($d['emailed_at'] ? 'Emailed' : 'Email not sent yet') : ($d['printed_at'] ? 'Printed' : 'Waiting to be printed'));
        $counts[$txt === 'Emailed' ? 'Emailed' : ($txt === 'Printed' ? 'Printed' : 'Not yet given')]++;
        $sum += (float)$d['total'];
        $rows[] = ['<a href="' . htmlspecialchars((string)(BASE_URL . '/user/receipt.php?id=' . (int)$d['orderinfo_id'])) . '">' . htmlspecialchars((string)(('R-' . str_pad((string)(int)$d['receipt_id'], 6, '0', STR_PAD_LEFT)))) . '</a>',
                   '<a href="' . htmlspecialchars((string)(BASE_URL . '/admin/orderDetails.php?id=' . (int)$d['orderinfo_id'])) . '">#' . (int)$d['orderinfo_id'] . '</a>',
                   htmlspecialchars((string)($d['fname'] . ' ' . $d['lname'])), htmlspecialchars((string)(date('M j, Y g:i A', strtotime($d['issued_at'])))), htmlspecialchars((string)$txt), htmlspecialchars((string)($d['issuer'] ?? '-')), (CURRENCY . number_format($d['total'], 2))];
    }
    $foot = [count($data) . ' receipt(s)', '', '', '', '', 'Total', (CURRENCY . number_format($sum, 2))];
    $chart['type'] = 'doughnut';
    $chart['money'] = false;
    $chart['labels'] = array_keys($counts);
    $chart['datasets'] = [['label' => 'Receipts', 'data' => array_values($counts), 'backgroundColor' => ['#000000', '#6b6b6b', '#bbbbbb']]];

} elseif ($r === 'sales') {
    $data = mysqli_fetch_all(mysqli_execute_query($conn, 'SELECT i.item_id, i.brand, i.model,
                           SUM(ol.quantity) AS units,
                           SUM(ol.unit_price * ol.quantity) AS sales,
                           SUM(ol.unit_cost * ol.quantity) AS cost,
                           SUM((ol.unit_price - ol.unit_cost) * ol.quantity) AS profit
                    FROM orderline ol
                    INNER JOIN orderinfo o ON o.orderinfo_id = ol.orderinfo_id
                    INNER JOIN item i ON i.item_id = ol.item_id
                    WHERE o.status = \'delivered\' AND ' . SALE_DATE_SQL . ' BETWEEN ? AND ?
                    GROUP BY i.item_id, i.brand, i.model
                    ORDER BY sales DESC', [$from, $to]), MYSQLI_ASSOC);
    $cols = [['Watch', 0], ['Units sold', 1], ['Sales', 1], ['Cost', 1], ['Profit', 1]];
    $t = ['units' => 0, 'sales' => 0, 'cost' => 0, 'profit' => 0];
    foreach ($data as $d) {
        $rows[] = [htmlspecialchars((string)(($d['brand'] . ' ' . $d['model']))), (int)$d['units'], (CURRENCY . number_format($d['sales'], 2)), (CURRENCY . number_format($d['cost'], 2)), (CURRENCY . number_format($d['profit'], 2))];
        foreach ($t as $k => $_) { $t[$k] += (float)$d[$k]; }
    }
    $foot = ['Total', (int)$t['units'], (CURRENCY . number_format($t['sales'], 2)), (CURRENCY . number_format($t['cost'], 2)), (CURRENCY . number_format($t['profit'], 2))];
    $top = array_slice($data, 0, 10);
    $chart['labels'] = array_map(fn($d) => $d['brand'] . ' ' . $d['model'], $top);
    $chart['datasets'] = [
        ['label' => 'Sales', 'data' => array_map(fn($d) => round((float)$d['sales'], 2), $top), 'backgroundColor' => '#000000'],
        ['label' => 'Profit', 'data' => array_map(fn($d) => round((float)$d['profit'], 2), $top), 'backgroundColor' => '#999999'],
    ];

} elseif ($r === 'monthly') {
    $byMonth = [];
    foreach (mysqli_fetch_all(mysqli_execute_query($conn, 'SELECT DATE_FORMAT(' . SALE_DATE_SQL . ', \'%Y-%m\') AS ym,
                            COUNT(DISTINCT o.orderinfo_id) AS orders,
                            SUM(ol.quantity) AS units,
                            SUM(ol.unit_price * ol.quantity) AS sales,
                            SUM(ol.unit_cost * ol.quantity) AS cogs
                     FROM orderinfo o
                     INNER JOIN orderline ol ON ol.orderinfo_id = o.orderinfo_id
                     WHERE o.status = \'delivered\' AND ' . SALE_DATE_SQL . ' BETWEEN ? AND ?
                     GROUP BY ym', [$from, $to]), MYSQLI_ASSOC) as $d) {
        $byMonth[$d['ym']] = ['orders' => (int)$d['orders'], 'units' => (int)$d['units'], 'sales' => (float)$d['sales'], 'cogs' => (float)$d['cogs'], 'ship' => 0.0, 'exp' => 0.0];
    }
    $blank = ['orders' => 0, 'units' => 0, 'sales' => 0.0, 'cogs' => 0.0, 'ship' => 0.0, 'exp' => 0.0];
    foreach (mysqli_fetch_all(mysqli_execute_query($conn, 'SELECT DATE_FORMAT(' . SALE_DATE_SQL . ', \'%Y-%m\') AS ym, SUM(o.shipping) AS ship
                     FROM orderinfo o WHERE o.status = \'delivered\' AND ' . SALE_DATE_SQL . ' BETWEEN ? AND ? GROUP BY ym', [$from, $to]), MYSQLI_ASSOC) as $d) {
        $byMonth[$d['ym']] = ($byMonth[$d['ym']] ?? $blank);
        $byMonth[$d['ym']]['ship'] = (float)$d['ship'];
    }
    foreach (mysqli_fetch_all(mysqli_execute_query($conn, 'SELECT DATE_FORMAT(expense_date, \'%Y-%m\') AS ym, SUM(amount) AS exp
                     FROM expense WHERE expense_date BETWEEN ? AND ? GROUP BY ym', [$from, $to]), MYSQLI_ASSOC) as $d) {
        $byMonth[$d['ym']] = ($byMonth[$d['ym']] ?? $blank);
        $byMonth[$d['ym']]['exp'] = (float)$d['exp'];
    }
    ksort($byMonth);

    $cols = [['Month', 0], ['Orders', 1], ['Watches', 1], ['Sales', 1], ['Shipping', 1], ['Total income', 1], ['Cost of watches', 1], ['Expenses', 1], ['Net income', 1]];
    $T = ['orders' => 0, 'units' => 0, 'sales' => 0.0, 'ship' => 0.0, 'cogs' => 0.0, 'exp' => 0.0];
    $labels = []; $inc = []; $exp = []; $net = [];
    foreach ($byMonth as $ym => $m) {
        $income = $m['sales'] + $m['ship'];
        $netM   = $income - $m['cogs'] - $m['exp'];
        $rows[] = [htmlspecialchars((string)(date('F Y', strtotime($ym . '-01')))), $m['orders'], $m['units'], (CURRENCY . number_format($m['sales'], 2)), (CURRENCY . number_format($m['ship'], 2)), (CURRENCY . number_format($income, 2)), (CURRENCY . number_format($m['cogs'], 2)), (CURRENCY . number_format($m['exp'], 2)), (CURRENCY . number_format($netM, 2))];
        foreach ($T as $k => $_) { $T[$k] += $m[$k]; }
        $labels[] = date('M Y', strtotime($ym . '-01'));
        $inc[] = round($income, 2); $exp[] = round($m['exp'], 2); $net[] = round($netM, 2);
    }
    $foot = ['Total', $T['orders'], $T['units'], (CURRENCY . number_format($T['sales'], 2)), (CURRENCY . number_format($T['ship'], 2)), (CURRENCY . number_format($T['sales'] + $T['ship'], 2)), (CURRENCY . number_format($T['cogs'], 2)), (CURRENCY . number_format($T['exp'], 2)), (CURRENCY . number_format($T['sales'] + $T['ship'] - $T['cogs'] - $T['exp'], 2))];
    $chart['labels'] = $labels;
    $chart['datasets'] = [
        ['label' => 'Income', 'data' => $inc, 'backgroundColor' => '#000000'],
        ['label' => 'Expenses', 'data' => $exp, 'backgroundColor' => '#999999'],
        ['label' => 'Net income', 'data' => $net, 'type' => 'line', 'borderColor' => '#6b6b6b', 'backgroundColor' => '#6b6b6b', 'tension' => 0.25],
    ];

} elseif ($r === 'expense') {
    $data = mysqli_fetch_all(mysqli_execute_query($conn, 'SELECT ec.name,
                           COUNT(e.expense_id) AS entries,
                           SUM(e.amount) AS total,
                           AVG(e.amount) AS average,
                           MAX(e.amount) AS biggest
                    FROM expense e
                    INNER JOIN expense_category ec ON ec.category_id = e.category_id
                    WHERE e.expense_date BETWEEN ? AND ?
                    GROUP BY ec.category_id, ec.name
                    ORDER BY total DESC', [$from, $to]), MYSQLI_ASSOC);
    $cols = [['Category', 0], ['Entries', 1], ['Total', 1], ['Average', 1], ['Biggest', 1]];
    $t = ['entries' => 0, 'total' => 0];
    foreach ($data as $d) {
        $rows[] = [htmlspecialchars((string)$d['name']), (int)$d['entries'], (CURRENCY . number_format($d['total'], 2)), (CURRENCY . number_format($d['average'], 2)), (CURRENCY . number_format($d['biggest'], 2))];
        foreach ($t as $k => $_) { $t[$k] += (float)$d[$k]; }
    }
    $foot = ['Total', (int)$t['entries'], (CURRENCY . number_format($t['total'], 2)), '', ''];
    $chart['type'] = 'doughnut';
    $chart['labels'] = array_column($data, 'name');
    $chart['datasets'] = [['label' => 'Expenses', 'data' => array_map(fn($d) => round((float)$d['total'], 2), $data),
        'backgroundColor' => ['#000000', '#6b6b6b', '#bbbbbb', '#333333', '#999999', '#dddddd', '#555555', '#777777']]];
}

$pageTitle = $r ? $titles[$r] : 'Reports';
$containerClass = 'container-fluid px-3 px-lg-5 admin-open-page admin-reports';
include('../includes/header.php');
?>
<section class="reports-overview">
<h1 class="admin-page-title mb-4">Reports</h1>

<form method="get" class="panel no-print">
  <div class="rep-toolbar">
    <div><label for="from">From</label><input type="date" class="form-control" id="from" name="from" value="<?= htmlspecialchars((string)$from) ?>"></div>
    <div><label for="to">To</label><input type="date" class="form-control" id="to" name="to" value="<?= htmlspecialchars((string)$to) ?>" max="<?= htmlspecialchars((string)$today) ?>"></div>
    <div class="text-secondary small pb-2">Choose the dates, then pick a report.</div>
  </div>
  <div class="rep-grid">
    <?php
    $tiles = [
        'customer' => ['fa-users',     'Customer Report',  'Orders and spending per customer'],
        'receipts' => ['fa-receipt',   'Receipt Records',  'Receipts issued, emailed or printed'],
        'sales'    => ['fa-chart-line','Sales Report',     'Units, sales and profit per watch'],
        'monthly'  => ['fa-coins',     'Monthly Income',   'Income, expenses and net by month'],
        'expense'  => ['fa-wallet',    'Expense Report',   'Spending grouped by category'],
    ];
    foreach ($tiles as $key => [$icon, $name, $desc]): ?>
      <button type="submit" name="r" value="<?= $key ?>" class="rep-tile <?= $r === $key ? 'is-on' : '' ?>" <?= $r === $key ? 'aria-current="true"' : '' ?>>
        <span class="rep-ico"><i class="fa-solid <?= $icon ?>"></i></span>
        <span><span class="rep-name"><?= htmlspecialchars((string)$name) ?></span><span class="rep-desc"><?= htmlspecialchars((string)$desc) ?></span></span>
      </button>
    <?php endforeach; ?>
  </div>
</form>
</section>

<?php if ($r): ?>
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-4 mb-1">
    <h2 class="admin-section-title mb-0"><?= htmlspecialchars((string)$titles[$r]) ?></h2>
    <button class="btn btn-outline-primary no-print" type="button" onclick="window.print()"><i class="fa-solid fa-print"></i> Print / Save as PDF</button>
  </div>
  <p class="text-secondary"><?= htmlspecialchars((string)$notes[$r]) ?> Range: <?= htmlspecialchars((string)(date('M j, Y', strtotime($from)))) ?> &ndash; <?= htmlspecialchars((string)(date('M j, Y', strtotime($to)))) ?>.</p>

  <?php if (!$rows): ?>
    <div class="panel text-center text-secondary py-5">Nothing to report for this range.</div>
  <?php else: ?>
    <div class="panel mb-4">
      <div class="chart-box"><canvas id="reportChart" aria-label="<?= htmlspecialchars((string)$titles[$r]) ?> chart" role="img"></canvas></div>
    </div>
    <div class="panel p-0 table-responsive">
      <table class="table table-hover mb-0">
        <thead><tr>
          <?php foreach ($cols as [$label, $isNum]): ?><th scope="col" class="<?= $isNum ? 'num text-end' : '' ?>"><?= htmlspecialchars((string)$label) ?></th><?php endforeach; ?>
        </tr></thead>
        <tbody>
          <?php foreach ($rows as $row): ?>
            <tr><?php foreach ($row as $i => $cell): ?><td class="<?= $cols[$i][1] ? 'num text-end' : '' ?>"><?= $cell ?></td><?php endforeach; ?></tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot><tr class="fw-bold"><?php foreach ($foot as $i => $cell): ?><td class="<?= $cols[$i][1] ? 'num text-end' : '' ?>"><?= $cell ?></td><?php endforeach; ?></tr></tfoot>
      </table>
    </div>

    <?= '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>' ?>
    <script>
      const cur = <?= json_encode(CURRENCY, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;
      const cfg = <?= json_encode($chart, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;
      const isPie = cfg.type === 'doughnut';
      const fmt = v => cfg.money ? cur + Number(v).toLocaleString(undefined, {minimumFractionDigits: 2}) : Number(v).toLocaleString();
      const axis = { beginAtZero: true, ticks: { callback: v => cfg.money ? cur + Number(v).toLocaleString() : v } };
      const reportChart = new Chart(document.getElementById('reportChart'), {
        type: cfg.type,
        data: { labels: cfg.labels, datasets: cfg.datasets },
        options: {
          responsive: true, maintainAspectRatio: false,
          indexAxis: cfg.horizontal ? 'y' : 'x',
          plugins: {
            legend: { display: isPie || cfg.datasets.length > 1, position: isPie ? 'right' : 'top' },
            tooltip: { callbacks: { label: c => {
              const v = isPie ? c.parsed : (cfg.horizontal ? c.parsed.x : c.parsed.y);
              return (isPie ? c.label : c.dataset.label) + ': ' + fmt(v);
            } } }
          },
          scales: isPie ? {} : (cfg.horizontal ? { x: axis } : { y: axis })
        }
      });
      window.addEventListener('beforeprint', () => reportChart.resize());
      window.addEventListener('afterprint', () => reportChart.resize());
    </script>
  <?php endif; ?>
<?php endif; ?>
<?php include('../includes/footer.php'); ?>
