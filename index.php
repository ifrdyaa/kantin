<?php
session_start();
require_once 'config/database.php';
require_once 'config/expire.php';

// Kembalikan stok dari pesanan yang sudah kedaluwarsa agar stok yang tampil akurat
batalkan_pesanan_kedaluwarsa($conn);

$result = mysqli_query($conn, "SELECT * FROM menu ORDER BY id DESC");

// Hitung jumlah item di keranjang
$total_cart_qty = 0;
if (isset($_SESSION['cart'])) {
    $total_cart_qty = array_sum($_SESSION['cart']);
}

// Gambar abu-abu lokal sebagai pengganti via.placeholder.com (tanpa internet)
$placeholder = "data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22200%22 height=%22140%22%3E%3Crect width=%22100%25%22 height=%22100%25%22 fill=%22%23ddd%22/%3E%3C/svg%3E";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kantin Online Kampus</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <header>
        <h1>Kantin Kampus</h1>
        <div style="display: flex; gap: 10px; align-items: center;">
            <a href="cart.php" style="background: rgba(255,255,255,0.2); color: white; padding: 6px 12px; border-radius: 20px; text-decoration: none; font-size: 0.85rem; font-weight: 600;">
                &#128722; Keranjang (<?= (int) $total_cart_qty; ?>)
            </a>
            <a href="admin/login.php" style="color: white; text-decoration: none; font-size: 0.85rem;">Login Penjual</a>
        </div>
    </header>

    <div class="container">
        <h2 style="margin-bottom: 15px;">Daftar Menu</h2>

        <div class="menu-grid">
            <?php while ($row = mysqli_fetch_assoc($result)):
                $in_cart_qty = isset($_SESSION['cart'][$row['id']]) ? $_SESSION['cart'][$row['id']] : 0;
                $is_habis = $row['stok'] <= 0;
                $is_max_cart = $in_cart_qty >= $row['stok'];
            ?>
                <div class="card">
                    <div class="card-img-wrap">
                        <img src="uploads/<?= htmlspecialchars($row['foto']); ?>"
                             alt="<?= htmlspecialchars($row['nama']); ?>"
                             onerror="this.onerror=null;this.src='<?= $placeholder; ?>';">

                        <?php if ($is_habis): ?>
                            <span class="badge-habis">Habis</span>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <div class="card-title"><?= htmlspecialchars($row['nama']); ?></div>
                        <div class="card-price">Rp <?= number_format($row['harga'], 0, ',', '.'); ?></div>
                        <div class="card-stok">
                            Stok: <?= $row['stok'] > 0 ? (int) $row['stok'] : '<span style="color:red;">0</span>'; ?>
                        </div>

                        <?php if ($is_habis): ?>
                            <button class="btn" style="margin-top: auto; background:#ccc;" disabled>Stok Habis</button>
                        <?php elseif ($is_max_cart): ?>
                            <button class="btn" style="margin-top: auto; background:#ccc;" disabled>Maks Stok</button>
                        <?php else: ?>
                            <a href="cart.php?action=add&id=<?= (int) $row['id']; ?>" class="btn" style="margin-top: auto;">+ Keranjang</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

</body>
</html>