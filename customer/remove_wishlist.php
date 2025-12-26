<?php
session_start();

define("DS", DIRECTORY_SEPARATOR);
define("SITE_ROOT", realpath(dirname(__FILE__) . '/../includes'));
define("LIB_PATH_INC", SITE_ROOT . DS);

require_once('../includes/config.php');
require_once('../includes/database.php');

if (!isset($_SESSION['customer_id'])) {
    header('Location: login.php');
    exit();
}

$wishlist_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$customer_id = $_SESSION['customer_id'];

if ($wishlist_id > 0) {
    $db->query("DELETE FROM wishlist WHERE id = $wishlist_id AND customer_id = $customer_id");
}

header('Location: wishlist.php');
exit();
