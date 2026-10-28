<?php
session_start();
include('includes/config.php');

$id = (int)($_GET['id'] ?? 0);
$it = mysqli_fetch_assoc(mysqli_execute_query($conn, 'SELECT i.*, s.quantity AS stock, s.low_stock_level,
                     (SELECT AVG(r.rating) FROM review r WHERE r.item_id = i.item_id) AS avg_rating
              FROM item i JOIN stock s ON s.item_id = i.item_id WHERE i.item_id = ?', [$id]));
if (!$it || (!$it['is_active'] && !(($_SESSION['role'] ?? '') === 'admin'))) {
    http_response_code(404);
    $_SESSION['warning'] = 'That watch could not be found.';
    header('Location: ' . BASE_URL . '/shop.php');
    exit();
}

$images = [];
if ($it['img_path']) {
    $images[] = $it['img_path'];
}
foreach (mysqli_fetch_all(mysqli_execute_query($conn, 'SELECT img_path FROM item_image WHERE item_id = ? ORDER BY image_id', [$id]), MYSQLI_ASSOC) as $g) {
    $images[] = $g['img_path'];
}

$reviews = mysqli_fetch_all(mysqli_execute_query($conn, 'SELECT r.*, u.email FROM review r JOIN users u ON u.user_id = r.user_id
                   WHERE r.item_id = ? ORDER BY r.created_at DESC', [$id]), MYSQLI_ASSOC);

$wished = in_array($id, (isset($_SESSION['user_id']) ? array_map('intval', array_column(mysqli_fetch_all(mysqli_execute_query($conn, 'SELECT item_id FROM wishlist WHERE user_id = ?', [(int)$_SESSION['user_id']]), MYSQLI_ASSOC), 'item_id')) : []), true);
$canReview = false;
$myReview = null;
if (isset($_SESSION['user_id'])) {
    $uid = (int)$_SESSION['user_id'];
    // Only customers whose order for this watch was delivered can review it
    $canReview = (bool)mysqli_fetch_row(mysqli_execute_query($conn, "SELECT 1 FROM orderline ol
        JOIN orderinfo o ON o.orderinfo_id = ol.orderinfo_id
        JOIN customer c ON c.customer_id = o.customer_id
        WHERE c.user_id = ? AND ol.item_id = ? AND o.status = 'delivered' LIMIT 1", [$uid, $id]));
    $myReview = mysqli_fetch_assoc(mysqli_execute_query($conn, 'SELECT rating, comment FROM review WHERE item_id = ? AND user_id = ?', [$id, $uid]));
}

$pageTitle = ($it['brand'] . ' ' . $it['model']);
include('includes/header.php');
$soldOut = (int)$it['stock'] < 1;
?>
<nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= BASE_URL ?>/shop.php">Shop</a></li><li class="breadcrumb-item active"><?= htmlspecialchars((string)(($it['brand'] . ' ' . $it['model']))) ?></li></ol></nav>

<div class="product-detail row g-4 g-lg-5 align-items-start">
  <div class="product-detail__gallery col-md-7">
    <?php if (count($images) > 1): ?>
      <div id="gallery" class="carousel slide main-shot" data-bs-ride="false">
        <div class="carousel-inner">
          <?php foreach ($images as $n => $p): ?>
            <div class="carousel-item <?= $n === 0 ? 'active' : '' ?>"><img src="<?= htmlspecialchars(product_image_url($p)) ?>" alt="<?= htmlspecialchars((string)(($it['brand'] . ' ' . $it['model']))) ?>, photo <?= $n + 1 ?>"></div>
          <?php endforeach; ?>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#gallery" data-bs-slide="prev"><span class="carousel-control-prev-icon" aria-hidden="true"></span><span class="visually-hidden">Previous photo</span></button>
        <button class="carousel-control-next" type="button" data-bs-target="#gallery" data-bs-slide="next"><span class="carousel-control-next-icon" aria-hidden="true"></span><span class="visually-hidden">Next photo</span></button>
      </div>
    <?php else: ?>
      <div class="main-shot"><img src="<?= htmlspecialchars(product_image_url($images[0] ?? null)) ?>" alt="<?= htmlspecialchars((string)(($it['brand'] . ' ' . $it['model']))) ?>"></div>
    <?php endif; ?>
  </div>

  <div class="product-detail__info col-md-5">
    <div class="product-brand"><?= htmlspecialchars((string)$it['brand']) ?></div>
    <h1 class="mb-2"><?= htmlspecialchars((string)$it['model']) ?></h1>
    <?php if ($reviews): ?>
      <p class="rating mb-2"><?= (str_repeat('★', (int)round((float)$it['avg_rating'])) . str_repeat('☆', 5 - (int)round((float)$it['avg_rating']))) ?> <span class="text-secondary"><?= number_format((float)$it['avg_rating'], 1) ?> from <?= count($reviews) ?> review<?= count($reviews) === 1 ? '' : 's' ?></span></p>
    <?php endif; ?>
    <p class="product-detail__price price mb-3"><?= (CURRENCY . number_format($it['sell_price'], 2)) ?></p>
    <?php if (!$it['is_active']): ?><div class="alert alert-secondary">This watch is archived and hidden from the shop (admin view).</div><?php endif; ?>
    <p class="product-detail__availability mb-3"><span>Availability</span>
      <?php if ($soldOut): ?><span class="availability-badge is-sold-out">Sold out</span>
      <?php elseif ((int)$it['stock'] <= (int)$it['low_stock_level']): ?><span class="availability-badge is-low-stock">Only <?= (int)$it['stock'] ?> left</span>
      <?php else: ?><span class="availability-badge is-in-stock">In stock</span><?php endif; ?>
    </p>

    <div class="product-purchase d-flex flex-wrap gap-2 align-items-end">
      <form method="post" action="<?= BASE_URL ?>/cart_update.php" class="d-flex gap-2 align-items-end">
<?= '<input type="hidden" name="back" value="' . htmlspecialchars($_SERVER['REQUEST_URI']) . '">' ?>
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="item_id" value="<?= $id ?>">
        <div>
          <label for="qty" class="form-label small mb-1">Quantity</label>
          <input type="number" class="form-control" style="width:90px" id="qty" name="qty" value="1" min="1" max="<?= max(1, (int)$it['stock']) ?>" <?= $soldOut ? 'disabled' : '' ?>>
        </div>
        <button class="btn btn-brass product-detail__add" type="submit" <?= $soldOut ? 'disabled' : '' ?>>Add to cart</button>
      </form>
      <?php if (isset($_SESSION['user_id'])): ?>
        <form method="post" action="<?= BASE_URL ?>/user/wishlist_toggle.php">
<?= '<input type="hidden" name="back" value="' . htmlspecialchars($_SERVER['REQUEST_URI']) . '">' ?>
          <input type="hidden" name="item_id" value="<?= $id ?>">
          <button class="btn btn-outline-primary" type="submit"><i class="fa-<?= $wished ? 'solid' : 'regular' ?> fa-heart"></i> <?= $wished ? 'Saved to wishlist' : 'Save to wishlist' ?></button>
        </form>
      <?php endif; ?>
    </div>

    <p class="mt-4"><?= nl2br(htmlspecialchars((string)$it['description'])) ?></p>

    <table class="table spec-table mb-3">
      <tr><th>Movement</th><td><?= ucfirst($it['movement']) ?></td></tr>
      <tr><th>For</th><td><?= ucfirst($it['category']) ?></td></tr>
      <tr><th>Case size</th><td><?= $it['case_size_mm'] ? (int)$it['case_size_mm'] . ' mm' : 'Not listed' ?></td></tr>
      <tr><th>Strap</th><td><?= htmlspecialchars((string)($it['strap_type'] ?: 'Not listed')) ?></td></tr>
      <tr><th>Water resistance</th><td><?= htmlspecialchars((string)($it['water_resistance'] ?: 'Not listed')) ?></td></tr>
    </table>
  </div>
</div>

<section class="mt-5" id="reviews">
  <h2>Reviews</h2>
  <div class="row g-4">
    <div class="col-md-7">
      <?php if (!$reviews): ?>
        <p class="text-secondary">No reviews yet. Customers can leave the first one once their order is delivered.</p>
      <?php endif; ?>
      <?php foreach ($reviews as $r): ?>
        <div class="review">
          <span class="rating"><?= (str_repeat('★', (int)round((float)$r['rating'])) . str_repeat('☆', 5 - (int)round((float)$r['rating']))) ?></span>
          <span class="small text-secondary ms-2"><?= htmlspecialchars((string)(substr($r['email'], 0, 2))) ?>*** on <?= htmlspecialchars((string)(date('M j, Y', strtotime($r['created_at'])))) ?></span>
          <?php if ($r['comment']): ?><p class="mb-0 mt-1"><?= nl2br(htmlspecialchars((string)$r['comment'])) ?></p><?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="col-md-5">
      <div class="panel">
        <?php if ($canReview): ?>
          <h3 class="h4"><?= $myReview ? 'Edit your review' : 'Write a review' ?></h3>
          <form method="post" action="<?= BASE_URL ?>/user/review_store.php">
            <input type="hidden" name="item_id" value="<?= $id ?>">
            <label class="form-label" for="rating">Rating</label>
            <select class="form-select mb-2" id="rating" name="rating" required>
              <?php for ($n = 5; $n >= 1; $n--): ?><option value="<?= $n ?>" <?= ($_SESSION['old']['rating'] ?? $myReview['rating'] ?? 5) == $n ? 'selected' : '' ?>><?= $n ?> out of 5</option><?php endfor; ?>
            </select>
            <label class="form-label" for="comment">Comment (optional)</label>
            <textarea class="form-control mb-3" id="comment" name="comment" rows="3" maxlength="500"><?= htmlspecialchars((string)($_SESSION['old']['comment'] ?? $myReview['comment'] ?? '')) ?></textarea>
            <button class="btn btn-primary" type="submit">Save review</button>
          </form>
        <?php elseif (isset($_SESSION['user_id'])): ?>
          <p class="mb-0 text-secondary">Reviews are open to customers who have received this watch (delivered order).</p>
        <?php else: ?>
          <p class="mb-0"><a href="<?= BASE_URL ?>/user/login.php">Log in</a> to review watches you've bought.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<?php unset($_SESSION['old'], $_SESSION['errors']); include('includes/footer.php'); ?>
