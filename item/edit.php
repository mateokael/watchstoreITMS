<?php
session_start();
include('../includes/config.php');
include('../includes/guard_admin.php');

$id = (int)($_GET['id'] ?? 0);
$item = mysqli_fetch_assoc(mysqli_execute_query($conn, 'SELECT i.*, s.quantity, s.low_stock_level FROM item i JOIN stock s ON s.item_id = i.item_id WHERE i.item_id = ?', [$id]));
if (!$item) {
    $_SESSION['message'] = 'That watch does not exist.';
    header('Location: ' . BASE_URL . '/item/index.php');
    exit();
}
$gallery = mysqli_fetch_all(mysqli_execute_query($conn, 'SELECT image_id, img_path FROM item_image WHERE item_id = ? ORDER BY image_id', [$id]), MYSQLI_ASSOC);

$pageTitle = 'Edit ' . ($item['brand'] . ' ' . $item['model']);
include('../includes/header.php');
?>
<h1 class="mb-3">Edit <?= htmlspecialchars((string)(($item['brand'] . ' ' . $item['model']))) ?></h1>
<?php
$formAction = BASE_URL . '/item/update.php';
$submitLabel = 'Save changes';
include('../includes/item_form.php');
?>
<?php include('../includes/footer.php'); ?>
