<?php
// controllers/EditController.php - Logika edit barang

require_once __DIR__ . '/../public/koneksi.php';
require_once __DIR__ . '/../models/BarangModel.php';

$model      = new BarangModel($conn);
$upload_dir = __DIR__ . '/../uploads/thumbnails/';
$errors     = [];

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: index.php?page=barang');
    exit;
}

$barang = $model->getBarangById($id);
if (!$barang) {
    $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Data barang tidak ditemukan.'];
    header('Location: index.php?page=barang');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. Ambil & sanitasi input
    $nama_barang   = trim($_POST['nama_barang']   ?? '');
    $jumlah        = trim($_POST['jumlah']        ?? '');
    $harga         = trim($_POST['harga']         ?? '');
    $tanggal_masuk = trim($_POST['tanggal_masuk'] ?? '');
    $hapus_gambar  = isset($_POST['hapus_gambar']) && $_POST['hapus_gambar'] === '1';

    // 2. Validasi
    if (empty($nama_barang)) {
        $errors[] = 'Nama barang wajib diisi.';
    } elseif (strlen($nama_barang) > 255) {
        $errors[] = 'Nama barang maksimal 255 karakter.';
    }

    if ($jumlah === '' || !ctype_digit($jumlah)) {
        $errors[] = 'Jumlah harus berupa angka bulat positif.';
    } elseif ((int)$jumlah < 0) {
        $errors[] = 'Jumlah tidak boleh negatif.';
    }

    if ($harga === '' || !is_numeric($harga)) {
        $errors[] = 'Harga harus berupa angka positif.';
    } elseif ((float)$harga < 0) {
        $errors[] = 'Harga tidak boleh negatif.';
    }

    if (empty($tanggal_masuk)) {
        $errors[] = 'Tanggal masuk wajib diisi.';
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal_masuk)) {
        $errors[] = 'Format tanggal tidak valid.';
    }

    // 3. Tentukan nilai gambar akhir
    $nama_file_gambar = $barang['gambar']; // default: gambar lama
    $ada_file_baru    = isset($_FILES['gambar']) && $_FILES['gambar']['error'] !== UPLOAD_ERR_NO_FILE;

    if ($ada_file_baru) {
        $file     = $_FILES['gambar'];
        $max_size = 2 * 1024 * 1024;

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Terjadi kesalahan saat mengunggah gambar (kode: ' . $file['error'] . ').';
        } else {
            $finfo    = new finfo(FILEINFO_MIME_TYPE);
            $mime     = $finfo->file($file['tmp_name']);
            $mime_map = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/gif'  => 'gif',
                'image/webp' => 'webp',
            ];

            if (!array_key_exists($mime, $mime_map)) {
                $errors[] = 'Tipe file tidak diizinkan. Gunakan JPG, PNG, GIF, atau WebP.';
            } elseif ($file['size'] > $max_size) {
                $errors[] = 'Ukuran gambar maksimal 2 MB.';
            } else {
                $nama_file_gambar = uniqid('barang_', true) . '.' . $mime_map[$mime];
            }
        }
    } elseif ($hapus_gambar) {
        $nama_file_gambar = null;
    }

    // 4. Eksekusi jika tidak ada error
    if (empty($errors)) {
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

        if ($ada_file_baru && $nama_file_gambar !== $barang['gambar']) {
            if (move_uploaded_file($file['tmp_name'], $upload_dir . $nama_file_gambar)) {
                // Hapus gambar lama
                if ($barang['gambar'] && file_exists($upload_dir . $barang['gambar'])) {
                    unlink($upload_dir . $barang['gambar']);
                }
            } else {
                $errors[] = 'Gagal menyimpan file gambar ke server.';
                $nama_file_gambar = $barang['gambar'];
            }
        } elseif ($hapus_gambar && $barang['gambar']) {
            $file_path = $upload_dir . $barang['gambar'];
            if (file_exists($file_path)) unlink($file_path);
        }

        if (empty($errors)) {
            $model->editBarang($id, $nama_barang, $jumlah, $harga, $tanggal_masuk, $nama_file_gambar);
            $_SESSION['flash'] = ['type' => 'success', 'msg' => '✅ Data barang berhasil diperbarui!'];
            header('Location: index.php?page=barang');
            exit;
        }
    }

    // Kembalikan nilai ke form jika ada error
    $barang['nama_barang']   = $nama_barang;
    $barang['jumlah']        = $jumlah;
    $barang['harga']         = $harga;
    $barang['tanggal_masuk'] = $tanggal_masuk;
}

$punya_gambar = !empty($barang['gambar']);

require_once __DIR__ . '/../views/barang/edit.php';
?>