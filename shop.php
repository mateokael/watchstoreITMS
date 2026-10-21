<?php
session_start();
include('includes/config.php');

// ---- read filters (all optional) ----
$q        = trim($_GET['q'] ?? '');
$brand    = trim($_GET['brand'] ?? '');
$movement = $_GET['movement'] ?? '';
$category = $_GET['category'] ?? '';
$min      = (($_GET['min'] ?? '') !== '' && is_numeric($_GET['min'])) ? (float)$_GET['min'] : null;
$max      = (($_GET['max'] ?? '') !== '' && is_numeric($_GET['max'])) ? (float)$_GET['max'] : null;
$inStock  = isset($_GET['in_stock']);
$sort     = $_GET['sort'] ?? 'newest';

$where  = ['i.is_active = 1'];
$params = [];
if ($q !== '') {
    $where[] = '(i.brand LIKE ? OR i.model LIKE ? OR i.description LIKE ?)';
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like);
}
if ($brand !== '')                                { $where[] = 'i.brand = ?';        $params[] = $brand; }
if (in_array($movement, MOVEMENTS, true))         { $where[] = 'i.movement = ?';     $params[] = $movement; }
if (in_array($category, CATEGORIES, true))        { $where[] = 'i.category = ?';     $params[] = $category; }
if ($min !== null)                                { $where[] = 'i.sell_price >= ?';  $params[] = $min; }
if ($max !== null)                                { $where[] = 'i.sell_price <= ?';  $params[] = $max; }
if ($inStock)                                     { $where[] = 's.quantity > 0'; }

$sorts = [
    'newest'     => 'i.item_id DESC',
    'price_asc'  => 'i.sell_price ASC',
    'price_desc' => 'i.sell_price DESC',
    'name'       => 'i.brand ASC, i.model ASC',
];
$orderBy = $sorts[$sort] ?? $sorts['newest'];

