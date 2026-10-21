<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = []; // Format: [menu_id => qty]
}

$action = isset($_GET['action']) ? $_GET['action'] : 'view';

// 1. Tambah ke keranjang
if ($action === 'add' && isset($_GET['id'])) {
    $menu_id = intval($_GET['id']);
    
    // Cek stok menu di database
    $res = mysqli_query($conn, "SELECT stok FROM menu WHERE id = $menu_id");
    if ($row = mysqli_fetch_assoc($res)) {
        $stok_db = $row['stok'];
        $current_qty = isset($_SESSION['cart'][$menu_id]) ? $_SESSION['cart'][$menu_id] : 0;

        if ($current_qty < $stok_db) {
            $_SESSION['cart'][$menu_id] = $current_qty + 1;
        }
    }
    header("Location: index.php");
    exit();
}

// 2. Update Qty (+ / -)
if ($action === 'update' && isset($_GET['id']) && isset($_GET['change'])) {
    $menu_id = intval($_GET['id']);
    $change = intval($_GET['change']);

    $res = mysqli_query($conn, "SELECT stok FROM menu WHERE id = $menu_id");
    if ($row = mysqli_fetch_assoc($res)) {
        $stok_db = $row['stok'];
        $current_qty = isset($_SESSION['cart'][$menu_id]) ? $_SESSION['cart'][$menu_id] : 0;
        $new_qty = $current_qty + $change;

        if ($new_qty <= 0) {
            unset($_SESSION['cart'][$menu_id]);
        } elseif ($new_qty <= $stok_db) {
            $_SESSION['cart'][$menu_id] = $new_qty;
        }
    }
    header("Location: cart.php");
    exit();
}

// 3. Hapus Item
if ($action === 'delete' && isset($_GET['id'])) {
    $menu_id = intval($_GET['id']);
    unset($_SESSION['cart'][$menu_id]);
    header("Location: cart.php");
    exit();
}

// Tampilkan Halaman Keranjang Belanja
$cart_items = [];
$total_harga = 0;

if (!empty($_SESSION['cart'])) {
    $ids = implode(',', array_map('intval', array_keys($_SESSION['cart'])));
    $res = mysqli_query($conn, "SELECT * FROM menu WHERE id IN ($ids)");
    while ($row = mysqli_fetch_assoc($res)) {
        $qty = $_SESSION['cart'][$row['id']];
        // Batasi qty jika melebihi stok di DB
        if ($qty > $row['stok']) {
            $qty = $row['stok'];
            $_SESSION['cart'][$row['id']] = $qty;
        }
        if ($qty > 0) {
            $subtotal = $row['harga'] * $qty;
            $total_harga += $subtotal;
            $row['qty'] = $qty;
            $row['subtotal'] = $subtotal;
            $cart_items[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Belanja - Kantin Kampus</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <header>
        <h1 onclick="window.location.href='index.php'" style="cursor:pointer;">Kantin Kampus</h1>
        <a href="index.php" style="color: white; text-decoration: none; font-size: 0.85rem;">&larr; Kembali ke Menu</a>
    </header>

    <div class="container">
        <h2 style="margin-bottom: 15px;">Keranjang Belanja</h2>

        <?php if (empty($cart_items)): ?>
            <div class="form-card" style="text-align: center; padding: 40px 20px;">
                <p style="color: var(--text-muted);">Keranjang belanjaan Anda masih kosong.</p>
                <a href="index.php" class="btn" style="margin-top: 15px;">Lihat Menu Kantin</a>
            </div>
        <?php else: ?>
            <div class="form-card">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Menu</th>
                                <th>Harga</th>
                                <th>Jumlah</th>
                                <th>Subtotal</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cart_items as $item): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($item['nama']); ?></strong></td>
                                    <td>Rp <?= number_format($item['harga'], 0, ',', '.'); ?></td>
                                    <td>
                                        <a href="cart.php?action=update&id=<?= $item['id']; ?>&change=-1" class="btn btn-sm btn-secondary">-</a>
                                        <span style="margin: 0 8px; font-weight: bold;"><?= $item['qty']; ?></span>
                                        <a href="cart.php?action=update&id=<?= $item['id']; ?>&change=1" class="btn btn-sm btn-secondary">+</a>
                                    </td>
                                    <td><strong>Rp <?= number_format($item['subtotal'], 0, ',', '.'); ?></strong></td>
                                    <td>
                                        <a href="cart.php?action=delete&id=<?= $item['id']; ?>" class="btn btn-danger btn-sm">Hapus</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div style="background: #FFF8F3; border: 1px solid #FFE0CC; padding: 15px; border-radius: 8px; margin-top: 15px; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <span style="color: var(--text-muted); font-size: 0.85rem;">Total Pembayaran:</span>
                        <div style="font-size: 1.2rem; font-weight: bold; color: var(--primary);">Rp <?= number_format($total_harga, 0, ',', '.'); ?></div>
                    </div>
                    <a href="index.php" class="btn btn-secondary btn-sm">+ Tambah Menu</a>
                </div>
            </div>

            <!-- Form Checkout -->
            <div class="form-card">
                <h3 style="font-size: 1.1rem; margin-bottom: 15px;">Form Checkout Pembeli</h3>
                <form action="checkout.php" method="POST">
                    <div class="form-group">
                        <label>Nama Mahasiswa *</label>
                        <input type="text" name="nama" required placeholder="Contoh: Budi Santoso">
                    </div>
                    <div class="form-group">
                        <label>NIM Mahasiswa *</label>
                        <input type="text" name="nim" required placeholder="Contoh: 212410101001">
                    </div>
                    <div class="form-group">
                        <label>Catatan Pesanan (Opsional)</label>
                        <textarea name="catatan" rows="2" style="width: 100%; padding: 9px; border: 1px solid #ccc; border-radius: 6px;" placeholder="Contoh: Sambal dipisah"></textarea>
                    </div>
                    <button type="submit" class="btn" style="width: 100%; padding: 12px; font-size: 1rem;">Konfirmasi &amp; Pesan Sekarang</button>
                </form>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>