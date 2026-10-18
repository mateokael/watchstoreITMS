<?php
// Saves a NEW watch (item + stock + photos) in one transaction
session_start();
include('../includes/config.php');
include('../includes/guard_admin.php');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/index.php');
    exit();
}

include('../includes/item_validate.php');
if ($err) {
    $_SESSION['errors'] = $err;
    $_SESSION['message'] = 'Please fix the highlighted fields.';
    $_SESSION['old'] = $d;
    header('Location: ' . BASE_URL . '/item/create.php');
    exit();
}

$upFile = $_FILES['img_path'] ?? ['error' => UPLOAD_ERR_NO_FILE];
$upFolder = 'products';
$upPrefix = 'watch_';
include('../includes/upload_one.php');
$main = $upPath;
$photoError = $upError;

$gallery = [];
if (!$photoError) {
    $upFiles = $_FILES['gallery'] ?? [];
    include('../includes/upload_many.php');
    $gallery = $upPaths;
    $photoError = $upError;
}
if ($photoError) {
    if ($main && is_file(ROOT_DIR . '/' . $main)) {
        unlink(ROOT_DIR . '/' . $main);
    }
    $_SESSION['message'] = $photoError;
    $_SESSION['old'] = $d;
    header('Location: ' . BASE_URL . '/item/create.php');
    exit();
}

$description = ($d['description'] === '') ? null : $d['description'];
$strap = ($d['strap_type'] === '') ? null : $d['strap_type'];
$water = ($d['water_resistance'] === '') ? null : $d['water_resistance'];
$caseSize = ($d['case_size_mm'] === '') ? null : (int)$d['case_size_mm'];

mysqli_begin_transaction($conn);
try {
    $sql = 'INSERT INTO item (brand, model, description, movement, category, case_size_mm, strap_type, water_resistance, cost_price, sell_price, img_path)
            VALUES (?,?,?,?,?,?,?,?,?,?,?)';
    mysqli_execute_query($conn, $sql, [$d['brand'], $d['model'], $description, $d['movement'], $d['category'], $caseSize, $strap, $water,
        (float)$d['cost_price'], (float)$d['sell_price'], $main]);
    $newId = (int)mysqli_insert_id($conn);

    mysqli_execute_query($conn, 'INSERT INTO stock (item_id, quantity, low_stock_level) VALUES (?,?,?)', [$newId, (int)$d['quantity'], (int)$d['low_stock_level']]);
    foreach ($gallery as $p) {
        mysqli_execute_query($conn, 'INSERT INTO item_image (item_id, img_path) VALUES (?,?)', [$newId, $p]);
    }
    mysqli_commit($conn);
} catch (mysqli_sql_exception $e) {
    mysqli_rollback($conn);
    if ($main && is_file(ROOT_DIR . '/' . $main)) {
        unlink(ROOT_DIR . '/' . $main);
    }
    foreach ($gallery as $p) {
        if (is_file(ROOT_DIR . '/' . $p)) {
            unlink(ROOT_DIR . '/' . $p);
        }
    }
    $_SESSION['message'] = 'Could not save the watch. Please try again.';
    $_SESSION['old'] = $d;
    header('Location: ' . BASE_URL . '/item/create.php');
    exit();
}

$_SESSION['success'] = $d['brand'] . ' ' . $d['model'] . ' was added.';
header('Location: ' . BASE_URL . '/item/index.php');
exit();
