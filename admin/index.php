<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit();
}

$halaman_aktif = 'dashboard';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Kantin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

    <header>
        <h1>Admin Kantin</h1>
        <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
    </header>

    <div class="container">
        <?php include 'nav.php'; ?>

        <div class="form-card" style="max-width: 100%;">
            <h3>Selamat Datang, <?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?>!</h3>
            <p style="margin-top: 10px; color: var(--text-muted);">
                Gunakan menu di atas: <strong>Kelola Menu</strong> untuk mengatur menu dan stok,
                <strong>Pesanan Masuk</strong> untuk mengonfirmasi pembayaran dan memproses pesanan,
                serta <strong>Pengaturan QRIS</strong> untuk mengganti gambar QRIS kantin.
            </p>
        </div>
    </div>

</body>
</html>