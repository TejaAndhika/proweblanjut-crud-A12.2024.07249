<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, DELETE");

include '../public/koneksi.php';

if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'DELETE'])) {
    http_response_code(405);
    echo json_encode([
        'status'  => 'gagal',
        'message' => 'Metode tidak diizinkan. Gunakan POST atau DELETE.'
    ]);
    exit;
}

$contentType = $_SERVER['CONTENT_TYPE'] ?? '';

if (str_contains($contentType, 'application/json')) {
    $body = json_decode(file_get_contents('php://input'), true);
    $id   = $body['id'] ?? null;
} else {
    $raw = file_get_contents('php://input');
    parse_str($raw, $data);
    $source = $_SERVER['REQUEST_METHOD'] === 'DELETE' ? $data : $_POST;
    $id     = $source['id'] ?? null;
}

if (empty($id) || !is_numeric($id)) {
    http_response_code(400);
    echo json_encode([
        'status'  => 'gagal',
        'message' => 'id wajib diisi dan harus berupa angka.'
    ]);
    exit;
}

try {
    $cek = $conn->prepare("SELECT id, nama_barang FROM barang WHERE id = :id");
    $cek->execute([':id' => $id]);
    $barang = $cek->fetch();

    if (!$barang) {
        http_response_code(404);
        echo json_encode([
            'status'  => 'gagal',
            'message' => 'Barang dengan id ' . $id . ' tidak ditemukan.'
        ]);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM barang WHERE id = :id");
    $stmt->execute([':id' => (int)$id]);

    echo json_encode([
        'status'  => 'sukses',
        'message' => 'Barang "' . $barang['nama_barang'] . '" berhasil dihapus.',
        'data'    => [
            'id'          => (int)$id,
            'nama_barang' => $barang['nama_barang']
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