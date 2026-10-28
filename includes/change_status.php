<?php
// Changes the status of an order.
$statusError = null;
$statusWarning = null;
if (!in_array($statusNew, ORDER_STATUSES, true)) {
    $statusError = 'Unknown status.';
} else {
    mysqli_begin_transaction($conn);
    try {
        $stOrder = mysqli_fetch_assoc(mysqli_execute_query($conn, 'SELECT status FROM orderinfo WHERE orderinfo_id = ? FOR UPDATE', [$statusOrderId]));
        if (!$stOrder) {
            mysqli_rollback($conn);
            $statusError = 'Order not found.';
        } elseif (!in_array($statusNew, $nextStatuses[$stOrder['status']] ?? [], true)) {
            mysqli_rollback($conn);
            $statusError = "A {$stOrder['status']} order can't be changed to $statusNew.";
        } else {
            if ($statusNew === 'cancelled') {
                mysqli_execute_query($conn, 'UPDATE stock s
                    JOIN orderline ol ON ol.item_id = s.item_id
                    SET s.quantity = s.quantity + ol.quantity
                    WHERE ol.orderinfo_id = ?', [$statusOrderId]);
            }

            $statusMap = ['shipped' => 'date_shipped', 'delivered' => 'date_delivered'];
            $statusSql = 'UPDATE orderinfo SET status = ?';
            if (isset($statusMap[$statusNew])) {
                $statusSql .= ', ' . $statusMap[$statusNew] . ' = NOW()';
            }
            mysqli_execute_query($conn, $statusSql . ' WHERE orderinfo_id = ?', [$statusNew, $statusOrderId]);
            mysqli_commit($conn);
        }
    } catch (Throwable $stError) {
        mysqli_rollback($conn);
        $statusError = 'Could not update the order. Please try again.';
    }

    if ($statusError === null) {
        if ($statusNew === 'delivered') {
            try {
                $receiptOrderId = $statusOrderId;
                $receiptMethod = null;
                $receiptDeliver = false;
                include(ROOT_DIR . '/includes/receipt_issue.php');
            } catch (Throwable $stReceiptError) {
                error_log("Could not issue receipt for order #$statusOrderId: " . $stReceiptError->getMessage());
                $statusWarning = 'The order was delivered, but its receipt could not be issued. Check the PHP error log.';
            }
        }
        $emailOrderId = $statusOrderId;
        $emailType = $statusNew;
        include(ROOT_DIR . '/includes/order_email.php');
        if ($statusNew === 'delivered' && $mailSent) {
            mysqli_execute_query($conn, "UPDATE receipt SET emailed_at = NOW() WHERE orderinfo_id = ? AND method = 'email'", [$statusOrderId]);
        }
    }
}
