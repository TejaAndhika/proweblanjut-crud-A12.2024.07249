<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");

include '../public/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status'  => 'gagal',
        'message' => 'Metode tidak diizinkan. Gunakan POST.'
    ]);
    exit;
}

$contentType = $_SERVER['CONTENT_TYPE'] ?? '';

if (str_contains($contentType, 'application/json')) {
    $body        = json_decode(file_get_contents('php://input'), true);
    $nama_barang   = trim($body['nama_barang']   ?? '');
    $jumlah        = trim($body['jumlah']        ?? '');
    $harga         = trim($body['harga']         ?? '');
    $tanggal_masuk = trim($body['tanggal_masuk'] ?? '');
} else {
    $nama_barang   = trim($_POST['nama_barang']   ?? '');
    $jumlah        = trim($_POST['jumlah']        ?? '');
    $harga         = trim($_POST['harga']         ?? '');
    $tanggal_masuk = trim($_POST['tanggal_masuk'] ?? '');
}

$errors = [];
if (empty($nama_barang)) {
    $errors[] = 'nama_barang wajib diisi.';
}
if ($jumlah === '' || !is_numeric($jumlah) || (int)$jumlah < 0) {
    $errors[] = 'jumlah harus berupa angka positif.';
}
if ($harga === '' || !is_numeric($harga) || (float)$harga < 0) {
    $errors[] = 'harga harus berupa angka positif.';
}
if (empty($tanggal_masuk)) {
    $errors[] = 'tanggal_masuk wajib diisi (format: YYYY-MM-DD).';
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode([
        'status'  => 'gagal',
        'message' => 'Validasi gagal',
        'errors'  => $errors
    ]);
    exit;
}

try {
    $stmt = $conn->prepare(
        "INSERT INTO barang (nama_barang, jumlah, harga, tanggal_masuk)
         VALUES (:nama_barang, :jumlah, :harga, :tanggal_masuk)"
    );
    $stmt->execute([
        ':nama_barang'   => $nama_barang,
        ':jumlah'        => (int)$jumlah,
        ':harga'         => (float)$harga,
        ':tanggal_masuk' => $tanggal_masuk,
    ]);

    http_response_code(201);
    echo json_encode([
        'status'  => 'sukses',
        'message' => 'Barang berhasil ditambahkan',
        'data'    => [
            'id'           => $conn->lastInsertId(),
            'nama_barang'  => $nama_barang,
            'jumlah'       => (int)$jumlah,
            'harga'        => (float)$harga,
            'tanggal_masuk'=> $tanggal_masuk,
        ]
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'gagal',
        'message' => 'Terjadi kesalahan server: ' . $e->getMessage()
    ]);
}
?>