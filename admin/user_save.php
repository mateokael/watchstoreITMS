<?php
// Admin: saves the add / edit account form.
session_start();
include('../includes/config.php');
include('../includes/guard_admin.php');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/index.php');
    exit();
}

$id       = (int)($_POST['user_id'] ?? 0);
$role     = $_POST['role'] ?? 'user';
$status   = $_POST['status'] ?? 'active';
$username = trim($_POST['username'] ?? '');
$email    = trim($_POST['email'] ?? '');
$pass     = (string)($_POST['password'] ?? '');
$cust     = [];
foreach (['title', 'fname', 'lname', 'addressline', 'town', 'zipcode', 'phone'] as $f) {
    $cust[$f] = trim($_POST[$f] ?? '');
}
$_SESSION['old'] = array_merge(['role' => $role, 'status' => $status, 'username' => $username, 'email' => $email], $cust);
$back = BASE_URL . '/admin/user_edit.php' . ($id ? '?id=' . $id : '');

$existing = $id ? mysqli_fetch_assoc(mysqli_execute_query(
    $conn,
    'SELECT user_id FROM users WHERE user_id = ? AND user_id <> ?',
    [$id, (int)$_SESSION['user_id']]
)) : null;
if ($id && !$existing) {
    $_SESSION['message'] = 'That account does not exist.';
    header('Location: ' . BASE_URL . '/admin/users.php');
    exit();
}

$wantsCustomer = $role === 'user' && implode('', array_diff_key($cust, ['title' => 1])) !== '';
$error = null;
if (!in_array($role, ['user', 'employee', 'admin'], true) || !in_array($status, ['active', 'inactive'], true)) {
    $error = 'Pick a valid account type and status.';
} elseif (!preg_match('/^[A-Za-z0-9._-]{3,20}$/', $username)) {
    $error = 'Username must be 3 to 20 characters: letters, numbers, dots, dashes or underscores.';
} elseif ((mysqli_fetch_row(mysqli_execute_query($conn, 'SELECT 1 FROM users WHERE username = ? AND user_id <> ?', [$username, $id])) !== null)) {
    $error = 'That username is already taken.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
    $error = 'Enter a valid email address.';
} elseif (mysqli_fetch_row(mysqli_execute_query($conn, 'SELECT 1 FROM users WHERE email = ? AND user_id <> ?', [$email, $id]))) {
    $error = 'That email is already registered.';
} elseif (($id === 0 || $pass !== '') && strlen($pass) < 8) {
    $error = 'The password needs at least 8 characters.';
} elseif ($wantsCustomer && in_array('', [$cust['fname'], $cust['lname'], $cust['addressline'], $cust['town'], $cust['zipcode'], $cust['phone']], true)) {
    $error = 'For customer details, fill in first name, last name, address, town, zip code and phone (or leave them all empty).';
}
if ($error) {
    $_SESSION['message'] = $error;
    header('Location: ' . $back);
    exit();
}

mysqli_begin_transaction($conn);
try {
    if ($id) {
        mysqli_execute_query($conn, 'UPDATE users SET username = ?, email = ?, role = ?, status = ? WHERE user_id = ?', [$username, $email, $role, $status, $id]);
        if ($pass !== '') {
            mysqli_execute_query($conn, 'UPDATE users SET password = ? WHERE user_id = ?', [password_hash($pass, PASSWORD_BCRYPT), $id]);
        }
    } else {
        mysqli_execute_query($conn, 'INSERT INTO users (username, email, password, role, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())', [$username, $email, password_hash($pass, PASSWORD_BCRYPT), $role, $status]);
        $id = (int)mysqli_insert_id($conn);
    }
    if ($wantsCustomer) {
        if (mysqli_fetch_row(mysqli_execute_query($conn, 'SELECT 1 FROM customer WHERE user_id = ?', [$id]))) {
            mysqli_execute_query($conn, 'UPDATE customer SET title = ?, fname = ?, lname = ?, addressline = ?, town = ?, zipcode = ?, phone = ? WHERE user_id = ?', [$cust['title'] ?: null, $cust['fname'], $cust['lname'], $cust['addressline'], $cust['town'], $cust['zipcode'], $cust['phone'], $id]);
        } else {
            mysqli_execute_query($conn, 'INSERT INTO customer (user_id, title, fname, lname, addressline, town, zipcode, phone) VALUES (?, ?, ?, ?, ?, ?, ?, ?)', [$id, $cust['title'] ?: null, $cust['fname'], $cust['lname'], $cust['addressline'], $cust['town'], $cust['zipcode'], $cust['phone']]);
        }
    }
    mysqli_commit($conn);
} catch (Throwable $t) {
    mysqli_rollback($conn);
    $_SESSION['message'] = 'Could not save the account. Please try again.';
    header('Location: ' . $back);
    exit();
}
unset($_SESSION['old'], $_SESSION['errors']);
$_SESSION['success'] = 'Account saved.';
header('Location: ' . BASE_URL . '/admin/users.php');
exit();
