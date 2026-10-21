<?php
session_start();
// Proteksi login admin
if (!isset($_SESSION['admin_logged_in'])) {
    http_response_code(403);
    die("Akses ditolak. Silakan login terlebih dahulu.");
}

// Hanya menerima POST dari tombol di halaman pesanan
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: pesanan.php");
    exit();
}

require_once '../config/database.php';

$kode        = trim($_POST['kode'] ?? '');
$status_baru = $_POST['status'] ?? '';

// Alur yang diizinkan: status_baru => status_sebelumnya yang wajib
// (tidak boleh loncat tahap, dan tidak bisa dikonfirmasi dua kali)
$alur = [
    'paid'      => 'pending',
    'process'   => 'paid',
    'ready'     => 'process',
    'completed' => 'ready',
];

if ($kode === '' || !isset($alur[$status_baru])) {
    $_SESSION['flash'] = ['error', 'Permintaan tidak valid.'];
    header("Location: pesanan.php");
    exit();
}

$status_lama = $alur[$status_baru];

if ($status_baru === 'paid') {
    // Konfirmasi pembayaran hanya sah kalau pesanan belum kedaluwarsa
    $sql = "UPDATE pesanan SET status = ?
            WHERE kode_pesanan = ? AND status = ?
              AND (expire_at IS NULL OR expire_at > NOW())";
} else {
    $sql = "UPDATE pesanan SET status = ? WHERE kode_pesanan = ? AND status = ?";
}

// Satu query atomik: status hanya berubah jika kondisinya masih cocok saat itu juga
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "sss", $status_baru, $kode, $status_lama);
mysqli_stmt_execute($stmt);

if (mysqli_stmt_affected_rows($stmt) > 0) {
    $_SESSION['flash'] = ['success', "Pesanan $kode berhasil diperbarui."];
} else {
    $_SESSION['flash'] = ['error', "Pesanan $kode tidak bisa diubah. Mungkin sudah dikonfirmasi, kedaluwarsa, atau dibatalkan."];
}

header("Location: pesanan.php");
exit();