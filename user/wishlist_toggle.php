<?php
session_start();
include('../includes/config.php');
include('../includes/guard_login.php');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/index.php');
    exit();
}

$uid  = (int)$_SESSION['user_id'];
$id   = (int)($_POST['item_id'] ?? 0);
$back = $_POST['back'] ?? '';
if (!str_starts_with($back, BASE_URL . '/') || str_starts_with($back, '//')) {   // Restrict redirects to this site.
    $back = BASE_URL . '/user/wishlist.php';
}

if (!mysqli_fetch_row(mysqli_execute_query($conn, 'SELECT 1 FROM item WHERE item_id = ?', [$id]))) {
    $_SESSION['message'] = 'That watch no longer exists.';
} elseif (mysqli_fetch_row(mysqli_execute_query($conn, 'SELECT 1 FROM wishlist WHERE user_id = ? AND item_id = ?', [$uid, $id]))) {
    mysqli_execute_query($conn, 'DELETE FROM wishlist WHERE user_id = ? AND item_id = ?', [$uid, $id]);
    $_SESSION['info'] = 'Removed from your wishlist.';
} else {
    mysqli_execute_query($conn, 'INSERT INTO wishlist (user_id, item_id) VALUES (?, ?)', [$uid, $id]);
    $_SESSION['success'] = 'Saved to your wishlist.';
}
header('Location: ' . $back);
exit();
