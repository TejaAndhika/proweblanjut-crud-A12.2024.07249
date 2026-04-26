<?php
// controllers/RegisterController.php - Logika Registrasi

require_once __DIR__ . '/../public/koneksi.php';
require_once __DIR__ . '/../models/UserModel.php';

$model   = new UserModel($conn);
$errors  = [];
$success = '';
$data    = ['username' => ''];

// Jika sudah login, langsung ke barang
if (!empty($_SESSION['user_id'])) {
    header('Location: index.php?page=barang');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data['username'] = trim($_POST['username']         ?? '');
    $password         = trim($_POST['password']         ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    // Validasi
    if (empty($data['username'])) {
        $errors[] = 'Username wajib diisi.';
    } elseif (strlen($data['username']) < 3) {
        $errors[] = 'Username minimal 3 karakter.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $data['username'])) {
        $errors[] = 'Username hanya boleh huruf, angka, dan underscore (_).';
    }

    if (empty($password)) {
        $errors[] = 'Password wajib diisi.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password minimal 6 karakter.';
    }

    if ($password !== $confirm_password) {
        $errors[] = 'Konfirmasi password tidak cocok.';
    }

    // Cek username sudah dipakai
    if (empty($errors) && $model->isUsernameTaken($data['username'])) {
        $errors[] = 'Username "' . htmlspecialchars($data['username']) . '" sudah digunakan.';
    }

    // Simpan ke database
    if (empty($errors)) {
        $model->registerUser($data['username'], $password);
        $success  = 'Akun berhasil dibuat! Silakan login menggunakan akun baru kamu.';
        $data     = ['username' => ''];
    }
}

require_once __DIR__ . '/../views/auth/register.php';
?>