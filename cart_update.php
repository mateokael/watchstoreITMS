<?php
// Handles every cart action: add, update (quantities + remove), clear
session_start();
include('includes/config.php');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/index.php');
    exit();
}

$action = $_POST['action'] ?? '';
$back = $_POST['back'] ?? '';
if (!str_starts_with($back, BASE_URL . '/') || str_starts_with($back, '//')) {
    $back = BASE_URL . '/' . 'view_cart.php';
}
$cart   = $_SESSION['cart'] ?? [];

if ($action === 'add') {
    $id  = (int)($_POST['item_id'] ?? 0);
    $qty = max(1, (int)($_POST['qty'] ?? 1));
    $it  = mysqli_fetch_assoc(mysqli_execute_query($conn, 'SELECT i.brand, i.model, s.quantity AS stock FROM item i
                   JOIN stock s ON s.item_id = i.item_id WHERE i.item_id = ? AND i.is_active = 1', [$id]));
    if (!$it) {
        $_SESSION['message'] = 'That watch is no longer available.';
    } elseif ((int)$it['stock'] < 1) {
        $_SESSION['message'] = ($it['brand'] . ' ' . $it['model']) . ' is sold out.';
    } else {
        $want = ($cart[$id] ?? 0) + $qty;
        $cart[$id] = min($want, (int)$it['stock']);
        $_SESSION['success'] = ($it['brand'] . ' ' . $it['model']) . ' is in your cart.';
        if ($want > (int)$it['stock']) {
            $_SESSION['warning'] = 'Only ' . (int)$it['stock'] . ' in stock, so your cart has ' . $cart[$id] . '.';
        }
    }
    $_SESSION['cart'] = $cart;
    header('Location: ' . $back);
    exit();
}

if ($action === 'update') {
    $removeId = isset($_POST['remove']) ? (int)$_POST['remove'] : null;
    if ($removeId !== null) {
        unset($cart[$removeId]);
    }
    foreach ((array)($_POST['qty'] ?? []) as $id => $q) {
        $id = (int)$id;
        if ($id === $removeId || !isset($cart[$id])) {
            continue;
        }
        if ((int)$q <= 0) {
            unset($cart[$id]);
        } else {
            $cart[$id] = (int)$q;
        }
    }
    $_SESSION['cart'] = $cart;
    $asked = $cart;
    include('includes/cart_lines.php');
    if ($_SESSION['cart'] != $asked) {
        $_SESSION['warning'] = 'Some quantities were lowered to what we have in stock.';
    } else {
        $_SESSION['success'] = 'Cart updated.';
    }
    header('Location: ' . BASE_URL . '/view_cart.php');
    exit();
}

if ($action === 'clear') {
    unset($_SESSION['cart']);
    $_SESSION['info'] = 'Your cart is empty.';
}
header('Location: ' . BASE_URL . '/view_cart.php');
exit();
