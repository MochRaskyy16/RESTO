<?php
session_start();

$id = $_POST['id'];

if (isset($_SESSION['keranjang'][$id])) {
    unset($_SESSION['keranjang'][$id]);
}

echo json_encode(['success' => true]);
?>