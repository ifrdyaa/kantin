<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit();
}

require_once '../config/database.php';
require_once 'helpers.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // Ambil nama file foto sebelum menu dihapus dari database
    $stmt = mysqli_prepare($conn, "SELECT foto FROM menu WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($data) {
        $del = mysqli_prepare($conn, "DELETE FROM menu WHERE id = ?");
        mysqli_stmt_bind_param($del, "i", $id);
        mysqli_stmt_execute($del);

        // Hapus file fisik setelah data di database terhapus (default.jpg tidak ikut dihapus)
        hapus_gambar($data['foto']);
    }
}

header("Location: menu.php");
exit();