<?php
session_start();
require_once '../config/database.php';

// Jika sudah login, lempar ke dashboard admin
if (isset($_SESSION['admin_logged_in'])) {
    header("Location: index.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Prepared statement: input pengguna tidak dimasukkan langsung ke query
    $stmt = mysqli_prepare($conn, "SELECT id, password, nama_lengkap FROM users WHERE username = ?");
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    // Hanya password_verify: password di database harus berupa hash dari password_hash()
    if ($row && password_verify($password, $row['password'])) {
        session_regenerate_id(true); // cegah session fixation
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_name'] = $row['nama_lengkap'];
        header("Location: index.php");
        exit();
    }

    // Pesan sengaja disamakan agar tidak membocorkan username mana yang valid
    $error = 'Username atau password salah!';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Penjual - Kantin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

    <div class="container" style="margin-top: 50px;">
        <div class="form-card">
            <h2 style="margin-bottom: 20px; text-align: center;">Login Penjual</h2>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" required autocomplete="off">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit" class="btn" style="width: 100%;">Login</button>
            </form>
            <div style="text-align: center; margin-top: 15px;">
                <a href="../index.php" style="color: var(--text-muted); font-size: 0.85rem; text-decoration: none;">&larr; Kembali ke Kantin</a>
            </div>
        </div>
    </div>

</body>
</html>