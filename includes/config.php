<?php
// Database connection (same style as the class sample: mysqli_connect + mysqli_select_db)
$db_host = "localhost";
$db_username = "root";
$db_passwd = "";
$conn = mysqli_connect($db_host, $db_username, $db_passwd) or die("Could not connect!\n");

// echo "Connection established.\n";
$db_name = "db_watchstoreproj";
mysqli_select_db($conn, $db_name) or die("Could not select the database $db_name!\n" . mysqli_error($conn));
mysqli_set_charset($conn, 'utf8mb4');

// ---- Shop settings ----
define('CURRENCY', '₱');             // shown before prices
define('SHIPPING_FEE', 150.00);      // flat shipping per order
define('MOVEMENTS', ['quartz', 'automatic', 'mechanical', 'solar', 'smart']);
define('CATEGORIES', ['men', 'women', 'unisex']);
define('ORDER_STATUSES', ['pending', 'shipped', 'delivered', 'cancelled']);
// a sale counts as income once the order is DELIVERED
define('SALE_DATE_SQL', 'DATE(COALESCE(o.date_delivered, o.date_shipped, o.date_placed))');

// badge colour of each order status, and which status an order may move to next
$statusColors = ['pending' => 'warning text-dark', 'shipped' => 'info text-dark', 'delivered' => 'success', 'cancelled' => 'secondary'];
$nextStatuses = ['pending' => ['shipped', 'cancelled'], 'shipped' => ['delivered'], 'delivered' => [], 'cancelled' => []];

// ---- Email settings live in their own file so your password stays out of GitHub ----
if (is_file(__DIR__ . '/mail_config.php')) {
    include(__DIR__ . '/mail_config.php');
}
defined('MAIL_MODE')      || define('MAIL_MODE', 'log');   // no mail_config.php yet: emails are written to logs/mail.log
defined('MAIL_HOST')      || define('MAIL_HOST', '');
defined('MAIL_PORT')      || define('MAIL_PORT', 587);
defined('MAIL_SECURE')    || define('MAIL_SECURE', 'tls');
defined('MAIL_USER')      || define('MAIL_USER', '');
defined('MAIL_PASS')      || define('MAIL_PASS', '');
defined('MAIL_FROM')      || define('MAIL_FROM', '');
defined('MAIL_FROM_NAME') || define('MAIL_FROM_NAME', 'WatchStore');
defined('ADMIN_EMAIL')    || define('ADMIN_EMAIL', '');

// ROOT_DIR = the project folder on disk. BASE_URL = the folder it sits in under htdocs ('' if it is the site root).
// BASE_URL is detected automatically, so it works whatever the folder is called. Set it by hand only if the guess is wrong.
define('ROOT_DIR', str_replace('\\', '/', dirname(__DIR__)));
if (!defined('BASE_URL')) {
    $docRoot = rtrim(str_replace('\\', '/', (string)realpath($_SERVER['DOCUMENT_ROOT'] ?? '')), '/');
    define('BASE_URL', ($docRoot !== '' && stripos(ROOT_DIR, $docRoot) === 0) ? substr(ROOT_DIR, strlen($docRoot)) : '');
}
// full address of the site, used for the links inside emails
if (!defined('APP_URL')) {
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    define('APP_URL', ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . BASE_URL);
}

// Runs on every page: a deactivated account is signed out at once, and a role change by the admin applies immediately.
if (isset($_SESSION['user_id'])) {
    $activeCheck = mysqli_fetch_assoc(mysqli_execute_query($conn, 'SELECT status, role FROM users WHERE user_id = ?', [$_SESSION['user_id']]));
    if (!$activeCheck || $activeCheck['status'] !== 'active') {
        unset($_SESSION['user_id'], $_SESSION['email'], $_SESSION['role'], $_SESSION['username'], $_SESSION['avatar']);
        $_SESSION['message'] = 'Your account is deactivated or no longer exists. Please contact the store.';
    } else {
        $_SESSION['role'] = $activeCheck['role'];
    }
}
