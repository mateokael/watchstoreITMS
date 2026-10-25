<?php
// Creates the receipt for a delivered order once.
$receipt = null;
$receiptOrderId = (int)($receiptOrderId ?? 0);

if ($receiptOrderId > 0) {
    $rcOrder = mysqli_fetch_assoc(mysqli_execute_query($conn, 'SELECT status, receipt_pref FROM orderinfo WHERE orderinfo_id = ?', [$receiptOrderId]));
    if ($rcOrder && $rcOrder['status'] === 'delivered') {
        $receipt = mysqli_fetch_assoc(mysqli_execute_query($conn, 'SELECT * FROM receipt WHERE orderinfo_id = ?', [$receiptOrderId]));
        if (!$receipt) {
            $rcMethod = in_array($receiptMethod ?? '', ['email', 'print'], true) ? $receiptMethod : $rcOrder['receipt_pref'];
            mysqli_execute_query($conn, 'INSERT INTO receipt (orderinfo_id, method, issued_by, issued_at) VALUES (?, ?, ?, NOW())',
                [$receiptOrderId, $rcMethod, $_SESSION['user_id'] ?? null]);
            $receipt = mysqli_fetch_assoc(mysqli_execute_query($conn, 'SELECT * FROM receipt WHERE orderinfo_id = ?', [$receiptOrderId]));
        }
    }
}
