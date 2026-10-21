<?php
// Home page. The full shop with filters is shop.php.
session_start();
include('includes/config.php');

$homeWatches = mysqli_fetch_all(mysqli_execute_query($conn, 'SELECT i.*, s.quantity AS stock, s.low_stock_level,
            (SELECT AVG(r.rating) FROM review r WHERE r.item_id = i.item_id) AS avg_rating,
            (SELECT COUNT(*) FROM review r WHERE r.item_id = i.item_id) AS review_count
     FROM item i JOIN stock s ON s.item_id = i.item_id
     WHERE i.is_active = 1 ORDER BY i.item_id DESC LIMIT 4'), MYSQLI_ASSOC);
$wished = (isset($_SESSION['user_id']) ? array_map('intval', array_column(mysqli_fetch_all(mysqli_execute_query($conn, 'SELECT item_id FROM wishlist WHERE user_id = ?', [(int)$_SESSION['user_id']]), MYSQLI_ASSOC), 'item_id')) : []);

$pageTitle = 'Home';
include('includes/header.php');
?>
<section class="home-banner">
  <video class="home-banner__video" autoplay muted loop playsinline preload="metadata" aria-hidden="true">
    <source src="<?= BASE_URL ?>/images/banner/watch-campaign.mp4" type="video/mp4">
  </video>
  <div class="home-banner__content">
    <?php if (isset($_SESSION['user_id'])): ?>
      <p class="mb-1">Welcome back, <?= htmlspecialchars((string)($_SESSION['username'] ?? '')) ?></p>
      <h1>Ready for your next watch?</h1>
      <p>Pick up where you left off: browse the shop, check your wishlist or see where your order is.</p>
      <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-light btn-lg" href="<?= BASE_URL ?>/shop.php">Shop watches</a>
        <a class="btn btn-outline-light btn-lg" href="<?= BASE_URL ?>/user/myorders.php">My orders</a>
      </div>
    <?php else: ?>
      <h1>A simple place to buy a good watch.</h1>
      <p>Quartz, automatic or solar. Browse the shop, save the ones you like, and we email you at every step of your order.</p>
      <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-light btn-lg" href="<?= BASE_URL ?>/shop.php">Shop watches</a>
        <a class="btn btn-outline-light btn-lg" href="<?= BASE_URL ?>/user/login.php">Log in</a>
        <a class="btn btn-outline-light btn-lg" href="<?= BASE_URL ?>/user/register.php">Create account</a>
      </div>
    <?php endif; ?>
  </div>
</section>

<section class="home-featured">
  <div class="d-flex justify-content-between align-items-end mb-3">
    <h2 class="mb-0">Featured watches</h2>
    <a href="<?= BASE_URL ?>/shop.php">See all watches</a>
  </div>
  <?php if (!$homeWatches): ?>
    <div class="panel text-center py-5"><p class="mb-0 text-secondary">No watches yet. Check back soon.</p></div>
  <?php else: ?>
    <div class="product-grid">
      <?php foreach ($homeWatches as $it) { include('includes/product_card.php'); } ?>
    </div>
  <?php endif; ?>
</section>

<?php if (count($homeWatches) > 1): ?>
<section class="home-showcase" aria-label="More watches from the collection">
  <div id="homeShowcaseCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="5000" data-bs-pause="hover">
    <div class="carousel-inner">
      <?php foreach ($homeWatches as $n => $watch): ?>
        <div class="carousel-item <?= $n === 0 ? 'active' : '' ?>">
          <a class="home-showcase__slide" href="<?= BASE_URL ?>/product.php?id=<?= (int)$watch['item_id'] ?>">
            <div class="home-showcase__image">
              <img src="<?= htmlspecialchars((string)(is_file(ROOT_DIR . '/' . $watch['img_path']) ? BASE_URL . '/' . $watch['img_path'] : BASE_URL . '/assets/placeholder.svg')) ?>" alt="<?= htmlspecialchars((string)($watch['brand'] . ' ' . $watch['model'])) ?>" loading="lazy">
            </div>
            <div class="home-showcase__copy">
              <span class="home-showcase__eyebrow">A closer look</span>
              <h2><?= htmlspecialchars((string)($watch['brand'] . ' ' . $watch['model'])) ?></h2>
              <span class="home-showcase__link">Discover this watch <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></span>
            </div>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
    <button class="home-showcase__control home-showcase__control--prev" type="button" data-bs-target="#homeShowcaseCarousel" data-bs-slide="prev" aria-label="Previous watch">
      <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
    </button>
    <button class="home-showcase__control home-showcase__control--next" type="button" data-bs-target="#homeShowcaseCarousel" data-bs-slide="next" aria-label="Next watch">
      <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
    </button>
    <div class="carousel-indicators">
      <?php foreach ($homeWatches as $n => $watch): ?>
        <button type="button" data-bs-target="#homeShowcaseCarousel" data-bs-slide-to="<?= $n ?>" class="<?= $n === 0 ? 'active' : '' ?>" <?= $n === 0 ? 'aria-current="true"' : '' ?> aria-label="Show <?= htmlspecialchars((string)($watch['brand'] . ' ' . $watch['model'])) ?>"></button>
      <?php endforeach; ?>
    </div>
  </div>
</section>
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
