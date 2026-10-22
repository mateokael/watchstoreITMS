<?php
// Customer cancels their own PENDING order; stock goes back
session_start();
include('../includes/config.php');
include('../includes/guard_login.php');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/index.php');
    exit();
}

$id = (int)($_POST['order_id'] ?? 0);
$owns = mysqli_fetch_row(mysqli_execute_query($conn, 'SELECT 1 FROM orderinfo o JOIN customer c ON c.customer_id = o.customer_id
                WHERE o.orderinfo_id = ? AND c.user_id = ?', [$id, (int)$_SESSION['user_id']]));
if (!$owns) {
    $_SESSION['message'] = 'That order is not yours to cancel.';
    header('Location: ' . BASE_URL . '/user/myorders.php');
    exit();
}
$statusOrderId = $id;
$statusNew = 'cancelled';
include('../includes/change_status.php');
$err = $statusError;
if ($err) {
    $_SESSION['message'] = $err;
} else {
    $_SESSION['success'] = "Order #$id was cancelled.";
}
header('Location: ' . BASE_URL . '/user/order_view.php?id=' . $id);
exit();
