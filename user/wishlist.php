<?php
session_start();
include('../includes/config.php');
include('../includes/guard_login.php');

$items = mysqli_fetch_all(mysqli_execute_query($conn, 'SELECT i.*, s.quantity AS stock FROM wishlist w
                 JOIN item i ON i.item_id = w.item_id JOIN stock s ON s.item_id = i.item_id
                 WHERE w.user_id = ? ORDER BY i.brand, i.model', [(int)$_SESSION['user_id']]), MYSQLI_ASSOC);

$pageTitle = 'Wishlist';
include('../includes/header.php');
?>
<h1 class="mb-3">Your wishlist</h1>
<?php if (!$items): ?>
  <div class="panel text-center py-5">
    <h2>Nothing saved yet.</h2>
    <p class="text-secondary">Tap the heart on any watch to keep it here.</p>
    <a class="btn btn-primary" href="<?= BASE_URL ?>/shop.php">Browse watches</a>
  </div>
<?php else: ?>
<div class="panel p-0 table-responsive">
  <table class="table mb-0">
    <tbody>
    <?php foreach ($items as $it): $id = (int)$it['item_id']; $ok = $it['is_active'] && (int)$it['stock'] > 0; ?>
      <tr>
        <td style="width:80px"><img class="cart-thumb" src="<?= htmlspecialchars((string)((is_file(ROOT_DIR . '/' . $it['img_path']) ? BASE_URL . '/' . $it['img_path'] : BASE_URL . '/assets/placeholder.svg'))) ?>" alt=""></td>
        <td><a href="<?= BASE_URL . '/product.php?id=' . $id ?>"><?= htmlspecialchars((string)(($it['brand'] . ' ' . $it['model']))) ?></a>
          <div class="small text-secondary"><?= !$it['is_active'] ? 'No longer sold' : ((int)$it['stock'] < 1 ? 'Sold out' : 'In stock') ?></div></td>
        <td class="num text-end"><?= (CURRENCY . number_format($it['sell_price'], 2)) ?></td>
        <td class="text-end">
          <div class="d-flex gap-2 justify-content-end">
            <form method="post" action="<?= BASE_URL ?>/cart_update.php">
<?= '<input type="hidden" name="back" value="' . htmlspecialchars($_SERVER['REQUEST_URI']) . '">' ?>
              <input type="hidden" name="action" value="add"><input type="hidden" name="item_id" value="<?= $id ?>"><input type="hidden" name="qty" value="1">
              <button class="btn btn-sm btn-primary" type="submit" <?= $ok ? '' : 'disabled' ?>>Add to cart</button>
            </form>
            <form method="post" action="<?= BASE_URL ?>/user/wishlist_toggle.php">
<?= '<input type="hidden" name="back" value="' . htmlspecialchars($_SERVER['REQUEST_URI']) . '">' ?><input type="hidden" name="item_id" value="<?= $id ?>">
              <button class="btn btn-sm btn-outline-danger" type="submit" aria-label="Remove <?= htmlspecialchars((string)(($it['brand'] . ' ' . $it['model']))) ?> from wishlist"><i class="fa-solid fa-xmark"></i></button>
            </form>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
<?php include('../includes/footer.php'); ?>
