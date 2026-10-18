<?php
// Admin: saves the add / edit expense form.
session_start();
include('../includes/config.php');
include('../includes/guard_admin.php');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/index.php');
    exit();
}

$id     = (int)($_POST['expense_id'] ?? 0);
$cat    = (int)($_POST['category_id'] ?? 0);
$desc   = trim($_POST['description'] ?? '');
$amount = filter_var($_POST['amount'] ?? '', FILTER_VALIDATE_FLOAT);
$date   = (string)($_POST['expense_date'] ?? '');
$_SESSION['old'] = ['category_id' => $cat, 'description' => $desc, 'amount' => $_POST['amount'] ?? '', 'expense_date' => $date];
$back = BASE_URL . '/admin/expense_edit.php' . ($id ? '?id=' . $id : '');

if (!(($vd = DateTime::createFromFormat('Y-m-d', (string)$date)) && $vd->format('Y-m-d') === $date)) {
    $error = 'Pick a valid date.';
} elseif (!mysqli_fetch_row(mysqli_execute_query($conn, 'SELECT 1 FROM expense_category WHERE category_id = ?', [$cat]))) {
    $error = 'Choose a category.';
} elseif ($desc === '' || mb_strlen($desc) > 200) {
    $error = 'Enter a description (up to 200 characters).';
} elseif ($amount === false || $amount <= 0 || $amount > 99999999) {
    $error = 'Enter an amount greater than zero.';
} elseif ($id && !mysqli_fetch_row(mysqli_execute_query($conn, 'SELECT 1 FROM expense WHERE expense_id = ?', [$id]))) {
    $error = 'That expense does not exist.';
} else {
    $error = null;
}
if ($error) {
    $_SESSION['message'] = $error;
    header('Location: ' . $back);
    exit();
}

$amount = round((float)$amount, 2);
if ($id) {
    mysqli_execute_query($conn, 'UPDATE expense SET category_id = ?, description = ?, amount = ?, expense_date = ? WHERE expense_id = ?', [$cat, $desc, $amount, $date, $id]);
} else {
    mysqli_execute_query($conn, 'INSERT INTO expense (category_id, description, amount, expense_date, created_by) VALUES (?, ?, ?, ?, ?)', [$cat, $desc, $amount, $date, (int)$_SESSION['user_id']]);
}
unset($_SESSION['old'], $_SESSION['errors']);
$_SESSION['success'] = 'Expense saved.';
header('Location: ' . BASE_URL . '/admin/expenses.php');
exit();
