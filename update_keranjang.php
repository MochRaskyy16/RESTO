<?php
session_start();

$id = $_POST['id'];
$qty = (int)$_POST['qty'];

if (isset($_SESSION['keranjang'][$id])) {
    if ($qty <= 0) {
        unset($_SESSION['keranjang'][$id]);
    } else {
        $_SESSION['keranjang'][$id]['qty'] = $qty;
    }
}

echo json_encode(['success' => true]);
?>