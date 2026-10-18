<?php
// Admin: change the role of an ACTIVE account (customer / employee / admin). Takes effect on that person's next page load.
session_start();
include('../includes/config.php');
include('../includes/guard_admin.php');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/index.php');
    exit();
}

$id   = (int)($_POST['user_id'] ?? 0);
$role = (string)($_POST['role'] ?? '');
$u = mysqli_fetch_assoc(mysqli_execute_query($conn, 'SELECT username, role, status FROM users WHERE user_id = ?', [$id]));

if (!in_array($role, ['user', 'employee', 'admin'], true)) {
    $_SESSION['message'] = 'Pick a valid role.';
} elseif ($id === (int)$_SESSION['user_id']) {
    $_SESSION['message'] = 'You cannot change your own role.';
} elseif (!$u) {
    $_SESSION['message'] = 'That account does not exist.';
} elseif ($u['status'] !== 'active') {
    $_SESSION['warning'] = $u['username'] . ' is inactive. Activate the account first, then change the role.';
} elseif ($u['role'] === $role) {
    $_SESSION['info'] = $u['username'] . ' already has that role.';
} else {
    mysqli_execute_query($conn, 'UPDATE users SET role = ? WHERE user_id = ?', [$role, $id]);
    $_SESSION['success'] = $u['username'] . ' is now ' . ($role === 'user' ? 'a customer' : 'an ' . $role) . '.';
}
header('Location: ' . BASE_URL . '/admin/users.php');
exit();
