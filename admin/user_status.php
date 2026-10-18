<?php
// Admin: activate / deactivate an account (an inactive account cannot log in).
session_start();
include('../includes/config.php');
include('../includes/guard_admin.php');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/index.php');
    exit();
}

$id = (int)($_POST['user_id'] ?? 0);
$u = mysqli_fetch_assoc(mysqli_execute_query($conn, "SELECT username, status FROM users WHERE user_id = ? AND user_id <> " . (int)$_SESSION['user_id'], [$id]));
if (!$u) {
    $_SESSION['message'] = 'That account does not exist.';
} else {
    $new = $u['status'] === 'active' ? 'inactive' : 'active';
    mysqli_execute_query($conn, 'UPDATE users SET status = ? WHERE user_id = ?', [$new, $id]);
    $_SESSION['success'] = $u['username'] . ' is now ' . $new . '.';
}
header('Location: ' . BASE_URL . '/admin/users.php');
exit();
