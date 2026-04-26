<?php

include 'koneksi.php';
session_start();

// Prepared Statement — Validasi ID dari URL
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'ID tidak valid.'];
    header('Location: index.php');
    exit;
}

// Prepared Statement — SELECT nama & gambar sebelum dihapus
$stmt = $conn->prepare("SELECT nama_barang, gambar FROM barang WHERE id = :id");
$stmt->execute([':id' => $id]);
$barang = $stmt->fetch();

if (!$barang) {
    $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Data barang tidak ditemukan.'];
    header('Location: index.php');
    exit;
}

// Prepared Statement — DELETE
$stmt = $conn->prepare("DELETE FROM barang WHERE id = :id");
$stmt->execute([':id' => $id]);

// Hapus file gambar dari server jika ada
if (!empty($barang['gambar'])) {
    $file_path = __DIR__ . '/uploads/' . $barang['gambar'];
    if (file_exists($file_path)) {
        unlink($file_path);
    }
}

$_SESSION['flash'] = [
    'type' => 'success',
    'msg'  => 'Barang "' . htmlspecialchars($barang['nama_barang']) . '" berhasil dihapus dari inventaris.',
];
header('Location: index.php');
exit;