<?php
// controllers/TambahController.php - Logika tambah barang

require_once __DIR__ . '/../public/koneksi.php';
require_once __DIR__ . '/../models/BarangModel.php';

$model      = new BarangModel($conn);
$errors     = [];
$upload_dir = __DIR__ . '/../uploads/thumbnails/';
$data       = [
    'nama_barang'   => '',
    'jumlah'        => '',
    'harga'         => '',
    'tanggal_masuk' => date('Y-m-d'),
];

if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. Ambil & sanitasi input
    $data['nama_barang']   = trim($_POST['nama_barang']   ?? '');
    $data['jumlah']        = trim($_POST['jumlah']        ?? '');
    $data['harga']         = trim($_POST['harga']         ?? '');
    $data['tanggal_masuk'] = trim($_POST['tanggal_masuk'] ?? '');

    // 2. Validasi input sisi server
    if (empty($data['nama_barang'])) {
        $errors[] = 'Nama barang wajib diisi.';
    } elseif (strlen($data['nama_barang']) > 255) {
        $errors[] = 'Nama barang maksimal 255 karakter.';
    }

    if ($data['jumlah'] === '' || !ctype_digit($data['jumlah'])) {
        $errors[] = 'Jumlah harus berupa angka bulat positif.';
    } elseif ((int)$data['jumlah'] < 0) {
        $errors[] = 'Jumlah tidak boleh negatif.';
    }

    if ($data['harga'] === '' || !is_numeric($data['harga'])) {
        $errors[] = 'Harga harus berupa angka positif.';
    } elseif ((float)$data['harga'] < 0) {
        $errors[] = 'Harga tidak boleh negatif.';
    }

    if (empty($data['tanggal_masuk'])) {
        $errors[] = 'Tanggal masuk wajib diisi.';
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['tanggal_masuk'])) {
        $errors[] = 'Format tanggal tidak valid.';
    }

    // 3. Proses upload gambar
    $nama_file_gambar = null;

    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] !== UPLOAD_ERR_NO_FILE) {
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
                $ext              = $mime_map[$mime];
                $nama_file_gambar = uniqid('barang_', true) . '.' . $ext;
            }
        }
    }

    // 4. Simpan ke database jika tidak ada error
    if (empty($errors)) {
        if ($nama_file_gambar !== null) {
            if (!move_uploaded_file($_FILES['gambar']['tmp_name'], $upload_dir . $nama_file_gambar)) {
                $errors[] = 'Gagal menyimpan file gambar ke server.';
                $nama_file_gambar = null;
            }
        }

        if (empty($errors)) {
            $model->tambahBarang(
                $data['nama_barang'],
                $data['jumlah'],
                $data['harga'],
                $data['tanggal_masuk'],
                $nama_file_gambar
            );

            $_SESSION['flash'] = [
                'type' => 'success',
                'msg'  => '✅ Barang "' . htmlspecialchars($data['nama_barang']) . '" berhasil ditambahkan!',
            ];
            header('Location: index.php?page=barang');
            exit;
        }
    }
}

require_once __DIR__ . '/../views/barang/tambah.php';
?>