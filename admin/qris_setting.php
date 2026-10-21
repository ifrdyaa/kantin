<?php
session_start();
// Proteksi login admin
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit();
}

require_once '../config/database.php';
require_once 'helpers.php';

$halaman_aktif = 'qris';
$error = '';
$success = '';

// Gambar abu-abu lokal sebagai pengganti via.placeholder.com (tanpa internet)
$placeholder = "data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22200%22 height=%22200%22%3E%3Crect width=%22100%25%22 height=%22100%25%22 fill=%22%23ddd%22/%3E%3C/svg%3E";

// Ambil satu nilai dari tabel settings (kosong jika belum ada)
function ambil_setting(mysqli $conn, string $key): string
{
    $stmt = mysqli_prepare($conn, "SELECT setting_value FROM settings WHERE setting_key = ?");
    mysqli_stmt_bind_param($stmt, "s", $key);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    return $row['setting_value'] ?? '';
}

// Handle Upload Gambar QRIS Baru
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    [$file_baru, $pesan_upload] = upload_gambar($_FILES['qris'] ?? ['error' => UPLOAD_ERR_NO_FILE], 'qris_kantin_');

    if ($pesan_upload) {
        $error = $pesan_upload;
    } elseif (!$file_baru) {
        $error = "Silakan pilih file gambar QRIS terlebih dahulu!";
    } else {
        $file_lama = ambil_setting($conn, 'qris_image');

        try {
            // setting_key harus PRIMARY KEY agar ON DUPLICATE KEY UPDATE bekerja
            $stmt = mysqli_prepare(
                $conn,
                "INSERT INTO settings (setting_key, setting_value) VALUES ('qris_image', ?)
                 ON DUPLICATE KEY UPDATE setting_value = ?"
            );
            mysqli_stmt_bind_param($stmt, "ss", $file_baru, $file_baru);
            mysqli_stmt_execute($stmt);

            hapus_gambar($file_lama); // hapus QRIS lama setelah yang baru tersimpan
            $success = "Gambar QRIS berhasil diperbarui!";
        } catch (mysqli_sql_exception $e) {
            hapus_gambar($file_baru); // batalkan file baru jika database gagal
            $error = "Gagal menyimpan data ke database!";
        }
    }
}

// Ambil gambar QRIS saat ini
$current_qris = ambil_setting($conn, 'qris_image') ?: 'default_qris.png';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan QRIS - Admin Kantin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

    <header>
        <h1>Pengaturan QRIS</h1>
        <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
    </header>

    <div class="container">
        <?php include 'nav.php'; ?>

        <div class="form-card" style="max-width: 500px; margin: 0 auto;">
            <h3 style="margin-bottom: 15px;">Kelola Gambar QRIS Kantin</h3>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert" style="background: #d4edda; color: #155724;"><?= htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <div style="text-align: center; margin-bottom: 20px;">
                <p style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 10px;">Gambar QRIS Saat Ini:</p>
                <div style="width: 200px; height: 200px; margin: 0 auto; border: 2px dashed #ccc; border-radius: 8px; padding: 5px; background: #fff;">
                    <img src="../uploads/<?= htmlspecialchars($current_qris); ?>"
                         alt="QRIS Kantin"
                         style="width: 100%; height: 100%; object-fit: contain;"
                         onerror="this.onerror=null;this.src='<?= $placeholder; ?>';">
                </div>
            </div>

            <form action="" method="POST" enctype="multipart/form-data" style="margin-top: 15px;">
                <div class="form-group">
                    <label>Upload QRIS Baru (JPG/PNG/WEBP, Max 2MB)</label>
                    <input type="file" name="qris" accept=".jpg,.jpeg,.png,.webp" required onchange="previewImage(this);">
                    <img id="img-preview" class="preview-img" alt="Preview QRIS Baru" style="display:none; width: 150px; height: 150px; object-fit: contain; margin: 10px auto;">
                </div>

                <button type="submit" class="btn" style="width: 100%;">Simpan QRIS Baru</button>
            </form>
        </div>
    </div>

    <script src="../js/script.js"></script>
</body>
</html>