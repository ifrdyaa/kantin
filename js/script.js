// Konfirmasi Hapus
function konfirmasiHapus(url) {
    if (confirm("Apakah Anda yakin ingin menghapus menu ini?")) {
        window.location.href = url;
    }
}

// Preview Foto sebelum diupload
function previewImage(input) {
    const preview = document.getElementById('img-preview');
    if (input.files && input.files[0]) {
        const file = input.files[0];
        
        // Validasi Ukuran Ukuran Max 2MB
        if (file.size > 2 * 1024 * 1024) {
            alert("Ukuran file foto maksimal 2 MB!");
            input.value = '';
            preview.style.display = 'none';
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        }
        reader.readAsDataURL(file);
    }
}

// Validasi Form Client-Side
function validateMenuForm() {
    const nama = document.forms["menuForm"]["nama"].value;
    const harga = document.forms["menuForm"]["harga"].value;
    const stok = document.forms["menuForm"]["stok"].value;

    if (nama.trim() === "" || harga === "" || stok === "") {
        alert("Semua field wajib diisi!");
        return false;
    }
    if (harga < 0 || stok < 0) {
        alert("Harga dan stok tidak boleh bernilai negatif!");
        return false;
    }
    return true;
}