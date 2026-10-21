<?php
// Include AFTER your redirect/guard logic, because this file starts sending HTML.
$pageTitle = $pageTitle ?? 'LANSTRO WATCHSTORE';
$containerClass = $containerClass ?? 'container';
$cartN = array_sum($_SESSION['cart'] ?? []);
$currentPage = basename((string)($_SERVER['PHP_SELF'] ?? ''));
$shopIsActive = in_array($currentPage, ['shop.php', 'product.php'], true);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars((string)$pageTitle) ?> | LANSTRO WATCHSTORE</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@400;500;700&family=Instrument+Serif&family=Roboto+Condensed:wght@400;500;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link href="<?= BASE_URL ?>/assets/css/style.css?v=<?= @filemtime(__DIR__ . '/../assets/css/style.css') ?: 1 ?>" rel="stylesheet">
</head>
<body>
<header class="site-header">
  <nav class="navbar navbar-expand-lg site-nav">
    <div class="container-fluid px-3 px-lg-5">
      <div class="top-row">
        <div class="top-left">
          <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav" aria-controls="nav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
          </button>
        </div>
        <a class="navbar-brand" href="<?= BASE_URL ?>/index.php">
          <span class="brand-word">LANSTR</span><span class="brand-watch-o" aria-hidden="true"><span class="brand-watch-o__hand brand-watch-o__hand--hour"></span><span class="brand-watch-o__hand brand-watch-o__hand--minute"></span><span class="brand-watch-o__pin"></span><span class="brand-watch-o__crown"></span></span><span class="visually-hidden">O</span><span class="brand-word brand-word--tail">WATCHSTORE</span>
        </a>
        <ul class="navbar-nav flex-row align-items-center top-right">
          <li class="nav-item">
            <form method="get" action="<?= BASE_URL ?>/shop.php" class="header-search" role="search">
              <input type="search" name="q" value="<?= htmlspecialchars((string)(trim($_GET['q'] ?? ''))) ?>" placeholder="Search" aria-label="Search">
              <button type="submit" aria-label="Search"><i class="fa-solid fa-magnifying-glass"></i></button>
            </form>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="<?= BASE_URL ?>/view_cart.php">
              <i class="fa-solid fa-bag-shopping"></i> Cart
              <?php if ($cartN > 0): ?><span class="badge cart-badge"><?= $cartN ?></span><?php endif; ?>
            </a>
          </li>
          <?php if (isset($_SESSION['user_id'])): ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              <?php [$avatarPath, $avatarName, $avatarSize, $avatarClass] = array_pad([$_SESSION['avatar'] ?? null, $_SESSION['username'] ?? '?', 28], 4, ''); include(ROOT_DIR . '/includes/avatar.php'); ?>
              <span><?= htmlspecialchars((string)($_SESSION['username'] ?? '')) ?></span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="<?= BASE_URL ?>/user/profile.php">My profile</a></li>
              <li><a class="dropdown-item" href="<?= BASE_URL ?>/user/myorders.php">My orders</a></li>
              <li><a class="dropdown-item" href="<?= BASE_URL ?>/user/wishlist.php">Wishlist</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="<?= BASE_URL ?>/user/logout.php">Log out</a></li>
            </ul>
          </li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/user/login.php">Log in</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/user/register.php">Register</a></li>
        <?php endif; ?>
        </ul>
      </div>
      <div class="collapse navbar-collapse justify-content-center" id="nav">
        <ul class="navbar-nav main-menu">
          <li class="nav-item"><a class="nav-link<?= $shopIsActive ? ' is-current' : '' ?>" href="<?= BASE_URL ?>/shop.php"<?= $shopIsActive ? ' aria-current="page"' : '' ?>>Shop</a></li>
          <li class="nav-item"><a class="nav-link<?= $currentPage === 'about.php' ? ' is-current' : '' ?>" href="<?= BASE_URL ?>/about.php"<?= $currentPage === 'about.php' ? ' aria-current="page"' : '' ?>>About Us</a></li>
          <li class="nav-item"><a class="nav-link<?= $currentPage === 'contact.php' ? ' is-current' : '' ?>" href="<?= BASE_URL ?>/contact.php"<?= $currentPage === 'contact.php' ? ' aria-current="page"' : '' ?>>Contact Us</a></li>
          <?php if ((($_SESSION['role'] ?? '') === 'admin')): ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">Admin</a>
            <ul class="dropdown-menu">
              <li><a class="dropdown-item" href="<?= BASE_URL ?>/admin/dashboard.php">Dashboard</a></li>
              <li><a class="dropdown-item" href="<?= BASE_URL ?>/item/index.php">Watches &amp; archive</a></li>
              <li><a class="dropdown-item" href="<?= BASE_URL ?>/admin/orders.php">Orders</a></li>
              <li><a class="dropdown-item" href="<?= BASE_URL ?>/admin/sale_new.php">New in-store sale</a></li>
              <li><a class="dropdown-item" href="<?= BASE_URL ?>/admin/users.php">Customers &amp; employees</a></li>
              <li><a class="dropdown-item" href="<?= BASE_URL ?>/admin/expenses.php">Expenses</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="<?= BASE_URL ?>/admin/income.php">Income (date range)</a></li>
              <li><a class="dropdown-item" href="<?= BASE_URL ?>/admin/reports.php">Reports</a></li>
            </ul>
          </li>
        <?php elseif ((($_SESSION['role'] ?? '') === 'employee')): ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">Staff</a>
            <ul class="dropdown-menu">
              <li><a class="dropdown-item" href="<?= BASE_URL ?>/admin/orders.php">Customer orders</a></li>
              <li><a class="dropdown-item" href="<?= BASE_URL ?>/admin/sale_new.php">New in-store sale</a></li>
              <li><a class="dropdown-item" href="<?= BASE_URL ?>/item/index.php">Restock</a></li>
            </ul>
          </li>
        <?php endif; ?>
        </ul>
      </div>
    </div>
  </nav>
</header>
<script>
(function () {
  var h = document.querySelector('.site-header');
  if (!h) return;
  function pad() { document.body.style.paddingTop = h.offsetHeight + 'px'; }
  pad();
  window.addEventListener('resize', pad);
  window.addEventListener('load', pad);
  if (window.ResizeObserver) new ResizeObserver(pad).observe(h);
})();
</script>
<main class="<?= htmlspecialchars((string)$containerClass) ?> py-4">
<?php include(ROOT_DIR . '/includes/alert.php'); ?>
