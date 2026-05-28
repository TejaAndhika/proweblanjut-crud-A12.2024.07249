<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, PUT");

include '../public/koneksi.php';

if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT'])) {
    http_response_code(405);
    echo json_encode([
        'status'  => 'gagal',
        'message' => 'Metode tidak diizinkan. Gunakan POST atau PUT.'
    ]);
    exit;
}

$contentType = $_SERVER['CONTENT_TYPE'] ?? '';

if (str_contains($contentType, 'application/json')) {
    $body          = json_decode(file_get_contents('php://input'), true);
    $id            = $body['id']            ?? null;
    $nama_barang   = trim($body['nama_barang']   ?? '');
    $jumlah        = trim($body['jumlah']        ?? '');
    $harga         = trim($body['harga']         ?? '');
    $tanggal_masuk = trim($body['tanggal_masuk'] ?? '');
} else {
    $raw = file_get_contents('php://input');
    parse_str($raw, $putData);
    $source        = $_SERVER['REQUEST_METHOD'] === 'PUT' ? $putData : $_POST;
    $id            = $source['id']            ?? null;
    $nama_barang   = trim($source['nama_barang']   ?? '');
    $jumlah        = trim($source['jumlah']        ?? '');
    $harga         = trim($source['harga']         ?? '');
    $tanggal_masuk = trim($source['tanggal_masuk'] ?? '');
}

$errors = [];
if (empty($id) || !is_numeric($id)) {
    $errors[] = 'id wajib diisi dan harus berupa angka.';
}
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
    $cek = $conn->prepare("SELECT id FROM barang WHERE id = :id");
    $cek->execute([':id' => $id]);
    if (!$cek->fetch()) {
        http_response_code(404);
        echo json_encode([
            'status'  => 'gagal',
            'message' => 'Barang dengan id ' . $id . ' tidak ditemukan.'
        ]);
        exit;
    }

    $stmt = $conn->prepare(
        "UPDATE barang
         SET nama_barang   = :nama_barang,
             jumlah        = :jumlah,
             harga         = :harga,
             tanggal_masuk = :tanggal_masuk
         WHERE id = :id"
    );
    $stmt->execute([
        ':id'            => (int)$id,
        ':nama_barang'   => $nama_barang,
        ':jumlah'        => (int)$jumlah,
        ':harga'         => (float)$harga,
        ':tanggal_masuk' => $tanggal_masuk,
    ]);

    echo json_encode([
        'status'  => 'sukses',
        'message' => 'Barang berhasil diperbarui',
        'data'    => [
            'id'            => (int)$id,
            'nama_barang'   => $nama_barang,
            'jumlah'        => (int)$jumlah,
            'harga'         => (float)$harga,
            'tanggal_masuk' => $tanggal_masuk,
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