<?php
include 'koneksi.php';
session_start();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'ID tidak valid.'];
    header('Location: index.php');
    exit;
}

// Ambil nama barang terlebih dahulu (untuk pesan flash)
$stmt = $conn->prepare("SELECT nama_barang FROM barang WHERE id = :id");
$stmt->execute([':id' => $id]);
$barang = $stmt->fetch();

if (!$barang) {
    $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Data barang tidak ditemukan.'];
    header('Location: index.php');
    exit;
}

// Hapus data dari database
$stmt = $conn->prepare("DELETE FROM barang WHERE id = :id");
$stmt->execute([':id' => $id]);

$_SESSION['flash'] = [
    'type' => 'success',
    'msg'  => 'Barang "' . htmlspecialchars($barang['nama_barang']) . '" berhasil dihapus dari inventaris.'
];
header('Location: index.php');
exit;