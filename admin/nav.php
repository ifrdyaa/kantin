<?php
// Navigasi admin yang dipakai SEMUA halaman admin, jadi cukup diubah di sini.
// Sebelum include, isi $halaman_aktif dengan: dashboard | menu | pesanan | qris
$halaman_aktif = $halaman_aktif ?? '';

$nav_items = [
    'dashboard' => ['index.php', 'Dashboard'],
    'menu'      => ['menu.php', 'Kelola Menu'],
    'pesanan'   => ['pesanan.php', 'Pesanan Masuk'],
    'qris'      => ['qris_setting.php', 'Pengaturan QRIS'],
];
?>
<div class="admin-nav">
    <?php foreach ($nav_items as $key => [$url, $label]): ?>
        <a href="<?= $url; ?>"<?= $key === $halaman_aktif ? ' class="active"' : ''; ?>><?= $label; ?></a>
    <?php endforeach; ?>
</div>