<?php
// controllers/HapusController.php - Logika hapus barang

require_once __DIR__ . '/../public/koneksi.php';
require_once __DIR__ . '/../models/BarangModel.php';

$model      = new BarangModel($conn);
$upload_dir = __DIR__ . '/../uploads/thumbnails/';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'ID tidak valid.'];
    header('Location: index.php?page=barang');
    exit;
}

$barang = $model->getBarangById($id);

if (!$barang) {
    $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Data barang tidak ditemukan.'];
    header('Location: index.php?page=barang');
    exit;
}

// Hapus gambar dari folder jika ada
if (!empty($barang['gambar'])) {
    $file_path = $upload_dir . $barang['gambar'];
    if (file_exists($file_path)) {
        unlink($file_path);
    }
}

$model->hapusBarang($id);

$_SESSION['flash'] = [
    'type' => 'success',
    'msg'  => 'Barang "' . htmlspecialchars($barang['nama_barang']) . '" berhasil dihapus.',
];

header('Location: index.php?page=barang');
exit;
?>