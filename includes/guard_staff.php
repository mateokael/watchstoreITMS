<?php
// Pages that employees may also use (orders, restock).
include(ROOT_DIR . '/includes/guard_login.php');
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'employee') {
    $_SESSION['message'] = 'That page is for store staff only.';
    header('Location: ' . BASE_URL . '/index.php');
    exit();
}
