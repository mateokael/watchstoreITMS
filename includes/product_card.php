<?php
// One watch card. Used by the home page (new arrivals) and the shop page, so they always look the same.
$id = (int)$it['item_id'];
$soldOut = (int)$it['stock'] < 1;
?>
  <article class="product-card">
    <div class="heart">
      <?php if (isset($_SESSION['user_id'])): $on = in_array($id, $wished, true); ?>
        <form method="post" action="<?= BASE_URL ?>/user/wishlist_toggle.php">
          <?= '<input type="hidden" name="back" value="' . htmlspecialchars($_SERVER['REQUEST_URI']) . '">' ?>
          <input type="hidden" name="item_id" value="<?= $id ?>">
          <button type="submit" class="<?= $on ? 'on' : '' ?>" aria-label="<?= $on ? 'Remove from wishlist' : 'Add to wishlist' ?>">
            <i class="fa-<?= $on ? 'solid' : 'regular' ?> fa-heart"></i>
          </button>
        </form>
      <?php endif; ?>
    </div>
    <a class="product-thumb" href="<?= BASE_URL . '/product.php?id=' . $id ?>">
      <img src="<?= htmlspecialchars((string)((is_file(ROOT_DIR . '/' . $it['img_path']) ? BASE_URL . '/' . $it['img_path'] : BASE_URL . '/assets/placeholder.svg'))) ?>" alt="<?= htmlspecialchars((string)(($it['brand'] . ' ' . $it['model']))) ?>" loading="lazy">
    </a>
    <div class="product-body">
      <span class="product-brand"><?= htmlspecialchars((string)$it['brand']) ?></span>
      <a class="product-model" href="<?= BASE_URL . '/product.php?id=' . $id ?>"><?= htmlspecialchars((string)$it['model']) ?></a>
      <span class="product-spec"><?= ucfirst($it['movement']) ?>, <?= $it['case_size_mm'] ? (int)$it['case_size_mm'] . ' mm' : 'size n/a' ?>, <?= htmlspecialchars((string)($it['strap_type'] ?: 'strap n/a')) ?></span>
      <?php if ($it['review_count'] > 0): ?>
        <span class="rating small" title="<?= number_format((float)$it['avg_rating'], 1) ?> out of 5"><?= (str_repeat('★', (int)round((float)$it['avg_rating'])) . str_repeat('☆', 5 - (int)round((float)$it['avg_rating']))) ?> <span class="text-secondary">(<?= (int)$it['review_count'] ?>)</span></span>
      <?php endif; ?>
      <span class="product-price price"><?= (CURRENCY . number_format($it['sell_price'], 2)) ?></span>
      <?php if ($soldOut): ?>
        <span class="badge badge-sold align-self-start">Sold out</span>
      <?php elseif ((int)$it['stock'] <= (int)$it['low_stock_level']): ?>
        <span class="badge badge-low align-self-start">Only <?= (int)$it['stock'] ?> left</span>
      <?php endif; ?>
      <form method="post" action="<?= BASE_URL ?>/cart_update.php" class="mt-auto pt-2">
        <?= '<input type="hidden" name="back" value="' . htmlspecialchars($_SERVER['REQUEST_URI']) . '">' ?>
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="item_id" value="<?= $id ?>">
        <input type="hidden" name="qty" value="1">
        <button type="submit" class="btn btn-primary w-100" <?= $soldOut ? 'disabled' : '' ?>>Add to cart</button>
      </form>
    </div>
  </article>
