<?php
// controllers/LogoutController.php - Proses Logout

require_once __DIR__ . '/../public/koneksi.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Hapus cookie remember_token dari database dan browser
if (!empty($_COOKIE['remember_token'])) {
    // Hapus token dari database
    $stmt = $conn->prepare("UPDATE users SET remember_token = NULL WHERE remember_token = :token");
    $stmt->execute([':token' => $_COOKIE['remember_token']]);

    // Hapus cookie dari browser
    setcookie('remember_token', '', [
        'expires'  => time() - 3600,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

// 2. Hancurkan semua data session
$_SESSION = [];

// 3. Hapus cookie session dari browser
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. Hancurkan session
session_destroy();

// 5. Redirect ke halaman login
header('Location: index.php?page=login');
exit;