<?php
// Keeps a logged-in user's cart in the database so it survives logout.

function cart_save(mysqli $conn, int $userId, array $cart): void
{
    mysqli_begin_transaction($conn);
    try {
        mysqli_execute_query($conn, 'DELETE FROM cart WHERE user_id = ?', [$userId]);
        foreach ($cart as $itemId => $qty) {
            if ((int)$qty > 0) {
                mysqli_execute_query($conn, 'INSERT INTO cart (user_id, item_id, quantity) VALUES (?, ?, ?)', [$userId, (int)$itemId, (int)$qty]);
            }
        }
        mysqli_commit($conn);
    } catch (Throwable $e) {
        mysqli_rollback($conn);
        throw $e;
    }
}

function cart_load(mysqli $conn, int $userId): array
{
    $cart = [];
    $rows = mysqli_fetch_all(mysqli_execute_query($conn, 'SELECT item_id, quantity FROM cart WHERE user_id = ?', [$userId]), MYSQLI_ASSOC);
    foreach ($rows as $r) {
        $cart[(int)$r['item_id']] = (int)$r['quantity'];
    }
    return $cart;
}

// Saves the current session cart if someone is logged in.
function cart_sync(mysqli $conn): void
{
    if (isset($_SESSION['user_id'])) {
        cart_save($conn, (int)$_SESSION['user_id'], $_SESSION['cart'] ?? []);
    }
}