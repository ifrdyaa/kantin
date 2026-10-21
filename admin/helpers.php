<?php
// File bantuan untuk halaman admin: upload gambar yang aman dan hapus file lama.
// Dipakai oleh menu.php, menu_delete.php, dan qris_setting.php.

const UPLOAD_DIR = __DIR__ . '/../uploads/';
const GAMBAR_DEFAULT = ['default.jpg', 'default_qris.png'];

/**
 * Validasi dan simpan gambar yang diupload.
 * Mengembalikan [nama_file, pesan_error].
 * - Tidak ada file dipilih  => [null, null]
 * - Gagal                   => [null, "pesan error"]
 * - Berhasil                => ["nama_file_acak.jpg", null]
 */
function upload_gambar(array $file, string $prefix): array
{
    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return [null, null];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return [null, "Upload gagal (kode error " . $file['error'] . ")."];
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        return [null, "Format foto hanya boleh JPG, PNG, atau WEBP!"];
    }
    if ($file['size'] > 2 * 1024 * 1024) {
        return [null, "Ukuran foto maksimal 2 MB!"];
    }
    // Pastikan isinya benar-benar gambar, bukan file lain yang diganti ekstensinya
    if (@getimagesize($file['tmp_name']) === false) {
        return [null, "File yang diunggah bukan gambar yang valid!"];
    }

    $nama = $prefix . bin2hex(random_bytes(8)) . '.' . $ext; // nama diacak
    if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $nama)) {
        return [null, "Gagal menyimpan file. Pastikan folder uploads bisa ditulis."];
    }
    return [$nama, null];
}

/** Hapus file di folder uploads, kecuali gambar default. */
function hapus_gambar(?string $nama): void
{
    $nama = basename((string) $nama);
    if ($nama === '' || in_array($nama, GAMBAR_DEFAULT, true)) {
        return;
    }
    $path = UPLOAD_DIR . $nama;
    if (is_file($path)) {
        @unlink($path);
    }
}