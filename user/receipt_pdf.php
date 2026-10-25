<?php
// Downloads the PDF receipt. The customer who owns the order and store staff can open it.
session_start();
include('../includes/config.php');
include('../includes/guard_login.php');

$id = (int)($_GET['id'] ?? 0);
$o  = mysqli_fetch_assoc(mysqli_execute_query($conn, 'SELECT user_id FROM v_order_details WHERE orderinfo_id = ? LIMIT 1', [$id]));
if (!$o || ((int)$o['user_id'] !== (int)$_SESSION['user_id'] && !in_array($_SESSION['role'] ?? '', ['admin', 'employee']))) {
    http_response_code(404);
    $_SESSION['warning'] = 'That receipt could not be found.';
    header('Location: ' . BASE_URL . (in_array($_SESSION['role'] ?? '', ['admin', 'employee']) ? '/admin/orders.php' : '/user/myorders.php'));
    exit();
}
$pdfOrderId = $id;
include('../includes/receipt_pdf.php');
$pdf = $pdfBytes;
if ($pdf === null) {
    $_SESSION['message'] = 'The PDF could not be created. Check logs/mail-error.log.';
    header('Location: ' . BASE_URL . (in_array($_SESSION['role'] ?? '', ['admin', 'employee']) ? '/admin/orderDetails.php?id=' : '/user/order_view.php?id=') . $id);
    exit();
}
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $pdfName . '"');
header('Content-Length: ' . strlen($pdf));
echo $pdf;
