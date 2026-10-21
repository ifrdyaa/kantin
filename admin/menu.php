<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit();
}

require_once '../config/database.php';
require_once 'helpers.php';

$halaman_aktif = 'menu';
$error = '';
$edit_data = null;

// Gambar abu-abu lokal sebagai pengganti via.placeholder.com (tanpa internet)
$placeholder = "data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2250%22 height=%2250%22%3E%3Crect width=%22100%25%22 height=%22100%25%22 fill=%22%23ddd%22/%3E%3C/svg%3E";

// Handle Simpan / Update Data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id    = intval($_POST['id'] ?? 0);
    $nama  = trim($_POST['nama'] ?? '');
    $harga = intval($_POST['harga'] ?? 0);
    $stok  = intval($_POST['stok'] ?? 0);

    if ($nama === '') {
        $error = "Nama menu wajib diisi!";
    } elseif ($harga < 0 || $stok < 0) {
        $error = "Harga dan stok tidak boleh negatif!";
    }

    // Upload foto (opsional)
    $foto_baru = null;
    if ($error === '') {
        [$foto_baru, $pesan_upload] = upload_gambar($_FILES['foto'] ?? ['error' => UPLOAD_ERR_NO_FILE], 'menu_');
        if ($pesan_upload) {
            $error = $pesan_upload;
        }
    }

    if ($error === '') {
        try {
            if ($id > 0) {
                // EDIT MENU
                $foto_lama = null;
                if ($foto_baru) {
                    // Ambil foto lama supaya bisa dihapus setelah update berhasil
                    $s = mysqli_prepare($conn, "SELECT foto FROM menu WHERE id = ?");
                    mysqli_stmt_bind_param($s, "i", $id);
                    mysqli_stmt_execute($s);
                    $r = mysqli_fetch_assoc(mysqli_stmt_get_result($s));
                    $foto_lama = $r['foto'] ?? null;

                    $stmt = mysqli_prepare($conn, "UPDATE menu SET nama = ?, harga = ?, stok = ?, foto = ? WHERE id = ?");
                    mysqli_stmt_bind_param($stmt, "siisi", $nama, $harga, $stok, $foto_baru, $id);
                } else {
                    $stmt = mysqli_prepare($conn, "UPDATE menu SET nama = ?, harga = ?, stok = ? WHERE id = ?");
                    mysqli_stmt_bind_param($stmt, "siii", $nama, $harga, $stok, $id);
                }
                mysqli_stmt_execute($stmt);

                if ($foto_lama) {
                    hapus_gambar($foto_lama);
                }
            } else {
                // TAMBAH MENU
                $foto_final = $foto_baru ?? 'default.jpg';
                $stmt = mysqli_prepare($conn, "INSERT INTO menu (nama, harga, stok, foto) VALUES (?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt, "siis", $nama, $harga, $stok, $foto_final);
                mysqli_stmt_execute($stmt);
            }

            header("Location: menu.php");
            exit();
        } catch (mysqli_sql_exception $e) {
            if ($foto_baru) {
                hapus_gambar($foto_baru); // jangan tinggalkan file yatim
            }
            $error = "Gagal menyimpan data ke database!";
        }
    }
}

// Sub-fitur Edit: ambil data jika ID dikirim lewat URL
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $stmt = mysqli_prepare($conn, "SELECT * FROM menu WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $edit_id);
    mysqli_stmt_execute($stmt);
    $edit_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) ?: null;
}

// Ambil semua menu
$result = mysqli_query($conn, "SELECT * FROM menu ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Menu - Admin Kantin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

    <header>
        <h1>Kelola Menu</h1>
        <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
    </header>

    <div class="container">
        <?php include 'nav.php'; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Form Tambah/Edit Menu -->
        <div class="form-card" style="max-width: 100%; margin-bottom: 25px;">
            <h3><?= $edit_data ? 'Edit Menu' : 'Tambah Menu Baru'; ?></h3>
            <form name="menuForm" method="POST" action="" enctype="multipart/form-data" onsubmit="return validateMenuForm();" style="margin-top: 15px;">
                <input type="hidden" name="id" value="<?= $edit_data ? (int) $edit_data['id'] : '0'; ?>">

                <div class="form-group">
                    <label>Nama Menu</label>
                    <input type="text" name="nama" value="<?= $edit_data ? htmlspecialchars($edit_data['nama']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label>Harga (Rp)</label>
                    <input type="number" name="harga" value="<?= $edit_data ? (int) $edit_data['harga'] : ''; ?>" min="0" required>
                </div>

                <div class="form-group">
                    <label>Stok</label>
                    <input type="number" name="stok" value="<?= $edit_data ? (int) $edit_data['stok'] : ''; ?>" min="0" required>
                </div>

                <div class="form-group">
                    <label>Foto Menu (JPG/PNG/WEBP, Max 2MB)</label>
                    <input type="file" name="foto" accept=".jpg,.jpeg,.png,.webp" onchange="previewImage(this);">
                    <img id="img-preview" class="preview-img" alt="Preview Foto">
                </div>

                <button type="submit" class="btn"><?= $edit_data ? 'Update Menu' : 'Tambah Menu'; ?></button>
                <?php if ($edit_data): ?>
                    <a href="menu.php" class="btn btn-danger" style="margin-left: 5px;">Batal</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Tabel Data Menu -->
        <h3>Daftar Menu Saat Ini</h3>
        <div class="table-responsive" style="margin-top: 10px;">
            <table>
                <thead>
                    <tr>
                        <th>Foto</th>
                        <th>Nama Menu</th>
                        <th>Harga</th>
                        <th>Stok</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td>
                                    <img src="../uploads/<?= htmlspecialchars($row['foto']); ?>"
                                         width="50" height="50" style="object-fit: cover; border-radius: 4px;"
                                         alt="<?= htmlspecialchars($row['nama']); ?>"
                                         onerror="this.onerror=null;this.src='<?= $placeholder; ?>';">
                                </td>
                                <td><strong><?= htmlspecialchars($row['nama']); ?></strong></td>
                                <td>Rp <?= number_format($row['harga'], 0, ',', '.'); ?></td>
                                <td><?= (int) $row['stok']; ?></td>
                                <td>
                                    <a href="menu.php?edit=<?= (int) $row['id']; ?>" class="btn btn-sm">Edit</a>
                                    <a href="javascript:void(0);" onclick="konfirmasiHapus('menu_delete.php?id=<?= (int) $row['id']; ?>');" class="btn btn-danger btn-sm">Hapus</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center;">Belum ada menu.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="../js/script.js"></script>
</body>
</html>