<?php
session_start();
include('includes/config.php');

$pageTitle = 'About Us';
include('includes/header.php');
?>
<section class="info-page about-page">
  <p class="text-uppercase small mb-2">About the store</p>
  <h1>Time, chosen your way.</h1>
  <p class="lead">A simple place to find a watch that fits your style and your everyday life.</p>
  <div class="about-copy">
    <p>Explore a selection of quartz, automatic, mechanical, solar, and smart watches. Browse the collection, compare product details, and place an order through our online store.</p>
    <p>Whether you are looking for an everyday essential or a watch for a special occasion, we make it easy to discover the right fit.</p>
    <a class="btn btn-primary btn-lg mt-3" href="<?= BASE_URL ?>/shop.php">Explore the collection</a>
  </div>
  <p class="about-contact">Have a question? <a href="<?= BASE_URL ?>/contact.php">Get in touch with us</a>.</p>
</section>
<?php include('includes/footer.php'); ?>
