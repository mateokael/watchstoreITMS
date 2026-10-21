</main>
<footer class="site-footer">
  <div class="container-fluid px-3 px-lg-5">
    <div class="row g-4 footer-cols">
      <div class="col-12 col-md-4">
        <a class="footer-brand" href="<?= BASE_URL ?>/index.php">
          <span class="brand-word">LANSTR</span><span class="brand-watch-o" aria-hidden="true"><span class="brand-watch-o__hand brand-watch-o__hand--hour"></span><span class="brand-watch-o__hand brand-watch-o__hand--minute"></span><span class="brand-watch-o__pin"></span><span class="brand-watch-o__crown"></span></span><span class="visually-hidden">O</span><span class="brand-word brand-word--tail">WATCHSTORE</span>
        </a>
      </div>
      <div class="col-6 col-md-2">
        <h3 class="footer-title">Shop</h3>
        <ul class="footer-links">
          <li><a href="<?= BASE_URL ?>/shop.php">Watch</a></li>
          <li><a href="<?= BASE_URL ?>/view_cart.php">Cart</a></li>
        </ul>
      </div>
      <div class="col-6 col-md-3">
        <h3 class="footer-title">Account</h3>
        <ul class="footer-links">
          <?php if (isset($_SESSION['user_id'])): ?>
            <li><a href="<?= BASE_URL ?>/user/profile.php">My profile</a></li>
            <li><a href="<?= BASE_URL ?>/user/myorders.php">My orders</a></li>
            <li><a href="<?= BASE_URL ?>/user/wishlist.php">Wishlist</a></li>
            <li><a href="<?= BASE_URL ?>/user/logout.php">Log out</a></li>
          <?php else: ?>
            <li><a href="<?= BASE_URL ?>/user/login.php">Log in</a></li>
            <li><a href="<?= BASE_URL ?>/user/register.php">Register</a></li>
          <?php endif; ?>
        </ul>
      </div>
      <div class="col-12 col-md-3">
        <h3 class="footer-title">Email updates</h3>
        <p class="footer-text mb-0">Confirmation, shipped and delivered, straight to your inbox.</p>
      </div>
    </div>

</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
