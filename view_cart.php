<?php
session_start();
include('includes/config.php');

include('includes/cart_lines.php');
$subtotal = array_sum(array_column($lines, 'subtotal'));
$shipping = $lines ? SHIPPING_FEE : 0;

$pageTitle = 'Your cart';
include('includes/header.php');
?>
<h1 class="mb-3">Your cart</h1>

<?php if (!$lines): ?>
  <div class="panel text-center py-5">
    <h2>Your cart is empty.</h2>
    <p class="text-secondary">Pick a watch and it will show up here.</p>
    <a class="btn btn-primary" href="<?= BASE_URL ?>/index.php">Browse watches</a>
  </div>
<?php else: ?>
<form method="post" action="<?= BASE_URL ?>/cart_update.php">
  <input type="hidden" name="action" value="update">
  <div class="panel p-0 table-responsive">
    <table class="table mb-0">
      <thead><tr><th scope="col" colspan="2">Watch</th><th scope="col" class="num text-end">Price</th><th scope="col" style="width:110px">Quantity</th><th scope="col" class="num text-end">Total</th><th scope="col"><span class="visually-hidden">Remove</span></th></tr></thead>
      <tbody>
      <?php foreach ($lines as $l): $id = (int)$l['item_id']; ?>
        <tr>
          <td style="width:80px"><img class="cart-thumb" src="<?= htmlspecialchars((string)((is_file(ROOT_DIR . '/' . $l['img_path']) ? BASE_URL . '/' . $l['img_path'] : BASE_URL . '/assets/placeholder.svg'))) ?>" alt=""></td>
          <td><a href="<?= BASE_URL . '/product.php?id=' . $id ?>"><?= htmlspecialchars((string)(($l['brand'] . ' ' . $l['model']))) ?></a><div class="small text-secondary"><?= (int)$l['stock'] ?> in stock</div></td>
          <td class="num text-end"><?= (CURRENCY . number_format($l['sell_price'], 2)) ?></td>
          <td><input type="number" class="form-control" name="qty[<?= $id ?>]" value="<?= (int)$l['qty'] ?>" min="0" max="<?= (int)$l['stock'] ?>" aria-label="Quantity for <?= htmlspecialchars((string)(($l['brand'] . ' ' . $l['model']))) ?>"></td>
          <td class="num text-end"><?= (CURRENCY . number_format($l['subtotal'], 2)) ?></td>
          <td class="text-end"><button type="submit" name="remove" value="<?= $id ?>" class="btn btn-sm btn-outline-danger" aria-label="Remove <?= htmlspecialchars((string)(($l['brand'] . ' ' . $l['model']))) ?>"><i class="fa-solid fa-trash"></i></button></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="d-flex flex-wrap justify-content-between gap-3 mt-3">
    <div class="d-flex gap-2 align-items-start">
      <button class="btn btn-outline-primary" type="submit">Update quantities</button>
      <a class="btn btn-link" href="<?= BASE_URL ?>/index.php">Keep shopping</a>
    </div>
    <div class="panel" style="min-width:280px">
      <div class="d-flex justify-content-between"><span>Subtotal</span><span class="num"><?= (CURRENCY . number_format($subtotal, 2)) ?></span></div>
      <div class="d-flex justify-content-between"><span>Shipping</span><span class="num"><?= (CURRENCY . number_format($shipping, 2)) ?></span></div>
      <hr class="my-2">
      <div class="d-flex justify-content-between fw-bold fs-5"><span>Total</span><span class="num"><?= (CURRENCY . number_format($subtotal + $shipping, 2)) ?></span></div>
      <a class="btn btn-brass w-100 mt-3" href="<?= BASE_URL ?>/checkout.php">Go to checkout</a>
    </div>
  </div>
</form>
<form method="post" action="<?= BASE_URL ?>/cart_update.php" class="mt-2">
<input type="hidden" name="action" value="clear">
  <button class="btn btn-sm btn-link text-danger p-0" type="submit">Empty cart</button>
</form>
<?php endif; ?>

<?php include('includes/footer.php'); ?>
