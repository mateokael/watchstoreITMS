<?php
// Processes the register form
session_start();
include('../includes/config.php');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/index.php');
    exit();
}

$username = trim($_POST['username'] ?? '');
$email   = trim($_POST['email'] ?? '');
$pass    = (string)($_POST['password'] ?? '');
$confirm = (string)($_POST['confirmPass'] ?? '');
$_SESSION['old'] = ['username' => $username, 'email' => $email];

$error = null;
if (!preg_match('/^[A-Za-z0-9._-]{3,20}$/', $username)) {
    $error = 'Username must be 3 to 20 characters: letters, numbers, dots, dashes or underscores.';
} elseif (mysqli_fetch_row(mysqli_execute_query($conn, 'SELECT 1 FROM users WHERE username = ?', [$username]))) {
    $error = 'That username is already taken. Please pick another one.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
    $error = 'Enter a valid email address.';
} elseif (strlen($pass) < 8) {
    $error = 'Your password needs at least 8 characters.';
} elseif ($pass !== $confirm) {
    $error = 'The two passwords do not match.';
} elseif (mysqli_fetch_row(mysqli_execute_query($conn, 'SELECT 1 FROM users WHERE email = ?', [$email]))) {
    $error = 'That email is already registered. Try logging in instead.';
}

if ($error) {
    $_SESSION['message'] = $error;
    header('Location: ' . BASE_URL . '/user/register.php');
    exit();
}

$upFile = $_FILES['avatar'] ?? ['error' => UPLOAD_ERR_NO_FILE];
$upFolder = 'avatars';
$upPrefix = 'user_';
include('../includes/upload_one.php');
$photo = $upPath;
if ($upError) {
    $_SESSION['message'] = $upError;
    header('Location: ' . BASE_URL . '/user/register.php');
    exit();
}

try {
    mysqli_execute_query($conn, "INSERT INTO users (username, email, password, profile_pic, role, status, created_at) VALUES (?, ?, ?, ?, 'user', 'active', NOW())", [$username, $email, password_hash($pass, PASSWORD_BCRYPT), $photo]);
} catch (mysqli_sql_exception $e) {
    if ($photo && is_file(ROOT_DIR . '/' . $photo)) { unlink(ROOT_DIR . '/' . $photo); }
    $_SESSION['message'] = 'Could not create your account. Please try again.';
    header('Location: ' . BASE_URL . '/user/register.php');
    exit();
}
unset($_SESSION['old'], $_SESSION['errors']);
$accountEmailType = 'welcome';
$accountEmail = $email;
$accountName = $username;
include('../includes/account_email.php');
$_SESSION['success'] = 'Account created. You can log in now.';
header('Location: ' . BASE_URL . '/user/login.php');
exit();
