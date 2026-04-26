<?php
// controllers/AuthController.php - Logika Login

require_once __DIR__ . '/../public/koneksi.php';
require_once __DIR__ . '/../models/UserModel.php';

$model = new UserModel($conn);
$error = '';

// Sudah login via SESSION
if (!empty($_SESSION['user_id'])) {
    header('Location: index.php?page=barang');
    exit;
}

// Cek cookie remember_token
if (empty($_SESSION['user_id']) && !empty($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];
    $user  = $model->getUserByToken($token);

    if ($user) {
        $_SESSION['user_id']  = $user['id'];
        $_SESSION['username'] = $user['username'];

        // Rolling cookie — perbarui token
        $new_token = bin2hex(random_bytes(32));
        $expire    = time() + (86400 * 30);
        setcookie('remember_token', $new_token, [
            'expires'  => $expire,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        $model->saveToken($user['id'], $new_token);

        header('Location: index.php?page=barang');
        exit;
    } else {
        setcookie('remember_token', '', time() - 3600, '/');
    }
}

// Proses form login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username    = trim($_POST['username'] ?? '');
    $password    = trim($_POST['password'] ?? '');
    $remember_me = isset($_POST['remember_me']);

    if (empty($username) || empty($password)) {
        $error = 'Username dan password wajib diisi.';
    } else {
        $user = $model->getUserByUsername($username);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];

            if ($remember_me) {
                $token  = bin2hex(random_bytes(32));
                $expire = time() + (86400 * 30);
                setcookie('remember_token', $token, [
                    'expires'  => $expire,
                    'path'     => '/',
                    'httponly' => true,
                    'samesite' => 'Lax',
                ]);
                $model->saveToken($user['id'], $token);
            }

            $redirect = $_SESSION['redirect_after_login'] ?? 'index.php?page=barang';
            unset($_SESSION['redirect_after_login']);
            header('Location: ' . $redirect);
            exit;
        } else {
            $error = 'Username atau password salah. Coba lagi.';
        }
    }
}

require_once __DIR__ . '/../views/auth/login.php';
?>