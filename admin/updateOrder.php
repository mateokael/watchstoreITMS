<?php
session_start();
include('../includes/config.php');
include('../includes/guard_staff.php');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/index.php');
    exit();
}

$id  = (int)($_POST['order_id'] ?? 0);        // comes from the form, not the session
$new = (string)($_POST['status'] ?? '');
if ($new === 'cancelled' && !(($_SESSION['role'] ?? '') === 'admin')) {
    $_SESSION['message'] = 'Only the store admin can cancel an order.';
    header('Location: ' . BASE_URL . '/admin/orderDetails.php?id=' . $id);
    exit();
}
$statusOrderId = $id;
$statusNew = $new;
include('../includes/change_status.php');
$err = $statusError;
if ($err) {
    $_SESSION['message'] = $err;
} else {
    $_SESSION['success'] = "Order #$id is now $new.";
}
header('Location: ' . BASE_URL . '/admin/orderDetails.php?id=' . $id);
exit();
