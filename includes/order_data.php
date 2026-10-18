<?php
// Loads one order for the emails and the PDF. Set before including: $dataOrderId
$ord = mysqli_fetch_assoc(mysqli_execute_query($conn, 'SELECT * FROM v_order_totals WHERE orderinfo_id = ?', [$dataOrderId]));
if ($ord) {
    $ord['lines'] = mysqli_fetch_all(mysqli_execute_query($conn, 'SELECT item_id, brand, model, img_path, quantity, unit_price, unit_cost, line_total
                                                                  FROM v_order_details WHERE orderinfo_id = ? ORDER BY item_id', [$dataOrderId]), MYSQLI_ASSOC);
    $ord['subtotal'] = array_sum(array_column($ord['lines'], 'line_total'));
}
