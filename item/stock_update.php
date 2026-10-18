<?php
// Quick restock from the watch list
session_start();
include('../includes/config.php');
include('../includes/guard_staff.php');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/index.php');
    exit();
}

$id  = (int)($_POST['item_id'] ?? 0);
$qty = filter_var($_POST['quantity'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
if ($qty === false || !mysqli_fetch_row(mysqli_execute_query($conn, 'SELECT 1 FROM stock WHERE item_id = ?', [$id]))) {
    $_SESSION['message'] = 'Stock must be a whole number, 0 or more.';
} else {
    mysqli_execute_query($conn, 'UPDATE stock SET quantity = ? WHERE item_id = ?', [$qty, $id]);
    $_SESSION['success'] = 'Stock updated.';
}
header('Location: ' . BASE_URL . '/item/index.php');
exit();
