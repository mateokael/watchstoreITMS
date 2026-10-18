<?php
// Emails the receipt to the customer and records it. Set before including: $receiptOrderId
$receiptSent = false;
$rsRow = mysqli_fetch_assoc(mysqli_execute_query($conn, 'SELECT receipt_id FROM receipt WHERE orderinfo_id = ?', [$receiptOrderId]));
if ($rsRow) {
    $emailOrderId = $receiptOrderId;
    $emailType = 'receipt';
    include(ROOT_DIR . '/includes/order_email.php');
    $receiptSent = $mailSent;
    if ($receiptSent) {
        mysqli_execute_query($conn, "UPDATE receipt SET method = 'email', emailed_at = NOW() WHERE orderinfo_id = ?", [$receiptOrderId]);
    }
}
