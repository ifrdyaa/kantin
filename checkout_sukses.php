<?php
session_start();
if (!isset($_SESSION['last_order'])) {
    header("Location: index.php");
    exit();
}

$order = $_SESSION['last_order'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Berhasil - Kantin Kampus</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <header>
        <h1>Kantin Kampus</h1>
    </header>

    <div class="container">
        <div class="form-card" style="text-align: center; padding: 30px 20px;">
            <div style="font-size: 3rem; color: var(--success);">&#10004;</div>
            <h2 style="margin-top: 10px;">Pesanan Berhasil Dibuat!</h2>
            <p style="color: var(--text-muted); margin-top: 5px;">Tunjukkan kode pesanan berikut ke penjual kantin untuk pengambilan &amp; pembayaran:</p>

            <div style="display: inline-block; background: #FFF0E5; color: var(--primary); border: 2px dashed var(--primary); font-size: 1.6rem; font-weight: bold; padding: 10px 20px; border-radius: 8px; margin: 15px 0; letter-spacing: 1px;">
                <?= htmlspecialchars($order['kode']); ?>
            </div>

            <div style="text-align: left; background: #f9f9f9; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 0.9rem;">
                <div><strong>Nama:</strong> <?= htmlspecialchars($order['nama']); ?> (<?= htmlspecialchars($order['nim']); ?>)</div>
                <?php if (!empty($order['catatan'])): ?>
                    <div><strong>Catatan:</strong> <?= htmlspecialchars($order['catatan']); ?></div>
                <?php endif; ?>
                <hr style="margin: 8px 0; border: none; border-top: 1px solid #ddd;">
                <strong>Detail Items:</strong>
                <?php foreach ($order['items'] as $item): ?>
                    <div>&bull; <?= htmlspecialchars($item['nama']); ?> (<?= $item['qty']; ?>x) - Rp <?= number_format($item['subtotal'], 0, ',', '.'); ?></div>
                <?php endforeach; ?>
                <hr style="margin: 8px 0; border: none; border-top: 1px solid #ddd;">
                <div style="font-size: 1rem; font-weight: bold; color: var(--primary);">Total: Rp <?= number_format($order['total'], 0, ',', '.'); ?></div>
            </div>

            <a href="index.php" class="btn">Kembali ke Menu Utama</a>
        </div>
    </div>

</body>
</html>