<?php
session_start();
include('../includes/config.php');
$_SESSION = [];
session_regenerate_id(true);
$_SESSION['info'] = 'You are logged out.';
header('Location: ' . BASE_URL . '/index.php');
exit();
