<?php
// Staff (admin or employee): give the customer their receipt.
session_start();
include('../includes/config.php');
include('../includes/guard_staff.php');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/index.php');
    exit();
}

$id     = (int)($_POST['order_id'] ?? 0);
$action = ($_POST['action'] ?? '') === 'print' ? 'print' : 'email';
$back   = BASE_URL . '/admin/orderDetails.php?id=' . $id;

$status = (mysqli_fetch_row(mysqli_execute_query($conn, 'SELECT status FROM orderinfo WHERE orderinfo_id = ?', [$id])) ?? [null])[0];
if ($status !== 'delivered') {
    $_SESSION['message'] = 'A receipt can only be issued for a delivered (completed) order.';
    header('Location: ' . ($status ? $back : BASE_URL . '/admin/orders.php'));
    exit();
}
$receiptOrderId = $id;
$receiptMethod = $action;
$receiptDeliver = false;
include('../includes/receipt_issue.php');

if ($action === 'print') {
    mysqli_execute_query($conn, "UPDATE receipt SET method = 'print', printed_at = NOW() WHERE orderinfo_id = ?", [$id]);
    header('Location: ' . BASE_URL . '/user/receipt.php?id=' . $id . '&autoprint=1');
    exit();
}
include('../includes/receipt_send.php');
if ($receiptSent) {
    $_SESSION['success'] = 'Receipt emailed to the customer.' . (MAIL_MODE === 'smtp' ? '' : ' (Mail is in log mode: see logs/mail.log.)');
} else {
    $_SESSION['message'] = 'The email could not be sent. Check logs/mail-error.log, or print the receipt instead.';
}
header('Location: ' . $back);
exit();
