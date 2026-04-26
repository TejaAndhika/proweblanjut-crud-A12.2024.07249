<?php
// controllers/BarangController.php - Tampilkan daftar barang

require_once __DIR__ . '/../public/koneksi.php';
require_once __DIR__ . '/../models/BarangModel.php';
require_once __DIR__ . '/../models/UserModel.php';

$userModel = new UserModel($conn);

// Cek cookie remember_token jika session kosong
if (empty($_SESSION['user_id']) && !empty($_COOKIE['remember_token'])) {
    $user = $userModel->getUserByToken($_COOKIE['remember_token']);
    if ($user) {
        $_SESSION['user_id']  = $user['id'];
        $_SESSION['username'] = $user['username'];

        $new_token = bin2hex(random_bytes(32));
        setcookie('remember_token', $new_token, [
            'expires'  => time() + (86400 * 30),
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        $userModel->saveToken($user['id'], $new_token);
    } else {
        setcookie('remember_token', '', time() - 3600, '/');
        header('Location: index.php?page=login');
        exit;
    }
}

$model       = new BarangModel($conn);
$barang_list = $model->getAllBarang();

// Statistik
$total_item  = count($barang_list);
$total_stok  = array_sum(array_column($barang_list, 'jumlah'));
$total_nilai = array_sum(array_map(fn($b) => $b['jumlah'] * $b['harga'], $barang_list));

// Flash message
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

require_once __DIR__ . '/../views/barang/daftar.php';
?>