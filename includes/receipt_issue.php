<?php
// Creates the receipt for a DELIVERED order (once).
$receipt = null;
$rcOrder = mysqli_fetch_assoc(mysqli_execute_query($conn, 'SELECT status, receipt_pref FROM orderinfo WHERE orderinfo_id = ?', [$receiptOrderId]));
if ($rcOrder && $rcOrder['status'] === 'delivered') {
    $receipt = mysqli_fetch_assoc(mysqli_execute_query($conn, 'SELECT * FROM receipt WHERE orderinfo_id = ?', [$receiptOrderId]));
    if (!$receipt) {
        $rcMethod = in_array($receiptMethod, ['email', 'print'], true) ? $receiptMethod : $rcOrder['receipt_pref'];
        mysqli_execute_query($conn, 'INSERT INTO receipt (orderinfo_id, method, issued_by, issued_at) VALUES (?, ?, ?, NOW())',
            [$receiptOrderId, $rcMethod, $_SESSION['user_id'] ?? null]);
        if ($receiptDeliver && $rcMethod === 'email') {
            include(ROOT_DIR . '/includes/receipt_send.php');
        }
        $receipt = mysqli_fetch_assoc(mysqli_execute_query($conn, 'SELECT * FROM receipt WHERE orderinfo_id = ?', [$receiptOrderId]));
    }
}
