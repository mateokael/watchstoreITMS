<?php
// The session cart is only [item_id => qty]. This reads the names and prices from the database and
$lines = [];
$cartNow = $_SESSION['cart'] ?? [];
if ($cartNow) {
    $cartIds = array_map('intval', array_keys($cartNow));
    $cartIn = implode(',', array_fill(0, count($cartIds), '?'));
    $cartRows = mysqli_fetch_all(mysqli_execute_query($conn, "SELECT i.*, s.quantity AS stock FROM item i JOIN stock s ON s.item_id = i.item_id
                                                            WHERE i.is_active = 1 AND i.item_id IN ($cartIn) ORDER BY i.item_id", $cartIds), MYSQLI_ASSOC);
    $cartClean = [];
    foreach ($cartRows as $cartRow) {
        $cartQty = min((int)$cartNow[(int)$cartRow['item_id']], (int)$cartRow['stock']);
        if ($cartQty < 1) {
            continue;
        }
        $cartRow['qty'] = $cartQty;
        $cartRow['subtotal'] = $cartQty * (float)$cartRow['sell_price'];
        $lines[] = $cartRow;
        $cartClean[(int)$cartRow['item_id']] = $cartQty;
    }
    $_SESSION['cart'] = $cartClean;
}
