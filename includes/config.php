<?php
$conn = mysqli_connect('localhost', 'root', '', 'db_watchstoreproj')
    or die('Could not connect to the database: ' . mysqli_connect_error());
mysqli_set_charset($conn, 'utf8mb4');

// Store settings.
define('CURRENCY', '₱');
define('SHIPPING_FEE', 150.00);
define('MOVEMENTS', ['quartz', 'automatic', 'mechanical', 'solar', 'smart']);
define('CATEGORIES', ['men', 'women', 'unisex']);
define('ORDER_STATUSES', ['pending', 'shipped', 'delivered', 'cancelled']);
define('SALE_DATE_SQL', 'DATE(o.date_delivered)');

$statusColors = ['pending' => 'warning text-dark', 'shipped' => 'info text-dark', 'delivered' => 'success', 'cancelled' => 'secondary'];
$nextStatuses = ['pending' => ['shipped', 'cancelled'], 'shipped' => ['delivered'], 'delivered' => [], 'cancelled' => []];

// Optional email settings.
if (is_file(__DIR__ . '/mail_config.php')) {
    include(__DIR__ . '/mail_config.php');
}
defined('MAIL_MODE')      || define('MAIL_MODE', 'log');
defined('MAIL_HOST')      || define('MAIL_HOST', '');
defined('MAIL_PORT')      || define('MAIL_PORT', 587);
defined('MAIL_SECURE')    || define('MAIL_SECURE', 'tls');
defined('MAIL_USER')      || define('MAIL_USER', '');
defined('MAIL_PASS')      || define('MAIL_PASS', '');
defined('MAIL_FROM')      || define('MAIL_FROM', '');
defined('MAIL_FROM_NAME') || define('MAIL_FROM_NAME', 'WatchStore');
defined('ADMIN_EMAIL')    || define('ADMIN_EMAIL', '');

// Resolve app paths from its location.
define('ROOT_DIR', str_replace('\\', '/', dirname(__DIR__)));
if (!defined('BASE_URL')) {
    $docRoot = rtrim(str_replace('\\', '/', (string)realpath($_SERVER['DOCUMENT_ROOT'] ?? '')), '/');
    define('BASE_URL', ($docRoot !== '' && stripos(ROOT_DIR, $docRoot) === 0) ? substr(ROOT_DIR, strlen($docRoot)) : '');
}
// Build the public URL for email links.
if (!defined('APP_URL')) {
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    define('APP_URL', ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . BASE_URL);
}

// Refresh the session role and sign out inactive accounts.
if (isset($_SESSION['user_id'])) {
    $activeCheck = mysqli_fetch_assoc(mysqli_execute_query($conn, 'SELECT status, role FROM users WHERE user_id = ?', [$_SESSION['user_id']]));
    if (!$activeCheck || $activeCheck['status'] !== 'active') {
        unset($_SESSION['user_id'], $_SESSION['email'], $_SESSION['role'], $_SESSION['username'], $_SESSION['avatar']);
        $_SESSION['message'] = 'Your account is deactivated or no longer exists. Please contact the store.';
    } else {
        $_SESSION['role'] = $activeCheck['role'];
    }
}

function fetch_order(mysqli $conn, int $id): ?array
{
    $row = mysqli_fetch_assoc(mysqli_execute_query($conn,
        'SELECT orderinfo_id, date_placed, date_shipped, date_delivered, status, shipping, receipt_pref,
                customer_id, user_id, title, fname, lname, addressline, town, zipcode, phone, username, email
         FROM v_order_details WHERE orderinfo_id = ? LIMIT 1', [$id]));
    return $row ?: null;
}

function fetch_order_lines(mysqli $conn, int $id): array
{
    $lines = mysqli_fetch_all(mysqli_execute_query($conn,
        'SELECT item_id, brand, model, img_path, quantity, unit_price, unit_cost
         FROM v_order_details WHERE orderinfo_id = ? ORDER BY item_id', [$id]), MYSQLI_ASSOC);
    foreach ($lines as &$line) {
        $line['line_total'] = (int)$line['quantity'] * (float)$line['unit_price'];
    }
    unset($line);
    return $lines;
}

function order_totals(array $lines, float $shipping): array
{
    $subtotal = 0.0;
    $units = 0;
    foreach ($lines as $line) {
        $subtotal += (int)$line['quantity'] * (float)$line['unit_price'];
        $units += (int)$line['quantity'];
    }
    return ['subtotal' => $subtotal, 'units' => $units, 'total' => $subtotal + $shipping];
}
