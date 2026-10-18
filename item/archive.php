<?php
// "Archive" takes a watch out of the shop (is_active = 0) without deleting it, so old orders, reviews and
session_start();
include('../includes/config.php');
include('../includes/guard_admin.php');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/index.php');
    exit();
}

$id = (int)($_POST['item_id'] ?? 0);
$it = mysqli_fetch_assoc(mysqli_execute_query($conn, 'SELECT brand, model FROM item WHERE item_id = ?', [$id]));
if (!$it) {
    $_SESSION['message'] = 'That watch does not exist.';
} elseif (($_POST['action'] ?? '') === 'restore') {
    mysqli_execute_query($conn, 'UPDATE item SET is_active = 1 WHERE item_id = ?', [$id]);
    $_SESSION['success'] = ($it['brand'] . ' ' . $it['model']) . ' was restored to the shop.';
} else {
    mysqli_execute_query($conn, 'UPDATE item SET is_active = 0 WHERE item_id = ?', [$id]);
    $_SESSION['success'] = ($it['brand'] . ' ' . $it['model']) . ' was archived. Past orders still show it, and you can restore it from the Archived list.';
}
header('Location: ' . BASE_URL . '/' . 'item/index.php' . (($_POST['action'] ?? '') === 'restore' ? '?show=archived' : ''));
exit();
