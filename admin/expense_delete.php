<?php
// Admin: delete one expense.
session_start();
include('../includes/config.php');
include('../includes/guard_admin.php');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/index.php');
    exit();
}

$id = (int)($_POST['expense_id'] ?? 0);
if (mysqli_fetch_row(mysqli_execute_query($conn, 'SELECT 1 FROM expense WHERE expense_id = ?', [$id]))) {
    mysqli_execute_query($conn, 'DELETE FROM expense WHERE expense_id = ?', [$id]);
    $_SESSION['success'] = 'Expense deleted.';
} else {
    $_SESSION['message'] = 'That expense does not exist.';
}
header('Location: ' . BASE_URL . '/admin/expenses.php');
exit();
