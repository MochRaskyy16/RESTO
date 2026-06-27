<?php
/**
 * backup_delete.php - Hapus file backup
 */

session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$file = isset($_GET['file']) ? $_GET['file'] : '';
$backup_dir = 'backup/';

if (!empty($file)) {
    $file_path = $backup_dir . $file;
    if (file_exists($file_path)) {
        unlink($file_path);
    }
}

header("Location: backup.php?status=deleted");
exit();
?>
