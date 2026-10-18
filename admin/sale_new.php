<?php
// Staff (admin or employee): ring up an in-store sale for a customer who is standing at the counter.
session_start();
include('../includes/config.php');
include('../includes/guard_staff.php');

$customers = mysqli_fetch_all(mysqli_execute_query($conn, "SELECT c.customer_id, c.fname, c.lname, u.username
                     FROM customer c INNER JOIN users u ON u.user_id = c.user_id
                     WHERE u.status = 'active' AND u.role = 'user' ORDER BY c.lname, c.fname"), MYSQLI_ASSOC);
$items = mysqli_fetch_all(mysqli_execute_query($conn, 'SELECT i.item_id, i.brand, i.model, i.sell_price, s.quantity
                 FROM item i INNER JOIN stock s ON s.item_id = i.item_id
                 WHERE i.is_active = 1 AND s.quantity > 0 ORDER BY i.brand, i.model'), MYSQLI_ASSOC);

$pageTitle = 'New in-store sale';
include('../includes/header.php');
?>
<h1 class="mb-1">New in-store sale</h1>
<p class="text-secondary">For a customer buying at the counter. The sale is completed immediately and a receipt is made.</p>

<?php if (!$customers): ?>
  <div class="panel">No customers yet. <?php if ((($_SESSION['role'] ?? '') === 'admin')): ?><a href="<?= BASE_URL ?>/admin/user_edit.php">Add a customer</a> first.<?php else: ?>Ask the admin to add the customer first (or have them register on the site).<?php endif; ?></div>
<?php elseif (!$items): ?>
  <div class="panel">Nothing is in stock.</div>
<?php else: ?>
<form method="post" action="<?= BASE_URL ?>/admin/sale_save.php" class="panel" style="max-width:760px" novalidate>
  <div class="mb-3">
    <label class="form-label" for="customer_id">Customer</label>
    <select class="form-select" id="customer_id" name="customer_id" required>
      <option value="">Choose a customer...</option>
      <?php foreach ($customers as $c): ?><option value="<?= (int)$c['customer_id'] ?>"><?= htmlspecialchars((string)($c['lname'] . ', ' . $c['fname'] . ' (' . $c['username'] . ')')) ?></option><?php endforeach; ?>
    </select>
  </div>

  <div class="mb-1 fw-bold">Watches</div>
  <div id="lines"></div>
  <button type="button" class="btn btn-sm btn-outline-secondary mb-3" id="addLine"><i class="fa-solid fa-plus"></i> Add another watch</button>

  <fieldset class="mb-3">
    <legend class="h6">Receipt for the customer</legend>
    <div class="form-check"><input class="form-check-input" type="radio" name="receipt_method" id="rm_print" value="print" checked><label class="form-check-label" for="rm_print">Print it now (hand it over in person)</label></div>
    <div class="form-check"><input class="form-check-input" type="radio" name="receipt_method" id="rm_email" value="email"><label class="form-check-label" for="rm_email">Email it to the customer's account email</label></div>
  </fieldset>

  <div class="d-flex justify-content-between align-items-center">
    <div class="fs-5">Total: <strong id="total">0.00</strong></div>
    <button class="btn btn-brass" type="submit">Complete sale</button>
  </div>
</form>

<script>
  const items = <?= json_encode(array_map(fn($i) => ['id' => (int)$i['item_id'], 'name' => $i['brand'] . ' ' . $i['model'], 'price' => (float)$i['sell_price'], 'stock' => (int)$i['quantity']], $items), JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;
  const cur = <?= json_encode(CURRENCY, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;
  const box = document.getElementById('lines');

  function addLine() {
    const row = document.createElement('div');
    row.className = 'd-flex gap-2 mb-2';
    const sel = document.createElement('select');
    sel.className = 'form-select'; sel.name = 'item_id[]'; sel.setAttribute('aria-label', 'Watch');
    sel.innerHTML = '<option value="">Choose a watch...</option>' + items.map(i =>
      `<option value="${i.id}">${i.name.replace(/</g, '&lt;')} - ${cur}${i.price.toLocaleString()} (${i.stock} in stock)</option>`).join('');
    const qty = document.createElement('input');
    qty.type = 'number'; qty.min = '1'; qty.value = '1'; qty.name = 'qty[]'; qty.className = 'form-control'; qty.style.maxWidth = '90px'; qty.setAttribute('aria-label', 'Quantity');
    const del = document.createElement('button');
    del.type = 'button'; del.className = 'btn btn-outline-danger'; del.innerHTML = '&times;'; del.setAttribute('aria-label', 'Remove line');
    del.onclick = () => { row.remove(); recalc(); };
    sel.onchange = qty.oninput = recalc;
    row.append(sel, qty, del);
    box.appendChild(row);
  }
  function recalc() {
    let t = 0;
    box.querySelectorAll('div').forEach(row => {
      const it = items.find(i => String(i.id) === row.querySelector('select').value);
      const q = parseInt(row.querySelector('input').value, 10) || 0;
      if (it) t += it.price * q;
    });
    document.getElementById('total').textContent = cur + t.toLocaleString(undefined, {minimumFractionDigits: 2});
  }
  document.getElementById('addLine').onclick = addLine;
  addLine();
</script>
<?php endif; ?>
<?php include('../includes/footer.php'); ?>
