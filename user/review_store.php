<?php
// Save reviews only for delivered purchases and mask foul language.
session_start();
include('../includes/config.php');
include('../includes/guard_login.php');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/index.php');
    exit();
}

$uid    = (int)$_SESSION['user_id'];
$id     = (int)($_POST['item_id'] ?? 0);
$rating = (int)($_POST['rating'] ?? 0);
$text   = trim(strip_tags($_POST['comment'] ?? ''));
$back   = BASE_URL . '/product.php?id=' . $id . '#reviews';

$bought = mysqli_fetch_row(mysqli_execute_query($conn, "SELECT 1 FROM orderline ol
    JOIN orderinfo o ON o.orderinfo_id = ol.orderinfo_id
    JOIN customer c ON c.customer_id = o.customer_id
    WHERE c.user_id = ? AND ol.item_id = ? AND o.status = 'delivered' LIMIT 1", [$uid, $id]));

if (!$bought) {
    $_SESSION['message'] = 'You can review a watch once your order for it has been delivered.';
} elseif ($rating < 1 || $rating > 5) {
    $_SESSION['message'] = 'Pick a rating from 1 to 5.';
} elseif (strlen($text) > 500) {
    $_SESSION['message'] = 'Keep the comment to 500 characters or fewer.';
} else {
    $badText = $text;
    include('../includes/badwords.php');
    $text = $badMaskedText;
    mysqli_execute_query($conn, 'INSERT INTO review (item_id, user_id, rating, comment, created_at) VALUES (?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE rating = VALUES(rating), comment = VALUES(comment), created_at = NOW()', [$id, $uid, $rating, $text === '' ? null : $text]);
    if ($badFound) {
        $_SESSION['warning'] = $badMessage;
    } else {
        $_SESSION['success'] = 'Thanks, your review is saved.';
    }
}
header('Location: ' . $back);
exit();
