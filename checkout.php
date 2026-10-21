<?php
session_start();
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['cart'])) {
    header("Location: index.php");
    exit();
}

$nama = mysqli_real_escape_string($conn, trim($_POST['nama']));
$nim = mysqli_real_escape_string($conn, trim($_POST['nim']));
$catatan = mysqli_real_escape_string($conn, trim($_POST['catatan']));

if (empty($nama) || empty($nim)) {
    die("Nama dan NIM wajib diisi!");
}

// 1. Verifikasi Stok & Hitung Total
$total_harga = 0;
$items_to_order = [];

foreach ($_SESSION['cart'] as $menu_id => $qty) {
    $menu_id = intval($menu_id);
    $qty = intval($qty);

    $res = mysqli_query($conn, "SELECT * FROM menu WHERE id = $menu_id FOR UPDATE");
    if ($row = mysqli_fetch_assoc($res)) {
        if ($qty > $row['stok']) {
            die("Maaf, stok menu '" . $row['nama'] . "' tidak mencukupi untuk pesanan Anda.");
        }
        $subtotal = $row['harga'] * $qty;
        $total_harga += $subtotal;
        $items_to_order[] = [
            'menu_id' => $menu_id,
            'nama' => $row['nama'],
            'harga' => $row['harga'],
            'qty' => $qty,
            'subtotal' => $subtotal
        ];
    }
}

// 2. Generate Kode Pesanan Sementara yang Unik
$kode_sementara = uniqid('TMP-'); // Contoh: TMP-65f123a4b5c6d

// 3. Simpan Pesanan ke DB
mysqli_begin_transaction($conn);

try {
    // A. Insert Header Pesanan dengan Kode Sementara
    $stmt1 = mysqli_prepare($conn, "INSERT INTO pesanan (kode_pesanan, nama_pembeli, nim_pembeli, catatan, total_harga, status, expire_at) VALUES (?, ?, ?, ?, ?, 'pending', DATE_ADD(NOW(), INTERVAL 10 MINUTE))");
    mysqli_stmt_bind_param($stmt1, "ssssi", $kode_sementara, $nama, $nim, $catatan, $total_harga);
    mysqli_stmt_execute($stmt1);
    
    // B. Ambil ID Auto Increment yang Baru Terbentuk
    $pesanan_id = mysqli_insert_id($conn);

    // C. Format Kode Pesanan Final (KTN-0001, KTN-0002, dst.)
    $kode_pesanan = 'KTN-' . str_pad($pesanan_id, 4, '0', STR_PAD_LEFT);

    // D. Update Kode Pesanan Final ke Baris Tersebut
    $stmt_update = mysqli_prepare($conn, "UPDATE pesanan SET kode_pesanan = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt_update, "si", $kode_pesanan, $pesanan_id);
    mysqli_stmt_execute($stmt_update);

    // E. Insert Detail & Kurangi Stok Menu
    $stmt2 = mysqli_prepare($conn, "INSERT INTO detail_pesanan (pesanan_id, menu_id, jumlah, harga_satuan, subtotal) VALUES (?, ?, ?, ?, ?)");
    $stmt_stok = mysqli_prepare($conn, "UPDATE menu SET stok = stok - ? WHERE id = ?");

    foreach ($items_to_order as $item) {
        mysqli_stmt_bind_param($stmt2, "iiiii", $pesanan_id, $item['menu_id'], $item['qty'], $item['harga'], $item['subtotal']);
        mysqli_stmt_execute($stmt2);

        mysqli_stmt_bind_param($stmt_stok, "ii", $item['qty'], $item['menu_id']);
        mysqli_stmt_execute($stmt_stok);
    }

    mysqli_commit($conn);

    // Reset keranjang
    $_SESSION['cart'] = [];

    // Redirect ke Halaman Pembayaran QRIS
    header("Location: pembayaran.php?kode=" . $kode_pesanan);
    exit();

} catch (Exception $e) {
    mysqli_rollback($conn);
    die("Gagal membuat pesanan: " . $e->getMessage());
}

catch (Exception $e) {
    mysqli_rollback($conn);
    die("Gagal membuat pesanan: " . $e->getMessage());
}
