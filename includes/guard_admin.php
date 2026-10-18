<?php
// Include on the CRUD / admin pages: only users with the admin role may stay.
include(ROOT_DIR . '/includes/guard_login.php');
if ($_SESSION['role'] !== 'admin') {
    http_response_code(403);
    $_SESSION['message'] = 'That page is for store admins only.';
    header('Location: ' . BASE_URL . '/index.php');
    exit();
}
