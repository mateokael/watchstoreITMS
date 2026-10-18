<?php
// Include on any page that needs a logged-in user. Guests are sent to the login page with a message.
if (!isset($_SESSION['user_id'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $_SESSION['next'] = $_SERVER['REQUEST_URI'];
    }
    $_SESSION['warning'] = 'Please log in to continue.';
    header('Location: ' . BASE_URL . '/user/login.php');
    exit();
}
