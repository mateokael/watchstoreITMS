<?php
// Staff: saves an in-store sale. One database transaction: order + lines + stock. The order is born "delivered".
session_start();
include('../includes/config.php');
include('../includes/guard_staff.php');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/index.php');
    exit();
}

$customerId = (int)($_POST['customer_id'] ?? 0);
$method     = ($_POST['receipt_method'] ?? 'print') === 'email' ? 'email' : 'print';
$ids  = (array)($_POST['item_id'] ?? []);
$qtys = (array)($_POST['qty'] ?? []);

$want = [];
foreach ($ids as $k => $iid) {
    $iid = (int)$iid;
    $q   = (int)($qtys[$k] ?? 0);
    if ($iid > 0 && $q > 0) {
        $want[$iid] = ($want[$iid] ?? 0) + $q;
    }
}

if (!mysqli_fetch_row(mysqli_execute_query($conn, "SELECT 1 FROM customer c INNER JOIN users u ON u.user_id = c.user_id WHERE c.customer_id = ? AND u.role = 'user' AND u.status = 'active'", [$customerId]))) {
    $_SESSION['message'] = 'Choose a customer.';
    header('Location: ' . BASE_URL . '/admin/sale_new.php');
    exit();
}
if (!$want) {
    $_SESSION['message'] = 'Add at least one watch.';
    header('Location: ' . BASE_URL . '/admin/sale_new.php');
    exit();
}

mysqli_begin_transaction($conn);
try {
    $rows = [];
    foreach ($want as $iid => $q) {
        $row = mysqli_fetch_assoc(mysqli_execute_query($conn, 'SELECT i.brand, i.model, i.sell_price, i.cost_price, i.is_active, s.quantity
                       FROM stock s INNER JOIN item i ON i.item_id = s.item_id WHERE s.item_id = ? FOR UPDATE', [$iid]));
        if (!$row || !$row['is_active']) {
            throw new RuntimeException('One of the watches is no longer for sale.');
        }
        if ((int)$row['quantity'] < $q) {
            throw new RuntimeException(($row['brand'] . ' ' . $row['model']) . ': only ' . (int)$row['quantity'] . ' in stock.');
        }
        $rows[$iid] = $row;
    }

    mysqli_execute_query($conn, "INSERT INTO orderinfo (customer_id, date_placed, date_shipped, date_delivered, shipping, status, receipt_pref)
                            VALUES (?, NOW(), NOW(), NOW(), 0, 'delivered', ?)", [$customerId, $method]);
    $orderId = (int)mysqli_insert_id($conn);
    foreach ($want as $iid => $q) {
        mysqli_execute_query($conn, 'INSERT INTO orderline (orderinfo_id, item_id, quantity, unit_price, unit_cost) VALUES (?, ?, ?, ?, ?)', [$orderId, $iid, $q, (float)$rows[$iid]['sell_price'], (float)$rows[$iid]['cost_price']]);
        mysqli_execute_query($conn, 'UPDATE stock SET quantity = quantity - ? WHERE item_id = ?', [$q, $iid]);
    }
    mysqli_commit($conn);
} catch (RuntimeException $e) {
    mysqli_rollback($conn);
    $_SESSION['message'] = $e->getMessage();
    header('Location: ' . BASE_URL . '/admin/sale_new.php');
    exit();
} catch (Throwable $t) {
    mysqli_rollback($conn);
    $_SESSION['message'] = 'Could not complete the sale. Nothing was changed.';
    header('Location: ' . BASE_URL . '/admin/sale_new.php');
    exit();
}

$receiptOrderId = $orderId;
$receiptMethod = $method;
$receiptDeliver = false;
include('../includes/receipt_issue.php');
if ($method === 'print') {
    mysqli_execute_query($conn, "UPDATE receipt SET method = 'print', printed_at = NOW() WHERE orderinfo_id = ?", [$orderId]);
    $_SESSION['success'] = "Sale #$orderId completed.";
    header('Location: ' . BASE_URL . '/' . 'user/receipt.php?id=' . $orderId . '&autoprint=1');
    exit();
}
include('../includes/receipt_send.php');
$sent = $receiptSent;
if ($sent) {
    $_SESSION['success'] = "Sale #$orderId completed and the receipt was emailed.";
} else {
    $_SESSION['warning'] = "Sale #$orderId completed, but the email could not be sent. You can resend or print it from the order page.";
}
header('Location: ' . BASE_URL . '/' . 'admin/orderDetails.php?id=' . $orderId);
exit();
