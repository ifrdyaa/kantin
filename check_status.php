<?php
header('Content-Type: application/json');
require_once 'config/database.php';
require_once 'config/expire.php';

$kode = trim($_GET['kode'] ?? '');

if ($kode === '') {
    echo json_encode(['success' => false, 'message' => 'Kode pesanan tidak valid']);
    exit();
}

// Batalkan pesanan yang sudah lewat batas waktu dan kembalikan stoknya
batalkan_pesanan_kedaluwarsa($conn);

// sisa_detik dihitung oleh MySQL, jadi tidak bergantung pada jam browser
$stmt = mysqli_prepare(
    $conn,
    "SELECT status, total_harga,
            COALESCE(GREATEST(TIMESTAMPDIFF(SECOND, NOW(), expire_at), 0), 0) AS sisa_detik
     FROM pesanan WHERE kode_pesanan = ?"
);
mysqli_stmt_bind_param($stmt, "s", $kode);
mysqli_stmt_execute($stmt);
$row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if ($row) {
    echo json_encode([
        'success' => true,
        'status' => $row['status'],
        'total_harga_formatted' => 'Rp ' . number_format($row['total_harga'], 0, ',', '.'),
        'sisa_detik' => (int) $row['sisa_detik']
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Pesanan tidak ditemukan']);
}