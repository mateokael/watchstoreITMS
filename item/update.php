<?php
// Saves changes to an existing watch. Photos you don't touch stay as they are.
session_start();
include('../includes/config.php');
include('../includes/guard_admin.php');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/index.php');
    exit();
}

$id = (int)($_POST['item_id'] ?? 0);
$existing = mysqli_fetch_assoc(mysqli_execute_query($conn, 'SELECT img_path FROM item WHERE item_id = ?', [$id]));
if (!$existing) {
    $_SESSION['message'] = 'That watch does not exist.';
    header('Location: ' . BASE_URL . '/item/index.php');
    exit();
}

include('../includes/item_validate.php');
if ($err) {
    $_SESSION['errors'] = $err;
    $_SESSION['message'] = 'Please fix the highlighted fields.';
    $_SESSION['old'] = $d;
    header('Location: ' . BASE_URL . '/item/edit.php?id=' . $id);
    exit();
}

$upFile = $_FILES['img_path'] ?? ['error' => UPLOAD_ERR_NO_FILE];
$upFolder = 'products';
$upPrefix = 'watch_';
include('../includes/upload_one.php');
$newMain = $upPath;
$photoError = $upError;

$newGallery = [];
if (!$photoError) {
    $upFiles = $_FILES['gallery'] ?? [];
    include('../includes/upload_many.php');
    $newGallery = $upPaths;
    $photoError = $upError;
}
if ($photoError) {
    if ($newMain && is_file(ROOT_DIR . '/' . $newMain)) {
        unlink(ROOT_DIR . '/' . $newMain);
    }
    $_SESSION['message'] = $photoError;
    $_SESSION['old'] = $d;
    header('Location: ' . BASE_URL . '/item/edit.php?id=' . $id);
    exit();
}

$mainPath = $newMain ?? $existing['img_path'];
$removeIds = array_map('intval', (array)($_POST['remove_images'] ?? []));
$filesToDelete = [];

$description = ($d['description'] === '') ? null : $d['description'];
$strap = ($d['strap_type'] === '') ? null : $d['strap_type'];
$water = ($d['water_resistance'] === '') ? null : $d['water_resistance'];
$caseSize = ($d['case_size_mm'] === '') ? null : (int)$d['case_size_mm'];

mysqli_begin_transaction($conn);
try {
    $sql = 'UPDATE item SET brand=?, model=?, description=?, movement=?, category=?, case_size_mm=?, strap_type=?,
            water_resistance=?, cost_price=?, sell_price=?, img_path=? WHERE item_id=?';
    mysqli_execute_query($conn, $sql, [$d['brand'], $d['model'], $description, $d['movement'], $d['category'], $caseSize, $strap, $water,
        (float)$d['cost_price'], (float)$d['sell_price'], $mainPath, $id]);

    mysqli_execute_query($conn, 'INSERT INTO stock (item_id, quantity, low_stock_level) VALUES (?,?,?)
            ON DUPLICATE KEY UPDATE quantity = VALUES(quantity), low_stock_level = VALUES(low_stock_level)', [$id, (int)$d['quantity'], (int)$d['low_stock_level']]);

    foreach ($removeIds as $imgId) {
        $row = mysqli_fetch_assoc(mysqli_execute_query($conn, 'SELECT img_path FROM item_image WHERE image_id = ? AND item_id = ?', [$imgId, $id]));
        if ($row) {
            mysqli_execute_query($conn, 'DELETE FROM item_image WHERE image_id = ?', [$imgId]);
            $filesToDelete[] = $row['img_path'];
        }
    }
    foreach ($newGallery as $p) {
        mysqli_execute_query($conn, 'INSERT INTO item_image (item_id, img_path) VALUES (?,?)', [$id, $p]);
    }
    if ($newMain && $existing['img_path']) {
        $filesToDelete[] = $existing['img_path'];
    }
    mysqli_commit($conn);
} catch (mysqli_sql_exception $e) {
    mysqli_rollback($conn);
    if ($newMain && is_file(ROOT_DIR . '/' . $newMain)) {
        unlink(ROOT_DIR . '/' . $newMain);
    }
    foreach ($newGallery as $p) {
        if (is_file(ROOT_DIR . '/' . $p)) {
            unlink(ROOT_DIR . '/' . $p);
        }
    }
    $_SESSION['message'] = 'Could not save your changes. Please try again.';
    $_SESSION['old'] = $d;
    header('Location: ' . BASE_URL . '/item/edit.php?id=' . $id);
    exit();
}

foreach ($filesToDelete as $f) {
    if ($f && !str_contains($f, '..') && is_file(ROOT_DIR . '/' . $f)) {
        unlink(ROOT_DIR . '/' . $f);
    }
}
$_SESSION['success'] = $d['brand'] . ' ' . $d['model'] . ' was updated.';
header('Location: ' . BASE_URL . '/item/index.php');
exit();