$items = mysqli_fetch_all(mysqli_execute_query($conn, "SELECT i.*, s.quantity AS stock, s.low_stock_level,
            (SELECT AVG(r.rating) FROM review r WHERE r.item_id = i.item_id) AS avg_rating,
            (SELECT COUNT(*) FROM review r WHERE r.item_id = i.item_id) AS review_count
     FROM item i JOIN stock s ON s.item_id = i.item_id
     WHERE " . implode(' AND ', $where) . " ORDER BY $orderBy", $params), MYSQLI_ASSOC);
$brands    = array_column(mysqli_fetch_all(mysqli_execute_query($conn, 'SELECT DISTINCT brand FROM item WHERE is_active = 1 ORDER BY brand'), MYSQLI_ASSOC), 'brand');
$wished    = (isset($_SESSION['user_id']) ? array_map('intval', array_column(mysqli_fetch_all(mysqli_execute_query($conn, 'SELECT item_id FROM wishlist WHERE user_id = ?', [(int)$_SESSION['user_id']]), MYSQLI_ASSOC), 'item_id')) : []);
$filtering = ($q !== '' || $brand !== '' || $movement !== '' || $category !== '' || $min !== null || $max !== null || $inStock);

$pageTitle = 'Shop';
$containerClass = 'container-fluid px-3 px-lg-5';
include('includes/header.php');
?>

<div class="page-head text-center">
  <?php if (!$filtering): ?>
  <section class="hero hero-center shop-intro">
    <div>
      <h1>Find the watch that fits your wrist and your week.</h1>
      <p>Quartz for easy days, automatic for the ones you want to feel, solar for never thinking about batteries. Filter by what matters to you.</p>
    </div>
    <div class="dial" aria-hidden="true">
      <?php for ($i = 0; $i < 12; $i++): ?><span class="tick" style="--r: <?= $i * 30 ?>deg"></span><?php endfor; ?>
      <span class="hand hour"></span><span class="hand minute"></span><span class="hand second"></span><span class="cap"></span>
    </div>
  </section>
  <?php endif; ?>
</div>

<div class="row g-4 shop-layout">
  <aside class="col-12 col-lg-3">
    <form method="get" id="filters" class="filter-bar">
      <h2 class="filters-title">Filters</h2>
      <?php if ($q !== ''): ?><input type="hidden" name="q" value="<?= htmlspecialchars((string)$q) ?>"><?php endif; ?>
      <button class="btn btn-primary" type="submit">Apply filters</button>
      <a class="btn btn-outline-primary ms-1" href="<?= BASE_URL ?>/shop.php">Clear</a>
      <hr class="filter-rule">

      <details class="filter-group" <?= $brand !== '' ? 'open' : '' ?>>
        <summary>Brand</summary>
        <div class="filter-body">
          <select class="form-select" id="brand" name="brand" aria-label="Brand">
            <option value="">All brands</option>
            <?php foreach ($brands as $b): ?><option value="<?= htmlspecialchars((string)$b) ?>" <?= $b === $brand ? 'selected' : '' ?>><?= htmlspecialchars((string)$b) ?></option><?php endforeach; ?>
          </select>
        </div>
      </details>
      <details class="filter-group" <?= $movement !== '' ? 'open' : '' ?>>
        <summary>Movement</summary>
        <div class="filter-body">
          <select class="form-select" id="movement" name="movement" aria-label="Movement">
            <option value="">Any</option>
            <?php foreach (MOVEMENTS as $m): ?><option value="<?= $m ?>" <?= $m === $movement ? 'selected' : '' ?>><?= ucfirst($m) ?></option><?php endforeach; ?>
          </select>
        </div>
      </details>
      <details class="filter-group" <?= $category !== '' ? 'open' : '' ?>>
        <summary>For</summary>
        <div class="filter-body">
          <select class="form-select" id="category" name="category" aria-label="For">
            <option value="">Anyone</option>
            <?php foreach (CATEGORIES as $c): ?><option value="<?= $c ?>" <?= $c === $category ? 'selected' : '' ?>><?= ucfirst($c) ?></option><?php endforeach; ?>
          </select>
        </div>
      </details>
      <details class="filter-group" <?= ($min !== null || $max !== null) ? 'open' : '' ?>>
        <summary>Price</summary>
        <div class="filter-body">
          <label class="form-label small mb-1" for="min">Min price</label>
          <input type="number" min="0" step="1" class="form-control mb-2" id="min" name="min" value="<?= $min !== null ? htmlspecialchars((string)$min) : '' ?>">
          <label class="form-label small mb-1" for="max">Max price</label>
          <input type="number" min="0" step="1" class="form-control" id="max" name="max" value="<?= $max !== null ? htmlspecialchars((string)$max) : '' ?>">
        </div>
      </details>
      <details class="filter-group" <?= $inStock ? 'open' : '' ?>>
        <summary>Stock</summary>
        <div class="filter-body">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="in_stock" name="in_stock" value="1" <?= $inStock ? 'checked' : '' ?>>
            <label class="form-check-label" for="in_stock">Only show watches in stock</label>
          </div>
        </div>
      </details>
    </form>
  </aside>

  <section class="col-12 col-lg-9">
    <div class="results-bar">
      <p class="results-count mb-0"><?= count($items) ?> watch<?= count($items) === 1 ? '' : 'es' ?><?= $filtering ? ' match your filters' : '' ?></p>
      <div class="sort-box">
        <label for="sort">Sort by</label>
        <select class="form-select" id="sort" name="sort" form="filters" onchange="this.form.submit()">
          <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
          <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price, low to high</option>
          <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price, high to low</option>
          <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Name</option>
        </select>
      </div>
    </div>

    <?php if (!$items): ?>
      <div class="panel text-center py-5">
        <h2>No watches match those filters.</h2>
        <p class="text-secondary">Try a wider price range or clear the filters.</p>
        <a class="btn btn-primary" href="<?= BASE_URL ?>/shop.php">Show all watches</a>
      </div>
    <?php else: ?>
    <div class="product-grid">
      <?php foreach ($items as $it) { include('includes/product_card.php'); } ?>
    </div>
    <?php endif; ?>
  </section>
</div>

<?php include('includes/footer.php'); ?>
