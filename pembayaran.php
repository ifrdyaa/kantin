<?php
session_start();
require_once 'config/database.php';
require_once 'config/expire.php';

$kode_pesanan = trim($_GET['kode'] ?? '');

if ($kode_pesanan === '') {
    header("Location: index.php");
    exit();
}

// Batalkan pesanan kedaluwarsa dulu supaya status yang tampil akurat
batalkan_pesanan_kedaluwarsa($conn);

// Ambil data pesanan beserta sisa waktu (dihitung oleh MySQL)
$stmt = mysqli_prepare(
    $conn,
    "SELECT *, COALESCE(GREATEST(TIMESTAMPDIFF(SECOND, NOW(), expire_at), 0), 0) AS sisa_detik
     FROM pesanan WHERE kode_pesanan = ?"
);
mysqli_stmt_bind_param($stmt, "s", $kode_pesanan);
mysqli_stmt_execute($stmt);
$order = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$order) {
    die("Pesanan tidak ditemukan!");
}

// Ambil gambar QRIS dari pengaturan
$qris_image = 'default_qris.png';
$qris_res = mysqli_query($conn, "SELECT setting_value FROM settings WHERE setting_key = 'qris_image'");
$qris_row = mysqli_fetch_assoc($qris_res);
if (!empty($qris_row['setting_value'])) {
    $qris_image = $qris_row['setting_value'];
}

$total_formatted = 'Rp ' . number_format($order['total_harga'], 0, ',', '.');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran QRIS - <?= htmlspecialchars($order['kode_pesanan']); ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <header>
        <h1>Kantin Kampus</h1>
    </header>

    <div class="container">
        <div class="form-card" id="payment-box" style="text-align: center; max-width: 480px; margin: 20px auto;">
            <!-- Isi kotak ini diperbarui oleh JavaScript -->
        </div>
    </div>

    <script>
        const kodePesanan = <?= json_encode($order['kode_pesanan']); ?>;
        const qrisImage = <?= json_encode($qris_image); ?>;
        const totalFormatted = <?= json_encode($total_formatted); ?>;
        let statusSekarang = <?= json_encode($order['status'] ?: 'pending'); ?>;
        let sisaDetik = <?= (int) $order['sisa_detik']; ?>; // dari server, bukan jam browser

        // Gambar abu-abu lokal jika gambar QRIS tidak ditemukan
        const placeholderQris = "data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22200%22 height=%22200%22%3E%3Crect width=%22100%25%22 height=%22100%25%22 fill=%22%23ddd%22/%3E%3C/svg%3E";

        const box = document.getElementById('payment-box');
        let timerPolling = null;

        function formatWaktu(detik) {
            const m = Math.floor(detik / 60);
            const s = detik % 60;
            return String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
        }

        // Menggambar tampilan sesuai status. Hanya dipanggil saat status berubah.
        function render() {
            if (statusSekarang === 'pending') {
                box.innerHTML = `
                    <div style="background: #FFF0E5; color: var(--primary); border: 1px solid #FFD1B3; padding: 8px 14px; border-radius: 20px; display: inline-block; font-weight: bold; margin-bottom: 15px;">
                        &#9200; Sisa Waktu Bayar: <span id="timer">${formatWaktu(sisaDetik)}</span>
                    </div>
                    <div style="font-size:0.9rem; color:#666;">Kode Pesanan:</div>
                    <div style="font-size: 1.8rem; font-weight: 800; color: var(--primary); letter-spacing: 1px;">${kodePesanan}</div>
                    <div style="font-size:0.85rem; color:#666; margin-top:5px;">Total Bayar:</div>
                    <div style="font-size: 2rem; font-weight: bold; color: #111;">${totalFormatted}</div>

                    <div style="width: 200px; height: 200px; margin: 15px auto; border: 2px dashed #ccc; border-radius: 8px; padding: 5px;">
                        <img src="uploads/${encodeURIComponent(qrisImage)}" alt="QRIS Kantin" style="width:100%; height:100%; object-fit:contain;" onerror="this.onerror=null;this.src='${placeholderQris}'">
                    </div>

                    <div style="margin-top: 15px; font-weight: 600; color: #d35400;">
                        Status: <span style="background:#FFF3CD; color:#856404; padding:4px 8px; border-radius:4px; font-size:0.8rem;">Menunggu Konfirmasi Penjual</span>
                    </div>
                    <p style="font-size: 0.8rem; color: #777; margin-top: 8px;">
                        Scan QRIS di atas. Halaman ini akan otomatis berubah setelah penjual mengonfirmasi.
                    </p>
                `;
            } else if (['paid', 'process', 'ready', 'completed'].includes(statusSekarang)) {
                let msg = 'Pesanan Anda telah dikonfirmasi dan sedang disiapkan!';
                if (statusSekarang === 'ready') msg = 'Pesanan Anda sudah SIAP! Silakan ambil di outlet kantin.';
                if (statusSekarang === 'completed') msg = 'Pesanan Selesai. Terima kasih!';

                box.innerHTML = `
                    <div style="font-size: 3.5rem; color: var(--success);">&#10004;</div>
                    <h2 style="margin-top: 5px;">Pembayaran Selesai!</h2>
                    <p style="color: var(--text-muted); font-size: 0.9rem; margin-top:5px;">${msg}</p>
                    <div style="margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 8px; font-weight:bold; font-size:1.2rem; color:var(--primary);">
                        Kode: ${kodePesanan}
                    </div>
                    <a href="index.php" class="btn">Kembali ke Menu Utama</a>
                `;
            } else if (statusSekarang === 'expired') {
                box.innerHTML = `
                    <div style="font-size: 3rem; color: var(--danger);">&#10008;</div>
                    <h2 style="color: var(--danger); margin-top:10px;">Pesanan Dibatalkan</h2>
                    <p style="color: var(--text-muted); font-size: 0.9rem; margin-top:5px;">Waktu pembayaran 10 menit telah habis. Stok barang telah dikembalikan.</p>
                    <a href="index.php" class="btn" style="margin-top:15px;">Buat Pesanan Baru</a>
                `;
            }

            // Berhenti memeriksa status jika pesanan sudah berakhir
            if (['completed', 'expired'].includes(statusSekarang) && timerPolling) {
                clearInterval(timerPolling);
            }
        }

        // Hitung mundur tiap 1 detik (hanya memperbarui teks timer, tidak menggambar ulang)
        setInterval(function () {
            if (statusSekarang !== 'pending') return;
            if (sisaDetik > 0) sisaDetik--;
            const el = document.getElementById('timer');
            if (el) el.textContent = formatWaktu(sisaDetik);
        }, 1000);

        // Cek status ke server tiap 2 detik
        function cekStatus() {
            fetch('check_status.php?kode=' + encodeURIComponent(kodePesanan))
                .then(response => response.json())
                .then(data => {
                    if (!data.success) return;
                    sisaDetik = data.sisa_detik;
                    if (data.status !== statusSekarang) {
                        statusSekarang = data.status;
                        render();
                    }
                })
                .catch(err => console.error("Error polling:", err));
        }

        render();
        cekStatus();
        timerPolling = setInterval(cekStatus, 2000);
    </script>
</body>
</html>