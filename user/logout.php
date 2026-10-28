<?php
session_start();
include('../includes/config.php');
include('../includes/cart_store.php');
cart_sync($conn);
$_SESSION = [];
session_regenerate_id(true);
$_SESSION['info'] = 'You are logged out.';
header('Location: ' . BASE_URL . '/index.php');
exit();
