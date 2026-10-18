<?php
session_start();
include('../includes/config.php');
include('../includes/guard_admin.php');

$pageTitle = 'Add watch';
include('../includes/header.php');
?>
<h1 class="mb-3">Add watch</h1>
<?php
$formAction = BASE_URL . '/item/store.php';
$item = null;
$submitLabel = 'Add watch';
include('../includes/item_form.php');
?>
<?php include('../includes/footer.php'); ?>
