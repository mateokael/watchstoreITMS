<?php
// Home page. The full shop with filters is shop.php.
session_start();
include('includes/config.php');

$items = mysqli_fetch_all(mysqli_execute_query($conn, 'SELECT i.*, s.quantity AS stock, s.low_stock_level,
            (SELECT AVG(r.rating) FROM review r WHERE r.item_id = i.item_id) AS avg_rating,
            (SELECT COUNT(*) FROM review r WHERE r.item_id = i.item_id) AS review_count
     FROM item i JOIN stock s ON s.item_id = i.item_id
     WHERE i.is_active = 1 ORDER BY i.item_id DESC LIMIT 4'), MYSQLI_ASSOC);
$wished = (isset($_SESSION['user_id']) ? array_map('intval', array_column(mysqli_fetch_all(mysqli_execute_query($conn, 'SELECT item_id FROM wishlist WHERE user_id = ?', [(int)$_SESSION['user_id']]), MYSQLI_ASSOC), 'item_id')) : []);

$pageTitle = 'Home';
include('includes/header.php');
?>
<section class="hero">
  <div>
    <?php if (isset($_SESSION['user_id'])): ?>
      <p class="text-secondary mb-1">Welcome back, <?= htmlspecialchars((string)($_SESSION['username'] ?? '')) ?></p>
      <h1>Ready for your next watch?</h1>
      <p>Pick up where you left off: browse the shop, check your wishlist or see where your order is.</p>
      <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-brass btn-lg" href="<?= BASE_URL ?>/shop.php">Shop watches</a>
        <a class="btn btn-outline-primary btn-lg" href="<?= BASE_URL ?>/user/myorders.php">My orders</a>
      </div>
    <?php else: ?>
      <h1>A simple place to buy a good watch.</h1>
      <p>Quartz, automatic or solar. Browse the shop, save the ones you like, and we email you at every step of your order.</p>
      <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-brass btn-lg" href="<?= BASE_URL ?>/shop.php">Shop watches</a>
        <a class="btn btn-primary btn-lg" href="<?= BASE_URL ?>/user/login.php">Log in</a>
        <a class="btn btn-outline-primary btn-lg" href="<?= BASE_URL ?>/user/register.php">Create account</a>
      </div>
    <?php endif; ?>
  </div>
  <div class="dial" aria-hidden="true">
    <?php for ($i = 0; $i < 12; $i++): ?><span class="tick" style="--r: <?= $i * 30 ?>deg"></span><?php endfor; ?>
    <span class="hand hour"></span><span class="hand minute"></span><span class="hand second"></span><span class="cap"></span>
  </div>
</section>

<div class="d-flex justify-content-between align-items-end mb-3">
  <h2 class="mb-0">New arrivals</h2>
  <a href="<?= BASE_URL ?>/shop.php">See all watches</a>
</div>
<?php if (!$items): ?>
  <div class="panel text-center py-5"><p class="mb-0 text-secondary">No watches yet. Check back soon.</p></div>
<?php else: ?>
  <div class="product-grid">
    <?php foreach ($items as $it) { include('includes/product_card.php'); } ?>
  </div>
<?php endif; ?>

<?php if (!isset($_SESSION['user_id'])): ?>
<section class="panel text-center mt-5 py-4">
  <h2>Already have an account?</h2>
  <p class="text-secondary">Log in to check out faster, see your orders and keep a wishlist.</p>
  <a class="btn btn-primary" href="<?= BASE_URL ?>/user/login.php">Log in</a>
  <a class="btn btn-outline-primary" href="<?= BASE_URL ?>/user/register.php">Create account</a>
</section>
<?php endif; ?>
<?php include('includes/footer.php'); ?>
