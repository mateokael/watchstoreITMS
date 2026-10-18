<?php
// Admin: delete an account. Customers with orders are protected (orders and reports need them): deactivate instead.
session_start();
include('../includes/config.php');
include('../includes/guard_admin.php');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/index.php');
    exit();
}

$id = (int)($_POST['user_id'] ?? 0);
$u = mysqli_fetch_assoc(mysqli_execute_query($conn, "SELECT username, profile_pic FROM users WHERE user_id = ? AND user_id <> " . (int)$_SESSION['user_id'], [$id]));
if (!$u) {
    $_SESSION['message'] = 'That account does not exist.';
} elseif ((int)(mysqli_fetch_row(mysqli_execute_query($conn, 'SELECT COUNT(*) FROM orderinfo o INNER JOIN customer c ON c.customer_id = o.customer_id WHERE c.user_id = ?', [$id])) ?? [null])[0] > 0) {
    $_SESSION['warning'] = $u['username'] . ' has orders, so the account can\'t be deleted. Deactivate it instead.';
} else {
    mysqli_execute_query($conn, 'DELETE FROM users WHERE user_id = ?', [$id]);
    if ($u['profile_pic'] && is_file(ROOT_DIR . '/' . $u['profile_pic'])) { unlink(ROOT_DIR . '/' . $u['profile_pic']); }
    $_SESSION['success'] = $u['username'] . ' was deleted.';
}
header('Location: ' . BASE_URL . '/admin/users.php');
exit();
