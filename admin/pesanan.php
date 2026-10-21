<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit();
}

require_once '../config/database.php';

$halaman_aktif = 'pesanan';

// Pesan hasil aksi dari confirm_payment.php (tampil sekali saja)
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// Label dan warna tiap status: [teks, warna latar, warna teks]
$label_status = [
    'pending'   => ['Menunggu Bayar',  '#FFF3CD', '#856404'],
    'paid'      => ['Dibayar',         '#D4EDDA', '#155724'],
    'process'   => ['Diproses',        '#D1ECF1', '#0C5460'],
    'ready'     => ['Siap Diambil',    '#E2E3E5', '#383D41'],
    'completed' => ['Selesai',         '#CCE5FF', '#004085'],
    'expired'   => ['Expired / Batal', '#F8D7DA', '#721C24'],
];

// Tombol aksi berikutnya untuk tiap status: [status_baru, teks tombol, kelas]
$aksi_berikut = [
    'pending' => ['paid',      '&#10004; Konfirmasi Pembayaran', 'btn-success'],
    'paid'    => ['process',   'Proses Makanan',                 'btn-info'],
    'process' => ['ready',     'Tandai Siap',                    'btn-secondary'],
    'ready'   => ['completed', 'Selesaikan',                     'btn-primary'],
];

// Ambil semua pesanan. "kedaluwarsa" dihitung oleh MySQL supaya waktunya konsisten
$result = mysqli_query(
    $conn,
    "SELECT *, (expire_at IS NOT NULL AND expire_at < NOW()) AS kedaluwarsa
     FROM pesanan ORDER BY id DESC"
);

// Ambil isi semua pesanan sekaligus, lalu kelompokkan per pesanan
$items = [];
$res_items = mysqli_query(
    $conn,
    "SELECT d.pesanan_id, d.jumlah, m.nama
     FROM detail_pesanan d
     LEFT JOIN menu m ON m.id = d.menu_id
     ORDER BY d.id"
);
while ($it = mysqli_fetch_assoc($res_items)) {
    $items[$it['pesanan_id']][] = $it;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Halaman dimuat ulang otomatis tiap 15 detik supaya pesanan baru muncul -->
    <meta http-equiv="refresh" content="15">
    <title>Pesanan Masuk - Admin Kantin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

    <header>
        <h1>Daftar Pesanan Masuk</h1>
        <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
    </header>

    <div class="container">
        <?php include 'nav.php'; ?>

        <?php if ($flash): ?>
            <?php if ($flash[0] === 'success'): ?>
                <div class="alert" style="background: #d4edda; color: #155724;"><?= htmlspecialchars($flash[1]); ?></div>
            <?php else: ?>
                <div class="alert alert-error"><?= htmlspecialchars($flash[1]); ?></div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Kode &amp; Waktu</th>
                        <th>Pembeli</th>
                        <th>Pesanan</th>
                        <th>Status Pembayaran</th>
                        <th>Total</th>
                        <th>Aksi Konfirmasi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) === 0): ?>
                        <tr>
                            <td colspan="6" style="text-align: center;">Belum ada pesanan.</td>
                        </tr>
                    <?php endif; ?>

                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <?php
                        $status = $row['status'] ?: 'pending';
                        $waktu_habis = ($status === 'pending' && $row['kedaluwarsa']);

                        if ($waktu_habis) {
                            $info = ['Waktu Habis', '#F8D7DA', '#721C24'];
                        } else {
                            $info = $label_status[$status] ?? [$status, '#eee', '#333'];
                        }
                        ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($row['kode_pesanan']); ?></strong><br>
                                <small style="color:#777;"><?= htmlspecialchars($row['created_at']); ?></small>
                            </td>
                            <td>
                                <?= htmlspecialchars($row['nama_pembeli']); ?><br>
                                <small style="color:#777;">NIM: <?= htmlspecialchars($row['nim_pembeli']); ?></small>
                            </td>
                            <td>
                                <?php foreach ($items[$row['id']] ?? [] as $it): ?>
                                    <?= (int) $it['jumlah']; ?>&times; <?= htmlspecialchars($it['nama'] ?? '(menu dihapus)'); ?><br>
                                <?php endforeach; ?>
                                <?php if (!empty($row['catatan'])): ?>
                                    <small style="color:#777;">Catatan: <?= htmlspecialchars($row['catatan']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span style="background:<?= $info[1]; ?>; color:<?= $info[2]; ?>; padding:4px 8px; border-radius:4px; font-size:0.75rem; font-weight:bold;">
                                    <?= htmlspecialchars($info[0]); ?>
                                </span>
                            </td>
                            <td><strong>Rp <?= number_format($row['total_harga'], 0, ',', '.'); ?></strong></td>
                            <td>
                                <?php if ($waktu_habis): ?>
                                    <small style="color:#721C24;">Pesanan melewati batas waktu pembayaran</small>
                                <?php elseif (isset($aksi_berikut[$status])): ?>
                                    <?php [$status_baru, $teks, $kelas] = $aksi_berikut[$status]; ?>
                                    <form action="confirm_payment.php" method="POST" style="display:inline;"
                                          <?php if ($status === 'pending'): ?>
                                          onsubmit="return confirm('Pastikan uang sudah masuk dengan total yang sesuai. Lanjutkan konfirmasi?');"
                                          <?php endif; ?>>
                                        <input type="hidden" name="kode" value="<?= htmlspecialchars($row['kode_pesanan']); ?>">
                                        <input type="hidden" name="status" value="<?= $status_baru; ?>">
                                        <button type="submit" class="btn btn-sm <?= $kelas; ?>"><?= $teks; ?></button>
                                    </form>
                                <?php else: ?>
                                    <span style="color:#aaa;">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>