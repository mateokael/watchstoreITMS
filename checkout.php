<?php
session_start();
include('includes/config.php');
include('includes/guard_login.php');

$uid = (int)$_SESSION['user_id'];
$customer = mysqli_fetch_assoc(mysqli_execute_query($conn, 'SELECT * FROM customer WHERE user_id = ?', [$uid]));
if (!$customer) {
    $_SESSION['warning'] = 'Add your delivery details first, then you can check out.';
    $_SESSION['next'] = BASE_URL . '/checkout.php';
    header('Location: ' . BASE_URL . '/user/profile.php');
    exit();
}

include('includes/cart_lines.php');
if (!$lines) {
    $_SESSION['info'] = 'Your cart is empty.';
    header('Location: ' . BASE_URL . '/index.php');
    exit();
}
$subtotal = array_sum(array_column($lines, 'subtotal'));

// ---- place the order (one transaction: order + order lines + stock) ----
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    mysqli_begin_transaction($conn);
    try {
        $orderItems = [];
        foreach ($lines as $l) {
            $itemId = (int)$l['item_id'];
            $row = mysqli_fetch_assoc(mysqli_execute_query(
                $conn,
                'SELECT s.quantity, i.sell_price, i.cost_price, i.is_active
                 FROM stock s JOIN item i ON i.item_id = s.item_id
                 WHERE s.item_id = ? FOR UPDATE',
                [$itemId]
            ));
            if (!$row || !$row['is_active'] || (int)$row['quantity'] < (int)$l['qty']) {
                throw new DomainException($l['brand'] . ' ' . $l['model'] . ' just ran low on stock. Please check your cart.');
            }
            $orderItems[$itemId] = [
                'price' => (float)$row['sell_price'],
                'cost' => (float)$row['cost_price'],
            ];
        }

        $pref = ($_POST['receipt_pref'] ?? 'email') === 'print' ? 'print' : 'email';
        mysqli_execute_query($conn, "INSERT INTO orderinfo (customer_id, date_placed, shipping, status, receipt_pref) VALUES (?, NOW(), ?, 'pending', ?)", [(int)$customer['customer_id'], (float)SHIPPING_FEE, $pref]);
        $orderId = (int)mysqli_insert_id($conn);

        foreach ($lines as $l) {
            $iid = (int)$l['item_id'];
            mysqli_execute_query($conn, 'INSERT INTO orderline (orderinfo_id, item_id, quantity, unit_price, unit_cost) VALUES (?, ?, ?, ?, ?)', [
                $orderId, $iid, (int)$l['qty'], $orderItems[$iid]['price'], $orderItems[$iid]['cost'],
            ]);
            mysqli_execute_query($conn, 'UPDATE stock SET quantity = quantity - ? WHERE item_id = ?', [(int)$l['qty'], $iid]);
        }
        mysqli_commit($conn);
    } catch (DomainException $e) {
        mysqli_rollback($conn);
        $_SESSION['message'] = $e->getMessage();
        header('Location: ' . BASE_URL . '/view_cart.php');
        exit();
    } catch (Throwable $e) {
        mysqli_rollback($conn);
        $_SESSION['message'] = 'Something went wrong placing your order. Nothing was charged or changed. Please try again.';
        header('Location: ' . BASE_URL . '/view_cart.php');
        exit();
    }
    unset($_SESSION['cart']);
    $emailOrderId = $orderId;
    $emailType = 'placed';
    include('includes/order_email.php');
    $_SESSION['success'] = "Order #$orderId placed. Thank you! A confirmation was emailed to " . ($_SESSION['email'] ?? 'you') . '.';
    header('Location: ' . BASE_URL . '/user/order_view.php?id=' . $orderId);
    exit();
}

$pageTitle = 'Checkout';
include('includes/header.php');
?>
<h1 class="mb-3">Checkout</h1>
<div class="row g-4">
  <div class="col-md-7">
    <div class="panel mb-3">
      <h2 class="h4">Deliver to</h2>
      <p class="mb-1"><?= htmlspecialchars((string)(trim(($customer['title'] ?? '') . ' ' . $customer['fname'] . ' ' . $customer['lname']))) ?></p>
      <p class="mb-1"><?= htmlspecialchars((string)$customer['addressline']) ?>, <?= htmlspecialchars((string)$customer['town']) ?> <?= htmlspecialchars((string)$customer['zipcode']) ?></p>
      <p class="mb-2"><?= htmlspecialchars((string)$customer['phone']) ?></p>
      <a href="<?= BASE_URL ?>/user/profile.php">Change delivery details</a>
    </div>
    <div class="panel table-responsive p-0">
      <table class="table mb-0">
        <thead><tr><th scope="col">Watch</th><th scope="col" class="num text-end">Qty</th><th scope="col" class="num text-end">Total</th></tr></thead>
        <tbody>
        <?php foreach ($lines as $l): ?>
          <tr><td><?= htmlspecialchars((string)(($l['brand'] . ' ' . $l['model']))) ?></td><td class="num text-end"><?= (int)$l['qty'] ?></td><td class="num text-end"><?= (CURRENCY . number_format($l['subtotal'], 2)) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="col-md-5">
    <div class="panel">
      <div class="d-flex justify-content-between"><span>Subtotal</span><span class="num"><?= (CURRENCY . number_format($subtotal, 2)) ?></span></div>
      <div class="d-flex justify-content-between"><span>Shipping</span><span class="num"><?= (CURRENCY . number_format(SHIPPING_FEE, 2)) ?></span></div>
      <hr class="my-2">
      <div class="d-flex justify-content-between fw-bold fs-5"><span>Total</span><span class="num"><?= (CURRENCY . number_format($subtotal + SHIPPING_FEE, 2)) ?></span></div>
      <p class="small text-secondary mt-2">Payment is collected on delivery. There is no online payment in this project.</p>
      <form method="post">        <fieldset class="mb-3">
          <legend class="h6">How do you want your receipt?</legend>
          <div class="form-check"><input class="form-check-input" type="radio" name="receipt_pref" id="rp_email" value="email" checked><label class="form-check-label" for="rp_email">Email it to me (<?= htmlspecialchars((string)($_SESSION['email'] ?? 'my account email')) ?>)</label></div>
          <div class="form-check"><input class="form-check-input" type="radio" name="receipt_pref" id="rp_print" value="print"><label class="form-check-label" for="rp_print">A printed copy from the store</label></div>
        </fieldset>
        <button class="btn btn-brass w-100" type="submit">Place order</button></form>
      <a class="btn btn-link w-100 mt-1" href="<?= BASE_URL ?>/view_cart.php">Back to cart</a>
    </div>
  </div>
</div>
<?php include('includes/footer.php'); ?>
