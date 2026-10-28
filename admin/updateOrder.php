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
} elseif ($statusWarning) {
    $_SESSION['warning'] = $statusWarning . (!$mailSent
        ? ' The transaction notification email also could not be sent. Check logs/mail-error.log.'
        : '');
} elseif (!$mailSent) {
    $_SESSION['warning'] = "Order #$id is now $new, but its notification email with the transaction PDF could not be sent. Check logs/mail-error.log.";
} else {
    $_SESSION['success'] = "Order #$id is now $new. The notification email with its transaction PDF was sent.";
}
header('Location: ' . BASE_URL . '/admin/orderDetails.php?id=' . $id);
exit();
