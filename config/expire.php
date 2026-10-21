<?php
// Membatalkan semua pesanan yang sudah lewat batas waktu bayar dan mengembalikan stoknya.
// Dipanggil dari: checkout.php, pembayaran.php, check_status.php, index.php, admin/pesanan.php.
// Perbandingan waktu memakai jam MySQL (NOW()), sama seperti saat pesanan dibuat.

function batalkan_pesanan_kedaluwarsa(mysqli $conn): void
{
    mysqli_begin_transaction($conn);

    try {
        // Kunci baris pesanan pending yang sudah kedaluwarsa
        $res = mysqli_query(
            $conn,
            "SELECT id FROM pesanan
             WHERE status = 'pending' AND expire_at IS NOT NULL AND expire_at <= NOW()
             FOR UPDATE"
        );

        $ids = [];
        while ($r = mysqli_fetch_assoc($res)) {
            $ids[] = (int) $r['id'];
        }

        if ($ids) {
            $stmt_items  = mysqli_prepare($conn, "SELECT menu_id, jumlah FROM detail_pesanan WHERE pesanan_id = ?");
            $stmt_stok   = mysqli_prepare($conn, "UPDATE menu SET stok = stok + ? WHERE id = ?");
            $stmt_status = mysqli_prepare($conn, "UPDATE pesanan SET status = 'expired' WHERE id = ?");

            foreach ($ids as $pesanan_id) {
                // Kembalikan stok tiap menu dalam pesanan
                mysqli_stmt_bind_param($stmt_items, "i", $pesanan_id);
                mysqli_stmt_execute($stmt_items);
                $details = mysqli_stmt_get_result($stmt_items);

                while ($item = mysqli_fetch_assoc($details)) {
                    $qty     = (int) $item['jumlah'];
                    $menu_id = (int) $item['menu_id'];
                    mysqli_stmt_bind_param($stmt_stok, "ii", $qty, $menu_id);
                    mysqli_stmt_execute($stmt_stok);
                }

                // Tandai pesanan expired
                mysqli_stmt_bind_param($stmt_status, "i", $pesanan_id);
                mysqli_stmt_execute($stmt_status);
            }
        }

        mysqli_commit($conn);
    } catch (Exception $e) {
        mysqli_rollback($conn);
    }
}