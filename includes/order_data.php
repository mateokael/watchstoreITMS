<?php
// Loads one order and its lines for the email/PDF receipt.
$dataOrderId = (int)($dataOrderId ?? 0);
$ord = fetch_order($conn, $dataOrderId);
if ($ord) {
    $ord['lines'] = fetch_order_lines($conn, $dataOrderId);
    $ord['subtotal'] = order_totals($ord['lines'], (float)$ord['shipping'])['subtotal'];
}
