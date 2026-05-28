<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

include '../public/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'status'  => 'gagal',
        'message' => 'Metode tidak diizinkan. Gunakan GET.'
    ]);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT * FROM barang ORDER BY id DESC");
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($data) > 0) {
        echo json_encode([
            'status'  => 'sukses',
            'message' => 'Data berhasil diambil',
            'total'   => count($data),
            'data'    => $data
        ]);
    } else {
        echo json_encode([
            'status'  => 'sukses',
            'message' => 'Belum ada data barang',
            'total'   => 0,
            'data'    => []
        ]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'gagal',
        'message' => 'Terjadi kesalahan server: ' . $e->getMessage()
    ]);
}
?>