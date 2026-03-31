<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Simpan username untuk pesan perpisahan (opsional)
$username = $_SESSION['username'] ?? 'Pengguna';

// 1. Hapus semua variabel session
$_SESSION = [];

// 2. Hapus cookie session jika ada
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Hancurkan session sepenuhnya
session_destroy();

// 4. Redirect ke halaman login
header('Location: login.php');
exit;